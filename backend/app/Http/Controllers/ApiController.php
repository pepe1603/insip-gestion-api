<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Carbon\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;


class ApiController extends Controller
{
    /**
     * Obtiene información general sobre la API.
     *
     * @return \Illuminate\Http\JsonResponse
     */
	public function info(): JsonResponse
{
    $apiInfo = config('app.api_info');

    // Sobrescribe el estado si existe en el archivo
    if (Storage::disk('local')->exists('api_status.json')) {
        $json = json_decode(Storage::get('api_status.json'), true);
        $apiInfo['status'] = $json['status'] ?? $apiInfo['status'];
    }

    $lastDeploymentTimestamp = filemtime(base_path('public/index.php'));
    $lastDeploymentDate = Carbon::createFromTimestamp($lastDeploymentTimestamp)->toIso8601String();

    return response()->json([
        'api_name' => $apiInfo['name'],
        'version' => $apiInfo['version'],
        'description' => $apiInfo['description'],
        'status' => ucfirst($apiInfo['status']),
        'environment' => app()->environment(),
        'last_deployment_date' => $lastDeploymentDate,
        'contact' => [
            'email' => $apiInfo['contact_email'],
            'phone' => $apiInfo['contact_phone'],
            'url' => $apiInfo['support_url'],
        ],
        'collaborators' => $apiInfo['collaborators'],
        'message' => $apiInfo['status'] === 'mantenimiento'
            ? 'La API está actualmente en mantenimiento.'
            : 'Bienvenido a la API de Gestión de Vacaciones.',
    ]);
}

    /**
     * Obtiene solo el estado de la API, diseñado para ser un endpoint público.
     * @return \Illuminate\Http\JsonResponse
     */
    public function getStatus(): JsonResponse
    {
        $apiStatus = config('app.api_info.status');

        if (Storage::disk('local')->exists('api_status.json')) {
            $json = json_decode(Storage::get('api_status.json'), true);
            $apiStatus = $json['status'] ?? $apiStatus;
        }

        return response()->json([
            'status' => $apiStatus,
        ]);
    }


}
