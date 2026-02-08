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
        Schema::create('vacaciones_officiales', function (Blueprint $table) {
            $table->id();
            $table->string('tiempo_servicio')->nullable(); // Tiempo de servicio del empleado
            $table->integer('dias_vacaciones')->nullable(); // Días de vacaciones asignados
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vacaciones_officiales');
    }
};
