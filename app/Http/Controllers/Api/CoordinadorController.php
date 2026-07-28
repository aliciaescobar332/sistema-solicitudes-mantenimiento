<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Solicitud;
use App\Models\Asignacion;
use App\Models\User;
use App\Models\HistorialCambio;
use Illuminate\Http\Request;

class CoordinadorController extends Controller
{
    // Listar todas las solicitudes abiertas o devueltas para revisión del coordinador
    public function solicitudesPendientes(Request $request)
    {
        $query = Solicitud::with(['solicitante', 'unidad.sede', 'evidencias'])
            ->whereIn('estado_solicitud', ['Abierta', 'Devuelta']);

        if ($request->filled('prioridad')) {
            $query->where('prioridad', $request->prioridad);
        }

        if ($request->filled('id_unidad')) {
            $query->where('id_unidad', $request->id_unidad);
        }

        $perPage = $request->integer('per_page', 15);
        return response()->json($query->oldest()->paginate($perPage));
    }

    // Clasificar y asignar prioridad a la solicitud
    public function clasificar(Request $request, string $id)
    {
        $solicitud = Solicitud::findOrFail($id);

        $request->validate([
            'prioridad' => 'required|in:Baja,Media,Alta,Crítica',
        ]);

        $anterior = $solicitud->prioridad;
        $solicitud->update(['prioridad' => $request->prioridad]);

        $this->registrarHistorial($solicitud, $request->user()->id_usuario, 'prioridad', $anterior, $request->prioridad);

        return response()->json([
            'mensaje'   => 'Prioridad asignada con éxito.',
            'solicitud' => $solicitud,
        ]);
    }

    // Devolver la solicitud para correcciones del solicitante
    public function devolver(Request $request, string $id)
    {
        $solicitud = Solicitud::findOrFail($id);

        $request->validate([
            'motivo' => 'required|string',
        ]);

        $estadoAnterior = $solicitud->estado_solicitud;
        $solicitud->update(['estado_solicitud' => 'Devuelta']);

        $this->registrarHistorial($solicitud, $request->user()->id_usuario, 'estado_solicitud', $estadoAnterior, 'Devuelta');

        // Registrar el motivo de la devolución en el historial de cambios
        $this->registrarHistorial($solicitud, $request->user()->id_usuario, 'motivo_devolucion', null, $request->motivo);

        return response()->json(['mensaje' => 'La solicitud ha sido devuelta al solicitante de manera formal.']);
    }

    // Asignar un técnico de soporte a la solicitud
    public function asignar(Request $request, string $id)
    {
        $solicitud = Solicitud::findOrFail($id);

        if (!in_array($solicitud->estado_solicitud, ['Abierta', 'Devuelta'])) {
            return response()->json([
                'mensaje' => 'Solo se pueden asignar solicitudes que se encuentren abiertas o devueltas en el sistema.',
            ], 422);
        }

        $request->validate([
            'id_usuario_tecnico' => 'required|exists:users,id_usuario',
        ]);

        // Validar que el usuario seleccionado tenga rol de Técnico
        $tecnico = User::with('rol')->findOrFail($request->id_usuario_tecnico);
        if (!$tecnico->tieneRol('Técnico')) {
            return response()->json(['mensaje' => 'El usuario seleccionado no posee el rol de Técnico. Favor verificar.'], 422);
        }

        // Eliminar asignación previa si existe (reasignación)
        Asignacion::where('id_solicitud', $solicitud->id_solicitud)->delete();

        $asignacion = Asignacion::create([
            'id_solicitud'           => $solicitud->id_solicitud,
            'id_usuario_tecnico'     => $request->id_usuario_tecnico,
            'id_usuario_coordinador' => $request->user()->id_usuario,
            'fecha_asignacion'       => now(),
        ]);

        $estadoAnterior = $solicitud->estado_solicitud;
        $solicitud->update(['estado_solicitud' => 'Asignada']);

        $this->registrarHistorial($solicitud, $request->user()->id_usuario, 'estado_solicitud', $estadoAnterior, 'Asignada');

        return response()->json([
            'mensaje'    => 'La solicitud ha sido asignada formalmente al técnico correspondiente.',
            'asignacion' => $asignacion->load('tecnico'),
        ], 201);
    }

    // Validar el trabajo realizado y marcar la solicitud como Validada
    public function validarCierre(Request $request, string $id)
    {
        $solicitud = Solicitud::findOrFail($id);

        if ($solicitud->estado_solicitud !== 'En Proceso') {
            return response()->json([
                'mensaje' => 'No es posible realizar la acción. Solo se puede cerrar una solicitud que se encuentre en proceso.',
            ], 422);
        }

        $solicitud->update(['estado_solicitud' => 'Validada']);
        $this->registrarHistorial($solicitud, $request->user()->id_usuario, 'estado_solicitud', 'En Proceso', 'Validada');

        return response()->json(['mensaje' => 'Solicitud validada y cerrada correctamente.']);
    }

    // Listar las solicitudes de reasignación pendientes
    public function listarReasignacionesPendientes(Request $request)
    {
        $perPage = $request->integer('per_page', 15);
        $reasignaciones = \App\Models\SolicitudReasignacion::with([
            'solicitud.unidad.sede',
            'tecnicoSolicitante',
            'tecnicoPropuesto'
        ])->where('estado', 'Pendiente')
          ->latest()
          ->paginate($perPage);

        return response()->json($reasignaciones);
    }

