<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Asignacion;
use App\Models\Bitacora;
use App\Models\Solicitud;
use App\Models\HistorialCambio;
use Illuminate\Http\Request;

class TecnicoController extends Controller
{
    // Consultar las asignaciones asignadas al técnico autenticado
    public function misAsignaciones(Request $request)
    {
        $asignaciones = Asignacion::with(['solicitud.unidad.sede', 'solicitud.evidencias', 'bitacoras'])
            ->where('id_usuario_tecnico', $request->user()->id_usuario)
            ->latest()
            ->get();

        return response()->json($asignaciones);
    }

    // Registrar el inicio de la atención del ticket con validación OTP
    public function iniciarAtencion(Request $request, string $idAsignacion)
    {
        $asignacion = Asignacion::where('id_usuario_tecnico', $request->user()->id_usuario)
            ->findOrFail($idAsignacion);

        $request->validate([
            'codigo_verificacion' => 'required|string|size:6',
        ]);

        $solicitud = $asignacion->solicitud;

        if ($solicitud->estado_solicitud !== 'Asignada') {
            return response()->json([
                'mensaje' => 'No es posible realizar la acción. Esta solicitud ya se encuentra en atención o fue cerrada.',
            ], 422);
        }

        // Validar el código de verificación física OTP ingresado por el técnico
        if (strtoupper($request->codigo_verificacion) !== strtoupper($solicitud->codigo_verificacion)) {
            return response()->json([
                'mensaje' => 'El código de verificación física ingresado es incorrecto. Favor solicitar el OTP correcto al solicitante.',
            ], 422);
        }

        $solicitud->update(['estado_solicitud' => 'En Proceso']);

        HistorialCambio::create([
            'id_solicitud'     => $solicitud->id_solicitud,
            'id_usuario'       => $request->user()->id_usuario,
            'campo_modificado' => 'estado_solicitud',
            'valor_anterior'   => 'Asignada',
            'valor_nuevo'      => 'En Proceso',
        ]);

        return response()->json(['mensaje' => 'Estado de la solicitud actualizado a En Proceso.']);
    }

    // Registrar una nueva entrada en la bitácora de trabajo
    public function registrarBitacora(Request $request, string $idAsignacion)
    {
        $asignacion = Asignacion::where('id_usuario_tecnico', $request->user()->id_usuario)
            ->findOrFail($idAsignacion);

        $request->validate([
            'descripcion_trabajo' => 'required|string',
            'materiales'          => 'nullable|string',
            'fecha_inicio'        => 'nullable|date',
            'fecha_fin'           => 'nullable|date|after_or_equal:fecha_inicio',
        ]);

        $bitacora = Bitacora::create([
            'id_asignacion'      => $asignacion->id_asignacion,
            'descripcion_trabajo' => $request->descripcion_trabajo,
            'materiales'         => $request->materiales,
            'fecha_inicio'       => $request->fecha_inicio,
            'fecha_fin'          => $request->fecha_fin,
        ]);

        return response()->json([
            'mensaje'  => 'Detalle de la bitácora de trabajo guardado exitosamente.',
            'bitacora' => $bitacora,
        ], 201);
    }

    // Obtener las bitácoras asociadas a una asignación
    public function verBitacoras(Request $request, string $idAsignacion)
    {
        $asignacion = Asignacion::where('id_usuario_tecnico', $request->user()->id_usuario)
            ->findOrFail($idAsignacion);

        return response()->json($asignacion->bitacoras()->latest()->get());
    }

    // Solicitar la revisión y el cierre de la solicitud al coordinador
    public function solicitarCierre(Request $request, string $idAsignacion)
    {
        $asignacion = Asignacion::where('id_usuario_tecnico', $request->user()->id_usuario)
            ->findOrFail($idAsignacion);

        $solicitud = $asignacion->solicitud;

        if ($solicitud->estado_solicitud !== 'En Proceso') {
            return response()->json([
                'mensaje' => 'Acción denegada. Solo se puede solicitar el cierre de solicitudes que estén en proceso.',
            ], 422);
        }

        // Registrar la solicitud de cierre en el historial de cambios
        HistorialCambio::create([
            'id_solicitud'     => $solicitud->id_solicitud,
            'id_usuario'       => $request->user()->id_usuario,
            'campo_modificado' => 'cierre_solicitado',
            'valor_anterior'   => null,
            'valor_nuevo'      => 'Técnico solicita revisión para cierre',
        ]);

        return response()->json([
            'mensaje' => 'Solicitud de revisión para el cierre enviada de conformidad al coordinador.',
        ]);
    }

    // Solicitar la reasignación de una orden de trabajo asignada
    public function solicitarReasignacion(Request $request, string $idAsignacion)
    {
        $asignacion = Asignacion::where('id_usuario_tecnico', $request->user()->id_usuario)
            ->findOrFail($idAsignacion);

        $solicitud = $asignacion->solicitud;

        // Validar que el ticket esté en un estado que permita ser reasignado
        if (!in_array($solicitud->estado_solicitud, ['Asignada', 'En Proceso'])) {
            return response()->json([
                'mensaje' => 'No es posible solicitar la reasignación. El ticket debe encontrarse en estado Asignada o En Proceso.',
            ], 422);
        }

        $request->validate([
            'motivo' => 'required|string|min:10',
            'id_usuario_tecnico_propuesto' => 'nullable|exists:users,id_usuario',
        ]);

        // Si se propone un técnico, validar que tenga el rol de Técnico
        if ($request->filled('id_usuario_tecnico_propuesto')) {
            $tecnicoPropuesto = \App\Models\User::with('rol')->findOrFail($request->id_usuario_tecnico_propuesto);
            if (!$tecnicoPropuesto->tieneRol('Técnico')) {
                return response()->json([
                    'mensaje' => 'El usuario propuesto no posee el rol de Técnico.',
                ], 422);
            }
        }

        // Crear la solicitud de reasignación
        $solicitudReasignacion = \App\Models\SolicitudReasignacion::create([
            'id_solicitud' => $solicitud->id_solicitud,
            'id_usuario_tecnico_solicitante' => $request->user()->id_usuario,
            'id_usuario_tecnico_propuesto' => $request->id_usuario_tecnico_propuesto,
            'motivo' => $request->motivo,
            'estado' => 'Pendiente',
        ]);

        return response()->json([
            'mensaje' => 'Solicitud de reasignación enviada con éxito al líder técnico/coordinador.',
            'solicitud_reasignacion' => $solicitudReasignacion,
        ], 201);
    }
}
