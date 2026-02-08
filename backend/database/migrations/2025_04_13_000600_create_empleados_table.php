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
        Schema::create('empleados', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('ape_materno');
            $table->string('ape_paterno');
            $table->date('fecha_ingreso');
            $table->string('puesto');
            $table->foreignId('departamento_id')->constrained('departamentos'); // Relación con la tabla departamentos
            $table->enum('status', ['ACTIVO', 'INACTIVO']); // Campo status como enum
            $table->enum('tipo_contrato', ['TIEMPO_COMPLETO', 'MEDIO_TIEMPO', 'TEMPORAL']); // Campo tipo_contrato como enum
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('empleados');
    }
};
