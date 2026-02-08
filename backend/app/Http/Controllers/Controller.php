<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController; // <-- Fíjate en este alias

class Controller extends BaseController // <-- Debería extender BaseController
{
    use AuthorizesRequests, ValidatesRequests;
}
