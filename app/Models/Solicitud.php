<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Solicitud extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'solicitudes';
    protected $primaryKey = 'id_solicitud';

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id_usuario_solicitante',
        'id_unidad',
        'titulo',
        'descripcion',
        'prioridad',
        'estado_solicitud',
        'fecha_apertura',
        'codigo_verificacion',
        'firma_integridad',
    ];

    protected $casts = [
        'fecha_apertura' => 'datetime',
        'titulo' => 'encrypted',
        'descripcion' => 'encrypted',
    ];

    protected static function booted()
    {
        static::creating(function ($solicitud) {
            if (empty($solicitud->codigo_verificacion)) {
                $solicitud->codigo_verificacion = strtoupper(bin2hex(random_bytes(3))); // 6 caracteres
            }
            $solicitud->firma_integridad = $solicitud->calcularFirma();
        });

        static::updating(function ($solicitud) {
            $solicitud->firma_integridad = $solicitud->calcularFirma();
        });
    }

    public function calcularFirma(): string
    {
        $datos = implode('|', [
            $this->id_solicitud ?? '',
            $this->id_usuario_solicitante ?? '',
            $this->id_unidad ?? '',
            $this->titulo ?? '',
            $this->descripcion ?? '',
            $this->estado_solicitud ?? 'Abierta'
        ]);
        $key = config('app.key') ?: 'base64:fallbackkeyforhmacverification1234567890=';
        return hash_hmac('sha256', $datos, $key);
    }

    public function validarIntegridad(): bool
    {
        return hash_equals($this->firma_integridad ?? '', $this->calcularFirma());
    }

    // Relación de pertenencia: define el usuario solicitante que da origen a este ticket de soporte.
    public function solicitante()
    {
        return $this->belongsTo(User::class, 'id_usuario_solicitante', 'id_usuario');
    }

    // Relación de pertenencia: especifica la unidad organizativa u oficina donde se localiza el desperfecto.
    public function unidad()
    {
        return $this->belongsTo(Unidad::class, 'id_unidad', 'id_unidad');
    }

    // Relación de uno a muchos: referencias o fotos anexadas por el solicitante en concepto de evidencia.
    public function evidencias()
    {
        return $this->hasMany(Evidencia::class, 'id_solicitud', 'id_solicitud');
    }

    // Relación de uno a uno: detalla la asignación de personal técnico para resolver la solicitud.
    public function asignacion()
    {
        return $this->hasOne(Asignacion::class, 'id_solicitud', 'id_solicitud');
    }

    // Relación de uno a muchos: solicitudes de reasignación de técnico asociadas
    public function reasignaciones()
    {
        return $this->hasMany(SolicitudReasignacion::class, 'id_solicitud', 'id_solicitud');
    }

    // Relación de uno a muchos: bitácora histórica de todas las transiciones de estado para auditoría obligatoria.
    public function historial()
    {
        return $this->hasMany(HistorialCambio::class, 'id_solicitud', 'id_solicitud');
    }

    // Relación de uno a uno: encuesta de satisfacción y valoración de calidad completada por el usuario.
    public function encuesta()
    {
        return $this->hasOne(Encuesta::class, 'id_solicitud', 'id_solicitud');
    }
}
