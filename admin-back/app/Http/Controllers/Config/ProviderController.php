<?php

namespace App\Http\Controllers\Config;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProviderResource;
use App\Models\Provider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ProviderController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $search = $request->get('search');
        $providers = Provider::where(DB::raw("providers.name || ' ' || providers.ruc || ' ' || providers.phone || ' ' || COALESCE(providers.email,'')"), 'ilike', '%' . $search . '%')->orderBy('id', 'desc')->get();

        $providers_resource = ProviderResource::collection($providers);

        return response()->json([
            'status' => 200,
            'providers' => $providers_resource,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $exists = Provider::where('ruc', $request->ruc)->first();

        if ($exists) {
            return response()->json([
                'status' => 403,
                'message' => 'Provider exists',
            ]);
        }

        if ($request->hasFile('image')) {
            $path = Storage::putFile('providers', $request->file('image'));
            $request->request->add(['imagen' => $path]);
        }

        $provider = Provider::create($request->all());

        $providers_resource = new ProviderResource($provider);

        return response()->json([
            'status' => 201,
            'provider' => $providers_resource
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
        $exists = Provider::where('ruc', $request->ruc)->where('id', '<>', $id)->first();

        if ($exists) {
            return response()->json([
                'status' => 403,
                'message' => 'Provider exists',
            ]);
        }

        $provider = Provider::findOrFail($id);

        if ($request->hasFile('image')) {
            if ($provider->imagen) {
                Storage::delete($provider->imagen);
            }
            $path = Storage::putFile('providers', $request->file('image'));
            $request->request->add(['imagen' => $path]);
        }

        $provider->update($request->all());

        $providers_resource = new ProviderResource($provider);

        return response()->json([
            'status' => 200,
            'provider' => $providers_resource
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $provider = Provider::findOrFail($id);
        $provider->delete();
        return response()->json([
            'message' => 'deleted provider'
        ]);
    }
}
