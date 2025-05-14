<?php

namespace App\Http\Controllers\Product;

use App\Exports\Product\ProductDownloadExcel;
use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductWallet;
use App\Models\ProductWarehouse;
use App\Models\Sucursale;
use App\Models\Unit;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $search         = $request->search;
        $category_id    = $request->category_id;
        $warehouse_id   = $request->warehouse_id;
        $unit_id        = $request->unit_id;
        $sucursale_id   = $request->sucursale_id;
        $available      = $request->available;
        $is_gift        = $request->is_gift;

        $products = Product::filterAdvance($search, $category_id, $warehouse_id, $unit_id, $sucursale_id, $available, $is_gift)
                            ->orderBy('id', 'desc')
                            ->paginate(25);

        $products_collection = ProductResource::collection($products);

        return response()->json([
            'data' => $products_collection,
            'total' => $products->total(),
            'last_page' => $products->lastPage(),
        ]);
    }

    public function s3_images(Request $request)
    {
        $filePath = $request->file('image')->store('uploads', 's3');

        if($filePath == false){
            $path = Storage::putFile('products', $request->file('image'));
            // $request->request->add(['imagen' => $path]);
            return response()->json(['file-path' => $path]);
        }

        $url = Storage::disk('s3')->url($filePath);
        return response()->json(['file-path' => $url]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $exists = Product::where('title', $request->title)->first();
        $exists_sku = Product::where('sku', $request->sku)->first();

        if ($exists) {
            return response()->json([
                'status' => 403,
                'message' => 'product exists',
            ]);
        }

        if ($exists_sku) {
            return response()->json([
                'status' => 403,
                'message' => 'sku exists',
            ]);
        }

        $product = Product::create($request->all());
        
        $product_warehouse = json_decode($request->product_warehouse, true);

        foreach ($product_warehouse as $value) {
            ProductWarehouse::create([
                'product_id' => $product->id,
                'warehouse_id' => $value['warehouse_id'],
                'unit_id' => $value['unit_id'],
                'stock' => $value['stock'],
                'umbral' => $value['umbral'],
            ]);
        }

        $product_price = json_decode($request->product_price, true);

        foreach ($product_price as $value) {
            ProductWallet::create([
                'product_id' => $product->id,
                'sucursal_id' => $value['sucursal_id'],
                'unit_id' => $value['unit_id'],
                'type_client' => $value['type_client'],
                'price' => $value['price'],
            ]);
        }

        if ($request->hasFile('image')) {
            // $filePath = $request->file('image')->store('uploads', 's3');

            $path = Storage::putFile('products', $request->file('image'));
            $product->update(['imagen' => $path]);

            // if($filePath == false){
            // } else {
            //     $url = Storage::disk('s3')->url($filePath);
            //     $product->update(['imagen' => $url]);
            // }
        }

        return response()->json([
            'status' => 201
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $product = Product::find($id);

        if (!$product) {
            return response()->json([
                'status' => 404,
                'message' => 'product not found',
            ]);
        }

        $product_resource = new ProductResource($product);

        return response()->json([
            'status' => 200,
            'product' => $product_resource
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $exists = Product::where('title', $request->title)->where('id', '<>', $id)->first();
        $exists_sku = Product::where('sku', $request->sku)->where('id', '<>', $id)->first();

        if ($exists) {
            return response()->json([
                'status' => 403,
                'message' => 'product exists',
            ]);
        }

        if ($exists_sku) {
            return response()->json([
                'status' => 403,
                'message' => 'sku exists',
            ]);
        }

        $product = Product::findOrFail($id);

        $product->update($request->all());

        if ($request->hasFile('image')) {
            // $filePath = $request->file('image')->store('uploads', 's3');

            if($product->imagen){
                Storage::delete($product->imagen);
            }

            $path = Storage::putFile('products', $request->file('image'));
            $product->update(['imagen' => $path]);

            // if($filePath == false){
            // } else {
            //     if($product->imagen){
            //         Storage::disk('s3')->delete($product->imagen);
            //     }

            //     $url = Storage::disk('s3')->url($filePath);
            //     $product->update(['imagen' => $url]);
            // }
        }

        return response()->json([
            'status' => 200
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $product = Product::findOrFail($id);
        $product->delete();
        return response()->json([
            'message' => 'product category'
        ]);
    }

    public function config()
    {
        $sucursales = Sucursale::where('status', 'Activo')->get();
        $warehouses = Warehouse::where('status', 'Activo')->get();
        $units = Unit::where('status', 'Activo')->get();
        $caregories = Category::where('status', 'Activo')->get();

        return response()->json([
            'sucursales' => $sucursales->map(function ($sucursal){
                return [
                    'id' => $sucursal->id,
                    'name' => $sucursal->name,
                ];
            }),
            'warehouses' => $warehouses->map(function ($warehouse){
                return [
                    'id' => $warehouse->id,
                    'name' => $warehouse->name,
                ];
            }),
            'units' => $units->map(function ($unit){
                return [
                    'id' => $unit->id,
                    'name' => $unit->name,
                ];
            }),
            'categories' => $caregories->map(function ($category){
                return [
                    'id' => $category->id,
                    'name' => $category->title,
                ];
            }),
        ]);
    }

    public function download_excel(Request $request)
    {
        $search         = $request->get('search');
        $category_id    = $request->get('category_id');
        $warehouse_id   = $request->get('warehouse_id');
        $unit_id        = $request->get('unit_id');
        $sucursale_id   = $request->get('sucursale_id');
        $available      = $request->get('available');
        $is_gift        = $request->get('is_gift');

        $products = Product::filterAdvance($search, $category_id, $warehouse_id, $unit_id, $sucursale_id, $available, $is_gift)
                            ->orderBy('id', 'desc')
                            ->get();

        return Excel::download(new ProductDownloadExcel($products), "lista_productos.xlsx");
    }
}
