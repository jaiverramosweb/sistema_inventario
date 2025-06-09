<?php

namespace App\Http\Controllers\Product;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProductPiceRequest;
use App\Http\Resources\ProductPriceResource;
use App\Models\ProductWallet;
use Illuminate\Http\Request;

class ProductWalletController extends Controller
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
    public function store(ProductPiceRequest $request)
    {
        $product_price = ProductWallet::create($request->validated());
        $price_resoruce = new ProductPriceResource($product_price);

        return response()->json([
            "status" => 201,
            "product_price" => $price_resoruce
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
    public function update(ProductPiceRequest $request, string $id)
    {
        $exists = ProductWallet::where('product_id', $request->product_id)
            ->where('id', '<>', $id)
            ->where('unit_id', $request->unit_id)
            ->first();

        if ($exists) {
            return response()->json([
                'status' => 403,
                'message' => 'precio exists',
            ]);
        }

        $product_price = ProductWallet::findOrFail($id);
        $product_price->update($request->validated());

        $price_resoruce = new ProductPriceResource($product_price);

        return response()->json([
            "status" => 200,
            "product_price" => $price_resoruce
        ],200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $product_warehouse = ProductWallet::findOrFail($id);
        $product_warehouse->delete();
        return response()->json([
            'message' => 'product warehouse deleted'
        ]);
    }
}
