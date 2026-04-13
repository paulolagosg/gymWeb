<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminController extends Controller
{
    /**
     * Dashboard — estadísticas globales para el panel administrador.
     */
    public function dashboard(Request $request): JsonResponse
    {
        $hoy = now();
        $primerDiaMes = $hoy->copy()->startOfMonth();
        $anioSeleccionado = (int) $request->query('anio', $hoy->year);
        $meses = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];

        $totalActivos = DB::table('clientes')->where('estado', 1)->count();

        $morosos = DB::table('cuentas_corrientes')
            ->whereNull('fecha_pago')
            ->where('fecha_vencimiento', '<', $hoy)
            ->where(fn($q) => $q->whereNull('saldo')->orWhere('saldo', '>', 0))
            ->distinct('id_cliente')
            ->count('id_cliente');

        $alDia = $totalActivos - $morosos;

        $ingresosMes = DB::table('cuentas_corrientes')
            ->whereNotNull('fecha_pago')
            ->whereBetween('fecha_pago', [$primerDiaMes, $hoy])
            ->sum('monto_pagado');

        $totalClientes = DB::table('clientes')->count();

        $clientesPorEdad = [
            ['label' => 'Menos de 20', 'value' => DB::table('clientes')->where('estado', 1)->whereBetween(DB::raw('TIMESTAMPDIFF(YEAR, fecha_nacimiento, CURDATE())'), [0, 19])->count()],
            ['label' => '20-29', 'value' => DB::table('clientes')->where('estado', 1)->whereBetween(DB::raw('TIMESTAMPDIFF(YEAR, fecha_nacimiento, CURDATE())'), [20, 29])->count()],
            ['label' => '30-39', 'value' => DB::table('clientes')->where('estado', 1)->whereBetween(DB::raw('TIMESTAMPDIFF(YEAR, fecha_nacimiento, CURDATE())'), [30, 39])->count()],
            ['label' => '40-49', 'value' => DB::table('clientes')->where('estado', 1)->whereBetween(DB::raw('TIMESTAMPDIFF(YEAR, fecha_nacimiento, CURDATE())'), [40, 49])->count()],
            ['label' => '50+', 'value' => DB::table('clientes')->where('estado', 1)->whereBetween(DB::raw('TIMESTAMPDIFF(YEAR, fecha_nacimiento, CURDATE())'), [50, 150])->count()],
        ];

        $clientesPorGenero = DB::table('clientes')
            ->leftJoin('generos', 'clientes.id_genero', '=', 'generos.id')
            ->where('clientes.estado', 1)
            ->selectRaw("COALESCE(generos.nombre, 'Sin género') as label, COUNT(*) as value")
            ->groupBy('label')
            ->orderByDesc('value')
            ->get()
            ->map(fn($row) => [
                'label' => $row->label,
                'value' => (int) $row->value,
            ])
            ->values();

        $clientesPorPlan = DB::table('clientes')
            ->leftJoin('planes', 'clientes.id_plan', '=', 'planes.id')
            ->where('clientes.estado', 1)
            ->selectRaw("COALESCE(planes.nombre, 'Sin plan') as label, COUNT(*) as value")
            ->groupBy('label')
            ->orderByDesc('value')
            ->get()
            ->map(fn($row) => [
                'label' => $row->label,
                'value' => (int) $row->value,
            ])
            ->values();

        $pagosPorFormaRows = DB::table('cuentas_corrientes')
            ->leftJoin('formas_pagos', 'cuentas_corrientes.id_forma_pago', '=', 'formas_pagos.id')
            ->whereYear('cuentas_corrientes.fecha_pago', $anioSeleccionado)
            ->whereNotNull('cuentas_corrientes.fecha_pago')
            ->selectRaw("COALESCE(formas_pagos.nombre, 'Sin forma de pago') as forma")
            ->selectRaw('MONTH(cuentas_corrientes.fecha_pago) as mes')
            ->selectRaw('SUM(cuentas_corrientes.monto_pagado) as total')
            ->groupBy('forma', 'mes')
            ->orderBy('forma')
            ->orderBy('mes')
            ->get();

        $seriesPorForma = [];
        foreach ($pagosPorFormaRows as $row) {
            $forma = $row->forma;
            if (! isset($seriesPorForma[$forma])) {
                $seriesPorForma[$forma] = array_fill(0, 12, 0);
            }

            $seriesPorForma[$forma][max(0, ((int) $row->mes) - 1)] = (float) $row->total;
        }

        $pagosPorForma = [];
        foreach ($seriesPorForma as $forma => $values) {
            $pagosPorForma[] = [
                'label' => $forma,
                'values' => array_values($values),
            ];
        }

        return response()->json([
            'total_clientes'  => $totalClientes,
            'activos'         => $totalActivos,
            'morosos'         => max(0, $morosos),
            'al_dia'          => max(0, $alDia),
            'ingresos_mes'    => (float) $ingresosMes,
            'clientes_por_edad' => $clientesPorEdad,
            'clientes_por_genero' => $clientesPorGenero,
            'clientes_por_plan' => $clientesPorPlan,
            'pagos_por_forma' => [
                'months' => $meses,
                'series' => $pagosPorForma,
            ],
        ]);
    }

    /**
     * Listado paginado de clientes.
     */
    public function clientes(Request $request): JsonResponse
    {
        $busqueda = $request->query('q', '');
        $perPage  = (int) $request->query('per_page', 20);

        $query = DB::table('clientes')
            ->select(
                'clientes.id',
                'clientes.nombres',
                'clientes.paterno',
                'clientes.materno',
                'clientes.email',
                'clientes.telefono',
                'clientes.slug',
                'clientes.estado',
                'clientes.fecha_ingreso',
                'clientes.fecha_fin',
                'planes.nombre as plan_nombre'
            )
            ->leftJoin('planes', 'clientes.id_plan', '=', 'planes.id')
            ->orderBy('clientes.paterno');

        if ($busqueda !== '') {
            $query->where(function ($q) use ($busqueda) {
                $q->where('clientes.nombres', 'like', "%{$busqueda}%")
                    ->orWhere('clientes.paterno', 'like', "%{$busqueda}%")
                    ->orWhere('clientes.materno', 'like', "%{$busqueda}%")
                    ->orWhere('clientes.email', 'like', "%{$busqueda}%");
            });
        }

        $total  = $query->count();
        $items  = $query->forPage($request->query('page', 1), $perPage)->get();

        $morososIds = DB::table('cuentas_corrientes')
            ->whereNull('fecha_pago')
            ->where('fecha_vencimiento', '<', now())
            ->pluck('id_cliente')
            ->unique()
            ->toArray();

        $result = $items->map(function ($c) use ($morososIds) {
            return [
                'id'             => $c->id,
                'nombre'         => $c->nombres,
                'apellido'       => $c->paterno,
                'nombre_completo' => trim("{$c->nombres} {$c->paterno} " . ($c->materno ?? '')),
                'email'          => $c->email,
                'telefono'       => $c->telefono,
                'slug'           => $c->slug,
                'estado'         => (int) $c->estado,
                'plan'           => $c->plan_nombre,
                'fecha_ingreso'  => $c->fecha_ingreso,
                'fecha_fin'      => $c->fecha_fin,
                'moroso'         => in_array($c->id, $morososIds),
            ];
        });

        return response()->json([
            'data'  => $result,
            'total' => $total,
            'page'  => (int) $request->query('page', 1),
            'per_page' => $perPage,
        ]);
    }

    /**
     * Detalle de un cliente por slug.
     */
    public function clienteDetalle(Request $request, string $slug): JsonResponse
    {
        $cliente = DB::table('clientes')
            ->select(
                'clientes.*',
                'planes.nombre as plan_nombre',
                'planes.valor as plan_valor'
            )
            ->leftJoin('planes', 'clientes.id_plan', '=', 'planes.id')
            ->where('clientes.slug', $slug)
            ->first();

        if (! $cliente) {
            return response()->json(['message' => 'Cliente no encontrado.'], 404);
        }

        // Cuotas más recientes
        $cuotas = DB::table('cuentas_corrientes')
            ->select('id', 'monto_pagar', 'monto_pagado', 'saldo', 'fecha_vencimiento', 'fecha_pago', 'id_estado_pago')
            ->where('id_cliente', $cliente->id)
            ->orderBy('fecha_vencimiento', 'desc')
            ->limit(10)
            ->get();

        // Peso más reciente
        $ultimoPeso = DB::table('pesos')
            ->where('id_cliente', $cliente->id)
            ->orderBy('created_at', 'desc')
            ->first();

        // IMC más reciente
        $ultimoImc = DB::table('imcs')
            ->where('id_cliente', $cliente->id)
            ->orderBy('created_at', 'desc')
            ->first();

        // Próximas sesiones
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
                'id'                 => $cliente->id,
                'nombres'            => $cliente->nombres,
                'paterno'            => $cliente->paterno,
                'materno'            => $cliente->materno,
                'ci'                 => $cliente->ci,
                'email'              => $cliente->email,
                'telefono'           => $cliente->telefono,
                'slug'               => $cliente->slug,
                'estado'             => (int) $cliente->estado,
                'fecha_ingreso'      => $cliente->fecha_ingreso,
                'fecha_fin'          => $cliente->fecha_fin,
                'id_plan'            => $cliente->id_plan,
                'plan'               => $cliente->plan_nombre,
                'plan_valor'         => $cliente->plan_valor,
                'altura'             => $cliente->altura,
                'ciudad'             => $cliente->ciudad,
                'direccion'          => $cliente->direccion,
                'fecha_nacimiento'   => $cliente->fecha_nacimiento,
                'id_usuario'         => $cliente->id_usuario,
                'id_motivo_ingreso'  => $cliente->id_motivo_ingreso,
                'id_motivo_egreso'   => $cliente->id_motivo_egreso,
                'perfil'             => $cliente->perfil,
                'moroso'             => $moroso,
            ],
            'ultimo_peso'  => $ultimoPeso ? (float) $ultimoPeso->peso : null,
            'ultimo_imc'   => $ultimoImc ? (float) $ultimoImc->imc : null,
            'cuotas'       => $cuotas,
            'agenda'       => $agenda,
        ]);
    }

    /**
     * Actualizar datos personales de un cliente.
     */
    public function updateCliente(Request $request, string $slug): JsonResponse
    {
        $cliente = DB::table('clientes')->where('slug', $slug)->first();

        if (! $cliente) {
            return response()->json(['message' => 'Cliente no encontrado.'], 404);
        }

        $validated = $request->validate([
            'nombres'            => 'required|string|max:255',
            'paterno'            => 'required|string|max:255',
            'materno'            => 'nullable|string|max:255',
            'ci'                 => "nullable|string|max:20|unique:clientes,ci,{$cliente->id}",
            'email'              => 'nullable|email|max:255',
            'telefono'           => 'nullable|string|max:50',
            'ciudad'             => 'nullable|string|max:255',
            'direccion'          => 'nullable|string|max:255',
            'fecha_nacimiento'   => 'nullable|date',
            'fecha_ingreso'      => 'nullable|date',
            'fecha_fin'          => 'nullable|date',
            'altura'             => 'nullable|numeric|min:0.5|max:2.5',
            'estado'             => 'nullable|integer|in:0,1',
            'id_plan'            => 'nullable|integer|exists:planes,id',
            'id_usuario'         => 'nullable|integer|exists:users,id',
            'id_motivo_ingreso'  => 'nullable|integer|exists:motivos,id',
            'id_motivo_egreso'   => 'nullable|integer|exists:motivos,id',
            'perfil'             => 'nullable|string',
        ]);

        DB::table('clientes')
            ->where('slug', $slug)
            ->update(array_merge($validated, ['updated_at' => now()]));

        $updated = DB::table('clientes')
            ->select('clientes.*', 'planes.nombre as plan_nombre')
            ->leftJoin('planes', 'clientes.id_plan', '=', 'planes.id')
            ->where('clientes.slug', $slug)
            ->first();

        return response()->json([
            'message' => 'Cliente actualizado correctamente.',
            'cliente' => $updated,
        ]);
    }

    /**
     * Evolución de peso e IMC del cliente.
     */
    public function pesos(Request $request, string $slug): JsonResponse
    {
        $cliente = DB::table('clientes')->where('slug', $slug)->first();
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
            'cliente_id'   => $cliente->id,
            'nombre'       => trim("{$cliente->nombres} {$cliente->paterno}"),
            'altura'       => $cliente->altura,
            'pesos'        => $pesos,
            'imcs'         => $imcs,
        ]);
    }

    /**
     * Agenda completa del cliente (histórica + futura con ejercicios).
     */
    public function agendaCompleta(Request $request, string $slug): JsonResponse
    {
        $cliente = DB::table('clientes')->where('slug', $slug)->first();
        if (! $cliente) {
            return response()->json(['message' => 'Cliente no encontrado.'], 404);
        }

        $sesiones = DB::table('agendas')
            ->where('id_cliente', $cliente->id)
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
                ]);

            foreach ($rows as $row) {
                $ejerciciosMap[$row->id_agenda][] = [
                    'nombre'       => $row->nombre,
                    'serie'        => $row->serie,
                    'repeticiones' => $row->repeticiones,
                    'carga'        => $row->carga,
                ];
            }
        }

        $result = $sesiones->map(fn($s) => [
            'id'           => $s->id,
            'titulo'       => $s->titulo,
            'descripcion'  => $s->descripcion,
            'fecha_inicio' => $s->fecha_inicio,
            'fecha_fin'    => $s->fecha_fin,
            'estado'       => (int) $s->estado,
            'ejercicios'   => $ejerciciosMap[$s->id] ?? [],
        ]);

        return response()->json([
            'total'  => $sesiones->count(),
            'agenda' => $result,
        ]);
    }

    /**
     * Entrenamientos agrupados por estado (con ejercicios).
     */
    public function entrenamientos(Request $request, string $slug): JsonResponse
    {
        $cliente = DB::table('clientes')->where('slug', $slug)->first();
        if (! $cliente) {
            return response()->json(['message' => 'Cliente no encontrado.'], 404);
        }

        $sesiones = DB::table('agendas')
            ->where('id_cliente', $cliente->id)
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
                ]);

            foreach ($rows as $row) {
                $ejerciciosMap[(int) $row->id_agenda][] = [
                    'nombre'       => $row->nombre,
                    'serie'        => $row->serie,
                    'repeticiones' => $row->repeticiones,
                    'carga'        => $row->carga,
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
                'id'           => $s->id,
                'titulo'       => $s->titulo,
                'fecha_inicio' => $s->fecha_inicio,
                'estado'       => $key,
                'ejercicios'   => $ejerciciosMap[$s->id] ?? [],
            ];
        }

        $result = array_map(fn($label, $items) => [
            'estado_label' => $label,
            'count'        => count($items),
            'sesiones'     => $items,
        ], array_keys($agrupado), array_values($agrupado));

        return response()->json([
            'total'  => $sesiones->count(),
            'grupos' => array_values($result),
        ]);
    }

    /**
     * Cuenta corriente completa del cliente.
     */
    public function cuotas(Request $request, string $slug): JsonResponse
    {
        $cliente = DB::table('clientes')->where('slug', $slug)->first();
        if (! $cliente) {
            return response()->json(['message' => 'Cliente no encontrado.'], 404);
        }

        $cuotas = DB::table('cuentas_corrientes')
            ->where('id_cliente', $cliente->id)
            ->orderBy('fecha_vencimiento', 'desc')
            ->get(['id', 'monto_pagar', 'monto_pagado', 'saldo', 'fecha_vencimiento', 'fecha_pago', 'id_estado_pago']);

        $pagadas   = $cuotas->whereNotNull('fecha_pago');
        $pendientes = $cuotas->whereNull('fecha_pago');

        return response()->json([
            'cuotas'  => $cuotas,
            'totales' => [
                'total_cobrado'      => (float) $cuotas->sum('monto_pagar'),
                'total_pagado'       => (float) $pagadas->sum('monto_pagado'),
                'total_pendiente'    => (float) $pendientes->sum('monto_pagar'),
                'cuotas_pagadas'     => $pagadas->count(),
                'cuotas_pendientes'  => $pendientes->count(),
            ],
        ]);
    }

    public function agendaCatalogo(): JsonResponse
    {
        $entrenadores = DB::table('users')
            ->where('id_tipo_usuario', 2)
            ->orderBy('name')
            ->get(['id', 'name', 'titulo'])
            ->map(fn($row) => [
                'id' => (int) $row->id,
                'label' => trim($row->name . ($row->titulo ? ' · ' . $row->titulo : '')),
            ])
            ->values();

        $clientes = DB::table('clientes')
            ->where('estado', 1)
            ->orderBy('nombres')
            ->get(['id', 'slug', 'nombres', 'paterno', 'materno'])
            ->map(fn($row) => [
                'id' => (int) $row->id,
                'slug' => $row->slug,
                'label' => trim($row->nombres . ' ' . $row->paterno . ' ' . ($row->materno ?? '')),
            ])
            ->values();

        $ejercicios = DB::table('ejercicios')
            ->whereIn('estado', ['b', '1', 1])
            ->orderBy('nombre')
            ->get(['id', 'nombre'])
            ->map(fn($row) => [
                'id' => (int) $row->id,
                'label' => $row->nombre,
            ])
            ->values();

        return response()->json([
            'entrenadores' => $entrenadores,
            'clientes' => $clientes,
            'ejercicios' => $ejercicios,
        ]);
    }

    public function storeAgenda(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'id_cliente' => 'required|integer|exists:clientes,id',
            'id_usuario' => 'required|integer|exists:users,id',
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date|after:fecha_inicio',
            'titulo' => 'nullable|string|max:255',
            'descripcion' => 'nullable|string',
            'recurrencia' => 'nullable|in:ninguna,mensual,trimestral,semestral,anual',
            'dias_semana' => 'nullable|array',
            'dias_semana.*' => 'integer|min:0|max:6',
            'ejercicios' => 'required|array|min:1',
            'ejercicios.*.id_ejercicio' => 'required|integer|exists:ejercicios,id',
            'ejercicios.*.serie' => 'required|integer|min:1|max:100',
            'ejercicios.*.repeticiones' => 'required|string|max:50',
            'ejercicios.*.carga' => 'nullable|string|max:50',
            'ejercicios.*.descanso' => 'nullable|string|max:50',
        ]);

        $fechaInicio = Carbon::parse($validated['fecha_inicio'], config('app.timezone'));
        $fechaFin = Carbon::parse($validated['fecha_fin'], config('app.timezone'));
        $recurrencia = $validated['recurrencia'] ?? 'ninguna';
        $diasSemana = array_values(array_unique(array_map('intval', $validated['dias_semana'] ?? [])));
        $diaInicio = $fechaInicio->dayOfWeek;
        if ($recurrencia !== 'ninguna' && ! in_array($diaInicio, $diasSemana, true)) {
            $diasSemana[] = $diaInicio;
        }

        $agendaSlots = [];
        switch ($recurrencia) {
            case 'mensual':
                $limite = $fechaInicio->copy()->endOfMonth();
                $cursor = $fechaInicio->copy();
                while ($cursor->lte($limite)) {
                    if ($cursor->gte($fechaInicio) && in_array($cursor->dayOfWeek, $diasSemana, true)) {
                        $agendaSlots[] = [
                            'fecha_inicio' => $cursor->copy()->setTimeFrom($fechaInicio),
                            'fecha_fin' => $cursor->copy()->setTimeFrom($fechaFin),
                        ];
                    }
                    $cursor->addDay();
                }
                break;
            case 'trimestral':
                $mesInicioTrimestre = intval(($fechaInicio->month - 1) / 3) * 3 + 1;
                $limite = $fechaInicio->copy()->month($mesInicioTrimestre)->startOfMonth()->addMonths(3)->subDay();
                $cursor = $fechaInicio->copy();
                while ($cursor->lte($limite)) {
                    if ($cursor->gte($fechaInicio) && in_array($cursor->dayOfWeek, $diasSemana, true)) {
                        $agendaSlots[] = [
                            'fecha_inicio' => $cursor->copy()->setTimeFrom($fechaInicio),
                            'fecha_fin' => $cursor->copy()->setTimeFrom($fechaFin),
                        ];
                    }
                    $cursor->addDay();
                }
                break;
            case 'semestral':
                $mesInicioSemestre = $fechaInicio->month <= 6 ? 1 : 7;
                $limite = $fechaInicio->copy()->month($mesInicioSemestre)->startOfMonth()->addMonths(6)->subDay();
                $cursor = $fechaInicio->copy();
                while ($cursor->lte($limite)) {
                    if ($cursor->gte($fechaInicio) && in_array($cursor->dayOfWeek, $diasSemana, true)) {
                        $agendaSlots[] = [
                            'fecha_inicio' => $cursor->copy()->setTimeFrom($fechaInicio),
                            'fecha_fin' => $cursor->copy()->setTimeFrom($fechaFin),
                        ];
                    }
                    $cursor->addDay();
                }
                break;
            case 'anual':
                $limite = $fechaInicio->copy()->endOfYear();
                $cursor = $fechaInicio->copy();
                while ($cursor->lte($limite)) {
                    if ($cursor->gte($fechaInicio) && in_array($cursor->dayOfWeek, $diasSemana, true)) {
                        $agendaSlots[] = [
                            'fecha_inicio' => $cursor->copy()->setTimeFrom($fechaInicio),
                            'fecha_fin' => $cursor->copy()->setTimeFrom($fechaFin),
                        ];
                    }
                    $cursor->addDay();
                }
                break;
            default:
                $agendaSlots[] = [
                    'fecha_inicio' => $fechaInicio->copy(),
                    'fecha_fin' => $fechaFin->copy(),
                ];
                break;
        }

        $agendaIds = DB::transaction(function () use ($agendaSlots, $validated) {
            $agendaIds = [];

            foreach ($agendaSlots as $slot) {
                $slug = hash('sha256', $validated['id_cliente'] . $validated['id_usuario'] . $slot['fecha_inicio']->format('Y-m-d H:i:s') . microtime(true) . random_int(1, 10000));

                $agendaId = DB::table('agendas')->insertGetId([
                    'id_cliente' => $validated['id_cliente'],
                    'id_usuario' => $validated['id_usuario'],
                    'fecha_inicio' => $slot['fecha_inicio'],
                    'fecha_fin' => $slot['fecha_fin'],
                    'titulo' => $validated['titulo'] ?? 'Entrenamiento personalizado',
                    'descripcion' => $validated['descripcion'] ?? null,
                    'slug' => $slug,
                    'estado' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $rows = array_map(fn($ejercicio) => [
                    'id_agenda' => $agendaId,
                    'id_ejercicio' => $ejercicio['id_ejercicio'],
                    'serie' => $ejercicio['serie'],
                    'repeticiones' => $ejercicio['repeticiones'],
                    'carga' => $ejercicio['carga'] ?? null,
                    'descanso' => $ejercicio['descanso'] ?? null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ], $validated['ejercicios']);

                DB::table('agendas_ejercicios')->insert($rows);
                $agendaIds[] = (int) $agendaId;
            }

            return $agendaIds;
        });

        return response()->json([
            'message' => count($agendaIds) > 1
                ? 'Entrenamientos agendados correctamente.'
                : 'Entrenamiento agendado correctamente.',
            'agenda_id' => $agendaIds[0] ?? null,
            'agenda_ids' => $agendaIds,
            'created_count' => count($agendaIds),
        ], 201);
    }
}
