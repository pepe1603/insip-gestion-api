<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Carbon\Carbon;
use Illuminate\Support\Facades\Config;

class ApiController extends Controller
{
    /**
     * Obtiene información general sobre la API.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function info(): JsonResponse
    {
        $apiInfo = Config::get('app.api_info');

        // Puedes obtener la fecha del último despliegue de varias maneras:
        // 1. Usando la fecha de modificación de un archivo clave (ej. public/index.php)
        // 2. Si usas un pipeline CI/CD, podrías escribir un timestamp en un archivo al final del despliegue.
        // Aquí un ejemplo simple usando el archivo index.php
        $lastDeploymentTimestamp = filemtime(base_path('public/index.php'));
        $lastDeploymentDate = Carbon::createFromTimestamp($lastDeploymentTimestamp)->toIso8601String();

        return response()->json([
            'api_name'              => $apiInfo['name'],
            'version'               => $apiInfo['version'],
            'description'           => $apiInfo['description'],
            'status'                => 'Operativo', // O podrías tener una lógica más compleja
            'environment'           => app()->environment(), // 'local', 'testing', 'production', etc.
            'last_deployment_date'  => $lastDeploymentDate,
            'contact'               => [
                'email' => $apiInfo['contact_email'],
                'phone' => $apiInfo['contact_phone'],
                'url'   => $apiInfo['support_url'],
            ],
            'collaborators'         => $apiInfo['collaborators'],
            'message'               => 'Bienvenido a la API de Gestión de Vacaciones.',
        ]);
    }
}