    // Obtener la carga de trabajo actual de los técnicos activos
    public function obtenerCargaTrabajoTecnicos(Request $request)
    {
        $tecnicos = \App\Models\User::whereHas('rol', fn($q) => $q->where('nombre_rol', 'Técnico'))
            ->where('estado', 'Activo')
            ->withCount(['asignacionesTecnico as tickets_activos' => function ($q) {
                $q->whereHas('solicitud', fn($sol) => $sol->whereIn('estado_solicitud', ['Asignada', 'En Proceso']));
            }])
            ->orderBy('tickets_activos', 'asc')
            ->get()
            ->map(fn($t) => [
                'id_usuario'      => $t->id_usuario,
                'nombre_completo' => $t->nombre . ' ' . $t->apellido,
                'tickets_activos' => $t->tickets_activos,
            ]);

        return response()->json($tecnicos);
    }

    // Resolver (Aprobar o Rechazar) una solicitud de reasignación
    public function resolverReasignacion(Request $request, string $idReasignacion)
    {
        $reasignacion = \App\Models\SolicitudReasignacion::where('estado', 'Pendiente')
            ->findOrFail($idReasignacion);

        $request->validate([
            'decision' => 'required|in:Aprobada,Rechazada',
            'id_usuario_tecnico_nuevo' => 'required_if:decision,Aprobada|exists:users,id_usuario',
            'comentarios_coordinador' => 'nullable|string',
        ]);

        $solicitud = $reasignacion->solicitud;

        if ($request->decision === 'Rechazada') {
            $reasignacion->update([
                'estado' => 'Rechazada',
                'id_usuario_coordinador' => $request->user()->id_usuario,
                'comentarios_coordinador' => $request->comentarios_coordinador,
            ]);

            return response()->json([
                'mensaje' => 'La solicitud de reasignación ha sido rechazada de manera formal.',
                'reasignacion' => $reasignacion,
            ]);
        }

        // Si es aprobada:
        $nuevoTecnico = \App\Models\User::with('rol')->findOrFail($request->id_usuario_tecnico_nuevo);
        if (!$nuevoTecnico->tieneRol('Técnico')) {
            return response()->json(['mensaje' => 'El nuevo usuario propuesto no posee el rol de Técnico.'], 422);
        }

        // Obtener asignación previa
        $asignacion = Asignacion::where('id_solicitud', $solicitud->id_solicitud)->first();
        $tecnicoAnteriorId = $asignacion ? $asignacion->id_usuario_tecnico : null;

        // Actualizar o crear asignación
        if ($asignacion) {
            $asignacion->update([
                'id_usuario_tecnico' => $request->id_usuario_tecnico_nuevo,
                'id_usuario_coordinador' => $request->user()->id_usuario,
                'fecha_asignacion' => now(),
            ]);
        } else {
            Asignacion::create([
                'id_solicitud' => $solicitud->id_solicitud,
                'id_usuario_tecnico' => $request->id_usuario_tecnico_nuevo,
                'id_usuario_coordinador' => $request->user()->id_usuario,
                'fecha_asignacion' => now(),
            ]);
        }

        // Obtener nombres para auditoría
        $tecnicoAnteriorNombre = 'Ninguno';
        if ($tecnicoAnteriorId) {
            $tecnicoAnterior = \App\Models\User::find($tecnicoAnteriorId);
            $tecnicoAnteriorNombre = $tecnicoAnterior ? $tecnicoAnterior->nombre . ' ' . $tecnicoAnterior->apellido : 'Técnico Anterior';
        }
        $nuevoTecnicoNombre = $nuevoTecnico->nombre . ' ' . $nuevoTecnico->apellido;

        // Si la solicitud estaba en proceso, revertir a "Asignada"
        $estadoAnterior = $solicitud->estado_solicitud;
        if ($estadoAnterior === 'En Proceso') {
            $solicitud->update([
                'estado_solicitud' => 'Asignada',
            ]);
            $this->registrarHistorial($solicitud, $request->user()->id_usuario, 'estado_solicitud', 'En Proceso', 'Asignada');
        }

        // Registrar el cambio de técnico en el historial
        $this->registrarHistorial(
            $solicitud,
            $request->user()->id_usuario,
            'tecnico_asignado',
            $tecnicoAnteriorNombre,
            $nuevoTecnicoNombre
        );

        // Actualizar la solicitud de reasignación
        $reasignacion->update([
            'estado' => 'Aprobada',
            'id_usuario_coordinador' => $request->user()->id_usuario,
            'comentarios_coordinador' => $request->comentarios_coordinador,
        ]);

        return response()->json([
            'mensaje' => 'La solicitud de reasignación ha sido aprobada y el ticket reasignado con éxito.',
            'reasignacion' => $reasignacion->load('solicitud'),
        ]);
    }

    // Registro histórico de cambios
    private function registrarHistorial(Solicitud $solicitud, string $idUsuario, string $campo, ?string $anterior, ?string $nuevo): void
    {
        HistorialCambio::create([
            'id_solicitud'     => $solicitud->id_solicitud,
            'id_usuario'       => $idUsuario,
            'campo_modificado' => $campo,
            'valor_anterior'   => $anterior,
            'valor_nuevo'      => $nuevo,
        ]);
    }
}
