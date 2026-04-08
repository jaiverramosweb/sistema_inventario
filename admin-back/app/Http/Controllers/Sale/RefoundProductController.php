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
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpException;

class RefoundProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $search_product = $request->search;
        $warehouse_id = $request->warehouse_id;
        $unit_id = $request->unit_id;
        $type = $request->type;
        $state = $request->state;
        $sale_id = $request->sale_id;
        $search_client = $request->search_client;
        $start_date = $request->start_date;
        $end_date = $request->end_date;

        $user = auth('api')->user();

        $refound = RefoundProduct::filterAdvance($search_product, $warehouse_id, $unit_id, $type, $state, $sale_id, $search_client, $start_date, $end_date, $user)
            ->orderBy('id', 'desc')
            ->paginate(25);

        return response()->json([
            'status' => 200,
            'data' => RefoundProductResource::collection($refound),
            "last_page" => $refound->lastPage()
        ]);

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
        $user = auth('api')->user();

        $sale_id = $request->sale_id;
        $sale_detail_id = $request->sale_detail_id;
        $type = (int) $request->type;
        $quantity = $request->quantity;
        $description = $request->description;

        if (!in_array($type, [1, 2, 3], true)) {
            return response()->json([
                'status' => 422,
                'message' => 'El tipo de devolución es inválido.'
            ]);
        }

        $sale = $this->scopedSaleQuery($user)->where('id', $sale_id)->first();
        if (!$sale) {
            return response()->json([
                'status' => 403,
                'message' => 'No se encontró la venta o no tienes permisos para usarla.'
            ]);
        }

        $sale_detail = SaleDetail::where('id', $sale_detail_id)
            ->where('sale_id', $sale->id)
            ->first();

        if (!$sale_detail) {
            return response()->json([
                'status' => 422,
                'message' => 'El detalle de venta no pertenece a la venta seleccionada.'
            ]);
        }

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

        $date_limit = Carbon::parse($sale_detail->created_at)->addDays((int) $sale_detail->product->warranty_day);

        if(!now()->lte($date_limit) ){
            return response()->json([
                'status' => 403,
                'message' => 'La fecha limite de la devolución del producto ya paso.'
            ]);
        }

        $product_warehouse = null;
        $sale_total_new = null;

        if ($type == 2) {
            $refound_v = RefoundProduct::where('sale_detail_id', $sale_detail_id)
                ->where('type', 1)
                ->first();

            if (!$refound_v) {
                return response()->json([
                    'status' => 403,
                    'message' => 'Es necesario primero pasar por el proceso de reparación y validación técnica.'
                ]);
            }

            $product_warehouse = ProductWarehouse::where('product_id', $sale_detail->product_id)
                ->where('warehouse_id', $sale_detail->warehouse_id)
                ->where('unit_id', $sale_detail->unit_id)
                ->first();

            if (!$product_warehouse || $product_warehouse->stock < $quantity) {
                return response()->json([
                    'status' => 403,
                    'message' => 'El producto no cuenta con stock suficiente para realizar el reemplazo.'
                ]);
            }
        }

        if ($type == 3) {
            $product_warehouse = ProductWarehouse::where('product_id', $sale_detail->product_id)
                ->where('warehouse_id', $sale_detail->warehouse_id)
                ->where('unit_id', $sale_detail->unit_id)
                ->first();

            $sale_details_total = $sale_detail->subtotal * ($sale_detail->quantity - $quantity);
            $sale_total_new = ($sale->total - $sale_detail->total) + $sale_details_total;

            if ($sale->paid_out > $sale_total_new) {
                return response()->json([
                    'status' => 403,
                    'message' => 'No puedes registrar esta devolución por que el valor cancelado de la venta es mayor, intente editar los pagos de la venta.'
                ]);
            }
        }

        try {
            DB::beginTransaction();
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
                if($product_warehouse){
                    $product_warehouse->update([
                        'stock' => $product_warehouse->stock + $quantity
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

                $state_attention = 1;

                if($new_quantity_pending == 0){
                    $state_attention = 3;
                } else if($new_quantity_pending == $new_quantity_soli) {
                    $state_attention = 1;
                } else {
                    $state_attention = 2;
                }
    
                $sale_detail->update([
                    'quantity' => $new_quantity_soli,
                    'subtotal' => $new_quantity_soli == 0 ? 0 : $sale_detail->subtotal,
                    'total' => $sale_total_new,
                    'quantity_pending' => $new_quantity_pending,
                    'state_attention' => $state_attention
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
            DB::commit();
        } catch (\Throwable $th) {
            DB::rollBack();
            throw new HttpException(500, $th->getMessage());
        }



        return response()->json([
            'status' => 201,
            'data' => new RefoundProductResource($refound),
            'message' => 'Devolución registrada con éxito.'
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
        $user = auth('api')->user();
        date_default_timezone_set('America/Bogota');

        $refound = $this->scopedRefoundQuery($user)->where('id', $id)->first();
        if (!$refound) {
            return response()->json([
                'status' => 403,
                'message' => 'No se encontró la devolución o no tienes permisos para editarla.'
            ]);
        }

        $rules = [
            'state' => 'nullable|integer|min:1',
            'description' => 'nullable|string',
            'resoslution_description' => 'nullable|string',
            'quantity' => 'nullable|integer|min:1',
            'type' => 'nullable|integer|in:1,2,3',
        ];

        $validated = $request->validate($rules);

        if (isset($validated['type']) && (int) $validated['type'] !== (int) $refound->type) {
            return response()->json([
                'status' => 422,
                'message' => 'No se puede cambiar el tipo de una devolución ya registrada.'
            ]);
        }

        $state = $validated['state'] ?? $refound->state;
        $quantity = $validated['quantity'] ?? $refound->quantity;
        $description = $validated['description'] ?? null;
        $resoslution_description = $validated['resoslution_description'] ?? null;

        if ((int) $refound->type === 1) {
            $saleDetail = $refound->saleDetail;
            if (!$saleDetail) {
                return response()->json([
                    'status' => 422,
                    'message' => 'La devolución no tiene detalle de venta asociado.'
                ]);
            }

            if ($saleDetail->quantity < $quantity) {
                return response()->json([
                    'status' => 403,
                    'message' => 'La cantidad ingresada es mayor a la vendida.'
                ]);
            }

            $quantity_attend = $saleDetail->quantity - $saleDetail->quantity_pending;
            if ($quantity_attend < $quantity) {
                return response()->json([
                    'status' => 403,
                    'message' => 'La cantidad ingresada es mucho mayor a la entregada.'
                ]);
            }
        }

        if($refound->type == 1){
            $resoslution_date = null;

            if($refound->resoslution_date){
                $resoslution_date = $refound->resoslution_date;
            } else {
                if($resoslution_description){
                    $resoslution_date = now();
                }
            }

            $refound->update([
                'state' => $state,
                'quantity' => $quantity,
                'description' => $description,
                'resoslution_description' => $resoslution_description,
                'resoslution_date' => $resoslution_date
            ]);
        }
        if($refound->type == 2 || $refound->type == 3){
            $refound->update([
                'state' => $state,
                'description' => $description
            ]);
        }

        return response()->json([
            'status' => 200,
            'data' => new RefoundProductResource($refound),
            'message' => 'Devolución actualizada con éxito.'
        ]);

    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $user = auth('api')->user();

        $refound = $this->scopedRefoundQuery($user)->where('id', $id)->first();
        if (!$refound) {
            return response()->json([
                'status' => 403,
                'message' => 'No se encontró la devolución o no tienes permisos para eliminarla.'
            ]);
        }

        if ((int) $refound->type === 2 || (int) $refound->type === 3) {
            return response()->json([
                'status' => 409,
                'message' => 'No se puede eliminar esta devolución porque impacta stock o montos de la venta.'
            ]);
        }

        $hasReplacement = RefoundProduct::where('sale_detail_id', $refound->sale_detail_id)
            ->where('type', 2)
            ->exists();

        if ($hasReplacement) {
            return response()->json([
                'status' => 409,
                'message' => 'No se puede eliminar la reparación porque ya existe un reemplazo asociado.'
            ]);
        }

        $refound->delete();

        return response()->json([
            'status' => 200,
            'message' => 'Devolución eliminada con éxito.'
        ]);
    }

    private function scopedSaleQuery($user)
    {
        return Sale::where(function ($query) use ($user) {
            if ($user->role_id != 1) {
                if ($user->role_id == 2) {
                    $query->where('sucursal_id', $user->sucuarsal_id);
                } else {
                    $query->where('user_id', $user->id);
                }
            }
        });
    }

    private function scopedRefoundQuery($user)
    {
        return RefoundProduct::where(function ($query) use ($user) {
            if ($user->role_id != 1) {
                if ($user->role_id == 2) {
                    $query->whereHas('saleDetail.sale', function ($saleQuery) use ($user) {
                        $saleQuery->where('sucursal_id', $user->sucuarsal_id);
                    });
                } else {
                    $query->where('user_id', $user->id);
                }
            }
        });
    }
}
