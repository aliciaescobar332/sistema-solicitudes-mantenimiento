<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\Solicitud;

class Evidencia extends Model
{
    protected $table = 'evidencias';

    protected $fillable = [
        'solicitud_id',
        'user_id',
        'nombre_archivo',
        'ruta',
        'tipo',
    ];

    /**
     * RELACIÓN: A qué solicitud pertenece esta evidencia
     */
    public function solicitud()
    {
        return $this->belongsTo(Solicitud::class);
    }

    /**
     * RELACIÓN: Qué usuario subió esta evidencia
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}