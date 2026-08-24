<?php

namespace App\Http\Controllers;

use App\Models\Clientes;
use App\Models\CuentasCorrientes;
use App\Models\Gimnasios;
use App\Models\Motivos;
use App\Models\PagoEntrenador;
use App\Models\SurveyResponse;
use App\Models\User;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function portada(Request $request)
    {
        $user = Auth::user();

        if ($user && in_array((int) $user->id_tipo_usuario, [1, 2, 10], true)) {
            return redirect()->route('dashboard');
        }

        if ($user && in_array((int) $user->id_tipo_usuario, [3, 4, 5], true)) {
            $cliente = $user->cliente ?? Clientes::find($user->id_cliente);

            if ($cliente && $cliente->slug) {
                return redirect()->route('clientes.agenda', $cliente->slug);
            }

            return redirect()->route('clientes.portada');
        }

        $slug = $user && (int) $user->id_tipo_usuario === 2 ? $user->slug : null;

        return view('portada', compact('slug'));
    }

    public function panel(Request $request)
    {
        return $this->portada($request);
    }

    private function formatYearMonthSql(string $column): string
    {
        return DB::getDriverName() === 'sqlite'
            ? "strftime('%Y-%m', {$column})"
            : "DATE_FORMAT({$column}, '%Y-%m')";
    }

    private function formatMonthSql(string $column): string
    {
        return DB::getDriverName() === 'sqlite'
            ? "strftime('%m', {$column})"
            : "DATE_FORMAT({$column}, '%m')";
    }

    private function formatYearMonthFromPartsSql(string $yearColumn, string $monthColumn): string
    {
        return DB::getDriverName() === 'sqlite'
            ? "printf('%04d-%02d', {$yearColumn}, {$monthColumn})"
            : "DATE_FORMAT(CONCAT({$yearColumn}, '-', LPAD({$monthColumn}, 2, '0'), '-01'), '%Y-%m')";
    }

    private function hourSql(string $column): string
    {
        return DB::getDriverName() === 'sqlite'
            ? "CAST(strftime('%H', {$column}) AS INTEGER)"
            : "HOUR({$column})";
    }

    private function ageYearsSql(string $column): string
    {
        return DB::getDriverName() === 'sqlite'
            ? "CAST((julianday('now') - julianday({$column})) / 365.25 AS INTEGER)"
            : "TIMESTAMPDIFF(YEAR, {$column}, CURDATE())";
    }

    public function index(Request $request)
    {

        $mesesNombres = [
            '01' => 'enero',
            '02' => 'febrero',
            '03' => 'marzo',
            '04' => 'abril',
            '05' => 'mayo',
            '06' => 'junio',
            '07' => 'julio',
            '08' => 'agosto',
            '09' => 'septiembre',
            '10' => 'octubre',
            '11' => 'noviembre',
            '12' => 'diciembre',
        ];

        $añoSeleccionado = $request->input('anio', Carbon::now()->year);

        $meses = [];
        for ($m = 1; $m <= 12; $m++) {
            $meses[] = $mesesNombres[sprintf('%02d', $m)];
        }
        $user = Auth::user();
        $esSuperAdmin = (int) $user->id_tipo_usuario === 10;
        $gimnasios = Gimnasios::where('estado', 1)->orderBy('nombre')->get();
        $idGimnasio = $esSuperAdmin
            ? ($request->filled('id_gimnasio') ? (int) $request->input('id_gimnasio') : null)
            : Gimnasios::gimnasioActualId();
        $gimnasioSeleccionado = $idGimnasio;
        DB::enableQueryLog();
        // Obtener ingresos mensuales
        if (in_array((int) $user->id_tipo_usuario, [1, 10], true)) {
            // Para admin: ingresos desde cuentas_corrientes
            $ingresosQuery = CuentasCorrientes::select(
                DB::raw($this->formatYearMonthSql('fecha_vencimiento') . ' as mes'),
                DB::raw("SUM(monto_pagado) as total")
            )
                ->join('clientes', 'clientes.id', '=', 'cuentas_corrientes.id_cliente')
                ->when($idGimnasio, function ($query) use ($idGimnasio) {
                    $query->where('clientes.id_gimnasio', $idGimnasio);
                })
                ->whereYear('cuentas_corrientes.fecha_pago', $añoSeleccionado)
                ->whereNotNull('cuentas_corrientes.fecha_pago');

            $ingresosDB = $ingresosQuery
                ->groupBy('mes')
                ->orderBy('mes')
                ->get()
                ->pluck('total', 'mes')
                ->toArray();
        } else {
            // Para entrenadores: ingresos desde pagos_entrenadores
            $ingresosDB = \App\Models\PagoEntrenador::select(
                DB::raw($this->formatYearMonthFromPartsSql('year', 'month') . ' as mes'),
                DB::raw("SUM(total) as total")
            )
                ->where('entrenador_id', $user->id)
                ->where('year', $añoSeleccionado)
                ->groupBy('mes')
                ->orderBy('mes')
                ->get()
                ->pluck('total', 'mes')
                ->toArray();
        }
        //dd(DB::getQueryLog());
        $ingresosMensuales = [];
        foreach (range(1, 12) as $m) {
            $mesNum = sprintf('%02d', $m);
            $mesNombre = $mesesNombres[$mesNum];
            $claveMes = $añoSeleccionado . '-' . $mesNum;
            $ingresosMensuales[] = [
                'mes' => $mesNombre,
                'total' => isset($ingresosDB[$claveMes]) ? $ingresosDB[$claveMes] : 0
            ];
        }

        // Para el selector de años
        $primerAnio = 2024;
        $anios = range($primerAnio, Carbon::now()->addYears(1)->year);


        $formasPago = \App\Models\FormasPagos::select('id', 'nombre', 'icono', 'color')
            ->where('estado', 1) // Solo formas de pago activas
            ->get()
            ->pluck('nombre', 'id')
            ->toArray();
        // Obtener todas las formas de pago
        $formasPagoList = \App\Models\FormasPagos::all();


        // // Consultar los montos pagados agrupados por forma de pago y mes
        $pagosPorForma = \App\Models\CuentasCorrientes::select(
            'id_forma_pago',
            DB::raw($this->formatYearMonthSql('cuentas_corrientes.fecha_pago') . ' as mes'),
            DB::raw("SUM(monto_pagado) as total")
        )
            ->join('clientes', 'clientes.id', '=', 'cuentas_corrientes.id_cliente')
            ->when($idGimnasio, function ($query) use ($idGimnasio) {
                $query->where('clientes.id_gimnasio', $idGimnasio);
            })
            ->whereYear('cuentas_corrientes.fecha_pago', $añoSeleccionado)
            ->whereNotNull('cuentas_corrientes.fecha_pago')
            ->groupBy('id_forma_pago', 'mes')
            ->get();

        $formasPagoMensual = [];
        foreach ($formasPagoList as $fp) {
            foreach (range(1, 12) as $m) {
                $mesNombre = $mesesNombres[sprintf('%02d', $m)];
                $formasPagoMensual[$fp->nombre][$mesNombre] = 0;
            }
        }

        // Llenar la estructura con los datos reales
        foreach ($pagosPorForma as $pago) {
            $mesNum = substr($pago->mes, 5, 2);
            $mesNombre = $mesesNombres[$mesNum];
            $forma = $formasPagoList->firstWhere('id', $pago->id_forma_pago);
            if ($forma) {
                $formasPagoMensual[$forma->nombre][$mesNombre] = $pago->total;
            }
        }

        $mesActual = Carbon::now()->format('Y-m');
        $im = [];
        if (in_array((int) $user->id_tipo_usuario, [1, 10], true)) {
            $clientes = Clientes::with(['cuotas', 'plan'])
                ->when($idGimnasio, function ($query) use ($idGimnasio) {
                    $query->where('id_gimnasio', $idGimnasio);
                })
                ->where('estado', 1)
                ->get();
            // $im = collect(DB::select('call ObtenerTotalesMensuales(?)', [$añoSeleccionado]))
            //     ->map(function ($item) {
            //         return (array)$item;
            //     })
            //     ->toArray();
        } else {
            $clientes = Clientes::with(['cuotas', 'plan'])
                ->where('id_usuario', $user->id)
                ->when($idGimnasio, function ($query) use ($idGimnasio) {
                    $query->where('id_gimnasio', $idGimnasio);
                })
                ->where('estado', 1)
                ->get();
            // $im = collect(DB::select('CALL GenerarReporteRentaConAcumuladoAnual(?, ?)', [$añoSeleccionado, $user->id]))
            //     ->map(function ($item) {
            //         return (array)$item;
            //     })
            //     ->toArray();
        }
        $clientesIds = $clientes->pluck('id');


        $fechaActual = Carbon::now()->toDateString();
        $clientesMorosos = 0;
        $clientesAlDia = 0;

        /*foreach ($clientes as $cliente) {
            $esMoroso = false;

            foreach ($cliente->cuotas as $cuota) {
                if (
                    $cuota->monto_pagado < $cuota->monto_pagar &&
                    $cuota->fecha_vencimiento <= $fechaActual
                ) {
                    $esMoroso = true;
                    break;
                }
            }

            if ($esMoroso) {
                $clientesMorosos++;
            } else {
                $clientesAlDia++; // Si no hay cuotas vencidas o todas están pagadas
            }
        }*/
        foreach ($clientes as $cliente) {
            $esMoroso = false;

            // Verificamos si el cliente tiene cuotas
            if ($cliente->cuotas->isEmpty()) {
                // Si no tiene cuotas, verificamos la fecha_fin
                if ($cliente->fecha_fin >= $fechaActual) {
                    $esMoroso = true;
                }
            } else {
                // Si tiene cuotas, revisamos cada cuota
                foreach ($cliente->cuotas as $cuota) {
                    if (
                        $cuota->monto_pagado < $cuota->monto_pagar &&
                        $cuota->fecha_vencimiento <= $fechaActual
                    ) {
                        $esMoroso = true;
                        break;
                    }
                }
            }

            if ($esMoroso) {
                $clientesMorosos++;
            } else {
                $clientesAlDia++; // Si no hay cuotas vencidas o todas están pagadas
            }
        }

        $totalClientes = $clientes->count();


        $horarios = [
            'Mañana' => [6, 12],   // 06:00 a 11:59
            'Tarde'  => [12, 18],  // 12:00 a 18:59
            'Noche'  => [18, 24],  // 19:00 a 23:59
        ];

        $picosHorarios = [];
        foreach ($horarios as $nombre => [$inicio, $fin]) {
            $picosHorarios[$nombre] = \App\Models\Agendas::whereBetween(DB::raw($this->hourSql('fecha_inicio')), [$inicio, $fin - 1])
                ->when($idGimnasio, function ($query) use ($idGimnasio) {
                    $query->where('id_gimnasio', $idGimnasio);
                })
                ->whereYear('fecha_inicio', $añoSeleccionado)
                ->count();
        }

        // Obtener clientes por tipo de plan
        $clientesPorTipoPlan = $clientes->groupBy(fn($c) => $c->plan->id ?? 'Sin plan')
            ->map(fn($group) => $group->count());

        $clientesPorNombrePlan = $clientes->groupBy(function ($c) {
            return $c->plan ? $c->plan->nombre : 'Sin plan';
        })->map(function ($group) {
            return $group->count();
        });

        $clientesPorGenero = Clientes::whereIn('clientes.id', $clientesIds)
            ->where('clientes.estado', 1)
            ->join('generos', 'clientes.id_genero', '=', 'generos.id')
            ->select('generos.nombre as genero', DB::raw('count(*) as cantidad'))
            ->groupBy('generos.nombre')
            ->pluck('cantidad', 'genero');

        // Por rango de edad
        $rangos = [
            'Menos de 20' => [0, 19],
            '20-29' => [20, 29],
            '30-39' => [30, 39],
            '40-49' => [40, 49],
            '50+'   => [50, 150],
        ];
        $clientesPorEdad = [];
        foreach ($rangos as $label => [$min, $max]) {
            $clientesPorEdad[$label] = Clientes::whereIn('id', $clientesIds)
                ->whereBetween(DB::raw($this->ageYearsSql('fecha_nacimiento')), [$min, $max])
                ->count();
        }
        DB::enableQueryLog();
        // Obtener ingresos proyectados por mes
        $proyectadosQuery = CuentasCorrientes::select(
            DB::raw($this->formatYearMonthSql('fecha_vencimiento') . ' as mes'),
            DB::raw("SUM(monto_pagar) as total")
        )
            ->whereYear('fecha_vencimiento', $añoSeleccionado);


        if (! in_array((int) $user->id_tipo_usuario, [1, 10], true)) {
            $porcentaje = $user->porcentaje ?? 0;
            $proyectadosQuery = CuentasCorrientes::select(
                DB::raw($this->formatYearMonthSql('fecha_vencimiento') . ' as mes'),
                DB::raw("SUM(monto_pagar * (" . $porcentaje . "/100)) as total")
            )
                ->whereYear('fecha_vencimiento', $añoSeleccionado);
            $proyectadosQuery->whereIn('id_cliente', $clientesIds);
        }

        $proyectadosDB = $proyectadosQuery
            ->groupBy('mes')
            ->orderBy('mes')
            ->get()
            ->pluck('total', 'mes')
            ->toArray();
        //dd(DB::getQueryLog());

        $ingresosProyectados = [];
        foreach (range(1, 12) as $m) {
            $mesNum = sprintf('%02d', $m);
            $mesNombre = $mesesNombres[$mesNum];
            $claveMes = $añoSeleccionado . '-' . $mesNum;
            $ingresosProyectados[] = [
                'mes' => $mesNombre,
                'total' => isset($proyectadosDB[$claveMes]) ? $proyectadosDB[$claveMes] : 0
            ];
        }
        //clientes por entrenador
        $clientesPorEntrenador = $this->obtenerClientesAcumuladosPorAnio($añoSeleccionado);

        $entrenadores = $clientesPorEntrenador->groupBy('entrenador');

        $mesesClientes = $clientesPorEntrenador->pluck('mes')->unique()->sort()->values();

        $datasetsClientesPorEntrenador  = [];

        foreach ($entrenadores as $nombre => $registros) {
            // Mapear los datos para que coincidan con todos los meses
            $data = [];
            foreach ($mesesClientes as $mes) {
                $registro = $registros->firstWhere('mes', $mes);
                $data[] = $registro ? $registro->total_acumulado : 0;
            }

            $datasetsClientesPorEntrenador[] = [
                'label' => $nombre,
                'data' => $data,
                'backgroundColor' => 'rgba(' . rand(0, 255) . ',' . rand(0, 255) . ',' . rand(0, 255) . ', 0.5)',
                'borderColor' => 'rgba(' . rand(0, 255) . ',' . rand(0, 255) . ',' . rand(0, 255) . ', 1)',
                'borderWidth' => 1,
                'fill' => false,
            ];
        }

        $reporte = [];
        $surveyData = [];
        $npsData = [];
        $wordCloudData = [];
        $motivos = [];
        $motivosIngreso = collect();
        $motivosEgreso = collect();
        $servqualAverages = [];
        $servqualSummary = [];
        $servqualLabelNames = [];
        $servqualValueNames = [];
        $topWords = [];
        $ingresosMesesChart = collect($ingresosMensuales)->pluck('mes')->values()->all();
        $ingresosTotalesChart = collect($ingresosMensuales)->pluck('total')->values()->all();
        $ingresosProyectadosChart = collect($ingresosProyectados)->pluck('total')->values()->all();
        $formasPagoNombresChart = array_keys($formasPagoMensual);
        $formasPagoDatasetsChart = collect($formasPagoMensual)->map(function ($valores, $nombre) {
            return [
                'label' => $nombre,
                'data' => array_values($valores),
                'borderWidth' => 2,
                'fill' => false,
            ];
        })->values()->all();
        $picosLabelsChart = array_keys($picosHorarios);
        $picosDataChart = array_values($picosHorarios);
        $nombrePlanLabelsChart = $clientesPorNombrePlan->keys()->values()->all();
        $nombrePlanDataChart = $clientesPorNombrePlan->values()->values()->all();
        $mesesClientesChart = $mesesClientes->values()->all();
        $datasetsClientesPorEntrenadorChart = $datasetsClientesPorEntrenador;

        return view('dashboard', [
            'totalClientes' => $totalClientes,
            'clientesMorosos' => $clientesMorosos,
            'clientesAlDia' => $clientesAlDia,
            'ingresosMensuales' => $ingresosMensuales,
            'meses' => $meses,
            'anios' => $anios,
            'añoSeleccionado' => $añoSeleccionado,
            'picosHorarios' => $picosHorarios,
            'clientesPorNombrePlan' => $clientesPorNombrePlan,
            'clientesPorGenero' => $clientesPorGenero,
            'clientesPorEdad' => $clientesPorEdad,
            'ingresosProyectados' => $ingresosProyectados,
            'im' => $im,
            'clientesPorEntrenador' => $clientesPorEntrenador,
            'mesesClientes' => $mesesClientes,
            'datasetsClientesPorEntrenador' => $datasetsClientesPorEntrenador,
            'reporte' => $reporte,
            'surveyData' => $surveyData,
            'npsData' => $npsData,
            'wordCloudData' => $wordCloudData,
            'motivos' => $motivos,
            'motivosIngreso' => $motivosIngreso,
            'motivosEgreso' => $motivosEgreso,
            'servqualAverages' => $servqualAverages,
            'servqualSummary' => $servqualSummary,
            'servqualLabelNames' => $servqualLabelNames,
            'servqualValueNames' => $servqualValueNames,
            'topWords' => $topWords,
            'ingresosMesesChart' => $ingresosMesesChart,
            'ingresosTotalesChart' => $ingresosTotalesChart,
            'ingresosProyectadosChart' => $ingresosProyectadosChart,
            'formasPagoNombresChart' => $formasPagoNombresChart,
            'formasPagoDatasetsChart' => $formasPagoDatasetsChart,
            'picosLabelsChart' => $picosLabelsChart,
            'picosDataChart' => $picosDataChart,
            'nombrePlanLabelsChart' => $nombrePlanLabelsChart,
            'nombrePlanDataChart' => $nombrePlanDataChart,
            'mesesClientesChart' => $mesesClientesChart,
            'datasetsClientesPorEntrenadorChart' => $datasetsClientesPorEntrenadorChart,
            'formasPago' => $formasPago,
            'formasPagoMensual' => $formasPagoMensual,
            'gimnasios' => $gimnasios,
            'gimnasioSeleccionado' => $gimnasioSeleccionado,
            'esSuperAdmin' => $esSuperAdmin,
        ]);
    }

    public function getDashboardData(Request $request)
    {
        $añoSeleccionado = $request->input('anio', Carbon::now()->year);
        $user = Auth::user();
        $idGimnasio = Gimnasios::gimnasioActualId();

        // Datos para tarjetas
        $clientesQuery = Clientes::query()
            ->when($idGimnasio, function ($query) use ($idGimnasio) {
                $query->where('id_gimnasio', $idGimnasio);
            });
        if (! in_array((int) $user->id_tipo_usuario, [1, 10], true)) {
            $clientesQuery->where('id_usuario', $user->id);
        }
        $clientes = $clientesQuery->where('estado', 1)->get();
        $totalClientes = $clientes->count();

        $fechaActual = Carbon::now()->toDateString();
        $clientesMorosos = 0;
        foreach ($clientes as $cliente) {
            $esMoroso = $cliente->cuotas()
                ->where('monto_pagado', '<', DB::raw('monto_pagar'))
                ->where('fecha_vencimiento', '<=', $fechaActual)
                ->exists();
            if ($esMoroso) {
                $clientesMorosos++;
            }
        }
        $clientesAlDia = $totalClientes - $clientesMorosos;

        // Ingresos mensuales
        $ingresosQuery = CuentasCorrientes::select(
            DB::raw($this->formatMonthSql('fecha_pago') . ' as mes'),
            DB::raw("SUM(monto_pagado) as total")
        )
            ->whereYear('fecha_pago', $añoSeleccionado)
            ->whereNotNull('fecha_pago');

        if (! in_array((int) $user->id_tipo_usuario, [1, 10], true)) {
            $clientesIds = $clientes->pluck('id');
            $ingresosQuery->whereIn('id_cliente', $clientesIds);
        }

        $ingresosDB = $ingresosQuery->groupBy('mes')->pluck('total', 'mes')->toArray();
        $ingresosMensuales = array_fill(1, 12, 0);
        foreach ($ingresosDB as $mes => $total) {
            $ingresosMensuales[(int)$mes] = $total;
        }

        return response()->json([
            'totalClientes' => $totalClientes,
            'clientesMorosos' => $clientesMorosos,
            'clientesAlDia' => $clientesAlDia,
            'ingresosMensuales' => array_values($ingresosMensuales),
        ]);
    }

    private function obtenerClientesAcumuladosPorAnio($anio)
    {
        $idGimnasio = Gimnasios::gimnasioActualId();

        return PagoEntrenador::query()
            ->join('users', 'users.id', '=', 'pagos_entrenadores.entrenador_id')
            ->when($idGimnasio, function ($query) use ($idGimnasio) {
                $query->where('users.id_gimnasio', $idGimnasio);
            })
            ->where('pagos_entrenadores.year', $anio)
            ->select(
                'users.name as entrenador',
                DB::raw($this->formatYearMonthFromPartsSql('pagos_entrenadores.year', 'pagos_entrenadores.month') . ' as mes'),
                DB::raw('SUM(total) as total_acumulado')
            )
            ->groupBy('users.name', 'pagos_entrenadores.year', 'pagos_entrenadores.month')
            ->orderBy('pagos_entrenadores.year')
            ->orderBy('pagos_entrenadores.month')
            ->get();
    }

    function obtenerTasaRetencionMensualPorEntrenador($anio)
    {
        $todosMeses = collect(range(1, 12))->map(function ($mes) use ($anio) {
            return Carbon::create($anio, $mes, 1)->format('Y-m');
        });

        $idGimnasio = Gimnasios::gimnasioActualId();

        $entrenadores = DB::table('users')
            ->where('id_tipo_usuario', 2)
            ->when($idGimnasio, function ($query) use ($idGimnasio) {
                $query->where('id_gimnasio', $idGimnasio);
            })
            ->whereIn('id', function ($query) use ($idGimnasio) {
                $query->select('id_usuario')->from('clientes');

                if ($idGimnasio) {
                    $query->where('id_gimnasio', $idGimnasio);
                }
            })
            ->pluck('name', 'id');

        $resultados = [];

        foreach ($entrenadores as $id_entrenador => $nombre_entrenador) {
            $activosMesAnterior = collect();

            foreach ($todosMeses as $i => $mes) {
                $finMes = Carbon::createFromFormat('Y-m', $mes)->endOfMonth()->toDateString();

                // Clientes activos al final de este mes
                $activosFin = DB::table('clientes')
                    ->where('id_usuario', $id_entrenador)
                    ->when($idGimnasio, function ($query) use ($idGimnasio) {
                        $query->where('id_gimnasio', $idGimnasio);
                    })
                    ->where('estado', 1)
                    ->where('fecha_ingreso', '<=', $finMes)
                    ->where(function ($q) use ($finMes) {
                        $q->whereNull('fecha_baja')
                            ->orWhere('fecha_baja', '>', $finMes);
                    })
                    ->pluck('id');

                if ($i == 0) {
                    // Primer mes, no hay mes anterior
                    $tasaRetencion = null;
                } else {
                    // Retenidos: clientes que estaban activos al final del mes anterior y siguen activos al final de este mes
                    $retenidos = $activosFin->intersect($activosMesAnterior)->count();
                    $tasaRetencion = $activosMesAnterior->count() > 0
                        ? round(($retenidos / $activosMesAnterior->count()) * 100, 2)
                        : null;
                }

                $resultados[] = (object) [
                    'entrenador' => $nombre_entrenador,
                    'mes' => $mes,
                    'tasa_retencion' => $tasaRetencion,
                    'activos_mes_anterior' => $activosMesAnterior->count(),
                    'activos_fin' => $activosFin->count(),
                ];

                // Para el próximo ciclo, el mes actual será el anterior
                $activosMesAnterior = $activosFin;
            }
        }

        return collect($resultados);
    }
}
