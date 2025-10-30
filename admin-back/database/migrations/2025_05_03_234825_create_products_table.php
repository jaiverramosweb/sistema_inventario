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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('category_id');
            $table->string('title', 250);
            $table->string('imagen', 250)->nullable();
            $table->double('price_general')->comment('Precio de venta general');
            $table->double('price_company')->comment('Precio de venta por empresa')->nullable();
            $table->text('description')->nullable();
            $table->smallInteger('is_discount')->default(1)->comment('1: Si, 2: No');
            $table->double('max_descount')->default(1)->comment('Descuento máximo permitido');
            $table->smallInteger('is_gift')->default(1)->comment('1: Es gratis, 2: No es gratis');
            $table->smallInteger('available')->default(2)->comment('1: se puede vender sin stok, 2: no se puede vender sin stok');
            $table->enum('status', ['Activo', 'Inactivo'])->default('Activo');
            $table->smallInteger('status_stok')->default(1)->comment('1: Disponible, 2: Por agotar, 3: Agotado');
            $table->double('warranty_day')->default(0)->comment('Días de garantía');
            $table->smallInteger('tax_selected')->default(1)->comment('1: esta sujeto a impuesto, 2: no esta sujeto a impuesto');
            $table->double('importe_iva')->default(18)->comment('importe del IVA es en porcentaje');
            $table->string('sku', 100)->nullable()->comment('Stock Keeping Unit');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
