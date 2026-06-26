<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Evidencia extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'evidencias';
    protected $primaryKey = 'id_evidencia';

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id_solicitud',
        'url_recurso',
        'tipo_archivo',
    ];

    // Relación de pertenencia: vincula de forma directa la evidencia (imagen o pdf) con la solicitud de origen.
    public function solicitud()
    {
        return $this->belongsTo(Solicitud::class, 'id_solicitud', 'id_solicitud');
    }
}
