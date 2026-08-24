<?php

use App\Http\Controllers\AntropometriaController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EjerciciosController;
use App\Http\Controllers\MensajeController;
use App\Http\Controllers\MovimientosFinancierosController;
use App\Http\Controllers\NotificationsController;
use App\Http\Controllers\OpenGymWebController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\EvaluacionInicialController;
use App\Http\Controllers\GimnasiosController;
use App\Http\Controllers\TermsAndConditionsWebController;
use App\Http\Controllers\SurveyController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('landing');
});

// El hosting de producción enruta absolutamente toda petición a través de Laravel
// (confirmado: ni siquiera un .php suelto en la raíz se sirve directo — el servidor
// web no hace bypass de archivos estáticos como asume Laravel/Vite por defecto). Por
// eso cualquier archivo estático servido normalmente por Apache (CSS/JS compilado,
// imágenes) necesita una ruta explícita en Laravel que lo entregue desde acá.
$serveStaticAsset = function (string $baseDir, string $path) {
    $file = public_path($baseDir . '/' . $path);

    abort_unless(is_file($file) && str_starts_with(realpath($file), realpath(public_path($baseDir))), 404);

    // response()->file() adivina el Content-Type con el fileinfo de PHP, que en este
    // servidor devuelve "text/plain" para .css — el navegador descarga el archivo bien
    // pero se niega a aplicarlo como hoja de estilos (así se manifestó: la página cargaba
    // sin ningún estilo de Tailwind). Se fuerza el tipo a mano según la extensión.
    $mimeTypes = [
        'css' => 'text/css; charset=UTF-8',
        'js' => 'text/javascript; charset=UTF-8',
        'json' => 'application/json',
        'map' => 'application/json',
        'svg' => 'image/svg+xml',
        'woff' => 'font/woff',
        'woff2' => 'font/woff2',
        'ttf' => 'font/ttf',
        'png' => 'image/png',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
    ];
    $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));

    return response()->file($file, [
        'Content-Type' => $mimeTypes[$extension] ?? 'application/octet-stream',
        'Cache-Control' => 'public, max-age=31536000, immutable',
    ]);
};

Route::get('/build/{path}', fn (string $path) => $serveStaticAsset('build', $path))
    ->where('path', '.*')->name('build.asset');

// Capturas reales de la app para la landing page (public/screenshots/, NO dentro de
// public/build/ a propósito: Vite borra el contenido de build/ en cada compilación).
Route::get('/screenshots/{path}', fn (string $path) => $serveStaticAsset('screenshots', $path))
    ->where('path', '.*')->name('screenshots.asset');

// Página intermedia para el enlace de recuperación de clave enviado por correo
// a la app móvil: los clientes de correo (Gmail, etc.) eliminan los enlaces con
// esquemas personalizados (gym.ampaya.cl://...) de los botones, así que el
// correo enlaza a esta vista https, que redirige al esquema de la app.
Route::get('/app/reset-password', function (\Illuminate\Http\Request $request) {
    return view('auth.reset-password-app', [
        'token' => (string) $request->query('token', ''),
        'email' => (string) $request->query('email', ''),
        'scheme' => env('APP_MOBILE_URL_SCHEME', 'gym.ampaya.cl'),
    ]);
})->name('app.reset-password.redirect');

// Route::get('/dashboard', function () {
//     return view('dashboard');
// })->middleware(['auth', 'verified'])->name('dashboard');



