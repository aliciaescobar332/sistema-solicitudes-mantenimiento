<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

use Illuminate\Support\Facades\Storage;

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

    protected static function booted()
    {
        static::deleted(function ($evidencia) {
            $url = $evidencia->url_recurso;
            $path = str_replace('/storage/', '', parse_url($url, PHP_URL_PATH));
            $path = ltrim($path, '/');

            if (Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
            }
        });
    }

    // Relación de pertenencia: vincula de forma directa la evidencia (imagen o pdf) con la solicitud de origen.
    public function solicitud()
    {
        return $this->belongsTo(Solicitud::class, 'id_solicitud', 'id_solicitud');
    }
}
