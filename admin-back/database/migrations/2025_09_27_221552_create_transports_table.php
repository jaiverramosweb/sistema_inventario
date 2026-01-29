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
        Schema::create('transports', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('user_id');
            $table->bigInteger('warehause_start_id');
            $table->bigInteger('warehause_end_id');
            $table->timestamp('date_emision')->nullable();
            $table->smallInteger('state')->default(1)->comment('1: Solicitud 2: Revision salida 3: Salida 4: Llegada 5: Revision llegada 6: entrega');
            $table->double('impote')->default(0);
            $table->double('total')->default(0);
            $table->double('iva')->default(0);
            $table->timestamp('date_delivery')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        // Ajustar secuencia de ID para que empiece en 1000
        DB::statement("ALTER SEQUENCE transports_id_seq RESTART WITH 1000;");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transports');
    }
};
