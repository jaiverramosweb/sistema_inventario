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
        Schema::create('puchase_details', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('puchase_id');
            $table->bigInteger('product_id');
            $table->bigInteger('unit_id');
            $table->integer('quantity')->default(0);
            $table->double('price_unit')->default(0);
            $table->double('total')->default(0);
            $table->smallInteger('state')->default(1)->comment('1: Solicitud 2: Entregado');
            $table->bigInteger('user_delivery')->nullable();
            $table->timestamp('date_delivery')->nullable();
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
        Schema::dropIfExists('puchase_details');
    }
};
