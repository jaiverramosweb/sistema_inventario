<?php

namespace App\Http\Controllers\CRM;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\Client;
use App\Models\Opportunity;
use App\Models\PipelineStage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class LeadConversionController extends Controller
{
    public function convert(Request $request, Lead $lead)
    {
        $request->validate([
            'create_opportunity' => 'boolean',
            'opportunity_name' => 'required_if:create_opportunity,true|string|max:255',
            'pipeline_stage_id' => 'required_if:create_opportunity,true|exists:pipeline_stages,id',
            // Client mandatory fields
            'type_client' => 'required|integer|in:1,2',
            'type_document' => 'required|string|max:30',
            'n_document' => 'required|string|max:100',
            'address' => 'required|string'
        ]);

        try {
            return DB::transaction(function () use ($request, $lead) {
                // 1. Create Client from Lead
                $client = Client::create([
                    'name' => $lead->name,
                    'surname' => $lead->surname,
                    'email' => $lead->email,
                    'phone' => $lead->phone,
                    'type_client' => $request->type_client,
                    'type_document' => $request->type_document,
                    'n_document' => $request->n_document,
                    'address' => $request->address,
                    'user_id' => Auth::id(),
                    'sucursal_id' => $lead->sucursal_id,
                    'status' => 1 // Active
                ]);

                // 2. Create Opportunity if requested
                $opportunity = null;
                if ($request->create_opportunity) {
                    $opportunity = Opportunity::create([
                        'name' => $request->opportunity_name,
                        'client_id' => $client->id,
                        'lead_id' => $lead->id,
                        'pipeline_stage_id' => $request->pipeline_stage_id,
                        'user_id' => Auth::id(),
                        'estimated_amount' => $request->estimated_amount ?? 0,
                        'priority' => $request->priority ?? 'MEDIUM'
                    ]);
                }

                // 3. Update Lead Status
                $lead->update(['status' => 'CONVERTED']);

                return response()->json([
                    'message' => 'Lead convertido exitosamente',
                    'client' => $client,
                    'opportunity' => $opportunity
                ]);
            });
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al convertir lead',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
