<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('audit_event_changes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('audit_event_id')->constrained('audit_events')->cascadeOnDelete();
            $table->string('field', 120);
            $table->json('before_value')->nullable();
            $table->json('after_value')->nullable();
            $table->timestamps();

            $table->index('field');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_event_changes');
    }
};
