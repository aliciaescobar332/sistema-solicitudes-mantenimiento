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

    /**
     * Solicitar un enlace de recuperación de contraseña por correo electrónico.
     */
    public function enviarEnlaceRecuperacion(Request $request)
    {
        $request->validate([
            'correo' => 'required|email',
        ]);

        $usuario = User::where('correo', $request->correo)->first();

        if ($usuario) {
            // Regla de negocio: sólo el usuario normal (Solicitante) puede autogestionar su recuperación.
            if (!$usuario->tieneRol('Solicitante')) {
                return response()->json([
                    'mensaje' => 'La recuperación autónoma de contraseña por correo electrónico está deshabilitada para roles superiores. Por motivos de seguridad de RCH Hospital, por favor comuníquese con el Administrador o Soporte Técnico para restablecer su clave.'
                ], 403);
            }

            // Generar token aleatorio seguro
            $token = \Illuminate\Support\Str::random(60);

            // Almacenar token hasheado en la base de datos (seguridad OWASP)
            \Illuminate\Support\Facades\DB::table('password_reset_tokens')->updateOrInsert(
                ['email' => $usuario->correo],
                [
                    'token'      => \Illuminate\Support\Facades\Hash::make($token),
                    'created_at' => now(),
                ]
            );

            // Enviar la notificación de correo electrónico
            $usuario->sendPasswordResetNotification($token);
        }

        // Retornar respuesta genérica exitosa para evitar enumeración y filtración de correos
        return response()->json([
            'mensaje' => 'Si el correo electrónico ingresado está registrado en el sistema, se le ha enviado un enlace de recuperación de contraseña.'
        ]);
    }

    /**
     * Procesar el restablecimiento formal de la contraseña.
     */
    public function restablecerPassword(Request $request)
    {
        $request->validate([
            'token'                 => 'required|string',
            'correo'                => 'required|email',
            'password'              => 'required|string|min:8|confirmed',
        ]);

        // Buscar el token en password_reset_tokens
        $registro = \Illuminate\Support\Facades\DB::table('password_reset_tokens')
            ->where('email', $request->correo)
            ->first();

        if (!$registro) {
            return response()->json([
                'mensaje' => 'El enlace de recuperación no es válido o ya ha sido utilizado.'
            ], 422);
        }

        // Validar expiración (máximo 60 minutos de vida del token)
        $expiracion = \Carbon\Carbon::parse($registro->created_at)->addMinutes(60);
        if ($expiracion->isPast()) {
            \Illuminate\Support\Facades\DB::table('password_reset_tokens')
                ->where('email', $request->correo)
                ->delete();

            return response()->json([
                'mensaje' => 'El enlace de recuperación ha expirado. Por favor, solicite uno nuevo.'
            ], 422);
        }

        // Validar autenticidad del token
        if (!\Illuminate\Support\Facades\Hash::check($request->token, $registro->token)) {
            return response()->json([
                'mensaje' => 'El enlace de recuperación no es válido.'
            ], 422);
        }

        // Obtener el usuario y realizar doble validación de rol por seguridad
        $usuario = User::where('correo', $request->correo)->first();
        if (!$usuario) {
            return response()->json([
                'mensaje' => 'No se encontró un usuario asociado a este correo electrónico.'
            ], 404);
        }

        if (!$usuario->tieneRol('Solicitante')) {
            return response()->json([
                'mensaje' => 'Acción denegada para este tipo de cuenta de usuario.'
            ], 403);
        }

        // Cambiar contraseña
        $usuario->update([
            'password' => \Illuminate\Support\Facades\Hash::make($request->password),
        ]);

        // Invalidar tokens previos de Sanctum para cerrar todas las sesiones del usuario de forma segura
        $usuario->tokens()->delete();

        // Eliminar el token utilizado de la base de datos
        \Illuminate\Support\Facades\DB::table('password_reset_tokens')
            ->where('email', $request->correo)
            ->delete();

        return response()->json([
            'mensaje' => 'Tu contraseña ha sido restablecida con éxito. Ya puedes iniciar sesión en el sistema con tus nuevas credenciales.'
        ]);
    }
}
