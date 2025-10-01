<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('conversions', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('user_id');
            $table->bigInteger('product_id');
            $table->bigInteger('warehause_id');
            $table->bigInteger('unit_start_id');
            $table->bigInteger('unit_end_id');
            $table->double('quantity_start')->default(0);
            $table->double('quantity_end')->default(0);
            $table->text('description')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        // Ajustar secuencia de ID para que empiece en 1000
        DB::statement("ALTER SEQUENCE conversions_id_seq RESTART WITH 1000;");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('conversions');
    }
};
