<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('product_warehouses', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('product_id')->comment('ID del producto');
            $table->unsignedInteger('warehouse_id')->comment('ID del almacén');
            $table->unsignedInteger('unit_id')->comment('ID de la unidad de medida');
            $table->double('stock')->default(0)->comment('Cantidad de stock');
            $table->integer('umbral')->default(0)->comment('Cantidad de umbral');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_warehouses');
    }
};
