<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Http\Requests\ClientRequest;
use App\Http\Resources\ClientResource;
use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ClientController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $search         = $request->search;
        $category_id    = $request->category_id;
        $warehouse_id   = $request->warehouse_id;
        $unit_id        = $request->unit_id;
        $sucursale_id   = $request->sucursale_id;
        $available      = $request->available;
        $is_gift        = $request->is_gift;

        $clients = Client::where(DB::raw("clients.name || ' ' || clients.n_document || ' ' || clients.phone || ' ' || COALESCE(clients.email,'')"), 'ilike', '%' . $search . '%')
                    ->orderBy('id', 'desc')
                    ->paginate(15);

        $clients_collection = ClientResource::collection($clients);

        return response()->json([
            'data' => $clients_collection,
            'total' => $clients->total(),
            'last_page' => $clients->lastPage(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(ClientRequest $request)
    {
        $exists = Client::where('n_document', $request->n_document)->first();
        if ($exists) {
            return response()->json([
                'status' => 403,
                'message' => 'El número de documento ya está registrado',
            ]);
        }

        $exists_email = Client::where('email', $request->email)->first();
        if ($exists_email) {
            return response()->json([
                'status' => 403,
                'message' => 'El email ya está registrado',
            ]);
        }

        $request->request->add(['user_id' => auth('api')->user()->id]);
        $request->request->add(['sucursal_id' => auth('api')->user()->sucuarsal_id]);

        $client = Client::create($request->all());

        return response()->json([
            'status' => 201,
            'data' => new ClientResource($client),
            'message' => 'Cliente creado con éxito',
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
    public function update(ClientRequest $request, string $id)
    {
        $exists = Client::where('n_document', $request->n_document)->where('id', '<>', $id)->first();
        if ($exists) {
            return response()->json([
                'status' => 403,
                'message' => 'El número de documento ya está registrado',
            ]);
        }

        $exists_email = Client::where('email', $request->email)->where('id', '<>', $id)->first();
        if ($exists_email) {
            return response()->json([
                'status' => 403,
                'message' => 'El email ya está registrado',
            ]);
        }

        $client = Client::findOrFail($id);

        $client->update($request->all());

        return response()->json([
            'status' => 200,
            'data' => new ClientResource($client),
            'message' => 'Cliente actualizado con éxito',
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $client = Client::findOrFail($id);

        if (!$client) {
            return response()->json([
                'status' => 404,
                'message' => 'Cliente no encontrado',
            ]);
        }

        $client->delete();

        return response()->json([
            'status' => 200,
            'message' => 'Cliente eliminado con éxito',
        ]);
    }
}
