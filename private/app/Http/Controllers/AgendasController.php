<?php

namespace App\Http\Controllers;

use App\Models\AgendasEjercicios;
use App\Models\Clientes;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;


class AgendasController extends Controller
{
    public function portada()
    {
        return view('agendas.portada');
    }
    public function index(Request $request)
    {
        $usuario = auth()->user();
        $entrenador_seleccionado = $request->input('id_usuario');

        if ($usuario->id_tipo_usuario == 1 || $usuario->id_clasificacion == 3) {
            $clientesIds = \App\Models\Clientes::where('estado', 1)->pluck('id');
            // Admin: ve todas las agendas
            $agendas = \App\Models\Agendas::with([
                'cliente',
                'ejercicios'
            ]);
            if ($entrenador_seleccionado) {
                $agendas = $agendas->where('id_usuario', $entrenador_seleccionado);
            }
            $agendas = $agendas->whereIn('id_cliente', $clientesIds);
            $agendas = $agendas->get();
        } else {
            // Otros usuarios: solo agendas de sus clientes
            $clientesIds = \App\Models\Clientes::where('id_usuario', $usuario->id)->where('estado', 1)->pluck('id');
            $agendas = \App\Models\Agendas::with([
                'cliente',
                'ejercicios'
            ])->whereIn('id_cliente', $clientesIds)->get();
        }



        $estados = [
            0 => 'Inactivo',
            1 => 'Activo',
            2 => 'Cancelado sin recuperación',
            3 => 'Cancelado con recuperación',
            4 => 'Realizado',
            5 => 'Reprogramado',
        ];
        $eventos = $agendas->map(function ($agenda) use ($estados, $usuario) {
            $detalle_ejercicios = $agenda->ejercicios->map(function ($ejercicio) {
                return  $ejercicio->nombre . ': ' . $ejercicio->pivot->serie . ' serie(s) ' . $ejercicio->pivot->repeticiones . ' Carga ' . $ejercicio->pivot->carga . ' RIR ' . $ejercicio->pivot->rir . ' RPE ' . $ejercicio->pivot->rpe . ' RM ' . $ejercicio->pivot->rm . ' Descanso ' . $ejercicio->pivot->descanso . " \n";
            })->implode('');

            $detalle_ejercicios_iconos = '<table style="width:100%;text-align:left"  cellpadding="0" cellspacing="0">';
            //            $detalle_ejercicios_iconos .= '<thead><td colspan="2">Ejercicio</td><td nowrap>Serie(s)</td><td nowrap>Carga</td><td nowrap>RIR</td><td nowrap>RPE</td><td nowrap>% 1RM</td></thead>';
            if ($usuario->id_clasificacion == 3) {
                $detalle_ejercicios_iconos .= '<thead><td colspan="2" style="border:1px solid #d2d2d2;font-size: small">Ejercicio</td><td nowrap style="border:1px solid #d2d2d2;font-size: small">S</td><td nowrap style="border:1px solid #d2d2d2;font-size: small">R</td><td nowrap style="border:1px solid #d2d2d2;font-size: small">C</td><td nowrap style="border:1px solid #d2d2d2;font-size: small">D</td><td nowrap style="border:1px solid #d2d2d2;font-size: small">Fundamento</td></thead>';
            } else {
                $detalle_ejercicios_iconos .= '<thead><td colspan="2" style="border:1px solid #d2d2d2;font-size: small">Ejercicio</td><td nowrap style="border:1px solid #d2d2d2;font-size: small">Serie(s)</td><td nowrap style="border:1px solid #d2d2d2;font-size: small">Reps</td><td nowrap style="border:1px solid #d2d2d2;font-size: small">Carga</td><td nowrap style="border:1px solid #d2d2d2;font-size: small">Descanso</td></thead>';
            }

            $detalle_ejercicios_iconos .= '<tbody>';

            foreach ($agenda->ejercicios as $ejercicio) {
                $detalle_ejercicios_iconos .= '<tr>';
                $detalle_ejercicios_iconos .= '<td  style="border-left:1px solid #d2d2d2;border-bottom:1px solid #d2d2d2;width:40px"><img src="/iconos/' . $ejercicio->icono . '" width="30"></td>';
                $detalle_ejercicios_iconos .= '<td style="border-bottom:1px solid #d2d2d2;font-size: small">' . $ejercicio->nombre . '</td>';
                $detalle_ejercicios_iconos .= '<td style="border:1px solid #d2d2d2;text-align:right;font-size: small">' . $ejercicio->pivot->serie . '</td>';
                $detalle_ejercicios_iconos .= '<td style="border:1px solid #d2d2d2;text-align:right;font-size: small">' . $ejercicio->pivot->repeticiones . '</td>';
                $detalle_ejercicios_iconos .= '<td style="border:1px solid #d2d2d2;text-align:right;font-size: small">' . $ejercicio->pivot->carga . '</td>';
                $detalle_ejercicios_iconos .= '<td  style="border:1px solid #d2d2d2;text-align:right;font-size: small">' . $ejercicio->pivot->descanso . '</td>';
                if ($usuario->id_clasificacion == 3) {
                    $detalle_ejercicios_iconos .= '<td  style="border:1px solid #d2d2d2;text-align:left;font-size: small">' . $ejercicio->pivot->fundamento . '</td>';
                }
                /* $detalle_ejercicios_iconos .= '<td style="text-align:right">' . $ejercicio->pivot->rir . '</td>';
                $detalle_ejercicios_iconos .= '<td style="text-align:right">' . $ejercicio->pivot->rpe . '</td>';
                $detalle_ejercicios_iconos .= '<td style="text-align:right">' . $ejercicio->pivot->rm . '</td>';*/
                $detalle_ejercicios_iconos .= '</tr>';
            }
            $detalle_ejercicios_iconos .= '</tbody></table>';
            switch ($agenda->estado) {
                case 0:
                    $color = '#f3f4f6'; // Gris claro
                    break;
                case 1:
                    $color = '#10b981'; // Verde
                    break;
                case 2:
                    $color = '#ef4444'; // Rojo
                    break;
                case 3:
                    $color = '#ef4444'; // Rojo
                    break;
                case 4:
                    $color = '#6366f1'; // Azul
                    break;
                case 5:
                    $color = '#f59e0b'; // Amarillo
                    break;
                default:
                    $color = null; // Sin color definido
            }
            return [
                'id' => $agenda->id,
                'slug' => $agenda->slug,
                // 'title' => $agenda->cliente->nombres . '  ' . $agenda->cliente->paterno . "\n" . $detalle_ejercicios,
                // 'titulo' => $agenda->cliente->nombres . '  ' . $agenda->cliente->paterno,
                'title' => ($agenda->cliente ? $agenda->cliente->nombres . ' ' . $agenda->cliente->paterno : 'Sin cliente') . "\n" . $detalle_ejercicios,
                'titulo' => $agenda->cliente ? $agenda->cliente->nombres . ' ' . $agenda->cliente->paterno : 'Sin cliente',
                'start' => $agenda->fecha_inicio,
                'end'   => $agenda->fecha_fin,
                'estado' => $estados[$agenda->estado] ?? 'Desconocido',
                'color' => $color,
                'iconos' =>  $detalle_ejercicios_iconos,
            ];
        });

        $entrenadores = User::where('id_tipo_usuario', 2)->get();
        $metodos = \App\Models\Metodos::where('estado', 1)->get();
        return view('agendas.index', compact('eventos', 'entrenadores', 'entrenador_seleccionado', 'metodos'));
    }

