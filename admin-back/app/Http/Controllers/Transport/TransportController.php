<?php

namespace App\Http\Controllers\Transport;

use App\Http\Controllers\Controller;
use App\Http\Resources\TransportResource;
use App\Models\Transport;
use App\Models\TransportDetail;
use App\Models\Unit;
use App\Models\Warehouse;
use Illuminate\Http\Request;

class TransportController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
         $transports = Transport::orderBy('id', 'desc')
            ->paginate(25);

        $transports_collection = TransportResource::collection($transports);

        return response()->json([
            'status' => 200,
            'data' => $transports_collection,
            "last_page" => $transports_collection->lastPage()
        ]);
    }

    public function config()
    {
        date_default_timezone_set('America/Bogota');

        $warehouses = Warehouse::select('id', 'name', 'sucursal_id')->where('status', 'Activo')->get();
        $units = Unit::select('id', 'name')->where('status', 'Activo')->get();

        return response()->json([
            'warehouses' => $warehouses,
            'units' => $units,
            'today' => now()->format("Y-m-d")
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $transport = Transport::create([
            'user_id' => auth('api')->user()->id,
            'warehause_start_id' => $request->warehause_start_id,
            'warehause_end_id' => $request->warehause_end_id,
            'date_emision' => $request->date_emision,
            'impote' => $request->impote,
            'total' => $request->total,
            'iva' => $request->iva,
            'description' => $request->description
        ]);

        foreach ($request->transport_details as $value) {
            TransportDetail::create([
                'transport_id' => $transport->id,
                'product_id' => $value['product']['id'],
                'unit_id' => $value['unit_id'],
                'quantity' => $value['quantity'],
                'price_unit' => $value['price_unit'],
                'total' => $value['total'],
            ]);
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
        $transport = Transport::findOrFail($id);
        
        return response()->json([
            'status' => 200,
            'data' => new TransportResource($transport)
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $transport = Transport::findOrFail($id);
        
        if($transport->state >= 3){
            if($transport->warehause_start_id != $request->warehause_start_id){
                return response()->json([
                    'status' => 403,
                    'message' => 'No puedes cambiar el almacen de atención'
                ]);
            }

            if($transport->warehause_end_id != $request->warehause_end_id){
                return response()->json([
                    'status' => 403,
                    'message' => 'No puedes cambiar el almacen de recepción'
                ]);
            }
        }

        $transport->update($request->all());

        return response()->json([
            'status' => 200
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $transport = Transport::findOrFail($id);
        if($transport->state >= 3){
            return response()->json([
                'status' => 403,
                'message' => 'No puedes eliminar la solicitud de transporte por que ya inicio su proceso de entrega'
            ]);
        }
        
        $transport->delete();

        return response()->json([
            'status' => 200
        ]);
    }
}
