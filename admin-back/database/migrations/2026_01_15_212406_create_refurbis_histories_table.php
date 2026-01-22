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
        Schema::create('refurbish_history', function (Blueprint $table) {
            $table->id();

            // Equipo principal (Laptop/PC)
            $table->foreignId('product_id')->constrained('products');
            
            // Usuario que realizó la acción
            $table->foreignId('user_id')->constrained('users');
            
            // Sucursal donde ocurrió (Para el SaaS)
            $table->foreignId('sucursal_id')->nullable()->constrained('sucursales');

            // Acción: 'AGREGAR', 'QUITAR', 'REEMPLAZAR', 'DIAGNOSTICO', 'FINALIZADO'
            $table->string('action');

            // Componente afectado (si aplica)
            $table->unsignedBigInteger('component_id')->nullable();
            
            // Trazabilidad económica
            $table->double('previous_equipment_cost')->default(0); // Costo total antes del cambio
            $table->double('new_equipment_cost')->default(0);      // Costo total después del cambio
            $table->double('cost_impact')->default(0);             // La diferencia ($)
            
            // Información técnica
            $table->string('component_serial')->nullable();
            $table->text('comments')->nullable();
            
            $table->timestamps();

            // Index para búsquedas rápidas de historial por equipo
            $table->index('product_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('refurbis_histories');
    }
};
