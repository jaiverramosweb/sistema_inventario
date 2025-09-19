<?php

namespace App\Http\Controllers\Sale;

use App\Http\Controllers\Controller;
use App\Http\Requests\RefoundProductRequest;
use App\Http\Resources\RefoundProductResource;
use App\Http\Resources\SaleResource;
use App\Models\ProductWarehouse;
use App\Models\RefoundProduct;
use App\Models\Sale;
use App\Models\SaleDetail;
use Illuminate\Http\Request;

class RefoundProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    public function searchSale($id)
    {
        $sale = Sale::find($id);

        if(!$sale){
            return response()->json([
                'status' => 403,
                'message' => 'El numero de la venta ingresada no existe'
            ]);
        }

        return response()->json([
            'data' => new SaleResource($sale)
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(RefoundProductRequest $request)
    {
        $sale_id = $request->sale_id;
        $sale_detail_id = $request->sale_detail_id;
        $type = $request->type;
        $quantity = $request->quantity;
        $description = $request->description;

        $sale = Sale::find($sale_id);
        $sale_detail = SaleDetail::find($sale_detail_id);

        if($sale_detail->quantity < $quantity){
            return response()->json([
                'status' => 403,
                'message' => 'La cantidad ingresada es mayor a la vendida.'
            ]);
        }

        $quantity_attend = $sale_detail->quantity - $sale_detail->quantity_pending;

        if($quantity_attend < $quantity){
            return response()->json([
                'status' => 403,
                'message' => 'La cantidad ingresada es mucho mayor a la entregada.'
            ]);
        }

        // SI ES REPARACION
        if($type == 1){
            $refound = RefoundProduct::create([
                'user_id' => auth('api')->user()->id,
                'client_id' => $sale->client_id,
                'product_id' => $sale_detail->product_id,
                'unit_id' => $sale_detail->unit_id,
                'warehouse_id' => $sale_detail->warehouse_id,
                'sale_detail_id' => $sale_detail_id,
                'quantity' => $quantity,
                'type' => $type,
                'state' => 1,
                'description' => $description
            ]);
        }

        // SI ES REEMPLAZO
        if($type == 2){
            $refound_v = RefoundProduct::where('sale_detail_id', $sale_detail_id)
                ->where('type', 1)
                ->first();

            if(!$refound_v){
                return response()->json([
                    'status' => 403,
                    'message' => 'Es necesario primero pasar por el procesa de reparación y validación tecnica.'
                ]);
            }

            $product_warehouse = ProductWarehouse::where('product_id', $sale_detail->product_id)
                ->where('warehouse_id', $sale_detail->warehouse_id)
                ->where('unit_id', $sale_detail->unit_id)
                ->first();

            if($product_warehouse->stock < $quantity){
                return response()->json([
                    'status' => 403,
                    'message' => 'E producto no cuenta con stock suficiente para realizar el reemplazo.'
                ]);
            }

            $product_warehouse->update([
                'stock' => $product_warehouse->stock - $quantity
            ]);

            $refound = RefoundProduct::create([
                'user_id' => auth('api')->user()->id,
                'client_id' => $sale->client_id,
                'product_id' => $sale_detail->product_id,
                'unit_id' => $sale_detail->unit_id,
                'warehouse_id' => $sale_detail->warehouse_id,
                'sale_detail_id' => $sale_detail_id,
                'quantity' => $quantity,
                'type' => $type,
                'description' => $description
            ]);
        }

        // SI ES DEVOLUCION
        if($type == 3){
            $product_warehouse = ProductWarehouse::where('product_id', $sale_detail->product_id)
                ->where('warehouse_id', $sale_detail->warehouse_id)
                ->where('unit_id', $sale_detail->unit_id)
                ->first();

            if($product_warehouse){
                $product_warehouse->update([
                    'stock' => $product_warehouse->stock + $quantity
                ]);
            }

            $sale_details_total = $sale_detail->subtotal * ($sale_detail->quantity - $quantity);
            $sale_total_new = ($sale->total - $sale_detail->total) + $sale_details_total;

            if($sale->paid_out > $sale_total_new){
                    return response()->json([
                    'status' => 403,
                    'message' => 'No puedes registrar esta devolución por que el valor cancelado de la venta es mayor, intente editar los pagos de la venta.'
                ]);
            }

            $quantity_soli = $sale_detail->quantity;
            $quantity_pending = $sale_detail->quantity_pending;
            $quantity_attend = $quantity_soli - $quantity_pending;

            $new_quantity_soli = $quantity_soli - $quantity;
            $new_quantity_pending = $new_quantity_soli - ($quantity_attend - $quantity);

            $iva_old = $sale_detail->iva * $sale_detail->quantity;
            $discount_old = $sale_detail->discount * $sale_detail->quantity;
            $subtotal_old = $sale_detail->price_unit * $sale_detail->quantity;

            $sale_detail->update([
                'quantity' => $new_quantity_soli,
                'total' => $sale_total_new,
                'quantity_pending' => $new_quantity_pending,
                'state_attention' => $new_quantity_pending == 0 ? 3 : 2
            ]);

            $iva_total = $sale_detail->iva * $sale_detail->quantity;
            $discount_total = $sale_detail->discount * $sale_detail->quantity;
            $subtotal_total = $sale_detail->price_unit * $sale_detail->quantity;

            // state_mayment
            // date_completed
            $state_mayment = 1;
            $date_completed = null;

            if($sale->paid_out == $sale_total_new){
                date_default_timezone_set('America/Bogota');
                $state_mayment = 3;
                $date_completed = now();
            } else if($sale->paid_out > 0) {
                $state_mayment = 2;
            }

            $sale->update([
                'iva' => ($sale->iva - $iva_old) + $iva_total,
                'discount' => ($sale->discount - $discount_old) + $discount_total,
                'subtotal' => ($sale->subtotal - $subtotal_old) + $subtotal_total,
                'total' => $sale_total_new,
                'debt' => $sale_total_new - $sale->paid_out,
                'state_mayment' => $state_mayment,
                'date_completed' => $date_completed,
            ]);

            $refound = RefoundProduct::create([
                'user_id' => auth('api')->user()->id,
                'client_id' => $sale->client_id,
                'product_id' => $sale_detail->product_id,
                'unit_id' => $sale_detail->unit_id,
                'warehouse_id' => $sale_detail->warehouse_id,
                'sale_detail_id' => $sale_detail_id,
                'quantity' => $quantity,
                'type' => $type,
                'description' => $description
            ]);
        }


        return response()->json([
            'status' => 200,
            'data' => new RefoundProductResource($refound)
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
