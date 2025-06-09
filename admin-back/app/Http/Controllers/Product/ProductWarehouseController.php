<?php

namespace App\Http\Controllers\Product;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProductWarehouseRequest;
use App\Http\Resources\ProductWarehouseResource;
use App\Models\ProductWarehouse;
use Illuminate\Http\Request;

class ProductWarehouseController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(ProductWarehouseRequest $request)
    {
        $product_warehouse = ProductWarehouse::create($request->validated());

        $warehouse_resourse = new ProductWarehouseResource($product_warehouse);

        return response()->json([
            "status" => 201,
            "product_warehouse" => $warehouse_resourse
        ],201);
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
    public function update(ProductWarehouseRequest $request, string $id)
    {
        $exists = ProductWarehouse::where('product_id', $request->product_id)
            ->where('id', '<>', $id)
            ->where('warehouse_id', $request->warehouse_id)
            ->where('unit_id', $request->unit_id)
            ->first();

        if ($exists) {
            return response()->json([
                'status' => 403,
                'message' => 'Warehouse exists',
            ]);
        }

        $product_warehouse = ProductWarehouse::findOrFail($id);
        $product_warehouse->update($request->validated());

        $warehouse_resourse = new ProductWarehouseResource($product_warehouse);

        return response()->json([
            "status" => 200,
            "product_warehouse" => $warehouse_resourse
        ],200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $product_warehouse = ProductWarehouse::findOrFail($id);
        $product_warehouse->delete();
        return response()->json([
            'message' => 'product warehouse deleted'
        ]);
    }
}
