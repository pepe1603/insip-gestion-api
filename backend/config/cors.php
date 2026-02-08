<?php
// backend/config/cors.php



return [
    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your settings for cross-origin resource sharing
    | or "CORS". This determines which domains are allowed to access your
    | application's HTTP responses from a different domain.
    |
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie'], // Asegúrate de que 'api/*' esté aquí

    'allowed_methods' => ['*'], // Permite todos los métodos (GET, POST, etc.)


    //'allowed_origins' => ['http://localhost:5174', 'http://127.0.0.1:5174'],
    // Esta es la forma recomendada de leer orígenes desde el .env
    'allowed_origins' => explode(',', env('CORS_ALLOWED_ORIGINS', 'http://localhost:5174')),


    'allowed_headers' => ['*'], // Permite todos los encabezados

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => false, // O true si usas Sanctum con SPA y cookies


];
