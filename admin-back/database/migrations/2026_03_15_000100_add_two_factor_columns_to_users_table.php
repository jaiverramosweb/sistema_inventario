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
            $table->boolean('two_factor_enabled')->default(false);
            $table->text('two_factor_secret_encrypted')->nullable();
            $table->text('two_factor_pending_secret_encrypted')->nullable();
            $table->timestamp('two_factor_confirmed_at')->nullable();
            $table->bigInteger('two_factor_last_used_step')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'two_factor_enabled',
                'two_factor_secret_encrypted',
                'two_factor_pending_secret_encrypted',
                'two_factor_confirmed_at',
                'two_factor_last_used_step',
            ]);
        });
    }
};
