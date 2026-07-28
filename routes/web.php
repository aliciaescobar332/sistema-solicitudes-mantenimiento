<?php

use Illuminate\Support\Facades\Route;

Route::get('/login', function () {
    if (request()->expectsJson()) {
        return response()->json(['message' => 'No autenticado.'], 401);
    }
    return view('app');
})->name('login');

/*
|--------------------------------------------------------------------
| SPA (Vue) — login, vistas de la app, etc.
|--------------------------------------------------------------------
| Catch-all: cualquier ruta no capturada arriba cae en la vista 'app',
| que monta la aplicación Vue. Debe ir SIEMPRE al final del archivo.
|--------------------------------------------------------------------
*/
Route::view('/{any}', 'app')->where('any', '.*');