<?php

namespace App\Http\Controllers;

use App\Models\Agendas;
use App\Models\ClasificacionesEntrenadores;
use App\Models\Clientes;
use App\Models\EncuestaSatisfaccion;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EntrenadoresController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $entrenadores = \App\Models\User::where('id_tipo_usuario', 2)
            ->with(['clientesActivos'])
            ->get();

        return view('entrenadores.index', compact('entrenadores'));
    }

    public function portada_opciones($slug)
    {
        $entrenador = \App\Models\User::where('slug', $slug)->firstOrFail();
        $userId = $entrenador->id;

        $fechaActual = Carbon::now()->toDateString();

        $sesionesSemana = Agendas::selectRaw('YEARWEEK(fecha_inicio) as semana, COUNT(*) as total')
            ->where('id_usuario', $userId)
            ->groupBy('semana')
            ->orderBy('semana', 'desc')
            ->get();

        $retencion = max($this->evolucionRetencionPorMes('2025-01-01', $userId));

        $clientes_activos = $entrenador->clientesActivos()->count();
        $categoria = $entrenador->clasificacion ? $entrenador->clasificacion->nombre : 'Sin categoría';
        $titulo = $entrenador->titulo ? $entrenador->titulo : 'Sin título profesional u otro';
        $evaluacion = \App\Models\EvaluacionEntrenador::where('id_entrenador', $userId)
            ->whereRaw("updated_at = (select max(updated_at) from evaluacion_entrenadors ee where ee.id_entrenador = evaluacion_entrenadors.id_entrenador)")
            ->selectRaw('round(((empatia+escucha_activa+comunicacion+anatomia+fisiologia+programacion+poblacion+psicologia)/56)*100,2) total')
            ->first();
        $encuestas = EncuestaSatisfaccion::where('id_entrenador', $userId)->get();

        $resumen = [];
        $respuestas_encuestas = EncuestaSatisfaccion::where('id_entrenador', $userId);
        $count = $respuestas_encuestas->count();
        if ($count > 0) {
            $resumen = [
                'entrenador' => $entrenador,
                'profesionalismo' => round($respuestas_encuestas->avg('profesionalismo'), 2),
                'claridad' => round($respuestas_encuestas->avg('claridad'), 2),
                'motivacion' => round($respuestas_encuestas->avg('motivacion'), 2),
                'disponibilidad' => round($respuestas_encuestas->avg('disponibilidad'), 2),
                'puntualidad' => round($respuestas_encuestas->avg('puntualidad'), 2),
                'valoracion_global' => round($respuestas_encuestas->avg('valoracion_global'), 2),
            ];
        }

        return view(
            'entrenadores.portada_opciones',
            compact(
                'entrenador',
                'sesionesSemana',
                'retencion',
                'clientes_activos',
                'categoria',
                'titulo',
                'evaluacion',
                'resumen'
            )
        );
    }

    public function sesiones_semana($slug)
    {
        $entrenador = \App\Models\User::where('slug', $slug)->firstOrFail();
        $userId = $entrenador->id;

        $agendas = Agendas::selectRaw(
            "DATE_FORMAT(MIN(DATE(fecha_inicio)),'%d/%m/%Y') as fecha_inicio_semana, 
    COUNT(*) as total"
        )
            ->where('id_usuario', $userId)
            ->groupByRaw('YEARWEEK(fecha_inicio, 1)')
            ->orderBy('fecha_inicio_semana', 'desc')
            ->get();

        return view('entrenadores.sesiones_semana', compact('entrenador', 'agendas'));
    }

    public function clientes($slug)
    {
        $entrenador = \App\Models\User::where('slug', $slug)->firstOrFail();
        $userId = $entrenador->id;

        //clientes por entrenador
        $añoSeleccionado = 2025;
        $clientesPorEntrenador = $this->obtenerClientesAcumuladosPorAnio($añoSeleccionado, $slug);

        $entrenadores = $clientesPorEntrenador->groupBy('entrenador');

        $mesesClientes = $clientesPorEntrenador->pluck('mes')->unique()->sort()->values();

        $datasetsClientesPorEntrenador  = [];

        foreach ($entrenadores as $nombre => $registros) {
            // Mapear los datos para que coincidan con todos los meses
            $data = [];
            foreach ($mesesClientes as $mes) {
                $registro = $registros->firstWhere('mes', $mes);
                $data[] = $registro ? $registro->clientes_acumulados : 0;
            }

            $datasetsClientesPorEntrenador[] = [
                'label' => $nombre,
                'data' => $data,
                'backgroundColor' => sprintf('rgba(%d, %d, %d)', rand(0, 255), rand(0, 255), rand(0, 255)),
                //'borderColor' => sprintf('rgba(%d, %d, %d, 1)', rand(0, 255), rand(0, 255), rand(0, 255)),
                'borderWidth' => 1
            ];
        }
        $clientesPorEntrenador = $datasetsClientesPorEntrenador;
        return view('entrenadores.clientes', compact('entrenador', 'datasetsClientesPorEntrenador', 'mesesClientes', 'clientesPorEntrenador'));
    }

    function obtenerClientesAcumuladosPorAnio($anio, $slug)
    {
        $todosMeses = collect(range(1, 12))->map(function ($mes) use ($anio) {
            return Carbon::create($anio, $mes, 1)->format('Y-m');
        });

        // Obtenemos todos los entrenadores
        $entrenadores = DB::table('users')
            ->where('id_tipo_usuario', 2) // Solo entrenadores
            ->where('slug', $slug) // Filtrar por slug del entrenador
            ->whereIn('id', function ($query) {
                $query->select('id_usuario')->from('clientes');
            })
            ->pluck('name', 'id');

        $resultados = [];

        foreach ($entrenadores as $id_entrenador => $nombre_entrenador) {
            $acumulado = 0;

            foreach ($todosMeses as $mes) {
                // Fecha de fin de mes
                $finMes = Carbon::createFromFormat('Y-m', $mes)->endOfMonth()->toDateString();

                // Clientes activos hasta fin de mes
                $clientesAcumulados = DB::table('clientes')
                    ->where('id_usuario', $id_entrenador)
                    ->where('fecha_ingreso', '<=', $finMes)
                    ->where(function ($q) use ($finMes) {
                        $q->whereNull('fecha_baja')
                            ->orWhere('fecha_baja', '>=', $finMes);
                    })
                    ->count();

                // Clientes nuevos en el mes
                $clientesNuevos = DB::table('clientes')
                    ->where('id_usuario', $id_entrenador)
                    ->where('estado', 1)
                    ->whereBetween('fecha_ingreso', [
                        Carbon::createFromFormat('Y-m', $mes)->startOfMonth()->toDateString(),
                        $finMes
                    ])
                    ->count();

                $resultados[] = (object) [
                    'entrenador' => $nombre_entrenador,
                    'mes' => $mes,
                    'clientes_acumulados' => $clientesAcumulados,
                    'clientes_nuevos' => $clientesNuevos
                ];
            }
        }

        return collect($resultados);
    }

    public function retencion($slug)
    {
        $entrenador = \App\Models\User::where('slug', $slug)->firstOrFail();
        $userId = $entrenador->id;
        $fechaInicio = '2025-01-01';
        $datos = $this->evolucionRetencionPorMes($fechaInicio, $userId);
        $labels = array_map(fn($d) => $d['mes'], $datos);
        $retencion = array_map(fn($d) => $d['tasa_retencion'], $datos);

        return view('entrenadores.retencion', compact('entrenador', 'datos', 'labels', 'retencion'));
    }

    function evolucionRetencionPorMes($fechaInicio, $userId = null)
    {
        $inicio = Carbon::parse($fechaInicio)->startOfMonth();
        $hoy = Carbon::now()->endOfMonth();

        $resultados = [];
        $clientesIniciales = null;

        while ($inicio <= $hoy) {
            $mes = $inicio->format('Y-m');
            $finMes = $inicio->copy()->endOfMonth();
            DB::enableQueryLog();
            // Clientes que ingresaron antes o durante este mes y estaban activos al inicio del mes
            $query = Clientes::where('fecha_ingreso', '<=', $finMes);

            //if (is_null($clientesIniciales)) {
            // Para el primer mes, todos los que ingresaron hasta esa fecha y no se dieron de baja
            $clientesIniciales = $query->clone()
                ->where(function ($q) use ($inicio) {
                    $q->whereNull('fecha_baja')
                        ->orWhere('fecha_baja', '>', $inicio);
                });
            //}

            // Clientes que siguen activos al final del mes (de los iniciales)
            $clientesActuales = $query->clone()
                ->where(function ($q) use ($finMes) {
                    $q->whereNull('fecha_baja')
                        ->orWhere('fecha_baja', '>', $finMes);
                });

            if ($userId) {
                $clientesIniciales->where('id_usuario', $userId);
                $clientesActuales->where('id_usuario', $userId);
            }

            $iniciales = $clientesIniciales->count();
            $actuales = $clientesActuales->count();


            $tasa = $iniciales > 0 ? ($actuales / $iniciales) * 100 : 0;

            $resultados[] = [
                'mes' => $mes,
                'clientes_iniciales' => $iniciales,
                'clientes_actuales' => $actuales,
                'clientes_baja' => $iniciales - $actuales,
                'tasa_retencion' => round($tasa, 2)
            ];

            $inicio->addMonth();
        }

        return $resultados;
    }


    public function cursos($slug)
    {
        $entrenador = \App\Models\User::where('slug', $slug)->firstOrFail();
        $cursos = \App\Models\EntrenadoresCursos::where('id_entrenador', $entrenador->id)
            ->select(
                'curso',
                'institucion',
                'fecha_inicio',
                'fecha_fin',
                DB::raw("case modalidad when 1 then 'Presencial' when 2 then 'Online' else 'Híbrido' end as modalidad")
            )
            ->get();

        return view('entrenadores.cursos', compact('entrenador', 'cursos'));
    }

    public function resumen_entrenador($slug)
    {
        $entrenador = \App\Models\User::where('slug', $slug)->firstOrFail();
        $userId = $entrenador->id;
        $retencion = $this->evolucionRetencionPorMes('2025-01-01', $userId);
        $clientes_activos = $entrenador->clientesActivos()->count();

        return view('entrenadores.resumen', compact('entrenador', 'retencion', 'clientes_activos'));
    }
}
