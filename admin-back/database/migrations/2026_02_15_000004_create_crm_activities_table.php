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
        Schema::create('crm_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('opportunity_id')->nullable()->constrained('opportunities');
            $table->foreignId('lead_id')->nullable()->constrained('leads');
            $table->foreignId('user_id')->constrained();
            $table->string('type'); // CALL, EMAIL, MEETING, NOTE, TASK
            $table->text('description');
            $table->dateTime('activity_date');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('crm_activities');
    }
};
