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
        Schema::create('estados_solicitud', function (Blueprint $table) {
            $table->id();
            $table->string('estado')->unique(); // Nombre del estado de la solicitud (ej. 'Pendiente', 'Aprobada', 'Rechazada')
            $table->text('descripcion')->nullable(); // Descripción opcional del estado de la solicitud
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('estados_solicitud');
    }
};
