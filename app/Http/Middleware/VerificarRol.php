<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class VerificarRol
{
    // Validar si el usuario tiene alguno de los roles permitidos
    public function handle(Request $request, Closure $next, string ...$roles)
    {
        $usuario = $request->user();

        if (!$usuario || !$usuario->rol) {
            return response()->json(['mensaje' => 'No se encuentra autenticado en el sistema. Favor iniciar sesión.'], 401);
        }

        if (!in_array($usuario->rol->nombre_rol, $roles)) {
            return response()->json([
                'mensaje' => 'No tiene la autorización requerida para realizar esta acción.',
                'tu_rol'  => $usuario->rol->nombre_rol,
            ], 403);
        }

        return $next($request);
    }
}
