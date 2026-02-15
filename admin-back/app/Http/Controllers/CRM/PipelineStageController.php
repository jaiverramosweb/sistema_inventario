<?php

namespace App\Http\Controllers\CRM;

use App\Http\Controllers\Controller;
use App\Models\PipelineStage;
use Illuminate\Http\Request;

class PipelineStageController extends Controller
{
    public function index()
    {
        $stages = PipelineStage::orderBy('order', 'asc')->get();
        return response()->json([
            'stages' => $stages
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'color' => 'nullable|string|max:7',
            'order' => 'nullable|integer',
            'description' => 'nullable|string'
        ]);

        $stage = PipelineStage::create($request->all());

        return response()->json([
            'message' => 'Etapa de pipeline creada exitosamente',
            'stage' => $stage
        ], 201);
    }

    public function update(Request $request, PipelineStage $stage)
    {
        $request->validate([
            'name' => 'nullable|string|max:255',
            'color' => 'nullable|string|max:7',
            'order' => 'nullable|integer',
            'description' => 'nullable|string'
        ]);

        $stage->update($request->all());

        return response()->json([
            'message' => 'Etapa de pipeline actualizada exitosamente',
            'stage' => $stage
        ]);
    }

    public function destroy(PipelineStage $stage)
    {
        $stage->delete();
        return response()->json([
            'message' => 'Etapa de pipeline eliminada exitosamente'
        ]);
    }

    public function reorder(Request $request)
    {
        $request->validate([
            'stages' => 'required|array',
            'stages.*.id' => 'required|exists:pipeline_stages,id',
            'stages.*.order' => 'required|integer'
        ]);

        foreach ($request->stages as $stageData) {
            PipelineStage::where('id', $stageData['id'])->update(['order' => $stageData['order']]);
        }

        return response()->json([
            'message' => 'Etapas reordenadas exitosamente'
        ]);
    }
}
