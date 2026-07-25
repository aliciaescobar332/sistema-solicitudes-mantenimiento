<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\SolicitudController;
use App\Http\Controllers\DepartamentoController;
use App\Http\Controllers\TecnicoController;
use App\Http\Controllers\EvidenciaController;
use Illuminate\Http\Request;

Route::post('/login', [AuthController::class, 'login']);
Route::post('/password/email', [AuthController::class, 'forgotPassword']);
Route::post('/password/reset', [AuthController::class, 'resetPassword'])->name('password.reset');

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('/solicitudes/resumen', [SolicitudController::class, 'resumen']);
    Route::get('/solicitudes/mias', [SolicitudController::class, 'misTareas']);
    Route::get('/solicitudes', [SolicitudController::class, 'index']);
    Route::post('/solicitudes', [SolicitudController::class, 'store']);
    Route::get('/solicitudes/{solicitud}', [SolicitudController::class, 'show']);
    Route::get('/solicitudes/{solicitud}/historial', [SolicitudController::class, 'historial']);
    Route::put('/solicitudes/{solicitud}', [SolicitudController::class, 'update']);
    Route::delete('/solicitudes/{solicitud}', [SolicitudController::class, 'destroy']);
    Route::apiResource('departamentos', DepartamentoController::class);

    Route::get('/tecnicos', [TecnicoController::class, 'index']);

    Route::get('/solicitudes/{solicitud}/evidencias', [EvidenciaController::class, 'index']);
    Route::post('/solicitudes/{solicitud}/evidencias', [EvidenciaController::class, 'store']);
    Route::delete('/evidencias/{evidencia}', [EvidenciaController::class, 'destroy']);
});