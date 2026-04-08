<?php

namespace App\Http\Controllers\Puchase;

use App\Http\Controllers\Controller;
use App\Http\Resources\PurchaseDetailResource;
use App\Models\ProductWarehouse;
use App\Models\Puchase;
use App\Models\PuchaseDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;

class PuchaseDetailController extends Controller
{
    private function errorResponse(int $status, string $code, string $message, array $errors = [])
    {
        $body = [
            'status' => $status,
            'code' => $code,
            'message' => $message,
        ];

        if (!empty($errors)) {
            $body['errors'] = $errors;
        }

        return response()->json($body, $status);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        Gate::authorize('update', Puchase::class);

        $validator = Validator::make($request->all(), [
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

        $product = $request->product;
        $details = null;
        $newImport = 0;
        $newIVA = 0;
        $newTotal = 0;

        DB::transaction(function () use ($request, $product, &$details, &$newImport, &$newIVA, &$newTotal) {
            $purchase = Puchase::where('id', $request->puchase_id)->lockForUpdate()->first();

            if (!$purchase) {
                return;
            }

            $details = PuchaseDetail::create([
                'puchase_id' => $request->puchase_id,
                'product_id' => $product['id'],
                'unit_id' => $request->unit_id,
                'quantity' => $request->quantity,
                'price_unit' => $request->price_unit,
                'total' => $request->total,
            ]);

            $newImport = round($purchase->immporte + $request->total, 2);
            $newIVA = round($newImport * 0.18, 2);
            $newTotal = round($newImport + $newIVA, 2);

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

        $validator = Validator::make($request->all(), [
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

        DB::transaction(function () use ($request, $id, &$response) {
            $detail = PuchaseDetail::where('id', $id)->lockForUpdate()->first();

            if (!$detail) {
                $response = $this->errorResponse(404, 'PURCHASE_DETAIL_NOT_FOUND', 'El detalle de compra no existe.');
                return;
            }

            if($detail->state != 1){
                if($detail->quantity != $request->quantity){
                    $response = $this->errorResponse(403, 'PURCHASE_DETAIL_QUANTITY_FORBIDDEN', 'No puedes editar la cantidad del detalle por que ya se a entregado el producto');
                    return;
                }

                if($detail->unit_id != $request->unit_id){
                    $response = $this->errorResponse(403, 'PURCHASE_DETAIL_UNIT_FORBIDDEN', 'No puedes editar la unidad del detalle por que ya se a entregado el producto');
                    return;
                }
            }

            $purchase = Puchase::where('id', $request->puchase_id)->lockForUpdate()->first();

            if (!$purchase) {
                $response = $this->errorResponse(404, 'PURCHASE_NOT_FOUND', 'La compra seleccionada no existe.');
                return;
            }

            $old_total = $detail->total;

            $detail->update([
                'puchase_id' => $request->puchase_id,
                'unit_id' => $request->unit_id,
                'quantity' => $request->quantity,
                'price_unit' => $request->price_unit,
                'total' => $request->total,
                'description' => $request->description
            ]);

            $newImport = round(($purchase->immporte - $old_total) + $request->total, 2);
            $newIVA = round($newImport * 0.18, 2);
            $newTotal = round($newImport + $newIVA, 2);

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

        $validator = Validator::make($request->all(), [
            'purchace_id' => ['required', 'integer', 'exists:puchases,id'],
            'purchace_detail_id' => ['required', 'integer', 'exists:puchase_details,id'],
        ]);

        if ($validator->fails()) {
            return $this->errorResponse(422, 'VALIDATION_ERROR', 'Datos de atencion invalidos.', $validator->errors()->toArray());
        }

        date_default_timezone_set('America/Bogota');

        $purchace_id = $request->purchace_id;
        $purchace_detail_id = $request->purchace_detail_id;

        $response = null;

        DB::transaction(function () use ($purchace_id, $purchace_detail_id, &$response) {
            $purchase = Puchase::where('id', $purchace_id)->lockForUpdate()->first();
            $detail = PuchaseDetail::where('id', $purchace_detail_id)->lockForUpdate()->first();

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

            $product_warehouse = ProductWarehouse::where('product_id', $detail->product_id)
                ->where('warehouse_id', $purchase->warehouse_id)
                ->where('unit_id', $detail->unit_id)
                ->lockForUpdate()
                ->first();

            if($product_warehouse){
                $product_warehouse->update([
                    'stock' => $product_warehouse->stock + $detail->quantity
                ]);
            } else {
                ProductWarehouse::create([
                    'product_id' => $detail->product_id,
                    'warehouse_id' => $purchase->warehouse_id,
                    'unit_id' => $detail->unit_id,
                    'stock' => $detail->quantity,
                ]);
            }

            $n_details = PuchaseDetail::where('puchase_id', $purchace_id)->count();
            $n_details_attends = PuchaseDetail::where('puchase_id', $purchace_id)->where('state', 2)->count();

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

            $newImport = round($purchase->immporte - $detail->total, 2);
            $newIVA = round($newImport * 0.18, 2);
            $newTotal = round($newImport + $newIVA, 2);

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
                'iva' => $newIVA
            ]);
        });

        return $response ?? $this->errorResponse(500, 'PURCHASE_DETAIL_DELETE_ERROR', 'No se pudo eliminar el detalle de compra.');
    }
}
