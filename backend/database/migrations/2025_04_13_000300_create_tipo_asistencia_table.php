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
        Schema::create('tipo_asistencia', function (Blueprint $table) {
            $table->id();
            $table->string('nombre')->unique(); // Nombre del tipo de asistencia (ej. 'Vacaciones', 'Permiso', 'Enfermedad')
            $table->text('descripcion')->nullable(); // Descripción opcional del tipo de asistencia
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tipo_asistencia');
    }
};
