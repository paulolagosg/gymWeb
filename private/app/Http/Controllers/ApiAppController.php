<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ClienteController;
use App\Models\EvaluacionInicial;
use App\Models\EvaluacionInicialOpcion;
use App\Models\EvaluacionInicialPregunta;
use App\Models\EvaluacionInicialRespuesta;
use App\Models\EvaluacionInicialSeccion;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
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
        if (! $user || ! in_array((int) $user->id_tipo_usuario, [1, 2])) {
            return response()->json(['message' => 'No autorizado.'], 403);
        }
        return null;
    }

    private function requireAdmin(Request $request): ?JsonResponse
    {
        $user = $request->user();
        if (! $user || (int) $user->id_tipo_usuario !== 1) {
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
            ->where('id_usuario', $user->id)
            ->pluck('id');

        $clientesEnAgenda = DB::table('agendas')
            ->where('id_usuario', $user->id)
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

        $rows = array_map(fn($ejercicio) => [
            'id_agenda' => $agendaId,
            'id_ejercicio' => $ejercicio['id_ejercicio'],
            'serie' => $ejercicio['serie'],
            'repeticiones' => $ejercicio['repeticiones'],
            'carga' => $ejercicio['carga'] ?? null,
            'descanso' => $ejercicio['descanso'] ?? null,
            'created_at' => now(),
            'updated_at' => now(),
        ], $ejercicios);

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

    // ===================================================================
    // ADMIN — DASHBOARD (con filtro por entrenador)
    // ===================================================================

    public function adminDashboard(Request $request): JsonResponse
    {
        if ($err = $this->requireAdminOrEntrenador($request)) return $err;

        $user         = $request->user();
        $isEntrenador = (int) $user->id_tipo_usuario === 2;
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
                    'horarios_peak' => [],
                    'pagos_por_forma'   => ['months' => $meses, 'series' => []],
                ]);
            }
        }

        $clientesBase = DB::table('clientes');
        if ($isEntrenador) $clientesBase->whereIn('id', $clienteIdsEntrenador);

        $totalClientes = (clone $clientesBase)->count();
        $totalActivos  = (clone $clientesBase)->where('estado', 1)->count();

        $morosaQ = DB::table('cuentas_corrientes')
            ->join('clientes', 'cuentas_corrientes.id_cliente', '=', 'clientes.id')
            ->whereNull('cuentas_corrientes.fecha_pago')
            ->whereDate('cuentas_corrientes.fecha_vencimiento', '<', $hoy->toDateString())
            ->where('clientes.estado', 1);
        if ($isEntrenador) $morosaQ->whereIn('cuentas_corrientes.id_cliente', $clienteIdsEntrenador);
        $morosos = $morosaQ->distinct('cuentas_corrientes.id_cliente')->count('cuentas_corrientes.id_cliente');

        $ingresosMes = 0.0;
        if (! $isEntrenador) {
            $ingresosMes = (float) DB::table('cuentas_corrientes')
                ->whereNotNull('fecha_pago')
                ->whereBetween('fecha_pago', [$primerDiaMes, $hoy])
                ->sum('monto_pagado');
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
        if ($isEntrenador) $generoQ->whereIn('clientes.id', $clienteIdsEntrenador);
        $clientesPorGenero = $generoQ
            ->selectRaw("COALESCE(generos.nombre, 'Sin género') as label, COUNT(*) as value")
            ->groupBy('label')->orderByDesc('value')
            ->get()->map(fn($r) => ['label' => $r->label, 'value' => (int) $r->value])->values();

        $planQ = DB::table('clientes')->leftJoin('planes', 'clientes.id_plan', '=', 'planes.id')->where('clientes.estado', 1);
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
            'horarios_peak'       => $horariosPeak,
            'pagos_por_forma'     => $pagosData,
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

        $entrenadores = DB::table('users')
            ->where('id_tipo_usuario', 2)->orderBy('name')
            ->get(['id', 'name', 'titulo'])
            ->map(fn($r) => ['id' => (int) $r->id, 'label' => trim($r->name . ($r->titulo ? ' · ' . $r->titulo : ''))])
            ->values();

        $clientesQ = DB::table('clientes')->where('estado', 1)->orderBy('nombres');
        if ($isEntrenador) {
            $ids = $this->getScopedClienteIdsForUser($user);
            if (empty($ids)) {
                $clientesQ->whereRaw('1 = 0');
            } else {
                $clientesQ->whereIn('id', $ids);
            }
        }
        $clientes = $clientesQ->get(['id', 'slug', 'nombres', 'paterno', 'materno'])
            ->map(fn($r) => [
                'id'    => (int) $r->id,
                'slug'  => $r->slug,
                'label' => trim($r->nombres . ' ' . $r->paterno . ' ' . ($r->materno ?? '')),
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
            'ejercicios' => 'required|array|min:1',
            'ejercicios.*.id_ejercicio' => 'required|integer|exists:ejercicios,id',
            'ejercicios.*.serie' => 'required|integer|min:1|max:100',
            'ejercicios.*.repeticiones' => 'required|string|max:50',
            'ejercicios.*.carga' => 'nullable|string|max:50',
            'ejercicios.*.descanso' => 'nullable|string|max:50',
        ]);

        DB::transaction(function () use ($agenda, $validated) {
            DB::table('agendas')
                ->where('id', $agenda->id)
                ->update([
                    'fecha_inicio' => Carbon::parse($validated['fecha_inicio'], config('app.timezone')),
                    'fecha_fin' => Carbon::parse($validated['fecha_fin'], config('app.timezone')),
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

        return response()->json([
            'message' => 'Estado del entrenamiento actualizado correctamente.',
        ]);
    }

    public function adminAgendaCalendario(Request $request): JsonResponse
    {
        if ($err = $this->requireAdminOrEntrenador($request)) return $err;

        $desde     = $request->query('desde', now()->startOfMonth()->format('Y-m-d'));
        $hasta     = $request->query('hasta', now()->endOfMonth()->format('Y-m-d'));
        $idUsuario = $request->query('idUsuario');

        $q = DB::table('agendas')
            ->join('clientes', 'agendas.id_cliente', '=', 'clientes.id')
            ->join('users', 'agendas.id_usuario', '=', 'users.id')
            ->whereBetween(DB::raw('DATE(agendas.fecha_inicio)'), [$desde, $hasta]);

        if ($idUsuario) $q->where('agendas.id_usuario', (int) $idUsuario);

        $sesiones = $q->select(
            'agendas.id',
            'agendas.slug',
            'agendas.titulo',
            'agendas.fecha_inicio',
            'agendas.fecha_fin',
            'agendas.estado',
            'agendas.descripcion',
            'agendas.id_cliente',
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
            'sesiones' => $sesiones->map(fn($s) => array_merge((array) $s, ['ejercicios' => $ejerciciosMap[$s->id] ?? []])),
        ]);
    }

    // ===================================================================
    // ADMIN — EJERCICIOS DEL SISTEMA
    // ===================================================================

    public function adminEjerciciosIndex(Request $request): JsonResponse
    {
        if ($err = $this->requireAdminOrEntrenador($request)) return $err;

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
        if ($err = $this->requireAdminOrEntrenador($request)) return $err;

        $busqueda = trim((string) $request->query('q', ''));
        $busquedaNormalizada = str_replace(['.', '-', ' '], '', Str::lower($busqueda));
        $perPage  = max(1, (int) $request->query('per_page', 20));
        $page     = max(1, (int) $request->query('page', 1));

        $query = DB::table('clientes')
            ->leftJoin('planes', 'clientes.id_plan', '=', 'planes.id')
            ->select(
                'clientes.id',
                'clientes.nombres',
                'clientes.paterno',
                'clientes.materno',
                'clientes.ci',
                'clientes.email',
                'clientes.telefono',
                'clientes.slug',
                'clientes.estado',
                'clientes.fecha_ingreso',
                'clientes.fecha_fin',
                'planes.nombre as plan_nombre'
            );

        $this->applyClienteScopeToQuery($query, $request->user());

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
            ->orderBy('clientes.paterno')
            ->orderBy('clientes.nombres')
            ->forPage($page, $perPage)
            ->get();

        $morososIds = DB::table('cuentas_corrientes')
            ->whereNull('fecha_pago')
            ->whereDate('fecha_vencimiento', '<', now()->toDateString())
            ->pluck('id_cliente')
            ->unique()
            ->toArray();

        $result = $items->map(function ($c) use ($morososIds) {
            return [
                'id' => (int) $c->id,
                'nombre' => $c->nombres,
                'apellido' => $c->paterno,
                'nombre_completo' => trim("{$c->nombres} {$c->paterno} " . ($c->materno ?? '')),
                'ci' => $c->ci,
                'email' => $c->email,
                'telefono' => $c->telefono,
                'slug' => $c->slug,
                'estado' => (int) $c->estado,
                'plan' => $c->plan_nombre,
                'fecha_ingreso' => $c->fecha_ingreso,
                'fecha_fin' => $c->fecha_fin,
                'moroso' => in_array($c->id, $morososIds),
            ];
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
            ->selectRaw('id_cliente, COUNT(*) as cuotas_vencidas, SUM(monto) as monto_deuda, MIN(fecha_vencimiento) as primera_vencida')
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
            ];
        });

        return response()->json(['data' => $result->values()]);
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

    public function adminClientesStore(Request $request): JsonResponse
    {
        if ($err = $this->requireAdminOrEntrenador($request)) return $err;

        $v = $request->validate([
            'nombre'             => 'required|string|max:255',
            'apellido'           => 'required|string|max:255',
            'email'              => 'required|email|unique:clientes,email',
            'telefono'           => 'nullable|string|max:50',
            'materno'            => 'nullable|string|max:255',
            'ci'                 => 'nullable|string|max:20|unique:clientes,ci',
            'fecha_nacimiento'   => 'nullable|date',
            'fecha_ingreso'      => 'nullable|date',
            'fecha_fin'          => 'nullable|date',
            'altura'             => 'nullable|numeric|min:0.5|max:2.5',
            'direccion'          => 'nullable|string|max:255',
            'ciudad'             => 'nullable|string|max:255',
            'id_plan'            => 'nullable|integer|exists:planes,id',
            'id_usuario'         => 'nullable|integer|exists:users,id',
            'id_motivo_ingreso'  => 'nullable|integer|exists:motivos,id',
            'id_motivo_egreso'   => 'nullable|integer|exists:motivos,id',
            'estado'             => 'nullable|integer|in:0,1',
            'perfil'             => 'nullable|string',
        ]);

        $base = Str::slug($v['nombre'] . '-' . $v['apellido']);
        $slug = $base;
        $i    = 0;
        while (DB::table('clientes')->where('slug', $slug)->exists()) {
            $slug = $base . '-' . (++$i);
        }

        $id = DB::table('clientes')->insertGetId([
            'nombres'           => $v['nombre'],
            'paterno'           => $v['apellido'],
            'materno'           => $v['materno'] ?? null,
            'ci'                => $v['ci'] ?? null,
            'email'             => $v['email'],
            'telefono'          => $v['telefono'] ?? null,
            'fecha_nacimiento'  => $v['fecha_nacimiento'] ?? null,
            'fecha_ingreso'     => $v['fecha_ingreso'] ?? null,
            'fecha_fin'         => $v['fecha_fin'] ?? null,
            'altura'            => $v['altura'] ?? null,
            'direccion'         => $v['direccion'] ?? null,
            'ciudad'            => $v['ciudad'] ?? null,
            'id_plan'           => $v['id_plan'] ?? null,
            'id_usuario'        => $v['id_usuario'] ?? null,
            'id_motivo_ingreso' => $v['id_motivo_ingreso'] ?? null,
            'id_motivo_egreso'  => $v['id_motivo_egreso'] ?? null,
            'perfil'            => $v['perfil'] ?? null,
            'slug'              => $slug,
            'estado'            => $v['estado'] ?? 0,
            'created_at'        => now(),
            'updated_at'        => now(),
        ]);

        return response()->json(['message' => 'Cliente creado.', 'id' => $id, 'slug' => $slug], 201);
    }

    public function adminClienteDetalle(Request $request, string $slug): JsonResponse
    {
        if ($err = $this->requireAdminOrEntrenador($request)) return $err;

        $cliente = $this->findScopedCliente($request, $slug);
        if (! $cliente) return response()->json(['message' => 'Cliente no encontrado.'], 404);

        return $this->buildAdminClienteDetalleResponse((int) $cliente->id);
    }

    public function adminClienteUpdate(Request $request, string $slug): JsonResponse
    {
        if ($err = $this->requireAdminOrEntrenador($request)) return $err;

        $cliente = $this->findScopedCliente($request, $slug);
        if (! $cliente) return response()->json(['message' => 'Cliente no encontrado.'], 404);

        $validated = $request->validate([
            'nombres' => 'required|string|max:255',
            'paterno' => 'required|string|max:255',
            'materno' => 'nullable|string|max:255',
            'ci' => "nullable|string|max:20|unique:clientes,ci,{$cliente->id}",
            'email' => 'nullable|email|max:255',
            'telefono' => 'nullable|string|max:50',
            'ciudad' => 'nullable|string|max:255',
            'direccion' => 'nullable|string|max:255',
            'fecha_nacimiento' => 'nullable|date',
            'fecha_ingreso' => 'nullable|date',
            'fecha_fin' => 'nullable|date',
            'altura' => 'nullable|numeric|min:0.5|max:2.5',
            'estado' => 'nullable|integer|in:0,1',
            'id_plan' => 'nullable|integer|exists:planes,id',
            'id_usuario' => 'nullable|integer|exists:users,id',
            'id_motivo_ingreso' => 'nullable|integer|exists:motivos,id',
            'id_motivo_egreso' => 'nullable|integer|exists:motivos,id',
            'perfil' => 'nullable|string',
        ]);

        DB::table('clientes')
            ->where('id', $cliente->id)
            ->update(array_merge($validated, ['updated_at' => now()]));

        $updated = DB::table('clientes')
            ->select('clientes.*', 'planes.nombre as plan_nombre')
            ->leftJoin('planes', 'clientes.id_plan', '=', 'planes.id')
            ->where('clientes.id', $cliente->id)
            ->first();

        return response()->json([
            'message' => 'Cliente actualizado correctamente.',
            'cliente' => $updated,
        ]);
    }

    public function adminClienteUpdateById(Request $request, int $id): JsonResponse
    {
        if ($err = $this->requireAdminOrEntrenador($request)) return $err;

        return $this->adminClienteUpdate($request, (string) $id);
    }

    public function adminClientePesos(Request $request, string $slug): JsonResponse
    {
        if ($err = $this->requireAdminOrEntrenador($request)) return $err;

        $cliente = $this->findScopedCliente($request, $slug);
        if (! $cliente) return response()->json(['message' => 'Cliente no encontrado.'], 404);

        return $this->buildAdminClientePesosResponse((int) $cliente->id);
    }

    public function adminClienteAgenda(Request $request, string $slug): JsonResponse
    {
        if ($err = $this->requireAdminOrEntrenador($request)) return $err;

        $cliente = $this->findScopedCliente($request, $slug);
        if (! $cliente) return response()->json(['message' => 'Cliente no encontrado.'], 404);

        return $this->buildAdminClienteAgendaResponse((int) $cliente->id);
    }

    public function adminClienteEntrenamientos(Request $request, string $slug): JsonResponse
    {
        if ($err = $this->requireAdminOrEntrenador($request)) return $err;

        $cliente = $this->findScopedCliente($request, $slug);
        if (! $cliente) return response()->json(['message' => 'Cliente no encontrado.'], 404);

        return $this->buildAdminClienteEntrenamientosResponse((int) $cliente->id);
    }

    public function adminClienteCuotas(Request $request, string $slug): JsonResponse
    {
        if ($err = $this->requireAdminOrEntrenador($request)) return $err;

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

    // ===================================================================
    // ADMIN — METRICAS POR CLIENTE
    // ===================================================================

    public function adminClienteMetricasIndex(Request $request, string $slug): JsonResponse
    {
        if ($err = $this->requireAdminOrEntrenador($request)) return $err;

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
        if ($err = $this->requireAdminOrEntrenador($request)) return $err;

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

    // ===================================================================
    // ADMIN — EJERCICIOS POR CLIENTE
    // ===================================================================

    public function adminClienteEjerciciosIndex(Request $request, string $slug): JsonResponse
    {
        if ($err = $this->requireAdminOrEntrenador($request)) return $err;

        $cliente = $this->findScopedCliente($request, $slug);
        if (! $cliente) return response()->json(['message' => 'Cliente no encontrado.'], 404);

        return $this->buildEjerciciosListResponse($cliente->id);
    }

    public function adminClienteEjercicioHistorial(Request $request, string $slug, int $idEjercicio): JsonResponse
    {
        if ($err = $this->requireAdminOrEntrenador($request)) return $err;

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
            'concepto' => 'required|string|max:255',
            'monto' => 'required|numeric|min:0',
            'fecha' => 'required|date',
            'notas' => 'nullable|string',
        ]);
        $id = DB::table('movimientos_financieros')->insertGetId(array_merge($v, ['created_at' => now(), 'updated_at' => now()]));

        return response()->json(['message' => 'Movimiento registrado.', 'id' => $id], 201);
    }

    public function adminMovimientosUpdate(Request $request, int $id): JsonResponse
    {
        if ($err = $this->requireAdmin($request)) return $err;

        $v = $request->validate([
            'tipo' => 'required|in:ingreso,egreso',
            'concepto' => 'required|string|max:255',
            'monto' => 'required|numeric|min:0',
            'fecha' => 'required|date',
            'notas' => 'nullable|string',
        ]);
        DB::table('movimientos_financieros')->where('id', $id)->update(array_merge($v, ['updated_at' => now()]));

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

        $usuarios = DB::table('users')->orderBy('name')
            ->get(['id', 'name', 'email', 'id_tipo_usuario', 'titulo', 'porcentaje', 'slug', 'created_at'])
            ->map(fn($u) => [
                'id' => (int) $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'id_tipo_usuario' => (int) $u->id_tipo_usuario,
                'titulo' => $u->titulo,
                'porcentaje' => $u->porcentaje,
                'slug' => $u->slug,
                'created_at' => $u->created_at,
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
            'id_tipo_usuario' => 'required|integer|in:1,2,3,4',
            'titulo' => 'nullable|string|max:255',
            'porcentaje' => 'nullable|numeric',
        ]);

        $id = DB::table('users')->insertGetId([
            'name' => $v['name'],
            'email' => $v['email'],
            'password' => Hash::make($v['password']),
            'id_tipo_usuario' => $v['id_tipo_usuario'],
            'titulo' => $v['titulo'] ?? null,
            'porcentaje' => $v['porcentaje'] ?? null,
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
            'id_tipo_usuario' => 'required|integer|in:1,2,3,4',
            'titulo' => 'nullable|string|max:255',
            'porcentaje' => 'nullable|numeric',
            'password' => 'nullable|string|min:6',
        ]);

        $data = [
            'name' => $v['name'],
            'email' => $v['email'],
            'id_tipo_usuario' => $v['id_tipo_usuario'],
            'titulo' => $v['titulo'] ?? null,
            'porcentaje' => $v['porcentaje'] ?? null,
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

        return response()->json(['planes' => DB::table('planes')->orderBy('nombre')->get()]);
    }

    public function adminPlanesStore(Request $request): JsonResponse
    {
        if ($err = $this->requireAdmin($request)) return $err;

        $v  = $request->validate(['nombre' => 'required|string|max:255', 'valor' => 'required|numeric|min:0', 'descripcion' => 'nullable|string']);
        $id = DB::table('planes')->insertGetId(array_merge($v, ['created_at' => now(), 'updated_at' => now()]));

        return response()->json(['message' => 'Plan creado.', 'id' => $id], 201);
    }

    public function adminPlanesUpdate(Request $request, int $id): JsonResponse
    {
        if ($err = $this->requireAdmin($request)) return $err;

        $v = $request->validate(['nombre' => 'required|string|max:255', 'valor' => 'required|numeric|min:0', 'descripcion' => 'nullable|string']);
        DB::table('planes')->where('id', $id)->update(array_merge($v, ['updated_at' => now()]));

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

    public function clienteAgendaCalendario(Request $request): JsonResponse
    {
        $cliente = $this->getClientePorUsuario($request->user());
        if (! $cliente) return response()->json(['message' => 'Perfil de cliente no encontrado.'], 404);

        $desde = $request->query('desde', now()->startOfMonth()->format('Y-m-d'));
        $hasta = $request->query('hasta', now()->endOfMonth()->format('Y-m-d'));

        $sesiones = DB::table('agendas')
            ->where('id_cliente', $cliente->id)
            ->whereBetween(DB::raw('DATE(fecha_inicio)'), [$desde, $hasta])
            ->orderBy('fecha_inicio')
            ->get(['id', 'titulo', 'fecha_inicio', 'fecha_fin', 'estado', 'descripcion']);

        $ids = $sesiones->pluck('id')->toArray();
        $ejerciciosMap = [];
        if (! empty($ids)) {
            $rows = DB::table('agendas_ejercicios')
                ->join('ejercicios', 'ejercicios.id', '=', 'agendas_ejercicios.id_ejercicio')
                ->whereIn('agendas_ejercicios.id_agenda', $ids)
                ->get([
                    'agendas_ejercicios.id_agenda',
                    'ejercicios.nombre',
                    'agendas_ejercicios.serie',
                    'agendas_ejercicios.repeticiones',
                    'agendas_ejercicios.carga',
                    'agendas_ejercicios.descanso'
                ]);
            foreach ($rows as $r) {
                $ejerciciosMap[$r->id_agenda][] = [
                    'nombre' => $r->nombre,
                    'serie' => $r->serie,
                    'repeticiones' => $r->repeticiones,
                    'carga' => $r->carga,
                    'descanso' => $r->descanso,
                ];
            }
        }

        return response()->json([
            'sesiones' => $sesiones->map(fn($s) => array_merge((array) $s, ['ejercicios' => $ejerciciosMap[$s->id] ?? []])),
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
            'cliente' => [
                'id'        => $cliente->id,
                'nombres'   => $cliente->nombres,
                'paterno'   => $cliente->paterno,
                'materno'   => $cliente->materno ?? null,
                'email'     => $cliente->email,
                'telefono'  => $cliente->telefono ?? null,
                'direccion' => $cliente->direccion ?? null,
                'altura'    => isset($cliente->altura) && $cliente->altura !== null ? (float) $cliente->altura : null,
                'slug'      => $cliente->slug,
            ],
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
        ]);

        DB::table('clientes')
            ->where('id', $cliente->id)
            ->update(array_merge($validated, ['updated_at' => now()]));

        $actualizado = DB::table('clientes')->where('id', $cliente->id)->first();

        return response()->json([
            'message' => 'Perfil actualizado correctamente.',
            'cliente' => [
                'id'        => $actualizado->id,
                'nombres'   => $actualizado->nombres,
                'paterno'   => $actualizado->paterno,
                'materno'   => $actualizado->materno ?? null,
                'email'     => $actualizado->email,
                'telefono'  => $actualizado->telefono ?? null,
                'direccion' => $actualizado->direccion ?? null,
                'altura'    => isset($actualizado->altura) && $actualizado->altura !== null ? (float) $actualizado->altura : null,
            ],
        ]);
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

        $validated = $request->validate([
            'respuestas' => 'required|array',
            'respuestas.*.pregunta_id' => 'required|integer',
            'respuestas.*.opcion_ids' => 'nullable|array',
            'respuestas.*.opcion_ids.*' => 'integer',
            'respuestas.*.otro_texto' => 'nullable|string|max:1000',
            'respuestas.*.valor_texto' => 'nullable|string|max:4000',
        ]);

        $questions = $this->getEvaluacionInicialQuestions();
        $inputResponses = collect($validated['respuestas'])->keyBy(fn(array $item) => (int) $item['pregunta_id']);
        $errors = [];
        $rowsToInsert = [];

        foreach ($questions as $question) {
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

            if ($question->tipo === 'text') {
                if ($question->es_requerida && $textValue === '') {
                    $errors["respuestas.{$question->id}"][] = 'Debes ingresar una respuesta.';
                    continue;
                }

                if ($textValue !== '') {
                    $rowsToInsert[] = [
                        'pregunta_id' => (int) $question->id,
                        'opcion_id' => null,
                        'valor_texto' => $textValue,
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

            if ($question->tipo === 'single' && $selectedOptionIds->count() > 1) {
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
                ];
            }
        }

        if (! empty($errors)) {
            return response()->json([
                'message' => 'Revisa los datos de la evaluación inicial.',
                'errors' => $errors,
            ], 422);
        }

        DB::transaction(function () use ($cliente, $rowsToInsert) {
            $evaluacion = EvaluacionInicial::firstOrCreate(
                ['id_cliente' => (int) $cliente->id],
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
            'evaluacion' => $this->buildEvaluacionInicialPayload((int) $cliente->id),
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

        $surveyId = DB::table('surveys')->where('is_active', true)->value('id') ?? 1;

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
                'id_usuario' => $cliente->id_usuario,
                'id_motivo_ingreso' => $cliente->id_motivo_ingreso,
                'id_motivo_egreso' => $cliente->id_motivo_egreso,
                'perfil' => $cliente->perfil,
                'moroso' => $moroso,
            ],
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

        // Una cuota está pagada si: id_estado_pago = 2, o id_forma_pago > 0, o monto_pagado > 0
        $esPagada = fn($c) => $c['id_estado_pago'] === 2
            || (($c['id_forma_pago'] ?? 0) > 0)
            || (($c['monto_pagado'] ?? 0) > 0);
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
        $c = DB::table('clientes')->where('id_usuario', $user->id)->first();
        if (! $c && isset($user->id_cliente) && $user->id_cliente) {
            $c = DB::table('clientes')->where('id', $user->id_cliente)->first();
        }
        return $c;
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
                ];
            }

            if ($respuesta->opcion_id) {
                $answersByQuestion[$preguntaId]['selected_option_ids'][] = (int) $respuesta->opcion_id;

                if ($respuesta->opcion?->es_otro && $respuesta->valor_texto) {
                    $answersByQuestion[$preguntaId]['other_text'] = $respuesta->valor_texto;
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
                            'orden' => (int) $question->orden,
                            'opciones' => $question->opciones->map(fn($option) => [
                                'id' => (int) $option->id,
                                'codigo' => $option->codigo,
                                'etiqueta' => $option->etiqueta,
                                'orden' => (int) $option->orden,
                                'es_otro' => (bool) $option->es_otro,
                            ])->values()->all(),
                            'respuesta' => $answersByQuestion[(int) $question->id] ?? [
                                'selected_option_ids' => [],
                                'other_text' => null,
                                'text_value' => null,
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
        $composicion = $this->buildCompositionSeries($clienteId);
        $perimetros = $this->buildPerimetrosSeries($clienteId);

        return response()->json(['composicion' => $composicion, 'perimetros' => $perimetros]);
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

        foreach ($perimetrosFields as $field => $value) {
            if (isset($perimetrosFieldMap[$field])) {
                $resolvedPerimetros[$perimetrosFieldMap[$field]] = $value;
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
