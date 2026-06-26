<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Solicitud;
use App\Models\Asignacion;
use App\Models\Encuesta;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportesController extends Controller
{
    // Obtener totales por estado, prioridad y otras métricas del dashboard
    public function dashboard()
    {
        $totalesPorEstado = Solicitud::select('estado_solicitud', DB::raw('count(*) as total'))
            ->groupBy('estado_solicitud')
            ->pluck('total', 'estado_solicitud');

        $totalesPorPrioridad = Solicitud::whereNotNull('prioridad')
            ->select('prioridad', DB::raw('count(*) as total'))
            ->groupBy('prioridad')
            ->pluck('total', 'prioridad');

        // Técnicos con mayor volumen de solicitudes resueltas en los últimos 30 días.
        $tecnicosMasActivos = Asignacion::with('tecnico')
            ->whereHas('solicitud', fn($q) => $q->whereIn('estado_solicitud', ['Validada', 'Cerrada']))
            ->where('created_at', '>=', now()->subDays(30))
            ->select('id_usuario_tecnico', DB::raw('count(*) as total'))
            ->groupBy('id_usuario_tecnico')
            ->orderByDesc('total')
            ->limit(5)
            ->get()
            ->map(fn($a) => [
                'tecnico' => $a->tecnico->nombre . ' ' . $a->tecnico->apellido,
                'total'   => $a->total,
            ]);

        // Promedio de calificaciones de las encuestas
        $promedioEncuestas = Encuesta::select(
            DB::raw('round(avg(calificacion_rapidez), 1) as rapidez'),
            DB::raw('round(avg(calificacion_calidad), 1) as calidad'),
            DB::raw('round(avg(calificacion_amabilidad), 1) as amabilidad'),
        )->first();

        return response()->json([
            'por_estado'         => $totalesPorEstado,
            'por_prioridad'      => $totalesPorPrioridad,
            'tecnicos_activos'   => $tecnicosMasActivos,
            'promedio_encuestas' => $promedioEncuestas,
        ]);
    }

    // Obtener solicitudes agrupadas por unidad con filtro de fechas opcional
    public function porArea(Request $request)
    {
        $request->validate([
            'desde' => 'nullable|date',
            'hasta' => 'nullable|date',
        ]);

        $datos = Solicitud::with('unidad.sede')
            ->when($request->desde, fn($q) => $q->whereDate('fecha_apertura', '>=', $request->desde))
            ->when($request->hasta, fn($q) => $q->whereDate('fecha_apertura', '<=', $request->hasta))
            ->select('id_unidad', 'estado_solicitud', DB::raw('count(*) as total'))
            ->groupBy('id_unidad', 'estado_solicitud')
            ->get();

        return response()->json($datos);
    }

    // Obtener estadísticas de rendimiento para cada técnico
    public function rendimientoTecnicos(Request $request)
    {
        $tecnicos = User::whereHas('rol', fn($q) => $q->where('nombre_rol', 'Técnico'))
            ->with(['asignacionesTecnico.solicitud'])
            ->get()
            ->map(function ($t) {
                $asignaciones = $t->asignacionesTecnico;
                $cerradas = $asignaciones->filter(
                    fn($a) => in_array($a->solicitud->estado_solicitud ?? '', ['Validada', 'Cerrada'])
                );

                return [
                    'tecnico'             => $t->nombre . ' ' . $t->apellido,
                    'total_asignadas'     => $asignaciones->count(),
                    'total_resueltas'     => $cerradas->count(),
                    'tasa_resolucion'     => $asignaciones->count()
                        ? round($cerradas->count() / $asignaciones->count() * 100, 1) . '%'
                        : '0%',
                ];
            });

        return response()->json($tecnicos);
    }
}
