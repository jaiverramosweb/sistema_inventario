<?php

namespace App\Http\Controllers\Refurbish;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\RefurbishService;
use Illuminate\Http\Request;

class RefurbishController extends Controller
{
    protected $refurbishService;

    public function __construct(RefurbishService $service)
    {
        $this->refurbishService = $service;
    }

    /**
     * Obtener el estado actual del equipo para el Workbench
     */
    public function show($id)
    {
        $equipment = Product::with([
            'category',
            'installedComponents.childProduct', // Piezas actuales
            'refurbishHistory'                  // Historial completo
        ])->findOrFail($id);

        return response()->json([
            'equipment' => $equipment,
            // Calculamos utilidad proyectada para el técnico/admin
            'projected_margin' => $equipment->price_general - ($equipment->base_cost + $equipment->refurbished_value)
        ]);
    }

    /**
     * Agregar una pieza al equipo
     */
    public function addComponent(Request $request)
    {
        $request->validate([
            'parent_id' => 'required|exists:products,id',
            'component_id' => 'required|exists:products,id',
            'custom_cost' => 'required|numeric|min:0',
            'apply_to_value' => 'required|boolean',
            'comments' => 'nullable|string'
        ]);

        try {
            $equipment = $this->refurbishService->addComponent(
                $request->parent_id,
                $request->component_id,
                $request->apply_to_value,
                $request->custom_cost,
                $request->comments
            );

            return response()->json([
                'message' => 'Componente instalado correctamente',
                'equipment' => $equipment
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    /**
     * Retirar una pieza del equipo
     */
    public function removeComponent(Request $request)
    {
        $request->validate([
            'product_item_id' => 'required|exists:product_items,id',
            'new_status' => 'required|in:OK,Repuesto,Venta,Daño', // Estados de tu entidad
            'reduce_value' => 'required|boolean',
            'comments' => 'nullable|string'
        ]);

        try {
            $equipment = $this->refurbishService->removeComponent(
                $request->product_item_id,
                $request->new_status,
                $request->reduce_value,
                $request->comments
            );

            return response()->json([
                'message' => 'Componente retirado y devuelto al inventario',
                'equipment' => $equipment
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    /**
     * Retirar pieza que no estaba registrada en el sistema
     */
    public function removeUnregistered(Request $request)
    {
        $request->validate([
            'parent_id' => 'required|exists:products,id',
            'title' => 'required|string',
            'serial' => 'nullable|string',
            'comments' => 'nullable|string'
        ]);

        try {
            $equipment = $this->refurbishService->removeUnregisteredComponent(
                $request->parent_id,
                $request->all()
            );

            return response()->json([
                'message' => 'Movimiento registrado en el historial',
                'equipment' => $equipment
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    /**
     * Finalizar proceso
     */
    public function finish(Request $request, $id)
    {
        $equipment = Product::findOrFail($id);
        
        $equipment->update([
            'refurbish_state' => 'Finalizado',
            'status' => 'Activo'
        ]);

        return response()->json(['message' => 'Equipo listo para la venta']);
    }
}
