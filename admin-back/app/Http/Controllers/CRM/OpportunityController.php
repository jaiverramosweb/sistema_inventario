<?php

namespace App\Http\Controllers\CRM;

use App\Http\Controllers\Controller;
use App\Models\Opportunity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OpportunityController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->get('search');
        $pipeline_stage_id = $request->get('pipeline_stage_id');
        $priority = $request->get('priority');

        $opportunities = Opportunity::with(['pipelineStage', 'client', 'lead', 'user'])
            ->when($search, function($query) use ($search) {
                $query->where('name', 'ilike', "%$search%");
            })
            ->when($pipeline_stage_id, function($query) use ($pipeline_stage_id) {
                $query->where('pipeline_stage_id', $pipeline_stage_id);
            })
            ->when($priority, function($query) use ($priority) {
                $query->where('priority', $priority);
            })
            ->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 15));

        return response()->json([
            'opportunities' => $opportunities
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'estimated_amount' => 'nullable|numeric',
            'pipeline_stage_id' => 'required|exists:pipeline_stages,id',
            'client_id' => 'nullable|exists:clients,id',
            'lead_id' => 'nullable|exists:leads,id',
            'expected_closed_at' => 'nullable|date',
            'priority' => 'nullable|string',
            'description' => 'nullable|string'
        ]);

        $opportunity = Opportunity::create([
            'name' => $request->name,
            'estimated_amount' => $request->estimated_amount ?? 0,
            'pipeline_stage_id' => $request->pipeline_stage_id,
            'client_id' => $request->client_id,
            'lead_id' => $request->lead_id,
            'user_id' => Auth::id(),
            'expected_closed_at' => $request->expected_closed_at,
            'priority' => $request->priority ?? 'MEDIUM',
            'description' => $request->description
        ]);

        return response()->json([
            'message' => 'Oportunidad creada exitosamente',
            'opportunity' => $opportunity
        ], 201);
    }

    public function show(Opportunity $opportunity)
    {
        return response()->json([
            'opportunity' => $opportunity->load(['pipelineStage', 'client', 'lead', 'user', 'activities.user'])
        ]);
    }

    public function update(Request $request, Opportunity $opportunity)
    {
        $request->validate([
            'name' => 'nullable|string|max:255',
            'estimated_amount' => 'nullable|numeric',
            'pipeline_stage_id' => 'nullable|exists:pipeline_stages,id',
            'client_id' => 'nullable|exists:clients,id',
            'lead_id' => 'nullable|exists:leads,id',
            'expected_closed_at' => 'nullable|date',
            'closed_at' => 'nullable|date',
            'priority' => 'nullable|string',
            'description' => 'nullable|string'
        ]);

        $opportunity->update($request->only([
            'name', 'estimated_amount', 'pipeline_stage_id', 'client_id', 
            'lead_id', 'expected_closed_at', 'closed_at', 'priority', 'description'
        ]));

        return response()->json([
            'message' => 'Oportunidad actualizada exitosamente',
            'opportunity' => $opportunity
        ]);
    }

    public function destroy(Opportunity $opportunity)
    {
        $opportunity->delete();
        return response()->json([
            'message' => 'Oportunidad eliminada exitosamente'
        ]);
    }

    public function changeStage(Request $request, Opportunity $opportunity)
    {
        $request->validate([
            'pipeline_stage_id' => 'required|exists:pipeline_stages,id'
        ]);

        $opportunity->update([
            'pipeline_stage_id' => $request->pipeline_stage_id
        ]);

        return response()->json([
            'message' => 'Etapa cambiada exitosamente',
            'opportunity' => $opportunity
        ]);
    }
}
