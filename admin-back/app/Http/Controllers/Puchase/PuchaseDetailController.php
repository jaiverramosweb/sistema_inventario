<?php

namespace App\Http\Controllers\Puchase;

use App\Http\Controllers\Controller;
use App\Http\Resources\PurchaseDetailResource;
use App\Models\Puchase;
use App\Models\PuchaseDetail;
use Illuminate\Http\Request;

class PuchaseDetailController extends Controller
{

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $product = $request->product;

        $details = PuchaseDetail::create([
            'puchase_id' => $request->puchase_id,
            'product_id' => $product['id'],
            'unit_id' => $request->unit_id,
            'quantity' => $request->quantity,
            'price_unit' => $request->price_unit,
            'total' => $request->total,
        ]);

        $purchase = Puchase::findOrFail($request->puchase_id);


        $newImport = round($purchase->immporte + $request->total, 2);
        $newIVA = round($newImport * 0.18, 2);
        $newTotal = round($newImport + $newIVA, 2);

        $state = 1;

        if($purchase->state == 3){
            $state = 2;
        }

        if($purchase->state == 2){
            $state = 2;
        }

        $purchase->update([
            'state' => $state,
            'immporte' => $newImport,
            'iva' => $newIVA,
            'total' => $newTotal
        ]);

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
        $detail = PuchaseDetail::findOrFail($id);

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
            'puchase_id' => $request->puchase_id,
            'unit_id' => $request->unit_id,
            'quantity' => $request->quantity,
            'price_unit' => $request->price_unit,
            'total' => $request->total,
            'description' => $request->description
        ]);

        $purchase = Puchase::findOrFail($request->puchase_id);

        $newImport = round(($purchase->immporte - $old_total) + $request->total, 2);
        $newIVA = round($newImport * 0.18, 2);
        $newTotal = round($newImport + $newIVA, 2);

        // $state = 1;

        // if($purchase->state == 3){
        //     $state = 2;
        // }

        // if($purchase->state == 2){
        //     $state = 2;
        // }

        $purchase->update([
            // 'state' => $state,
            'immporte' => $newImport,
            'iva' => $newIVA,
            'total' => $newTotal
        ]);

        return response()->json([
            'status' => 200,
            'data' => new PurchaseDetailResource($detail),
            'total' => $newTotal,
            'immporte' => $newImport,
            'iva' => $newIVA
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $detail = PuchaseDetail::findOrFail($id);

        if($detail->state != 1){
            return response()->json([
                    'status' => 403,
                    'message' => 'No puedes eliminar el detalle por que ya se a entregado por el proveedor'
                ]);
        }

        $detail->delete();

        $purchase = $detail->puchase;

        $newImport = round($purchase->immporte - $detail->total, 2);
        $newIVA = round($newImport * 0.18, 2);
        $newTotal = round($newImport + $newIVA, 2);

        $purchase->update([
            'immporte' => $newImport,
            'iva' => $newIVA,
            'total' => $newTotal
        ]);

        return response()->json([
            'status' => 200,
            'id' => $id,
            'total' => $newTotal,
            'immporte' => $newImport,
            'iva' => $newIVA
        ]);
    }
}
