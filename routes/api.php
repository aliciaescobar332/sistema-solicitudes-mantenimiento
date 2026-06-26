<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UsuariosController;
use App\Http\Controllers\Api\SolicitudesController;
use App\Http\Controllers\Api\CoordinadorController;
use App\Http\Controllers\Api\TecnicoController;
use App\Http\Controllers\Api\EncuestasController;
use App\Http\Controllers\Api\ReportesController;
use App\Http\Controllers\Api\CatalogosController;

// ------------------------------------------------------------------
// Rutas públicas: Accesibles de forma general sin necesidad de token.
// ------------------------------------------------------------------
Route::post('/login', [AuthController::class, 'login']);

// Catálogos públicos que permiten cargar de forma dinámica sedes y unidades en el formulario.
Route::get('/catalogos/sedes', [CatalogosController::class, 'sedes']);
Route::get('/catalogos/sedes/{id}/unidades', [CatalogosController::class, 'unidadesPorSede']);


// ------------------------------------------------------------------
// Rutas protegidas: Requieren de forma obligatoria autenticación mediante Sanctum.
// ------------------------------------------------------------------
Route::middleware('auth:sanctum')->group(function () {

    // Endpoints de autenticación general.
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);


    // ------------------------------------------------------------------
    // Perfil Administrador: Rutas de administración y control general de usuarios del sistema.
    // ------------------------------------------------------------------
    Route::middleware('rol:Administrador')->prefix('admin')->group(function () {
        Route::get('/usuarios', [UsuariosController::class, 'index']);
        Route::post('/usuarios', [UsuariosController::class, 'store']);
        Route::get('/usuarios/{id}', [UsuariosController::class, 'show']);
        Route::put('/usuarios/{id}', [UsuariosController::class, 'update']);
        Route::patch('/usuarios/{id}/deshabilitar', [UsuariosController::class, 'deshabilitar']);
        Route::patch('/usuarios/{id}/habilitar', [UsuariosController::class, 'habilitar']);

        // Catálogos auxiliares de sedes y unidades organizativas.
        Route::post('/catalogos/sedes', [CatalogosController::class, 'crearSede']);
        Route::post('/catalogos/unidades', [CatalogosController::class, 'crearUnidad']);
    });


    // ------------------------------------------------------------------
    // Perfil Solicitante: Rutas para la gestión autónoma de sus propias solicitudes de soporte.
    // ------------------------------------------------------------------
    Route::middleware('rol:Solicitante,Administrador')->prefix('solicitante')->group(function () {
        Route::get('/solicitudes', [SolicitudesController::class, 'index']);
        Route::post('/solicitudes', [SolicitudesController::class, 'store']);
        Route::get('/solicitudes/{id}', [SolicitudesController::class, 'show']);
        Route::put('/solicitudes/{id}', [SolicitudesController::class, 'update']);
        Route::patch('/solicitudes/{id}/cancelar', [SolicitudesController::class, 'cancelar']);
        Route::post('/solicitudes/{id}/evidencias', [SolicitudesController::class, 'adjuntarEvidencia']);

        // Encuesta de satisfacción y control de calidad al culminar la solicitud.
        Route::post('/solicitudes/{id}/encuesta', [EncuestasController::class, 'store']);
    });


    // ------------------------------------------------------------------
    // Perfil Coordinador: Rutas para la priorización y asignación formal de soporte técnico.
    // ------------------------------------------------------------------
    Route::middleware('rol:Coordinador,Administrador')->prefix('coordinador')->group(function () {
        Route::get('/solicitudes/pendientes', [CoordinadorController::class, 'solicitudesPendientes']);
        Route::patch('/solicitudes/{id}/clasificar', [CoordinadorController::class, 'clasificar']);
        Route::patch('/solicitudes/{id}/devolver', [CoordinadorController::class, 'devolver']);
        Route::post('/solicitudes/{id}/asignar', [CoordinadorController::class, 'asignar']);
        Route::patch('/solicitudes/{id}/validar-cierre', [CoordinadorController::class, 'validarCierre']);
    });


    // ------------------------------------------------------------------
    // Perfil Técnico: Rutas para el registro de bitácoras y atención en sitio del desperfecto.
    // ------------------------------------------------------------------
    Route::middleware('rol:Técnico,Administrador')->prefix('tecnico')->group(function () {
        Route::get('/asignaciones', [TecnicoController::class, 'misAsignaciones']);
        Route::patch('/asignaciones/{id}/iniciar', [TecnicoController::class, 'iniciarAtencion']);
        Route::post('/asignaciones/{id}/bitacoras', [TecnicoController::class, 'registrarBitacora']);
        Route::get('/asignaciones/{id}/bitacoras', [TecnicoController::class, 'verBitacoras']);
        Route::patch('/asignaciones/{id}/solicitar-cierre', [TecnicoController::class, 'solicitarCierre']);
    });


    // ------------------------------------------------------------------
    // Módulo de Reportes y Estadísticas Generales: Acceso para Coordinadores y Administradores.
    // ------------------------------------------------------------------
    Route::middleware('rol:Coordinador,Administrador')->prefix('reportes')->group(function () {
        Route::get('/dashboard', [ReportesController::class, 'dashboard']);
        Route::get('/por-area', [ReportesController::class, 'porArea']);
        Route::get('/tecnicos', [ReportesController::class, 'rendimientoTecnicos']);
    });

    // Consulta detallada del ticket (accesible para el coordinador o el técnico asignado).
    Route::middleware('rol:Coordinador,Técnico,Administrador')
        ->get('/solicitudes/{id}', [SolicitudesController::class, 'show']);
});
