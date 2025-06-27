<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use App\Enums\UserRole; // Importa tu nuevo Enum

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // No hay cambios de esquema aquí, solo actualización de datos.
        // Si tu columna 'role' no existiera, esto fallaría.
        // Asumimos que la columna 'role' ya existe como VARCHAR (string en Laravel).

        // Mapeo de roles de español a inglés
        $roleMap = [
            'empleado'   => UserRole::Employee->value, // 'employee'
            'admin'      => UserRole::Admin->value,    // 'admin' (si ya estaba en inglés, igual se mapea)
            'supervisor' => UserRole::Supervisor->value, // 'supervisor' (igual)
            // Agrega cualquier otro mapeo si tienes más roles en español
            // 'otro_rol_espanol' => UserRole::SomeOtherRole->value,
        ];

        foreach ($roleMap as $oldRole => $newRole) {
            DB::table('users')
                ->where('role', $oldRole)
                ->update(['role' => $newRole]);
        }

        // Opcional: Para asegurar que cualquier rol que no esté en el mapeo
        // y no sea uno de los definidos (ej. un null o valor inesperado)
        // se convierta al rol por defecto 'user'.
        DB::table('users')
            ->whereNotIn('role', array_values($roleMap))
            ->update(['role' => UserRole::User->value]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Si quisieras revertir esto, tendrías que definir el mapeo inverso.
        // En este caso, como los nombres en español ya no se usarán,
        // no es necesario un "down" que revierta los nombres.
        // Generalmente, las migraciones de datos como esta no suelen tener un `down()` complejo
        // a menos que sea absolutamente crítico poder restaurar los datos exactos anteriores.
        // Podemos dejarlo vacío o con un comentario.
    }
};
