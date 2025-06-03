<?php

namespace App\Helpers;

class ApiResponse
{
    /**
     * Genera una respuesta API estructurada
     *
     * @param int $status Código de estado HTTP
     * @param mixed $data Los datos que pueden ser un array o un mensaje de error
     * @return array
     */
    public static function send(int $status, $data)
    {
        return response()->json([
            'status' => $status,  // Código de estado (ej. 200, 400, 500)
            'data' => $data       // Datos o mensaje de error
        ], $status);
    }

    /**
     * Respuesta de éxito
     *
     * @param mixed $data Los datos de la respuesta
     * @return \Illuminate\Http\JsonResponse
     */
    public static function success($data)
    {
        return self::send(200, $data);
    }

    /**
     * Respuesta de error
     *
     * @param string $message Mensaje de error
     * @return \Illuminate\Http\JsonResponse
     */
    public static function error($message)
    {
        return self::send(400, [
            'error' => $message
        ]);
    }

    /**
     * Respuesta de servidor de error
     *
     * @param string $message Mensaje de error del servidor
     * @return \Illuminate\Http\JsonResponse
     */
    public static function serverError($message)
    {
        return self::send(500, [
            'error' => $message
        ]);
    }
}
