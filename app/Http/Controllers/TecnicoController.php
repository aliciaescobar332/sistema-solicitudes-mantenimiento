<?php

namespace App\Http\Controllers;

use App\Models\User;

class TecnicoController extends Controller
{
    public function index()
    {
        $tecnicos = User::where('rol', 'tecnico')
            ->where('activo', true)
            ->select('id', 'name', 'email')
            ->get();

        return response()->json($tecnicos);
    }
}