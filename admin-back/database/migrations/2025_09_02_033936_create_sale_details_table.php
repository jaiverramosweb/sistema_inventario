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
        Schema::create('sale_details', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('sale_id');
            $table->bigInteger('product_id');
            $table->bigInteger('product_categoryid');
            $table->bigInteger('unit_id');
            $table->bigInteger('warehouse_id')->nullable();
            $table->double('quantity')->default(1);
            $table->double('price_unit')->default(0);
            $table->double('discount')->nullable()->default(0);
            $table->double('iva')->default(0);
            $table->double('subtotal')->default(0)->comment('es el precio unitario menos el descuento');
            $table->double('total')->default(0)->comment('es el subtotal por la cantidad');
            $table->text('description')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sale_details');
    }
};
