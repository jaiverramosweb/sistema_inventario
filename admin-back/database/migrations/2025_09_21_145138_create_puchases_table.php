<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('puchases', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('warehouse_id');
            $table->bigInteger('user_id');
            $table->bigInteger('sucuarsal_id');
            $table->timestamp('date_emition')->nullable();
            $table->smallInteger('state')->default(1)->nullable()->comment('1: Solicitud 2: Parcial 3: Entregado');
            $table->string('type_comprobant', 100)->nullable();
            $table->string('n_comprobant', 100)->nullable();
            $table->bigInteger('provider_id');
            $table->double('total');
            $table->double('immporte');
            $table->double('iva');
            $table->timestamp('date_delivery')->nullable();
            $table->timestamp('date_exit')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        // Ajustar secuencia de ID para que empiece en 1000
        DB::statement("ALTER SEQUENCE puchases_id_seq RESTART WITH 1000;");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('puchases');
    }
};
