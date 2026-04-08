<?php

namespace App\Http\Controllers\Transport;

use App\Http\Controllers\Concerns\ApiErrorResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\TransportDetailResource;
use App\Models\ProductWarehouse;
use App\Models\Transport;
use App\Models\TransportDetail;
use App\Services\Finance\DocumentTotalsService;
use App\Services\Inventory\ProductWarehouseStockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;

class TransportDetailController extends Controller
{
    use ApiErrorResponse;

    public function __construct(
        private DocumentTotalsService $documentTotalsService,
        private ProductWarehouseStockService $stockService,
    )
    {
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        Gate::authorize('update', Transport::class);

        $validator = Validator::make($request->all(), [
            'transport_id' => ['required', 'integer', 'exists:transports,id'],
            'product.id' => ['required', 'integer', 'exists:products,id'],
            'unit_id' => ['required', 'integer', 'exists:units,id'],
            'quantity' => ['required', 'numeric', 'min:0.01'],
            'price_unit' => ['required', 'numeric', 'min:0'],
            'total' => ['required', 'numeric', 'min:0'],
        ]);

        if ($validator->fails()) {
            return $this->errorResponse(422, 'VALIDATION_ERROR', 'Datos del detalle de traslado invalidos.', $validator->errors()->toArray());
        }

        $product = $request->product;
        $details = null;
        $newImport = 0;
        $newIVA = 0;
        $newTotal = 0;

        DB::transaction(function () use ($request, $product, &$details, &$newImport, &$newIVA, &$newTotal) {
            $transport = Transport::where('id', $request->transport_id)->lockForUpdate()->first();

            if(!$transport || $transport->state >= 3){
                return;
            }

            $details = TransportDetail::create([
                'transport_id' => $request->transport_id,
                'product_id' => $product['id'],
                'unit_id' => $request->unit_id,
                'quantity' => $request->quantity,
                'price_unit' => $request->price_unit,
                'total' => $request->total,
                'state' => 1
            ]);

            $totals = $this->documentTotalsService->calculateFromImport((float) $transport->impote + (float) $request->total);
            $newImport = $totals['impote'];
            $newIVA = $totals['iva'];
            $newTotal = $totals['total'];

            $transport->update([
                'impote' => $newImport,
                'iva' => $newIVA,
                'total' => $newTotal
            ]);
        });

        if (!$details) {
            $transport = Transport::find($request->transport_id);

            if (!$transport) {
                return $this->errorResponse(404, 'TRANSPORT_NOT_FOUND', 'El traslado seleccionado no existe.');
            }

            return $this->errorResponse(403, 'TRANSPORT_DETAIL_CREATE_FORBIDDEN', 'No puedes agregar mas productos por que ya se le dio salida a la solicitud');
        }

        return response()->json([
            'status' => 201,
            'data' => new TransportDetailResource($details),
            'impote' => $newImport,
            'iva' => $newIVA,
            'total' => $newTotal
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        Gate::authorize('update', Transport::class);

        $validator = Validator::make($request->all(), [
            'transport_id' => ['required', 'integer', 'exists:transports,id'],
            'unit_id' => ['required', 'integer', 'exists:units,id'],
            'quantity' => ['required', 'numeric', 'min:0.01'],
            'price_unit' => ['required', 'numeric', 'min:0'],
            'total' => ['required', 'numeric', 'min:0'],
            'description' => ['nullable', 'string'],
        ]);

        if ($validator->fails()) {
            return $this->errorResponse(422, 'VALIDATION_ERROR', 'Datos del detalle de traslado invalidos.', $validator->errors()->toArray());
        }

        $response = null;

        DB::transaction(function () use ($request, $id, &$response) {
            $detail = TransportDetail::where('id', $id)->lockForUpdate()->first();

            if (!$detail) {
                $response = $this->errorResponse(404, 'TRANSPORT_DETAIL_NOT_FOUND', 'El detalle de traslado no existe.');
                return;
            }

            if($detail->state != 1){
                if($detail->quantity != $request->quantity){
                    $response = $this->errorResponse(403, 'TRANSPORT_DETAIL_QUANTITY_FORBIDDEN', 'No puedes editar la cantidad del detalle por que ya se a entregado el producto');
                    return;
                }

                if($detail->unit_id != $request->unit_id){
                    $response = $this->errorResponse(403, 'TRANSPORT_DETAIL_UNIT_FORBIDDEN', 'No puedes editar la unidad del detalle por que ya se a entregado el producto');
                    return;
                }
            }

            $transport = Transport::where('id', $request->transport_id)->lockForUpdate()->first();

            if (!$transport) {
                $response = $this->errorResponse(404, 'TRANSPORT_NOT_FOUND', 'El traslado seleccionado no existe.');
                return;
            }

            $old_total = $detail->total;

            $detail->update([
                'transport_id' => $request->transport_id,
                'unit_id' => $request->unit_id,
                'quantity' => $request->quantity,
                'price_unit' => $request->price_unit,
                'total' => $request->total,
                'description' => $request->description
            ]);

            $totals = $this->documentTotalsService->calculateFromDelta(
                (float) $transport->impote,
                (float) $old_total,
                (float) $request->total
            );
            $newImport = $totals['impote'];
            $newIVA = $totals['iva'];
            $newTotal = $totals['total'];

            $transport->update([
                'impote' => $newImport,
                'iva' => $newIVA,
                'total' => $newTotal
            ]);

            $response = response()->json([
                'status' => 200,
                'data' => new TransportDetailResource($detail),
                'total' => $newTotal,
                'impote' => $newImport,
                'iva' => $newIVA
            ]);
        });

        return $response ?? $this->errorResponse(500, 'TRANSPORT_DETAIL_UPDATE_ERROR', 'No se pudo actualizar el detalle de traslado.');
    }

    public function attentionExit(Request $request)
    {
        Gate::authorize('update', Transport::class);

        $validator = Validator::make($request->all(), [
            'transport_detail_id' => ['required', 'integer', 'exists:transport_details,id'],
        ]);

        if ($validator->fails()) {
            return $this->errorResponse(422, 'VALIDATION_ERROR', 'Datos de salida invalidos.', $validator->errors()->toArray());
        }

        date_default_timezone_set('America/Bogota');

        $transport_detail_id = $request->transport_detail_id;
        $response = null;

        DB::transaction(function () use ($transport_detail_id, &$response) {
            $detail = TransportDetail::where('id', $transport_detail_id)->lockForUpdate()->first();

            if (!$detail) {
                $response = $this->errorResponse(404, 'TRANSPORT_DETAIL_NOT_FOUND', 'El detalle de traslado no existe.');
                return;
            }

            $transport = Transport::where('id', $detail->transport_id)->lockForUpdate()->first();

            if (!$transport) {
                $response = $this->errorResponse(404, 'TRANSPORT_NOT_FOUND', 'El traslado asociado no existe.');
                return;
            }

            if($detail->state != 1){
                $response = $this->errorResponse(403, 'TRANSPORT_EXIT_FORBIDDEN', 'No se puede dar salida a este producto por que ya se atendio');
                return;
            }

            $product_warehouse = ProductWarehouse::where('product_id', $detail->product_id)
                ->where('unit_id', $detail->unit_id)
                ->where('warehouse_id', $transport->warehause_start_id)
                ->lockForUpdate()
                ->first();

            if(!$product_warehouse){
                $response = $this->errorResponse(403, 'TRANSPORT_EXIT_STOCK_UNAVAILABLE', 'No se puede atender la cantidad solicitada por que no existe stock en el almacen de salida');
                return;
            }

            if($product_warehouse->stock < $detail->quantity){
                $response = $this->errorResponse(403, 'TRANSPORT_EXIT_STOCK_INSUFFICIENT', 'No se puede atender la cantidad solicitada por que unicamente tenemos ' . $product_warehouse->stock . ' unidades');
                return;
            }

            $product_warehouse->update([
                'stock' => $product_warehouse->stock - $detail->quantity
            ]);

            $detail->update([
                'state' => 2,
                'user_exit_id' => auth('api')->user()->id,
                'date_exit' => now()
            ]);

            $response = response()->json([
                'status' => 200,
                'data' => new TransportDetailResource($detail)
            ]);
        });

        return $response ?? $this->errorResponse(500, 'TRANSPORT_EXIT_ERROR', 'No se pudo registrar la salida del detalle.');
    }

    public function attentionDelivery(Request $request)
    {
        Gate::authorize('update', Transport::class);

        $validator = Validator::make($request->all(), [
            'transport_detail_id' => ['required', 'integer', 'exists:transport_details,id'],
        ]);

        if ($validator->fails()) {
            return $this->errorResponse(422, 'VALIDATION_ERROR', 'Datos de recepcion invalidos.', $validator->errors()->toArray());
        }

        date_default_timezone_set('America/Bogota');

        $transport_detail_id = $request->transport_detail_id;
        $response = null;

        DB::transaction(function () use ($transport_detail_id, &$response) {
            $detail = TransportDetail::where('id', $transport_detail_id)->lockForUpdate()->first();

            if (!$detail) {
                $response = $this->errorResponse(404, 'TRANSPORT_DETAIL_NOT_FOUND', 'El detalle de traslado no existe.');
                return;
            }

            $transport = Transport::where('id', $detail->transport_id)->lockForUpdate()->first();

            if (!$transport) {
                $response = $this->errorResponse(404, 'TRANSPORT_NOT_FOUND', 'El traslado asociado no existe.');
                return;
            }

            if($detail->state == 3){
                $response = $this->errorResponse(403, 'TRANSPORT_DELIVERY_FORBIDDEN', 'No se puede recibir este producto por que ya se atendio');
                return;
            }

            if($detail->state == 1){
                $response = $this->errorResponse(403, 'TRANSPORT_DELIVERY_EXIT_REQUIRED', 'No se puede recibir este producto por que aun no tiene salida');
                return;
            }

            $this->stockService->increaseOrCreate(
                (int) $detail->product_id,
                (int) $detail->unit_id,
                (int) $transport->warehause_end_id,
                (float) $detail->quantity
            );

            $detail->update([
                'state' => 3,
                'user_delivery_id' => auth('api')->user()->id,
                'date_delivery' => now()
            ]);

            $response = response()->json([
                'status' => 200,
                'data' => new TransportDetailResource($detail)
            ]);
        });

        return $response ?? $this->errorResponse(500, 'TRANSPORT_DELIVERY_ERROR', 'No se pudo registrar la recepcion del detalle.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        Gate::authorize('update', Transport::class);

        $response = null;

        DB::transaction(function () use ($id, &$response) {
            $detail = TransportDetail::where('id', $id)->lockForUpdate()->first();

            if (!$detail) {
                $response = $this->errorResponse(404, 'TRANSPORT_DETAIL_NOT_FOUND', 'El detalle de traslado no existe.');
                return;
            }

            if($detail->state != 1){
                $response = $this->errorResponse(403, 'TRANSPORT_DETAIL_DELETE_FORBIDDEN', 'No puedes eliminar el detalle por que ya se a entregado');
                return;
            }

            $transport = Transport::where('id', $detail->transport_id)->lockForUpdate()->first();

            if (!$transport) {
                $response = $this->errorResponse(404, 'TRANSPORT_NOT_FOUND', 'El traslado asociado no existe.');
                return;
            }

            $detail->delete();

            $totals = $this->documentTotalsService->calculateFromImport((float) $transport->impote - (float) $detail->total);
            $newImport = $totals['impote'];
            $newIVA = $totals['iva'];
            $newTotal = $totals['total'];

            $transport->update([
                'impote' => $newImport,
                'iva' => $newIVA,
                'total' => $newTotal
            ]);

            $response = response()->json([
                'status' => 200,
                'id' => $id,
                'total' => $newTotal,
                'impote' => $newImport,
                'iva' => $newIVA
            ]);
        });

        return $response ?? $this->errorResponse(500, 'TRANSPORT_DETAIL_DELETE_ERROR', 'No se pudo eliminar el detalle de traslado.');
    }
}
