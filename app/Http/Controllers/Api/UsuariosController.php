<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Rol;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;

class UsuariosController extends Controller
{
    // Listar todos los usuarios con sus roles correspondientes
    public function index()
    {
        $usuarios = User::with('rol')
            ->orderBy('nombre')
            ->get()
            ->map(fn($u) => $this->formatear($u));

        return response()->json($usuarios);
    }

    // Registro formal de un nuevo usuario en la base de datos, asignándole sus credenciales iniciales.
    public function store(Request $request)
    {
        $request->validate([
            'nombre'   => 'required|string|max:100',
            'apellido' => 'required|string|max:100',
            'correo'   => 'required|email|unique:users,correo',
            'id_rol'   => 'required|exists:roles,id_rol',
        ]);

        // Se genera una contraseña temporal de manera aleatoria para el primer acceso del usuario.
        $passTemp = Str::random(10);

        $usuario = User::create([
            'id_rol'   => $request->id_rol,
            'nombre'   => $request->nombre,
            'apellido' => $request->apellido,
            'correo'   => $request->correo,
            'password' => Hash::make($passTemp),
            'estado'   => 'Activo',
        ]);

        // TODO: Acá se debe implementar el envío de correo automático con la clave temporal al buzón del usuario.
        // Para efectos de desarrollo, se retorna temporalmente en el cuerpo de la respuesta JSON.

        return response()->json([
            'mensaje'         => 'Usuario creado correctamente en el sistema.',
            'usuario'         => $this->formatear($usuario->load('rol')),
            'password_temp'   => $passTemp,
        ], 201);
    }

    // Consulta y despliegue del detalle completo de un usuario en particular.
    public function show(string $id)
    {
        $usuario = User::with('rol')->findOrFail($id);

        return response()->json($this->formatear($usuario));
    }

    // Modificación formal de los datos generales del usuario en el sistema.
    public function update(Request $request, string $id)
    {
        $usuario = User::findOrFail($id);

        $request->validate([
            'nombre'   => 'sometimes|string|max:100',
            'apellido' => 'sometimes|string|max:100',
            'correo'   => 'sometimes|email|unique:users,correo,' . $id . ',id_usuario',
            'id_rol'   => 'sometimes|exists:roles,id_rol',
        ]);

        $usuario->update($request->only(['nombre', 'apellido', 'correo', 'id_rol']));

        return response()->json([
            'mensaje' => 'Usuario actualizado con éxito.',
            'usuario' => $this->formatear($usuario->load('rol')),
        ]);
    }

    // Deshabilitación de la cuenta del usuario (borrado lógico para preservar integridad referencial).
    public function deshabilitar(string $id)
    {
        $usuario = User::findOrFail($id);
        $usuario->update(['estado' => 'Inactivo']);

        return response()->json([
            'mensaje' => 'El usuario ha sido deshabilitado correctamente. Ya no podrá ingresar al sistema.',
        ]);
    }

    // Reactivación de la cuenta del usuario en el sistema.
    public function habilitar(string $id)
    {
        $usuario = User::findOrFail($id);
        $usuario->update(['estado' => 'Activo']);

        return response()->json([
            'mensaje' => 'El usuario ha sido reactivado de forma exitosa.',
        ]);
    }

    // Método de apoyo para formatear la respuesta del modelo de conformidad al estándar del API.
    private function formatear(User $u): array
    {
        return [
            'id'       => $u->id_usuario,
            'nombre'   => $u->nombre . ' ' . $u->apellido,
            'correo'   => $u->correo,
            'estado'   => $u->estado,
            'rol'      => $u->rol->nombre_rol ?? null,
            'id_rol'   => $u->id_rol,
        ];
    }
}
