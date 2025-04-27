<?php

namespace App\Http\Controllers\Config;

use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $search = $request->get('search');
        $categories = Category::where('title', 'ilike', '%' . $search . '%')
            ->orderBy('id', 'desc')
            ->get();

        $categories_resource = CategoryResource::collection($categories);

        return response()->json([
            'status' => 201,
            'categories' => $categories_resource,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $exists = Category::where('title', $request->title)->first();

        if ($exists) {
            return response()->json([
                'status' => 403,
                'message' => 'category exists',
            ]);
        }

        if ($request->hasFile('image')) {
            $path = Storage::putFile('categores', $request->file('image'));
            $request->request->add(['imagen' => $path]);
        }

        $category = Category::create($request->all());

        $category_resource = new CategoryResource($category);

        return response()->json([
            'status' => 201,
            'category' => $category_resource
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
        $exists = Category::where('title', $request->title)->where('id', '<>', $id)->first();

        if ($exists) {
            return response()->json([
                'status' => 403,
                'message' => 'category exists',
            ]);
        }

        $category = Category::findOrFail($id);

        if ($request->hasFile('image')) {
            if ($category->imagen) {
                Storage::delete($category->imagen);
            }
            $path = Storage::putFile('categores', $request->file('image'));
            $request->request->add(['imagen' => $path]);
        }

        $category->update($request->all());

        $category_resource = new CategoryResource($category);

        return response()->json([
            'status' => 200,
            'category' => $category_resource
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $category = Category::findOrFail($id);
        $category->delete();
        return response()->json([
            'message' => 'deleted category'
        ]);
    }
}
