<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ClienteController;
use App\Mail\RecordatorioCuotasVencidas;
use App\Mail\RecordatorioInactividad;
use App\Models\Clientes;
use App\Models\EvaluacionInicial;
use App\Models\EvaluacionInicialOpcion;
use App\Models\EvaluacionInicialPregunta;
use App\Models\EvaluacionInicialRespuesta;
use App\Models\EstadosPagos;
use App\Models\EvaluacionInicialSeccion;
use App\Models\GimnasioFacturacion;
use App\Models\Gimnasios;
use App\Models\PlanPreset;
use App\Models\User;
use App\Services\Clientes\ClienteLifecycleService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * ApiAppController — Punto de entrada único para la app móvil React Native.
 *
 * No hace proxy HTTP (sin loops con localhost:9191).
 * Delega a los controladores existentes donde sea posible,
 * e implementa directamente los endpoints faltantes.
 *
 * Credenciales de servicio configurables en .env:
 *   APP_NODE_API_EMAIL    → paulo@dominio.com
 *   APP_NODE_API_PASSWORD → Admin1234!
 */
class ApiAppController extends Controller
{
    // ===================================================================
    // ROLE GUARDS
    // ===================================================================

    private function requireAdminOrEntrenador(Request $request): ?JsonResponse
    {
        $user = $request->user();
        if (! $user || ! in_array((int) $user->id_tipo_usuario, [1, 2, 10], true)) {
            return response()->json(['message' => 'No autorizado.'], 403);
        }
        return null;
    }

    private function requireAdminEntrenadorOrRecepcionista(Request $request): ?JsonResponse
    {
        $user = $request->user();
        if (! $user || ! in_array((int) $user->id_tipo_usuario, [1, 2, 3, 10], true)) {
            return response()->json(['message' => 'No autorizado.'], 403);
        }
        return null;
    }

    private function requireAdmin(Request $request): ?JsonResponse
    {
        $user = $request->user();
        if (! $user || ! in_array((int) $user->id_tipo_usuario, [1, 10], true)) {
            return response()->json(['message' => 'No autorizado.'], 403);
        }
        return null;
    }

