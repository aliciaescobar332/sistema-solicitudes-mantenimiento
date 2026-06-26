<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Bitacora extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'bitacoras';
    protected $primaryKey = 'id_bitacora';

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id_asignacion',
        'descripcion_trabajo',
        'materiales',
        'fecha_inicio',
        'fecha_fin',
    ];

    protected $casts = [
        'fecha_inicio' => 'datetime',
        'fecha_fin'    => 'datetime',
    ];

    // Relación de pertenencia: define la asignación de soporte técnico a la cual pertenece este registro diario de trabajo.
    public function asignacion()
    {
        return $this->belongsTo(Asignacion::class, 'id_asignacion', 'id_asignacion');
    }
}
