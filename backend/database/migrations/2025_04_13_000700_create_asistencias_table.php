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
        Schema::create('asistencias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empleado_id')->constrained('empleados'); // Relación con la tabla empleados
            $table->date('fecha'); // Fecha de la asistencia
            $table->time('hora_entrada')->nullable(); // Hora de entrada
            $table->time('hora_salida')->nullable(); // Hora de salida
            $table->foreignId('tipo_asistencia_id')->constrained('tipo_asistencia'); // Relación con la tabla tipo_asistencia
            $table->text('observaciones')->nullable(); // Observaciones opcionales sobre la asistencia
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('asistencias');
    }
};
