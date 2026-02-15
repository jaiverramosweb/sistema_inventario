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
        Schema::create('opportunities', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->decimal('estimated_amount', 15, 2)->default(0);
            $table->foreignId('pipeline_stage_id')->constrained('pipeline_stages');
            $table->foreignId('client_id')->nullable()->constrained('clients');
            $table->foreignId('lead_id')->nullable()->constrained('leads');
            $table->foreignId('user_id')->constrained();
            $table->date('expected_closed_at')->nullable();
            $table->date('closed_at')->nullable();
            $table->string('priority')->default('MEDIUM'); // LOW, MEDIUM, HIGH
            $table->text('description')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('opportunities');
    }
};