    public function create(Request $request)
    {
        $agenda = null;
        if ($request->has('reagendar')) {
            $agenda = \App\Models\Agendas::with(['cliente', 'ejercicios'])->find($request->reagendar);
        }
        $usuario = auth()->user();

        if ($usuario->id_tipo_usuario == 1) {
            $clientes = \App\Models\Clientes::orderBy('nombres', 'ASC')->with('plan')->get();
            $usuarios = \App\Models\User::where('id_tipo_usuario', 2)->get(); // Obtener solo usuarios tipo 2 (entrenadores)
        } else {
            $clientes = \App\Models\Clientes::where('id_usuario', $usuario->id)->orderBy('nombres', 'ASC')->get();
            $usuarios = \App\Models\User::where('id', $usuario->id)->get();
        }
        $ejercicios = \App\Models\Ejercicios::orderBy('nombre', 'asc')->where('estado', 'b')->get(); // Obtener todos los ejercicios ordenados por nombre
        $tipos = \App\Models\TipoEjercicio::orderBy('nombre')->get();
        $metodos = \App\Models\Metodos::where('estado', 1)->get();

        $clientes_duos = Clientes::join('planes', 'clientes.id_plan', 'planes.id')
            ->where('planes.tipo', 2)
            ->orderBy('clientes.nombres', 'asc')
            ->select('clientes.id', 'clientes.nombres', 'clientes.paterno', 'clientes.materno')
            ->get();

        return view('agendas.create', compact('clientes', 'usuarios', 'ejercicios', 'agenda', 'metodos', 'clientes_duos', 'tipos'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'fecha_inicio'   => 'required|date',
            'fecha_fin'      => 'required|date|after_or_equal:fecha_inicio',
            'id_cliente'     => 'required|exists:clientes,id',
            'id_usuario'     => 'required|exists:users,id',
            //'titulo'         => 'required|string|max:255',
            //'descripcion'    => 'nullable|string',
            'ejercicios'     => 'required|array',
            'series'         => 'required|array',
            'repeticiones'   => 'required|array',
            'carga'         => 'required|array',
            'rir'   => 'nullable|array',
            'rpe'   => 'nullable|array',
            'rm'    => 'nullable|array',
            'metodo'    => 'nullable|array',
            'progresion'    => 'nullable|array',
            'fundamento'    => 'nullable|array',
            'descanso'    => 'required|array',
        ]);

        //$slug = $validatedData['slug'] = hash('sha256', $validated['id_cliente'] . $validated['fecha_inicio']. $fechaHoy);

        $recurrencia = $request->input('recurrencia', 'ninguna');


        $fecha_inicio = Carbon::parse($request->fecha_inicio, config('app.timezone'));
        $fecha_fin = Carbon::parse($request->fecha_fin, config('app.timezone'));

        $agendas = [];
        $diaSemana = $fecha_inicio->dayOfWeek; // 0=domingo, 1=lunes, ..., 6=sábado

        $dias_semana = $request->input('dias_semana', []);
        $dia_inicio = $fecha_inicio->dayOfWeek;
        if (!in_array($dia_inicio, $dias_semana)) {
            $dias_semana[] = $dia_inicio;
        }

        switch ($recurrencia) {
            case 'mensual':
                // Solo el mes de la fecha de inicio
                $inicioMes = $fecha_inicio->copy()->startOfMonth();
                $finMes = $fecha_inicio->copy()->endOfMonth();
                $fecha = $fecha_inicio->copy();
                // Buscar todos los días del mismo día de la semana en el mes, desde la fecha de inicio
                while ($fecha->month == $inicioMes->month && $fecha->lte($finMes)) {
                    //if ($fecha->gte($fecha_inicio) && $fecha->dayOfWeek == $diaSemana) {
                    if ($fecha->gte($fecha_inicio) && in_array($fecha->dayOfWeek, $dias_semana)) {
                        $agendas[] = [
                            'fecha_inicio' => $fecha->copy()->setTimeFrom($fecha_inicio)->setTimezone(config('app.timezone')),
                            'fecha_fin'    => $fecha->copy()->setTimeFrom($fecha_fin)->setTimezone(config('app.timezone')),

                        ];
                    }
                    $fecha->addDay();
                }
                break;

            case 'trimestral':
                // Solo el trimestre de la fecha de inicio
                $mesInicioTrimestre = intval(($fecha_inicio->month - 1) / 3) * 3 + 1;
                $inicioTrimestre = $fecha_inicio->copy()->month($mesInicioTrimestre)->startOfMonth();
                $finTrimestre = $inicioTrimestre->copy()->addMonths(3)->subDay();
                $fecha = $fecha_inicio->copy();
                while ($fecha->lte($finTrimestre)) {
                    //if ($fecha->gte($fecha_inicio) && $fecha->dayOfWeek == $diaSemana) {
                    if ($fecha->gte($fecha_inicio) && in_array($fecha->dayOfWeek, $dias_semana)) {
                        $agendas[] = [
                            'fecha_inicio' => $fecha->copy()->setTimeFrom($fecha_inicio)->setTimezone(config('app.timezone')),
                            'fecha_fin'    => $fecha->copy()->setTimeFrom($fecha_fin)->setTimezone(config('app.timezone')),
                        ];
                    }
                    $fecha->addDay();
                }
                break;

            case 'semestral':
                // Solo el semestre de la fecha de inicio
                $mesInicioSemestre = $fecha_inicio->month <= 6 ? 1 : 7;
                $inicioSemestre = $fecha_inicio->copy()->month($mesInicioSemestre)->startOfMonth();
                $finSemestre = $inicioSemestre->copy()->addMonths(6)->subDay();
                $fecha = $fecha_inicio->copy();
                while ($fecha->lte($finSemestre)) {
                    if ($fecha->gte($fecha_inicio) && in_array($fecha->dayOfWeek, $dias_semana)) {
                        $agendas[] = [
                            'fecha_inicio' => $fecha->copy()->setTimeFrom($fecha_inicio)->setTimezone(config('app.timezone')),
                            'fecha_fin'    => $fecha->copy()->setTimeFrom($fecha_fin)->setTimezone(config('app.timezone')),
                        ];
                    }
                    $fecha->addDay();
                }
                break;

            case 'anual':
                // Solo el año de la fecha de inicio
                $inicioAnio = $fecha_inicio->copy()->startOfYear();
                $finAnio = $inicioAnio->copy()->endOfYear();
                $fecha = $fecha_inicio->copy();
                while ($fecha->lte($finAnio)) {
                    if ($fecha->gte($fecha_inicio) && in_array($fecha->dayOfWeek, $dias_semana)) {
                        $agendas[] = [
                            'fecha_inicio' => $fecha->copy()->setTimeFrom($fecha_inicio)->setTimezone(config('app.timezone')),
                            'fecha_fin'    => $fecha->copy()->setTimeFrom($fecha_fin)->setTimezone(config('app.timezone')),
                        ];
                    }
                    $fecha->addDay();
                }
                break;

            default:
                $fecha = $fecha_inicio->copy();
                $agendas[] = [
                    'fecha_inicio' => $fecha->copy()->setTimeFrom($fecha_inicio)->setTimezone(config('app.timezone')),
                    'fecha_fin'    => $fecha->copy()->setTimeFrom($fecha_fin)->setTimezone(config('app.timezone')),
                ];
                break;
        }


        $clientes_ids = [$request->id_cliente];
        if ($request->filled('id_cliente_duo')) {
            $clientes_ids[] = $request->id_cliente_duo;
        }
        //dd($agendas);

        $primerAgenda = null;
        foreach ($clientes_ids as $cliente_id) {
            foreach ($agendas as $index => $agendaData) {
                $fechaHoy = date('YmdHis');
                $nuevaAgenda = \App\Models\Agendas::create([
                    'fecha_inicio' => $agendaData['fecha_inicio'],
                    'fecha_fin'    => $agendaData['fecha_fin'],
                    'id_cliente'   => $cliente_id,
                    'id_usuario'   => $request->id_usuario,
                    'slug'         => hash('sha256', $cliente_id . $fechaHoy . random_int(1, 10000)),
                ]);

                $syncData = [];
                foreach ($request->ejercicios as $i => $ejercicio_id) {
                    if ($ejercicio_id) {
                        $syncData[$ejercicio_id] = [
                            'serie' => $request->series[$i],
                            'repeticiones' => $request->repeticiones[$i],
                            'rir' => $request->rir[$i],
                            'rpe' => $request->rpe[$i],
                            'rm' => $request->rm[$i] ?? null,
                            'metodo' => $request->metodo[$i] ?? null,
                            'progresion' => $request->progresion[$i] ?? null,
                            'fundamento' => $request->fundamento[$i] ?? null,
                            'carga' => $request->carga[$i] ?? null,
                            'descanso' => $request->descanso[$i] ?? null,
                        ];
                    }
                }
                $nuevaAgenda->ejercicios()->attach($syncData);
                $primerAgenda = $nuevaAgenda;
            }
        }

        return redirect()->route('agendas.index')->with('success', 'Agenda creada correctamente');
    }
    public function edit($slug)
    {
        $usuarios = \App\Models\User::get(); // Obtener usuarios activos
        $clientes = \App\Models\Clientes::where('estado', 1)->get(); // Obtener clientes activos
        $ejercicios = \App\Models\Ejercicios::all(); // Obtener todos los ejercicios
        $tipos = \App\Models\TipoEjercicio::orderBy('nombre')->get();
        $agenda = \App\Models\Agendas::where('slug', $slug)->firstOrFail();
        $metodos = \App\Models\Metodos::where('estado', 1)->get();

        return view('agendas.edit', compact('agenda', 'usuarios', 'clientes', 'ejercicios', 'metodos', 'tipos'));
    }
    /*public function update(Request $request, $slug)
    {
        try {
            //dd($request->all());
            DB::beginTransaction();
            DB::enableQueryLog();
            $validated = $request->validate([
                'fecha_inicio'   => 'required|date',
                'fecha_fin'      => 'required|date|after_or_equal:fecha_inicio',
                'id_cliente'     => 'required|exists:clientes,id',
                'id_usuario'     => 'required|exists:users,id',
                'ejercicios'     => 'required|array',
                'series'         => 'required|array',
                'repeticiones'   => 'required|array',
                'rir'   => 'required|array',
                'rpe'   => 'required|array',
                'rm'    => 'required|array',
                'metodo'    => 'required|array',
                'progresion'    => 'required|array',
                'carga'         => 'required|array',
                'fundamento'    => 'required|array',
                'descanso'    => 'required|array',
            ]);

            $agenda = \App\Models\Agendas::where('slug', $slug)->firstOrFail();

            // // Datos para comparar futuros entrenamientos
            // $modificarFuturos = $request->has('modificar_futuros');
            // $diaSemana = Carbon::parse($request->fecha_inicio)->dayOfWeek;
            // $horaInicio = Carbon::parse($request->fecha_inicio)->format('H:i');
            // $id_cliente = $request->id_cliente;

            $agenda->update([
                'fecha_inicio' => $request->fecha_inicio,
                'fecha_fin'    => $request->fecha_fin,
                'id_cliente'   => $request->id_cliente,
                'id_usuario'   => $request->id_usuario,
                'titulo'       => $request->titulo,
                'descripcion'  => $request->descripcion,
            ]);
            //dd(DB::getQueryLog());
            // Actualizar ejercicios asociados
            $syncData = [];
            foreach ($request->ejercicios as $i => $ejercicio_id) {
                if ($ejercicio_id) {
                    $syncData[$ejercicio_id] = [
                        'serie' => $request->series[$i],
                        'repeticiones' => $request->repeticiones[$i],
                        'rir' => $request->rir[$i],
                        'rpe' => $request->rpe[$i],
                        'rm'  => $request->rm[$i] ?? null,
                        'metodo' => $request->metodo[$i] ?? null,
                        'progresion' => $request->progresion[$i] ?? null,
                        'fundamento' => $request->fundamento[$i] ?? null,
                        'carga' => $request->carga[$i] ?? null,
                        'descanso' => $request->descanso[$i] ?? null,
                    ];
                }
            }
            $agenda->ejercicios()->sync($syncData);

            DB::commit();
            return redirect()->route('agendas.index')->with('success', 'Agenda actualizada correctamente');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->withErrors(['error' => 'Error al actualizar la agenda: ' . $e->getMessage()]);
        }
    }
        */
    public function update(Request $request, $slug)
    {
        try {
            DB::beginTransaction();
            $validated = $request->validate([
                'fecha_inicio'   => 'required|date',
                'fecha_fin'      => 'required|date|after_or_equal:fecha_inicio',
                'id_cliente'     => 'required|exists:clientes,id',
                'id_usuario'     => 'required|exists:users,id',
                'ejercicios'     => 'required|array',
                'series'         => 'required|array',
                'repeticiones'   => 'required|array',
                'rir'   => 'nullable|array',
                'rpe'   => 'nullable|array',
                'rm'    => 'nullable|array',
                'metodo'    => 'nullable|array',
                'progresion'    => 'nullable|array',
                'carga'         => 'required|array',
                'fundamento'    => 'nullable|array',
                'descanso'    => 'required|array',
            ]);

            $agenda = \App\Models\Agendas::where('slug', $slug)->firstOrFail();

            if ((int) $agenda->estado === 4) {
                DB::rollBack();
                return redirect()->back()->withInput()->withErrors([
                    'error' => 'No se puede modificar un entrenamiento realizado.',
                ]);
            }

            // Datos para comparar futuros entrenamientos
            $modificarFuturos = $request->has('modificar_futuros');
            $diaSemana = Carbon::parse($request->fecha_inicio)->dayOfWeek;
            $horaInicio = Carbon::parse($request->fecha_inicio)->format('H:i');
            $id_cliente = $request->id_cliente;

            // Datos de ejercicios
            $syncData = [];
            foreach ($request->ejercicios as $i => $ejercicio_id) {
                if ($ejercicio_id) {
                    $syncData[$ejercicio_id] = [
                        'serie' => $request->series[$i],
                        'repeticiones' => $request->repeticiones[$i],
                        'rir' => $request->rir[$i],
                        'rpe' => $request->rpe[$i],
                        'rm'  => $request->rm[$i] ?? null,
                        'metodo' => $request->metodo[$i] ?? null,
                        'progresion' => $request->progresion[$i] ?? null,
                        'fundamento' => $request->fundamento[$i] ?? null,
                        'carga' => $request->carga[$i] ?? null,
                        'descanso' => $request->descanso[$i] ?? null,
                    ];
                }
            }

            if ($modificarFuturos) {
                // Busca todas las agendas futuras del mismo cliente, día de la semana y hora
                $agendasFuturas = \App\Models\Agendas::where('id_cliente', $id_cliente)
                    ->whereDate('fecha_inicio', '>', Carbon::parse($request->fecha_inicio)->format('Y-m-d'))
                    ->whereRaw('DAYOFWEEK(fecha_inicio) = ?', [$diaSemana + 1]) // MySQL: 1=domingo, 2=lunes...
                    ->whereRaw('TIME(fecha_inicio) = ?', [$horaInicio])
                    ->where('estado', '!=', 4)
                    ->get();

                foreach ($agendasFuturas as $agendaFutura) {
                    $agendaFutura->update([
                        'id_usuario'   => $request->id_usuario,
                        'titulo'       => $request->titulo,
                        'descripcion'  => $request->descripcion,
                    ]);
                    $agendaFutura->ejercicios()->sync($syncData);
                }
            }

            // Actualiza solo la agenda seleccionada
            $agenda->update([
                'fecha_inicio' => $request->fecha_inicio,
                'fecha_fin'    => $request->fecha_fin,
                'id_cliente'   => $request->id_cliente,
                'id_usuario'   => $request->id_usuario,
                'titulo'       => $request->titulo,
                'descripcion'  => $request->descripcion,
            ]);
            $agenda->ejercicios()->sync($syncData);

            DB::commit();
            return redirect()->route('agendas.index')->with('success', 'Agenda actualizada correctamente');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->withErrors(['error' => 'Error al actualizar la agenda: ' . $e->getMessage()]);
        }
    }
    public function destroy($slug)
    {
        $agenda = \App\Models\Agendas::where('slug', $slug)->firstOrFail();
        $ejercicios = AgendasEjercicios::where('id_agenda', $agenda->id)->get();
        //dd($ejercicios);
        foreach ($ejercicios as $ejercicio) {
            $ejercicio->delete();
        }
        $agenda->delete();

        return redirect()->route('agendas.index')->with('success', 'Agenda eliminada exitosamente.');
    }
    public function toggleStatus($slug)
    {
        $agenda = \App\Models\Agendas::where('slug', $slug)->firstOrFail();
        $agenda->estado = !$agenda->estado; // Cambiar el estado (1 a 0 o 0 a 1)
        $agenda->save();

        return redirect()->route('agendas.index')->with('success', 'Estado de la agenda actualizado exitosamente.');
    }

