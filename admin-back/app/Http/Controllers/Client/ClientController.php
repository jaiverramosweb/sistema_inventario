<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Http\Requests\ClientRequest;
use App\Http\Resources\ClientResource;
use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class ClientController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        Gate::authorize('viewAny', Client::class);

        $search         = $request->search;

        $user = auth('api')->user();

        $clients = Client::where(DB::raw("clients.name || ' ' || clients.n_document || ' ' || clients.phone || ' ' || COALESCE(clients.email,'')"), 'ilike', '%' . $search . '%')
                    ->where(function($query) use($user){
                        if($user->role_id != 1){
                            if($user->role_id == 2){
                                $query->where("sucursal_id", $user->sucuarsal_id);
                            } else {
                                $query->where("user_id", $user->id);
                            }
                        }
                    })
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
        Gate::authorize('create', Client::class);

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

        $user = auth('api')->user();

        $request->merge(['user_id' => $user->id]);
        $request->merge(['sucursal_id' => $user->sucuarsal_id]);

        // return response()->json([
        //     'user' => $request->all()
        // ]);

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
        Gate::authorize('update', Client::class);

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
        Gate::authorize('delete', Client::class);

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
