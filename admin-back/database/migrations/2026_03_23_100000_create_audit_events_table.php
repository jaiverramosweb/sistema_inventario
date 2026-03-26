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
        Schema::create('audit_events', function (Blueprint $table) {
            $table->id();
            $table->dateTime('occurred_at', 6)->index();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('user_name', 150)->nullable();
            $table->string('session_id', 128)->nullable();
            $table->uuid('request_id')->nullable()->index();
            $table->uuid('correlation_id')->nullable()->index();
            $table->string('action', 50)->index();
            $table->string('module', 80)->index();
            $table->string('entity_type', 150)->nullable();
            $table->string('entity_id', 64)->nullable();
            $table->string('description', 500);
            $table->boolean('status')->default(true)->index();
            $table->ipAddress('ip')->nullable();
            $table->string('user_agent', 512)->nullable();
            $table->string('method', 10)->nullable();
            $table->string('route_name', 150)->nullable();
            $table->string('url', 1000)->nullable();
            $table->json('metadata')->nullable();
            $table->char('event_hash', 64);
            $table->char('prev_hash', 64)->nullable();
            $table->timestamps();

            $table->index(['user_id', 'occurred_at']);
            $table->index(['module', 'action', 'occurred_at']);
            $table->index(['entity_type', 'entity_id', 'occurred_at']);
            $table->index(['status', 'occurred_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_events');
    }
};
