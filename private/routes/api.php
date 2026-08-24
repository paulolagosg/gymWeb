<?php

use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\BeneficiosConveniosController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ClienteController;
use App\Http\Controllers\Api\EntrenadorPerfilController;
use App\Http\Controllers\Api\EjerciciosVideosController;
use App\Http\Controllers\Api\OpenGymController;
use App\Http\Controllers\Api\TermsAndConditionsController;
use App\Http\Controllers\NotificationsController;
use App\Http\Controllers\Api\PlanesAlimentacionController;
use App\Http\Controllers\ApiAppController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes — Ampaya Gym (Mobile)
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
        Route::get('/clientes/id/{id}', [AdminController::class, 'clienteDetalleById'])->where('id', '[0-9]+');
        Route::put('/clientes/id/{id}', [AdminController::class, 'updateClienteById'])->where('id', '[0-9]+');
        Route::post('/clientes/id/{id}', [AdminController::class, 'updateClienteById'])->where('id', '[0-9]+');
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
    Route::post('/auth/forgot-password', [ApiAppController::class, 'authForgotPassword']);
    Route::post('/auth/reset-password', [ApiAppController::class, 'authResetPassword']);

    // Rutas protegidas con Sanctum
    Route::middleware('api.auth')->group(function () {

        // Autenticación
        Route::get('/auth/me',              [ApiAppController::class, 'me']);
        Route::post('/auth/logout',         [ApiAppController::class, 'logout']);
        Route::post('/auth/change-password', [ApiAppController::class, 'changePassword']);
        Route::get('/legal/terms/current', [TermsAndConditionsController::class, 'current']);
        Route::post('/legal/terms/accept', [TermsAndConditionsController::class, 'acceptCurrent']);
        Route::get('/notifications', [NotificationsController::class, 'index']);
        Route::post('/notifications/read-all', [NotificationsController::class, 'markAllAsRead']);
        Route::post('/notifications/{notificationId}/read', [NotificationsController::class, 'markAsRead']);

        // -- Admin / Entrenador --------------------------------------------
        Route::prefix('admin')->group(function () {
            Route::get('/dashboard', [ApiAppController::class, 'adminDashboard']);

            Route::get('/terminos', [TermsAndConditionsController::class, 'adminIndex']);
            Route::get('/terminos/{id}', [TermsAndConditionsController::class, 'adminShow']);
            Route::post('/terminos', [TermsAndConditionsController::class, 'adminStore']);
            Route::put('/terminos/{id}', [TermsAndConditionsController::class, 'adminUpdate']);
            Route::delete('/terminos/{id}', [TermsAndConditionsController::class, 'adminDestroy']);

            // Beneficios y convenios
            Route::middleware('feature:beneficios')->group(function () {
                Route::get('/tiendas-aliadas', [BeneficiosConveniosController::class, 'adminStoresIndex']);
                Route::get('/tiendas-aliadas/{id}', [BeneficiosConveniosController::class, 'adminStoreShow']);
                Route::post('/tiendas-aliadas', [BeneficiosConveniosController::class, 'adminStoreStore']);
                Route::put('/tiendas-aliadas/{id}', [BeneficiosConveniosController::class, 'adminStoreUpdate']);
                Route::delete('/tiendas-aliadas/{id}', [BeneficiosConveniosController::class, 'adminStoreDestroy']);

                Route::get('/stores', [BeneficiosConveniosController::class, 'adminStoresIndex']);
                Route::get('/stores/{id}', [BeneficiosConveniosController::class, 'adminStoreShow']);
                Route::post('/stores', [BeneficiosConveniosController::class, 'adminStoreStore']);
                Route::put('/stores/{id}', [BeneficiosConveniosController::class, 'adminStoreUpdate']);
                Route::delete('/stores/{id}', [BeneficiosConveniosController::class, 'adminStoreDestroy']);

                Route::get('/beneficios', [BeneficiosConveniosController::class, 'adminBenefitsIndex']);
                Route::get('/beneficios/{id}', [BeneficiosConveniosController::class, 'adminBenefitShow']);
                Route::post('/beneficios', [BeneficiosConveniosController::class, 'adminBenefitStore']);
                Route::put('/beneficios/{id}', [BeneficiosConveniosController::class, 'adminBenefitUpdate']);
                Route::put('/beneficios/{id}/estado', [BeneficiosConveniosController::class, 'adminBenefitStatusUpdate']);
                Route::put('/beneficios/{id}/status', [BeneficiosConveniosController::class, 'adminBenefitStatusUpdate']);
                Route::delete('/beneficios/{id}', [BeneficiosConveniosController::class, 'adminBenefitDestroy']);

                Route::get('/benefits', [BeneficiosConveniosController::class, 'adminBenefitsIndex']);
                Route::get('/benefits/{id}', [BeneficiosConveniosController::class, 'adminBenefitShow']);
                Route::post('/benefits', [BeneficiosConveniosController::class, 'adminBenefitStore']);
                Route::put('/benefits/{id}', [BeneficiosConveniosController::class, 'adminBenefitUpdate']);
                Route::put('/benefits/{id}/estado', [BeneficiosConveniosController::class, 'adminBenefitStatusUpdate']);
                Route::put('/benefits/{id}/status', [BeneficiosConveniosController::class, 'adminBenefitStatusUpdate']);
                Route::delete('/benefits/{id}', [BeneficiosConveniosController::class, 'adminBenefitDestroy']);
            });

            // Agenda
            Route::get('/agendas/catalogo',  [ApiAppController::class, 'adminAgendaCatalogo']);
            Route::post('/agendas',          [ApiAppController::class, 'adminAgendaStore']);
            Route::put('/agendas/{id}',      [ApiAppController::class, 'adminAgendaUpdate']);
            Route::put('/agendas/{id}/estado', [ApiAppController::class, 'adminAgendaEstadoUpdate']);
            Route::post('/agendas/cierre-dia', [ApiAppController::class, 'adminAgendaCierreDia']);
            Route::get('/agenda/calendario', [ApiAppController::class, 'adminAgendaCalendario']);

            // Reporte de agendas (feature flag real, no solo de ruta/menú — ver
            // adminReporteAgendas() para por qué no reutiliza /agenda/calendario)
            Route::middleware('feature:reporte_agendas')->group(function () {
                Route::get('/reporte-agendas', [ApiAppController::class, 'adminReporteAgendas']);
            });

            // Ejercicios del sistema
            Route::get('/ejercicios',           [ApiAppController::class, 'adminEjerciciosIndex']);
            Route::post('/ejercicios',          [ApiAppController::class, 'adminEjerciciosStore']);
            Route::put('/ejercicios/{id}',      [ApiAppController::class, 'adminEjerciciosUpdate']);
            Route::delete('/ejercicios/{id}',   [ApiAppController::class, 'adminEjerciciosDestroy']);

            // Clientes CRUD
            Route::get('/clientes',         [ApiAppController::class, 'adminClientesIndex']);
            Route::get('/morosos',          [ApiAppController::class, 'adminMorososIndex']);
            Route::get('/motivos',          [ApiAppController::class, 'adminMotivosIndex']);
            Route::get('/generos',          [ApiAppController::class, 'adminGenerosIndex']);
            Route::get('/tipos-usuarios',   [ApiAppController::class, 'adminTiposUsuariosIndex']);
            Route::get('/entrenadores',     [ApiAppController::class, 'adminEntrenadoresIndex']);
            Route::post('/clientes',        [ApiAppController::class, 'adminClientesStore']);
            Route::put('/clientes/id/{id}', [ApiAppController::class, 'adminClienteUpdateById']);

            // Subpantallas de cliente (por slug)
            Route::get('/clientes/{slug}',                          [ApiAppController::class, 'adminClienteDetalle']);
            Route::put('/clientes/{slug}',                          [ApiAppController::class, 'adminClienteUpdate']);
            Route::get('/clientes/{slug}/pesos',                    [ApiAppController::class, 'adminClientePesos']);
            Route::get('/clientes/{slug}/agenda',                   [ApiAppController::class, 'adminClienteAgenda']);
            Route::get('/clientes/{slug}/entrenamientos',           [ApiAppController::class, 'adminClienteEntrenamientos']);
            Route::get('/clientes/{slug}/cuotas',                   [ApiAppController::class, 'adminClienteCuotas']);
            Route::post('/clientes/{slug}/acceso',                  [ApiAppController::class, 'adminClienteEnviarAcceso']);
            Route::post('/clientes/{slug}/reporte-pdf/enviar',      [ApiAppController::class, 'adminClienteEnviarReportePdf'])->middleware('feature:reporte_pdf');
            Route::post('/clientes/{slug}/recordatorio',            [ApiAppController::class, 'adminClienteEnviarRecordatorio']);
            Route::post('/clientes/{slug}/recordatorio-inactividad', [ApiAppController::class, 'adminClienteEnviarRecordatorioInactividad']);
            Route::post('/clientes/{slug}/cuotas',                  [ApiAppController::class, 'adminClienteCuotasStore']);
            Route::post('/clientes/{slug}/cuotas/pagar',            [ApiAppController::class, 'adminClienteCuotasPagar']);
            Route::post('/clientes/{slug}/cuotas/{idCuota}/pago-parcial', [ApiAppController::class, 'adminClienteCuotaPagoParcial']);
            Route::get('/clientes/{slug}/metricas',                 [ApiAppController::class, 'adminClienteMetricasIndex']);
            Route::post('/clientes/{slug}/metricas',                [ApiAppController::class, 'adminClienteMetricasStore']);
            Route::get('/clientes/{slug}/evaluacion-inicial',       [ApiAppController::class, 'adminClienteEvaluacionInicial']);
            Route::post('/clientes/{slug}/evaluacion-inicial',      [ApiAppController::class, 'adminClienteEvaluacionInicialStore']);
            Route::get('/clientes/{slug}/ejercicios',               [ApiAppController::class, 'adminClienteEjerciciosIndex']);
            Route::get('/clientes/{slug}/ejercicios/{idEjercicio}', [ApiAppController::class, 'adminClienteEjercicioHistorial']);
            Route::middleware('feature:plan_alimentacion')->group(function () {
                Route::get('/clientes/{slug}/planes-alimentacion',      [PlanesAlimentacionController::class, 'adminClienteIndex']);

                // Planes de alimentación
                Route::get('/planes-alimentacion',                      [PlanesAlimentacionController::class, 'adminIndex']);
                Route::post('/planes-alimentacion',                     [PlanesAlimentacionController::class, 'adminStore']);
                Route::get('/planes-alimentacion/{id}',                 [PlanesAlimentacionController::class, 'adminShow']);
                Route::put('/planes-alimentacion/{id}',                 [PlanesAlimentacionController::class, 'adminUpdate']);
                Route::delete('/planes-alimentacion/{id}',              [PlanesAlimentacionController::class, 'adminDestroy']);
                Route::post('/planes-alimentacion/{id}/duplicar',       [PlanesAlimentacionController::class, 'adminDuplicate']);
            });

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

            // Gimnasios
            Route::get('/gimnasios',            [ApiAppController::class, 'adminGimnasiosIndex']);
            Route::post('/gimnasios',           [ApiAppController::class, 'adminGimnasiosStore']);
            Route::put('/gimnasios/{id}',       [ApiAppController::class, 'adminGimnasiosUpdate']);
            Route::delete('/gimnasios/{id}',    [ApiAppController::class, 'adminGimnasiosDestroy']);
            Route::put('/gimnasios/{id}/features', [ApiAppController::class, 'adminGimnasiosFeaturesUpdate']);
            Route::put('/gimnasios/{id}/plan',     [ApiAppController::class, 'adminGimnasiosPlanUpdate']);

            // Composición de planes comerciales (Starter/Estándar/Pro)
            Route::get('/planes-comerciales',                  [ApiAppController::class, 'adminPlanesComercialesIndex']);
            Route::put('/planes-comerciales/{plan}',            [ApiAppController::class, 'adminPlanesComercialesUpdate']);
            Route::post('/planes-comerciales/{plan}/aplicar',   [ApiAppController::class, 'adminPlanesComercialesAplicar']);

            // Pagos a entrenadores
            Route::middleware('feature:pagos_entrenadores')->group(function () {
                Route::get('/pagos-entrenadores',            [ApiAppController::class, 'adminPagosEntrenadoresIndex']);
                Route::post('/pagos-entrenadores',            [ApiAppController::class, 'adminPagosEntrenadoresStore']);
                Route::put('/pagos-entrenadores/{id}',        [ApiAppController::class, 'adminPagosEntrenadoresUpdate']);
                Route::delete('/pagos-entrenadores/{id}',     [ApiAppController::class, 'adminPagosEntrenadoresDestroy']);
            });

            // Evaluaciones
            Route::get('/evaluaciones/resumen',          [ApiAppController::class, 'adminEvaluacionesResumen']);
            Route::get('/evaluaciones/mis-evaluaciones', [ApiAppController::class, 'adminMisEvaluaciones']);

            // Mi perfil del entrenador
            Route::get('/mi-perfil',                        [EntrenadorPerfilController::class, 'show']);
            Route::put('/mi-perfil',                        [EntrenadorPerfilController::class, 'update']);
            Route::post('/mi-perfil/cursos',                [EntrenadorPerfilController::class, 'storeCurso']);
            Route::put('/mi-perfil/cursos/{slug}',          [EntrenadorPerfilController::class, 'updateCurso']);
            Route::delete('/mi-perfil/cursos/{slug}',       [EntrenadorPerfilController::class, 'destroyCurso']);
        });

        // -- Cliente -------------------------------------------------------
        Route::prefix('cliente')->group(function () {
            Route::get('/portada',                  [ApiAppController::class, 'clientePortada']);
            Route::middleware('feature:beneficios')->group(function () {
                Route::get('/beneficios',               [BeneficiosConveniosController::class, 'clienteBenefitsIndex']);
                Route::get('/beneficios/{id}',          [BeneficiosConveniosController::class, 'clienteBenefitShow']);
                Route::get('/benefits',                 [BeneficiosConveniosController::class, 'clienteBenefitsIndex']);
                Route::get('/benefits/{id}',            [BeneficiosConveniosController::class, 'clienteBenefitShow']);
            });
            Route::get('/cuotas',                   [ApiAppController::class, 'clienteCuotas']);
            Route::get('/pesos',                    [ApiAppController::class, 'clientePesos']);
            Route::get('/agenda',                   [ApiAppController::class, 'clienteAgenda']);
            Route::get('/agenda/calendario',        [ApiAppController::class, 'clienteAgendaCalendario']);
            Route::get('/gamificacion',              [ApiAppController::class, 'clienteGamificacion'])->middleware('feature:gamificacion');
            Route::post('/reporte-pdf/enviar',       [ApiAppController::class, 'clienteEnviarReportePdf'])->middleware('feature:reporte_pdf');
            Route::get('/metricas',                 [ApiAppController::class, 'clienteMetricasIndex']);
            Route::post('/metricas',                [ApiAppController::class, 'clienteMetricasStore']);
            Route::get('/ejercicios',               [ApiAppController::class, 'clienteEjerciciosIndex']);
            Route::get('/ejercicios/{idEjercicio}', [ApiAppController::class, 'clienteEjercicioHistorial']);
            Route::get('/videos',                   [EjerciciosVideosController::class, 'clienteIndex'])->middleware('feature:biblioteca_videos');
            Route::middleware('feature:plan_alimentacion')->group(function () {
                Route::get('/plan-alimentacion',        [PlanesAlimentacionController::class, 'clienteActivo']);
                Route::put('/plan-alimentacion/{id}/registro', [PlanesAlimentacionController::class, 'clienteRegistrar']);
            });
            Route::get('/perfil/{slug}',             [ApiAppController::class, 'clientePerfilGet']);
            Route::put('/perfil/{slug}',             [ApiAppController::class, 'clientePerfilUpdate']);
            Route::get('/resumen-entrenamientos',    [ApiAppController::class, 'clienteResumenEntrenamientos']);
            Route::get('/evaluacion-inicial',        [ApiAppController::class, 'clienteEvaluacionInicialGet']);
            Route::post('/evaluacion-inicial',       [ApiAppController::class, 'clienteEvaluacionInicialStore']);
            Route::get('/encuesta/entrenador',       [ApiAppController::class, 'clienteEncuestaEntrenadorIndex']);
            Route::post('/encuesta/entrenador',      [ApiAppController::class, 'clienteEncuestaEntrenadorStore'])->middleware('feature:encuestas');
            Route::get('/encuesta/gimnasio',         [ApiAppController::class, 'clienteEncuestaGimnasioIndex']);
            Route::post('/encuesta/gimnasio',        [ApiAppController::class, 'clienteEncuestaGimnasioStore'])->middleware('feature:encuestas');

            Route::get('/open-gym/rutinas',                    [OpenGymController::class, 'routinesIndex']);
            Route::get('/open-gym/catalogo-ejercicios',        [OpenGymController::class, 'catalogExercises']);
            Route::get('/open-gym/rutinas/{id}',               [OpenGymController::class, 'routineShow']);
            Route::post('/open-gym/rutinas',                   [OpenGymController::class, 'routineStore']);
            Route::put('/open-gym/rutinas/{id}',               [OpenGymController::class, 'routineUpdate']);
            Route::post('/open-gym/rutinas/{id}/duplicar',     [OpenGymController::class, 'routineDuplicate']);
            Route::delete('/open-gym/rutinas/{id}',            [OpenGymController::class, 'routineDestroy']);
            Route::get('/open-gym/entrenamientos/activo',      [OpenGymController::class, 'activeWorkout']);
            Route::post('/open-gym/entrenamientos',            [OpenGymController::class, 'workoutStart']);
            Route::get('/open-gym/entrenamientos/{id}',        [OpenGymController::class, 'workoutShow']);
            Route::put('/open-gym/entrenamientos/{id}/series/{setId}', [OpenGymController::class, 'workoutSetUpdate']);
            Route::post('/open-gym/entrenamientos/{id}/finalizar', [OpenGymController::class, 'workoutFinish']);
            Route::get('/open-gym/progreso',                   [OpenGymController::class, 'progress']);
            Route::get('/open-gym/historial',                  [OpenGymController::class, 'history']);
        });
    });
});