    private function getScopedClienteIdsForUser(object $user): array
    {
        if ((int) $user->id_tipo_usuario !== 2) {
            return [];
        }

        $clientesAsignados = DB::table('clientes')
            ->where('clientes.id_usuario', $user->id)
            ->pluck('id');

        $clientesEnAgenda = DB::table('agendas')
            ->where('agendas.id_usuario', $user->id)
            ->distinct()
            ->pluck('id_cliente');

        return $clientesAsignados
            ->merge($clientesEnAgenda)
            ->filter(fn($id) => $id !== null)
            ->map(fn($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    private function applyClienteScopeToQuery($query, object $user)
    {
        if ((int) $user->id_tipo_usuario !== 2) {
            return $query;
        }

        $clienteIds = $this->getScopedClienteIdsForUser($user);

        if (empty($clienteIds)) {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereIn('clientes.id', $clienteIds);
    }

    private function findScopedCliente(Request $request, string $identifier): ?object
    {
        $identifier = trim($identifier);

        if ($identifier === '' || in_array($identifier, ['undefined', 'null'], true)) {
            return null;
        }

        $query = DB::table('clientes');
        $this->applyClienteScopeToQuery($query, $request->user());

        $query->where(function ($q) use ($identifier) {
            $q->where('clientes.slug', $identifier);

            if (ctype_digit($identifier)) {
                $q->orWhere('clientes.id', (int) $identifier);
            }
        });

        return $query->first();
    }

    private function findScopedAgenda(Request $request, int $agendaId): ?object
    {
        $query = DB::table('agendas')
            ->where('agendas.id', $agendaId);

        if ((int) $request->user()->id_tipo_usuario === 2) {
            $query->where('agendas.id_usuario', $request->user()->id);
        }

        return $query->first();
    }

    private function syncAgendaEjercicios(int $agendaId, array $ejercicios): void
    {
        DB::table('agendas_ejercicios')
            ->where('id_agenda', $agendaId)
            ->delete();

        $rows = array_map(fn($ejercicio, $indice) => [
            'id_agenda' => $agendaId,
            'id_ejercicio' => $ejercicio['id_ejercicio'],
            'orden' => $indice,
            'serie' => $ejercicio['serie'],
            'repeticiones' => $ejercicio['repeticiones'],
            'carga' => $ejercicio['carga'] ?? null,
            'descanso' => $ejercicio['descanso'] ?? null,
            'created_at' => now(),
            'updated_at' => now(),
        ], $ejercicios, array_keys($ejercicios));

        if (! empty($rows)) {
            DB::table('agendas_ejercicios')->insert($rows);
        }
    }

    // ===================================================================
    // HEALTH
    // ===================================================================

    public function health(): JsonResponse
    {
        return response()->json([
            'status'   => 'ok',
            'app'      => config('app.name'),
            'database' => config('database.connections.mysql.host'),
            'api_mode' => 'laravel-direct',
        ]);
    }

    // ===================================================================
    // AUTH — delega a AuthController
    // ===================================================================

    public function login(Request $request): JsonResponse
    {
        return (new AuthController())->login($request);
    }

    public function authForgotPassword(Request $request): JsonResponse
    {
        return (new AuthController())->forgotPassword($request);
    }

    public function authResetPassword(Request $request): JsonResponse
    {
        return (new AuthController())->resetPassword($request);
    }

    public function me(Request $request): JsonResponse
    {
        return (new AuthController())->me($request);
    }

    public function logout(Request $request): JsonResponse
    {
        return (new AuthController())->logout($request);
    }

    public function changePassword(Request $request): JsonResponse
    {
        return (new AuthController())->changePassword($request);
    }

    public function eliminarCuenta(Request $request): JsonResponse
    {
        return (new AuthController())->eliminarCuenta($request);
    }

    // ===================================================================
    // ADMIN — DASHBOARD (con filtro por entrenador)
    // ===================================================================

    public function adminDashboard(Request $request): JsonResponse
    {
        if ($err = $this->requireAdminOrEntrenador($request)) return $err;

        $user         = $request->user();
        $isEntrenador = (int) $user->id_tipo_usuario === 2;
        $isSuperAdmin = (int) $user->id_tipo_usuario === 10;
        $idGimnasio   = $isSuperAdmin ? (int) $request->query('id_gimnasio', 0) : (int) ($user->id_gimnasio ?? 0);
        $hoy          = now();
        $primerDiaMes = $hoy->copy()->startOfMonth();
        $anio         = (int) $request->query('anio', $hoy->year);
        $meses        = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];

        $clienteIdsEntrenador = [];
        if ($isEntrenador) {
            $clienteIdsEntrenador = $this->getScopedClienteIdsForUser($user);
            if (empty($clienteIdsEntrenador)) {
                return response()->json([
                    'total_clientes' => 0,
                    'activos' => 0,
                    'morosos' => 0,
                    'al_dia' => 0,
                    'ingresos_mes' => 0,
                    'clientes_por_edad' => [],
                    'clientes_por_genero' => [],
                    'clientes_por_plan' => [],
                    'clientes_por_motivo_ingreso' => [],
                    'clientes_por_motivo_egreso' => [],
                    'clientes_por_entrenador' => [],
                    'horarios_peak' => [],
                    'pagos_por_forma'   => ['months' => $meses, 'series' => []],
                    'clientes_alerta'   => [],
                    'agenda_hoy_count'  => 0,
                    'rating_evaluaciones' => null,
                ]);
            }
        }

        $clientesBase = DB::table('clientes');
        if ($idGimnasio > 0 && !$isEntrenador) $clientesBase->where('id_gimnasio', $idGimnasio);
        if ($isEntrenador) $clientesBase->whereIn('id', $clienteIdsEntrenador);

        $totalClientes = (clone $clientesBase)->count();
        $totalActivos  = (clone $clientesBase)->where('estado', 1)->count();

        $morosaQ = DB::table('cuentas_corrientes')
            ->join('clientes', 'cuentas_corrientes.id_cliente', '=', 'clientes.id')
            ->whereNull('cuentas_corrientes.fecha_pago')
            ->whereDate('cuentas_corrientes.fecha_vencimiento', '<', $hoy->toDateString())
            ->where('clientes.estado', 1);
        if ($idGimnasio > 0 && !$isEntrenador) $morosaQ->where('clientes.id_gimnasio', $idGimnasio);
        if ($isEntrenador) $morosaQ->whereIn('cuentas_corrientes.id_cliente', $clienteIdsEntrenador);
        $morosos = $morosaQ->distinct('cuentas_corrientes.id_cliente')->count('cuentas_corrientes.id_cliente');

        $ingresosMes = 0.0;
        if (! $isEntrenador) {
            $ingresosMesQuery = DB::table('cuentas_corrientes')
                ->join('clientes', 'cuentas_corrientes.id_cliente', '=', 'clientes.id')
                ->whereNotNull('cuentas_corrientes.fecha_pago')
                ->whereBetween('cuentas_corrientes.fecha_pago', [$primerDiaMes, $hoy]);

            if ($idGimnasio > 0) {
                $ingresosMesQuery->where('clientes.id_gimnasio', $idGimnasio);
            }

            $ingresosMes = (float) $ingresosMesQuery->sum('cuentas_corrientes.monto_pagado');

            $this->ensureMovimientosTable();

            $movimientosMesQuery = DB::table('movimientos_financieros')
                ->whereDate('fecha', '>=', $primerDiaMes->toDateString())
                ->whereDate('fecha', '<=', $hoy->toDateString());

            if ($idGimnasio > 0 && Schema::hasColumn('movimientos_financieros', 'id_gimnasio')) {
                $movimientosMesQuery->where('id_gimnasio', $idGimnasio);
            }

            $movimientosMesBalance = (float) ($movimientosMesQuery
                ->selectRaw("SUM(CASE WHEN tipo = 'ingreso' THEN monto WHEN tipo = 'egreso' THEN -monto ELSE 0 END) as balance")
                ->value('balance') ?? 0);

            $ingresosMes += $movimientosMesBalance;
        }

        $actBase = (clone $clientesBase)->where('estado', 1);
        $clientesPorEdad = [
            ['label' => 'Menos de 20', 'value' => (clone $actBase)->whereBetween(DB::raw('TIMESTAMPDIFF(YEAR, fecha_nacimiento, CURDATE())'), [0, 19])->count()],
            ['label' => '20-29',        'value' => (clone $actBase)->whereBetween(DB::raw('TIMESTAMPDIFF(YEAR, fecha_nacimiento, CURDATE())'), [20, 29])->count()],
            ['label' => '30-39',        'value' => (clone $actBase)->whereBetween(DB::raw('TIMESTAMPDIFF(YEAR, fecha_nacimiento, CURDATE())'), [30, 39])->count()],
            ['label' => '40-49',        'value' => (clone $actBase)->whereBetween(DB::raw('TIMESTAMPDIFF(YEAR, fecha_nacimiento, CURDATE())'), [40, 49])->count()],
            ['label' => '50+',          'value' => (clone $actBase)->whereBetween(DB::raw('TIMESTAMPDIFF(YEAR, fecha_nacimiento, CURDATE())'), [50, 150])->count()],
        ];

        $generoQ = DB::table('clientes')->leftJoin('generos', 'clientes.id_genero', '=', 'generos.id')->where('clientes.estado', 1);
        if ($idGimnasio > 0 && !$isEntrenador) $generoQ->where('clientes.id_gimnasio', $idGimnasio);
        if ($isEntrenador) $generoQ->whereIn('clientes.id', $clienteIdsEntrenador);
        $clientesPorGenero = $generoQ
            ->selectRaw("COALESCE(generos.nombre, 'Sin género') as label, COUNT(*) as value")
            ->groupBy('label')->orderByDesc('value')
            ->get()->map(fn($r) => ['label' => $r->label, 'value' => (int) $r->value])->values();

        $planQ = DB::table('clientes')->leftJoin('planes', 'clientes.id_plan', '=', 'planes.id')->where('clientes.estado', 1);
        if ($idGimnasio > 0 && !$isEntrenador) $planQ->where('clientes.id_gimnasio', $idGimnasio);
        if ($isEntrenador) $planQ->whereIn('clientes.id', $clienteIdsEntrenador);
        $clientesPorPlan = $planQ
            ->selectRaw("COALESCE(planes.nombre, 'Sin plan') as label, COUNT(*) as value")
            ->groupBy('label')->orderByDesc('value')
            ->get()->map(fn($r) => ['label' => $r->label, 'value' => (int) $r->value])->values();

        $motivosIngresoQ = DB::table('clientes')
            ->leftJoin('motivos', function ($join) {
                $join->on('clientes.id_motivo_ingreso', '=', 'motivos.id')
                    ->where('motivos.tipo', 1);
            })
            ->whereNotNull('clientes.id_motivo_ingreso');
        if ($idGimnasio > 0 && !$isEntrenador) $motivosIngresoQ->where('clientes.id_gimnasio', $idGimnasio);
        if ($isEntrenador) $motivosIngresoQ->whereIn('clientes.id', $clienteIdsEntrenador);
        $clientesPorMotivoIngreso = $motivosIngresoQ
            ->selectRaw("COALESCE(motivos.nombre, 'Sin motivo') as label, COUNT(*) as value")
            ->groupBy('label')
            ->orderByDesc('value')
            ->get()
            ->map(fn($r) => ['label' => $r->label, 'value' => (int) $r->value])
            ->values();

        $motivosEgresoQ = DB::table('clientes')
            ->leftJoin('motivos', function ($join) {
                $join->on('clientes.id_motivo_egreso', '=', 'motivos.id')
                    ->where('motivos.tipo', 2);
            })
            ->whereNotNull('clientes.id_motivo_egreso');
        if ($idGimnasio > 0 && !$isEntrenador) $motivosEgresoQ->where('clientes.id_gimnasio', $idGimnasio);
        if ($isEntrenador) $motivosEgresoQ->whereIn('clientes.id', $clienteIdsEntrenador);
        $clientesPorMotivoEgreso = $motivosEgresoQ
            ->selectRaw("COALESCE(motivos.nombre, 'Sin motivo') as label, COUNT(*) as value")
            ->groupBy('label')
            ->orderByDesc('value')
            ->get()
            ->map(fn($r) => ['label' => $r->label, 'value' => (int) $r->value])
            ->values();

        $horariosPeakBaseQ = DB::table('agendas')
            ->join('clientes', 'agendas.id_cliente', '=', 'clientes.id')
            ->whereNotNull('agendas.id_cliente')
            ->where('clientes.estado', 1)
            ->whereYear('agendas.fecha_inicio', $hoy->year)
            ->whereMonth('agendas.fecha_inicio', $hoy->month)
            ->selectRaw("WEEKDAY(agendas.fecha_inicio) as day_index, DATE_FORMAT(agendas.fecha_inicio, '%H:00') as hour_label, agendas.id_cliente");
        if ($isEntrenador) {
            $horariosPeakBaseQ->where('agendas.id_usuario', $user->id);
        }
        $horariosPeak = DB::query()
            ->fromSub($horariosPeakBaseQ, 'peak_slots')
            ->select('day_index', 'hour_label')
            ->selectRaw('COUNT(DISTINCT id_cliente) as value')
            ->groupBy('day_index', 'hour_label')
            ->orderBy('day_index')
            ->orderBy('hour_label')
            ->get()
            ->map(fn($r) => [
                'label' => $r->day_index . '|' . $r->hour_label,
                'value' => (int) $r->value,
            ])
            ->values();

        $pagosData = ['months' => $meses, 'series' => []];
        if (! $isEntrenador) {
            $filas = DB::table('cuentas_corrientes')
                ->leftJoin('formas_pagos', 'cuentas_corrientes.id_forma_pago', '=', 'formas_pagos.id')
                ->whereYear('cuentas_corrientes.fecha_pago', $anio)
                ->whereNotNull('cuentas_corrientes.fecha_pago')
                ->selectRaw("COALESCE(formas_pagos.nombre, 'Sin forma de pago') as forma")
                ->selectRaw('MONTH(cuentas_corrientes.fecha_pago) as mes')
                ->selectRaw('SUM(cuentas_corrientes.monto_pagado) as total')
                ->groupBy('forma', 'mes')->orderBy('forma')->orderBy('mes')
                ->get();

            $seriesPorForma = [];
            foreach ($filas as $row) {
                $f = $row->forma;
                if (! isset($seriesPorForma[$f])) $seriesPorForma[$f] = array_fill(0, 12, 0);
                $seriesPorForma[$f][max(0, ((int) $row->mes) - 1)] = (float) $row->total;
            }
            foreach ($seriesPorForma as $forma => $values) {
                $pagosData['series'][] = ['label' => $forma, 'values' => array_values($values)];
            }
        }

        $clientesPorEntrenador = collect();
        if (! $isEntrenador) {
            $entrenadoresQ = DB::table('clientes')
                ->leftJoin('users', 'clientes.id_usuario', '=', 'users.id')
                ->whereNotNull('clientes.id_usuario');

            if ($idGimnasio > 0) {
                $entrenadoresQ->where('clientes.id_gimnasio', $idGimnasio);
            }

            $clientesPorEntrenador = $entrenadoresQ
                ->selectRaw("COALESCE(users.name, CONCAT('Entrenador #', clientes.id_usuario)) as label, COUNT(clientes.id) as value")
                ->groupBy('clientes.id_usuario', 'users.name')
                ->orderByDesc('value')
                ->orderBy('label')
                ->get()
                ->map(fn($r) => ['label' => $r->label, 'value' => (int) $r->value])
                ->values();
        }

        // ── Campos exclusivos para entrenadores ───────────────────────────────────
        $clientesAlerta     = null;
        $agendaHoyCount     = null;
        $ratingEvaluaciones = null;

        if ($isEntrenador) {
            // 1. Agenda de hoy
            $agendaHoyCount = DB::table('agendas')
                ->where('agendas.id_usuario', $user->id)
                ->whereDate('fecha_inicio', $hoy->toDateString())
                ->whereNotNull('id_cliente')
                ->distinct('id_cliente')
                ->count('id_cliente');

            // 2. Rating promedio (valoracion_global 0-10 → /2 = 0-5 estrellas)
            $ratingRaw = DB::table('encuesta_satisfaccions')
                ->where('id_entrenador', $user->id)
                ->whereNotNull('valoracion_global')
                ->avg('valoracion_global');
            $ratingEvaluaciones = $ratingRaw !== null ? round((float) $ratingRaw / 2, 1) : null;

            // 3. Clientes que te necesitan (máx. 10 alertas, deduplicadas por cliente)
            $alertas = [];

            if (! empty($clienteIdsEntrenador)) {
                // 3a. Sin entrenar: última agenda hace 5+ días (o nunca)
                $ultimasSesiones = DB::table('agendas')
                    ->whereIn('id_cliente', $clienteIdsEntrenador)
                    ->whereNotNull('id_cliente')
                    ->select('id_cliente', DB::raw('MAX(DATE(fecha_inicio)) as ultima_fecha'))
                    ->groupBy('id_cliente')
                    ->get()
                    ->keyBy('id_cliente');

                $clientesActivos = DB::table('clientes')
                    ->whereIn('id', $clienteIdsEntrenador)
                    ->where('estado', 1)
                    ->get(['id', 'nombres', 'paterno', 'materno', 'slug']);

                foreach ($clientesActivos as $c) {
                    $sesion      = $ultimasSesiones->get($c->id);
                    $ultimaFecha = $sesion?->ultima_fecha ?? null;
                    $dias        = $ultimaFecha
                        ? (int) $hoy->copy()->startOfDay()->diffInDays(Carbon::parse($ultimaFecha)->startOfDay())
                        : null;

                    if ($dias === null || $dias >= 5) {
                        $alertas[] = [
                            'id'             => (int) $c->id,
                            'nombre_completo' => trim($c->nombres . ' ' . $c->paterno . ($c->materno ? ' ' . $c->materno : '')),
                            'slug'           => $c->slug,
                            'tipo'           => 'sin_entrenar',
                            'dias'           => $dias,
                            'ultima_sesion'  => $ultimaFecha,
                            '_prioridad'     => 1,
                            '_urgencia'      => $dias ?? 9999,
                        ];
                    }
                }

                // 3b. Cuota vencida
                $morososDetalle = DB::table('cuentas_corrientes')
                    ->join('clientes', 'cuentas_corrientes.id_cliente', '=', 'clientes.id')
                    ->whereIn('cuentas_corrientes.id_cliente', $clienteIdsEntrenador)
                    ->whereNull('cuentas_corrientes.fecha_pago')
                    ->whereDate('cuentas_corrientes.fecha_vencimiento', '<', $hoy->toDateString())
                    ->where('clientes.estado', 1)
                    ->select(
                        'clientes.id',
                        DB::raw("TRIM(CONCAT(clientes.nombres, ' ', clientes.paterno, IF(clientes.materno IS NOT NULL AND clientes.materno != '', CONCAT(' ', clientes.materno), ''))) as nombre_completo"),
                        'clientes.slug',
                        DB::raw('SUM(COALESCE(cuentas_corrientes.saldo, cuentas_corrientes.monto_pagar, cuentas_corrientes.monto, 0)) as monto_deuda'),
                        DB::raw('DATEDIFF(CURDATE(), MIN(cuentas_corrientes.fecha_vencimiento)) as dias_vencida'),
                    )
                    ->groupBy('clientes.id', 'clientes.nombres', 'clientes.paterno', 'clientes.materno', 'clientes.slug')
                    ->orderByDesc('dias_vencida')
                    ->limit(5)
                    ->get();

                foreach ($morososDetalle as $m) {
                    $alertas[] = [
                        'id'              => (int) $m->id,
                        'nombre_completo' => $m->nombre_completo,
                        'slug'            => $m->slug,
                        'tipo'            => 'cuota_vencida',
                        'dias'            => (int) $m->dias_vencida,
                        'monto_deuda'     => (float) $m->monto_deuda,
                        '_prioridad'      => 0,
                        '_urgencia'       => (int) $m->dias_vencida,
                    ];
                }

                // 3c. Evaluación inicial pendiente (activos sin evaluación completada)
                $totalPreguntas = DB::table('evaluacion_inicial_preguntas')
                    ->where('estado', true)
                    ->count();

                $evalPendientes = DB::table('clientes')
                    ->leftJoin('evaluaciones_iniciales', 'clientes.id', '=', 'evaluaciones_iniciales.id_cliente')
                    ->whereIn('clientes.id', $clienteIdsEntrenador)
                    ->where('clientes.estado', 1)
                    ->whereNull('evaluaciones_iniciales.completada_en')
                    ->select(
                        'clientes.id',
                        DB::raw("TRIM(CONCAT(clientes.nombres, ' ', clientes.paterno, IF(clientes.materno IS NOT NULL AND clientes.materno != '', CONCAT(' ', clientes.materno), ''))) as nombre_completo"),
                        'clientes.slug',
                        'evaluaciones_iniciales.id as eval_id',
                    )
                    ->limit(5)
                    ->get();

                foreach ($evalPendientes as $ep) {
                    $respondidas = $ep->eval_id
                        ? DB::table('evaluacion_inicial_respuestas')
                        ->where('evaluacion_inicial_id', $ep->eval_id)
                        ->distinct('pregunta_id')
                        ->count('pregunta_id')
                        : 0;

                    $progreso = $totalPreguntas > 0
                        ? (int) round(($respondidas / $totalPreguntas) * 100)
                        : 0;

                    $alertas[] = [
                        'id'                   => (int) $ep->id,
                        'nombre_completo'      => $ep->nombre_completo,
                        'slug'                 => $ep->slug,
                        'tipo'                 => 'evaluacion_pendiente',
                        'progreso_evaluacion'  => $progreso,
                        '_prioridad'           => 2,
                        '_urgencia'            => $progreso,
                    ];
                }
            }

            // Ordenar por prioridad (cuota_vencida > sin_entrenar > evaluacion_pendiente),
            // luego por urgencia descendente. Deduplicar por id de cliente (primer tipo ganador).
            usort($alertas, function ($a, $b) {
                if ($a['_prioridad'] !== $b['_prioridad']) {
                    return $a['_prioridad'] <=> $b['_prioridad'];
                }
                return $b['_urgencia'] <=> $a['_urgencia'];
            });

            $seen    = [];
            $deduped = [];
            foreach ($alertas as $alerta) {
                if (! isset($seen[$alerta['id']])) {
                    $seen[$alerta['id']] = true;
                    unset($alerta['_prioridad'], $alerta['_urgencia']);
                    $deduped[] = $alerta;
                }
            }

            $clientesAlerta = array_slice($deduped, 0, 10);
        }

        return response()->json([
            'total_clientes'      => $totalClientes,
            'activos'             => $totalActivos,
            'morosos'             => max(0, $morosos),
            'al_dia'              => max(0, $totalActivos - $morosos),
            'ingresos_mes'        => $ingresosMes,
            'clientes_por_edad'   => $clientesPorEdad,
            'clientes_por_genero' => $clientesPorGenero,
            'clientes_por_plan'   => $clientesPorPlan,
            'clientes_por_motivo_ingreso' => $clientesPorMotivoIngreso,
            'clientes_por_motivo_egreso' => $clientesPorMotivoEgreso,
            'clientes_por_entrenador' => $clientesPorEntrenador,
            'horarios_peak'       => $horariosPeak,
            'pagos_por_forma'     => $pagosData,
            'clientes_alerta'     => $clientesAlerta,
            'agenda_hoy_count'    => $agendaHoyCount,
            'rating_evaluaciones' => $ratingEvaluaciones,
        ]);
    }

    // ===================================================================
    // ADMIN — AGENDA
    // ===================================================================

    public function adminAgendaCatalogo(Request $request): JsonResponse
    {
        if ($err = $this->requireAdminOrEntrenador($request)) return $err;

        $user         = $request->user();
        $isEntrenador = (int) $user->id_tipo_usuario === 2;
        $isSuperAdmin = (int) $user->id_tipo_usuario === 10;
        $idGimnasio   = $isSuperAdmin ? (int) $request->query('id_gimnasio', 0) : (int) ($user->id_gimnasio ?? 0);
        $idUsuario    = (int) $request->query('id_usuario', 0);

        $entrenadoresQ = DB::table('users')
            ->leftJoin('gimnasios', 'users.id_gimnasio', '=', 'gimnasios.id')
            ->where('users.id_tipo_usuario', 2)
            ->orderBy('users.name');
        if ($idGimnasio > 0) $entrenadoresQ->where('users.id_gimnasio', $idGimnasio);

        $entrenadores = $entrenadoresQ
            ->get(['users.id', 'users.name', 'users.titulo', 'users.id_gimnasio', 'gimnasios.nombre as gimnasio'])
            ->map(function ($r) {
                $gymSuffix = $r->gimnasio && ! str_contains(Str::lower($r->name), Str::lower($r->gimnasio))
                    ? ' - ' . $r->gimnasio
                    : '';

                return [
                    'id' => (int) $r->id,
                    'label' => trim($r->name . $gymSuffix . ($r->titulo ? ' · ' . $r->titulo : '')),
                    'id_gimnasio' => $r->id_gimnasio ? (int) $r->id_gimnasio : null,
                    'gimnasio' => $r->gimnasio,
                ];
            })
            ->values();

        $clientesQ = DB::table('clientes')
            ->leftJoin('gimnasios', 'clientes.id_gimnasio', '=', 'gimnasios.id')
            ->where('clientes.estado', 1)
            ->orderBy('clientes.nombres');
        if ($idGimnasio > 0 && !$isEntrenador) $clientesQ->where('clientes.id_gimnasio', $idGimnasio);
        if ($idUsuario > 0) $clientesQ->where('clientes.id_usuario', $idUsuario);
        if ($isEntrenador) {
            $ids = $this->getScopedClienteIdsForUser($user);
            if (empty($ids)) {
                $clientesQ->whereRaw('1 = 0');
            } else {
                $clientesQ->whereIn('clientes.id', $ids);
            }
        }
        $clientes = $clientesQ->get(['clientes.id', 'clientes.slug', 'clientes.nombres', 'clientes.paterno', 'clientes.materno', 'clientes.id_gimnasio', 'clientes.id_usuario', 'gimnasios.nombre as gimnasio'])
            ->map(fn($r) => [
                'id'    => (int) $r->id,
                'slug'  => $r->slug,
                'label' => trim($r->nombres . ' ' . $r->paterno . ' ' . ($r->materno ?? '')),
                'id_gimnasio' => $r->id_gimnasio ? (int) $r->id_gimnasio : null,
                'id_usuario' => $r->id_usuario ? (int) $r->id_usuario : null,
                'gimnasio' => $r->gimnasio,
            ])->values();

        $tiposEjercicio = DB::table('tipos_ejercicios')->orderBy('nombre')->get(['id', 'nombre'])
            ->map(fn($r) => ['id' => (int) $r->id, 'label' => $r->nombre])->values();

        $ejercicios = DB::table('ejercicios')
            ->where('estado', 1)->orderBy('nombre')
            ->get(['id', 'nombre', 'id_tipo'])
            ->map(fn($r) => ['id' => (int) $r->id, 'label' => $r->nombre, 'id_tipo' => $r->id_tipo])
            ->values();

        return response()->json([
            'entrenadores'    => $entrenadores,
            'clientes'        => $clientes,
            'ejercicios'      => $ejercicios,
            'tipos_ejercicio' => $tiposEjercicio,
            'requester_id'    => $isEntrenador ? $user->id : null,
        ]);
    }

    public function adminAgendaStore(Request $request): JsonResponse
    {
        if ($err = $this->requireAdminOrEntrenador($request)) return $err;
        return (new AdminController())->storeAgenda($request);
    }

    public function adminAgendaUpdate(Request $request, int $id): JsonResponse
    {
        if ($err = $this->requireAdminOrEntrenador($request)) return $err;

        $agenda = $this->findScopedAgenda($request, $id);
        if (! $agenda) {
            return response()->json(['message' => 'Entrenamiento no encontrado.'], 404);
        }

        if ((int) $agenda->estado === 4) {
            return response()->json([
                'message' => 'No se puede modificar un entrenamiento realizado.',
            ], 422);
        }

        $validated = $request->validate([
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date|after:fecha_inicio',
            'titulo' => 'nullable|string|max:255',
            'descripcion' => 'nullable|string',
            'modificar_futuros' => 'nullable|boolean',
            'ejercicios' => 'required|array|min:1',
            'ejercicios.*.id_ejercicio' => 'required|integer|exists:ejercicios,id',
            'ejercicios.*.serie' => 'required|integer|min:1|max:100',
            'ejercicios.*.repeticiones' => 'required|string|max:50',
            'ejercicios.*.carga' => 'nullable|string|max:50',
            'ejercicios.*.descanso' => 'nullable|string|max:50',
        ]);

        $fechaInicio = Carbon::parse($validated['fecha_inicio'], config('app.timezone'));
        $fechaFin = Carbon::parse($validated['fecha_fin'], config('app.timezone'));
        $modificarFuturos = (bool) ($validated['modificar_futuros'] ?? false);

        DB::transaction(function () use ($request, $agenda, $validated, $fechaInicio, $fechaFin, $modificarFuturos) {
            if ($modificarFuturos) {
                // Las sesiones "futuras coincidentes" se buscan por el día de semana/hora
                // ORIGINALES de la sesión editada (antes de este cambio), no por la nueva
                // hora que se está guardando: si no, al cambiar la hora nunca se encuentra
                // ninguna coincidencia porque las futuras siguen con la hora vieja.
                $agendaOriginal = Carbon::parse($agenda->fecha_inicio, config('app.timezone'));
                $duracionMinutos = $fechaInicio->diffInMinutes($fechaFin);
                $nuevaHora = $fechaInicio->format('H:i:s');

                $agendasFuturasQuery = DB::table('agendas')
                    ->where('id_cliente', $agenda->id_cliente)
                    ->where('id_gimnasio', $agenda->id_gimnasio)
                    ->where('id', '!=', $agenda->id)
                    ->whereDate('fecha_inicio', '>', $agendaOriginal->format('Y-m-d'))
                    ->whereRaw('DAYOFWEEK(fecha_inicio) = ?', [$agendaOriginal->dayOfWeek + 1])
                    ->whereRaw('TIME(fecha_inicio) = ?', [$agendaOriginal->format('H:i:s')])
                    ->where('estado', '!=', 4);

                if ((int) $request->user()->id_tipo_usuario === 2) {
                    $agendasFuturasQuery->where('id_usuario', $request->user()->id);
                }

                $agendasFuturas = $agendasFuturasQuery->select('id', 'fecha_inicio')->get();

                foreach ($agendasFuturas as $agendaFutura) {
                    $fechaInicioFutura = Carbon::parse($agendaFutura->fecha_inicio, config('app.timezone'))
                        ->setTimeFromTimeString($nuevaHora);
                    $fechaFinFutura = (clone $fechaInicioFutura)->addMinutes($duracionMinutos);

                    DB::table('agendas')
                        ->where('id', $agendaFutura->id)
                        ->update([
                            'fecha_inicio' => $fechaInicioFutura,
                            'fecha_fin' => $fechaFinFutura,
                            'titulo' => $validated['titulo'] ?? null,
                            'descripcion' => $validated['descripcion'] ?? null,
                            'updated_at' => now(),
                        ]);

                    $this->syncAgendaEjercicios((int) $agendaFutura->id, $validated['ejercicios']);
                }
            }

            DB::table('agendas')
                ->where('id', $agenda->id)
                ->update([
                    'fecha_inicio' => $fechaInicio,
                    'fecha_fin' => $fechaFin,
                    'titulo' => $validated['titulo'] ?? null,
                    'descripcion' => $validated['descripcion'] ?? null,
                    'updated_at' => now(),
                ]);

            $this->syncAgendaEjercicios((int) $agenda->id, $validated['ejercicios']);
        });

        return response()->json([
            'message' => 'Entrenamiento actualizado correctamente.',
        ]);
    }

    public function adminAgendaEstadoUpdate(Request $request, int $id): JsonResponse
    {
        if ($err = $this->requireAdminOrEntrenador($request)) return $err;

        $agenda = $this->findScopedAgenda($request, $id);
        if (! $agenda) {
            return response()->json(['message' => 'Entrenamiento no encontrado.'], 404);
        }

        $validated = $request->validate([
            'estado' => 'required|integer|in:1,2,3,4,5',
        ]);

        if ((int) $agenda->estado === 4) {
            return response()->json([
                'message' => 'No se puede cambiar el estado de un entrenamiento realizado.',
            ], 422);
        }

        DB::table('agendas')
            ->where('id', $agenda->id)
            ->update([
                'estado' => (int) $validated['estado'],
                'updated_at' => now(),
            ]);

        app(\App\Services\Gamificacion\GamificacionService::class)->recalcularPuntos((int) $agenda->id_cliente);

        return response()->json([
            'message' => 'Estado del entrenamiento actualizado correctamente.',
        ]);
    }

    /**
     * "Cierre de día": el entrenador confirma en bloque cuáles de las
     * sesiones de hoy realmente se hicieron. Solo toca las que están en
     * estado Agendado (1) — no reabre canceladas/reagendadas/ya realizadas.
     */
    public function adminAgendaCierreDia(Request $request): JsonResponse
    {
        if ($err = $this->requireAdminOrEntrenador($request)) return $err;

        $validated = $request->validate([
            'sesiones' => 'required|array|min:1',
            'sesiones.*' => 'integer',
        ]);

        $user = $request->user();
        $query = DB::table('agendas')
            ->whereIn('id', $validated['sesiones'])
            ->where('estado', 1);

        if ((int) $user->id_tipo_usuario === 2) {
            $query->where('id_usuario', $user->id);
        }

        $agendas = $query->get(['id', 'id_cliente']);

        if ($agendas->isEmpty()) {
            return response()->json(['message' => 'No hay sesiones válidas para confirmar.'], 422);
        }

        DB::transaction(function () use ($agendas) {
            DB::table('agendas')
                ->whereIn('id', $agendas->pluck('id'))
                ->update(['estado' => 4, 'updated_at' => now()]);
        });

        $gamificacion = app(\App\Services\Gamificacion\GamificacionService::class);
        foreach ($agendas->pluck('id_cliente')->unique() as $idCliente) {
            $gamificacion->recalcularPuntos((int) $idCliente);
        }

        return response()->json([
            'message' => 'Sesiones confirmadas como realizadas.',
            'confirmadas' => $agendas->count(),
        ]);
    }

    public function adminAgendaCalendario(Request $request): JsonResponse
    {
        if ($err = $this->requireAdminOrEntrenador($request)) return $err;

        $user       = $request->user();
        $isSuperAdmin = (int) $user->id_tipo_usuario === 10;
        $isEntrenador = (int) $user->id_tipo_usuario === 2;
        $desde       = $request->query('desde', now()->startOfMonth()->format('Y-m-d'));
        $hasta       = $request->query('hasta', now()->endOfMonth()->format('Y-m-d'));
        $idUsuario   = $request->query('idUsuario');
        $idGimnasio  = $isSuperAdmin ? (int) $request->query('id_gimnasio', 0) : (int) ($user->id_gimnasio ?? 0);

        $q = DB::table('agendas')
            ->join('clientes', 'agendas.id_cliente', '=', 'clientes.id')
            ->join('users', 'agendas.id_usuario', '=', 'users.id')
            ->leftJoin('gimnasios', 'clientes.id_gimnasio', '=', 'gimnasios.id')
            ->whereBetween(DB::raw('DATE(agendas.fecha_inicio)'), [$desde, $hasta]);

        if ($idUsuario) $q->where('agendas.id_usuario', (int) $idUsuario);
        // Para entrenador, filtrar por agendas.id_usuario (arriba) ya acota
        // correctamente a sus propias sesiones; agregar el filtro de gimnasio
        // encima es redundante y puede ocultar sesiones si hay un desajuste de
        // id_gimnasio entre el entrenador y el cliente de esa sesión puntual.
        if ($idGimnasio > 0 && !$isEntrenador) $q->where('clientes.id_gimnasio', $idGimnasio);

        $sesiones = $q->select(
            'agendas.id',
            'agendas.slug',
            'agendas.titulo',
            'agendas.fecha_inicio',
            'agendas.fecha_fin',
            'agendas.estado',
            'agendas.descripcion',
            'agendas.id_cliente',
            'clientes.id_gimnasio',
            'gimnasios.nombre as gimnasio',
            DB::raw("CONCAT(clientes.nombres,' ',clientes.paterno) as cliente_nombre"),
            'clientes.slug as cliente_slug',
            'users.name as entrenador_nombre',
            'agendas.id_usuario'
        )->orderBy('agendas.fecha_inicio')->get();

        $ids = $sesiones->pluck('id')->toArray();
        $ejerciciosMap = [];
        if (! empty($ids)) {
            $rows = DB::table('agendas_ejercicios')
                ->join('ejercicios', 'ejercicios.id', '=', 'agendas_ejercicios.id_ejercicio')
                ->whereIn('agendas_ejercicios.id_agenda', $ids)
                ->orderBy('agendas_ejercicios.orden')
                ->get([
                    'agendas_ejercicios.id_agenda',
                    'agendas_ejercicios.id_ejercicio',
                    'ejercicios.nombre',
                    'agendas_ejercicios.serie',
                    'agendas_ejercicios.repeticiones',
                    'agendas_ejercicios.carga',
                    'agendas_ejercicios.descanso'
                ]);
            foreach ($rows as $r) {
                $ejerciciosMap[$r->id_agenda][] = [
                    'id_ejercicio' => (int) $r->id_ejercicio,
                    'nombre' => $r->nombre,
                    'serie' => $r->serie,
                    'repeticiones' => $r->repeticiones,
                    'carga' => $r->carga,
                    'descanso' => $r->descanso,
                ];
            }
        }

        return response()->json([
            'sesiones' => $sesiones->map(fn($s) => [
                'id' => (int) $s->id,
                'titulo' => $s->titulo,
                'fecha_inicio' => $s->fecha_inicio,
                'fecha_fin' => $s->fecha_fin,
                'estado' => $s->estado !== null ? (int) $s->estado : null,
                'descripcion' => $s->descripcion,
                'id_cliente' => (int) $s->id_cliente,
                'cliente_nombre' => $s->cliente_nombre,
                'cliente_slug' => $s->cliente_slug,
                'entrenador_nombre' => $s->entrenador_nombre,
                'ejercicios' => $ejerciciosMap[$s->id] ?? [],
            ]),
        ]);
    }

    /**
     * Endpoint dedicado para "Reporte de agendas" (feature flag reporte_agendas),
     * separado de adminAgendaCalendario() a propósito: ese otro endpoint también lo usa
     * el calendario CORE (AgendaCalendarPage), así que no se le puede poner el
     * middleware feature:reporte_agendas sin romper el calendario para todo el mundo.
     * Este solo devuelve id+estado por sesión (lo único que el reporte necesita para
     * las cuentas por estado), con el mismo scoping por gimnasio/entrenador que el
     * endpoint del calendario.
     */
    public function adminReporteAgendas(Request $request): JsonResponse
    {
        if ($err = $this->requireAdminOrEntrenador($request)) return $err;

        $user         = $request->user();
        $isSuperAdmin = (int) $user->id_tipo_usuario === 10;
        $isEntrenador = (int) $user->id_tipo_usuario === 2;
        $desde        = $request->query('desde', now()->startOfMonth()->format('Y-m-d'));
        $hasta        = $request->query('hasta', now()->endOfMonth()->format('Y-m-d'));
        $idUsuario    = $request->query('idUsuario');
        $idGimnasio   = $isSuperAdmin ? (int) $request->query('id_gimnasio', 0) : (int) ($user->id_gimnasio ?? 0);

        $q = DB::table('agendas')
            ->join('clientes', 'agendas.id_cliente', '=', 'clientes.id')
            ->whereBetween(DB::raw('DATE(agendas.fecha_inicio)'), [$desde, $hasta]);

        if ($idUsuario) $q->where('agendas.id_usuario', (int) $idUsuario);
        // Mismo criterio que adminAgendaCalendario(): para entrenador, el filtro por
        // agendas.id_usuario ya acota completamente a sus sesiones propias.
        if ($idGimnasio > 0 && !$isEntrenador) $q->where('clientes.id_gimnasio', $idGimnasio);

        $sesiones = $q->select('agendas.id', 'agendas.estado')
            ->orderBy('agendas.fecha_inicio')
            ->get();

        return response()->json([
            'sesiones' => $sesiones->map(fn($s) => [
                'id' => (int) $s->id,
                'estado' => $s->estado !== null ? (int) $s->estado : null,
            ]),
        ]);
    }

    // ===================================================================
    // ADMIN — EJERCICIOS DEL SISTEMA
    // ===================================================================

    public function adminEjerciciosIndex(Request $request): JsonResponse
    {
        if ($err = $this->requireAdminEntrenadorOrRecepcionista($request)) return $err;

        $tiposEjercicio = DB::table('tipos_ejercicios')
            ->orderBy('nombre')
            ->get(['id', 'nombre', 'icono'])
            ->map(fn($r) => [
                'id' => (int) $r->id,
                'nombre' => $r->nombre,
                'icono' => $r->icono,
            ])
            ->values();

        $ejercicios = DB::table('ejercicios')
            ->leftJoin('tipos_ejercicios', 'ejercicios.id_tipo', '=', 'tipos_ejercicios.id')
            ->orderBy('ejercicios.nombre')
            ->get([
                'ejercicios.id',
                'ejercicios.nombre',
                'ejercicios.slug',
                'ejercicios.descripcion',
                'ejercicios.estado',
                'ejercicios.id_tipo',
                'tipos_ejercicios.nombre as tipo_nombre',
                'tipos_ejercicios.icono as tipo_icono'
            ])
            ->map(fn($r) => [
                'id' => (int) $r->id,
                'nombre' => $r->nombre,
                'slug' => $r->slug,
                'descripcion' => $r->descripcion,
                'estado' => $r->estado,
                'id_tipo' => $r->id_tipo,
                'tipo_nombre' => $r->tipo_nombre,
                'tipo_icono' => $r->tipo_icono,
            ])
            ->values();

        return response()->json([
            'ejercicios' => $ejercicios,
            'tipos_ejercicios' => $tiposEjercicio,
        ]);
    }

    public function adminEjerciciosStore(Request $request): JsonResponse
    {
        if ($err = $this->requireAdminOrEntrenador($request)) return $err;

        $v = $request->validate([
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string|max:500',
            'id_tipo' => 'required|integer|exists:tipos_ejercicios,id',
            'estado' => 'required|integer|in:0,1',
        ]);

        $tipo = DB::table('tipos_ejercicios')->where('id', $v['id_tipo'])->first(['icono']);
        $slug = Str::slug($v['nombre']) . '-' . Str::random(6);
        $id   = DB::table('ejercicios')->insertGetId([
            'nombre' => $v['nombre'],
            'descripcion' => $v['descripcion'] ?? null,
            'slug' => $slug,
            'id_tipo' => $v['id_tipo'],
            'icono' => $tipo->icono ?? null,
            'estado' => (int) $v['estado'],
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json(['message' => 'Ejercicio creado.', 'id' => $id], 201);
    }

    public function adminEjerciciosUpdate(Request $request, int $id): JsonResponse
    {
        if ($err = $this->requireAdminOrEntrenador($request)) return $err;

        $v = $request->validate([
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string|max:500',
            'id_tipo' => 'required|integer|exists:tipos_ejercicios,id',
            'estado' => 'required|integer|in:0,1',
        ]);

        $tipo = DB::table('tipos_ejercicios')->where('id', $v['id_tipo'])->first(['icono']);

        DB::table('ejercicios')->where('id', $id)->update([
            'nombre' => $v['nombre'],
            'descripcion' => $v['descripcion'] ?? null,
            'id_tipo' => $v['id_tipo'],
            'icono' => $tipo->icono ?? null,
            'estado' => (int) $v['estado'],
            'updated_at' => now(),
        ]);

        return response()->json(['message' => 'Ejercicio actualizado.']);
    }

    public function adminEjerciciosDestroy(Request $request, int $id): JsonResponse
    {
        if ($err = $this->requireAdmin($request)) return $err;

        DB::transaction(function () use ($id) {
            DB::table('agendas_ejercicios')->where('id_ejercicio', $id)->delete();
            DB::table('ejercicios')->where('id', $id)->delete();
        });

        return response()->json(['message' => 'Ejercicio eliminado.']);
    }

    // ===================================================================
    // ADMIN — CLIENTES
    // ===================================================================

    public function adminClientesIndex(Request $request): JsonResponse
    {
        if ($err = $this->requireAdminEntrenadorOrRecepcionista($request)) return $err;

        $busqueda = trim((string) $request->query('q', ''));
        $busquedaNormalizada = str_replace(['.', '-', ' '], '', Str::lower($busqueda));
        $perPage  = max(1, (int) $request->query('per_page', 20));
        $page     = max(1, (int) $request->query('page', 1));
        $esSuperAdmin = (int) $request->user()->id_tipo_usuario === 10;
        $esEntrenador = (int) $request->user()->id_tipo_usuario === 2;
        $idGimnasio = $esSuperAdmin
            ? max(0, (int) $request->query('id_gimnasio', 0))
            : (int) ($request->user()->id_gimnasio ?? 0);

        $selectColumns = [
            'clientes.id',
            'clientes.nombres',
            'clientes.paterno',
            'clientes.materno',
            'clientes.ci',
            'clientes.email',
            'clientes.telefono',
            'clientes.id_genero',
            'clientes.id_plan',
            'clientes.id_usuario',
            'clientes.slug',
            'clientes.estado',
            'clientes.fecha_ingreso',
            'clientes.fecha_fin',
            'clientes.id_gimnasio',
            'planes.nombre as plan_nombre',
            'gimnasios.nombre as gimnasio_nombre',
        ];

        if ($this->clientePhotoColumnExists()) {
            $selectColumns[] = 'clientes.foto_path';
        }

        $query = DB::table('clientes')
            ->leftJoin('planes', 'clientes.id_plan', '=', 'planes.id')
            ->leftJoin('gimnasios', 'clientes.id_gimnasio', '=', 'gimnasios.id')
            ->select($selectColumns);

        $this->applyClienteScopeToQuery($query, $request->user());

        if (!$esEntrenador) {
            // El entrenador ya queda acotado por applyClienteScopeToQuery() (clientes
            // asignados a él); agregar además el filtro de gimnasio aquí es redundante
            // y puede excluir clientes legítimamente asignados si hay algún desajuste
            // de id_gimnasio entre el entrenador y ese cliente puntual.
            if ($idGimnasio > 0) {
                $query->where('clientes.id_gimnasio', $idGimnasio);
            } elseif (!$esSuperAdmin) {
                $query->whereRaw('1 = 0');
            }
        }

        if ($busqueda !== '') {
            $query->where(function ($q) use ($busqueda, $busquedaNormalizada) {
                $q->where('clientes.nombres', 'like', "%{$busqueda}%")
                    ->orWhere('clientes.paterno', 'like', "%{$busqueda}%")
                    ->orWhere('clientes.materno', 'like', "%{$busqueda}%")
                    ->orWhere('clientes.email', 'like', "%{$busqueda}%");

                if ($busquedaNormalizada !== '') {
                    $q->orWhereRaw(
                        "REPLACE(REPLACE(REPLACE(LOWER(COALESCE(clientes.ci, '')), '.', ''), '-', ''), ' ', '') like ?",
                        ["%{$busquedaNormalizada}%"]
                    );
                }
            });
        }

        $total = (clone $query)->count('clientes.id');
        $items = $query
            ->orderBy('clientes.nombres')
            ->orderBy('clientes.paterno')
            ->forPage($page, $perPage)
            ->get();

        $morososIds = DB::table('cuentas_corrientes')
            ->whereNull('fecha_pago')
            ->whereDate('fecha_vencimiento', '<', now()->toDateString())
            ->pluck('id_cliente')
            ->unique()
            ->toArray();

        // ── Datos enriquecidos por cliente ─────────────────────────────────
        $clienteIds = $items->pluck('id')->toArray();
        $ahora      = now();
        $semIni     = $ahora->copy()->startOfWeek()->toDateString();
        $semFin     = $ahora->copy()->endOfWeek()->toDateString();

        // Próxima sesión futura (la más cercana desde ahora, no cancelada)
        $proximasSesionesMap = DB::table('agendas')
            ->whereIn('id_cliente', $clienteIds)
            ->where('fecha_inicio', '>=', $ahora->toDateTimeString())
            ->whereNotIn('estado', [0, 2, 3])
            ->select('id_cliente', DB::raw('MIN(fecha_inicio) as proxima_sesion'))
            ->groupBy('id_cliente')
            ->pluck('proxima_sesion', 'id_cliente')
            ->toArray();

        // Deuda total (cuotas vencidas sin pagar)
        $deudasMap = DB::table('cuentas_corrientes')
            ->whereIn('id_cliente', $clienteIds)
            ->whereNull('fecha_pago')
            ->whereDate('fecha_vencimiento', '<', $ahora->toDateString())
            ->select('id_cliente', DB::raw('SUM(COALESCE(monto_pagar, monto, 0)) as deuda'))
            ->groupBy('id_cliente')
            ->pluck('deuda', 'id_cliente')
            ->toArray();

        // Última sesión completada (para calcular días sin actividad)
        $ultimaSesionMap = DB::table('agendas')
            ->whereIn('id_cliente', $clienteIds)
            ->where('estado', 4)
            ->select('id_cliente', DB::raw('MAX(fecha_inicio) as ultima_sesion'))
            ->groupBy('id_cliente')
            ->pluck('ultima_sesion', 'id_cliente')
            ->toArray();

        // Sesiones de la semana actual por cliente
        $sesionesSemanaRows = DB::table('agendas')
            ->whereIn('id_cliente', $clienteIds)
            ->whereBetween(DB::raw('DATE(fecha_inicio)'), [$semIni, $semFin])
            ->whereIn('estado', [1, 4, 5])
            ->select('id_cliente', 'estado', DB::raw('COUNT(*) as cnt'))
            ->groupBy('id_cliente', 'estado')
            ->get();

        $sesionesMap = [];
        foreach ($sesionesSemanaRows as $row) {
            $cid = (int) $row->id_cliente;
            if (!isset($sesionesMap[$cid])) {
                $sesionesMap[$cid] = ['total' => 0, 'completas' => 0];
            }
            $sesionesMap[$cid]['total'] += (int) $row->cnt;
            if ((int) $row->estado === 4) {
                $sesionesMap[$cid]['completas'] += (int) $row->cnt;
            }
        }
        // ───────────────────────────────────────────────────────────────────

        $result = $items->map(function ($c) use ($morososIds, $proximasSesionesMap, $deudasMap, $ultimaSesionMap, $sesionesMap, $ahora) {
            $cid = (int) $c->id;
            $ultimaSesionStr = $ultimaSesionMap[$cid] ?? null;
            $diasSinActividad = null;
            if ($ultimaSesionStr) {
                try {
                    $diasSinActividad = (int) \Carbon\Carbon::parse($ultimaSesionStr)->diffInDays($ahora);
                } catch (\Throwable $e) {
                    $diasSinActividad = null;
                }
            }
            return array_merge([
                'id' => $cid,
                'nombre' => $c->nombres,
                'apellido' => $c->paterno,
                'nombre_completo' => trim("{$c->nombres} {$c->paterno} " . ($c->materno ?? '')),
                'ci' => $c->ci,
                'email' => $c->email,
                'telefono' => $c->telefono,
                'id_genero' => $c->id_genero ? (int) $c->id_genero : null,
                'id_plan' => $c->id_plan ? (int) $c->id_plan : null,
                'id_usuario' => $c->id_usuario ? (int) $c->id_usuario : null,
                'slug' => $c->slug,
                'estado' => (int) $c->estado,
                'id_gimnasio' => $c->id_gimnasio ? (int) $c->id_gimnasio : null,
                'gimnasio' => $c->gimnasio_nombre,
                'plan' => $c->plan_nombre,
                'fecha_ingreso' => $c->fecha_ingreso,
                'fecha_fin' => $c->fecha_fin,
                'moroso' => in_array($cid, $morososIds),
                'deuda' => isset($deudasMap[$cid]) ? (float) $deudasMap[$cid] : 0.0,
                'proxima_sesion' => $proximasSesionesMap[$cid] ?? null,
                'dias_sin_actividad' => $diasSinActividad,
                'sesiones_semana_total' => $sesionesMap[$cid]['total'] ?? 0,
                'sesiones_semana_completas' => $sesionesMap[$cid]['completas'] ?? 0,
            ], $this->buildClientePhotoPayload($this->resolveClientePhotoPath($c)));
        })->values();

        return response()->json([
            'data' => $result,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
        ]);
    }

    public function adminMorososIndex(Request $request): JsonResponse
    {
        if ($err = $this->requireAdminOrEntrenador($request)) return $err;

        $hoy = now()->toDateString();

        // Clientes activos que tienen al menos una cuota impaga ya vencida
        $morososIds = DB::table('cuentas_corrientes')
            ->join('clientes', 'cuentas_corrientes.id_cliente', '=', 'clientes.id')
            ->whereNull('cuentas_corrientes.fecha_pago')
            ->whereDate('cuentas_corrientes.fecha_vencimiento', '<', $hoy)
            ->where('clientes.estado', 1)
            ->pluck('cuentas_corrientes.id_cliente')
            ->unique()
            ->toArray();

        if ((int) $request->user()->id_tipo_usuario === 2) {
            $clienteIdsEntrenador = $this->getScopedClienteIdsForUser($request->user());
            $morososIds = array_values(array_intersect($morososIds, $clienteIdsEntrenador));
        }

        if (empty($morososIds)) {
            return response()->json(['data' => []]);
        }

        // Resumen de cuotas vencidas por cliente
        $resumen = DB::table('cuentas_corrientes')
            ->whereIn('id_cliente', $morososIds)
            ->whereNull('fecha_pago')
            ->whereDate('fecha_vencimiento', '<', $hoy)
            ->selectRaw('id_cliente, COUNT(*) as cuotas_vencidas, SUM(saldo) as monto_deuda, MIN(fecha_vencimiento) as primera_vencida, DATEDIFF(CURDATE(), MIN(fecha_vencimiento)) as dias_vencida')
            ->groupBy('id_cliente')
            ->get()
            ->keyBy('id_cliente');

        $clientes = DB::table('clientes')
            ->whereIn('clientes.id', $morososIds)
            ->where('clientes.estado', 1)
            ->leftJoin('planes', 'clientes.id_plan', '=', 'planes.id')
            ->select(
                'clientes.id',
                'clientes.nombres',
                'clientes.paterno',
                'clientes.materno',
                'clientes.email',
                'clientes.telefono',
                'clientes.slug',
                'clientes.estado',
                'planes.nombre as plan_nombre'
            )
            ->orderBy('clientes.paterno')
            ->get();

        $result = $clientes->map(function ($c) use ($resumen) {
            $r = $resumen[$c->id] ?? null;
            $diasVencida = $r ? (int) $r->dias_vencida : 0;
            return [
                'id'              => $c->id,
                'nombre'          => $c->nombres,
                'apellido'        => $c->paterno,
                'nombre_completo' => trim("{$c->nombres} {$c->paterno} " . ($c->materno ?? '')),
                'email'           => $c->email,
                'telefono'        => $c->telefono,
                'slug'            => $c->slug,
                'estado'          => (int) $c->estado,
                'plan'            => $c->plan_nombre,
                'cuotas_vencidas' => $r ? (int) $r->cuotas_vencidas : 0,
                'monto_deuda'     => $r ? (float) $r->monto_deuda : 0,
                'primera_vencida' => $r?->primera_vencida,
                'dias_vencida'    => $diasVencida,
                'aging_bucket'    => $this->resolveAgingBucket($diasVencida),
            ];
        });

        return response()->json(['data' => $result->values()]);
    }

    private function resolveAgingBucket(int $diasVencida): string
    {
        if ($diasVencida <= 30) return '0-30';
        if ($diasVencida <= 60) return '31-60';
        if ($diasVencida <= 90) return '61-90';
        return '90+';
    }

    private function resolveClientePhotoPath(object $cliente): ?string
    {
        if (! $this->clientePhotoColumnExists()) {
            return null;
        }

        return property_exists($cliente, 'foto_path') && is_string($cliente->foto_path) && $cliente->foto_path !== ''
            ? $cliente->foto_path
            : null;
    }

    private function clientePhotoColumnExists(): bool
    {
        static $exists = null;

        if ($exists === null) {
            $exists = Schema::hasColumn('clientes', 'foto_path');
        }

        return $exists;
    }
    public function adminClienteEnviarAcceso(
        Request $request,
        string $slug,
        ClienteLifecycleService $clienteLifecycleService
    ): JsonResponse {
        if ($err = $this->requireAdminOrEntrenador($request)) return $err;

        $clienteScoped = $this->findScopedCliente($request, $slug);
        if (! $clienteScoped) {
            return response()->json(['message' => 'Cliente no encontrado.'], 404);
        }

        $cliente = Clientes::query()->find($clienteScoped->id);
        if (! $cliente) {
            return response()->json(['message' => 'Cliente no encontrado.'], 404);
        }

        if (! $cliente->email) {
            return response()->json([
                'message' => 'El cliente no tiene un correo electrónico registrado.',
            ], 422);
        }

        $clienteLifecycleService->resendAccessCredentials($cliente);

        return response()->json([
            'message' => 'Accesos enviados con éxito al correo del cliente.',
        ]);
    }

    public function adminClienteEnviarReportePdf(Request $request, string $slug): JsonResponse
    {
        if ($err = $this->requireAdminOrEntrenador($request)) return $err;

        $clienteScoped = $this->findScopedCliente($request, $slug);
        if (! $clienteScoped) {
            return response()->json(['message' => 'Cliente no encontrado.'], 404);
        }

        $cliente = Clientes::query()->find($clienteScoped->id);
        if (! $cliente) {
            return response()->json(['message' => 'Cliente no encontrado.'], 404);
        }

        if (! $cliente->email) {
            return response()->json([
                'message' => 'El cliente no tiene un correo electrónico registrado.',
            ], 422);
        }

        try {
            (new \App\Http\Controllers\ClientesController())->generarYEnviarReportePdf($cliente);
            return response()->json(['message' => 'Reporte enviado con éxito al correo del cliente.']);
        } catch (\Exception $e) {
            \Log::error("Error enviando reporte PDF para cliente (admin): " . $e->getMessage());
            return response()->json(['message' => 'Error al generar o enviar el reporte: ' . $e->getMessage()], 500);
        }
    }

    public function adminClienteEnviarRecordatorio(
        Request $request,
        string $slug,
        ClienteLifecycleService $clienteLifecycleService
    ): JsonResponse {
        if ($err = $this->requireAdminOrEntrenador($request)) return $err;

        $clienteScoped = $this->findScopedCliente($request, $slug);
        if (! $clienteScoped) {
            return response()->json(['message' => 'Cliente no encontrado.'], 404);
        }

        $cliente = Clientes::query()->find($clienteScoped->id);
        if (! $cliente) {
            return response()->json(['message' => 'Cliente no encontrado.'], 404);
        }

        $fechaActual = Carbon::now()->toDateString();
        $cuotasVencidas = $cliente->cuotas()
            ->where('id_estado_pago', '<>', 2)
            ->where('fecha_vencimiento', '<=', $fechaActual)
            ->get();

        if ($cuotasVencidas->isEmpty()) {
            return response()->json([
                'message' => 'El cliente no tiene cuotas vencidas.',
            ], 422);
        }

        Mail::to($cliente->email)->send(new RecordatorioCuotasVencidas($cliente, $cuotasVencidas));

        $actor = $request->user();
        if ($actor) {
            $clienteLifecycleService->notifyMorosidadReminderSent($cliente, $actor, $cuotasVencidas->count());
        }

        return response()->json([
            'message' => 'Recordatorio enviado con éxito.',
        ]);
    }

    public function adminClienteEnviarRecordatorioInactividad(
        Request $request,
        string $slug,
        ClienteLifecycleService $clienteLifecycleService
    ): JsonResponse {
        if ($err = $this->requireAdminOrEntrenador($request)) return $err;

        $clienteScoped = $this->findScopedCliente($request, $slug);
        if (! $clienteScoped) {
            return response()->json(['message' => 'Cliente no encontrado.'], 404);
        }

        $cliente = Clientes::query()->find($clienteScoped->id);
        if (! $cliente) {
            return response()->json(['message' => 'Cliente no encontrado.'], 404);
        }

        // Calcular días de inactividad desde la última agenda registrada
        $ultimaSesion = \DB::table('agendas')
            ->where('id_cliente', $cliente->id)
            ->max('fecha_inicio');

        $diasSinEntrenar = $ultimaSesion
            ? (int) \Carbon\Carbon::parse($ultimaSesion)->diffInDays(\Carbon\Carbon::today())
            : (int) \Carbon\Carbon::parse($cliente->fecha_ingreso)->diffInDays(\Carbon\Carbon::today());

        $emailError = null;
        try {
            Mail::to($cliente->email)->send(new RecordatorioInactividad($cliente, $diasSinEntrenar));
        } catch (\Throwable $e) {
            $emailError = $e->getMessage();
            \Log::error('RecordatorioInactividad email failed', [
                'cliente_id' => $cliente->id,
                'email'      => $cliente->email,
                'error'      => $emailError,
            ]);
        }

        $actor = $request->user();
        if ($actor) {
            $clienteLifecycleService->notifyInactividadReminderSent($cliente, $actor, $diasSinEntrenar);
        }

        $message = $emailError
            ? 'Recordatorio enviado (notificación), pero falló el correo: ' . $emailError
            : 'Recordatorio enviado con éxito.';

        return response()->json(['message' => $message]);
    }

    public function adminMotivosIndex(Request $request): JsonResponse
    {
        if ($err = $this->requireAdminOrEntrenador($request)) return $err;

        $tipo  = $request->query('tipo');
        $query = DB::table('motivos')->where('estado', 1)->orderBy('nombre');

        if ($tipo !== null) {
            $query->where('tipo', (int) $tipo);
        }

        return response()->json(['motivos' => $query->get(['id', 'nombre', 'slug', 'tipo'])]);
    }

    public function adminGenerosIndex(Request $request): JsonResponse
    {
        if ($err = $this->requireAdminOrEntrenador($request)) return $err;

        $generos = DB::table('generos')
            ->where('estado', 1)
            ->orderBy('nombre')
            ->get(['id', 'nombre', 'slug']);

        return response()->json(['generos' => $generos]);
    }

    public function adminTiposUsuariosIndex(Request $request): JsonResponse
    {
        if ($err = $this->requireAdminOrEntrenador($request)) return $err;

        $tipos = DB::table('tipos_usuarios')
            ->orderBy('nombre')
            ->get(['id', 'nombre', 'slug', 'descripcion']);

        return response()->json(['tipos_usuarios' => $tipos]);
    }

    public function adminEntrenadoresIndex(Request $request): JsonResponse
    {
        if ($err = $this->requireAdminOrEntrenador($request)) return $err;

        $actor = $request->user();
        $isSuperAdmin = (int) $actor->id_tipo_usuario === 10;
        $idGimnasio = $isSuperAdmin ? (int) $request->query('id_gimnasio', 0) : (int) ($actor->id_gimnasio ?? 0);

        $query = DB::table('users')
            ->where('id_tipo_usuario', 2)
            ->orderBy('name');

        if ($idGimnasio > 0) {
            $query->where('id_gimnasio', $idGimnasio);
        }

        $entrenadores = $query->get(['id', 'name', 'id_gimnasio'])
            ->map(fn($r) => [
                'id' => (int) $r->id,
                'name' => $r->name,
                'id_gimnasio' => $r->id_gimnasio ? (int) $r->id_gimnasio : null,
            ])
            ->values();

        return response()->json(['entrenadores' => $entrenadores]);
    }

    public function adminClientesStore(Request $request, ClienteLifecycleService $clienteLifecycleService): JsonResponse
    {
        if ($err = $this->requireAdminOrEntrenador($request)) return $err;

        $actor = $request->user();
        $isSuperAdmin = (int) $actor->id_tipo_usuario === 10;
        $isEntrenador = (int) $actor->id_tipo_usuario === 2;

        $v = $request->validate([
            'nombre'             => 'required|string|max:255',
            'apellido'           => 'required|string|max:255',
            'email'              => 'required|email|unique:clientes,email|unique:users,email',
            'telefono'           => 'required|string|max:50',
            'materno'            => 'nullable|string|max:255',
            'ci'                 => 'required|string|max:20|unique:clientes,ci',
            'fecha_nacimiento'   => 'nullable|date',
            'fecha_ingreso'      => 'nullable|date',
            'fecha_fin'          => 'nullable|date',
            'altura'             => 'nullable|numeric|min:0.5|max:2.5',
            'direccion'          => 'nullable|string|max:255',
            'ciudad'             => 'nullable|string|max:255',
            'id_genero'          => 'required|integer|exists:generos,id',
            'id_plan'            => 'required|integer|exists:planes,id',
            'id_usuario'         => 'nullable|integer|exists:users,id',
            'id_motivo_ingreso'  => 'nullable|integer|exists:motivos,id',
            'id_motivo_egreso'   => 'nullable|integer|exists:motivos,id',
            'estado'             => 'nullable|integer|in:0,1',
            'perfil'             => 'nullable|string',
            'id_gimnasio'        => 'nullable|integer|exists:gimnasios,id',
            'foto'               => 'nullable|image|max:5120',
            'avatar'             => 'nullable|image|max:5120',
            'image'              => 'nullable|image|max:5120',
        ]);

        $idGimnasio = $isSuperAdmin
            ? (int) ($v['id_gimnasio'] ?? ($actor->id_gimnasio ?? 0))
            : (int) ($actor->id_gimnasio ?? 0);

        $idUsuario = $isEntrenador ? (int) $actor->id : (int) ($v['id_usuario'] ?? 0);

        if (!$isEntrenador && $idUsuario) {
            $trainerValido = DB::table('users')
                ->where('id', $idUsuario)
                ->where('id_tipo_usuario', 2)
                ->when($idGimnasio, fn($q) => $q->where('id_gimnasio', $idGimnasio))
                ->exists();

            if (!$trainerValido) {
                return response()->json(['message' => 'El entrenador seleccionado no pertenece a este gimnasio.'], 422);
            }
        }

        $base = Str::slug($v['nombre'] . '-' . $v['apellido']);
        $slug = $base;
        $i    = 0;
        while (DB::table('clientes')->where('slug', $slug)->exists()) {
            $slug = $base . '-' . (++$i);
        }

        $cliente = null;
        $foto = $this->getClientePhotoFromRequest($request);

        DB::transaction(function () use (&$cliente, $clienteLifecycleService, $v, $slug, $idUsuario, $idGimnasio, $actor, $foto) {
            $createData = [
                'nombres' => $v['nombre'],
                'paterno' => $v['apellido'],
                'materno' => $v['materno'] ?? null,
                'ci' => $v['ci'],
                'email' => $v['email'],
                'telefono' => $v['telefono'],
                'id_genero' => (int) $v['id_genero'],
                'fecha_nacimiento' => $v['fecha_nacimiento'] ?? null,
                'fecha_ingreso' => $v['fecha_ingreso'] ?? now()->toDateString(),
                'fecha_pago' => $v['fecha_ingreso'] ?? now()->toDateString(),
                'fecha_fin' => $v['fecha_fin'] ?? null,
                'altura' => $v['altura'] ?? null,
                'direccion' => $v['direccion'] ?? null,
                'ciudad' => $v['ciudad'] ?? null,
                'id_plan' => $v['id_plan'] ?? null,
                'id_usuario' => $idUsuario ?: null,
                'id_motivo_ingreso' => $v['id_motivo_ingreso'] ?? null,
                'id_motivo_egreso' => $v['id_motivo_egreso'] ?? null,
                'perfil' => $v['perfil'] ?? null,
                'slug' => $slug,
                'estado' => 1,
                'id_gimnasio' => $idGimnasio ?: null,
            ];

            if ($this->clientePhotoColumnExists()) {
                $createData['foto_path'] = $foto ? $foto->store('clientes/fotos', 'public') : null;
            }

            $cliente = Clientes::query()->create($createData);

            $clienteLifecycleService->createAccessUserForCliente($cliente);
            $clienteLifecycleService->sendActivationWelcomeEmail($cliente);
        });

        return response()->json([
            'message' => 'Cliente creado correctamente.',
            'id' => (int) $cliente->id,
            'slug' => $cliente->slug,
        ], 201);
    }

    public function adminClienteDetalle(Request $request, string $slug): JsonResponse
    {
        if ($err = $this->requireAdminEntrenadorOrRecepcionista($request)) return $err;

        $cliente = $this->findScopedCliente($request, $slug);
        if (! $cliente) return response()->json(['message' => 'Cliente no encontrado.'], 404);

        return $this->buildAdminClienteDetalleResponse((int) $cliente->id);
    }

    public function adminClienteUpdate(Request $request, string $slug, ClienteLifecycleService $clienteLifecycleService): JsonResponse
    {
        if ($err = $this->requireAdminOrEntrenador($request)) return $err;

        $cliente = $this->findScopedCliente($request, $slug);
        if (! $cliente) return response()->json(['message' => 'Cliente no encontrado.'], 404);

        $actor = $request->user();
        $clienteModel = Clientes::query()->findOrFail($cliente->id);
        $estadoAnterior = (int) $clienteModel->getRawOriginal('estado');
        $clienteUser = User::query()->where('id_cliente', $clienteModel->id)->first();

        $validated = $request->validate([
            'nombres' => 'required|string|max:255',
            'paterno' => 'required|string|max:255',
            'materno' => 'nullable|string|max:255',
            'ci' => "required|string|max:20|unique:clientes,ci,{$cliente->id}",
            'email' => [
                'required',
                'email',
                'max:255',
                \Illuminate\Validation\Rule::unique('clientes', 'email')->ignore($cliente->id),
                \Illuminate\Validation\Rule::unique('users', 'email')->ignore($clienteUser?->id),
            ],
            'telefono' => 'required|string|max:50',
            'ciudad' => 'nullable|string|max:255',
            'direccion' => 'nullable|string|max:255',
            'fecha_nacimiento' => 'nullable|date',
            'fecha_ingreso' => 'required|date',
            'fecha_fin' => 'nullable|date',
            'altura' => 'nullable|numeric|min:0.5|max:2.5',
            'estado' => 'nullable|integer|in:0,1',
            'id_plan' => 'required|integer|exists:planes,id',
            'id_usuario' => 'nullable|integer|exists:users,id',
            'id_motivo_ingreso' => 'nullable|integer|exists:motivos,id',
            'id_motivo_egreso' => 'nullable|integer|exists:motivos,id',
            'id_genero' => 'required|integer|exists:generos,id',
            'perfil' => 'nullable|string',
            'foto' => 'nullable|image|max:5120',
            'avatar' => 'nullable|image|max:5120',
            'image' => 'nullable|image|max:5120',
            'remove_foto' => 'nullable|boolean',
        ]);

        if (!empty($validated['id_usuario'])) {
            $trainerValido = DB::table('users')
                ->where('id', $validated['id_usuario'])
                ->where('id_tipo_usuario', 2)
                ->where('id_gimnasio', $clienteModel->id_gimnasio)
                ->exists();

            if (!$trainerValido) {
                return response()->json(['message' => 'El entrenador seleccionado no pertenece a este gimnasio.'], 422);
            }
        }

        $foto = $this->getClientePhotoFromRequest($request);

        if ($foto && ! $this->clientePhotoColumnExists()) {
            return response()->json([
                'message' => 'La base actual aun no tiene habilitado el guardado de fotos de clientes. Ejecuta la migracion pendiente y vuelve a intentarlo.',
            ], 409);
        }

        if ((int) $actor->id_tipo_usuario === 2 && array_key_exists('estado', $validated) && (int) $validated['estado'] !== $estadoAnterior) {
            return response()->json(['message' => 'Solo el administrador o el super administrador pueden activar o dar de baja clientes.'], 403);
        }

        DB::transaction(function () use ($clienteModel, $validated, $clienteLifecycleService, $foto) {
            $data = $validated;
            unset($data['foto'], $data['avatar'], $data['image'], $data['remove_foto']);

            if ($this->clientePhotoColumnExists() && (($validated['remove_foto'] ?? false) || $foto)) {
                if ($clienteModel->foto_path) {
                    Storage::disk('public')->delete($clienteModel->foto_path);
                }

                $data['foto_path'] = $foto ? $foto->store('clientes/fotos', 'public') : null;
            }

            $clienteModel->update($data);

            $tipoUsuarioId = User::query()->where('id_cliente', $clienteModel->id)->value('id_tipo_usuario');
            $clienteLifecycleService->syncExistingClienteUser($clienteModel->fresh(), $tipoUsuarioId ? (int) $tipoUsuarioId : null);
        });

        $clienteModel->refresh();
        $estadoActual = (int) $clienteModel->getRawOriginal('estado');

        if ($estadoAnterior !== 1 && $estadoActual === 1) {
            $clienteLifecycleService->sendActivationWelcomeEmail($clienteModel);
            $clienteLifecycleService->notifyClienteActivated($clienteModel, $actor);
        }

        $updated = DB::table('clientes')
            ->select('clientes.*', 'planes.nombre as plan_nombre')
            ->leftJoin('planes', 'clientes.id_plan', '=', 'planes.id')
            ->where('clientes.id', $cliente->id)
            ->first();

        return response()->json([
            'message' => 'Cliente actualizado correctamente.',
            'cliente' => array_merge(
                (array) $updated,
                $this->buildClientePhotoPayload($this->resolveClientePhotoPath($updated))
            ),
        ]);
    }

    public function adminClienteUpdateById(Request $request, int $id, ClienteLifecycleService $clienteLifecycleService): JsonResponse
    {
        if ($err = $this->requireAdminOrEntrenador($request)) return $err;

        return $this->adminClienteUpdate($request, (string) $id, $clienteLifecycleService);
    }

    public function adminClientePesos(Request $request, string $slug): JsonResponse
    {
        if ($err = $this->requireAdminEntrenadorOrRecepcionista($request)) return $err;

        $cliente = $this->findScopedCliente($request, $slug);
        if (! $cliente) return response()->json(['message' => 'Cliente no encontrado.'], 404);

        return $this->buildAdminClientePesosResponse((int) $cliente->id);
    }

    public function adminClienteAgenda(Request $request, string $slug): JsonResponse
    {
        if ($err = $this->requireAdminEntrenadorOrRecepcionista($request)) return $err;

        $cliente = $this->findScopedCliente($request, $slug);
        if (! $cliente) return response()->json(['message' => 'Cliente no encontrado.'], 404);

        return $this->buildAdminClienteAgendaResponse((int) $cliente->id);
    }

    public function adminClienteEntrenamientos(Request $request, string $slug): JsonResponse
    {
        if ($err = $this->requireAdminEntrenadorOrRecepcionista($request)) return $err;

        $cliente = $this->findScopedCliente($request, $slug);
        if (! $cliente) return response()->json(['message' => 'Cliente no encontrado.'], 404);

        return $this->buildAdminClienteEntrenamientosResponse((int) $cliente->id);
    }

    public function adminClienteCuotas(Request $request, string $slug): JsonResponse
    {
        if ($err = $this->requireAdminEntrenadorOrRecepcionista($request)) return $err;

        $cliente = $this->findScopedCliente($request, $slug);
        if (! $cliente) return response()->json(['message' => 'Cliente no encontrado.'], 404);

        return $this->buildAdminClienteCuotasResponse((int) $cliente->id);
    }

    public function adminClienteCuotasStore(Request $request, string $slug): JsonResponse
    {
        if ($err = $this->requireAdminOrEntrenador($request)) return $err;

        $cliente = $this->findScopedCliente($request, $slug);
        if (! $cliente) return response()->json(['message' => 'Cliente no encontrado.'], 404);

        $validated = $request->validate([
            'fecha_vencimiento' => 'required|date_format:Y-m-d',
            'monto' => 'required|numeric|min:0',
            'descuento' => 'nullable|numeric|min:0',
            'cantidad' => 'required|integer|min:1|max:120',
        ]);

        $fechaBase = Carbon::createFromFormat('Y-m-d', $validated['fecha_vencimiento']);
        $monto = (float) $validated['monto'];
        $descuento = isset($validated['descuento']) ? (float) $validated['descuento'] : 0;
        $montoAPagar = max(0, $monto - $descuento);

        DB::transaction(function () use ($cliente, $validated, $fechaBase, $monto, $descuento, $montoAPagar) {
            for ($index = 0; $index < (int) $validated['cantidad']; $index++) {
                DB::table('cuentas_corrientes')->insert([
                    'id_cliente' => (int) $cliente->id,
                    'fecha_vencimiento' => $fechaBase->copy()->addMonths($index)->format('Y-m-d'),
                    'monto' => $monto,
                    'descuento' => $descuento,
                    'id_estado_pago' => 1,
                    'monto_pagado' => 0,
                    'saldo' => $montoAPagar,
                    'monto_pagar' => $montoAPagar,
                    'id_tipo_cuota' => 2,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        });

        $response = $this->buildAdminClienteCuotasResponse((int) $cliente->id)->getData(true);

        return response()->json([
            'message' => 'Cuotas creadas correctamente.',
            ...$response,
        ]);
    }

    public function adminClienteCuotasPagar(Request $request, string $slug): JsonResponse
    {
        if ($err = $this->requireAdminOrEntrenador($request)) return $err;

        $cliente = $this->findScopedCliente($request, $slug);
        if (! $cliente) return response()->json(['message' => 'Cliente no encontrado.'], 404);

        $validated = $request->validate([
            'cuotas' => 'required|array|min:1',
            'cuotas.*' => 'integer|exists:cuentas_corrientes,id',
            'id_forma_pago' => 'required|exists:formas_pagos,id',
            'fecha_pago' => 'required|date_format:Y-m-d',
            'comprobante' => 'nullable|image|max:10240',
        ]);

        $cuotas = DB::table('cuentas_corrientes')
            ->where('id_cliente', (int) $cliente->id)
            ->whereIn('id', $validated['cuotas'])
            ->get(['id', 'monto_pagar', 'fecha_pago']);

        if ($cuotas->count() !== count($validated['cuotas'])) {
            return response()->json(['message' => 'Hay cuotas seleccionadas que no pertenecen al cliente.'], 422);
        }

        $comprobantePath = null;
        if ($request->hasFile('comprobante')) {
            $comprobantePath = $request->file('comprobante')->store('comprobantes', 'public');
        }

        DB::transaction(function () use ($cuotas, $validated, $comprobantePath) {
            foreach ($cuotas as $cuota) {
                DB::table('cuentas_corrientes')
                    ->where('id', $cuota->id)
                    ->update([
                        'monto_pagado' => $cuota->monto_pagar,
                        'saldo' => 0,
                        'fecha_pago' => $validated['fecha_pago'],
                        'id_estado_pago' => 2,
                        'id_forma_pago' => (int) $validated['id_forma_pago'],
                        'comprobante' => $comprobantePath,
                        'updated_at' => now(),
                    ]);
            }
        });

        $response = $this->buildAdminClienteCuotasResponse((int) $cliente->id)->getData(true);

        return response()->json([
            'message' => 'Cuotas marcadas como pagadas correctamente.',
            ...$response,
        ]);
    }

    public function adminClienteCuotaPagoParcial(Request $request, string $slug, int $idCuota): JsonResponse
    {
        if ($err = $this->requireAdminOrEntrenador($request)) return $err;

        $cliente = $this->findScopedCliente($request, $slug);
        if (! $cliente) return response()->json(['message' => 'Cliente no encontrado.'], 404);

        $cuota = DB::table('cuentas_corrientes')
            ->where('id', $idCuota)
            ->where('id_cliente', (int) $cliente->id)
            ->first(['id', 'monto', 'monto_pagar', 'monto_pagado', 'comprobante']);

        if (! $cuota) {
            return response()->json(['message' => 'Cuota no encontrada.'], 404);
        }

        $validated = $request->validate([
            'monto' => 'required|numeric|min:0.01',
            'id_forma_pago' => 'required|exists:formas_pagos,id',
            'fecha_pago' => 'required|date_format:Y-m-d',
            'comprobante' => 'nullable|image|max:10240',
        ]);

        $montoAPagar = (float) ($cuota->monto_pagar ?? $cuota->monto ?? 0);
        $montoPagadoActual = (float) ($cuota->monto_pagado ?? 0);
        $nuevoMontoPagado = min($montoAPagar, $montoPagadoActual + (float) $validated['monto']);
        $nuevoSaldo = max(0, round($montoAPagar - $nuevoMontoPagado, 2));
        $quedaPagada = $nuevoSaldo <= 0;

        $comprobantePath = $cuota->comprobante;
        if ($request->hasFile('comprobante')) {
            $comprobantePath = $request->file('comprobante')->store('comprobantes', 'public');
        }

        $idEstadoParcial = DB::table('estados_pagos')->where('slug', 'parcial')->value('id');

        DB::table('cuentas_corrientes')->where('id', $idCuota)->update([
            'monto_pagado' => $nuevoMontoPagado,
            'saldo' => $nuevoSaldo,
            'fecha_pago' => $quedaPagada ? $validated['fecha_pago'] : null,
            'fecha_ultimo_abono' => $validated['fecha_pago'],
            'id_estado_pago' => $quedaPagada ? 2 : ($idEstadoParcial ?? 1),
            'id_forma_pago' => (int) $validated['id_forma_pago'],
            'comprobante' => $comprobantePath,
            'updated_at' => now(),
        ]);

        $response = $this->buildAdminClienteCuotasResponse((int) $cliente->id)->getData(true);

        return response()->json([
            'message' => $quedaPagada ? 'Cuota pagada completamente.' : 'Abono parcial registrado correctamente.',
            'saldo' => $nuevoSaldo,
            ...$response,
        ]);
    }

    // ===================================================================
    // ADMIN — METRICAS POR CLIENTE
    // ===================================================================

    public function adminClienteMetricasIndex(Request $request, string $slug): JsonResponse
    {
        if ($err = $this->requireAdminEntrenadorOrRecepcionista($request)) return $err;

        $cliente = $this->findScopedCliente($request, $slug);
        if (! $cliente) return response()->json(['message' => 'Cliente no encontrado.'], 404);

        return $this->buildMetricasResponse($cliente->id);
    }

    public function adminClienteMetricasStore(Request $request, string $slug): JsonResponse
    {
        if ($err = $this->requireAdminOrEntrenador($request)) return $err;

        $cliente = $this->findScopedCliente($request, $slug);
        if (! $cliente) return response()->json(['message' => 'Cliente no encontrado.'], 404);

        return $this->storeMetricas($request, $cliente->id);
    }

    public function adminClienteEvaluacionInicial(Request $request, string $slug): JsonResponse
    {
        if ($err = $this->requireAdminEntrenadorOrRecepcionista($request)) return $err;

        $cliente = $this->findScopedCliente($request, $slug);
        if (! $cliente) return response()->json(['message' => 'Cliente no encontrado.'], 404);

        return response()->json(array_merge(
            [
                'cliente' => [
                    'id' => (int) $cliente->id,
                    'slug' => $cliente->slug,
                    'nombre_completo' => trim(($cliente->nombres ?? '') . ' ' . ($cliente->paterno ?? '') . ' ' . ($cliente->materno ?? '')),
                    'email' => $cliente->email ?? null,
                ],
            ],
            $this->buildEvaluacionInicialPayload((int) $cliente->id),
        ));
    }

    public function adminClienteEvaluacionInicialStore(Request $request, string $slug): JsonResponse
    {
        if ($err = $this->requireAdminOrEntrenador($request)) return $err;

        $cliente = $this->findScopedCliente($request, $slug);
        if (! $cliente) return response()->json(['message' => 'Cliente no encontrado.'], 404);

        return $this->storeEvaluacionInicialForCliente($request, (int) $cliente->id);
    }

    // ===================================================================
    // ADMIN — EJERCICIOS POR CLIENTE
    // ===================================================================

    public function adminClienteEjerciciosIndex(Request $request, string $slug): JsonResponse
    {
        if ($err = $this->requireAdminEntrenadorOrRecepcionista($request)) return $err;

        $cliente = $this->findScopedCliente($request, $slug);
        if (! $cliente) return response()->json(['message' => 'Cliente no encontrado.'], 404);

        return $this->buildEjerciciosListResponse($cliente->id);
    }

    public function adminClienteEjercicioHistorial(Request $request, string $slug, int $idEjercicio): JsonResponse
    {
        if ($err = $this->requireAdminEntrenadorOrRecepcionista($request)) return $err;

        $cliente = $this->findScopedCliente($request, $slug);
        if (! $cliente) return response()->json(['message' => 'Cliente no encontrado.'], 404);

        return $this->buildEjercicioHistorialResponse($cliente->id, $idEjercicio);
    }

    // ===================================================================
    // ADMIN — MOVIMIENTOS FINANCIEROS
    // ===================================================================

    public function adminMovimientosIndex(Request $request): JsonResponse
    {
        if ($err = $this->requireAdmin($request)) return $err;

        $this->ensureMovimientosTable();

        $q = DB::table('movimientos_financieros')
            ->select('*', DB::raw("'manual' as origen"))
            ->orderBy('fecha', 'desc');
        if ($request->query('desde')) $q->whereDate('fecha', '>=', $request->query('desde'));
        if ($request->query('hasta')) $q->whereDate('fecha', '<=', $request->query('hasta'));
        if ($request->query('tipo'))  $q->where('tipo', $request->query('tipo'));

        $movimientos = $q->get();

        // Los pagos de cuotas son siempre ingresos; no incluir cuando se filtra por egreso
        $incluirPagos = $request->query('tipo') !== 'egreso';
        $pagos = collect();
        if ($incluirPagos) {
            $pq = DB::table('cuentas_corrientes')
                ->join('clientes', 'cuentas_corrientes.id_cliente', '=', 'clientes.id')
                ->whereNotNull('cuentas_corrientes.fecha_pago');
            if ($request->query('desde')) $pq->whereDate('cuentas_corrientes.fecha_pago', '>=', $request->query('desde'));
            if ($request->query('hasta')) $pq->whereDate('cuentas_corrientes.fecha_pago', '<=', $request->query('hasta'));
            $pagos = $pq->select(
                'cuentas_corrientes.id',
                'cuentas_corrientes.monto_pagado',
                'cuentas_corrientes.fecha_pago',
                DB::raw("CONCAT(clientes.nombres,' ',clientes.paterno) as cliente_nombre")
            )
                ->orderBy('cuentas_corrientes.fecha_pago', 'desc')
                ->get()
                ->map(fn($p) => [
                    'id'      => 'pago-' . $p->id,
                    'tipo'    => 'ingreso',
                    'origen'  => 'cuota',
                    'concepto' => 'Cuota: ' . $p->cliente_nombre,
                    'monto'   => (float) $p->monto_pagado,
                    'fecha'   => $p->fecha_pago,
                    'notas'   => null,
                ]);
        }

        $ingresos = $movimientos->where('tipo', 'ingreso')->sum('monto') + $pagos->sum('monto');
        $egresos  = $movimientos->where('tipo', 'egreso')->sum('monto');

        return response()->json([
            'movimientos'    => $movimientos,
            'pagos_clientes' => $pagos,
            'totales'        => [
                'ingresos' => (float) $ingresos,
                'egresos'  => (float) $egresos,
                'balance'  => (float) ($ingresos - $egresos),
            ],
        ]);
    }

    public function adminMovimientosStore(Request $request): JsonResponse
    {
        if ($err = $this->requireAdmin($request)) return $err;

        $this->ensureMovimientosTable();

        $v  = $request->validate([
            'tipo' => 'required|in:ingreso,egreso',
            'descripcion' => 'required_without:concepto|string|max:255',
            'concepto' => 'sometimes|nullable|string|max:255',
            'monto' => 'required|numeric|min:0',
            'fecha' => 'required|date',
        ]);

        $payload = [
            'tipo' => $v['tipo'],
            'descripcion' => trim((string) ($v['descripcion'] ?? $v['concepto'] ?? '')),
            'monto' => $v['monto'],
            'fecha' => $v['fecha'],
            'created_at' => now(),
            'updated_at' => now(),
        ];

        $id = DB::table('movimientos_financieros')->insertGetId($payload);

        return response()->json(['message' => 'Movimiento registrado.', 'id' => $id], 201);
    }

    public function adminMovimientosUpdate(Request $request, int $id): JsonResponse
    {
        if ($err = $this->requireAdmin($request)) return $err;

        $v = $request->validate([
            'tipo' => 'required|in:ingreso,egreso',
            'descripcion' => 'required_without:concepto|string|max:255',
            'concepto' => 'sometimes|nullable|string|max:255',
            'monto' => 'required|numeric|min:0',
            'fecha' => 'required|date',
        ]);

        $payload = [
            'tipo' => $v['tipo'],
            'descripcion' => trim((string) ($v['descripcion'] ?? $v['concepto'] ?? '')),
            'monto' => $v['monto'],
            'fecha' => $v['fecha'],
            'updated_at' => now(),
        ];

        DB::table('movimientos_financieros')->where('id', $id)->update($payload);

        return response()->json(['message' => 'Movimiento actualizado.']);
    }

    public function adminMovimientosDestroy(Request $request, int $id): JsonResponse
    {
        if ($err = $this->requireAdmin($request)) return $err;

        DB::table('movimientos_financieros')->where('id', $id)->delete();

        return response()->json(['message' => 'Movimiento eliminado.']);
    }

    // ===================================================================
    // ADMIN — USUARIOS
    // ===================================================================

    public function adminUsuariosIndex(Request $request): JsonResponse
    {
        if ($err = $this->requireAdminOrEntrenador($request)) return $err;

        $user = $request->user();
        $isSuperAdmin = (int) $user->id_tipo_usuario === 10;
        $idGimnasio = $isSuperAdmin ? (int) $request->query('id_gimnasio', 0) : (int) ($user->id_gimnasio ?? 0);

        $usuariosQ = DB::table('users')
            ->leftJoin('gimnasios', 'users.id_gimnasio', '=', 'gimnasios.id')
            ->orderBy('users.name');

        if ($idGimnasio > 0) {
            $usuariosQ->where('users.id_gimnasio', $idGimnasio);
        }

        $usuarios = $usuariosQ
            ->get(['users.id', 'users.name', 'users.email', 'users.id_tipo_usuario', 'users.titulo', 'users.porcentaje', 'users.individual', 'users.duo', 'users.slug', 'users.created_at', 'users.id_gimnasio', 'gimnasios.nombre as gimnasio'])
            ->map(fn($u) => [
                'id' => (int) $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'id_tipo_usuario' => (int) $u->id_tipo_usuario,
                'titulo' => $u->titulo,
                'porcentaje' => $u->porcentaje,
                'individual' => $u->individual,
                'duo' => $u->duo,
                'slug' => $u->slug,
                'created_at' => $u->created_at,
                'id_gimnasio' => $u->id_gimnasio ? (int) $u->id_gimnasio : null,
                'gimnasio' => $u->gimnasio,
            ]);

        return response()->json(['usuarios' => $usuarios]);
    }

    public function adminUsuariosStore(Request $request): JsonResponse
    {
        if ($err = $this->requireAdminOrEntrenador($request)) return $err;

        $v = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
            'id_tipo_usuario' => 'required|integer|in:1,2,3,4,5',
            'titulo' => 'nullable|string|max:255',
            'porcentaje' => 'nullable|numeric',
            'individual' => 'nullable|integer|min:0',
            'duo' => 'nullable|integer|min:0',
        ]);

        $id = DB::table('users')->insertGetId([
            'name' => $v['name'],
            'email' => $v['email'],
            'password' => Hash::make($v['password']),
            'id_tipo_usuario' => $v['id_tipo_usuario'],
            'titulo' => $v['titulo'] ?? null,
            'porcentaje' => $v['porcentaje'] ?? null,
            'individual' => $v['individual'] ?? null,
            'duo' => $v['duo'] ?? null,
            'slug' => Str::slug($v['name']) . '-' . Str::random(6),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json(['message' => 'Usuario creado.', 'id' => $id], 201);
    }

    public function adminUsuariosUpdate(Request $request, int $id): JsonResponse
    {
        if ($err = $this->requireAdminOrEntrenador($request)) return $err;

        $v = $request->validate([
            'name' => 'required|string|max:255',
            'email' => "required|email|unique:users,email,{$id}",
            'id_tipo_usuario' => 'required|integer|in:1,2,3,4,5',
            'titulo' => 'nullable|string|max:255',
            'individual' => 'nullable|integer|min:0',
            'duo' => 'nullable|integer|min:0',
            'password' => 'nullable|string|min:6',
        ]);

        // 'porcentaje' ya no se edita desde esta pantalla — no se incluye en $data para
        // no sobreescribir con null la comisión ya guardada del entrenador (se sigue
        // usando en DashboardController para la proyección de ingresos). 'individual' y
        // 'duo' sí se editan acá (tarifa por defecto para Pago de entrenadores), así que
        // sí se sobreescriben con lo que venga en la petición.
        $data = [
            'name' => $v['name'],
            'email' => $v['email'],
            'id_tipo_usuario' => $v['id_tipo_usuario'],
            'titulo' => $v['titulo'] ?? null,
            'individual' => $v['individual'] ?? null,
            'duo' => $v['duo'] ?? null,
            'updated_at' => now(),
        ];
        if (! empty($v['password'])) $data['password'] = Hash::make($v['password']);

        DB::table('users')->where('id', $id)->update($data);

        return response()->json(['message' => 'Usuario actualizado.']);
    }

    public function adminUsuariosDestroy(Request $request, int $id): JsonResponse
    {
        if ($err = $this->requireAdmin($request)) return $err;

        DB::table('users')->where('id', $id)->delete();

        return response()->json(['message' => 'Usuario eliminado.']);
    }

    // ===================================================================
    // ADMIN — PLANES
    // ===================================================================

    public function adminPlanesIndex(Request $request): JsonResponse
    {
        if ($err = $this->requireAdminOrEntrenador($request)) return $err;

        $user = $request->user();
        $isSuperAdmin = (int) $user->id_tipo_usuario === 10;
        $idGimnasio = $isSuperAdmin ? (int) $request->query('id_gimnasio', 0) : (int) ($user->id_gimnasio ?? 0);

        $planesQ = DB::table('planes')
            ->leftJoin('gimnasios', 'planes.id_gimnasio', '=', 'gimnasios.id')
            ->orderBy('planes.nombre');

        if ($idGimnasio > 0) {
            $planesQ->where('planes.id_gimnasio', $idGimnasio);
        }

        return response()->json([
            'planes' => $planesQ->get([
                'planes.id',
                'planes.nombre',
                'planes.descripcion',
                'planes.valor',
                'planes.porcentaje',
                'planes.slug',
                'planes.estado',
                'planes.created_at',
                'planes.updated_at',
                'planes.id_gimnasio',
                'gimnasios.nombre as gimnasio',
            ]),
        ]);
    }

    public function adminPlanesStore(Request $request): JsonResponse
    {
        if ($err = $this->requireAdmin($request)) return $err;

        $v = $request->validate([
            'nombre' => 'required|string|max:255',
            'valor' => 'required|numeric|min:0',
            'descripcion' => 'nullable|string',
            'id_gimnasio' => 'nullable|integer|exists:gimnasios,id',
        ]);

        $slug = \Illuminate\Support\Str::slug($v['nombre']);
        // Garantizar unicidad del slug
        $base = $slug;
        $i = 2;
        while (DB::table('planes')->where('slug', $slug)->exists()) {
            $slug = $base . '-' . $i++;
        }

        $idGimnasio = (int) ($v['id_gimnasio'] ?? ($request->user()->id_gimnasio ?? 0));
        $payload = array_merge($v, ['slug' => $slug, 'created_at' => now(), 'updated_at' => now()]);
        if ($idGimnasio > 0) {
            $payload['id_gimnasio'] = $idGimnasio;
        }

        $id = DB::table('planes')->insertGetId($payload);

        return response()->json(['message' => 'Plan creado.', 'id' => $id], 201);
    }

    public function adminPlanesUpdate(Request $request, int $id): JsonResponse
    {
        if ($err = $this->requireAdmin($request)) return $err;

        $v = $request->validate([
            'nombre' => 'required|string|max:255',
            'valor' => 'required|numeric|min:0',
            'descripcion' => 'nullable|string',
            'id_gimnasio' => 'nullable|integer|exists:gimnasios,id',
        ]);

        $slug = \Illuminate\Support\Str::slug($v['nombre']);
        // Garantizar unicidad del slug (excluyendo el plan actual)
        $base = $slug;
        $i = 2;
        while (DB::table('planes')->where('slug', $slug)->where('id', '!=', $id)->exists()) {
            $slug = $base . '-' . $i++;
        }

        $payload = array_merge($v, ['slug' => $slug, 'updated_at' => now()]);
        if (! array_key_exists('id_gimnasio', $payload) || ! $payload['id_gimnasio']) {
            $payload['id_gimnasio'] = $request->user()->id_gimnasio ?? null;
        }

        DB::table('planes')->where('id', $id)->update($payload);

        return response()->json(['message' => 'Plan actualizado.']);
    }

    public function adminPlanesDestroy(Request $request, int $id): JsonResponse
    {
        if ($err = $this->requireAdmin($request)) return $err;

        if (DB::table('clientes')->where('id_plan', $id)->where('estado', 1)->exists()) {
            return response()->json(['message' => 'No se puede eliminar: hay clientes activos con este plan.'], 422);
        }

        DB::table('planes')->where('id', $id)->delete();

        return response()->json(['message' => 'Plan eliminado.']);
    }

    // ===================================================================
    // ADMIN — GIMNASIOS
    // ===================================================================

    public function adminGimnasiosIndex(Request $request): JsonResponse
    {
        if ($err = $this->requireAdmin($request)) return $err;

        $gimnasios = DB::table('gimnasios')
            ->orderByDesc('estado')
            ->orderBy('nombre')
            ->get([
                'id',
                'nombre',
                'slug',
                'direccion',
                'descripcion',
                'telefono',
                'correo_electronico',
                'color_primario',
                'color_secundario',
                'email_encabezado',
                'email_firma',
                'email_pie',
                'estado',
                'features',
                'plan',
                'bloqueado',
                'bloqueado_motivo',
            ])
            ->map(function ($gimnasio) {
                $gimnasio->features = Gimnasios::featuresActivas((int) $gimnasio->id);
                $gimnasio->bloqueado = (bool) $gimnasio->bloqueado;
                $gimnasio->facturacion = $this->facturacionResumen((int) $gimnasio->id);
                return $gimnasio;
            });

        return response()->json(['gimnasios' => $gimnasios]);
    }

    public function adminGimnasiosStore(Request $request): JsonResponse
    {
        if ($err = $this->requireAdmin($request)) return $err;

        $v = $request->validate([
            'nombre' => 'required|string|max:255|unique:gimnasios,nombre',
            'direccion' => 'nullable|string|max:255',
            'descripcion' => 'nullable|string|max:2000',
            'telefono' => 'nullable|string|max:50',
            'correo_electronico' => 'nullable|email|max:255',
            'color_primario' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'color_secundario' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'email_encabezado' => 'nullable|string|max:255',
            'email_firma' => 'nullable|string|max:255',
            'email_pie' => 'nullable|string|max:2000',
            'estado' => 'required|in:0,1',
        ]);

        $id = DB::table('gimnasios')->insertGetId([
            'nombre' => $v['nombre'],
            'slug' => Str::slug($v['nombre']) . '-' . Str::random(6),
            'direccion' => $v['direccion'] ?? null,
            'descripcion' => $v['descripcion'] ?? null,
            'telefono' => $v['telefono'] ?? null,
            'correo_electronico' => $v['correo_electronico'] ?? null,
            'color_primario' => $v['color_primario'] ?? null,
            'color_secundario' => $v['color_secundario'] ?? null,
            'email_encabezado' => $v['email_encabezado'] ?? null,
            'email_firma' => $v['email_firma'] ?? null,
            'email_pie' => $v['email_pie'] ?? null,
            'estado' => (int) $v['estado'],
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json(['message' => 'Gimnasio creado.', 'id' => $id], 201);
    }

    public function adminGimnasiosUpdate(Request $request, int $id): JsonResponse
    {
        if ($err = $this->requireAdmin($request)) return $err;

        $v = $request->validate([
            'nombre' => 'required|string|max:255|unique:gimnasios,nombre,' . $id,
            'direccion' => 'nullable|string|max:255',
            'descripcion' => 'nullable|string|max:2000',
            'telefono' => 'nullable|string|max:50',
            'correo_electronico' => 'nullable|email|max:255',
            'color_primario' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'color_secundario' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'email_encabezado' => 'nullable|string|max:255',
            'email_firma' => 'nullable|string|max:255',
            'email_pie' => 'nullable|string|max:2000',
            'estado' => 'required|in:0,1',
        ]);

        DB::table('gimnasios')->where('id', $id)->update([
            'nombre' => $v['nombre'],
            'slug' => Str::slug($v['nombre']),
            'direccion' => $v['direccion'] ?? null,
            'descripcion' => $v['descripcion'] ?? null,
            'telefono' => $v['telefono'] ?? null,
            'correo_electronico' => $v['correo_electronico'] ?? null,
            'color_primario' => $v['color_primario'] ?? null,
            'color_secundario' => $v['color_secundario'] ?? null,
            'email_encabezado' => $v['email_encabezado'] ?? null,
            'email_firma' => $v['email_firma'] ?? null,
            'email_pie' => $v['email_pie'] ?? null,
            'estado' => (int) $v['estado'],
            'updated_at' => now(),
        ]);

        return response()->json(['message' => 'Gimnasio actualizado.']);
    }

    public function adminGimnasiosDestroy(Request $request, int $id): JsonResponse
    {
        if ($err = $this->requireAdmin($request)) return $err;

        $tieneRelaciones = DB::table('clientes')->where('id_gimnasio', $id)->exists()
            || DB::table('agendas')->where('id_gimnasio', $id)->exists()
            || DB::table('movimientos_financieros')->where('id_gimnasio', $id)->exists()
            || DB::table('planes')->where('id_gimnasio', $id)->exists()
            || DB::table('users')->where('id_gimnasio', $id)->exists();

        if ($tieneRelaciones) {
            return response()->json(['message' => 'No se puede eliminar el gimnasio porque tiene información asociada.'], 422);
        }

        DB::table('gimnasios')->where('id', $id)->delete();

        return response()->json(['message' => 'Gimnasio eliminado.']);
    }

    public function adminGimnasiosFeaturesUpdate(Request $request, int $id): JsonResponse
    {
        // Administrable exclusivamente por el super-admin, a diferencia del resto
        // del CRUD de gimnasios (requireAdmin permite también admin=1).
        if ((int) ($request->user()->id_tipo_usuario ?? 0) !== 10) {
            return response()->json(['message' => 'No autorizado.'], 403);
        }

        $v = $request->validate([
            'features' => 'required|array',
            ...array_fill_keys(
                array_map(fn($key) => "features.{$key}", Gimnasios::FEATURE_KEYS),
                'sometimes|boolean',
            ),
        ]);

        $gimnasio = Gimnasios::find($id);
        if (! $gimnasio) {
            return response()->json(['message' => 'Gimnasio no encontrado.'], 404);
        }

        $features = [];
        foreach (Gimnasios::FEATURE_KEYS as $key) {
            $features[$key] = (bool) ($v['features'][$key] ?? false);
        }

        $gimnasio->update(['features' => $features]);

        return response()->json(['message' => 'Funcionalidades actualizadas.', 'features' => $features]);
    }

    public function adminGimnasiosPlanUpdate(Request $request, int $id): JsonResponse
    {
        // Mismo guardado exclusivo de super-admin que adminGimnasiosFeaturesUpdate.
        if ((int) ($request->user()->id_tipo_usuario ?? 0) !== 10) {
            return response()->json(['message' => 'No autorizado.'], 403);
        }

        $v = $request->validate([
            'plan' => 'nullable|in:' . implode(',', Gimnasios::PLAN_TIERS),
        ]);

        $gimnasio = Gimnasios::find($id);
        if (! $gimnasio) {
            return response()->json(['message' => 'Gimnasio no encontrado.'], 404);
        }

        $plan = $v['plan'] ?? null;
        $preset = Gimnasios::featurePresetForPlan($plan);

        $update = ['plan' => $plan, 'bloqueado' => false, 'bloqueado_motivo' => null];
        if ($preset !== null) {
            $features = [];
            foreach (Gimnasios::FEATURE_KEYS as $key) {
                $features[$key] = in_array($key, $preset, true);
            }
            $update['features'] = $features;
        }

        $gimnasio->update($update);

        // Cambiar de plan cierra el ciclo de facturación a medio empezar (si había uno
        // sin pagar) y abre uno nuevo — el trial dura 7 días, los planes pagos 1 mes.
        GimnasioFacturacion::where('id_gimnasio', $id)->whereNull('fecha_pago')->delete();

        if (in_array($plan, Gimnasios::PLAN_TIERS, true)) {
            $inicio = now()->toDateString();
            GimnasioFacturacion::create([
                'id_gimnasio' => $id,
                'plan' => $plan,
                'monto' => (int) (PlanPreset::where('plan', $plan)->value('precio_mensual') ?? 0),
                'fecha_inicio' => $inicio,
                'fecha_vencimiento' => $plan === 'trial'
                    ? now()->addDays(7)->toDateString()
                    : now()->addMonth()->toDateString(),
                'fecha_pago' => null,
                'id_estado_pago' => $this->resolveEstadoPagoId('pendiente'),
            ]);
        }

        return response()->json([
            'message' => 'Plan actualizado.',
            'plan' => $gimnasio->plan,
            'features' => Gimnasios::featuresActivas($id),
            'facturacion' => $this->facturacionResumen($id),
        ]);
    }

    public function adminGimnasioMarcarPago(Request $request, int $id): JsonResponse
    {
        if ((int) ($request->user()->id_tipo_usuario ?? 0) !== 10) {
            return response()->json(['message' => 'No autorizado.'], 403);
        }

        $gimnasio = Gimnasios::find($id);
        if (! $gimnasio) {
            return response()->json(['message' => 'Gimnasio no encontrado.'], 404);
        }

        $pendiente = Gimnasios::facturacionVigente($id);
        if (! $pendiente) {
            return response()->json(['message' => 'No hay una facturación pendiente para este gimnasio.'], 422);
        }

        $hoy = now()->toDateString();
        $pendiente->update([
            'fecha_pago' => $hoy,
            'id_estado_pago' => $this->resolveEstadoPagoId('pagado'),
        ]);

        GimnasioFacturacion::create([
            'id_gimnasio' => $id,
            'plan' => $gimnasio->plan,
            'monto' => (int) (PlanPreset::where('plan', $gimnasio->plan)->value('precio_mensual') ?? 0),
            'fecha_inicio' => $pendiente->fecha_vencimiento,
            'fecha_vencimiento' => \Carbon\Carbon::parse($pendiente->fecha_vencimiento)->addMonth()->toDateString(),
            'fecha_pago' => null,
            'id_estado_pago' => $this->resolveEstadoPagoId('pendiente'),
        ]);

        if ($gimnasio->bloqueado) {
            $gimnasio->update(['bloqueado' => false, 'bloqueado_motivo' => null]);
        }

        return response()->json([
            'message' => 'Pago registrado.',
            'facturacion' => $this->facturacionResumen($id),
        ]);
    }

    public function adminGimnasioBloquear(Request $request, int $id): JsonResponse
    {
        if ((int) ($request->user()->id_tipo_usuario ?? 0) !== 10) {
            return response()->json(['message' => 'No autorizado.'], 403);
        }

        $gimnasio = Gimnasios::find($id);
        if (! $gimnasio) {
            return response()->json(['message' => 'Gimnasio no encontrado.'], 404);
        }

        $gimnasio->update(['bloqueado' => true, 'bloqueado_motivo' => 'pago_vencido_manual']);

        return response()->json(['message' => 'Gimnasio bloqueado.']);
    }

    public function adminGimnasioDesbloquear(Request $request, int $id): JsonResponse
    {
        if ((int) ($request->user()->id_tipo_usuario ?? 0) !== 10) {
            return response()->json(['message' => 'No autorizado.'], 403);
        }

        $gimnasio = Gimnasios::find($id);
        if (! $gimnasio) {
            return response()->json(['message' => 'Gimnasio no encontrado.'], 404);
        }

        $gimnasio->update(['bloqueado' => false, 'bloqueado_motivo' => null]);

        return response()->json(['message' => 'Gimnasio desbloqueado.']);
    }

    private function resolveEstadoPagoId(string $slug): int
    {
        return (int) (EstadosPagos::where('slug', $slug)->value('id') ?? 1);
    }

    private function facturacionResumen(int $idGimnasio): ?array
    {
        $pendiente = Gimnasios::facturacionVigente($idGimnasio);
        if (! $pendiente) {
            return null;
        }

        $hoy = now()->toDateString();

        return [
            'fecha_vencimiento' => $pendiente->fecha_vencimiento->toDateString(),
            'monto' => (int) $pendiente->monto,
            'estado' => $pendiente->fecha_vencimiento->toDateString() < $hoy ? 'vencido' : 'pendiente',
        ];
    }

    // ===================================================================
    // ADMIN — COMPOSICIÓN DE PLANES COMERCIALES (super-admin)
    // ===================================================================

    public function adminPlanesComercialesIndex(Request $request): JsonResponse
    {
        if ((int) ($request->user()->id_tipo_usuario ?? 0) !== 10) {
            return response()->json(['message' => 'No autorizado.'], 403);
        }

        $presets = PlanPreset::whereIn('plan', Gimnasios::PLAN_TIERS)->get()->keyBy('plan');
        $conteoPorPlan = DB::table('gimnasios')
            ->whereIn('plan', Gimnasios::PLAN_TIERS)
            ->select('plan', DB::raw('count(*) as total'))
            ->groupBy('plan')
            ->pluck('total', 'plan');

        $planes = [];
        foreach (Gimnasios::PLAN_TIERS as $plan) {
            $stored = $presets->get($plan)?->features ?? [];
            $features = [];
            foreach (Gimnasios::FEATURE_KEYS as $key) {
                $features[$key] = (bool) ($stored[$key] ?? false);
            }

            $planes[] = [
                'plan' => $plan,
                'features' => $features,
                'precio_mensual' => (int) ($presets->get($plan)?->precio_mensual ?? 0),
                'gimnasios_count' => (int) ($conteoPorPlan[$plan] ?? 0),
            ];
        }

        return response()->json(['planes' => $planes]);
    }

    public function adminPlanesComercialesUpdate(Request $request, string $plan): JsonResponse
    {
        if ((int) ($request->user()->id_tipo_usuario ?? 0) !== 10) {
            return response()->json(['message' => 'No autorizado.'], 403);
        }

        if (! in_array($plan, Gimnasios::PLAN_TIERS, true)) {
            return response()->json(['message' => 'Plan inválido.'], 404);
        }

        $v = $request->validate([
            'features' => 'required|array',
            ...array_fill_keys(
                array_map(fn($key) => "features.{$key}", Gimnasios::FEATURE_KEYS),
                'sometimes|boolean',
            ),
            'precio_mensual' => 'sometimes|integer|min:0',
        ]);

        $features = [];
        foreach (Gimnasios::FEATURE_KEYS as $key) {
            $features[$key] = (bool) ($v['features'][$key] ?? false);
        }

        // El plan "trial" es gratuito por definición — se ignora cualquier precio que
        // llegue del cliente para ese plan en particular, en vez de validarlo aparte.
        $precioMensual = $plan === 'trial' ? 0 : (int) ($v['precio_mensual'] ?? 0);

        PlanPreset::updateOrCreate(['plan' => $plan], [
            'features' => $features,
            'precio_mensual' => $precioMensual,
        ]);

        return response()->json([
            'message' => 'Composición del plan actualizada. Los gimnasios ya asignados a este plan no cambian hasta que se aplique explícitamente.',
            'plan' => $plan,
            'features' => $features,
            'precio_mensual' => $precioMensual,
        ]);
    }

    public function adminPlanesComercialesAplicar(Request $request, string $plan): JsonResponse
    {
        if ((int) ($request->user()->id_tipo_usuario ?? 0) !== 10) {
            return response()->json(['message' => 'No autorizado.'], 403);
        }

        if (! in_array($plan, Gimnasios::PLAN_TIERS, true)) {
            return response()->json(['message' => 'Plan inválido.'], 404);
        }

        $preset = Gimnasios::featurePresetForPlan($plan) ?? [];
        $features = [];
        foreach (Gimnasios::FEATURE_KEYS as $key) {
            $features[$key] = in_array($key, $preset, true);
        }

        $afectados = DB::table('gimnasios')
            ->where('plan', $plan)
            ->update(['features' => json_encode($features), 'updated_at' => now()]);

        return response()->json([
            'message' => "Composición aplicada a {$afectados} gimnasio(s) en el plan \"{$plan}\".",
            'gimnasios_afectados' => $afectados,
        ]);
    }

    // ===================================================================
    // ADMIN — PAGOS A ENTRENADORES (feature flag: pagos_entrenadores)
    // ===================================================================

    private function resolveGimnasioScopeForAdmin(object $user): ?int
    {
        $isSuperAdmin = (int) $user->id_tipo_usuario === 10;
        $idGimnasio = $isSuperAdmin ? (int) request()->query('id_gimnasio', 0) : (int) ($user->id_gimnasio ?? 0);

        return $idGimnasio > 0 ? $idGimnasio : null;
    }

    private function entrenadorPerteneceAlGimnasio(int $entrenadorId, ?int $idGimnasio): bool
    {
        $query = DB::table('users')->where('id', $entrenadorId)->where('id_tipo_usuario', 2);
        if ($idGimnasio !== null) {
            $query->where('id_gimnasio', $idGimnasio);
        }

        return $query->exists();
    }

    private function resolverTarifaEntrenador(int $entrenadorId, int $year, int $month): array
    {
        $tarifa = DB::table('historial_tarifas_entrenadores')
            ->where('entrenador_id', $entrenadorId)
            ->where('year', $year)
            ->where('month', $month)
            ->first(['individual', 'duo']);

        if ($tarifa) {
            return ['individual' => (float) $tarifa->individual, 'duo' => (float) $tarifa->duo];
        }

        $entrenador = DB::table('users')->where('id', $entrenadorId)->first(['individual', 'duo']);
        $individual = (float) ($entrenador->individual ?? 0);
        $duo = (float) ($entrenador->duo ?? 0);

        DB::table('historial_tarifas_entrenadores')->insert([
            'entrenador_id' => $entrenadorId,
            'year' => $year,
            'month' => $month,
            'individual' => $individual,
            'duo' => $duo,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return ['individual' => $individual, 'duo' => $duo];
    }

    public function adminPagosEntrenadoresIndex(Request $request): JsonResponse
    {
        if ($err = $this->requireAdmin($request)) return $err;

        $user = $request->user();
        $isSuperAdmin = (int) $user->id_tipo_usuario === 10;
        $idGimnasio = $this->resolveGimnasioScopeForAdmin($user);

        $month = (int) $request->query('month', now()->month);
        $year = (int) $request->query('year', now()->year);

        $entrenadoresQuery = DB::table('users')->where('id_tipo_usuario', 2);
        if ($idGimnasio !== null) {
            $entrenadoresQuery->where('id_gimnasio', $idGimnasio);
        } elseif (! $isSuperAdmin) {
            $entrenadoresQuery->whereRaw('1 = 0');
        }
        $entrenadores = $entrenadoresQuery->orderBy('name')->get(['id', 'name', 'individual', 'duo']);
        $entrenadorIds = $entrenadores->pluck('id');

        $tarifas = DB::table('historial_tarifas_entrenadores')
            ->where('year', $year)
            ->where('month', $month)
            ->whereIn('entrenador_id', $entrenadorIds)
            ->get()
            ->keyBy('entrenador_id');

        $entrenadoresPayload = $entrenadores->map(function ($e) use ($tarifas) {
            $tarifa = $tarifas->get($e->id);
            return [
                'id' => (int) $e->id,
                'label' => $e->name,
                'valor_individual' => (float) ($tarifa->individual ?? $e->individual ?? 0),
                'valor_duo' => (float) ($tarifa->duo ?? $e->duo ?? 0),
            ];
        })->values();

        $pagos = DB::table('pagos_entrenadores')
            ->join('users', 'users.id', '=', 'pagos_entrenadores.entrenador_id')
            ->where('pagos_entrenadores.year', $year)
            ->where('pagos_entrenadores.month', $month)
            ->whereIn('pagos_entrenadores.entrenador_id', $entrenadorIds)
            ->orderBy('users.name')
            ->get([
                'pagos_entrenadores.id',
                'pagos_entrenadores.entrenador_id',
                'users.name as entrenador_nombre',
                'pagos_entrenadores.month',
                'pagos_entrenadores.year',
                'pagos_entrenadores.sesiones_individual',
                'pagos_entrenadores.sesiones_duo',
                'pagos_entrenadores.valor_individual',
                'pagos_entrenadores.valor_duo',
                'pagos_entrenadores.bono',
                'pagos_entrenadores.descuento',
                'pagos_entrenadores.total',
            ]);

        return response()->json([
            'month' => $month,
            'year' => $year,
            'entrenadores' => $entrenadoresPayload,
            'pagos' => $pagos,
        ]);
    }

    public function adminPagosEntrenadoresStore(Request $request): JsonResponse
    {
        if ($err = $this->requireAdmin($request)) return $err;

        $v = $request->validate([
            'entrenador_id' => 'required|integer',
            'month' => 'required|integer|min:1|max:12',
            'year' => 'required|integer|min:2000|max:2100',
            'sesiones_individual' => 'nullable|integer|min:0',
            'sesiones_duo' => 'nullable|integer|min:0',
            'bono' => 'nullable|numeric|min:0',
            'descuento' => 'nullable|numeric|min:0',
        ]);

        $idGimnasio = $this->resolveGimnasioScopeForAdmin($request->user());
        if (! $this->entrenadorPerteneceAlGimnasio((int) $v['entrenador_id'], $idGimnasio)) {
            return response()->json(['message' => 'Entrenador no encontrado.'], 404);
        }

        $sesIndividual = (int) ($v['sesiones_individual'] ?? 0);
        $sesDuo = (int) ($v['sesiones_duo'] ?? 0);
        $bono = (float) ($v['bono'] ?? 0);
        $descuento = (float) ($v['descuento'] ?? 0);

        $tarifa = $this->resolverTarifaEntrenador((int) $v['entrenador_id'], (int) $v['year'], (int) $v['month']);
        $total = round($sesIndividual * $tarifa['individual'] + $sesDuo * $tarifa['duo'] + $bono - $descuento, 2);

        $id = DB::table('pagos_entrenadores')->updateOrInsert(
            [
                'entrenador_id' => $v['entrenador_id'],
                'year' => $v['year'],
                'month' => $v['month'],
            ],
            [
                'sesiones_individual' => $sesIndividual,
                'sesiones_duo' => $sesDuo,
                'valor_individual' => $tarifa['individual'],
                'valor_duo' => $tarifa['duo'],
                'bono' => $bono,
                'descuento' => $descuento,
                'total' => $total,
                'updated_at' => now(),
                'created_at' => now(),
            ],
        );

        $pagoId = DB::table('pagos_entrenadores')
            ->where('entrenador_id', $v['entrenador_id'])
            ->where('year', $v['year'])
            ->where('month', $v['month'])
            ->value('id');

        return response()->json(['message' => 'Pago registrado correctamente.', 'id' => (int) $pagoId], 201);
    }

    public function adminPagosEntrenadoresUpdate(Request $request, int $id): JsonResponse
    {
        if ($err = $this->requireAdmin($request)) return $err;

        $pago = DB::table('pagos_entrenadores')->where('id', $id)->first();
        if (! $pago) {
            return response()->json(['message' => 'Pago no encontrado.'], 404);
        }

        $idGimnasio = $this->resolveGimnasioScopeForAdmin($request->user());
        if (! $this->entrenadorPerteneceAlGimnasio((int) $pago->entrenador_id, $idGimnasio)) {
            return response()->json(['message' => 'Pago no encontrado.'], 404);
        }

        $v = $request->validate([
            'sesiones_individual' => 'nullable|integer|min:0',
            'sesiones_duo' => 'nullable|integer|min:0',
            'bono' => 'nullable|numeric|min:0',
            'descuento' => 'nullable|numeric|min:0',
        ]);

        $sesIndividual = (int) ($v['sesiones_individual'] ?? 0);
        $sesDuo = (int) ($v['sesiones_duo'] ?? 0);
        $bono = (float) ($v['bono'] ?? 0);
        $descuento = (float) ($v['descuento'] ?? 0);

        $tarifa = $this->resolverTarifaEntrenador((int) $pago->entrenador_id, (int) $pago->year, (int) $pago->month);
        $total = round($sesIndividual * $tarifa['individual'] + $sesDuo * $tarifa['duo'] + $bono - $descuento, 2);

        DB::table('pagos_entrenadores')->where('id', $id)->update([
            'sesiones_individual' => $sesIndividual,
            'sesiones_duo' => $sesDuo,
            'valor_individual' => $tarifa['individual'],
            'valor_duo' => $tarifa['duo'],
            'bono' => $bono,
            'descuento' => $descuento,
            'total' => $total,
            'updated_at' => now(),
        ]);

        return response()->json(['message' => 'Pago actualizado correctamente.']);
    }

    public function adminPagosEntrenadoresDestroy(Request $request, int $id): JsonResponse
    {
        if ($err = $this->requireAdmin($request)) return $err;

        $pago = DB::table('pagos_entrenadores')->where('id', $id)->first();
        if (! $pago) {
            return response()->json(['message' => 'Pago no encontrado.'], 404);
        }

        $idGimnasio = $this->resolveGimnasioScopeForAdmin($request->user());
        if (! $this->entrenadorPerteneceAlGimnasio((int) $pago->entrenador_id, $idGimnasio)) {
            return response()->json(['message' => 'Pago no encontrado.'], 404);
        }

        DB::table('pagos_entrenadores')->where('id', $id)->delete();

        return response()->json(['message' => 'Pago eliminado correctamente.']);
    }

    // ===================================================================
    // ADMIN — EVALUACIONES (resumen general y mis evaluaciones)
    // ===================================================================

    public function adminEvaluacionesResumen(Request $request): JsonResponse
    {
        if ($err = $this->requireAdminOrEntrenador($request)) return $err;

        // --- Entrenadores ---
        $trainerIds = DB::table('users')->where('id_tipo_usuario', 2)->pluck('id');

        $entrenadores = [];
        foreach ($trainerIds as $trainerId) {
            $trainer = DB::table('users')->where('id', $trainerId)->first(['id', 'name']);
            $evals   = DB::table('encuesta_satisfaccions')
                ->where('id_entrenador', $trainerId)
                ->get(['profesionalismo', 'claridad', 'motivacion', 'disponibilidad', 'puntualidad', 'valoracion_global', 'destacaria', 'sugerencias']);

            if ($evals->isEmpty()) continue;

            $total           = $evals->count();
            $promedioGeneral = round($evals->avg('valoracion_global') / 2, 2);
            $preguntas       = $this->buildEncuestaEntrenadorPreguntas($evals);

            $entrenadores[] = [
                'entrenador_id'      => (int) $trainerId,
                'entrenador_nombre'  => $trainer->name,
                'total_evaluaciones' => $total,
                'promedio_general'   => $promedioGeneral,
                'preguntas'          => $preguntas,
            ];
        }

        $gimnasio = $this->buildGimnasioResumen();

        return response()->json([
            'entrenadores' => $entrenadores,
            'gimnasio'     => $gimnasio,
        ]);
    }

    public function adminMisEvaluaciones(Request $request): JsonResponse
    {
        if ($err = $this->requireAdminOrEntrenador($request)) return $err;

        $user  = $request->user();
        $evals = DB::table('encuesta_satisfaccions')
            ->where('id_entrenador', $user->id)
            ->get(['profesionalismo', 'claridad', 'motivacion', 'disponibilidad', 'puntualidad', 'valoracion_global', 'destacaria', 'sugerencias']);

        $total = $evals->count();

        return response()->json([
            'total_evaluaciones' => $total,
            'promedio_general'   => $total > 0 ? round($evals->avg('valoracion_global') / 2, 2) : null,
            'preguntas'          => $total > 0 ? $this->buildEncuestaEntrenadorPreguntas($evals) : [],
        ]);
    }

    private function buildEncuestaEntrenadorPreguntas(\Illuminate\Support\Collection $evals): array
    {
        $campos = [
            'profesionalismo' => 'Profesionalismo',
            'claridad'        => 'Claridad',
            'motivacion'      => 'Motivación',
            'disponibilidad'  => 'Disponibilidad',
            'puntualidad'     => 'Puntualidad',
        ];

        $preguntas = [];
        foreach ($campos as $campo => $label) {
            $preguntas[] = [
                'pregunta' => $label,
                'tipo'     => 'rating',
                'promedio' => round((float) ($evals->avg($campo) ?? 0), 2),
            ];
        }

        $destacaria = $evals->pluck('destacaria')->filter()->values()->all();
        if (! empty($destacaria)) {
            $preguntas[] = ['pregunta' => '¿Qué destacarías?', 'tipo' => 'texto', 'respuestas' => $destacaria];
        }

        $sugerencias = $evals->pluck('sugerencias')->filter()->values()->all();
        if (! empty($sugerencias)) {
            $preguntas[] = ['pregunta' => 'Sugerencias', 'tipo' => 'texto', 'respuestas' => $sugerencias];
        }

        return $preguntas;
    }

    private function buildGimnasioResumen(): array
    {
        $responses  = DB::table('survey_responses')->get();
        $total      = $responses->count();
        $preguntas  = [];

        if ($total > 0) {
            $timeLabels = [
                'less_1_month'    => 'Menos de 1 mes',
                '1_to_3_months'   => '1 a 3 meses',
                '3_to_6_months'   => '3 a 6 meses',
                'more_6_months'   => 'Más de 6 meses',
                'more_1_year'     => 'Más de 1 año',
            ];
            $tiempos = $responses->groupBy('training_time')
                ->map(fn($g, $k) => ['label' => $timeLabels[$k] ?? $k, 'count' => $g->count()])
                ->values()->all();
            $preguntas[] = ['pregunta' => 'Tiempo de entrenamiento', 'tipo' => 'opcion', 'opciones' => $tiempos];

            $reasons = $responses->pluck('nps_reason')->filter()->values()->all();
            if (! empty($reasons)) {
                $preguntas[] = ['pregunta' => 'Motivo NPS', 'tipo' => 'texto', 'respuestas' => $reasons];
            }

            $servqualCampos = [
                'tangibles'        => 'Instalaciones',
                'reliability'      => 'Confiabilidad',
                'responsiveness'   => 'Capacidad de respuesta',
                'security'         => 'Seguridad',
                'empathy'          => 'Empatía',
            ];
            $valorLabels = [
                'excellent'          => 'Excelente',
                'very_good'          => 'Muy bueno',
                'good'               => 'Bueno',
                'needs_improvement'  => 'Mejorable',
                'poor'               => 'Deficiente',
            ];
            foreach ($servqualCampos as $campo => $label) {
                $opciones = $responses
                    ->map(fn($r) => data_get(json_decode($r->servqual_ratings ?? '{}', true), $campo))
                    ->filter()
                    ->groupBy(fn($v) => $v)
                    ->map(fn($g, $k) => ['label' => $valorLabels[$k] ?? $k, 'count' => $g->count()])
                    ->values()->all();
                if (! empty($opciones)) {
                    $preguntas[] = ['pregunta' => $label, 'tipo' => 'opcion', 'opciones' => $opciones];
                }
            }
        }

        return [
            'total_evaluaciones' => $total,
            'promedio_general'   => $total > 0 ? round($responses->avg('nps_score') / 2, 2) : null,
            'preguntas'          => $preguntas,
        ];
    }

    public function clientePortada(Request $request): JsonResponse
    {
        return (new ClienteController())->portada($request);
    }

    public function clienteCuotas(Request $request): JsonResponse
    {
        return (new ClienteController())->cuotas($request);
    }

    public function clientePesos(Request $request): JsonResponse
    {
        return (new ClienteController())->pesos($request);
    }

    public function clienteAgenda(Request $request): JsonResponse
    {
        return (new ClienteController())->agenda($request);
    }

    public function clienteGamificacion(Request $request): JsonResponse
    {
        return (new ClienteController())->gamificacion($request);
    }

    public function clienteEnviarReportePdf(Request $request): JsonResponse
    {
        $clienteObj = $this->getClientePorUsuario($request->user());
        if (! $clienteObj) {
            return response()->json(['message' => 'Perfil de cliente no encontrado.'], 404);
        }

        $cliente = Clientes::query()->find($clienteObj->id);
        if (! $cliente) {
            return response()->json(['message' => 'Cliente no encontrado.'], 404);
        }

        if (! $cliente->email) {
            return response()->json([
                'message' => 'No tienes un correo electrónico registrado.',
            ], 422);
        }

        try {
            (new \App\Http\Controllers\ClientesController())->generarYEnviarReportePdf($cliente);
            return response()->json(['message' => 'Reporte enviado con éxito a tu correo.']);
        } catch (\Exception $e) {
            \Log::error("Error enviando reporte PDF para cliente: " . $e->getMessage());
            return response()->json(['message' => 'Error al generar o enviar el reporte: ' . $e->getMessage()], 500);
        }
    }

    public function clienteAgendaCalendario(Request $request): JsonResponse
    {
        $cliente = $this->getClientePorUsuario($request->user());
        if (! $cliente) return response()->json(['message' => 'Perfil de cliente no encontrado.'], 404);

        $desde = $request->query('desde', now()->startOfMonth()->format('Y-m-d'));
        $hasta = $request->query('hasta', now()->endOfMonth()->format('Y-m-d'));

        $sesiones = DB::table('agendas')
            ->leftJoin('users', 'agendas.id_usuario', '=', 'users.id')
            ->leftJoin('entrenadores_perfiles', 'users.id', '=', 'entrenadores_perfiles.id_entrenador')
            ->where('agendas.id_cliente', $cliente->id)
            ->whereBetween(DB::raw('DATE(agendas.fecha_inicio)'), [$desde, $hasta])
            ->orderBy('agendas.fecha_inicio')
            ->get([
                'agendas.id',
                'agendas.titulo',
                'agendas.fecha_inicio',
                'agendas.fecha_fin',
                'agendas.estado',
                'agendas.descripcion',
                'users.name as entrenador_nombre',
                'entrenadores_perfiles.instagram as entrenador_instagram',
                'entrenadores_perfiles.foto as entrenador_foto',
            ]);

        $ids = $sesiones->pluck('id')->toArray();
        $ejerciciosMap = [];
        if (! empty($ids)) {
            $rows = DB::table('agendas_ejercicios')
                ->join('ejercicios', 'ejercicios.id', '=', 'agendas_ejercicios.id_ejercicio')
                ->leftJoin('tipos_ejercicios', 'tipos_ejercicios.id', '=', 'ejercicios.id_tipo')
                ->leftJoin('grupos_musculares', 'grupos_musculares.id', '=', 'tipos_ejercicios.id_grupo')
                ->whereIn('agendas_ejercicios.id_agenda', $ids)
                ->orderBy('agendas_ejercicios.orden')
                ->get([
                    'agendas_ejercicios.id_agenda',
                    'agendas_ejercicios.id_ejercicio',
                    'ejercicios.nombre',
                    'agendas_ejercicios.serie',
                    'agendas_ejercicios.repeticiones',
                    'agendas_ejercicios.carga',
                    'agendas_ejercicios.descanso',
                    'grupos_musculares.nombre as grupo_muscular',
                    'grupos_musculares.color as color_grupo',
                    'grupos_musculares.icono as icono_grupo',
                ]);
            foreach ($rows as $r) {
                $ejerciciosMap[$r->id_agenda][] = [
                    'id_ejercicio' => $r->id_ejercicio ? (int) $r->id_ejercicio : null,
                    'nombre' => $r->nombre,
                    'serie' => $r->serie,
                    'repeticiones' => $r->repeticiones,
                    'carga' => $r->carga,
                    'descanso' => $r->descanso,
                    'grupo_muscular' => $r->grupo_muscular ?? 'General',
                    'color_grupo' => $r->color_grupo,
                    'icono_grupo' => $r->icono_grupo,
                ];
            }
        }

        return response()->json([
            'sesiones' => $sesiones->map(fn($s) => [
                'id' => (int) $s->id,
                'titulo' => $s->titulo,
                'descripcion' => $s->descripcion,
                'fecha_inicio' => $s->fecha_inicio,
                'fecha_fin' => $s->fecha_fin,
                'estado' => $s->estado !== null ? (int) $s->estado : null,
                'id_estado' => $s->estado !== null ? (int) $s->estado : null,
                'entrenador_nombre' => $s->entrenador_nombre,
                'entrenador_foto_url' => $s->entrenador_foto ? url('/storage/' . ltrim($s->entrenador_foto, '/')) : null,
                'entrenador_instagram' => $s->entrenador_instagram ?: null,
                'ejercicios' => array_map(fn($ejercicio) => [
                    'id_ejercicio' => $ejercicio['id_ejercicio'] ?? null,
                    'nombre' => $ejercicio['nombre'] ?? null,
                    'serie' => $ejercicio['serie'] ?? null,
                    'repeticiones' => $ejercicio['repeticiones'] ?? null,
                    'carga' => $ejercicio['carga'] ?? null,
                    'descanso' => $ejercicio['descanso'] ?? null,
                    'grupo_muscular' => $ejercicio['grupo_muscular'] ?? null,
                    'color_grupo' => $ejercicio['color_grupo'] ?? null,
                    'icono_grupo' => $ejercicio['icono_grupo'] ?? null,
                ], $ejerciciosMap[$s->id] ?? []),
            ]),
        ]);
    }

    public function clienteMetricasIndex(Request $request): JsonResponse
    {
        $cliente = $this->getClientePorUsuario($request->user());
        if (! $cliente) return response()->json(['message' => 'Perfil de cliente no encontrado.'], 404);

        return $this->buildMetricasResponse($cliente->id);
    }

    public function clienteMetricasStore(Request $request): JsonResponse
    {
        $cliente = $this->getClientePorUsuario($request->user());
        if (! $cliente) return response()->json(['message' => 'Perfil de cliente no encontrado.'], 404);

        return $this->storeMetricas($request, $cliente->id);
    }

    public function clienteEjerciciosIndex(Request $request): JsonResponse
    {
        $cliente = $this->getClientePorUsuario($request->user());
        if (! $cliente) return response()->json(['message' => 'Perfil de cliente no encontrado.'], 404);

        return $this->buildEjerciciosListResponse($cliente->id);
    }

    public function clienteEjercicioHistorial(Request $request, int $idEjercicio): JsonResponse
    {
        $cliente = $this->getClientePorUsuario($request->user());
        if (! $cliente) return response()->json(['message' => 'Perfil de cliente no encontrado.'], 404);

        return $this->buildEjercicioHistorialResponse($cliente->id, $idEjercicio);
    }

    public function clientePerfilGet(Request $request, string $slug): JsonResponse
    {
        $cliente = $this->getClientePorUsuario($request->user());
        if (! $cliente) return response()->json(['message' => 'Perfil de cliente no encontrado.'], 404);

        return response()->json([
            'cliente' => $this->buildClientePerfilPayload($cliente),
        ]);
    }

    public function clientePerfilUpdate(Request $request, string $slug): JsonResponse
    {
        $cliente = $this->getClientePorUsuario($request->user());
        if (! $cliente) return response()->json(['message' => 'Perfil de cliente no encontrado.'], 404);

        // Seguridad: el slug del token debe coincidir con el slug de la URL
        if ($cliente->slug !== $slug) {
            return response()->json(['message' => 'No autorizado.'], 403);
        }

        $validated = $request->validate([
            'nombres'   => 'required|string|max:100',
            'paterno'   => 'required|string|max:100',
            'materno'   => 'nullable|string|max:100',
            'telefono'  => 'nullable|string|max:20',
            'direccion' => 'nullable|string|max:255',
            'altura'    => 'nullable|numeric|min:0.5|max:2.5',
            'foto'      => 'nullable|image|max:5120',
            'avatar'    => 'nullable|image|max:5120',
            'image'     => 'nullable|image|max:5120',
            'remove_foto' => 'nullable|boolean',
        ]);

        $foto = $this->getClientePhotoFromRequest($request);

        if ($foto && ! $this->clientePhotoColumnExists()) {
            return response()->json([
                'message' => 'La base actual aun no tiene habilitado el guardado de fotos de clientes. Ejecuta la migracion pendiente y vuelve a intentarlo.',
            ], 409);
        }

        $updateData = $validated;
        unset($updateData['foto'], $updateData['avatar'], $updateData['image'], $updateData['remove_foto']);

        // El cliente no puede cambiar estas relaciones obligatorias desde su perfil.
        // Las preservamos explícitamente para evitar que cualquier flujo parcial las nulifique.
        if (isset($cliente->id_genero)) {
            $updateData['id_genero'] = $cliente->id_genero;
        }
        if (isset($cliente->id_plan)) {
            $updateData['id_plan'] = $cliente->id_plan;
        }

        if ($this->clientePhotoColumnExists() && (($validated['remove_foto'] ?? false) || $foto)) {
            if (! empty($cliente->foto_path)) {
                Storage::disk('public')->delete($cliente->foto_path);
            }

            $updateData['foto_path'] = $foto ? $foto->store('clientes/fotos', 'public') : null;
        }

        DB::table('clientes')
            ->where('id', $cliente->id)
            ->update(array_merge($updateData, ['updated_at' => now()]));

        $actualizado = DB::table('clientes')->where('id', $cliente->id)->first();

        return response()->json([
            'message' => 'Perfil actualizado correctamente.',
            'cliente' => $this->buildClientePerfilPayload($actualizado),
        ]);
    }

    private function buildClientePerfilPayload(object $cliente): array
    {
        $planNombre = null;
        if (isset($cliente->id_plan) && $cliente->id_plan !== null) {
            $planNombre = DB::table('planes')->where('id', $cliente->id_plan)->value('nombre');
        }

        $generoNombre = null;
        if (isset($cliente->id_genero) && $cliente->id_genero !== null) {
            $generoNombre = DB::table('generos')->where('id', $cliente->id_genero)->value('nombre');
        }

        return [
            'id' => $cliente->id,
            'ci' => $cliente->ci ?? null,
            'nombres' => $cliente->nombres,
            'paterno' => $cliente->paterno,
            'materno' => $cliente->materno ?? null,
            'email' => $cliente->email,
            'telefono' => $cliente->telefono ?? null,
            'direccion' => $cliente->direccion ?? null,
            'altura' => isset($cliente->altura) && $cliente->altura !== null ? (float) $cliente->altura : null,
            'slug' => $cliente->slug ?? null,
            'fecha_ingreso' => $cliente->fecha_ingreso ?? null,
            'fecha_fin' => $cliente->fecha_fin ?? null,
            'id_genero' => isset($cliente->id_genero) && $cliente->id_genero !== null ? (int) $cliente->id_genero : null,
            'genero' => $generoNombre,
            'id_plan' => isset($cliente->id_plan) && $cliente->id_plan !== null ? (int) $cliente->id_plan : null,
            'plan' => $planNombre,
        ] + $this->buildClientePhotoPayload($this->resolveClientePhotoPath($cliente));
    }

    // ===================================================================
    // CLIENTE - RESUMEN ENTRENAMIENTOS
    // ===================================================================

    public function clienteResumenEntrenamientos(Request $request): JsonResponse
    {
        $cliente = $this->getClientePorUsuario($request->user());
        if (! $cliente) return response()->json(['message' => 'Perfil de cliente no encontrado.'], 404);

        $definicion = [
            1 => ['id' => 1, 'nombre' => 'Agendado',                  'color' => '#10b981', 'texto' => '#ffffff'],
            2 => ['id' => 2, 'nombre' => 'Cancelado sin recuperación', 'color' => '#ef4444', 'texto' => '#333333'],
            3 => ['id' => 3, 'nombre' => 'Cancelado con recuperación', 'color' => '#ef4444', 'texto' => '#333333'],
            4 => ['id' => 4, 'nombre' => 'Realizado',                  'color' => '#6366f1', 'texto' => '#ffffff'],
            5 => ['id' => 5, 'nombre' => 'Reagendado',                 'color' => '#f59e0b', 'texto' => '#ffffff'],
        ];

        $conteos = DB::table('agendas')
            ->select('estado', DB::raw('COUNT(*) as total'))
            ->where('id_cliente', $cliente->id)
            ->groupBy('estado')
            ->get()
            ->keyBy('estado');

        $resumen = [];
        foreach ($definicion as $id => $info) {
            $resumen[] = array_merge($info, ['total' => (int) ($conteos[$id]->total ?? 0)]);
        }

        return response()->json([
            'resumen'       => $resumen,
            'total_general' => (int) array_sum(array_column($resumen, 'total')),
        ]);
    }

    public function clienteEvaluacionInicialGet(Request $request): JsonResponse
    {
        $cliente = $this->getClientePorUsuario($request->user());
        if (! $cliente) return response()->json(['message' => 'Perfil de cliente no encontrado.'], 404);

        return response()->json(array_merge(
            [
                'cliente' => [
                    'id' => (int) $cliente->id,
                    'slug' => $cliente->slug,
                    'nombre_completo' => trim(($cliente->nombres ?? '') . ' ' . ($cliente->paterno ?? '') . ' ' . ($cliente->materno ?? '')),
                    'email' => $cliente->email ?? null,
                ],
            ],
            $this->buildEvaluacionInicialPayload((int) $cliente->id),
        ));
    }

    public function clienteEvaluacionInicialStore(Request $request): JsonResponse
    {
        $cliente = $this->getClientePorUsuario($request->user());
        if (! $cliente) return response()->json(['message' => 'Perfil de cliente no encontrado.'], 404);

        return $this->storeEvaluacionInicialForCliente($request, (int) $cliente->id);
    }

    private function storeEvaluacionInicialForCliente(Request $request, int $clienteId): JsonResponse
    {

        $validated = $request->validate([
            'respuestas' => 'required|array',
            'respuestas.*.pregunta_id' => 'required|integer',
            'respuestas.*.opcion_ids' => 'nullable|array',
            'respuestas.*.opcion_ids.*' => 'integer',
            'respuestas.*.otro_texto' => 'nullable|string|max:1000',
            'respuestas.*.valor_texto' => 'nullable|string|max:4000',
        ]);

        $tiposTextoLibre = ['texto', 'textarea', 'numero', 'fecha', 'text'];
        $tiposSingle = ['unica', 'si_no', 'escala', 'single'];

        $idGimnasioCliente = (int) (DB::table('clientes')->where('id', $clienteId)->value('id_gimnasio') ?? 0);

        $questions = $this->getEvaluacionInicialQuestions();
        $questionsById = $questions->keyBy('id');
        $inputResponses = collect($validated['respuestas'])->keyBy(fn(array $item) => (int) $item['pregunta_id']);
        $errors = [];
        $rowsToInsert = [];

        $visibilityCache = [];
        $isVisible = function (int $questionId) use (&$isVisible, &$visibilityCache, $questionsById, $inputResponses): bool {
            if (isset($visibilityCache[$questionId])) {
                return $visibilityCache[$questionId];
            }

            $question = $questionsById->get($questionId);

            if (! $question || ! $question->depende_pregunta_id) {
                return $visibilityCache[$questionId] = true;
            }

            if (! $isVisible((int) $question->depende_pregunta_id)) {
                return $visibilityCache[$questionId] = false;
            }

            $parentResponse = $inputResponses->get((int) $question->depende_pregunta_id);

            if (! $parentResponse) {
                return $visibilityCache[$questionId] = false;
            }

            $parentSelected = collect($parentResponse['opcion_ids'] ?? [])->map(fn($value) => (int) $value);
            $parentText = trim((string) ($parentResponse['valor_texto'] ?? ''));

            if ($question->depende_opcion_id) {
                return $visibilityCache[$questionId] = $parentSelected->contains((int) $question->depende_opcion_id);
            }

            return $visibilityCache[$questionId] = ($parentSelected->isNotEmpty() || $parentText !== '');
        };

        foreach ($questions as $question) {
            if (! $isVisible((int) $question->id)) {
                continue;
            }

            $response = $inputResponses->get((int) $question->id);

            if (! $response) {
                if ($question->es_requerida) {
                    $errors["respuestas.{$question->id}"][] = 'Esta pregunta es obligatoria.';
                }
                continue;
            }

            $selectedOptionIds = collect($response['opcion_ids'] ?? [])
                ->map(fn($value) => (int) $value)
                ->filter()
                ->unique()
                ->values();

            $otherText = trim((string) ($response['otro_texto'] ?? ''));
            $textValue = trim((string) ($response['valor_texto'] ?? ''));

            if (in_array($question->tipo, $tiposTextoLibre, true)) {
                if ($question->es_requerida && $textValue === '') {
                    $errors["respuestas.{$question->id}"][] = 'Debes ingresar una respuesta.';
                    continue;
                }

                if ($textValue !== '' && $question->tipo === 'numero' && ! is_numeric($textValue)) {
                    $errors["respuestas.{$question->id}"][] = 'Debe ser un valor numérico.';
                    continue;
                }

                if ($textValue !== '' && $question->tipo === 'fecha') {
                    $fecha = \DateTime::createFromFormat('Y-m-d', $textValue);
                    if (! $fecha || $fecha->format('Y-m-d') !== $textValue) {
                        $errors["respuestas.{$question->id}"][] = 'Debe ser una fecha válida (AAAA-MM-DD).';
                        continue;
                    }
                }

                if ($textValue !== '') {
                    $rowsToInsert[] = [
                        'pregunta_id' => (int) $question->id,
                        'opcion_id' => null,
                        'valor_texto' => $textValue,
                        'id_gimnasio' => $idGimnasioCliente,
                    ];
                }
                continue;
            }

            $optionsById = $question->opciones->keyBy('id');
            $invalidOptions = $selectedOptionIds->filter(fn(int $optionId) => ! $optionsById->has($optionId));
            if ($invalidOptions->isNotEmpty()) {
                $errors["respuestas.{$question->id}"][] = 'Hay opciones inválidas para esta pregunta.';
                continue;
            }

            if (in_array($question->tipo, $tiposSingle, true) && $selectedOptionIds->count() > 1) {
                $errors["respuestas.{$question->id}"][] = 'Solo puedes seleccionar una opción.';
                continue;
            }

            if ($question->es_requerida && $selectedOptionIds->isEmpty()) {
                $errors["respuestas.{$question->id}"][] = 'Debes seleccionar al menos una opción.';
                continue;
            }

            $selectedOtherOption = $selectedOptionIds
                ->map(fn(int $optionId) => $optionsById->get($optionId))
                ->first(fn($option) => (bool) $option?->es_otro);

            if ($selectedOtherOption && $otherText === '') {
                $errors["respuestas.{$question->id}"][] = 'Debes detallar el campo Otro/a.';
                continue;
            }

            foreach ($selectedOptionIds as $optionId) {
                $option = $optionsById->get($optionId);
                $rowsToInsert[] = [
                    'pregunta_id' => (int) $question->id,
                    'opcion_id' => $optionId,
                    'valor_texto' => $option?->es_otro ? $otherText : null,
                    'id_gimnasio' => $idGimnasioCliente,
                ];
            }
        }

        if (! empty($errors)) {
            return response()->json([
                'message' => 'Revisa los datos de la evaluación inicial.',
                'errors' => $errors,
            ], 422);
        }

        DB::transaction(function () use ($clienteId, $rowsToInsert) {
            $evaluacion = EvaluacionInicial::firstOrCreate(
                ['id_cliente' => $clienteId],
                ['completada_en' => now()],
            );

            $evaluacion->update([
                'completada_en' => now(),
            ]);

            $evaluacion->respuestas()->delete();

            if (! empty($rowsToInsert)) {
                $evaluacion->respuestas()->createMany(array_map(fn(array $row) => array_merge($row, [
                    'created_at' => now(),
                    'updated_at' => now(),
                ]), $rowsToInsert));
            }
        });

        return response()->json([
            'message' => 'Evaluación inicial guardada correctamente.',
            'evaluacion' => $this->buildEvaluacionInicialPayload($clienteId),
        ]);
    }

    // ===================================================================
    // CLIENTE - ENCUESTA ENTRENADOR
    // ===================================================================

    public function clienteEncuestaEntrenadorIndex(Request $request): JsonResponse
    {
        $user = $request->user();
        $encuestas = DB::table('encuesta_satisfaccions')
            ->where('id_cliente', $user->id)
            ->orderBy('created_at', 'desc')
            ->get([
                'id',
                'profesionalismo',
                'claridad',
                'motivacion',
                'disponibilidad',
                'puntualidad',
                'valoracion_global',
                'destacaria',
                'sugerencias',
                'created_at'
            ]);

        return response()->json(['encuestas' => $encuestas]);
    }

    public function clienteEncuestaEntrenadorStore(Request $request): JsonResponse
    {
        $user    = $request->user();
        $cliente = $this->getClientePorUsuario($user);
        if (! $cliente) return response()->json(['message' => 'Perfil de cliente no encontrado.'], 404);

        if (empty($cliente->id_usuario)) {
            return response()->json(['message' => 'No tienes un entrenador asignado.'], 422);
        }

        $validated = $request->validate([
            'profesionalismo'   => 'required|integer|min:1|max:5',
            'claridad'          => 'required|integer|min:1|max:5',
            'motivacion'        => 'required|integer|min:1|max:5',
            'disponibilidad'    => 'required|integer|min:1|max:5',
            'puntualidad'       => 'required|integer|min:1|max:5',
            'destacaria'        => 'nullable|string|max:4000',
            'sugerencias'       => 'nullable|string|max:4000',
            'valoracion_global' => 'required|integer|min:1|max:10',
        ]);

        $slug = hash('sha256', $cliente->id_usuario . $user->id . uniqid() . microtime());

        DB::table('encuesta_satisfaccions')->insert(array_merge($validated, [
            'id_cliente'    => $user->id,
            'id_entrenador' => $cliente->id_usuario,
            'slug'          => $slug,
            'created_at'    => now(),
            'updated_at'    => now(),
        ]));

        return response()->json(['message' => 'Evaluación enviada. ¡Gracias!'], 201);
    }

    // ===================================================================
    // CLIENTE - ENCUESTA GIMNASIO (Survey)
    // ===================================================================

    public function clienteEncuestaGimnasioIndex(Request $request): JsonResponse
    {
        $user      = $request->user();
        $responses = DB::table('survey_responses')
            ->where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get(['id', 'nps_score', 'training_time', 'nps_reason', 'created_at']);

        return response()->json(['encuestas' => $responses]);
    }

    public function clienteEncuestaGimnasioStore(Request $request): JsonResponse
    {
        $user = $request->user();
        $validated = $request->validate([
            'training_time'                       => 'required|in:less_1_month,1_to_3_months,3_to_6_months,more_6_months,more_1_year',
            'nps_score'                           => 'required|integer|between:0,10',
            'nps_reason'                          => 'required|string|max:500',
            'servqual_ratings.tangibles'          => 'required|in:excellent,very_good,good,needs_improvement,poor',
            'servqual_ratings.reliability'        => 'required|in:excellent,very_good,good,needs_improvement,poor',
            'servqual_ratings.responsiveness'     => 'required|in:excellent,very_good,good,needs_improvement,poor',
            'servqual_ratings.security'           => 'required|in:excellent,very_good,good,needs_improvement,poor',
            'servqual_ratings.empathy'            => 'required|in:excellent,very_good,good,needs_improvement,poor',
            'open_answers.essential_aspect'       => 'required|string|max:500',
            'open_answers.valued_moment'          => 'required|string|max:500',
            'open_answers.improvement_suggestion' => 'required|string|max:500',
            'open_answers.disappointing_moment'   => 'nullable|string|max:500',
            'open_answers.describing_word'        => 'required|string|max:100',
            'open_answers.additional_comments'    => 'nullable|string|max:1000',
        ]);

        $surveyId = DB::table('surveys')->where('is_active', true)->value('id');
        if (!$surveyId) {
            $surveyId = DB::table('surveys')->insertGetId([
                'title'      => 'Encuesta de Satisfacción del Gimnasio',
                'is_active'  => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        DB::table('survey_responses')->insert([
            'user_id'          => $user->id,
            'survey_id'        => $surveyId,
            'training_time'    => $validated['training_time'],
            'nps_score'        => $validated['nps_score'],
            'nps_reason'       => $validated['nps_reason'],
            'servqual_ratings' => json_encode($validated['servqual_ratings']),
            'open_answers'     => json_encode($validated['open_answers']),
            'created_at'       => now(),
            'updated_at'       => now(),
        ]);

        return response()->json(['message' => '¡Gracias por tu evaluación del gimnasio!'], 201);
    }

    private function buildAdminClienteDetalleResponse(int $clienteId): JsonResponse
    {
        $cliente = DB::table('clientes')
            ->select(
                'clientes.*',
                'planes.nombre as plan_nombre',
                'planes.valor as plan_valor'
            )
            ->leftJoin('planes', 'clientes.id_plan', '=', 'planes.id')
            ->where('clientes.id', $clienteId)
            ->first();

        if (! $cliente) {
            return response()->json(['message' => 'Cliente no encontrado.'], 404);
        }

        $cuotas = DB::table('cuentas_corrientes')
            ->select('id', 'monto_pagar', 'monto_pagado', 'saldo', 'fecha_vencimiento', 'fecha_pago', 'id_estado_pago')
            ->where('id_cliente', $cliente->id)
            ->orderBy('fecha_vencimiento', 'desc')
            ->limit(10)
            ->get();

        $latestComposition = $this->buildCompositionSeries($cliente->id)->first();

        $agenda = DB::table('agendas')
            ->select('id', 'titulo', 'descripcion', 'fecha_inicio', 'fecha_fin', 'estado')
            ->where('id_cliente', $cliente->id)
            ->where('fecha_inicio', '>=', now())
            ->orderBy('fecha_inicio')
            ->limit(5)
            ->get();

        $moroso = DB::table('cuentas_corrientes')
            ->where('id_cliente', $cliente->id)
            ->whereNull('fecha_pago')
            ->where('fecha_vencimiento', '<', now())
            ->exists();

        return response()->json([
            'cliente' => [
                'id' => $cliente->id,
                'nombres' => $cliente->nombres,
                'paterno' => $cliente->paterno,
                'materno' => $cliente->materno,
                'ci' => $cliente->ci,
                'email' => $cliente->email,
                'telefono' => $cliente->telefono,
                'slug' => $cliente->slug,
                'estado' => (int) $cliente->estado,
                'fecha_ingreso' => $cliente->fecha_ingreso,
                'fecha_fin' => $cliente->fecha_fin,
                'id_plan' => $cliente->id_plan,
                'plan' => $cliente->plan_nombre,
                'plan_valor' => $cliente->plan_valor,
                'altura' => $cliente->altura,
                'ciudad' => $cliente->ciudad,
                'direccion' => $cliente->direccion,
                'fecha_nacimiento' => $cliente->fecha_nacimiento,
                'id_genero' => isset($cliente->id_genero) && $cliente->id_genero !== null ? (int) $cliente->id_genero : null,
                'id_usuario' => $cliente->id_usuario,
                'id_motivo_ingreso' => $cliente->id_motivo_ingreso,
                'id_motivo_egreso' => $cliente->id_motivo_egreso,
                'perfil' => $cliente->perfil,
                'moroso' => $moroso,
            ] + $this->buildClientePhotoPayload($this->resolveClientePhotoPath($cliente)),
            'ultimo_peso' => $latestComposition['peso'] ?? null,
            'ultimo_imc' => $latestComposition['imc'] ?? null,
            'ultimo_pct_grasa_corporal' => $latestComposition['pct_grasa_corporal'] ?? null,
            'ultimo_pct_masa_muscular' => $latestComposition['pct_masa_muscular'] ?? null,
            'ultimo_pct_masa_osea' => $latestComposition['pct_masa_osea'] ?? null,
            'ultima_composicion_fecha' => $latestComposition['fecha'] ?? null,
            'cuotas' => $cuotas,
            'agenda' => $agenda,
        ]);
    }

    private function buildAdminClientePesosResponse(int $clienteId): JsonResponse
    {
        $cliente = DB::table('clientes')->where('id', $clienteId)->first();
        if (! $cliente) {
            return response()->json(['message' => 'Cliente no encontrado.'], 404);
        }

        $pesos = DB::table('pesos')
            ->where('id_cliente', $cliente->id)
            ->orderBy('created_at', 'asc')
            ->get(['id', 'peso', 'created_at']);

        $imcs = DB::table('imcs')
            ->where('id_cliente', $cliente->id)
            ->orderBy('created_at', 'asc')
            ->get(['id', 'imc', 'created_at']);

        return response()->json([
            'cliente_id' => $cliente->id,
            'nombre' => trim("{$cliente->nombres} {$cliente->paterno}"),
            'altura' => $cliente->altura,
            'pesos' => $pesos,
            'imcs' => $imcs,
        ]);
    }

    private function buildAdminClienteAgendaResponse(int $clienteId): JsonResponse
    {
        $sesiones = DB::table('agendas')
            ->where('id_cliente', $clienteId)
            ->orderBy('fecha_inicio', 'desc')
            ->get(['id', 'titulo', 'descripcion', 'fecha_inicio', 'fecha_fin', 'estado']);

        $agendaIds = $sesiones->pluck('id')->toArray();
        $ejerciciosMap = [];

        if (! empty($agendaIds)) {
            $rows = DB::table('agendas_ejercicios')
                ->join('ejercicios', 'ejercicios.id', '=', 'agendas_ejercicios.id_ejercicio')
                ->whereIn('agendas_ejercicios.id_agenda', $agendaIds)
                ->orderBy('agendas_ejercicios.orden')
                ->get([
                    'agendas_ejercicios.id_agenda',
                    'ejercicios.nombre as nombre',
                    'agendas_ejercicios.serie',
                    'agendas_ejercicios.repeticiones',
                    'agendas_ejercicios.carga',
                    'agendas_ejercicios.descanso',
                ]);

            foreach ($rows as $row) {
                $ejerciciosMap[$row->id_agenda][] = [
                    'nombre' => $row->nombre,
                    'serie' => $row->serie,
                    'repeticiones' => $row->repeticiones,
                    'carga' => $row->carga,
                    'descanso' => $row->descanso,
                ];
            }
        }

        $result = $sesiones->map(fn($s) => [
            'id' => $s->id,
            'titulo' => $s->titulo,
            'descripcion' => $s->descripcion,
            'fecha_inicio' => $s->fecha_inicio,
            'fecha_fin' => $s->fecha_fin,
            'estado' => (int) $s->estado,
            'ejercicios' => $ejerciciosMap[$s->id] ?? [],
        ]);

        return response()->json([
            'total' => $sesiones->count(),
            'agenda' => $result,
        ]);
    }

    private function buildAdminClienteEntrenamientosResponse(int $clienteId): JsonResponse
    {
        $sesiones = DB::table('agendas')
            ->where('id_cliente', $clienteId)
            ->orderBy('fecha_inicio', 'desc')
            ->get(['id', 'titulo', 'fecha_inicio', 'fecha_fin', 'estado']);

        $agendaIds = $sesiones->pluck('id')->toArray();
        $ejerciciosMap = [];

        if (! empty($agendaIds)) {
            $rows = DB::table('agendas_ejercicios')
                ->join('ejercicios', 'ejercicios.id', '=', 'agendas_ejercicios.id_ejercicio')
                ->whereIn('agendas_ejercicios.id_agenda', $agendaIds)
                ->orderBy('agendas_ejercicios.orden')
                ->get([
                    'agendas_ejercicios.id_agenda',
                    'ejercicios.nombre as nombre',
                    'agendas_ejercicios.serie',
                    'agendas_ejercicios.repeticiones',
                    'agendas_ejercicios.carga',
                    'agendas_ejercicios.descanso',
                ]);

            foreach ($rows as $row) {
                $ejerciciosMap[(int) $row->id_agenda][] = [
                    'nombre' => $row->nombre,
                    'serie' => $row->serie,
                    'repeticiones' => $row->repeticiones,
                    'carga' => $row->carga,
                    'descanso' => $row->descanso,
                ];
            }
        }

        $estadosLabels = [
            1 => 'Programado',
            2 => 'En curso',
            3 => 'Cancelado',
            4 => 'Completado',
            5 => 'No asistió',
        ];

        $agrupado = [];
        foreach ($sesiones as $s) {
            $key = (int) $s->estado;
            $label = $estadosLabels[$key] ?? "Estado {$key}";
            if (! isset($agrupado[$label])) {
                $agrupado[$label] = [];
            }
            $agrupado[$label][] = [
                'id' => $s->id,
                'titulo' => $s->titulo,
                'fecha_inicio' => $s->fecha_inicio,
                'estado' => $key,
                'ejercicios' => $ejerciciosMap[$s->id] ?? [],
            ];
        }

        $result = array_map(fn($label, $items) => [
            'estado_label' => $label,
            'count' => count($items),
            'sesiones' => $items,
        ], array_keys($agrupado), array_values($agrupado));

        return response()->json([
            'total' => $sesiones->count(),
            'grupos' => array_values($result),
        ]);
    }

    private function buildAdminClienteCuotasResponse(int $clienteId): JsonResponse
    {
        $cuotas = DB::table('cuentas_corrientes')
            ->leftJoin('formas_pagos', 'cuentas_corrientes.id_forma_pago', '=', 'formas_pagos.id')
            ->leftJoin('tipos_cuotas', 'cuentas_corrientes.id_tipo_cuota', '=', 'tipos_cuotas.id')
            ->where('id_cliente', $clienteId)
            ->orderBy('fecha_vencimiento', 'desc')
            ->get([
                'cuentas_corrientes.id',
                'cuentas_corrientes.monto_pagar',
                'cuentas_corrientes.monto_pagado',
                'cuentas_corrientes.saldo',
                'cuentas_corrientes.fecha_vencimiento',
                'cuentas_corrientes.fecha_pago',
                'cuentas_corrientes.fecha_ultimo_abono',
                'cuentas_corrientes.id_estado_pago',
                'cuentas_corrientes.id_forma_pago',
                'cuentas_corrientes.id_tipo_cuota',
                'cuentas_corrientes.comprobante',
                'formas_pagos.nombre as forma_pago',
                'tipos_cuotas.nombre as tipo_cuota',
            ])
            ->map(fn($cuota) => [
                'id' => (int) $cuota->id,
                'monto_pagar' => (float) $cuota->monto_pagar,
                'monto_pagado' => $cuota->monto_pagado !== null ? (float) $cuota->monto_pagado : null,
                'saldo' => $cuota->saldo !== null ? (float) $cuota->saldo : null,
                'fecha_vencimiento' => $cuota->fecha_vencimiento,
                'fecha_pago' => $cuota->fecha_pago,
                'fecha_ultimo_abono' => $cuota->fecha_ultimo_abono,
                'id_estado_pago' => $cuota->id_estado_pago !== null ? (int) $cuota->id_estado_pago : null,
                'id_forma_pago' => $cuota->id_forma_pago !== null ? (int) $cuota->id_forma_pago : null,
                'id_tipo_cuota' => $cuota->id_tipo_cuota !== null ? (int) $cuota->id_tipo_cuota : null,
                'forma_pago' => $cuota->forma_pago,
                'tipo_cuota' => $cuota->tipo_cuota,
                'comprobante' => $cuota->comprobante,
                'comprobante_url' => $cuota->comprobante ? url('/storage/' . ltrim($cuota->comprobante, '/')) : null,
            ]);

        $formasPago = DB::table('formas_pagos')
            ->where('estado', 1)
            ->orderBy('nombre')
            ->get(['id', 'nombre'])
            ->map(fn($formaPago) => [
                'id' => (int) $formaPago->id,
                'nombre' => $formaPago->nombre,
            ])
            ->values();

        // Una cuota está pagada si: id_estado_pago = 2, o su saldo ya es 0.
        // No se usa monto_pagado/id_forma_pago como señal: con pago parcial
        // ambos pueden ser > 0 sin que la cuota esté completamente pagada.
        $esPagada = fn($c) => $c['id_estado_pago'] === 2
            || ($c['saldo'] !== null && $c['saldo'] <= 0);
        $pagadas    = $cuotas->filter($esPagada);
        $pendientes = $cuotas->reject($esPagada);

        return response()->json([
            'cuotas' => $cuotas,
            'formas_pago' => $formasPago,
            'totales' => [
                'total_cobrado' => (float) $cuotas->sum('monto_pagar'),
                'total_pagado' => (float) $pagadas->sum('monto_pagado'),
                'total_pendiente' => (float) $pendientes->sum('monto_pagar'),
                'cuotas_pagadas' => $pagadas->count(),
                'cuotas_pendientes' => $pendientes->count(),
            ],
        ]);
    }

    // ===================================================================
    // PRIVATE HELPERS
    // ===================================================================

    private function getClientePorUsuario(object $user): ?object
    {
        $userId = isset($user->id) ? (int) $user->id : 0;
        $c = null;

        if ($userId > 0) {
            $c = DB::table('clientes')->where('clientes.id_usuario', $userId)->first();
        }

        $clienteIdFromToken = (int) ($user->id_cliente ?? $user->idCliente ?? $user->cliente_id ?? 0);
        if (! $c && $clienteIdFromToken > 0) {
            $c = DB::table('clientes')->where('clientes.id', $clienteIdFromToken)->first();
        }

        if (! $c && $userId > 0) {
            $clienteIdFromUser = (int) DB::table('users')->where('id', $userId)->value('id_cliente');
            if ($clienteIdFromUser > 0) {
                $c = DB::table('clientes')->where('clientes.id', $clienteIdFromUser)->first();
            }
        }

        $email = isset($user->email) ? trim((string) $user->email) : '';
        if (! $c && $email !== '') {
            $c = DB::table('clientes')->where('clientes.email', $email)->first();
        }

        return $c;
    }

    private function getClientePhotoFromRequest(Request $request)
    {
        return $request->file('foto') ?? $request->file('avatar') ?? $request->file('image');
    }

    private function buildClientePhotoPayload(?string $fotoPath): array
    {
        $fotoUrl = $this->buildPublicStorageUrl($fotoPath);

        return [
            'foto_path' => $fotoPath,
            'foto_url' => $fotoUrl,
            'avatar_url' => $fotoUrl,
            'image_url' => $fotoUrl,
        ];
    }

    private function buildPublicStorageUrl(?string $path): ?string
    {
        if (! is_string($path) || $path === '') {
            return null;
        }

        $relativeUrl = Storage::url($path);
        $request = request();

        if ($request) {
            return rtrim($request->getSchemeAndHttpHost(), '/') . $relativeUrl;
        }

        return url($relativeUrl);
    }

    private function buildMergedMeasurementTimeline(array $sources, int $limit = 60)
    {
        $events = [];

        foreach ($sources as $key => $rows) {
            foreach ($rows as $row) {
                if (! isset($row->created_at) || ! isset($row->valor)) {
                    continue;
                }

                $timestamp = Carbon::parse($row->created_at)->format('Y-m-d H:i:s');

                if (! isset($events[$timestamp])) {
                    $events[$timestamp] = ['fecha' => Carbon::parse($row->created_at)->toDateTimeString()];
                }

                $events[$timestamp][$key] = (float) $row->valor;
            }
        }

        if (empty($events)) {
            return collect();
        }

        ksort($events);

        $carry = array_fill_keys(array_keys($sources), null);
        $timeline = collect();

        foreach ($events as $event) {
            foreach (array_keys($sources) as $key) {
                if (array_key_exists($key, $event) && $event[$key] !== null) {
                    $carry[$key] = $event[$key];
                }
            }

            $timeline->push(array_merge(['fecha' => $event['fecha']], $carry));
        }

        if ($timeline->count() > $limit) {
            $timeline = $timeline->slice(-$limit)->values();
        }

        return $timeline;
    }

    private function getEvaluacionInicialSections()
    {
        return EvaluacionInicialSeccion::query()
            ->where('estado', true)
            ->orderBy('orden')
            ->with([
                'preguntas' => fn($query) => $query
                    ->where('estado', true)
                    ->orderBy('orden')
                    ->with([
                        'opciones' => fn($optionQuery) => $optionQuery->where('estado', true)->orderBy('orden'),
                    ]),
            ])
            ->get();
    }

    private function getEvaluacionInicialQuestions()
    {
        return EvaluacionInicialPregunta::query()
            ->where('estado', true)
            ->orderBy('orden')
            ->with([
                'opciones' => fn($query) => $query->where('estado', true)->orderBy('orden'),
            ])
            ->get();
    }

    private function buildEvaluacionInicialPayload(int $clienteId): array
    {
        $sections = $this->getEvaluacionInicialSections();
        $evaluacion = EvaluacionInicial::with(['respuestas.opcion'])
            ->where('id_cliente', $clienteId)
            ->first();

        $answersByQuestion = [];
        foreach ($evaluacion?->respuestas ?? collect() as $respuesta) {
            $preguntaId = (int) $respuesta->pregunta_id;

            if (! isset($answersByQuestion[$preguntaId])) {
                $answersByQuestion[$preguntaId] = [
                    'selected_option_ids' => [],
                    'other_text' => null,
                    'text_value' => null,
                    'tiene_alerta' => false,
                ];
            }

            if ($respuesta->opcion_id) {
                $answersByQuestion[$preguntaId]['selected_option_ids'][] = (int) $respuesta->opcion_id;

                if ($respuesta->opcion?->es_otro && $respuesta->valor_texto) {
                    $answersByQuestion[$preguntaId]['other_text'] = $respuesta->valor_texto;
                }

                // Se lee directo de la relación (sin filtro de `estado`) para que la
                // alerta no desaparezca si la opción se desactiva después desde el catálogo.
                if ($respuesta->opcion?->genera_alerta) {
                    $answersByQuestion[$preguntaId]['tiene_alerta'] = true;
                }
            } elseif ($respuesta->valor_texto) {
                $answersByQuestion[$preguntaId]['text_value'] = $respuesta->valor_texto;
            }
        }

        return [
            'completada_en' => $evaluacion?->completada_en?->format('Y-m-d H:i:s'),
            'secciones' => $sections->map(function ($section) use ($answersByQuestion) {
                return [
                    'id' => (int) $section->id,
                    'codigo' => $section->codigo,
                    'titulo' => $section->titulo,
                    'descripcion' => $section->descripcion,
                    'orden' => (int) $section->orden,
                    'preguntas' => $section->preguntas->map(function ($question) use ($answersByQuestion) {
                        return [
                            'id' => (int) $question->id,
                            'codigo' => $question->codigo,
                            'pregunta' => $question->pregunta,
                            'descripcion' => $question->descripcion,
                            'tipo' => $question->tipo,
                            'es_requerida' => (bool) $question->es_requerida,
                            'permite_otro' => (bool) $question->permite_otro,
                            'es_sensible' => (bool) $question->es_sensible,
                            'depende_de' => $question->depende_pregunta_id ? [
                                'pregunta_id' => (int) $question->depende_pregunta_id,
                                'opcion_id' => $question->depende_opcion_id ? (int) $question->depende_opcion_id : null,
                            ] : null,
                            'orden' => (int) $question->orden,
                            'opciones' => $question->opciones->map(fn($option) => [
                                'id' => (int) $option->id,
                                'codigo' => $option->codigo,
                                'etiqueta' => $option->etiqueta,
                                'orden' => (int) $option->orden,
                                'es_otro' => (bool) $option->es_otro,
                                'genera_alerta' => (bool) $option->genera_alerta,
                            ])->values()->all(),
                            'respuesta' => $answersByQuestion[(int) $question->id] ?? [
                                'selected_option_ids' => [],
                                'other_text' => null,
                                'text_value' => null,
                                'tiene_alerta' => false,
                            ],
                        ];
                    })->values()->all(),
                ];
            })->values()->all(),
        ];
    }

    private function buildCompositionSeries(int $clienteId)
    {
        $columns = array_column(DB::select('SHOW COLUMNS FROM pesos'), 'Field');
        $hasExtended = in_array('imc', $columns);

        $sources = [
            'peso' => DB::table('pesos')
                ->where('id_cliente', $clienteId)
                ->whereNotNull('peso')
                ->orderBy('created_at')
                ->limit(60)
                ->get(['created_at', 'peso as valor']),
        ];

        if ($hasExtended) {
            $sources['imc'] = DB::table('pesos')
                ->where('id_cliente', $clienteId)
                ->whereNotNull('imc')
                ->orderBy('created_at')
                ->limit(60)
                ->get(['created_at', 'imc as valor']);

            $sources['pct_grasa_corporal'] = DB::table('pesos')
                ->where('id_cliente', $clienteId)
                ->whereNotNull('pct_grasa_corporal')
                ->orderBy('created_at')
                ->limit(60)
                ->get(['created_at', 'pct_grasa_corporal as valor']);

            $sources['pct_masa_muscular'] = DB::table('pesos')
                ->where('id_cliente', $clienteId)
                ->whereNotNull('pct_masa_muscular')
                ->orderBy('created_at')
                ->limit(60)
                ->get(['created_at', 'pct_masa_muscular as valor']);

            $sources['pct_masa_osea'] = DB::table('pesos')
                ->where('id_cliente', $clienteId)
                ->whereNotNull('pct_masa_osea')
                ->orderBy('created_at')
                ->limit(60)
                ->get(['created_at', 'pct_masa_osea as valor']);
        } else {
            $sources['imc'] = DB::table('imcs')
                ->where('id_cliente', $clienteId)
                ->whereNotNull('imc')
                ->orderBy('created_at')
                ->limit(60)
                ->get(['created_at', 'imc as valor']);

            $sources['pct_grasa_corporal'] = DB::table('grasas')
                ->where('id_cliente', $clienteId)
                ->whereNotNull('valor')
                ->orderBy('created_at')
                ->limit(60)
                ->get(['created_at', 'valor']);

            $sources['pct_masa_muscular'] = DB::table('pmusculares')
                ->where('id_cliente', $clienteId)
                ->whereNotNull('valor')
                ->orderBy('created_at')
                ->limit(60)
                ->get(['created_at', 'valor']);

            $sources['pct_masa_osea'] = DB::table('poseas')
                ->where('id_cliente', $clienteId)
                ->whereNotNull('valor')
                ->orderBy('created_at')
                ->limit(60)
                ->get(['created_at', 'valor']);
        }

        return $this->buildMergedMeasurementTimeline($sources);
    }

    private function getPerimetrosFieldMap(): array
    {
        if (! DB::getSchemaBuilder()->hasTable('perimetros')) {
            return [];
        }

        $availableColumns = DB::getSchemaBuilder()->getColumnListing('perimetros');
        $candidates = [
            'cabeza' => ['cabeza'],
            'torax' => ['torax', 'torax_mesoexternal'],
            'cintura' => ['cintura', 'cintura_minima'],
            'caderas' => ['caderas', 'caderas_maxima'],
            'brazo_relajado' => ['brazo_relajado'],
            'brazo_flexionado' => ['brazo_flexionado', 'brazo_flexionado_tension'],
            'antebrazo' => ['antebrazo'],
            'muslo_superior' => ['muslo_superior'],
            'muslo_medial' => ['muslo_medial'],
            'pantorrilla' => ['pantorrilla', 'pantorrilla_maxima'],
        ];

        $resolvedMap = [];

        foreach ($candidates as $responseField => $possibleColumns) {
            foreach ($possibleColumns as $column) {
                if (in_array($column, $availableColumns, true)) {
                    $resolvedMap[$responseField] = $column;
                    break;
                }
            }
        }

        return $resolvedMap;
    }

    private function buildPerimetrosSeries(int $clienteId)
    {
        $fieldMap = $this->getPerimetrosFieldMap();

        if (empty($fieldMap)) {
            return collect();
        }

        $rows = DB::table('perimetros')
            ->where('id_cliente', $clienteId)
            ->orderBy('created_at')
            ->limit(60)
            ->get(array_merge(['created_at'], array_values(array_unique($fieldMap))));

        $carry = array_fill_keys(array_keys($fieldMap), null);
        $timeline = collect();

        foreach ($rows as $row) {
            foreach ($fieldMap as $responseField => $column) {
                if ($row->{$column} !== null && $row->{$column} !== '') {
                    $carry[$responseField] = (float) $row->{$column};
                }
            }

            $timeline->push(array_merge([
                'fecha' => Carbon::parse($row->created_at)->toDateTimeString(),
            ], $carry));
        }

        return $timeline->reverse()->values();
    }

    private function buildMetricasResponse(int $clienteId): JsonResponse
    {
        $cliente = DB::table('clientes')->where('id', $clienteId)->first(['id_gimnasio', 'altura']);
        $idGimnasio = $cliente->id_gimnasio ?? null;
        $composicion = $this->buildCompositionSeries($clienteId);
        $perimetros = Gimnasios::tieneFeature('metricas_perimetros', $idGimnasio ? (int) $idGimnasio : null)
            ? $this->buildPerimetrosSeries($clienteId)
            : collect();

        return response()->json([
            'composicion' => $composicion,
            'perimetros' => $perimetros,
            'altura' => $cliente->altura !== null ? (float) $cliente->altura : null,
        ]);
    }

    private function storeMetricas(Request $request, int $clienteId): JsonResponse
    {
        $v = $request->validate([
            'peso' => 'nullable|numeric',
            'imc' => 'nullable|numeric',
            'pct_grasa' => 'nullable|numeric',
            'pct_muscular' => 'nullable|numeric',
            'pct_oseo' => 'nullable|numeric',
            'cabeza' => 'nullable|numeric',
            'torax' => 'nullable|numeric',
            'cintura' => 'nullable|numeric',
            'caderas' => 'nullable|numeric',
            'brazo_relajado' => 'nullable|numeric',
            'brazo_flexionado' => 'nullable|numeric',
            'antebrazo' => 'nullable|numeric',
            'muslo_superior' => 'nullable|numeric',
            'muslo_medial' => 'nullable|numeric',
            'pantorrilla' => 'nullable|numeric',
        ]);

        if (isset($v['peso']) && ! isset($v['imc'])) {
            $altura = DB::table('clientes')->where('id', $clienteId)->value('altura');
            if ($altura !== null && (float) $altura > 0) {
                $v['imc'] = round($v['peso'] / ((float) $altura ** 2), 2);
            }
        }

        $columns     = array_column(DB::select('SHOW COLUMNS FROM pesos'), 'Field');
        $hasExtended = in_array('imc', $columns);

        $pesoData = ['id_cliente' => $clienteId, 'peso' => $v['peso'] ?? 0, 'created_at' => now(), 'updated_at' => now()];
        if ($hasExtended) {
            if (isset($v['imc']))          $pesoData['imc']                = $v['imc'];
            if (isset($v['pct_grasa']))    $pesoData['pct_grasa_corporal'] = $v['pct_grasa'];
            if (isset($v['pct_muscular'])) $pesoData['pct_masa_muscular']  = $v['pct_muscular'];
            if (isset($v['pct_oseo']))     $pesoData['pct_masa_osea']      = $v['pct_oseo'];
        }
        DB::table('pesos')->insert($pesoData);

        if (! $hasExtended) {
            if (isset($v['imc']))          DB::table('imcs')->insert(['id_cliente' => $clienteId, 'imc' => $v['imc'], 'created_at' => now(), 'updated_at' => now()]);
            if (isset($v['pct_grasa']))    DB::table('grasas')->insert(['id_cliente' => $clienteId, 'valor' => $v['pct_grasa'], 'created_at' => now(), 'updated_at' => now()]);
            if (isset($v['pct_muscular'])) DB::table('pmusculares')->insert(['id_cliente' => $clienteId, 'valor' => $v['pct_muscular'], 'created_at' => now(), 'updated_at' => now()]);
            if (isset($v['pct_oseo']))     DB::table('poseas')->insert(['id_cliente' => $clienteId, 'valor' => $v['pct_oseo'], 'created_at' => now(), 'updated_at' => now()]);
        }

        $perimetrosFields = array_filter(array_intersect_key($v, array_flip([
            'cabeza',
            'torax',
            'cintura',
            'caderas',
            'brazo_relajado',
            'brazo_flexionado',
            'antebrazo',
            'muslo_superior',
            'muslo_medial',
            'pantorrilla',
        ])));

        $resolvedPerimetros = [];
        $perimetrosFieldMap = $this->getPerimetrosFieldMap();
        $idGimnasio = DB::table('clientes')->where('id', $clienteId)->value('id_gimnasio');
        $incluirPerimetros = Gimnasios::tieneFeature('metricas_perimetros', $idGimnasio ? (int) $idGimnasio : null);

        if ($incluirPerimetros) {
            foreach ($perimetrosFields as $field => $value) {
                if (isset($perimetrosFieldMap[$field])) {
                    $resolvedPerimetros[$perimetrosFieldMap[$field]] = $value;
                }
            }
        }

        if (! empty($resolvedPerimetros)) {
            DB::table('perimetros')->insert(array_merge($resolvedPerimetros, ['id_cliente' => $clienteId, 'created_at' => now(), 'updated_at' => now()]));
        }

        return response()->json(['message' => 'Metricas registradas correctamente.'], 201);
    }

    private function buildEjerciciosListResponse(int $clienteId): JsonResponse
    {
        $ejercicios = DB::table('agendas_ejercicios')
            ->join('agendas', 'agendas.id', '=', 'agendas_ejercicios.id_agenda')
            ->join('ejercicios', 'ejercicios.id', '=', 'agendas_ejercicios.id_ejercicio')
            ->where('agendas.id_cliente', $clienteId)
            ->where('agendas.estado', 4)
            ->whereNotNull('agendas.fecha_inicio')
            ->select(
                'ejercicios.id',
                'ejercicios.nombre',
                DB::raw('COUNT(agendas_ejercicios.id) as total_registros'),
                DB::raw('MAX(agendas.fecha_inicio) as ultima_fecha')
            )
            ->groupBy('ejercicios.id', 'ejercicios.nombre')
            ->orderBy('ejercicios.nombre')
            ->get()
            ->map(fn($e) => [
                'id'              => (int) $e->id,
                'nombre'          => $e->nombre,
                'total_registros' => (int) $e->total_registros,
                'ultima_fecha'    => $e->ultima_fecha,
            ]);

        return response()->json(['ejercicios' => $ejercicios]);
    }

    private function buildEjercicioHistorialResponse(int $clienteId, int $idEjercicio): JsonResponse
    {
        $ejercicio = DB::table('ejercicios')->where('id', $idEjercicio)->first(['id', 'nombre']);
        if (! $ejercicio) return response()->json(['message' => 'Ejercicio no encontrado.'], 404);

        $historial = DB::table('agendas_ejercicios')
            ->join('agendas', 'agendas.id', '=', 'agendas_ejercicios.id_agenda')
            ->where('agendas.id_cliente', $clienteId)
            ->where('agendas_ejercicios.id_ejercicio', $idEjercicio)
            ->where('agendas.estado', 4)
            ->whereNotNull('agendas.fecha_inicio')
            ->orderBy('agendas.fecha_inicio', 'asc')
            ->get(['agendas.fecha_inicio as fecha', 'agendas_ejercicios.serie', 'agendas_ejercicios.repeticiones', 'agendas_ejercicios.carga'])
            ->map(fn($r) => [
                'fecha'        => $r->fecha,
                'serie'        => (int) $r->serie,
                'repeticiones' => $r->repeticiones,
                'carga'        => $r->carga !== null ? (float) $r->carga : null,
            ]);

        return response()->json([
            'ejercicio' => ['id' => (int) $ejercicio->id, 'nombre' => $ejercicio->nombre],
            'registros' => $historial,
        ]);
    }

    private function ensureMovimientosTable(): void
    {
        if (! DB::getSchemaBuilder()->hasTable('movimientos_financieros')) {
            DB::statement('CREATE TABLE movimientos_financieros (
                id INT AUTO_INCREMENT PRIMARY KEY,
                tipo VARCHAR(20) NOT NULL,
                concepto VARCHAR(255) NOT NULL,
                monto DECIMAL(10,2) NOT NULL,
                fecha DATE NOT NULL,
                notas TEXT NULL,
                created_at TIMESTAMP NULL,
                updated_at TIMESTAMP NULL
            )');
        }
    }
}
