<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('vacaciones', function (Blueprint $table) {
            $table->dropColumn('ciclo_servicio'); // Eliminar el campo anterior
            // Añadir la nueva clave foránea, apuntando al nombre correcto de la tabla 'ciclo_servicios'
            $table->foreignId('ciclo_servicio_id')->nullable()->constrained('ciclo_servicios')->onDelete('set null')->after('empleado_id');
        });
    }

    public function down()
    {
        Schema::table('vacaciones', function (Blueprint $table) {
            // Revertir la columna anterior (si la columna 'ciclo_servicio' existía originalmente)
            $table->string('ciclo_servicio')->nullable()->after('empleado_id'); // Asegúrate de que permita nulos si no es requerido

            // Eliminar la clave foránea y luego la columna
            $table->dropForeign(['ciclo_servicio_id']);
            $table->dropColumn('ciclo_servicio_id');
        });
    }
};
