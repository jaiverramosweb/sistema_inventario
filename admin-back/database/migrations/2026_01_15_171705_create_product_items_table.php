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
        Schema::create('product_items', function (Blueprint $table) {
            $table->id();
            // El "Padre" (ej: La Laptop)
            $table->foreignId('parent_product_id')->constrained('products')->onDelete('cascade');
            // El "Hijo" (ej: La RAM que se instaló)
            $table->foreignId('child_product_id')->constrained('products');
            
            $table->double('cost_at_installation')->default(0);
            $table->boolean('affects_final_price')->default(true);
            $table->foreignId('user_id')->constrained('users');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_items');
    }
};
