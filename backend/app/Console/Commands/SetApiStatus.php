<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class SetApiStatus extends Command
{
    protected $signature = 'api:status {status : operativo|mantenimiento|degradado}';
    protected $description = 'Cambia el estado actual de la API';

    public function handle(): void
    {
        $status = $this->argument('status');

        if (!in_array($status, ['operativo', 'mantenimiento', 'degradado'])) {
            $this->error('Estado inválido. Usa: operativo, mantenimiento o degradado.');
            return;
        }

        // Guarda el estado en un archivo local
        Storage::disk('local')->put('api_status.json', json_encode(['status' => $status]));

        $this->info("Estado de la API cambiado a: {$status}");
    }
}
