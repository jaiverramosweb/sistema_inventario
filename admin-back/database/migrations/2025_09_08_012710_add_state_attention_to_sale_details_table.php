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
        Schema::table('sale_details', function (Blueprint $table) {
            $table->double('quantity_pending')->default(0)->comment('Cantidad pendiente de atención');
            $table->smallInteger('state_attention')->default(1)->comment('1: pendiente, 2: parcial, 3: completo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sale_details', function (Blueprint $table) {
            $table->dropColumn('quantity_pending');
            $table->dropColumn('state_attention');
        });
    }
};
