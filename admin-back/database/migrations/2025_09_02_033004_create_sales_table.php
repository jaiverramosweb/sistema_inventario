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
        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('user_id');
            $table->bigInteger('client_id');
            $table->smallInteger('type_client')->default(1)->comment('1: cliente final, 2: cliente empresa');
            $table->bigInteger('sucursal_id');
            $table->double('subtotal')->default(0);
            $table->double('total')->default(0);
            $table->double('iva')->default(0);
            $table->smallInteger('state')->default(1)->comment('1: activo, 2: inactivo');
            $table->smallInteger('state_mayment')->default(1)->comment('1: pago pendiente, 2: pago carcial, 3: pago total');
            $table->double('debt')->default(0)->comment('deuda');
            $table->double('paid_out')->default(0)->comment('pagado');
            $table->timestamp('date_validation')->nullable()->comment('fecha de validacion del pago');
            $table->timestamp('date_completed')->nullable();
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
        Schema::dropIfExists('sales');
    }
};
