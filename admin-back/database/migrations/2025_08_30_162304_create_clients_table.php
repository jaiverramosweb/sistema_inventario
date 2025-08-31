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
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->string('name',250);
            $table->string('surname',250)->nullable();
            $table->string('email',250)->nullable();
            $table->string('phone',50)->nullable();
            $table->smallInteger('type_client')->default(1)->comment('1=Cliente final, 2=Cliente empresa');
            $table->string('type_document',30);
            $table->string('n_document',100);
            $table->timestamp('date_birthday')->nullable();
            $table->bigInteger('user_id')->comment('ID del usuario que creó el cliente');
            $table->bigInteger('sucursal_id')->comment('ID de la sucursal');
            $table->string('gender')->nullable();
            $table->smallInteger('status')->default(1)->comment('1=Activo, 2=Inactivo');
            $table->string('id_department',25)->nullable();
            $table->string('id_municipality',25)->nullable();
            $table->string('id_district',25)->nullable(); 
            $table->string('department',150)->nullable();
            $table->string('municipality',150)->nullable();
            $table->string('district',150)->nullable();
            $table->text('address')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};
