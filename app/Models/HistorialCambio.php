<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class HistorialCambio extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'historial_cambios';
    protected $primaryKey = 'id_historial';

    public $incrementing = false;
    protected $keyType = 'string';

    // Campos asignables de forma masiva.
    protected $fillable = [
        'id_solicitud',
        'id_usuario',
        'campo_modificado',
        'valor_anterior',
        'valor_nuevo',
    ];

    // Relación con la solicitud afectada
    public function solicitud()
    {
        return $this->belongsTo(Solicitud::class, 'id_solicitud', 'id_solicitud');
    }

    // Relación con el usuario que realizó la modificación
    public function usuario()
    {
        return $this->belongsTo(User::class, 'id_usuario', 'id_usuario');
    }
}
