<?php

namespace App\Http\Controllers\Puchase;

use App\Http\Controllers\Concerns\ApiErrorResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\PurchaseDetailResource;
use App\Models\Puchase;
use App\Models\PuchaseDetail;
use App\Services\Finance\DocumentTotalsService;
use App\Services\Inventory\ProductWarehouseStockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;

class PuchaseDetailController extends Controller
{
    use ApiErrorResponse;

    public function __construct(
        private DocumentTotalsService $documentTotalsService,
        private ProductWarehouseStockService $stockService,
    )
    {
    }

    private function normalizeDetailPayload(Request $request): array
    {
        $payload = $request->all();

        if (!isset($payload['puchase_id'])) {
            $payload['puchase_id'] = $payload['purchase_id']
                ?? $payload['pushase_id']
                ?? $payload['purchace_id']
                ?? null;
        }

        if (!isset($payload['puchase_detail_id'])) {
            $payload['puchase_detail_id'] = $payload['purchase_detail_id']
                ?? $payload['pushase_detail_id']
                ?? $payload['purchace_detail_id']
                ?? null;
        }

        if (isset($payload['product_id']) && !isset($payload['product']['id'])) {
            $payload['product']['id'] = $payload['product_id'];
        }

        return $payload;
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        Gate::authorize('update', Puchase::class);

        $payload = $this->normalizeDetailPayload($request);

        $validator = Validator::make($payload, [
            'puchase_id' => ['required', 'integer', 'exists:puchases,id'],
            'product.id' => ['required', 'integer', 'exists:products,id'],
            'unit_id' => ['required', 'integer', 'exists:units,id'],
            'quantity' => ['required', 'numeric', 'min:0.01'],
            'price_unit' => ['required', 'numeric', 'min:0'],
            'total' => ['required', 'numeric', 'min:0'],
        ]);

        if ($validator->fails()) {
            return $this->errorResponse(422, 'VALIDATION_ERROR', 'Datos del detalle invalidos.', $validator->errors()->toArray());
        }

        $product = $payload['product'];
        $details = null;
        $newImport = 0;
        $newIVA = 0;
        $newTotal = 0;

        DB::transaction(function () use ($payload, $product, &$details, &$newImport, &$newIVA, &$newTotal) {
            $purchase = Puchase::where('id', $payload['puchase_id'])->lockForUpdate()->first();

            if (!$purchase) {
                return;
            }

            $details = PuchaseDetail::create([
                'puchase_id' => $payload['puchase_id'],
                'product_id' => $product['id'],
                'unit_id' => $payload['unit_id'],
                'quantity' => $payload['quantity'],
                'price_unit' => $payload['price_unit'],
                'total' => $payload['total'],
            ]);

            $totals = $this->documentTotalsService->calculateFromImport((float) $purchase->immporte + (float) $payload['total']);
            $newImport = $totals['immporte'];
            $newIVA = $totals['iva'];
            $newTotal = $totals['total'];

            $state = 1;

            if($purchase->state == 3 || $purchase->state == 2){
                $state = 2;
            }

            $purchase->update([
                'state' => $state,
                'immporte' => $newImport,
                'iva' => $newIVA,
                'total' => $newTotal
            ]);
        });

        if (!$details) {
            return $this->errorResponse(404, 'PURCHASE_NOT_FOUND', 'La compra seleccionada no existe.');
        }

        return response()->json([
            'status' => 201,
            'data' => new PurchaseDetailResource($details),
            'immporte' => $newImport,
            'importe' => $newImport,
            'iva' => $newIVA,
            'total' => $newTotal
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        Gate::authorize('update', Puchase::class);

        $payload = $this->normalizeDetailPayload($request);

        $validator = Validator::make($payload, [
            'puchase_id' => ['required', 'integer', 'exists:puchases,id'],
            'unit_id' => ['required', 'integer', 'exists:units,id'],
            'quantity' => ['required', 'numeric', 'min:0.01'],
            'price_unit' => ['required', 'numeric', 'min:0'],
            'total' => ['required', 'numeric', 'min:0'],
            'description' => ['nullable', 'string'],
        ]);

        if ($validator->fails()) {
            return $this->errorResponse(422, 'VALIDATION_ERROR', 'Datos del detalle invalidos.', $validator->errors()->toArray());
        }

        $response = null;

        DB::transaction(function () use ($payload, $id, &$response) {
            $detail = PuchaseDetail::where('id', $id)->lockForUpdate()->first();

            if (!$detail) {
                $response = $this->errorResponse(404, 'PURCHASE_DETAIL_NOT_FOUND', 'El detalle de compra no existe.');
                return;
            }

            if($detail->state != 1){
                if($detail->quantity != $payload['quantity']){
                    $response = $this->errorResponse(403, 'PURCHASE_DETAIL_QUANTITY_FORBIDDEN', 'No puedes editar la cantidad del detalle por que ya se a entregado el producto');
                    return;
                }

                if($detail->unit_id != $payload['unit_id']){
                    $response = $this->errorResponse(403, 'PURCHASE_DETAIL_UNIT_FORBIDDEN', 'No puedes editar la unidad del detalle por que ya se a entregado el producto');
                    return;
                }
            }

            $purchase = Puchase::where('id', $payload['puchase_id'])->lockForUpdate()->first();

            if (!$purchase) {
                $response = $this->errorResponse(404, 'PURCHASE_NOT_FOUND', 'La compra seleccionada no existe.');
                return;
            }

            $old_total = $detail->total;

            $detail->update([
                'puchase_id' => $payload['puchase_id'],
                'unit_id' => $payload['unit_id'],
                'quantity' => $payload['quantity'],
                'price_unit' => $payload['price_unit'],
                'total' => $payload['total'],
                'description' => $payload['description'] ?? null
            ]);

            $totals = $this->documentTotalsService->calculateFromDelta(
                (float) $purchase->immporte,
                (float) $old_total,
                (float) $payload['total']
            );
            $newImport = $totals['immporte'];
            $newIVA = $totals['iva'];
            $newTotal = $totals['total'];

            $purchase->update([
                'immporte' => $newImport,
                'iva' => $newIVA,
                'total' => $newTotal
            ]);

            $response = response()->json([
                'status' => 200,
                'data' => new PurchaseDetailResource($detail),
                'total' => $newTotal,
                'immporte' => $newImport,
                'importe' => $newImport,
                'iva' => $newIVA
            ]);
        });

        if ($response) {
            return $response;
        }

        return $this->errorResponse(500, 'PURCHASE_DETAIL_UPDATE_ERROR', 'No se pudo actualizar el detalle de compra.');
    }

    public function attention(Request $request)
    {
        Gate::authorize('update', Puchase::class);

        $payload = $this->normalizeDetailPayload($request);

        $validator = Validator::make($payload, [
            'puchase_id' => ['required', 'integer', 'exists:puchases,id'],
            'puchase_detail_id' => ['required', 'integer', 'exists:puchase_details,id'],
        ]);

        if ($validator->fails()) {
            return $this->errorResponse(422, 'VALIDATION_ERROR', 'Datos de atencion invalidos.', $validator->errors()->toArray());
        }

        date_default_timezone_set('America/Bogota');

        $puchase_id = $payload['puchase_id'];
        $puchase_detail_id = $payload['puchase_detail_id'];

        $response = null;

        DB::transaction(function () use ($puchase_id, $puchase_detail_id, &$response) {
            $purchase = Puchase::where('id', $puchase_id)->lockForUpdate()->first();
            $detail = PuchaseDetail::where('id', $puchase_detail_id)->lockForUpdate()->first();

            if (!$purchase) {
                $response = $this->errorResponse(404, 'PURCHASE_NOT_FOUND', 'La compra seleccionada no existe.');
                return;
            }

            if (!$detail) {
                $response = $this->errorResponse(404, 'PURCHASE_DETAIL_NOT_FOUND', 'El detalle de compra no existe.');
                return;
            }

            if($detail->puchase_id != $purchase->id){
                $response = $this->errorResponse(403, 'PURCHASE_DETAIL_MISMATCH', 'El detalle no pertenece a la compra seleccionada.');
                return;
            }

            if($detail->state != 1){
                $response = $this->errorResponse(403, 'PURCHASE_DETAIL_ALREADY_ATTENDED', 'No se puede atender este detalle por que ya fue atendido previamente.');
                return;
            }

            $detail->update([
                'state' => 2,
                'user_delivery' => auth('api')->user()->id,
                'date_delivery' => now()
            ]);

            $this->stockService->increaseOrCreate(
                (int) $detail->product_id,
                (int) $detail->unit_id,
                (int) $purchase->warehouse_id,
                (float) $detail->quantity
            );

            $n_details = PuchaseDetail::where('puchase_id', $puchase_id)->count();
            $n_details_attends = PuchaseDetail::where('puchase_id', $puchase_id)->where('state', 2)->count();

            $purchase->update([
                'state' => $n_details_attends == $n_details ? 3 : 2
            ]);

            $response = response()->json([
                'status' => 200,
                'data' => new PurchaseDetailResource($detail)
            ]);
        });

        return $response ?? $this->errorResponse(500, 'PURCHASE_DETAIL_ATTENTION_ERROR', 'No se pudo atender el detalle de compra.');
        
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        Gate::authorize('update', Puchase::class);

        $response = null;

        DB::transaction(function () use ($id, &$response) {
            $detail = PuchaseDetail::where('id', $id)->lockForUpdate()->first();

            if (!$detail) {
                $response = $this->errorResponse(404, 'PURCHASE_DETAIL_NOT_FOUND', 'El detalle de compra no existe.');
                return;
            }

            if($detail->state != 1){
                $response = $this->errorResponse(403, 'PURCHASE_DETAIL_DELETE_FORBIDDEN', 'No puedes eliminar el detalle por que ya se a entregado por el proveedor');
                return;
            }

            $purchase = Puchase::where('id', $detail->puchase_id)->lockForUpdate()->first();

            if (!$purchase) {
                $response = $this->errorResponse(404, 'PURCHASE_NOT_FOUND', 'La compra relacionada no existe.');
                return;
            }

            $detail->delete();

            $totals = $this->documentTotalsService->calculateFromImport((float) $purchase->immporte - (float) $detail->total);
            $newImport = $totals['immporte'];
            $newIVA = $totals['iva'];
            $newTotal = $totals['total'];

            $purchase->update([
                'immporte' => $newImport,
                'iva' => $newIVA,
                'total' => $newTotal
            ]);

            $response = response()->json([
                'status' => 200,
                'id' => $id,
                'total' => $newTotal,
                'immporte' => $newImport,
                'importe' => $newImport,
                'iva' => $newIVA
            ]);
        });

        return $response ?? $this->errorResponse(500, 'PURCHASE_DETAIL_DELETE_ERROR', 'No se pudo eliminar el detalle de compra.');
    }
}
