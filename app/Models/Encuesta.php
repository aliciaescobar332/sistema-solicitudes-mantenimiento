<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Encuesta extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'encuestas';
    protected $primaryKey = 'id_encuesta';

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id_solicitud',
        'calificacion_rapidez',
        'calificacion_calidad',
        'calificacion_amabilidad',
        'comentarios',
    ];

    // Relación de pertenencia: vincula la encuesta directamente a la solicitud de servicio ya finalizada.
    public function solicitud()
    {
        return $this->belongsTo(Solicitud::class, 'id_solicitud', 'id_solicitud');
    }
}
