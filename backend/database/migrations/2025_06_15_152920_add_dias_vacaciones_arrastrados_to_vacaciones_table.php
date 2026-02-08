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
        Schema::table('vacaciones', function (Blueprint $table) {
            // Añadimos el nuevo campo 'dias_vacaciones_arrastrados'
            // Lo ponemos después de 'dias_vacaciones_totales' para que tenga un orden lógico.
            $table->integer('dias_vacaciones_arrastrados')->default(0)->after('dias_vacaciones_totales');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vacaciones', function (Blueprint $table) {
            // Revertimos la adición del campo en caso de un rollback
            $table->dropColumn('dias_vacaciones_arrastrados');
        });
    }
};
