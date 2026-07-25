<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class SolicitudReasignacion extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'solicitudes_reasignacion';
    protected $primaryKey = 'id_solicitud_reasignacion';

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id_solicitud',
        'id_usuario_tecnico_solicitante',
        'id_usuario_tecnico_propuesto',
        'motivo',
        'estado',
        'id_usuario_coordinador',
        'comentarios_coordinador',
    ];

    // Relación: Vincula con la solicitud original a ser reasignada
    public function solicitud()
    {
        return $this->belongsTo(Solicitud::class, 'id_solicitud', 'id_solicitud');
    }

    // Relación: Técnico que solicita la reasignación
    public function tecnicoSolicitante()
    {
        return $this->belongsTo(User::class, 'id_usuario_tecnico_solicitante', 'id_usuario');
    }

    // Relación: Técnico propuesto (opcional)
    public function tecnicoPropuesto()
    {
        return $this->belongsTo(User::class, 'id_usuario_tecnico_propuesto', 'id_usuario');
    }

    // Relación: Coordinador que resuelve la solicitud (opcional)
    public function coordinador()
    {
        return $this->belongsTo(User::class, 'id_usuario_coordinador', 'id_usuario');
    }
}
