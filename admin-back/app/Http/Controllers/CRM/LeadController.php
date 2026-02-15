<?php

namespace App\Http\Controllers\CRM;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LeadController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->get('search');
        $status = $request->get('status');

        $leads = Lead::with(['user', 'sucursale'])
            ->when($search, function($query) use ($search) {
                $query->where('name', 'ilike', "%$search%")
                      ->orWhere('email', 'ilike', "%$search%")
                      ->orWhere('phone', 'ilike', "%$search%");
            })
            ->when($status, function($query) use ($status) {
                $query->where('status', $status);
            })
            ->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 15));

        return response()->json([
            'leads' => $leads
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'surname' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'source' => 'nullable|string|max:255',
            'status' => 'nullable|string',
            'sucursal_id' => 'nullable|exists:sucursales,id'
        ]);

        $lead = Lead::create([
            'name' => $request->name,
            'surname' => $request->surname,
            'email' => $request->email,
            'phone' => $request->phone,
            'source' => $request->source,
            'status' => $request->status ?? 'NEW',
            'user_id' => Auth::id(),
            'sucursal_id' => $request->sucursal_id
        ]);

        return response()->json([
            'message' => 'Lead creado exitosamente',
            'lead' => $lead
        ], 201);
    }

    public function show(Lead $lead)
    {
        return response()->json([
            'lead' => $lead->load(['user', 'sucursale', 'activities.user', 'opportunities'])
        ]);
    }

    public function update(Request $request, Lead $lead)
    {
        $request->validate([
            'name' => 'nullable|string|max:255',
            'surname' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'source' => 'nullable|string|max:255',
            'status' => 'nullable|string',
            'sucursal_id' => 'nullable|exists:sucursales,id'
        ]);

        $lead->update($request->only([
            'name', 'surname', 'email', 'phone', 'source', 'status', 'sucursal_id'
        ]));

        return response()->json([
            'message' => 'Lead actualizado exitosamente',
            'lead' => $lead
        ]);
    }

    public function destroy(Lead $lead)
    {
        $lead->delete();
        return response()->json([
            'message' => 'Lead eliminado exitosamente'
        ]);
    }
}
