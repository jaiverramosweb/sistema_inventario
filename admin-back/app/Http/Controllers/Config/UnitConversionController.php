<?php

namespace App\Http\Controllers\Config;

use App\Http\Controllers\Controller;
use App\Http\Resources\UnitConversionResource;
use App\Models\UnitConversion;
use Illuminate\Http\Request;

class UnitConversionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $unit_id = $request->get('unit_id');
        $unit_conversions = UnitConversion::where('unit_id', $unit_id)
            ->orderBy('id', 'desc')
            ->get();

        $unit_conversion_resource = UnitConversionResource::collection($unit_conversions);

        return response()->json([   
            'unit_conversions' => $unit_conversion_resource
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $exists = UnitConversion::where('unit_id', $request->unit_id)
            ->where('unit_to_id', $request->unit_to_id)
            ->first();
            
        if ($exists) {
            return response()->json([
                'status' => 403,
                'message' => 'Unit conversion already exists',
            ]);
        }

        $unit_conversion = UnitConversion::create($request->all());
        $unit_conversion_resource = new UnitConversionResource($unit_conversion);
        return response()->json([
            'status' => 201,
            'unit_conversion' => $unit_conversion_resource
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
        $unit_conversion = UnitConversion::find($id);
        if ($unit_conversion) {
            $unit_conversion->delete();
            return response()->json([
                'status' => 200,
                'message' => 'Unit conversion deleted successfully'
            ]);
        } else {
            return response()->json([
                'status' => 404,
                'message' => 'Unit conversion not found'
            ]);
        }
    }
}
