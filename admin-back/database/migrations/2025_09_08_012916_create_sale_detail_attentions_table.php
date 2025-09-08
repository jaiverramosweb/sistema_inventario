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
        Schema::create('sale_detail_attentions', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('sale_detail_id');
            $table->bigInteger('product_id');
            $table->bigInteger('warehouse_id');
            $table->bigInteger('unit_id');
            $table->double('quantity');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sale_detail_attentions');
    }
};
