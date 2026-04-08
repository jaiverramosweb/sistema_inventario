<?php

namespace App\Http\Controllers\Product;

use App\Http\Controllers\Controller;
use App\Http\Resources\ConversionResource;
use App\Models\Conversion;
use App\Models\ProductWarehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ConversionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        abort_unless($request->user('api')?->can('conversions'), 403);

        $search = $request->search;
        $conversion_id = $request->conversion_id;
        $warehouse_id = $request->warehouse_id;
        $unit_start_id = $request->unit_start_id;
        $unit_end_id = $request->unit_end_id;
        $start_date = $request->start_date;
        $end_date = $request->end_date;

        $conversions = Conversion::filterAdvance($search, $conversion_id, $warehouse_id, $unit_start_id, $unit_end_id, $start_date, $end_date)
            ->orderBy('id', 'desc')->paginate(15);
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
        abort_unless($request->user('api')?->can('conversions'), 403);

        $user = auth('api')->user();
        $warehouseId = $request->warehause_id ?? $request->warehouse_id;

        if(!$user || !$warehouseId){
            return response()->json([
                'status' => 422,
                'message' => 'Faltan datos obligatorios para registrar la conversion.',
            ], 422);
        }

        $conversion = DB::transaction(function () use ($request, $warehouseId, $user) {
            // Disminucion de stock
            $product_warehouse = ProductWarehouse::where('product_id', $request->product_id)
                ->where('unit_id', $request->unit_start_id)
                ->where('warehouse_id', $warehouseId)
                ->lockForUpdate()
                ->first();

            if(!$product_warehouse || $product_warehouse->stock < (int) $request->quantity_start){
                return null;
            }

            $product_warehouse->update([
                'stock' => $product_warehouse->stock - (int) $request->quantity_start
            ]);

            // Aumento de stock
            $product_warehouse_aument = ProductWarehouse::where('product_id', $request->product_id)
                ->where('unit_id', $request->unit_end_id)
                ->where('warehouse_id', $warehouseId)
                ->lockForUpdate()
                ->first();

            if(!$product_warehouse_aument){
                ProductWarehouse::create([
                    'product_id' => $request->product_id,
                    'warehouse_id' => $warehouseId,
                    'unit_id' => $request->unit_end_id,
                    'stock' => (int) $request->quantity_end,
                ]);
            } else {
                $product_warehouse_aument->update([
                    'stock' => $product_warehouse_aument->stock + (int) $request->quantity_end
                ]);
            }

            // Registro
            return Conversion::create([
                'user_id' => $user->id,
                'product_id' => $request->product_id,
                'warehause_id' => $warehouseId,
                'unit_start_id' => $request->unit_start_id,
                'unit_end_id' => $request->unit_end_id,
                'quantity_start' => $request->quantity_start,
                'quantity_end' => $request->quantity_end,
                'description' => $request->description,
            ]);
        });

        if(!$conversion){
            return response()->json([
                'status' => 403,
                'message' => 'No puedes registra la conversion por que no se encuentra con el stock suficiente',
            ], 403);
        }

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
    public function destroy(Request $request, string $id)
    {
        abort_unless($request->user('api')?->can('conversions'), 403);

        $conversion = Conversion::findOrFail($id);

        $result = DB::transaction(function () use ($conversion) {
            // devolver la unidad convertida
            $product_warehouse = ProductWarehouse::where('product_id', $conversion->product_id)
                ->where('unit_id', $conversion->unit_end_id)
                ->where('warehouse_id', $conversion->warehause_id)
                ->lockForUpdate()
                ->first();

            if(!$product_warehouse || $product_warehouse->stock < $conversion->quantity_end)
            {
                return false;
            }

            $product_warehouse->update([
                'stock' => $product_warehouse->stock - $conversion->quantity_end
            ]);

            // aumentar el stock de la unidad origen
            $product_warehouse_aument = ProductWarehouse::where('product_id', $conversion->product_id)
                ->where('unit_id', $conversion->unit_start_id)
                ->where('warehouse_id', $conversion->warehause_id)
                ->lockForUpdate()
                ->first();

            if(!$product_warehouse_aument){
                ProductWarehouse::create([
                    'product_id' => $conversion->product_id,
                    'warehouse_id' => $conversion->warehause_id,
                    'unit_id' => $conversion->unit_start_id,
                    'stock' => $conversion->quantity_start,
                ]);
            } else {
                $product_warehouse_aument->update([
                    'stock' => $product_warehouse_aument->stock + $conversion->quantity_start
                ]);
            }

            // eliminar la conversion
            $conversion->delete();

            return true;
        });

        if(!$result){
            return response()->json([
                'status' => 403,
                'message' => 'No puedes realizar la eliminación por que no se encuentra con el stock suficiente',
            ]);
        }

        return response()->json([
            'status' => 200
        ]);
    }
}
