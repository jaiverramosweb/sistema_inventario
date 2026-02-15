<?php

namespace App\Http\Controllers\CRM;

use App\Http\Controllers\Controller;
use App\Models\CrmActivity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CrmActivityController extends Controller
{
    public function index(Request $request)
    {
        $opportunity_id = $request->get('opportunity_id');
        $lead_id = $request->get('lead_id');

        $activities = CrmActivity::with(['user', 'opportunity', 'lead'])
            ->when($opportunity_id, function($query) use ($opportunity_id) {
                $query->where('opportunity_id', $opportunity_id);
            })
            ->when($lead_id, function($query) use ($lead_id) {
                $query->where('lead_id', $lead_id);
            })
            ->orderBy('activity_date', 'desc')
            ->get();

        return response()->json([
            'activities' => $activities
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'opportunity_id' => 'nullable|exists:opportunities,id',
            'lead_id' => 'nullable|exists:leads,id',
            'type' => 'required|string', // CALL, EMAIL, MEETING, NOTE, TASK
            'description' => 'required|string',
            'activity_date' => 'required|date'
        ]);

        $activity = CrmActivity::create([
            'opportunity_id' => $request->opportunity_id,
            'lead_id' => $request->lead_id,
            'user_id' => Auth::id(),
            'type' => $request->type,
            'description' => $request->description,
            'activity_date' => $request->activity_date
        ]);

        return response()->json([
            'message' => 'Actividad registrada exitosamente',
            'activity' => $activity
        ], 201);
    }

    public function update(Request $request, CrmActivity $activity)
    {
        $request->validate([
            'type' => 'nullable|string',
            'description' => 'nullable|string',
            'activity_date' => 'nullable|date'
        ]);

        $activity->update($request->only(['type', 'description', 'activity_date']));

        return response()->json([
            'message' => 'Actividad actualizada exitosamente',
            'activity' => $activity
        ]);
    }

    public function destroy(CrmActivity $activity)
    {
        $activity->delete();
        return response()->json([
            'message' => 'Actividad eliminada exitosamente'
        ]);
    }
}
