<?php

namespace App\Http\Controllers\Transport;

use App\Http\Controllers\Controller;
use App\Http\Resources\TransportResource;
use App\Models\Transport;
use App\Models\TransportDetail;
use App\Models\Unit;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;

class TransportController extends Controller
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
        Gate::authorize('viewAny', Transport::class);

        $search = $request->search;
        $warehause_start_id = $request->warehause_start_id;
        $warehause_end_id = $request->warehause_end_id;
        $unit_id = $request->unit_id;
        $start_date = $request->start_date;
        $end_date = $request->end_date;
        $search_product = $request->search_product;

         $transports = Transport::filterAdvance($search, $warehause_start_id, $warehause_end_id, $unit_id, $start_date, $end_date, $search_product)
            ->orderBy('id', 'desc')
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

    public function transports_pdf($id)
    {
        $transport = Transport::findOrFail($id);

        $pdf = Pdf::loadView('transport.transport_pdf', compact('transport'));
        return $pdf->stream('compra_'.$id.'.pdf');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        Gate::authorize('create', Transport::class);

        $validator = Validator::make($request->all(), [
            'warehause_start_id' => ['required', 'integer', 'exists:warehouses,id', 'different:warehause_end_id'],
            'warehause_end_id' => ['required', 'integer', 'exists:warehouses,id'],
            'date_emision' => ['required', 'date'],
            'description' => ['nullable', 'string'],
            'transport_details' => ['required', 'array', 'min:1'],
            'transport_details.*.product.id' => ['required', 'integer', 'exists:products,id'],
            'transport_details.*.unit_id' => ['required', 'integer', 'exists:units,id'],
            'transport_details.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'transport_details.*.price_unit' => ['required', 'numeric', 'min:0'],
            'transport_details.*.total' => ['required', 'numeric', 'min:0'],
        ]);

        if ($validator->fails()) {
            return $this->errorResponse(422, 'VALIDATION_ERROR', 'Datos de traslado invalidos.', $validator->errors()->toArray());
        }

        $transport = null;

        DB::transaction(function () use ($request, &$transport) {
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
        $transport = Transport::find($id);

        if (!$transport) {
            return $this->errorResponse(404, 'TRANSPORT_NOT_FOUND', 'El traslado solicitado no existe.');
        }
        
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
        Gate::authorize('update', Transport::class);

        $transport = Transport::find($id);

        if (!$transport) {
            return $this->errorResponse(404, 'TRANSPORT_NOT_FOUND', 'El traslado solicitado no existe.');
        }

        $validator = Validator::make($request->all(), [
            'warehause_start_id' => ['sometimes', 'integer', 'exists:warehouses,id', 'different:warehause_end_id'],
            'warehause_end_id' => ['sometimes', 'integer', 'exists:warehouses,id'],
            'date_emision' => ['sometimes', 'date'],
            'state' => ['sometimes', 'integer'],
            'impote' => ['sometimes', 'numeric', 'min:0'],
            'total' => ['sometimes', 'numeric', 'min:0'],
            'iva' => ['sometimes', 'numeric', 'min:0'],
            'description' => ['sometimes', 'nullable', 'string'],
        ]);

        if ($validator->fails()) {
            return $this->errorResponse(422, 'VALIDATION_ERROR', 'Datos de traslado invalidos.', $validator->errors()->toArray());
        }

        if($request->state >= 3 && $transport->state < 3){
            $n_details = TransportDetail::where('transport_id', $id)->count();
            $n_state_exit = TransportDetail::where('transport_id', $id)->where('state', 2)->count();

            if($n_details != $n_state_exit){
                return $this->errorResponse(403, 'TRANSPORT_STATE_FORBIDDEN', 'No puedes cambiar el estado de la solicitud por que aun los productos estan en pendiente');
            }

        }

        if($request->state == 6){
            $n_details = TransportDetail::where('transport_id', $id)->count();
            $n_state_delivery = TransportDetail::where('transport_id', $id)->where('state', 3)->count();

            if($n_details != $n_state_delivery){
                return $this->errorResponse(403, 'TRANSPORT_STATE_FORBIDDEN', 'No puedes cambiar el estado de la solicitud por que aun los productos estan en salida');
            }
        }
        
        if($transport->state >= 3){
            if($request->has('warehause_start_id') && $transport->warehause_start_id != $request->warehause_start_id){
                return $this->errorResponse(403, 'TRANSPORT_WAREHOUSE_LOCKED', 'No puedes cambiar el almacen de atencion');
            }

            if($request->has('warehause_end_id') && $transport->warehause_end_id != $request->warehause_end_id){
                return $this->errorResponse(403, 'TRANSPORT_WAREHOUSE_LOCKED', 'No puedes cambiar el almacen de recepcion');
            }
        }

        $allowed = [
            'warehause_start_id',
            'warehause_end_id',
            'date_emision',
            'state',
            'impote',
            'total',
            'iva',
            'description',
        ];

        $payload = collect($request->all())->only($allowed)->toArray();

        if($transport->state < 3 && $request->state == 3){
            date_default_timezone_set('America/Bogota');
            $payload['date_exit'] = now();
        }

        if($transport->state < 6 && $request->state == 6){
            date_default_timezone_set('America/Bogota');
            $payload['date_delivery'] = now();
        }

        DB::transaction(function () use ($id, $payload) {
            $transport = Transport::where('id', $id)->lockForUpdate()->first();
            if (!$transport) {
                return;
            }

            $transport->update($payload);
        });

        return response()->json([
            'status' => 200
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        Gate::authorize('delete', Transport::class);

        $transport = Transport::find($id);

        if (!$transport) {
            return $this->errorResponse(404, 'TRANSPORT_NOT_FOUND', 'El traslado solicitado no existe.');
        }

        if($transport->state >= 3){
            return $this->errorResponse(403, 'TRANSPORT_DELETE_FORBIDDEN', 'No puedes eliminar la solicitud de transporte por que ya inicio su proceso de entrega');
        }
        
        $transport->delete();

        return response()->json([
            'status' => 200
        ]);
    }
}
