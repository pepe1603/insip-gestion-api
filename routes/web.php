<?php

use Illuminate\Support\Facades\Route;

Route::get('/{any}', function () {
    return view('welcome');  // Esto asegura que Vue maneje las rutas del frontend
})->where('any', '.*');


