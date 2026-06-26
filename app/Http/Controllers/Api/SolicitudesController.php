<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Solicitud;
use App\Models\Evidencia;
use App\Models\HistorialCambio;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SolicitudesController extends Controller
{
    // Listar solicitudes creadas por el usuario autenticado
    public function index(Request $request)
    {
        $query = Solicitud::with(['unidad.sede', 'evidencias', 'asignacion.tecnico'])
            ->where('id_usuario_solicitante', $request->user()->id_usuario);

        // Para efectos de filtrado en el frontend, validamos parámetros opcionales.
        if ($request->filled('estado')) {
            $query->where('estado_solicitud', $request->estado);
        }

        if ($request->filled('desde')) {
            $query->whereDate('fecha_apertura', '>=', $request->desde);
        }

        if ($request->filled('hasta')) {
            $query->whereDate('fecha_apertura', '<=', $request->hasta);
        }

        return response()->json($query->latest()->get());
    }

    // Registro de una nueva solicitud a través del formulario provisto por el usuario.
    public function store(Request $request)
    {
        $request->validate([
            'id_unidad'   => 'required|exists:unidades,id_unidad',
            'titulo'      => 'required|string|max:200',
            'descripcion' => 'required|string',
        ]);

        $solicitud = Solicitud::create([
            'id_usuario_solicitante' => $request->user()->id_usuario,
            'id_unidad'              => $request->id_unidad,
            'titulo'                 => $request->titulo,
            'descripcion'            => $request->descripcion,
            'estado_solicitud'       => 'Abierta',
        ]);

        // Registrar la creación en el historial de cambios
        $this->registrarHistorial($solicitud, $request->user()->id_usuario, 'estado_solicitud', null, 'Abierta');

        return response()->json([
            'mensaje'   => 'Solicitud enviada con éxito. En breve será revisada por el coordinador.',
            'solicitud' => $solicitud->load('unidad.sede'),
        ], 201);
    }

    // Obtener el detalle completo de una solicitud
    public function show(Request $request, string $id)
    {
        $solicitud = Solicitud::with([
            'solicitante',
            'unidad.sede',
            'evidencias',
            'asignacion.tecnico',
            'asignacion.coordinador',
            'asignacion.bitacoras',
            'encuesta',
            'historial.usuario',
        ])->findOrFail($id);

        // Validar que el solicitante sea el dueño de la solicitud
        $usuario = $request->user();
        if ($usuario->rol->nombre_rol === 'Solicitante' && $solicitud->id_usuario_solicitante !== $usuario->id_usuario) {
            return response()->json(['mensaje' => 'No cuenta con la autorización requerida para visualizar esta solicitud.'], 403);
        }

        return response()->json($solicitud);
    }

    // Actualización de datos de una solicitud, siempre y cuando se mantenga en un estado que lo permita.
    public function update(Request $request, string $id)
    {
        $solicitud = Solicitud::where('id_usuario_solicitante', $request->user()->id_usuario)
            ->findOrFail($id);

        // Solo se permite la modificación si el ticket no ha sido asignado a un técnico todavía.
        if (!in_array($solicitud->estado_solicitud, ['Abierta', 'Devuelta'])) {
            return response()->json([
                'mensaje' => 'No es posible realizar la modificación dado que el ticket se encuentra en estado: ' . $solicitud->estado_solicitud,
            ], 422);
        }

        $request->validate([
            'titulo'      => 'sometimes|string|max:200',
            'descripcion' => 'sometimes|string',
            'id_unidad'   => 'sometimes|exists:unidades,id_unidad',
        ]);

        $solicitud->update($request->only(['titulo', 'descripcion', 'id_unidad']));

        return response()->json([
            'mensaje'   => 'Solicitud actualizada con éxito.',
            'solicitud' => $solicitud->load('unidad.sede'),
        ]);
    }

    // Permite al solicitante desistir y proceder con la cancelación formal del ticket ingresado.
    public function cancelar(Request $request, string $id)
    {
        $solicitud = Solicitud::where('id_usuario_solicitante', $request->user()->id_usuario)
            ->findOrFail($id);

        if (!in_array($solicitud->estado_solicitud, ['Abierta', 'Devuelta'])) {
            return response()->json([
                'mensaje' => 'Solo puede proceder a la cancelación de solicitudes que se encuentren en estado Abierta o Devuelta.',
            ], 422);
        }

        $estadoAnterior = $solicitud->estado_solicitud;
        $solicitud->update(['estado_solicitud' => 'Cancelada']);

        $this->registrarHistorial($solicitud, $request->user()->id_usuario, 'estado_solicitud', $estadoAnterior, 'Cancelada');

        return response()->json(['mensaje' => 'La solicitud ha sido cancelada satisfactoriamente.']);
    }

    // Subir un archivo de evidencia para la solicitud
    public function adjuntarEvidencia(Request $request, string $id)
    {
        $solicitud = Solicitud::where('id_usuario_solicitante', $request->user()->id_usuario)
            ->findOrFail($id);

        $request->validate([
            'archivo' => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120',
        ]);

        $path = $request->file('archivo')->store('evidencias', 'public');

        $evidencia = Evidencia::create([
            'id_solicitud' => $solicitud->id_solicitud,
            'url_recurso'  => Storage::url($path),
            'tipo_archivo' => $request->file('archivo')->getMimeType(),
        ]);

        return response()->json([
            'mensaje'   => 'Archivo de evidencia adjuntado con éxito.',
            'evidencia' => $evidencia,
        ], 201);
    }

    // Registrar cambio en el historial
    private function registrarHistorial(Solicitud $solicitud, string $idUsuario, string $campo, ?string $anterior, ?string $nuevo): void
    {
        HistorialCambio::create([
            'id_solicitud'    => $solicitud->id_solicitud,
            'id_usuario'      => $idUsuario,
            'campo_modificado' => $campo,
            'valor_anterior'  => $anterior,
            'valor_nuevo'     => $nuevo,
        ]);
    }
}
