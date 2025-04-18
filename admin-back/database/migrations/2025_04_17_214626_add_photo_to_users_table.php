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
        Schema::table('users', function (Blueprint $table) {
            $table->bigInteger('sucuarsal_id')->default(1);
            $table->string('phone', 50)->nullable();
            $table->string('type_document', 50)->nullable();
            $table->string('document', 50)->nullable();
            $table->string('gender')->default('M')->comment('M es masculino y F es femenino');

            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('sucuarsal_id');
            $table->dropColumn('phone');
            $table->dropColumn('type_document');
            $table->dropColumn('document');
            $table->dropColumn('gender');
        });
    }
};
