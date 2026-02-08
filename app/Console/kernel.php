<?php

namespace App\Console;

use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Los comandos Artisan proporcionados por la aplicación.
     *
     * @var array
     */
    protected $commands = [
        \App\Console\Commands\SetApiStatus::class,
    ];

    /**
     * Define los comandos para la aplicación.
     */
    protected function commands(): void
    {
        // Esto carga automáticamente los comandos que estén en el directorio Commands
        $this->load(__DIR__.'/Commands');

        // También puedes cargar los comandos de routes/console.php si tienes comandos definidos ahí
        require base_path('routes/console.php');
    }
}
