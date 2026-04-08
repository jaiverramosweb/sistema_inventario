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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;

class PuchaseController extends Controller
{
    private function errorResponse(int $status, string $code, string $message, array $errors = [])
    {
        $body = [
            'status' => $status,
            'code' => $code,
            'message' => $message,
        ];

        if (!empty($errors)) {
            $body['errors'] = $errors;
        }

        return response()->json($body, $status);
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        Gate::authorize('viewAny', Puchase::class);

        $search = $request->search;
        $warehouse_id = $request->warehouse_id;
        $unit_id = $request->unit_id;
        $provider_id = $request->provider_id;
        $type_comprobant = $request->type_comprobant;
        $start_date = $request->start_date;
        $end_date = $request->end_date;
        $search_product = $request->search_product;

        $user = auth('api')->user();

        $purchases = Puchase::filterAdvance($search, $warehouse_id, $unit_id, $provider_id, $type_comprobant, $start_date, $end_date, $search_product, $user)
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
        Gate::authorize('create', Puchase::class);

        $validator = Validator::make($request->all(), [
            'warehouse_id' => ['required', 'integer', 'exists:warehouses,id'],
            'provider_id' => ['required', 'integer', 'exists:providers,id'],
            'type_comprobant' => ['required', 'string', 'max:50'],
            'n_comprobant' => ['nullable', 'string', 'max:120'],
            'date_emition' => ['nullable', 'date'],
            'date_emission' => ['nullable', 'date'],
            'description' => ['nullable', 'string'],
            'pushase_details' => ['required', 'array', 'min:1'],
            'pushase_details.*.product.id' => ['required', 'integer', 'exists:products,id'],
            'pushase_details.*.unit_id' => ['required', 'integer', 'exists:units,id'],
            'pushase_details.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'pushase_details.*.price_unit' => ['required', 'numeric', 'min:0'],
            'pushase_details.*.total' => ['required', 'numeric', 'min:0'],
        ]);

        if ($validator->fails()) {
            return $this->errorResponse(422, 'VALIDATION_ERROR', 'Datos de compra invalidos.', $validator->errors()->toArray());
        }

        $dateEmission = $request->date_emition ?? $request->date_emission;

        if(!$dateEmission){
            return $this->errorResponse(422, 'VALIDATION_ERROR', 'El campo fecha de emision es obligatorio.');
        }

        $puchase = null;

        DB::transaction(function () use ($request, $dateEmission, &$puchase) {
            $puchase = Puchase::create([
                'warehouse_id' => $request->warehouse_id,
                'user_id' => auth('api')->user()->id,
                'sucuarsal_id' =>  auth('api')->user()->sucuarsal_id,
                'date_emition' => $dateEmission,
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
        });

        return response()->json([
            'status' => 201
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $purchase = Puchase::find($id);

        if (!$purchase) {
            return $this->errorResponse(404, 'PURCHASE_NOT_FOUND', 'La compra solicitada no existe.');
        }

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
        Gate::authorize('update', Puchase::class);

        $purchase = Puchase::find($id);

        if (!$purchase) {
            return $this->errorResponse(404, 'PURCHASE_NOT_FOUND', 'La compra solicitada no existe.');
        }

        $payload = $request->all();
        if(isset($payload['date_emission']) && !isset($payload['date_emition'])){
            $payload['date_emition'] = $payload['date_emission'];
        }

        $validator = Validator::make($payload, [
            'warehouse_id' => ['sometimes', 'integer', 'exists:warehouses,id'],
            'date_emition' => ['sometimes', 'date'],
            'state' => ['sometimes', 'integer'],
            'type_comprobant' => ['sometimes', 'string', 'max:50'],
            'n_comprobant' => ['sometimes', 'nullable', 'string', 'max:120'],
            'provider_id' => ['sometimes', 'integer', 'exists:providers,id'],
            'total' => ['sometimes', 'numeric', 'min:0'],
            'immporte' => ['sometimes', 'numeric', 'min:0'],
            'importe' => ['sometimes', 'numeric', 'min:0'],
            'iva' => ['sometimes', 'numeric', 'min:0'],
            'description' => ['sometimes', 'nullable', 'string'],
        ]);

        if ($validator->fails()) {
            return $this->errorResponse(422, 'VALIDATION_ERROR', 'Datos de compra invalidos.', $validator->errors()->toArray());
        }

        $allowed = [
            'warehouse_id',
            'date_emition',
            'state',
            'type_comprobant',
            'n_comprobant',
            'provider_id',
            'total',
            'immporte',
            'iva',
            'description',
        ];

        if (isset($payload['importe']) && !isset($payload['immporte'])) {
            $payload['immporte'] = $payload['importe'];
        }

        $purchase->update(collect($payload)->only($allowed)->toArray());

        return response()->json([
            'status' => 200
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        Gate::authorize('delete', Puchase::class);

        $purchase = Puchase::find($id);

        if (!$purchase) {
            return $this->errorResponse(404, 'PURCHASE_NOT_FOUND', 'La compra solicitada no existe.');
        }

        if($purchase->state != 1){
            return $this->errorResponse(403, 'PURCHASE_DELETE_FORBIDDEN', 'No puedes eliminar esta compra por que ya a iniciado su proceso de entrega');
        }
        
        $purchase->delete();

        return response()->json([
            'status' => 200
        ]);
    }
}
