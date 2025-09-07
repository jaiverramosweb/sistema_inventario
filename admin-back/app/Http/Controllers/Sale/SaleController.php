<?php

namespace App\Http\Controllers\Sale;

use App\Http\Controllers\Controller;
use App\Http\Requests\SaleRequest;
use App\Http\Resources\SaleResource;
use App\Models\Client;
use App\Models\Sale;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SaleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $sales = Sale::orderBy('id', 'desc')
            ->get();

        $sale_resource = SaleResource::collection($sales);

        return response()->json([
            'sales' => $sale_resource
        ]);
    }

    public function config()
    {
        $today = now()->format('Y-m-d');
        $warehouses = Warehouse::where('status', 'Activo')->orderBy('id', 'desc')
            ->get();

        return response()->json([
            'today' => $today,
            'warehouses' => $warehouses->map(function ($warehouse) {
                return [
                    'id' => $warehouse->id,
                    'name' => $warehouse->name,
                    'sucursale_id' => $warehouse->sucursal_id,
                ];
            })
        ]);
    }

    public function searchClient(Request $request)
    {
        $search = $request->get('search');

        if (!$search) {
            return response()->json([
                'clients' => []
            ]);
        }

         $clients = Client::where(DB::raw("clients.name || ' ' || clients.n_document || ' ' || clients.phone || ' ' || COALESCE(clients.email,'')"), 'ilike', '%' . $search . '%')
                    ->where('status', 1)
                    ->orderBy('id', 'desc')
                    ->get();

        return response()->json([
            'clients' => $clients->map(function ($client) {
                return [
                    'id' => $client->id,
                    'name' => $client->name,
                    'type_document' => $client->type_document,
                    'n_document' => $client->n_document,
                    'phone' => $client->phone,
                    'type_client' => $client->type_client,
                ];
            })
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(SaleRequest $request)
    {
        Sale::create($request->validated());

        return response()->json([
            'status' => 201
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $sale = Sale::findOrFail($id);

        return response()->json([
            'status' => 200,
            'sale' => new SaleResource($sale)
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(SaleRequest $request, string $id)
    {
        $sale = Sale::findOrFail($id);
        $sale->update($request->validated());

        return response()->json([
            'status' => 200
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $sale = Sale::findOrFail($id);
        $sale->delete();

        return response()->json([
            'status' => 200
        ]);
    }
}
