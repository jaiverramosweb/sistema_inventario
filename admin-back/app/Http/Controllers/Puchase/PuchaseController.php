<?php

namespace App\Http\Controllers\Puchase;

use App\Http\Controllers\Controller;
use App\Http\Resources\PuchaseResource;
use App\Models\Provider;
use App\Models\Puchase;
use App\Models\PuchaseDetail;
use App\Models\Unit;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class PuchaseController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $search = $request->search;
        $warehouse_id = $request->warehouse_id;
        $unit_id = $request->unit_id;
        $provider_id = $request->provider_id;
        $type_comprobant = $request->type_comprobant;
        $start_date = $request->start_date;
        $end_date = $request->end_date;
        $search_product = $request->search_product;

        $purchases = Puchase::filterAdvance($search, $warehouse_id, $unit_id, $provider_id, $type_comprobant, $start_date, $end_date, $search_product)
            ->orderBy('id', 'desc')
            ->paginate(25);

        $purchases_collection = PuchaseResource::collection($purchases);

        return response()->json([
            'status' => 200,
            'data' => $purchases_collection,
            "last_page" => $purchases_collection->lastPage()
        ]);
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

    public function pushases_pdf($id)
    {
        $purchase = Puchase::findOrFail($id);

        $pdf = Pdf::loadView('purchase.purchase_pdf', compact('purchase'));
        return $pdf->stream('compra_'.$id.'.pdf');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $puchase = Puchase::create([
            'warehouse_id' => $request->warehouse_id,
            'user_id' => auth('api')->user()->id,
            'sucuarsal_id' =>  auth('api')->user()->sucuarsal_id,
            'date_emition' => $request->date_emition,
            'type_comprobant' => $request->type_comprobant,
            'n_comprobant' => $request->n_comprobant,
            'provider_id' => $request->provider_id,
            'total' => $request->total,
            'immporte' => $request->importe,
            'iva' => $request->iva,
            'description' => $request->description
        ]);

        foreach ($request->pushase_details as $value) {
            PuchaseDetail::create([
                'puchase_id' => $puchase->id,
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
        $purchase = Puchase::findOrFail($id);

        return response()->json([
            'status' => 200,
            'data' => new PuchaseResource($purchase)
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $purchase = Puchase::findOrFail($id);
        $purchase->update($request->all());

        return response()->json([
            'status' => 200
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $purchase = Puchase::findOrFail($id);
        if($purchase->state != 1){
            return response()->json([
            'status' => 403,
            'message' => 'No puedes eliminar esta compra por que ya a iniciado su proceso de entrega'
        ]);
        }
        
        $purchase->delete();

        return response()->json([
            'status' => 200
        ]);
    }
}
