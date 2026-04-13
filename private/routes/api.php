<?php

use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ClienteController;
use App\Http\Controllers\ApiAppController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes — MAX Fitness & Health (Mobile)
|--------------------------------------------------------------------------
|
| Rutas consumidas por la app móvil React Native.
| Autenticación: Laravel Sanctum (tokens Bearer).
|
*/

// Ruta pública de health-check
Route::get('/health', fn() => response()->json(['status' => 'ok', 'app' => config('app.name')]));

// Rutas de autenticación (sin middleware auth)
Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
});

// Rutas protegidas con Sanctum
Route::middleware('api.auth')->group(function () {
    Route::prefix('auth')->group(function () {
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/logout', [AuthController::class, 'logout']);
    });

    // Rutas exclusivas del Administrador
    Route::middleware('admin')->prefix('admin')->group(function () {
        Route::get('/dashboard', [AdminController::class, 'dashboard']);
        Route::get('/agendas/catalogo', [AdminController::class, 'agendaCatalogo']);
        Route::post('/agendas', [AdminController::class, 'storeAgenda']);
        Route::get('/clientes', [AdminController::class, 'clientes']);
        Route::get('/clientes/{slug}', [AdminController::class, 'clienteDetalle']);
        Route::put('/clientes/{slug}', [AdminController::class, 'updateCliente']);
        Route::get('/clientes/{slug}/pesos', [AdminController::class, 'pesos']);
        Route::get('/clientes/{slug}/agenda', [AdminController::class, 'agendaCompleta']);
        Route::get('/clientes/{slug}/entrenamientos', [AdminController::class, 'entrenamientos']);
        Route::get('/clientes/{slug}/cuotas', [AdminController::class, 'cuotas']);
    });

    // Rutas del Cliente
    Route::prefix('cliente')->group(function () {
        Route::get('/portada', [ClienteController::class, 'portada']);
        Route::get('/cuotas', [ClienteController::class, 'cuotas']);
        Route::get('/pesos', [ClienteController::class, 'pesos']);
        Route::get('/agenda', [ClienteController::class, 'agenda']);
    });
});

// ==========================================================================
// APP MÓVIL — Proxy hacia la API Node.js (ApiAppController)
// ==========================================================================
// Estas rutas son consumidas EXCLUSIVAMENTE por la app React Native.
// El controlador reenvía todas las peticiones al servidor Node.js configurado
// en APP_NODE_API_URL. No aplica middleware Sanctum de Laravel: la auth es
// gestionada por el propio servidor Node.js (tokens Bearer).
// ==========================================================================

