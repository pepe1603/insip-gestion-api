<?php

// database/migrations/2025_04_20_001000_create_ciclos_servicio_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('ciclo_servicios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empleado_id')->constrained('empleados')->onDelete('cascade'); // Añadí onDelete para eliminar en cascada
            // cuando se elimine un empleado
            // Esto es útil si deseas mantener la integridad referencial
            // y eliminar automáticamente los ciclos de servicio asociados
            // al empleado eliminado, de tal manera que no queden registros huérfanos
            // en la tabla de ciclos de servicio ejemlo: si un empleado es eliminado
            // de la base de datos, sus ciclos de servicio también se eliminarán
            // para evitar que queden registros huérfanos
            $table->year('anio');
            $table->timestamps();

            // Opcional: Añadir índice único si tiene sentido para tu lógica de negocio
            $table->unique(['empleado_id', 'anio']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('ciclo_servicios'); // ¡Cambiado aquí!
    }
};
