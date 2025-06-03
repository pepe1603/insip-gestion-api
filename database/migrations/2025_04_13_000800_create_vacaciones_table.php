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
        Schema::create('vacaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empleado_id')->constrained('empleados'); // Relación con la tabla empleados
            $table->string('ciclo_servicio'); // Año o ciclo actual del servicio/trabajo
            $table->integer('dias_vacaciones_totales');//calcular los días de vacaciones totales de acuerdo al tiempo de servicio del empleado en el ciclo o año actual
            $table->integer('dias_vacaciones_disfrutados')->default(0); // Días de vacaciones disfrutados
            $table->integer('dias_vacaciones_solicitados'); // Días de vacaciones solicitados calculados por deacuerdo a la fecha de inicio y fin
            $table->integer('dias_vacaciones_disponibles'); //Dias disponibles para solicitar vacaciones calculados de acuerdo a los días de vacaciones totales y los días disfrutados
            $table->date('fecha_solicitud')->nullable();
            $table->date('fecha_aprobacion')->nullable();// Fecha de aprobación de la solicitud se puede dejar nula si no ha sido aprobada, caso contrario se llena automáticamente cuando se aprueba
            $table->date('fecha_inicio')->nullable();
            $table->date('fecha_fin')->nullable();
            $table->foreignId('estado_solicitud_id')->constrained('estados_solicitud'); // Relación con la tabla estados_solicitud de estado de solicitud de vacaciones
            $table->text('observaciones')->nullable(); // Observaciones opcionales sobre la solicitud de vacaciones
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vacaciones');
    }
};
