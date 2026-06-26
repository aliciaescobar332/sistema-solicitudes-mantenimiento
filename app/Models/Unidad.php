<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Unidad extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'unidades';
    protected $primaryKey = 'id_unidad';

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id_sede',
        'nombre_unidad',
    ];

    // Relación de pertenencia: define la sede o edificio en el cual se ubica esta área física.
    public function sede()
    {
        return $this->belongsTo(Sede::class, 'id_sede', 'id_sede');
    }

    // Relación de uno a muchos: registra de forma agrupada todas las solicitudes recibidas desde esta unidad.
    public function solicitudes()
    {
        return $this->hasMany(Solicitud::class, 'id_unidad', 'id_unidad');
    }
}