Route::prefix('app')->group(function () {

    // Rutas públicas (sin autenticación)
    Route::get('/health', [ApiAppController::class, 'health']);
    Route::post('/auth/login', [ApiAppController::class, 'login']);

    // Rutas protegidas con Sanctum
    Route::middleware('api.auth')->group(function () {

        // Autenticación
        Route::get('/auth/me',              [ApiAppController::class, 'me']);
        Route::post('/auth/logout',         [ApiAppController::class, 'logout']);
        Route::post('/auth/change-password', [ApiAppController::class, 'changePassword']);

        // -- Admin / Entrenador --------------------------------------------
        Route::prefix('admin')->group(function () {
            Route::get('/dashboard', [ApiAppController::class, 'adminDashboard']);

            // Agenda
            Route::get('/agendas/catalogo',  [ApiAppController::class, 'adminAgendaCatalogo']);
            Route::post('/agendas',          [ApiAppController::class, 'adminAgendaStore']);
            Route::put('/agendas/{id}',      [ApiAppController::class, 'adminAgendaUpdate']);
            Route::put('/agendas/{id}/estado', [ApiAppController::class, 'adminAgendaEstadoUpdate']);
            Route::get('/agenda/calendario', [ApiAppController::class, 'adminAgendaCalendario']);

            // Ejercicios del sistema
            Route::get('/ejercicios',           [ApiAppController::class, 'adminEjerciciosIndex']);
            Route::post('/ejercicios',          [ApiAppController::class, 'adminEjerciciosStore']);
            Route::put('/ejercicios/{id}',      [ApiAppController::class, 'adminEjerciciosUpdate']);
            Route::delete('/ejercicios/{id}',   [ApiAppController::class, 'adminEjerciciosDestroy']);

            // Clientes CRUD
            Route::get('/clientes',         [ApiAppController::class, 'adminClientesIndex']);
            Route::get('/morosos',          [ApiAppController::class, 'adminMorososIndex']);
            Route::get('/motivos',          [ApiAppController::class, 'adminMotivosIndex']);
            Route::post('/clientes',        [ApiAppController::class, 'adminClientesStore']);
            Route::put('/clientes/id/{id}', [ApiAppController::class, 'adminClienteUpdateById']);

            // Subpantallas de cliente (por slug)
            Route::get('/clientes/{slug}',                          [ApiAppController::class, 'adminClienteDetalle']);
            Route::put('/clientes/{slug}',                          [ApiAppController::class, 'adminClienteUpdate']);
            Route::get('/clientes/{slug}/pesos',                    [ApiAppController::class, 'adminClientePesos']);
            Route::get('/clientes/{slug}/agenda',                   [ApiAppController::class, 'adminClienteAgenda']);
            Route::get('/clientes/{slug}/entrenamientos',           [ApiAppController::class, 'adminClienteEntrenamientos']);
            Route::get('/clientes/{slug}/cuotas',                   [ApiAppController::class, 'adminClienteCuotas']);
            Route::post('/clientes/{slug}/cuotas',                  [ApiAppController::class, 'adminClienteCuotasStore']);
            Route::post('/clientes/{slug}/cuotas/pagar',            [ApiAppController::class, 'adminClienteCuotasPagar']);
            Route::get('/clientes/{slug}/metricas',                 [ApiAppController::class, 'adminClienteMetricasIndex']);
            Route::post('/clientes/{slug}/metricas',                [ApiAppController::class, 'adminClienteMetricasStore']);
            Route::get('/clientes/{slug}/evaluacion-inicial',       [ApiAppController::class, 'adminClienteEvaluacionInicial']);
            Route::get('/clientes/{slug}/ejercicios',               [ApiAppController::class, 'adminClienteEjerciciosIndex']);
            Route::get('/clientes/{slug}/ejercicios/{idEjercicio}', [ApiAppController::class, 'adminClienteEjercicioHistorial']);

            // Movimientos financieros
            Route::get('/movimientos',          [ApiAppController::class, 'adminMovimientosIndex']);
            Route::post('/movimientos',         [ApiAppController::class, 'adminMovimientosStore']);
            Route::put('/movimientos/{id}',     [ApiAppController::class, 'adminMovimientosUpdate']);
            Route::delete('/movimientos/{id}',  [ApiAppController::class, 'adminMovimientosDestroy']);

            // Usuarios del sistema
            Route::get('/usuarios',             [ApiAppController::class, 'adminUsuariosIndex']);
            Route::post('/usuarios',            [ApiAppController::class, 'adminUsuariosStore']);
            Route::put('/usuarios/{id}',        [ApiAppController::class, 'adminUsuariosUpdate']);
            Route::delete('/usuarios/{id}',     [ApiAppController::class, 'adminUsuariosDestroy']);

            // Planes / tarifas
            Route::get('/planes',               [ApiAppController::class, 'adminPlanesIndex']);
            Route::post('/planes',              [ApiAppController::class, 'adminPlanesStore']);
            Route::put('/planes/{id}',          [ApiAppController::class, 'adminPlanesUpdate']);
            Route::delete('/planes/{id}',       [ApiAppController::class, 'adminPlanesDestroy']);

            // Evaluaciones
            Route::get('/evaluaciones/resumen',          [ApiAppController::class, 'adminEvaluacionesResumen']);
            Route::get('/evaluaciones/mis-evaluaciones', [ApiAppController::class, 'adminMisEvaluaciones']);
        });

        // -- Cliente -------------------------------------------------------
        Route::prefix('cliente')->group(function () {
            Route::get('/portada',                  [ApiAppController::class, 'clientePortada']);
            Route::get('/cuotas',                   [ApiAppController::class, 'clienteCuotas']);
            Route::get('/pesos',                    [ApiAppController::class, 'clientePesos']);
            Route::get('/agenda',                   [ApiAppController::class, 'clienteAgenda']);
            Route::get('/agenda/calendario',        [ApiAppController::class, 'clienteAgendaCalendario']);
            Route::get('/metricas',                 [ApiAppController::class, 'clienteMetricasIndex']);
            Route::post('/metricas',                [ApiAppController::class, 'clienteMetricasStore']);
            Route::get('/ejercicios',               [ApiAppController::class, 'clienteEjerciciosIndex']);
            Route::get('/ejercicios/{idEjercicio}', [ApiAppController::class, 'clienteEjercicioHistorial']);
            Route::get('/perfil/{slug}',             [ApiAppController::class, 'clientePerfilGet']);
            Route::put('/perfil/{slug}',             [ApiAppController::class, 'clientePerfilUpdate']);
            Route::get('/resumen-entrenamientos',    [ApiAppController::class, 'clienteResumenEntrenamientos']);
            Route::get('/evaluacion-inicial',        [ApiAppController::class, 'clienteEvaluacionInicialGet']);
            Route::post('/evaluacion-inicial',       [ApiAppController::class, 'clienteEvaluacionInicialStore']);
            Route::get('/encuesta/entrenador',       [ApiAppController::class, 'clienteEncuestaEntrenadorIndex']);
            Route::post('/encuesta/entrenador',      [ApiAppController::class, 'clienteEncuestaEntrenadorStore']);
            Route::get('/encuesta/gimnasio',         [ApiAppController::class, 'clienteEncuestaGimnasioIndex']);
            Route::post('/encuesta/gimnasio',        [ApiAppController::class, 'clienteEncuestaGimnasioStore']);
        });
    });
});
