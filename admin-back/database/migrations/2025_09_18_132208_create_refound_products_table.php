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
        Schema::create('refound_products', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('user_id');
            $table->bigInteger('client_id');
            $table->bigInteger('product_id');
            $table->bigInteger('unit_id');
            $table->bigInteger('warehouse_id');
            $table->bigInteger('sale_detail_id');
            $table->double('quantity');
            $table->smallInteger('type')->comment('1: Reparacion 2: Remplazo 3: Devolución');
            $table->smallInteger('state')->default(0)->comment('0: Si estado 1: Pendiente 2: Revision 3: Reparado 4: Descartado');
            $table->text('description')->nullable();
            $table->timestamp('resoslution_date')->nullable();
            $table->text('resoslution_description')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('refound_products');
    }
};