Route::middleware(['auth'])->group(function () {

    Route::get('/notificaciones', [NotificationsController::class, 'index'])->name('notifications.index');
    Route::post('/notificaciones/leer-todas', [NotificationsController::class, 'markAllAsRead'])->name('notifications.read-all');
    Route::post('/notificaciones/{notificationId}/leer', [NotificationsController::class, 'markAsRead'])->name('notifications.read');

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/api/dashboard-data', [DashboardController::class, 'getDashboardData']);
    // Route::get('/dashboard', function () {
    //     return redirect()->route('portada');
    // });
    Route::get('/portada', [DashboardController::class, 'portada'])->name('portada');
    Route::get('/panel', [DashboardController::class, 'panel'])->name('panel');
    /* planes */
    Route::get('/planes', [\App\Http\Controllers\PlanesController::class, 'index'])->name('planes.index');
    Route::get('/planes/create', [\App\Http\Controllers\PlanesController::class, 'create'])->name('planes.create');
    Route::post('/planes', [\App\Http\Controllers\PlanesController::class, 'store'])->name('planes.store');
    Route::get('/planes/{slug}/edit', [\App\Http\Controllers\PlanesController::class, 'edit'])->name('planes.edit');
    Route::put('/planes/{slug}', [\App\Http\Controllers\PlanesController::class, 'update'])->name('planes.update');
    Route::delete('/planes/{slug}', [\App\Http\Controllers\PlanesController::class, 'destroy'])->name('planes.destroy');
    Route::patch('/planes/{slug}/estado', [\App\Http\Controllers\PlanesController::class, 'toggleStatus'])->name('planes.cambiarEstado');
    /* gimnasios */
    Route::get('/gimnasios', [GimnasiosController::class, 'index'])->name('gimnasios.index');
    Route::get('/gimnasios/create', [GimnasiosController::class, 'create'])->name('gimnasios.create');
    Route::post('/gimnasios', [GimnasiosController::class, 'store'])->name('gimnasios.store');
    Route::get('/gimnasios/{slug}/edit', [GimnasiosController::class, 'edit'])->name('gimnasios.edit');
    Route::put('/gimnasios/{slug}', [GimnasiosController::class, 'update'])->name('gimnasios.update');
    Route::delete('/gimnasios/{slug}', [GimnasiosController::class, 'destroy'])->name('gimnasios.destroy');
    Route::patch('/gimnasios/{slug}/estado', [GimnasiosController::class, 'toggleStatus'])->name('gimnasios.cambiarEstado');
    /* terminos y condiciones */
    Route::get('/terminos', [TermsAndConditionsWebController::class, 'index'])->name('terminos.index');
    Route::get('/terminos/create', [TermsAndConditionsWebController::class, 'create'])->name('terminos.create');
    Route::post('/terminos', [TermsAndConditionsWebController::class, 'store'])->name('terminos.store');
    Route::get('/terminos/{id}/edit', [TermsAndConditionsWebController::class, 'edit'])->name('terminos.edit');
    Route::put('/terminos/{id}', [TermsAndConditionsWebController::class, 'update'])->name('terminos.update');
    Route::delete('/terminos/{id}', [TermsAndConditionsWebController::class, 'destroy'])->name('terminos.destroy');
    /* usuarios */
    Route::get('/usuarios', [\App\Http\Controllers\UsuariosController::class, 'index'])->name('usuarios.index');
    Route::get('/usuarios/create', [\App\Http\Controllers\UsuariosController::class, 'create'])->name('usuarios.create');
    Route::post('/usuarios', [\App\Http\Controllers\UsuariosController::class, 'store'])->name('usuarios.store');
    Route::get('/usuarios/{id}/edit', [\App\Http\Controllers\UsuariosController::class, 'edit'])->name('usuarios.edit');
    Route::put('/usuarios/{id}', [\App\Http\Controllers\UsuariosController::class, 'update'])->name('usuarios.update');
    Route::delete('/usuarios/{id}', [\App\Http\Controllers\UsuariosController::class, 'destroy'])->name('usuarios.destroy');
    Route::get('/usuarios/{id}/cambiar-estado', [\App\Http\Controllers\UsuariosController::class, 'toggleStatus'])->name('usuarios.cambiarEstado');
    // clientes
    Route::get('/clientes/portada', [\App\Http\Controllers\ClientesController::class, 'portada'])->name('clientes.portada');
    Route::get('/clientes/opciones/portada/{slug}', [\App\Http\Controllers\ClientesController::class, 'portada_opciones'])->name('clientes.opciones.portada');
    Route::get('/clientes', [\App\Http\Controllers\ClientesController::class, 'index'])->name('clientes.index');
    Route::get('/clientes/morosos', [\App\Http\Controllers\ClientesController::class, 'morosos'])->name('clientes.morosos');
    Route::get('/clientes/correo_acceso', [\App\Http\Controllers\ClientesController::class, 'enviarCorreoAcceso'])->name('clientes.enviarCorreoAcceso');
    Route::get('/clientes/traeDupla/{id}', [\App\Http\Controllers\ClientesController::class, 'traeDupla'])->name('clientes.traeDupla');
    Route::get('/clientes/create', [\App\Http\Controllers\ClientesController::class, 'create'])->name('clientes.create');
    Route::post('/clientes', [\App\Http\Controllers\ClientesController::class, 'store'])->name('clientes.store');
    Route::get('/clientes/importar', [\App\Http\Controllers\ClientesImportController::class, 'create'])->name('clientes.importar');
    Route::get('/clientes/importar/plantilla', [\App\Http\Controllers\ClientesImportController::class, 'plantilla'])->name('clientes.importar.plantilla');
    Route::post('/clientes/importar/previsualizar', [\App\Http\Controllers\ClientesImportController::class, 'previsualizar'])->name('clientes.importar.previsualizar');
    Route::post('/clientes/importar', [\App\Http\Controllers\ClientesImportController::class, 'store'])->name('clientes.importar.store');
    Route::get('/clientes/{slug}/edit', [\App\Http\Controllers\ClientesController::class, 'edit'])->name('clientes.edit');
    Route::put('/clientes/{slug}', [\App\Http\Controllers\ClientesController::class, 'update'])->name('clientes.update');
    Route::delete('/clientes/{slug}', [\App\Http\Controllers\ClientesController::class, 'destroy'])->name('clientes.destroy');
    Route::get('/clientes/{slug}/cambiar-estado', [\App\Http\Controllers\ClientesController::class, 'toggleStatus'])->name('clientes.cambiarEstado');
    Route::get('/clientes/{slug}', [\App\Http\Controllers\ClientesController::class, 'show'])->name('clientes.show');
    Route::get('/clientes/{slug}/cuenta-corriente', [\App\Http\Controllers\ClientesController::class, 'cuentaCorriente'])->name('clientes.cuenta_corriente');
    Route::get('/clientes/{slug}/pagar', [\App\Http\Controllers\ClientesController::class, 'pagar'])->name('clientes.cuenta_corriente.pagar');
    Route::post('/clientes/{slug}/pagar', [\App\Http\Controllers\ClientesController::class, 'procesarPago'])->name('clientes.cuenta_corriente.pagar.store');
    Route::get('/clientes/{slug}/cuotas/create', [\App\Http\Controllers\ClientesController::class, 'createCuota'])->name('clientes.cuotas.create');
    Route::post('/clientes/{slug}/cuotas', [\App\Http\Controllers\ClientesController::class, 'storeCuota'])->name('clientes.cuotas.store');
    Route::post('/clientes/{slug}/cuenta-corriente/pagar-multiple', [\App\Http\Controllers\ClientesController::class, 'pagarMultiple'])->name('clientes.cuenta_corriente.pagar_multiple');
    Route::delete('/clientes/{slug}/cuenta-corriente/cuotas/{id}', [\App\Http\Controllers\ClientesController::class, 'destroyCuota'])->name('clientes.cuenta_corriente.eliminar');
    Route::get('/clientes/recordatorio/{slug}', [\App\Http\Controllers\ClientesController::class, 'ejecutarRecordatorio'])->name('clientes.recordatorio');
    Route::get('/clientes/{slug}/evolucion', [\App\Http\Controllers\ClientesController::class, 'evolucionCliente'])->name('clientes.evolucion');
    Route::get('/clientes/{slug}/evolucion_tabla', [\App\Http\Controllers\ClientesController::class, 'evolucionClienteTabla'])->name('clientes.evolucion_tabla');
    Route::get('/clientes/{slug}/evolucion-filtrada', [\App\Http\Controllers\ClientesController::class, 'evolucionClienteFiltrada'])->name('clientes.evolucion_filtrada');
    Route::get('/clientes/{slug}/evolucion-ejercicios', [\App\Http\Controllers\ClientesController::class, 'evolucionEjercicios'])->name('clientes.evolucion_ejercicios');
    Route::get('/clientes/{slug}/evolucion-ejercicios/pdf', [\App\Http\Controllers\ClientesController::class, 'evolucionEjerciciosPdf'])->name('clientes.evolucion_ejercicios.pdf');
    //Route::delete('/clientes/cuotas/{id}', [\App\Http\Controllers\ClientesController::class, 'destroyCuota'])->name('clientes.cuenta_corriente.eliminar');
    //pesos
    Route::get('/clientes/{slug}/pesos', [\App\Http\Controllers\ClientesController::class, 'pesos'])->name('clientes.pesos');
    Route::get('/clientes/{slug}/pesos/create', [\App\Http\Controllers\ClientesController::class, 'createPeso'])->name('clientes.pesos.create');
    Route::post('/clientes/{slug}/pesos', [\App\Http\Controllers\ClientesController::class, 'storePeso'])->name('clientes.pesos.store');
    Route::get('/clientes/{slug}/pesos/{id}/edit', [\App\Http\Controllers\ClientesController::class, 'editPeso'])->name('clientes.pesos.edit');
    Route::put('/clientes/{slug}/pesos/{id}', [\App\Http\Controllers\ClientesController::class, 'updatePeso'])->name('clientes.pesos.update');
    Route::delete('/clientes/{slug}/pesos/{id}', [\App\Http\Controllers\ClientesController::class, 'destroyPeso'])->name('clientes.pesos.destroy');


    // Perímetros
    Route::get('/clientes/{slug}/perimetros', [\App\Http\Controllers\PerimetroController::class, 'index'])->name('clientes.perimetros');

    Route::post('clientes/{slug}/perimetros', [\App\Http\Controllers\PerimetroController::class, 'storePerimetro'])->name('clientes.perimetros.store');
    Route::delete('perimetros/{id}', [\App\Http\Controllers\ClientesController::class, 'destroyPerimetro'])->name('perimetros.destroy');
    //imcs
    Route::get('/clientes/{slug}/imcs', [\App\Http\Controllers\ClientesController::class, 'imcs'])->name('clientes.imcs');
    Route::get('/clientes/{slug}/imcs/create', [\App\Http\Controllers\ClientesController::class, 'createImc'])->name('clientes.imcs.create');
    Route::post('/clientes/{slug}/imcs', [\App\Http\Controllers\ClientesController::class, 'storeImc'])->name('clientes.imcs.store');
    Route::get('/clientes/{slug}/imcs/{id}/edit', [\App\Http\Controllers\ClientesController::class, 'editImc'])->name('clientes.imcs.edit');
    Route::put('/clientes/{slug}/imcs/{id}', [\App\Http\Controllers\ClientesController::class, 'updateImc'])->name('clientes.imcs.update');
    Route::delete('/clientes/{slug}/imcs/{id}', [\App\Http\Controllers\ClientesController::class, 'destroyImc'])->name('clientes.imcs.destroy');

    //aguas
    Route::get('/clientes/{slug}/aguas', [\App\Http\Controllers\ClientesController::class, 'aguas'])->name('clientes.aguas');
    Route::get('/clientes/{slug}/aguas/create', [\App\Http\Controllers\ClientesController::class, 'createAgua'])->name('clientes.aguas.create');
    Route::post('/clientes/{slug}/aguas', [\App\Http\Controllers\ClientesController::class, 'storeAgua'])->name('clientes.aguas.store');
    Route::get('/clientes/{slug}/aguas/{id}/edit', [\App\Http\Controllers\ClientesController::class, 'editAgua'])->name('clientes.aguas.edit');
    Route::put('/clientes/{slug}/aguas/{id}', [\App\Http\Controllers\ClientesController::class, 'updateAgua'])->name('clientes.aguas.update');
    Route::delete('/clientes/{slug}/aguas/{id}', [\App\Http\Controllers\ClientesController::class, 'destroyAgua'])->name('clientes.aguas.destroy');

    // grasas
    Route::get('/clientes/{slug}/grasas', [\App\Http\Controllers\ClientesController::class, 'grasas'])->name('clientes.grasas');
    Route::get('/clientes/{slug}/grasas/create', [\App\Http\Controllers\ClientesController::class, 'createGrasa'])->name('clientes.grasas.create');
    Route::post('/clientes/{slug}/grasas', [\App\Http\Controllers\ClientesController::class, 'storeGrasa'])->name('clientes.grasas.store');
    Route::get('/clientes/{slug}/grasas/{id}/edit', [\App\Http\Controllers\ClientesController::class, 'editGrasa'])->name('clientes.grasas.edit');
    Route::put('/clientes/{slug}/grasas/{id}', [\App\Http\Controllers\ClientesController::class, 'updateGrasa'])->name('clientes.grasas.update');
    Route::delete('/clientes/{slug}/grasas/{id}', [\App\Http\Controllers\ClientesController::class, 'destroyGrasa'])->name('clientes.grasas.destroy');

    // porcentaje masas muscular
    Route::get('/clientes/{slug}/pmusculares', [\App\Http\Controllers\ClientesController::class, 'pmusculares'])->name('clientes.pmusculares');
    Route::get('/clientes/{slug}/pmusculares/create', [\App\Http\Controllers\ClientesController::class, 'createPmuscular'])->name('clientes.pmusculares.create');
    Route::post('/clientes/{slug}/pmusculares', [\App\Http\Controllers\ClientesController::class, 'storePmuscular'])->name('clientes.pmusculares.store');
    Route::get('/clientes/{slug}/pmusculares/{id}/edit', [\App\Http\Controllers\ClientesController::class, 'editPmuscular'])->name('clientes.pmusculares.edit');
    Route::put('/clientes/{slug}/pmusculares/{id}', [\App\Http\Controllers\ClientesController::class, 'updatePmuscular'])->name('clientes.pmusculares.update');
    Route::delete('/clientes/{slug}/pmusculares/{id}', [\App\Http\Controllers\ClientesController::class, 'destroyPmuscular'])->name('clientes.pmusculares.destroy');

    //porcentaje masa osea
    Route::get('/clientes/{slug}/poseas', [\App\Http\Controllers\ClientesController::class, 'poseas'])->name('clientes.poseas');
    Route::get('/clientes/{slug}/poseas/create', [\App\Http\Controllers\ClientesController::class, 'createPosea'])->name('clientes.poseas.create');
    Route::post('/clientes/{slug}/poseas', [\App\Http\Controllers\ClientesController::class, 'storePosea'])->name('clientes.poseas.store');
    Route::get('/clientes/{slug}/poseas/{id}/edit', [\App\Http\Controllers\ClientesController::class, 'editPosea'])->name('clientes.poseas.edit');
    Route::put('/clientes/{slug}/poseas/{id}', [\App\Http\Controllers\ClientesController::class, 'updatePosea'])->name('clientes.poseas.update');
    Route::delete('/clientes/{slug}/poseas/{id}', [\App\Http\Controllers\ClientesController::class, 'destroyPosea'])->name('clientes.poseas.destroy');

    //cuenta corriente gym
    Route::get('/movimientos', [MovimientosFinancierosController::class, 'index'])->name('caja.index');
    Route::get('/movimientos/create', [MovimientosFinancierosController::class, 'create'])->name('caja.create');
    Route::post('/movimientos', [MovimientosFinancierosController::class, 'store'])->name('caja.store');
    Route::get('/movimientos/export/excel', [MovimientosFinancierosController::class, 'exportExcel'])->name('caja.export.excel');
    Route::get('/movimientos/export/pdf', [MovimientosFinancierosController::class, 'exportPdf'])->name('caja.export.pdf');

    //parq
    Route::get('/clientes/{slug}/parq', [App\Http\Controllers\ParqController::class, 'create'])->name('parq.create');
    Route::post('/clientes/{slug}/parq', [App\Http\Controllers\ParqController::class, 'store'])->name('parq.store');
    Route::get('/clientes/{slug}/parq/show', [App\Http\Controllers\ParqController::class, 'show'])->name('parq.show');

    // fitplan
    Route::get('/clientes/{slug}/fitplan', [App\Http\Controllers\FitPlanController::class, 'create'])->name('fitplan.create');
    Route::post('/clientes/{slug}/fitplan', [App\Http\Controllers\FitPlanController::class, 'store'])->name('fitplan.store');
    Route::get('/clientes/{slug}/fitplan/edit', [App\Http\Controllers\FitPlanController::class, 'edit'])->name('fitplan.edit');
    Route::put('/clientes/{slug}/fitplan', [App\Http\Controllers\FitPlanController::class, 'update'])->name('fitplan.update');
    Route::delete('/clientes/{slug}/fitplan', [App\Http\Controllers\FitPlanController::class, 'destroy'])->name('fitplan.destroy');
    Route::get('/clientes/{slug}/fitplan/show', [App\Http\Controllers\FitPlanController::class, 'show'])->name('fitplan.show');
    Route::get('/clientes/{slug}/evaluacion-inicial', [EvaluacionInicialController::class, 'showCliente'])->name('clientes.evaluacion_inicial.show');

    //agenda cliente
    Route::get('/clientes/{slug}/agenda', [\App\Http\Controllers\ClientesController::class, 'agenda'])->name('clientes.agenda');

    Route::get('/open-gym', [OpenGymWebController::class, 'index'])->name('open-gym.index');
    Route::get('/open-gym/rutinas/create', [OpenGymWebController::class, 'create'])->name('open-gym.create');
    Route::post('/open-gym/rutinas', [OpenGymWebController::class, 'store'])->name('open-gym.store');
    Route::get('/open-gym/rutinas/{id}/edit', [OpenGymWebController::class, 'edit'])->name('open-gym.edit');
    Route::put('/open-gym/rutinas/{id}', [OpenGymWebController::class, 'update'])->name('open-gym.update');
    Route::post('/open-gym/rutinas/{id}/duplicar', [OpenGymWebController::class, 'duplicate'])->name('open-gym.duplicate');
    Route::delete('/open-gym/rutinas/{id}', [OpenGymWebController::class, 'destroy'])->name('open-gym.destroy');
    Route::get('/open-gym/progreso', [OpenGymWebController::class, 'progress'])->name('open-gym.progress');
    Route::get('/open-gym/historial', [OpenGymWebController::class, 'history'])->name('open-gym.history');
    Route::post('/open-gym/entrenamientos/{routineId}', [OpenGymWebController::class, 'startWorkout'])->name('open-gym.workouts.start');
    Route::get('/open-gym/entrenamientos/{id}/show', [OpenGymWebController::class, 'showWorkout'])->name('open-gym.workouts.show');
    Route::put('/open-gym/entrenamientos/{id}/series/{setId}', [OpenGymWebController::class, 'updateWorkoutSet'])->name('open-gym.workouts.sets.update');
    Route::post('/open-gym/entrenamientos/{id}/finalizar', [OpenGymWebController::class, 'finishWorkout'])->name('open-gym.workouts.finish');

    Route::get('/clientes/{slug}/reporte', [\App\Http\Controllers\ClientesController::class, 'reporte'])->name('clientes.reporte');
    Route::post('/clientes/{slug}/reporte/enviar', [\App\Http\Controllers\ClientesController::class, 'enviarReporte'])->name('clientes.reporte.enviar');
    // agendas
    Route::get('/agendas/portada', [\App\Http\Controllers\AgendasController::class, 'portada'])->name('agendas.portada');
    Route::get('/agendas', [\App\Http\Controllers\AgendasController::class, 'index'])->name('agendas.index');
    Route::get('/agendas/create', [\App\Http\Controllers\AgendasController::class, 'create'])->name('agendas.create');
    Route::post('/agendas', [\App\Http\Controllers\AgendasController::class, 'store'])->name('agendas.store');
    Route::get('/agendas/{slug}/edit', [\App\Http\Controllers\AgendasController::class, 'edit'])->name('agendas.edit');
    Route::put('/agendas/{slug}', [\App\Http\Controllers\AgendasController::class, 'update'])->name('agendas.update');
    Route::delete('/agendas/{slug}', [\App\Http\Controllers\AgendasController::class, 'destroy'])->name('agendas.destroy');
    Route::get('/agendas/{slug}', [\App\Http\Controllers\AgendasController::class, 'show'])->name('agendas.show');
    Route::patch('/agendas/{id}/cambiar-estado', [\App\Http\Controllers\AgendasController::class, 'cambiarEstado']);
    Route::post('/agendas/{slug}/copiar', [\App\Http\Controllers\AgendasController::class, 'copiar'])->name('agendas.copiar');
    Route::get('/clientes/{slug}/agenda-por-mes', [\App\Http\Controllers\AgendasController::class, 'agendaClientePorMes'])->name('agendas.cliente_por_mes');
    // ejercicios
    Route::get('/ejercicios', [\App\Http\Controllers\EjerciciosController::class, 'index'])->name('ejercicios.index');
    Route::get('/ejercicios/create', [\App\Http\Controllers\EjerciciosController::class, 'create'])->name('ejercicios.create');
    Route::post('/ejercicios', [\App\Http\Controllers\EjerciciosController::class, 'store'])->name('ejercicios.store');
    Route::get('/ejercicios/{slug}/edit', [EjerciciosController::class, 'edit'])->name('ejercicios.edit');
    Route::put('/ejercicios/{slug}', [\App\Http\Controllers\EjerciciosController::class, 'update'])->name('ejercicios.update');
    Route::delete('/ejercicios/{slug}', [\App\Http\Controllers\EjerciciosController::class, 'destroy'])->name('ejercicios.destroy');
    Route::get('/ejercicios/{slug}/cambiar-estado', [\App\Http\Controllers\EjerciciosController::class, 'toggleStatus'])->name('ejercicios.cambiarEstado');
    Route::get('/ejercicios/por-tipo/{id}', [\App\Http\Controllers\EjerciciosController::class, 'porTipo'])->name('ejercicios.por_tipo');
    //perfiles
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    //mensajeria
    Route::get('/mensajes', [MensajeController::class, 'index'])->name('mensajes.index');
    Route::get('/mensajes/create', [MensajeController::class, 'create'])->name('mensajes.create');
    Route::post('/mensajes', [MensajeController::class, 'store'])->name('mensajes.store');

    //entrenadores
    Route::get('/entrenadores', [\App\Http\Controllers\EntrenadoresController::class, 'index'])->name('entrenadores.index');
    Route::get('/entrenadores/resumen/{slug}', [\App\Http\Controllers\EntrenadoresController::class, 'resumen_entrenador'])->name('entrenadores.resumen');
    Route::get('/entrenadores/opciones/portada/{slug}', [\App\Http\Controllers\EntrenadoresController::class, 'portada_opciones'])->name('entrenadores.opciones.portada');
    Route::get('/entrenadores/create', [\App\Http\Controllers\EntrenadoresController::class, 'create'])->name('entrenadores.create');
    Route::post('/entrenadores', [\App\Http\Controllers\EntrenadoresController::class, 'store'])->name('entrenadores.store');
    Route::get('/entrenadores/{id}/edit', [\App\Http\Controllers\EntrenadoresController::class, 'edit'])->name('entrenadores.edit');
    Route::put('/entrenadores/{id}', [\App\Http\Controllers\EntrenadoresController::class, 'update'])->name('entrenadores.update');
    Route::delete('/entrenadores/{id}', [\App\Http\Controllers\EntrenadoresController::class, 'destroy'])->name('entrenadores.destroy');
    Route::get('/entrenadores/{id}/cambiar-estado', [\App\Http\Controllers\EntrenadoresController::class, 'toggleStatus'])->name('entrenadores.cambiarEstado');
    Route::get('/entrenadores/{id}', [\App\Http\Controllers\EntrenadoresController::class, 'show'])->name('entrenadores.show');
    Route::get('/entrenadores/{slug}/sesiones-semana', [\App\Http\Controllers\EntrenadoresController::class, 'sesiones_semana'])->name('entrenadores.sesiones_semana');
    Route::get('/entrenadores/{slug}/clientes', [\App\Http\Controllers\EntrenadoresController::class, 'clientes'])->name('entrenadores.clientes');
    Route::get('/entrenadores/{slug}/retencion', [\App\Http\Controllers\EntrenadoresController::class, 'retencion'])->name('entrenadores.retencion');
    Route::get('/entrenadores/{slug}/cursos', [\App\Http\Controllers\EntrenadoresController::class, 'cursos'])->name('entrenadores.cursos');

    Route::get('/pagos-entrenadores', [\App\Http\Controllers\PagoEntrenadorController::class, 'index'])->name('pagos_entrenadores.index');
    Route::post('/pagos-entrenadores', [\App\Http\Controllers\PagoEntrenadorController::class, 'store'])->name('pagos_entrenadores.store');
    Route::delete('/pagos-entrenadores/{id}', [\App\Http\Controllers\PagoEntrenadorController::class, 'destroy'])->name('pagos_entrenadores.destroy');

    //tareas
    Route::get('/tareas', [\App\Http\Controllers\TareasController::class, 'index'])->name('tareas.index');
    Route::get('/tareas/create', [\App\Http\Controllers\TareasController::class, 'create'])->name('tareas.create');
    Route::post('/tareas', [\App\Http\Controllers\TareasController::class, 'store'])->name('tareas.store');
    Route::get('/tareas/{slug}', [\App\Http\Controllers\TareasController::class, 'show'])->name('tareas.show');
    Route::get('/tareas/{slug}/edit', [\App\Http\Controllers\TareasController::class, 'edit'])->name('tareas.edit');
    Route::put('/tareas/{slug}', [\App\Http\Controllers\TareasController::class, 'update'])->name('tareas.update');
    Route::delete('/tareas/{slug}', [\App\Http\Controllers\TareasController::class, 'destroy'])->name('tareas.destroy');
    Route::get('/tareas/{slug}/cambiar-estado', [\App\Http\Controllers\TareasController::class, 'toggleStatus'])->name('tareas.cambiarEstado');
    Route::get('/tareas/{slug}/completar', [\App\Http\Controllers\TareasController::class, 'completar'])->name('tareas.completar');
    Route::post('/tareas/{slug}/completar', [\App\Http\Controllers\TareasController::class, 'procesarCompletar'])->name('tareas.procesarCompletar');
    Route::get('/tareas/{slug}/reabrir', [\App\Http\Controllers\TareasController::class, 'reabrir'])->name('tareas.reabrir');
    Route::post('/tareas/{slug}/reabrir', [\App\Http\Controllers\TareasController::class, 'procesarReabrir'])->name('tareas.procesarReabrir');

    //cursos
    Route::get('/cursos', [\App\Http\Controllers\CursosController::class, 'index'])->name('cursos.index');
    Route::get('/cursos/create', [\App\Http\Controllers\CursosController::class, 'create'])->name('cursos.create');
    Route::post('/cursos', [\App\Http\Controllers\CursosController::class, 'store'])->name('cursos.store');
    Route::get('/cursos/{slug}/edit', [\App\Http\Controllers\CursosController::class, 'edit'])->name('cursos.edit');
    Route::put('/cursos/{slug}', [\App\Http\Controllers\CursosController::class, 'update'])->name('cursos.update');
    Route::delete('/cursos/{slug}', [\App\Http\Controllers\CursosController::class, 'destroy'])->name('cursos.destroy');

    //encuestas de satisfaccion
    Route::get('/encuestas/{entrenador}/index/{origen}', [\App\Http\Controllers\EncuestaSatisfaccionController::class, 'index'])->name('encuestas.index');
    Route::get('/encuestas/{entrenador}/nueva', [\App\Http\Controllers\EncuestaSatisfaccionController::class, 'create'])->name('encuestas.create');
    Route::post('/encuestas/{entrenador}', [\App\Http\Controllers\EncuestaSatisfaccionController::class, 'store'])->name('encuestas.store');
    Route::get('/encuestas/gracias', [\App\Http\Controllers\EncuestaSatisfaccionController::class, 'gracias'])->name('encuestas.gracias');
    Route::get('/encuestas/{encuesta}/show', [\App\Http\Controllers\EncuestaSatisfaccionController::class, 'show'])->name('encuestas.show');

    Route::get('/survey/{slug}', [SurveyController::class, 'show'])->name('survey.show');
    Route::post('/survey', [SurveyController::class, 'store'])->name('survey.store');
    Route::get('/survey/thanks', [SurveyController::class, 'thanks'])->name('survey.thanks');

    //categorizacion
    Route::get('/evaluaciones/{entrenador}/nueva', [\App\Http\Controllers\EvaluacionEntrenadorController::class, 'create'])->name('evaluaciones.create');
    Route::post('/evaluaciones/{entrenador}', [\App\Http\Controllers\EvaluacionEntrenadorController::class, 'store'])->name('evaluaciones.store');
    Route::get('/evaluaciones/{entrenador}/index', [\App\Http\Controllers\EvaluacionEntrenadorController::class, 'index'])->name('evaluaciones.index');
    Route::get('/evaluaciones/{entrenador}/exportar-pdf', [\App\Http\Controllers\EvaluacionEntrenadorController::class, 'exportarPDF'])->name('evaluaciones.exportar_pdf');

    Route::get('/evaluacion-inicial/catalogo', [EvaluacionInicialController::class, 'index'])->name('evaluacion-inicial.catalogo');
    Route::put('/evaluacion-inicial/catalogo/preguntas/{pregunta}', [EvaluacionInicialController::class, 'updatePregunta'])->name('evaluacion-inicial.preguntas.update');
    Route::post('/evaluacion-inicial/catalogo/preguntas/{pregunta}/opciones', [EvaluacionInicialController::class, 'storeOpcion'])->name('evaluacion-inicial.opciones.store');
    Route::put('/evaluacion-inicial/catalogo/opciones/{opcion}', [EvaluacionInicialController::class, 'updateOpcion'])->name('evaluacion-inicial.opciones.update');


    //antropometría
    Route::get('/antropometria', [AntropometriaController::class, 'index']);
    Route::post('/antropometrias/calcular', [AntropometriaController::class, 'calcular']);
});

require __DIR__ . '/auth.php';
