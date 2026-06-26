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

        return response()->json($query->oldest()->get());
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
