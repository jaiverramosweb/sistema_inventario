<?php

namespace App\Http\Controllers\Config;

use App\Http\Controllers\Controller;
use App\Http\Resources\UnitResource;
use App\Models\Unit;
use Illuminate\Http\Request;

class UnitController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $search = $request->get('search');
        $units = Unit::where('name', 'ilike', '%' . $search . '%')
            ->orderBy('id', 'desc')
            ->get();

        $units_resource = UnitResource::collection($units);

        return response()->json([
            'units' => $units_resource
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $exists = Unit::where('name', $request->name)->first();

        if ($exists) {
            return response()->json([
                'status' => 403,
                'message' => 'unidad exists',
            ]);
        }

        $unit = Unit::create($request->all());

        $unit_resource = new UnitResource($unit);

        return response()->json([
            'status' => 201,
            'unit' => $unit_resource
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
        $exists = Unit::where('name', $request->name)->where('id', '<>', $id)->first();

        if ($exists) {
            return response()->json([
                'status' => 403,
                'message' => 'unidad exists',
            ]);
        }

        $unit = Unit::findOrFail($id);

        $unit->update($request->all());

        $unit_resource = new UnitResource($unit);

        return response()->json([
            'status' => 200,
            'unit' => $unit_resource
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $unit = Unit::findOrFail($id);
        $unit->delete();
        return response()->json([
            'message' => 'deleted unit'
        ]);
    }
}
