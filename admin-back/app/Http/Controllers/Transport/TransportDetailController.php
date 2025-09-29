<?php

namespace App\Http\Controllers\Transport;

use App\Http\Controllers\Controller;
use App\Http\Resources\TransportDetailResource;
use App\Models\ProductWarehouse;
use App\Models\Transport;
use App\Models\TransportDetail;
use Illuminate\Http\Request;

class TransportDetailController extends Controller
{
    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $product = $request->product;

        $transport = Transport::findOrFail($request->transport_id);

        if($transport->state >= 3){
            return response()->json([
                'status' => 403,
                'message' => 'No puedes agregar mas productos por que ya se le dio salida a la solicitud'
            ]);
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

        $newImport = round($transport->impote + $request->total, 2);
        $newIVA = round($newImport * 0.18, 2);
        $newTotal = round($newImport + $newIVA, 2);

        $transport->update([
            'impote' => $newImport,
            'iva' => $newIVA,
            'total' => $newTotal
        ]);

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
        $detail = TransportDetail::findOrFail($id);

        if($detail->state != 1){
            if($detail->quantity != $request->quantity){
                return response()->json([
                    'status' => 403,
                    'message' => 'No puedes editar la cantidad del detalle por que ya se a entregado el producto'
                ]);
            }

            if($detail->unit_id != $request->unit_id){
                return response()->json([
                    'status' => 403,
                    'message' => 'No puedes editar la unidad del detalle por que ya se a entregado el producto'
                ]);
            }
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

        $transport = Transport::findOrFail($request->transport_id);

        $newImport = round(($transport->impote - $old_total) + $request->total, 2);
        $newIVA = round($newImport * 0.18, 2);
        $newTotal = round($newImport + $newIVA, 2);

        $transport->update([
            'impote' => $newImport,
            'iva' => $newIVA,
            'total' => $newTotal
        ]);

        return response()->json([
            'status' => 200,
            'data' => new TransportDetailResource($detail),
            'total' => $newTotal,
            'impote' => $newImport,
            'iva' => $newIVA
        ]);
    }

    public function attentionExit(Request $request)
    {
        date_default_timezone_set('America/Bogota');

        $transport_detail_id = $request->transport_detail_id;
        $detail = TransportDetail::findOrFail($transport_detail_id);
        $transport = $detail->transport;

        if($detail->state != 1){
            return response()->json([
                'status' => 403,
                'message' => 'No se puede dar salida a este producto por que ya se atendio'
            ]);
        }

        $product_warehouse = ProductWarehouse::where('product_id', $detail->product_id)
            ->where('unit_id', $detail->unit_id)
            ->where('warehouse_id', $transport->warehause_start_id)
            ->first();

        if($product_warehouse->stock < $detail->quantity){
            return response()->json([
                'status' => 403,
                'message' => 'No se puede atender la cantidad solicitada por que unicamente tenemos ' . $product_warehouse->stock . ' unidades'
            ]);
        }

        $product_warehouse->update([
            'stock' => $product_warehouse->stock - $detail->quantity
        ]);

        $detail->update([
            'state' => 2,
            'user_exit_id' => auth('api')->user()->id,
            'date_exit' => now()
        ]);

        return response()->json([
            'status' => 200,
            'data' => new TransportDetailResource($detail)
        ]);
    }

    public function attentionDelivery(Request $request)
    {
        date_default_timezone_set('America/Bogota');

        $transport_detail_id = $request->transport_detail_id;
        $detail = TransportDetail::findOrFail($transport_detail_id);
        $transport = $detail->transport;

        if($detail->state == 3){
            return response()->json([
                'status' => 403,
                'message' => 'No se puede dar salida a este producto por que ya se atendio'
            ]);
        }

        $product_warehouse = ProductWarehouse::where('product_id', $detail->product_id)
            ->where('unit_id', $detail->unit_id)
            ->where('warehouse_id', $transport->warehause_end_id)
            ->first();

        if(!$product_warehouse){
            ProductWarehouse::create([
                'product_id' => $detail->product_id,
                'unit_id' => $detail->unit_id,
                'warehouse_id' => $transport->warehause_end_id,
                'unit_id' => $detail->unit_id,
                'stock' => $detail->quantity
            ]);
        }

        $product_warehouse->update([
            'stock' => $product_warehouse->stock + $detail->quantity
        ]);

        $detail->update([
            'state' => 3,
            'user_delivery_id' => auth('api')->user()->id,
            'date_delivery' => now()
        ]);

        return response()->json([
            'status' => 200,
            'data' => new TransportDetailResource($detail)
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $detail = TransportDetail::findOrFail($id);

        if($detail->state != 1){
            return response()->json([
                    'status' => 403,
                    'message' => 'No puedes eliminar el detalle por que ya se a entregado'
                ]);
        }

        $detail->delete();

        $transport = $detail->transport;

        $newImport = round($transport->impote - $detail->total, 2);
        $newIVA = round($newImport * 0.18, 2);
        $newTotal = round($newImport + $newIVA, 2);

        $transport->update([
            'impote' => $newImport,
            'iva' => $newIVA,
            'total' => $newTotal
        ]);

        return response()->json([
            'status' => 200,
            'id' => $id,
            'total' => $newTotal,
            'impote' => $newImport,
            'iva' => $newIVA
        ]);
    }
}
