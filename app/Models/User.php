<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasUuids;

    // Nombre oficial de la tabla correspondiente en nuestra base de datos.
    protected $table = 'users';

    // Especificamos a Laravel que la llave primaria de la entidad es id_usuario y no el estándar id.
    protected $primaryKey = 'id_usuario';

    // Tomar en cuenta que los identificadores de tipo UUID no son de naturaleza autoincrementable.
    public $incrementing = false;
    protected $keyType = 'string';

    // Atributos del modelo habilitados para su respectiva asignación masiva de datos.
    protected $fillable = [
        'id_rol',
        'nombre',
        'apellido',
        'correo',
        'password',
        'estado',
    ];

    // Atributos excluidos de las respuestas en formato JSON para cuidar la confidencialidad.
    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password'          => 'hashed',
    ];

    // Sanctum requiere identificar de manera inequívoca qué campo representa el correo del usuario para el inicio de sesión.
    public function getEmailForPasswordReset(): string
    {
        return $this->correo;
    }

    // Sobreescritura del identificador de autenticación para que Sanctum trabaje con id_usuario.
    public function getAuthIdentifierName(): string
    {
        return 'id_usuario';
    }

    // Relación de pertenencia: define que el usuario posee un rol asignado en el sistema.
    public function rol()
    {
        return $this->belongsTo(Rol::class, 'id_rol', 'id_rol');
    }

    // Relación de uno a muchos: registra las solicitudes ingresadas por el usuario.
    public function solicitudes()
    {
        return $this->hasMany(Solicitud::class, 'id_usuario_solicitante', 'id_usuario');
    }

    // Relación de uno a muchos: asignaciones técnicas vinculadas a este usuario de soporte.
    public function asignacionesTecnico()
    {
        return $this->hasMany(Asignacion::class, 'id_usuario_tecnico', 'id_usuario');
    }

    // Relación de uno a muchos: tareas de coordinación asignadas a este usuario.
    public function asignacionesCoordinador()
    {
        return $this->hasMany(Asignacion::class, 'id_usuario_coordinador', 'id_usuario');
    }

    // Método de apoyo para validar cabalmente el rol asignado sin necesidad de consultas redundantes.
    public function tieneRol(string $nombreRol): bool
    {
        return $this->rol && $this->rol->nombre_rol === $nombreRol;
    }
}