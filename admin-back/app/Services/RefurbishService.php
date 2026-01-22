<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductItems;
use App\Models\RefurbishHistory;
use Illuminate\Support\Facades\DB;

class RefurbishService {
    
    /**
     * OPERACIÓN: AGREGAR COMPONENTE
     */
    public function addComponent($equipmentId, $componentId, $applyCost, $customCost, $comments) {
        return DB::transaction(function () use ($equipmentId, $componentId, $applyCost, $customCost, $comments) {
            $equipment = Product::findOrFail($equipmentId);
            $component = Product::findOrFail($componentId);

            // 1. Crear el vínculo en product_items
            ProductItems::create([
                'parent_product_id' => $equipment->id,
                'child_product_id' => $component->id,
                'cost_at_installation' => $customCost,
                'affects_final_price' => $applyCost,
                'user_id' => auth()->id()
            ]);

            // 2. Si el usuario decidió sumar al valor del equipo
            if ($applyCost) {
                $equipment->increment('refurbished_value', $customCost);
            }

            // 3. Bloquear el componente (Ya no está disponible para venta individual)
            $component->update([
                'status_stok' => 3, // Agotado (porque está dentro de otro equipo)
                'status' => 'Inactivo' 
            ]);

            // 4. Registrar Historial (Auditoría)
            $this->logHistory($equipment, 'AGREGAR', $component, $customCost, $comments);

            return $equipment;
        });
    }

    /**
     * OPERACIÓN: QUITAR COMPONENTE
     */
    public function removeComponent($productItemId, $newStatusForComponent, $reduceValue) {
        return DB::transaction(function () use ($productItemId, $newStatusForComponent, $reduceValue) {
            $item = ProductItems::with('childProduct', 'parentProduct')->findOrFail($productItemId);
            $equipment = $item->parentProduct;
            $component = $item->childProduct;

            $costToRemove = $item->cost_at_installation;

            // 1. Si afectaba el precio, restarlo del valor del equipo
            if ($item->affects_final_price && $reduceValue) {
                $equipment->decrement('refurbished_value', $costToRemove);
            }

            // 2. Liberar la pieza al inventario general
            $component->update([
                'condition_status' => $newStatusForComponent, // ej: 'Repuesto' o 'Venta'
                'status' => 'Activo',
                'status_stok' => 1 // Disponible
            ]);

            // 3. Eliminar vínculo
            $item->delete();

            // 4. Historial
            $this->logHistory($equipment, 'QUITAR', $component, -$costToRemove, "Pieza retirada. Nueva condición: $newStatusForComponent");

            return $equipment;
        });
    }

    /**
     * OPERACIÓN: RETIRAR COMPONENTE NO REGISTRADO (Encontrado en diagnóstico)
     */
    public function removeUnregisteredComponent($equipmentId, $data) {
        return DB::transaction(function () use ($equipmentId, $data) {
            $equipment = Product::findOrFail($equipmentId);

            // Registro de historia sin componente de inventario vinculado
            RefurbishHistory::create([
                'product_id' => $equipment->id,
                'user_id' => auth()->id(),
                'sucursal_id' => $data['sucursal_id'] ?? 1,
                'action' => 'QUITAR',
                'component_id' => null,
                'previous_equipment_cost' => $equipment->total_cost,
                'new_equipment_cost' => $equipment->total_cost, // No impacta costo monetario ya que no existía
                'cost_impact' => 0,
                'component_serial' => $data['serial'] ?? null,
                'comments' => "Retiro de pieza NO registrada: " . ($data['title'] ?? 'Sin nombre') . ". " . ($data['comments'] ?? '')
            ]);

            return $equipment;
        });
    }

    private function logHistory($equipment, $action, $component, $impact, $comments) {
        // Obtenemos el costo total calculado (base + piezas)
        $previousCost = $equipment->total_cost - ($action == 'AGREGAR' ? 0 : $impact);
        $newCost = $previousCost + $impact;

        RefurbishHistory::create([
            'product_id' => $equipment->id,
            'user_id' => auth()->id(),
            'sucursal_id' => $equipment->sucursal_id ?? 1, // Fallback a sucursal 1 si no está definida
            'action' => $action,
            'component_id' => $component ? $component->id : null,
            'previous_equipment_cost' => $previousCost,
            'new_equipment_cost' => $newCost,
            'cost_impact' => $impact,
            'component_serial' => $component ? $component->serial : null,
            'comments' => $comments
        ]);
    }
}