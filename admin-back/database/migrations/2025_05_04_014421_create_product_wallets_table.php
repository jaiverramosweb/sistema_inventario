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
        Schema::create('product_wallets', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('product_id')->comment('ID del producto');
            $table->smallInteger('type_client')->default(1)->comment('Tipo de cliente: 1=Cliente, 2=Cliente empresa');
            $table->unsignedInteger('unit_id')->nullable()->comment('ID de la unidad de medida');
            $table->unsignedInteger('sucursal_id')->nullable()->comment('ID de la sucursal');
            $table->double('price');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_wallets');
    }
};
