<?php

namespace App\Http\Controllers\Config;

use App\Http\Controllers\Controller;
use App\Http\Requests\WarehouseRequest;
use App\Http\Resources\WarehouseResource;
use App\Models\Sucursale;
use App\Models\Warehouse;
use Illuminate\Http\Request;

class WarehouseController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $sucursales = Sucursale::all();

        $search = $request->get('search');
        $warehouses = Warehouse::where('name', 'ilike', '%' . $search . '%')
            ->orderBy('id', 'desc')
            ->get();

        $warehouse_resource = WarehouseResource::collection($warehouses);

        return response()->json([
            'status' => 201,
            'warehouses' => $warehouse_resource,
            'sucursales' => $sucursales->map(function ($sucursale) {
                return [
                    'id' => $sucursale->id,
                    'name' => $sucursale->name
                ];
            })
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(WarehouseRequest $request)
    {
        $exists = Warehouse::where('name', $request->name)->first();

        if ($exists) {
            return response()->json([
                'status' => 403,
                'message' => 'warehouse exists',
            ]);
        }

        $warehouse = Warehouse::create($request->all());

        $warehouse_resource = new WarehouseResource($warehouse);

        return response()->json([
            'status' => 201,
            'warehouses' => $warehouse_resource
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
    public function update(WarehouseRequest $request, string $id)
    {
        $exists = Warehouse::where('name', $request->name)->where('id', '<>', $id)->first();

        if ($exists) {
            return response()->json([
                'status' => 403,
                'message' => 'warehouse exists',
            ]);
        }

        $warehouse = Warehouse::findOrFail($id);

        $warehouse->update($request->all());

        $warehouse_resource = new WarehouseResource($warehouse);

        return response()->json([
            'status' => 200,
            'warehouses' => $warehouse_resource
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $warehouse = Warehouse::findOrFail($id);
        $warehouse->delete();
        return response()->json([
            'message' => 'deleted warehouse'
        ]);
    }
}
