<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Encuesta;
use App\Models\Solicitud;
use Illuminate\Http\Request;

class EncuestasController extends Controller
{
    // Registrar la encuesta de satisfacción del solicitante
    public function store(Request $request, string $idSolicitud)
    {
        $solicitud = Solicitud::where('id_usuario_solicitante', $request->user()->id_usuario)
            ->findOrFail($idSolicitud);

        if ($solicitud->estado_solicitud !== 'Validada') {
            return response()->json([
                'mensaje' => 'Solo puede calificar una solicitud que ya haya sido validada por el coordinador.',
            ], 422);
        }

        // Evitar duplicados de encuestas
        if ($solicitud->encuesta) {
            return response()->json([
                'mensaje' => 'Ya completó la encuesta de satisfacción para esta solicitud.',
            ], 409);
        }

        $request->validate([
            'calificacion_rapidez'     => 'required|integer|min:1|max:5',
            'calificacion_calidad'     => 'required|integer|min:1|max:5',
            'calificacion_amabilidad'  => 'required|integer|min:1|max:5',
            'comentarios'              => 'nullable|string|max:500',
        ]);

        $encuesta = Encuesta::create([
            'id_solicitud'            => $solicitud->id_solicitud,
            'calificacion_rapidez'    => $request->calificacion_rapidez,
            'calificacion_calidad'    => $request->calificacion_calidad,
            'calificacion_amabilidad' => $request->calificacion_amabilidad,
            'comentarios'             => $request->comentarios,
        ]);

        // Cerrar la solicitud de forma definitiva
        $solicitud->update(['estado_solicitud' => 'Cerrada']);

        return response()->json([
            'mensaje'  => '¡Agradecemos mucho su calificación!',
            'encuesta' => $encuesta,
        ], 201);
    }
}
