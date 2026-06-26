<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Sede;
use App\Models\Unidad;
use Illuminate\Http\Request;

class CatalogosController extends Controller
{
    // Listar todas las sedes con sus respectivas unidades
    public function sedes()
    {
        return response()->json(Sede::with('unidades')->orderBy('nombre_sede')->get());
    }

    // Obtener las unidades de una sede específica
    public function unidadesPorSede(string $idSede)
    {
        $sede = Sede::with('unidades')->findOrFail($idSede);
        return response()->json($sede->unidades()->orderBy('nombre_unidad')->get());
    }

    // Crear una nueva sede (exclusivo para Administrador)
    public function crearSede(Request $request)
    {
        $request->validate([
            'nombre_sede' => 'required|string|max:100',
            'direccion'   => 'required|string',
        ]);

        $sede = Sede::create($request->only(['nombre_sede', 'direccion']));

        return response()->json([
            'mensaje' => 'Sede creada con éxito.',
            'sede'    => $sede,
        ], 201);
    }

    // Crear una nueva unidad organizativa dentro de una sede
    public function crearUnidad(Request $request)
    {
        $request->validate([
            'id_sede'      => 'required|exists:sedes,id_sede',
            'nombre_unidad' => 'required|string|max:100',
        ]);

        $unidad = Unidad::create($request->only(['id_sede', 'nombre_unidad']));

        return response()->json([
            'mensaje' => 'Unidad creada con éxito.',
            'unidad'  => $unidad->load('sede'),
        ], 201);
    }
}