    public function show($id)
    {
        $agenda = \App\Models\Agendas::with(['cliente', 'entrenador', 'ejercicios'])->findOrFail($id);
        return view('agendas.show', compact('agenda'));
    }

    public function cambiarEstado(Request $request, $id)
    {
        $agenda = \App\Models\Agendas::findOrFail($id);

        if ((int) $agenda->estado === 4) {
            return response()->json([
                'success' => false,
                'message' => 'No se puede cambiar el estado de un entrenamiento realizado.',
            ], 422);
        }

        $agenda->estado = $request->estado;
        $agenda->save();

        return response()->json(['success' => true]);
    }

    public function copiar(Request $request, $slug)
    {
        $agenda = \App\Models\Agendas::where('slug', $slug)->firstOrFail();

        // Nueva fecha desde el request
        $nueva_fecha_inicio = $request->input('nueva_fecha_inicio');
        $nueva_fecha_fin = $request->input('nueva_fecha_fin');

        // Clona la agenda
        $nuevaAgenda = $agenda->replicate();
        $nuevaAgenda->fecha_inicio = $nueva_fecha_inicio;
        $nuevaAgenda->fecha_fin = $nueva_fecha_fin;
        $nuevaAgenda->slug = hash('sha256', $agenda->id_cliente . now()->format('YmdHis') . random_int(1, 10000));
        $nuevaAgenda->save();

        foreach ($agenda->ejercicios as $ejercicio) {
            $pivot = $ejercicio->pivot->toArray();
            unset($pivot['id'], $pivot['created_at'], $pivot['updated_at']);
            $nuevaAgenda->ejercicios()->attach($ejercicio->id, [
                'serie' => $pivot['serie'],
                'repeticiones' => $pivot['repeticiones'],
                'rir' => $pivot['rir'],
                'rpe' => $pivot['rpe'],
                'rm' => $pivot['rm'] ?? null,
                'metodo' => $pivot['metodo'] ?? null,
                'progresion' => $pivot['progresion'] ?? null,
                'fundamento' => $pivot['fundamento'] ?? null,
                'carga' => $pivot['carga'] ?? null,
                'descanso' => $pivot['descanso'] ?? null,
            ]);
        }

        return response()->json(['success' => true]);
    }

