<?php

namespace App\Http\Controllers\Product;

use App\Http\Controllers\Concerns\ApiErrorResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\ConversionResource;
use App\Models\Conversion;
use App\Services\Inventory\ProductWarehouseStockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ConversionController extends Controller
{
    use ApiErrorResponse;

    public function __construct(private ProductWarehouseStockService $stockService)
    {
    }

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
            return $this->errorResponse(
                422,
                'VALIDATION_ERROR',
                'Faltan datos obligatorios para registrar la conversion.',
                [
                    'warehouse_id' => ['El almacen es obligatorio.'],
                ]
            );
        }

        $conversion = DB::transaction(function () use ($request, $warehouseId, $user) {
            // Disminucion de stock
            $product_warehouse = $this->stockService->lock(
                (int) $request->product_id,
                (int) $request->unit_start_id,
                (int) $warehouseId
            );

            if(!$product_warehouse || $product_warehouse->stock < (int) $request->quantity_start){
                return null;
            }

            $product_warehouse->update([
                'stock' => $product_warehouse->stock - (int) $request->quantity_start
            ]);

            $this->stockService->increaseOrCreate(
                (int) $request->product_id,
                (int) $request->unit_end_id,
                (int) $warehouseId,
                (float) $request->quantity_end
            );

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
            return $this->errorResponse(
                422,
                'INSUFFICIENT_STOCK',
                'No puedes registrar la conversion porque no hay stock suficiente.'
            );
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
            $product_warehouse = $this->stockService->lock(
                (int) $conversion->product_id,
                (int) $conversion->unit_end_id,
                (int) $conversion->warehause_id
            );

            if(!$product_warehouse || $product_warehouse->stock < $conversion->quantity_end)
            {
                return false;
            }

            $product_warehouse->update([
                'stock' => $product_warehouse->stock - $conversion->quantity_end
            ]);

            $this->stockService->increaseOrCreate(
                (int) $conversion->product_id,
                (int) $conversion->unit_start_id,
                (int) $conversion->warehause_id,
                (float) $conversion->quantity_start
            );

            // eliminar la conversion
            $conversion->delete();

            return true;
        });

        if(!$result){
            return $this->errorResponse(
                422,
                'INSUFFICIENT_STOCK',
                'No puedes realizar la eliminacion porque no hay stock suficiente.'
            );
        }

        return response()->json([
            'status' => 200
        ]);
    }
}
