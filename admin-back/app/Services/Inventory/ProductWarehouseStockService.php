<?php

namespace App\Services\Inventory;

use App\Models\ProductWarehouse;

class ProductWarehouseStockService
{
    public function lock(int $productId, int $unitId, int $warehouseId): ?ProductWarehouse
    {
        return ProductWarehouse::where('product_id', $productId)
            ->where('unit_id', $unitId)
            ->where('warehouse_id', $warehouseId)
            ->lockForUpdate()
            ->first();
    }

    public function increaseOrCreate(int $productId, int $unitId, int $warehouseId, float $quantity): ProductWarehouse
    {
        $record = $this->lock($productId, $unitId, $warehouseId);

        if (!$record) {
            return ProductWarehouse::create([
                'product_id' => $productId,
                'unit_id' => $unitId,
                'warehouse_id' => $warehouseId,
                'stock' => $quantity,
            ]);
        }

        $record->update([
            'stock' => $record->stock + $quantity,
        ]);

        return $record;
    }
}