    public function agendaClientePorMes($slug, Request $request)
    {
        $cliente = \App\Models\Clientes::where('slug', $slug)->firstOrFail();

        // Obtener año del request o usar el año actual
        $ano = $request->input('ano', Carbon::now()->year);

        // Obtener todos los años disponibles para esta agendas del cliente
        $anosDisponibles = \App\Models\Agendas::where('id_cliente', $cliente->id)
            ->selectRaw('YEAR(fecha_inicio) as ano')
            ->distinct()
            ->orderBy('ano', 'desc')
            ->pluck('ano')
            ->toArray();

        // Si no hay años disponibles, agregar el año actual
        if (empty($anosDisponibles)) {
            $anosDisponibles = [Carbon::now()->year];
        }

        // Estados de agenda
        $estados = [
            1 => 'Agendado',
            2 => 'Cancelado sin recuperación',
            3 => 'Cancelado con recuperación',
            4 => 'Realizado',
            5 => 'Reagendado',
        ];

        // Meses en español
        $mesesEnEspanol = [
            1 => 'Enero',
            2 => 'Febrero',
            3 => 'Marzo',
            4 => 'Abril',
            5 => 'Mayo',
            6 => 'Junio',
            7 => 'Julio',
            8 => 'Agosto',
            9 => 'Septiembre',
            10 => 'Octubre',
            11 => 'Noviembre',
            12 => 'Diciembre',
        ];

        // Construir la tabla de agendas por mes y estado
        $tablaAgendas = [];

        for ($mes = 1; $mes <= 12; $mes++) {
            $inicioMes = Carbon::createFromDate($ano, $mes, 1);
            $finMes = $inicioMes->clone()->endOfMonth();

            $fila = [
                'mes' => $mesesEnEspanol[$mes] . ' ' . $ano,
                'mes_num' => $mes,
            ];

            // Contar agendas por estado para este mes
            foreach ($estados as $id_estado => $nombre_estado) {
                $cantidad = \App\Models\Agendas::where('id_cliente', $cliente->id)
                    ->where('estado', $id_estado)
                    ->whereDate('fecha_inicio', '>=', $inicioMes)
                    ->whereDate('fecha_inicio', '<=', $finMes)
                    ->count();

                $fila[$nombre_estado] = $cantidad;
            }

            // Calcular total
            $total = array_sum(array_filter($fila, function ($key) {
                return !in_array($key, ['mes', 'mes_num']);
            }, ARRAY_FILTER_USE_KEY));

            $fila['total'] = $total;
            $tablaAgendas[] = $fila;
        }

        // Filtrar meses sin datos (opcional, comentar si quieres mostrar todos)
        // $tablaAgendas = array_filter($tablaAgendas, function ($fila) {
        //     return $fila['total'] > 0;
        // });

        return view('agendas.agenda_cliente_por_mes', compact(
            'cliente',
            'tablaAgendas',
            'estados',
            'ano',
            'anosDisponibles'
        ));
    }
}
