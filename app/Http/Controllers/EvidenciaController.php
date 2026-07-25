<?php

namespace App\Http\Controllers;

use App\Models\Solicitud;
use App\Models\Evidencia;
use Illuminate\Http\Request;

class EvidenciaController extends Controller
{
    /**
     * Listar todas las evidencias de una solicitud
     */
    public function index(Solicitud $solicitud)
    {
        $evidencias = $solicitud->evidencias()->with('user')->get();

        return response()->json($evidencias);
    }

    /**
     * Subir una nueva evidencia (foto o documento) para una solicitud
     */
    public function store(Request $request, Solicitud $solicitud)
    {
        $request->validate([
            'archivo' => 'required|file|mimes:jpg,jpeg,png,pdf|max:10240',
        ]);

        $archivo = $request->file('archivo');
        $ruta = $archivo->store('evidencias', 'public');

        $evidencia = Evidencia::create([
            'solicitud_id' => $solicitud->id,
            'user_id' => $request->user()?->id,
            'nombre_archivo' => $archivo->getClientOriginalName(),
            'ruta' => $ruta,
            'tipo' => $archivo->getClientMimeType(),
        ]);

        return response()->json($evidencia->load('user'), 201);
    }

    /**
     * Eliminar una evidencia
     */
    public function destroy(Evidencia $evidencia)
    {
        \Storage::disk('public')->delete($evidencia->ruta);
        $evidencia->delete();

        return response()->json(null, 204);
    }
}