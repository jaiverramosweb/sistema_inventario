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
        Schema::table('products', function (Blueprint $table) {
            // Campos de Identificación de Hardware
            $table->string('brand', 100)->nullable()->after('title');
            $table->string('model', 100)->nullable()->after('brand');
            $table->string('serial', 150)->nullable()->unique()->after('sku');
            $table->string('part_number', 150)->nullable()->after('serial');
            $table->string('internal_code', 100)->nullable()->after('part_number');
            $table->enum('equipment_type', ['Laptop', 'Desktop', 'All-in-one', 'Minipc', 'Componente', 'Otros'])
                ->default('Componente')
                ->after('category_id');

            // Estados de Reacondicionamiento
            // Usamos condition_status para no chocar con tu 'status' (Activo/Inactivo)
            $table->enum('condition_status', ['OK', 'Repuesto', 'Venta', 'Daño', 'Nuevo', 'Usado'])
                ->default('Usado')
                ->after('status');
            
            $table->enum('refurbish_state', ['Ninguno', 'Pendiente Diagnostico', 'En Proceso', 'Finalizado'])
                ->default('Ninguno')
                ->after('condition_status');

            // Control de Costos (Complemento a tu price_general)
            $table->double('base_cost')->default(0)->comment('Costo de adquisición inicial');
            $table->double('refurbished_value')->default(0)->comment('Valor sumado por piezas nuevas');
            $table->text('technical_comments')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
             $table->dropColumn('brand');
             $table->dropColumn('model');
             $table->dropColumn('serial');
             $table->dropColumn('part_number');
             $table->dropColumn('internal_code');
             $table->dropColumn('equipment_type');
             $table->dropColumn('condition_status');
             $table->dropColumn('refurbish_state');
             $table->dropColumn('base_cost');
             $table->dropColumn('refurbished_value');
             $table->dropColumn('technical_comments');
        });
    }
};
