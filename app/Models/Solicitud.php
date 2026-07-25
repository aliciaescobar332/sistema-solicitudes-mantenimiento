<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class Solicitud extends Model
{
    protected $table = 'solicitudes';

    protected $fillable = [
        'titulo',
        'subtitulo',
        'descripcion',
        'ubicacion',
        'departamento',
        'fecha',
        'prioridad',
        'estado',
        'tecnico_id',
    ];

    /**
     * RELACIÓN: Una solicitud pertenece a un técnico (usuario con rol 'tecnico')
     */
    public function tecnico()
    {
        return $this->belongsTo(User::class, 'tecnico_id');
    }

    /**
     * RELACIÓN: Historial de cambios de esta solicitud
     */
    public function historial()
    {
        return $this->hasMany(SolicitudHistorial::class)->latest();
    }

    /**
     * RELACIÓN: Evidencias (fotos/documentos) subidas para esta solicitud
     */
    public function evidencias()
    {
        return $this->hasMany(Evidencia::class)->latest();
    }
}