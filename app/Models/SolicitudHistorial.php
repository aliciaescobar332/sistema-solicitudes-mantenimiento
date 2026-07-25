<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\Solicitud;

class SolicitudHistorial extends Model
{
    protected $table = 'solicitud_historial';

    protected $fillable = [
        'solicitud_id',
        'user_id',
        'campo',
        'valor_anterior',
        'valor_nuevo',
    ];

    /**
     * RELACIÓN: A qué solicitud pertenece este registro de historial
     */
    public function solicitud()
    {
        return $this->belongsTo(Solicitud::class);
    }

    /**
     * RELACIÓN: Qué usuario hizo el cambio
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}