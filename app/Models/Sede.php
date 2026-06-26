<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Sede extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'sedes';
    protected $primaryKey = 'id_sede';

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'nombre_sede',
        'direccion',
    ];

    // Relación de uno a muchos: una sede física alberga múltiples unidades organizativas o departamentos.
    public function unidades()
    {
        return $this->hasMany(Unidad::class, 'id_sede', 'id_sede');
    }
}
