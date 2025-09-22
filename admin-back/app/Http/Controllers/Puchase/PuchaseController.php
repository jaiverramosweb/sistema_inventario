<?php

namespace App\Http\Controllers\Puchase;

use App\Http\Controllers\Controller;
use App\Models\Provider;
use App\Models\Unit;
use App\Models\Warehouse;
use Illuminate\Http\Request;

class PuchaseController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    public function config()
    {
        date_default_timezone_set('America/Bogota');
        
        $warehouses = Warehouse::select('id', 'name', 'sucursal_id')->where('status', 'Activo')->get();
        $units = Unit::select('id', 'name')->where('status', 'Activo')->get();
        $providers = Provider::select('id', 'name', 'ruc')->where('status', 'Activo')->get();

        return response()->json([
            'warehouses' => $warehouses,
            'units' => $units,
            'providers' => $providers,
            'today' => now()->format("Y-m-d")
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
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
        //
    }
}
