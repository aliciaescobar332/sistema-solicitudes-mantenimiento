<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    // Iniciar sesión y generar el token de acceso con Sanctum
    public function login(Request $request)
    {
        $request->validate([
            'correo'   => 'required|email',
            'password' => 'required|string',
        ]);

        $usuario = User::where('correo', $request->correo)->first();

        // Verificar que el usuario exista y la contraseña sea válida
        if (!$usuario || !Hash::check($request->password, $usuario->password)) {
            throw ValidationException::withMessages([
                'correo' => ['Las credenciales introducidas no son válidas.'],
            ]);
        }

        // Bloquear acceso si la cuenta del usuario está inactiva
        if ($usuario->estado !== 'Activo') {
            return response()->json([
                'mensaje' => 'Su cuenta se encuentra inactiva en este momento. Por favor, comuníquese con el administrador para solventar.',
            ], 403);
        }

        // Revocar tokens anteriores y generar un nuevo token de acceso
        $usuario->tokens()->delete();
        $token = $usuario->createToken('app-token')->plainTextToken;

        return response()->json([
            'mensaje' => 'Sesión iniciada con éxito.',
            'token'   => $token,
            'usuario' => [
                'id'     => $usuario->id_usuario,
                'nombre' => $usuario->nombre . ' ' . $usuario->apellido,
                'correo' => $usuario->correo,
                'rol'    => $usuario->rol->nombre_rol ?? null,
            ],
        ]);
    }

    // Cerrar sesión e invalidar el token de acceso actual
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'mensaje' => 'Sesión cerrada correctamente. Feliz día.',
        ]);
    }

    // Obtener los datos del usuario autenticado
    public function me(Request $request)
    {
        $usuario = $request->user()->load('rol');

        return response()->json([
            'id'     => $usuario->id_usuario,
            'nombre' => $usuario->nombre . ' ' . $usuario->apellido,
            'correo' => $usuario->correo,
            'estado' => $usuario->estado,
            'rol'    => $usuario->rol->nombre_rol ?? null,
        ]);
    }
}
