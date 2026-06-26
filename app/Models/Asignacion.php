<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Asignacion extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'asignaciones';
    protected $primaryKey = 'id_asignacion';

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id_solicitud',
        'id_usuario_tecnico',
        'id_usuario_coordinador',
        'fecha_asignacion',
    ];

    protected $casts = [
        'fecha_asignacion' => 'datetime',
    ];

    // Relación de pertenencia: define la solicitud a la cual corresponde la presente asignación.
    public function solicitud()
    {
        return $this->belongsTo(Solicitud::class, 'id_solicitud', 'id_solicitud');
    }

    // Relación de pertenencia: vincula al técnico responsable de solventar el ticket.
    public function tecnico()
    {
        return $this->belongsTo(User::class, 'id_usuario_tecnico', 'id_usuario');
    }

    // Relación de pertenencia: especifica al coordinador que ordenó y programó esta asignación.
    public function coordinador()
    {
        return $this->belongsTo(User::class, 'id_usuario_coordinador', 'id_usuario');
    }

    // Relación de uno a muchos: bitácoras de trabajo que registran el progreso detallado de la reparación.
    public function bitacoras()
    {
        return $this->hasMany(Bitacora::class, 'id_asignacion', 'id_asignacion');
    }
}
