<?php

namespace App\Http\Controllers\Product;

use App\Http\Controllers\Controller;
use App\Http\Resources\ConversionResource;
use App\Models\Conversion;
use App\Models\ProductWarehouse;
use Illuminate\Http\Request;

class ConversionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $search = $request->search;
        $conversion_id = $request->conversion_id;
        $warehouse_id = $request->warehouse_id;
        $unit_start_id = $request->unit_start_id;
        $unit_end_id = $request->unit_end_id;
        $start_date = $request->start_date;
        $end_date = $request->end_date;

        $conversions = Conversion::filterAdvance($search, $conversion_id, $warehouse_id, $unit_start_id, $unit_end_id, $start_date, $end_date)
            ->oderBy('id', 'desc')->paginate(15);
        $conversions_collection = ConversionResource::collection($conversions);

        return response()->json([
            'data' => $conversions_collection,
            'total' => $conversions->total(),
            'last_page' => $conversions->lastPage(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Disminucion de stock
        $product_warehouse = ProductWarehouse::where('product_id', $request->product_id)
            ->where('unit_id', $request->unit_start_id)
            ->where('warehouse_id', $request->warehouse_id)
            ->first();

        if($product_warehouse->stock < (int) $request->quantity_start){
            return response()->json([
                'status' => 403,
                'message' => 'No puedes registra la conversion por que no se encuentra con el stock suficiente',
            ]);
        }

        $product_warehouse->update([
            'stock' => $product_warehouse->stock - (int) $request->quantity_start
        ]);

        // Aumento de stock
        $product_warehouse_aument = ProductWarehouse::where('product_id', $request->product_id)
            ->where('unit_id', $request->unit_end_id)
            ->where('warehouse_id', $request->warehouse_id)
            ->first();

        if(!$product_warehouse_aument){
            ProductWarehouse::create([
                'product_id' => $request->product_id,
                'warehouse_id' => $request->warehouse_id,
                'unit_id' => $request->unit_end_id,
                'stock' => (int) $request->quantity_end,
            ]);
        } else {
            $product_warehouse_aument->update([
                'stock' => $product_warehouse_aument->stock + (int) $request->quantity_end
            ]);
        }


        // Registro
        $request->request->add(['user_id' => auth('api')->user->id]);

        $conversion = Conversion::create($request->all());

        return response()->json([
            'status' => 201,
            'data' => new ConversionResource($conversion)
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
        $conversion = Conversion::findOrFail($id);

        // devolver la unidad a convertir
        $product_warehouse = ProductWarehouse::where('product_id', $conversion->product_id)
            ->where('unit_id', $conversion->unit_end_id)
            ->where('warehouse_id', $conversion->warehouse_id)
            ->first();

        if($product_warehouse->stock < $conversion->quantity_end)
        {
            return response()->json([
                'status' => 403,
                'message' => 'No puedes realizar la eliminación por que no se encuentra con el stock suficiente',
            ]);
        }

        $product_warehouse->update([
            'stock' => $product_warehouse->stock - $conversion->quantity_end
        ]);


        // aumentar el stock de la unidad que se utilizo
        $product_warehouse_aument = ProductWarehouse::where('product_id', $conversion->product_id)
            ->where('unit_id', $conversion->unit_start_id)
            ->where('warehouse_id', $conversion->warehouse_id)
            ->first();

        $product_warehouse_aument->update([
            'stock' => $product_warehouse_aument->stock + $conversion->quantity_end
        ]);

        // eliminar la conversion
        $conversion->delete();

        return response()->json([
            'status' => 200
        ]);
    }
}
