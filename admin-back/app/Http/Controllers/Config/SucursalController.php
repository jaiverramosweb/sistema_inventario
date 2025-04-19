<?php

namespace App\Http\Controllers\Config;

use App\Http\Controllers\Controller;
use App\Http\Resources\SucursalResource;
use App\Models\Sucursale;
use Illuminate\Http\Request;

class SucursalController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $search = $request->get('search');
        $sucursales = Sucursale::where('name', 'ilike', '%' . $search . '%')
            ->orderBy('id', 'desc')
            ->get();

        $sucursal_resource = SucursalResource::collection($sucursales);

        return response()->json([
            'sucursales' => $sucursal_resource
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $exists = Sucursale::where('name', $request->name)->first();

        if ($exists) {
            return response()->json([
                'status' => 403,
                'message' => 'sucursal exists',
            ]);
        }

        $sucursal = Sucursale::create($request->all());

        $sucursal_resource = new SucursalResource($sucursal);

        return response()->json([
            'status' => 201,
            'sucursales' => $sucursal_resource
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
        $exists = Sucursale::where('name', $request->name)->where('id', '<>', $id)->first();

        if ($exists) {
            return response()->json([
                'status' => 403,
                'message' => 'sucursal exists',
            ]);
        }

        $sucursal = Sucursale::findOrFail($id);

        $sucursal->update($request->all());

        $sucursal_resource = new SucursalResource($sucursal);

        return response()->json([
            'status' => 200,
            'sucursales' => $sucursal_resource
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $sucursal = Sucursale::findOrFail($id);
        $sucursal->delete();
        return response()->json([
            'message' => 'deleted sucursal'
        ]);
    }
}
