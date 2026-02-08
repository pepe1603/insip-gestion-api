<?php
namespace App\Helpers;

use Illuminate\Http\JsonResponse;

class JsonResponseValidator
{
    public static function validate($response)
    {
        if ($response instanceof JsonResponse) {
            $data = $response->getData(true); // convertimos JsonResponse a array asociativo
            // Puedes loguear el error si es necesario
            //\Log::error('Error en respuesta JSON', $data);
            dd($data); // Esto te ayudará a saber si es un objeto o un array
            return []; // devolvemos un array vacío
        }

        return $response;
    }
}
