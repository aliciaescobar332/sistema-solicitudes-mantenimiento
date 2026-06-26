<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;

class Authenticate extends Middleware
{
    // para un proyecto de API pura nunca redirigimos al login,
    // solo devolvemos 401 en JSON y el frontend se encarga de mandar al usuario al formulario
    protected function redirectTo(Request $request): ?string
    {
        return null;
    }
}
