<?php

namespace App\Http\Controllers;

use App\Mail\BienvenidaClienteMail;
use App\Mail\RecordatorioCuotasVencidas;
use App\Models\Agendas;
use App\Models\Clientes;
use App\Models\Generos;
use App\Models\Gimnasios;
use App\Models\IMCS;
use App\Models\Planes;
use App\Models\User;
use App\Rules\RutChileno;
use App\Services\Clientes\ClienteLifecycleService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf; // Si usas barryvdh/laravel-dompdf
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use \App\Models\Perimetro;




class ClientesController extends Controller
{
    public function portada()
    {

        return view('clientes.portada');
    }

    public function portada_opciones($slug)
    {
        // $cliente = \App\Models\Clientes::where('slug', $slug)->firstOrFail();
        // $cuotas = $cliente->cuotas()->orderBy('fecha_vencimiento', 'asc')->get();

        // $fechaActual = Carbon::now()->toDateString();

        // $alDia = !$cuotas->contains(function ($cuota) use ($fechaActual) {
        //     return $cuota->id_estado_pago != 2 && $cuota->fecha_vencimiento <= $fechaActual;
        // });
        // $estado = $alDia ? '<div class="text-green-700 mt-4">Al día</div>' : '<div class="text-red-700 mt-4">Atrasado</div>';

        $cliente = \App\Models\Clientes::with('plan')->where('slug', $slug)->firstOrFail();
        $cuotas = $cliente->cuotas()->orderBy('fecha_vencimiento', 'asc')->get();

        $fechaActual = Carbon::now()->toDateString();

        // Verificar si el cliente tiene cuotas
        if ($cuotas->isEmpty()) {
            // Si no tiene cuotas, verificamos la fecha_fin
            $alDia = $cliente->fecha_fin >= $fechaActual;
        } else {
            // Si tiene cuotas, verificamos si alguna cuota está atrasada
            $alDia = !$cuotas->contains(function ($cuota) use ($fechaActual) {
                return $cuota->id_estado_pago != 2 && $cuota->fecha_vencimiento <= $fechaActual;
            });
        }

        $estado = $alDia ? '<div class="text-green-700 mt-4">Al día</div>' : '<div class="text-red-700 mt-4">Atrasado</div>';

        $pesoReciente = $cliente->pesos()
            ->orderByDesc('updated_at')
            ->orderByDesc('created_at')
            ->first();
        if (!$pesoReciente) {
            $pesoReciente = new \App\Models\Pesos();
            $pesoReciente->peso = 0; // Valor por defecto si no hay peso
            $pesoReciente->created_at = Carbon::now();
            $pesoReciente->updated_at = Carbon::now();
        }

        $imcReciente = $cliente->imcs()
            ->orderByDesc('updated_at')
            ->orderByDesc('created_at')
            ->first();
        if (!$imcReciente) {
            $imcReciente = new \App\Models\IMCS();
            $imcReciente->imc = 0; // Valor por defecto si no hay IMC
            $imcReciente->created_at = Carbon::now();
            $imcReciente->updated_at = Carbon::now();
        }

        $tieneParq = \App\Models\ParqRespuestas::where('id_cliente', $cliente->id)->count();
        $mensajeParQ = $tieneParq ? '<div class="text-green-700 mt-4">Realizado</div>' : '<div class="text-red-700 mt-4">Pendiente</div>';

        $tieneFitPlan = \App\Models\Cuestionarios::where('id_cliente', $cliente->id)->count();
        $mensajeFitPlan = $tieneFitPlan ? '<div class="text-green-700 mt-4">Realizado</div>' : '<div class="text-red-700 mt-4">Pendiente</div>';

        $tieneEvaluacionInicial = \App\Models\EvaluacionInicial::where('id_cliente', $cliente->id)->exists();
        $mensajeEvaluacionInicial = $tieneEvaluacionInicial
            ? '<div class="text-green-700 mt-4">Completa</div>'
            : '<div class="text-red-700 mt-4">Pendiente</div>';

        $aguas = $cliente->aguas()->orderBy('created_at', 'desc')->get();
        $aguaReciente = $cliente->aguas()
            ->orderByDesc('updated_at')
            ->orderByDesc('created_at')
            ->first();
        if (!$aguaReciente) {
            $aguaReciente = new \App\Models\Aguas();
            $aguaReciente->valor = 0; // Valor por defecto si no hay
            $aguaReciente->created_at = Carbon::now();
            $aguaReciente->updated_at = Carbon::now();
        }

        $grasas = $cliente->grasas()->orderBy('created_at', 'desc')->get();
        $grasaReciente = $cliente->grasas()
            ->orderByDesc('updated_at')
            ->orderByDesc('created_at')
            ->first();
        if (!$grasaReciente) {
            $grasaReciente = new \App\Models\Grasas();
            $grasaReciente->valor = 0; // Valor por defecto si no hay
            $grasaReciente->created_at = Carbon::now();
            $grasaReciente->updated_at = Carbon::now();
        }

        $pmusculares = $cliente->pmusculares()->orderBy('created_at', 'desc')->get();
        $pmuscularReciente = $cliente->pmusculares()
            ->orderByDesc('updated_at')
            ->orderByDesc('created_at')
            ->first();
        if (!$pmuscularReciente) {
            $pmuscularReciente = new \App\Models\PMusculares();
            $pmuscularReciente->valor = 0; // Valor por defecto si no hay
            $pmuscularReciente->created_at = Carbon::now();
            $pmuscularReciente->updated_at = Carbon::now();
        }

        $poseas = $cliente->poseas()->orderBy('created_at', 'desc')->get();
        $poseaReciente = $cliente->poseas()
            ->orderByDesc('updated_at')
            ->orderByDesc('created_at')
            ->first();
        if (!$poseaReciente) {
            $poseaReciente = new \App\Models\Poseas();
            $poseaReciente->valor = 0; // Valor por defecto si no hay
            $poseaReciente->created_at = Carbon::now();
            $poseaReciente->updated_at = Carbon::now();
        }

        $estados_entrenamiento = [
            ['id' => 1, 'nombre' => 'Agendado', 'color' => '#10b981', 'texto' => '#ffffff'],
            ['id' => 2, 'nombre' => 'Cancelado sin recuperación', 'color' => '#ef4444', 'texto' => '#333333'],
            ['id' => 3, 'nombre' => 'Cancelado con recuperación', 'color' => '#ef4444', 'texto' => '#333333'],
            ['id' => 4, 'nombre' => 'Realizado', 'color' => '#6366f1', 'texto' => '#ffffff'],
            ['id' => 5, 'nombre' => 'Reagendado', 'color' => '#f59e0b', 'texto' => '#ffffff'],
        ];

        // Primero creamos una consulta base vacía
        $baseQuery = DB::query()->from(DB::raw("(SELECT null as id_estado, '' as estado, '' as color, '' as texto, 0 as total) as base"));

        // Agregamos cada estado mediante UNION ALL
        foreach ($estados_entrenamiento as $estados) {
            $baseQuery->unionAll(
                DB::query()->select(
                    DB::raw("" . $estados['id'] . " as id_estado"),
                    DB::raw("'" . $estados['nombre'] . "' as estado"),
                    DB::raw("'" . $estados['color'] . "' as color"),
                    DB::raw("'" . $estados['texto'] . "' as texto"),
                    DB::raw("0 as total")
                )
            );
        }

        $entrenamientos_estado = DB::table(DB::raw("({$baseQuery->toSql()}) as estados_temporales"))
            ->mergeBindings($baseQuery)
            ->leftJoinSub(
                Agendas::where('id_cliente', $cliente->id)
                    ->select(
                        DB::raw('count(1) as total'),
                        DB::raw('estado as id_estado')
                    )
                    ->groupBy('estado'),
                'agendas',
                function ($join) {
                    $join->on('estados_temporales.id_estado', '=', 'agendas.id_estado');
                }
            )
            ->select(
                'estados_temporales.id_estado',
                'estados_temporales.estado',
                'estados_temporales.color',
                'estados_temporales.texto',
                DB::raw('COALESCE(agendas.total, 0) as total')
            )
            ->orderBy('estados_temporales.id_estado')
            ->get();



        $entrenador = User::where('id', $cliente->id_usuario)->first();
        //$entrenador = $cliente->user; // Obtener el entrenador asociado al cliente
        $slug = $entrenador ? $entrenador->slug : null;

        return view(
            'clientes.portada_opciones',
            compact(
                'cliente',
                'cuotas',
                'estado',
                'pesoReciente',
                'imcReciente',
                'mensajeParQ',
                'tieneParq',
                'mensajeFitPlan',
                'tieneFitPlan',
                'mensajeEvaluacionInicial',
                'tieneEvaluacionInicial',
                'aguas',
                'aguaReciente',
                'grasas',
                'grasaReciente',
                'pmusculares',
                'pmuscularReciente',
                'poseas',
                'poseaReciente',
                'entrenamientos_estado',
                'slug'
            )
        );
    }

    public function index(Request $request)
    {
        $usuario = Auth::user();
        $esSuperAdmin = (int) $usuario->id_tipo_usuario === 10;
        $gimnasios = Gimnasios::where('estado', 1)->orderBy('nombre')->get();
        $idGimnasio = $esSuperAdmin
            ? ($request->filled('id_gimnasio') ? (int) $request->input('id_gimnasio') : null)
            : Gimnasios::gimnasioActualId();
        $gimnasioSeleccionado = $idGimnasio;

        if (!in_array((int) $usuario->id_tipo_usuario, [1, 2, 10], true)) {
            abort(403, 'No tienes permiso para acceder a esta sección.');
        }

        $query = \App\Models\Clientes::with(['plan', 'user', 'cuotas', 'entrenador', 'gimnasio'])
            ->leftJoin('users as u', 'clientes.id', '=', 'u.id_cliente')
            ->leftJoin('tipos_usuarios as tu', 'u.id_tipo_usuario', '=', 'tu.id')
            ->select('clientes.*', 'tu.nombre as tipo_usuario')
            ->when($idGimnasio, function ($subQuery) use ($idGimnasio) {
                $subQuery->where('clientes.id_gimnasio', $idGimnasio);
            });

        if ((int) $usuario->id_tipo_usuario === 2) {
            $query->where('clientes.id_usuario', $usuario->id);
        }

        $clientes = $query->orderBy('clientes.nombres')->get();

        $fechaActual = Carbon::now()->toDateString();

        $clientesMorosos = $clientes->filter(function ($cliente) use ($fechaActual) {
            if ((int) $cliente->getRawOriginal('estado') !== 1) {
                return false;
            }

            return $cliente->cuotas->contains(function ($cuota) use ($fechaActual) {
                return $cuota->monto_pagado < $cuota->monto_pagar &&
                    $cuota->fecha_vencimiento <= $fechaActual;
            });
        });

        return view('clientes.index', compact('clientes', 'clientesMorosos', 'gimnasios', 'gimnasioSeleccionado'));
    }

    public function morosos()
    {
        $usuario = Auth::user();
        $idGimnasio = Gimnasios::gimnasioActualId();

        if (in_array((int) $usuario->id_tipo_usuario, [1, 10], true)) {
            // Admin: ve todos los clientes
            $clientes = \App\Models\Clientes::with(['plan', 'user', 'cuotas'])
                ->when($idGimnasio, function ($query) use ($idGimnasio) {
                    $query->where('id_gimnasio', $idGimnasio);
                })
                ->where('estado', 1)->get();
        } else {
            // Otros usuarios: solo sus clientes
            $clientes = \App\Models\Clientes::with(['plan', 'user', 'cuotas'])
                ->where('id_usuario', $usuario->id)
                ->when($idGimnasio, function ($query) use ($idGimnasio) {
                    $query->where('id_gimnasio', $idGimnasio);
                })
                ->where('estado', 1)
                ->get();
        }
        $fechaActual = Carbon::now()->toDateString();

        // $clientesMorosos = $clientes->filter(function ($cliente) use ($fechaActual) {
        //     return $cliente->cuotas->contains(function ($cuota) use ($fechaActual) {
        //         // Considera cuota morosa si el monto pagado es menor al monto a pagar y la fecha de vencimiento es menor o igual a la fecha actual
        //         return $cuota->monto_pagado < $cuota->monto_pagar &&
        //             $cuota->fecha_vencimiento <= $fechaActual;
        //     });
        // });
        $clientesMorosos = $clientes->filter(function ($cliente) use ($fechaActual) {
            // Si el cliente no tiene cuotas, se considera moroso si su fecha_fin es posterior a la fecha actual
            if ($cliente->cuotas->isEmpty()) {
                return $cliente->fecha_fin >= $fechaActual; // Considera moroso si fecha_fin es posterior
            }

            // Si tiene cuotas, se verifica si alguna cuota está morosa
            return $cliente->cuotas->contains(function ($cuota) use ($fechaActual) {
                return $cuota->monto_pagado < $cuota->monto_pagar &&
                    $cuota->fecha_vencimiento <= $fechaActual;
            });
        });

        return view('clientes.morosos', compact('clientes', 'clientesMorosos'));
    }

    public function create()
    {
        $usuario = Auth::user();
        $esSuperAdmin = (int) $usuario->id_tipo_usuario === 10;
        $gimnasios = Gimnasios::where('estado', 1)->orderBy('nombre')->get();
        $idGimnasio = $esSuperAdmin
            ? (request()->filled('id_gimnasio') ? (int) request()->input('id_gimnasio') : null)
            : Gimnasios::gimnasioActualId();

        $generos = Generos::where('estado', 1)->get();
        $planes = Planes::where('estado', 1)
            ->when(! $esSuperAdmin && $idGimnasio, function ($query) use ($idGimnasio) {
                $query->where('id_gimnasio', $idGimnasio);
            })
            ->orderBy('nombre', 'asc')
            ->get();
        $tipos_usuarios = \App\Models\TiposUsuarios::where('estado', 1)
            ->whereIn('id', [4, 5])
            ->orderByRaw('CASE WHEN id = 5 THEN 1 ELSE 0 END')
            ->orderBy('id')
            ->get();
        $motivos = \App\Models\Motivos::where('estado', 1)->where('tipo', 1)->orderBy('nombre', 'asc')->get();

        if (in_array((int) $usuario->id_tipo_usuario, [1, 10], true)) {
            $usuarios = User::where('id_tipo_usuario', 2)
                ->when(! $esSuperAdmin && $idGimnasio, function ($query) use ($idGimnasio) {
                    $query->where('id_gimnasio', $idGimnasio);
                })
                ->orderBy('name')
                ->get();
        } else {
            $usuarios = User::where('id_tipo_usuario', 2)
                ->when($idGimnasio, function ($query) use ($idGimnasio) {
                    $query->where('id_gimnasio', $idGimnasio);
                })
                ->where('id', $usuario->id)
                ->get();
        }

        return view('clientes.create', compact('generos', 'planes', 'usuarios', 'tipos_usuarios', 'motivos', 'gimnasios', 'idGimnasio'));
    }

    public function store(Request $request, ClienteLifecycleService $clienteLifecycleService)
    {
        $usuarioActual = Auth::user();
        if (!in_array((int) $usuarioActual->id_tipo_usuario, [1, 2, 10], true)) {
            abort(403, 'No tienes permiso para crear clientes.');
        }

        try {
            DB::beginTransaction();
            $esSuperAdmin = (int) $usuarioActual->id_tipo_usuario === 10;
            $esEntrenador = (int) $usuarioActual->id_tipo_usuario === 2;
            $validatedData = $request->validate([
                'nombres' => 'required|string|max:255',
                'paterno' => 'required|string|max:255',
                'materno' => 'nullable|string|max:255',
                'id_plan' => 'required|exists:planes,id',
                'email' => ['required', 'email', 'max:255', 'unique:clientes,email', 'unique:users,email'],
                'fecha_nacimiento' => 'nullable|date_format:Y-m-d',
                'ci' => ['required', 'string', 'max:20', 'unique:clientes,ci', new RutChileno],
                'telefono' => 'required|string|max:15',
                'id_genero' => 'required|exists:generos,id',
                'direccion' => 'nullable|string|max:255',
                'ciudad' => 'nullable|string|max:100',
                'estado' => 'nullable|integer',
                'descuento' => 'nullable|numeric|min:0',
                'id_usuario' => ['bail', 'required', 'exists:users,id'],
                'id_tipo_usuario' => ['bail', 'required', 'exists:tipos_usuarios,id', Rule::in([4, 5])],
                'id_gimnasio' => ($esSuperAdmin ? 'required' : 'nullable') . '|exists:gimnasios,id',
                'altura' => 'nullable|numeric|min:0',
                'fecha_vencimiento' => 'required|date_format:Y-m-d',
                'fecha_registro' => 'required|date_format:Y-m-d',
                'fecha_fin' => 'required|date_format:Y-m-d|after:fecha_registro',
                'id_motivo_ingreso' => 'required|exists:motivos,id',
                'otro_ingreso' => 'nullable|string|max:100',
                'perfil' => 'nullable|string',
            ], [
                'ci.required' => 'La cédula de identidad es obligatoria.',
                'telefono.required' => 'El teléfono es obligatorio.',
                'email.required' => 'El correo electrónico es obligatorio.',
                'id_usuario.required' => 'Debe seleccionar un entrenador.',
                'id_tipo_usuario.required' => 'Debe seleccionar el tipo de cliente.',
                'id_gimnasio.required' => 'Debe seleccionar un gimnasio.',
            ]);
            $idGimnasio = $esSuperAdmin
                ? (int) $validatedData['id_gimnasio']
                : (Gimnasios::gimnasioActualId() ?: (int) ($validatedData['id_gimnasio'] ?? 0));

            $trainerValido = User::where('id', $validatedData['id_usuario'])
                ->where('id_tipo_usuario', 2)
                ->when($idGimnasio, function ($query) use ($idGimnasio) {
                    $query->where('id_gimnasio', $idGimnasio);
                })->exists();

            $planValido = Planes::where('id', $validatedData['id_plan'])
                ->when($idGimnasio, function ($query) use ($idGimnasio) {
                    $query->where('id_gimnasio', $idGimnasio);
                })->exists();

            if (! $trainerValido || ! $planValido) {
                return redirect()->back()->withErrors(['id_usuario' => 'El cliente solo puede asociarse a entrenadores y planes de su gimnasio.'])->withInput($request->all());
            }

            $fechaHoy = date('YmdHis');
            $validatedData['slug'] = hash('sha256', $validatedData['ci'] . $fechaHoy);
            $validatedData['fecha_ingreso'] = $validatedData['fecha_registro'];
            $validatedData['fecha_pago'] = $validatedData['fecha_vencimiento'];
            $validatedData['id_gimnasio'] = $idGimnasio;
            $validatedData['estado'] = 1;

            if ($esEntrenador) {
                $validatedData['id_usuario'] = (int) $usuarioActual->id;
            }

            // Convertir a objetos Carbon para manipulación
            $fechaInicio = Carbon::parse($validatedData['fecha_registro']);
            $fechaFin = Carbon::parse($validatedData['fecha_fin']);

            // Calcular diferencia en meses
            $mesesDiferencia = $fechaInicio->diffInMonths($fechaFin);
            $cliente = \App\Models\Clientes::create($validatedData);

            $plan = \App\Models\Planes::find($request->id_plan);
            $descuento = $request->descuento ?? 0; // Descuento en pesos
            $monto_pagar = $plan->valor - $descuento;
            $monto = $plan->valor;
            if ($plan->tipo == 2) {
                $monto_pagar = $monto_pagar / 2;
                $monto = $monto / 2;
            }
            // Crear las cuotas mensuales por un año
            $fecha_inicio = Carbon::parse($validatedData['fecha_vencimiento']);
            if ($request->activacion == 1) {
                $activacion = 50000; // Monto de activación
                $cliente->cuotas()->create([
                    'fecha_vencimiento' => $fecha_inicio,
                    'monto' => $activacion,
                    'id_estado_pago' => 1, // Asignar estado de pago "Pendiente"
                    'monto_pagar' => $activacion,
                    'fecha_pago' => null,
                    'descuento' => 0,
                    'monto_pagado' => 0,
                    'saldo' => 0,
                    'id_tipo_cuota' => 1 // Asignar tipo de cuota "Mensual"
                ]);
            }
            for ($i = 0; $i < $mesesDiferencia; $i++) {
                $fecha_vencimiento = $fecha_inicio->copy()->addMonths($i);
                $cliente->cuotas()->create([
                    'fecha_vencimiento' => $fecha_vencimiento,
                    'monto' => $monto,
                    'id_estado_pago' => 1, // Asignar estado de pago "Pendiente"
                    'monto_pagar' => $monto_pagar,
                    'fecha_pago' => null,
                    'descuento' => $descuento,
                    'monto_pagado' => 0,
                    'saldo' => 0,
                    'id_tipo_cuota' => 2 // Asignar tipo de cuota "Mensual"
                ]);
            }

            $clienteLifecycleService->createAccessUserForCliente($cliente, (int) $validatedData['id_tipo_usuario']);
            $clienteLifecycleService->sendActivationWelcomeEmail($cliente);

            DB::commit();
            return redirect()->route('clientes.index')->with('success', 'Cliente creado exitosamente.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->withErrors(['error' => 'Error al crear el cliente: ' . $e->getMessage()])->withInput($request->all());
        }
    }
    public function edit($slug)
    {
        $usuario = Auth::user();
        $esSuperAdmin = (int) $usuario->id_tipo_usuario === 10;
        $idGimnasio = $esSuperAdmin ? null : Gimnasios::gimnasioActualId();
        $gimnasios = Gimnasios::where('estado', 1)->orderBy('nombre')->get();

        $cliente = \App\Models\Clientes::with('plan')
            ->when($idGimnasio, function ($query) use ($idGimnasio) {
                $query->where('id_gimnasio', $idGimnasio);
            })
            ->where('slug', $slug)
            ->firstOrFail();
        $gymObjetivo = $cliente->id_gimnasio ?: $idGimnasio;
        $generos = Generos::where('estado', 1)->get();
        $planes = Planes::where('estado', 1)
            ->when(! $esSuperAdmin && $gymObjetivo, function ($query) use ($gymObjetivo) {
                $query->where('id_gimnasio', $gymObjetivo);
            })->get();
        $tipos_usuarios = \App\Models\TiposUsuarios::where('estado', 1)
            ->whereIn('id', [4, 5])
            ->orderByRaw('CASE WHEN id = 5 THEN 1 ELSE 0 END')
            ->orderBy('id')
            ->get();
        $motivos = \App\Models\Motivos::where('estado', 1)->where('tipo', 2)->orderBy('nombre', 'asc')->get(); // Obtener motivos de ingreso activos
        $motivosi = \App\Models\Motivos::where('estado', 1)->where('tipo', 1)->orderBy('nombre', 'asc')->get(); // Obtener motivos de ingreso activos
        $clientes_duos = Clientes::join('planes', 'clientes.id_plan', 'planes.id')
            ->when($gymObjetivo, function ($query) use ($gymObjetivo) {
                $query->where('clientes.id_gimnasio', $gymObjetivo);
            })
            ->orderBy('clientes.nombres', 'asc')
            ->select('clientes.id', 'clientes.nombres', 'clientes.paterno', 'clientes.materno')
            ->get();

        if (in_array((int) $usuario->id_tipo_usuario, [1, 10], true)) {
            $usuarios = User::where('id_tipo_usuario', 2)
                ->when(! $esSuperAdmin && $gymObjetivo, function ($query) use ($gymObjetivo) {
                    $query->where('id_gimnasio', $gymObjetivo);
                })->get();
        }
        if ((int) $usuario->id_tipo_usuario === 2) {
            $usuarios = User::where('id_tipo_usuario', 2)
                ->when($gymObjetivo, function ($query) use ($gymObjetivo) {
                    $query->where('id_gimnasio', $gymObjetivo);
                })
                ->where('id', $usuario->id)
                ->get();
        }
        if (!in_array((int) $usuario->id_tipo_usuario, [1, 2, 10], true)) {
            $id_cliente = $usuario->id_cliente;
            $usuarios = User::join('clientes', 'clientes.id', 'users.id_cliente')
                ->join('users as u2', 'clientes.id_usuario', 'u2.id')
                ->where('clientes.id', $id_cliente)
                ->select('u2.id', 'u2.name')
                ->get();
        }
        return view('clientes.edit', compact('cliente', 'generos', 'planes', 'usuarios', 'tipos_usuarios', 'motivos', 'motivosi', 'clientes_duos', 'gimnasios'));
    }
    public function update(Request $request, $slug, ClienteLifecycleService $clienteLifecycleService)
    {
        $usuarioActual = Auth::user();
        $esSuperAdmin = (int) $usuarioActual->id_tipo_usuario === 10;
        $esEntrenador = (int) $usuarioActual->id_tipo_usuario === 2;
        $idGimnasio = $esSuperAdmin ? null : Gimnasios::gimnasioActualId();
        $cliente = \App\Models\Clientes::with('plan')
            ->when($idGimnasio, function ($query) use ($idGimnasio) {
                $query->where('id_gimnasio', $idGimnasio);
            })->where('slug', $slug)->firstOrFail();
        $estadoAnterior = (int) $cliente->getRawOriginal('estado');
        $usuarioCliente = User::where('id_cliente', $cliente->id)->first();

        $validatedData = $request->validate([
            'ci' => [
                'required',
                'string',
                'max:20',
                Rule::unique('clientes', 'ci')->ignore($cliente->id),
                new RutChileno,
            ],
            'nombres' => 'required|string|max:255',
            'paterno' => 'required|string|max:255',
            'materno' => 'nullable|string|max:255',
            'email' => [
                'required',
                'email',
                Rule::unique('clientes', 'email')->ignore($cliente->id),
                Rule::unique('users', 'email')->ignore($usuarioCliente?->id),
            ],
            'id_genero' => 'required|exists:generos,id',
            'id_plan' => 'required|exists:planes,id',
            'id_usuario' => ['bail', 'required', 'exists:users,id'],
            'id_tipo_usuario' => ['bail', 'required', 'exists:tipos_usuarios,id', Rule::in([4, 5])],
            'id_gimnasio' => ($esSuperAdmin ? 'required' : 'nullable') . '|exists:gimnasios,id',
            'telefono' => 'required|string|max:15',
            'direccion' => 'nullable|string|max:255',
            'fecha_nacimiento' => 'nullable|date_format:Y-m-d',
            'fecha_ingreso' => 'required|date_format:Y-m-d',
            'fecha_fin' => 'required|date_format:Y-m-d',
            'fecha_vencimiento' => 'required|date_format:Y-m-d',
            'estado' => 'required|integer',
            'altura' => 'nullable|numeric|min:0',
            'id_motivo_egreso' => 'nullable|exists:motivos,id',
            'otro_egreso' => 'nullable|string|max:100',
            'id_motivo_ingreso' => 'required|exists:motivos,id',
            'otro_ingreso' => 'nullable|string|max:100',
            'perfil' => 'nullable|string',
            'id_cliente_duo' => 'nullable|exists:clientes,id',
        ], [
            'ci.required' => 'La cédula de identidad es obligatoria',
            'ci.unique' => 'La cédula de identidad ya está registrada',
            'nombres.required' => 'El nombre es obligatorio',
            'nombres.max' => 'El nombre no debe exceder los 255 caracteres',
            'paterno.required' => 'El apellido paterno es obligatorio',
            'email.required' => 'El correo electrónico es requerido',
            'email.email' => 'Debe ingresar un correo electrónico válido',
            'email.unique' => 'Este correo electrónico ya está registrado',
            'id_genero.required' => 'Debe seleccionar el género del cliente',
            'id_plan.required' => 'Debe seleccionar un plan',
            'id_plan.exists' => 'El plan seleccionado no es válido',
            'id_usuario.required' => 'Debe seleccionar un entrenador',
            'telefono.required' => 'El teléfono es obligatorio',
            'id_tipo_usuario.required' => 'Debe seleccionar el tipo de cliente',
            'id_gimnasio.required' => 'Debe seleccionar un gimnasio',
            'fecha_vencimiento.required' => 'Debe indicar la fecha de vencimiento',
            'estado.required' => 'Debe especificar el estado del cliente',
            'estado.integer' => 'El estado debe ser un valor numérico',
        ]);

        if ($esEntrenador && array_key_exists('estado', $validatedData) && (int) $validatedData['estado'] !== $estadoAnterior) {
            return redirect()->back()->withErrors(['estado' => 'Solo el administrador o el super administrador pueden activar o dar de baja clientes.'])->withInput($request->all());
        }

        $gymObjetivo = $esSuperAdmin
            ? (int) ($validatedData['id_gimnasio'] ?? $cliente->id_gimnasio)
            : ($cliente->id_gimnasio ?: Gimnasios::gimnasioActualId());

        $trainerValido = User::where('id', $validatedData['id_usuario'])
            ->where('id_tipo_usuario', 2)
            ->when($gymObjetivo, function ($query) use ($gymObjetivo) {
                $query->where('id_gimnasio', $gymObjetivo);
            })->exists();

        $planValido = Planes::where('id', $validatedData['id_plan'])
            ->when($gymObjetivo, function ($query) use ($gymObjetivo) {
                $query->where('id_gimnasio', $gymObjetivo);
            })->exists();

        if (! $trainerValido || ! $planValido) {
            return redirect()->back()->withErrors(['id_usuario' => 'El cliente solo puede asociarse a entrenadores y planes de su gimnasio.'])->withInput($request->all());
        }

        $fechaHoy = date('YmdHis');

        $validatedData['slug'] = hash('sha256', $cliente->ci . $fechaHoy);
        $validatedData['fecha_pago'] = $validatedData['fecha_vencimiento'];
        unset($validatedData['fecha_vencimiento']);
        $validatedData['id_gimnasio'] = $gymObjetivo;

        $cliente->update($validatedData);

        if ($usuarioCliente) {
            $usuarioCliente->update([
                'name' => trim(($validatedData['nombres'] ?? '') . ' ' . ($validatedData['paterno'] ?? '') . ' ' . ($validatedData['materno'] ?? '')),
                'email' => $validatedData['email'] ?: $usuarioCliente->email,
                'id_tipo_usuario' => (int) $validatedData['id_tipo_usuario'],
                'id_gimnasio' => $validatedData['id_gimnasio'],
            ]);
        } else {
            $clienteLifecycleService->createAccessUserForCliente($cliente->fresh(), (int) $validatedData['id_tipo_usuario']);
        }

        $cliente->refresh();
        $estadoActual = (int) $cliente->getRawOriginal('estado');

        if ($estadoAnterior !== 1 && $estadoActual === 1) {
            $clienteLifecycleService->sendActivationWelcomeEmail($cliente);
            $clienteLifecycleService->notifyClienteActivated($cliente, $usuarioActual);
        }

        return redirect()->route('clientes.index')->with('success', 'Cliente actualizado exitosamente.');
    }
    public function destroy($id)
    {
        $usuario = Auth::user();
        $esSuperAdmin = (int) $usuario->id_tipo_usuario === 10;
        $idGimnasio = $esSuperAdmin ? null : Gimnasios::gimnasioActualId();
        $cliente = \App\Models\Clientes::when($idGimnasio, function ($query) use ($idGimnasio) {
            $query->where('id_gimnasio', $idGimnasio);
        })->findOrFail($id);

        DB::transaction(function () use ($cliente) {
            $agendaIds = DB::table('agendas')->where('id_cliente', $cliente->id)->pluck('id');

            if ($agendaIds->isNotEmpty() && Schema::hasTable('agendas_ejercicios')) {
                DB::table('agendas_ejercicios')->whereIn('id_agenda', $agendaIds)->delete();
            }

            if (Schema::hasTable('tareas')) {
                DB::table('tareas')->where('id_cliente', $cliente->id)->delete();
            }

            if (Schema::hasTable('parq_respuestas')) {
                DB::table('parq_respuestas')->where('id_cliente', $cliente->id)->delete();
            }

            if (Schema::hasTable('cuestionarios_historicos')) {
                DB::table('cuestionarios_historicos')->where('id_cliente', $cliente->id)->delete();
            }

            $cliente->cuotas()->delete();
            $cliente->pesos()->delete();
            $cliente->imcs()->delete();
            $cliente->aguas()->delete();
            $cliente->grasas()->delete();
            $cliente->pmusculares()->delete();
            $cliente->poseas()->delete();
            $cliente->perimetros()->delete();
            $cliente->cuestionarios()->delete();
            $cliente->cuestionariosHistoricos()->delete();
            $cliente->evaluacionInicial()->delete();

            DB::table('agendas')->where('id_cliente', $cliente->id)->delete();
            User::where('id_cliente', $cliente->id)->delete();
            $cliente->delete();
        });

        return redirect()->route('clientes.index')->with('success', 'Cliente eliminado exitosamente.');
    }
    public function toggleStatus($id, ClienteLifecycleService $clienteLifecycleService)
    {
        $usuarioActual = Auth::user();
        if (! in_array((int) $usuarioActual->id_tipo_usuario, [1, 10], true)) {
            abort(403, 'No tienes permiso para cambiar el estado del cliente.');
        }

        $cliente = \App\Models\Clientes::query()
            ->where(function ($query) use ($id) {
                $query->where('slug', $id);

                if (ctype_digit((string) $id)) {
                    $query->orWhere('id', (int) $id);
                }
            })
            ->firstOrFail();

        $estadoAnterior = (int) $cliente->getRawOriginal('estado');
        $nuevoEstado = $estadoAnterior === 1 ? 0 : 1;

        $cliente->estado = $nuevoEstado;
        $cliente->fecha_baja = $nuevoEstado === 1 ? null : Carbon::now();
        $cliente->save();

        if ($estadoAnterior !== 1 && $nuevoEstado === 1) {
            $clienteLifecycleService->sendActivationWelcomeEmail($cliente);
            $clienteLifecycleService->notifyClienteActivated($cliente, $usuarioActual);
        }

        return redirect()->route('clientes.index')->with('success', 'Estado del cliente actualizado exitosamente.');
    }
    public function show($id)
    {
        // Aquí puedes implementar la lógica para mostrar los detalles de un cliente
        $cliente = \App\Models\Clientes::findOrFail($id);
        return view('clientes.show', compact('cliente'));
    }
    public function cuentaCorriente($slug)
    {
        $cliente = \App\Models\Clientes::with('plan')->where('slug', $slug)->firstOrFail();
        $formas_pagos = \App\Models\FormasPagos::get(); // Obtener formas de pago activas
        //$cuotas = $cliente->cuotas()->orderBy('fecha_vencimiento', 'ASC')->get();

        //$cuotas = $cliente->cuotas()->with('formaPago')->orderBy('fecha_vencimiento', 'asc')->get();
        $cuotas = $cliente->cuotas()->with(['formaPago', 'tipoCuota'])->orderBy('fecha_vencimiento', 'asc')->get();
        //$cuotas = $cliente->cuotas->sortBy('fecha_vencimiento');
        $total_monto = $cuotas->sum('monto');
        $total_pagado = $cuotas->sum('monto_pagado');
        $total_saldo = $cuotas->sum('saldo');
        $descuento = $cuotas->sum('descuento'); //$cuotas[0]->descuento ?? 0; // Asignar descuento de la primera cuota, si existe
        //dd($cuotas);
        return view('clientes.cuenta_corriente', compact('cliente', 'cuotas', 'total_monto', 'total_pagado', 'total_saldo', 'descuento', 'formas_pagos'));
    }
    public function pagar($id)
    {
        $datos = \App\Models\Clientes::join('cuentas_corrientes', 'clientes.id', '=', 'cuentas_corrientes.id_cliente')
            ->where('cuentas_corrientes.id', $id)
            ->select('clientes.*', 'cuentas_corrientes.*')
            ->firstOrFail();
        $formas_pagos = \App\Models\FormasPagos::get();
        $estados_pagos = \App\Models\EstadosPagos::get();
        return view('clientes.pagar', compact('datos', 'formas_pagos', 'estados_pagos'));
    }

    public function procesarPago(Request $request, $id)
    {
        try {
            DB::beginTransaction();
            $cliente = \App\Models\Clientes::join('cuentas_corrientes', 'clientes.id', '=', 'cuentas_corrientes.id_cliente')
                ->where('cuentas_corrientes.id', $id)
                ->select(
                    'clientes.*',
                    'cuentas_corrientes.*',
                    DB::raw('cuentas_corrientes.id as cuenta_id')
                )
                ->firstOrFail();
            $validatedData = $request->validate([
                'id_forma_pago' => 'required|exists:formas_pagos,id',
                'monto' => 'required|integer|min:0',
                'fecha_pago' => 'required|date_format:Y-m-d',
            ]);
            $slug = $cliente->slug;
            $estado_pago = 2;
            if ($request->monto < $request->monto_pagar) {
                $estado_pago = 3;
            }
            $monto_a_pagar = $cliente->monto_pagar;
            $monto_pagado = $cliente->monto_pagado;
            if ($monto_a_pagar == ($monto_pagado + $request->monto)) {
                $estado_pago = 2;
            }
            // Procesar el pago
            $cuota = $cliente->cuenta_id;
            DB::table('cuentas_corrientes')
                ->where('id', $cuota)
                ->update([
                    'monto_pagado' => DB::raw('monto_pagado + ' . $validatedData['monto']),
                    'fecha_pago' => Carbon::parse($validatedData['fecha_pago']),
                    'id_estado_pago' => $estado_pago,
                    'id_forma_pago' => $validatedData['id_forma_pago'],
                ]);

            if ($request->hasFile('comprobante')) {
                $comprobante = $request->file('comprobante');
                $comprobantePath = $comprobante->store('comprobantes', 'public');
                DB::table('cuentas_corrientes')
                    ->where('id', $cuota)
                    ->update(['comprobante' => $comprobantePath]);
            }
            DB::commit();

            // Obtener datos para el correo
            $cuotaPagada = \App\Models\CuentasCorrientes::with(['formaPago', 'tipoCuota'])
                ->where('id', $cuota)
                ->first();

            $datosPago = [
                'fecha_pago' => $cuotaPagada->fecha_pago,
                'monto_pagado' => $cuotaPagada->monto_pagado,
                'forma_pago' => $cuotaPagada->formaPago->nombre ?? 'Desconocido',
                'fecha_vencimiento' => $cuotaPagada->fecha_vencimiento,
                'tipo_cuota' => $cuotaPagada->tipoCuota->nombre ?? 'Desconocido',
            ];
            // Enviar notificación al cliente
            $clienteModel = \App\Models\Clientes::find($cuotaPagada->id_cliente);
            if ($clienteModel && $clienteModel->email) {
                $clienteModel->notify(new \App\Notifications\PagoCuotaRealizado($datosPago));
            }

            return redirect()->route('clientes.cuenta_corriente', ['slug' => $slug])->with('success', 'Pago procesado exitosamente.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->withErrors(['error' => 'Error al procesar el pago: ' . $e->getMessage()]);
        }
    }
    public function createCuota($slug)
    {
        $cliente = \App\Models\Clientes::with('plan')->where('slug', $slug)->firstOrFail();
        return view('clientes.create_cuota', compact('cliente'));
    }
    public function storeCuota(Request $request, $slug)
    {
        $cliente = \App\Models\Clientes::where('slug', $slug)->firstOrFail();
        /*$validatedData = $request->validate([
            'fecha_vencimiento' => 'required|date_format:Y-m-d',
            'monto' => 'required|numeric|min:0',
            'descuento' => 'nullable|numeric|min:0',
        ]);
        $validatedData['id_cliente'] = $cliente->id;
        $validatedData['id_estado_pago'] = 1; // Asignar estado de pago "Pendiente"
        $validatedData['monto_pagado'] = 0;
        $validatedData['saldo'] = $validatedData['monto'] - ($validatedData['monto'] * ($validatedData['descuento'] ?? 0) / 100);
        $validatedData['monto_pagar'] = $validatedData['monto'] - ($validatedData['monto'] * ($validatedData['descuento'] ?? 0) / 100);
        $cliente->cuotas()->create($validatedData);
        */

        $validatedData = $request->validate([
            'fecha_vencimiento' => 'required|date_format:Y-m-d',
            'monto' => 'required|numeric|min:0',
            'descuento' => 'nullable|numeric|min:0', // Descuento en pesos
            'cantidad' => 'required|integer|min:1', // Nuevo campo para la cantidad de cuotas
        ]);

        $fechaBase = Carbon::createFromFormat('Y-m-d', $validatedData['fecha_vencimiento']);
        $monto = $validatedData['monto'];
        $descuento = $validatedData['descuento'] ?? 0; // Descuento en pesos

        // Calcular el monto a pagar (monto - descuento en pesos)
        $montoAPagar = $monto - $descuento;

        // Crear las cuotas mensuales
        for ($i = 0; $i < $validatedData['cantidad']; $i++) {
            $cuotaData = [
                'fecha_vencimiento' => $fechaBase->copy()->addMonths($i)->format('Y-m-d'),
                'monto' => $monto,
                'descuento' => $descuento,
                'id_cliente' => $cliente->id,
                'id_estado_pago' => 1, // Estado "Pendiente"
                'monto_pagado' => 0,
                'saldo' => $montoAPagar,
                'monto_pagar' => $montoAPagar,
                'id_tipo_cuota' => 2
            ];

            $cliente->cuotas()->create($cuotaData);
        }
        return redirect()->route('clientes.cuenta_corriente', ['slug' => $slug])->with('success', 'Cuota creada exitosamente.');
    }

    public function pagarMultiple(Request $request, $slug)
    {
        $request->validate([
            'cuotas' => 'required|array|min:1',
            'cuotas.*' => 'exists:cuentas_corrientes,id',
            'id_forma_pago' => 'required|exists:formas_pagos,id',
            'comprobante' => 'nullable|file|max:10240', // 10MB máx
        ]);

        $cuotas = \App\Models\CuentasCorrientes::whereIn('id', $request->cuotas)->get();

        // Si hay comprobante, súbelo una sola vez y asócialo a todas las cuotas seleccionadas
        $comprobantePath = null;
        $comprobanteTipo = null;
        if ($request->hasFile('comprobante')) {
            $comprobante = $request->file('comprobante');
            $comprobantePath = $comprobante->store('comprobantes', 'public');
            $comprobanteTipo = $comprobante->getMimeType();
        }

        foreach ($cuotas as $cuota) {
            $cuota->update([
                'monto_pagado' => $cuota->monto_pagar,
                'saldo' => 0,
                'fecha_pago' => now(),
                'id_estado_pago' => 2, // Pagada
                'id_forma_pago' => $request->id_forma_pago,
                'comprobante' => $comprobantePath, // Guarda la ruta del comprobante
            ]);

            $cuota->load(['formaPago', 'tipoCuota']);
            $datosPago = [
                'fecha_pago' => $cuota->fecha_pago,
                'monto_pagado' => $cuota->monto_pagado,
                'forma_pago' => $cuota->formaPago->nombre ?? 'Desconocido',
                'fecha_vencimiento' => $cuota->fecha_vencimiento,
                'tipo_cuota' => $cuota->tipoCuota->nombre ?? 'Desconocido',

            ];
            // Enviar notificación al cliente
            $clienteModel = \App\Models\Clientes::find($cuota->id_cliente);
            if ($clienteModel && $clienteModel->email) {
                $clienteModel->notify(new \App\Notifications\PagoCuotaRealizado($datosPago));
            }
        }

        return redirect()->route('clientes.cuenta_corriente', $slug)
            ->with('success', 'Cuotas pagadas correctamente.');
    }

    // public function destroyCuota($slug, $id)
    // {
    //     $cuota = \App\Models\CuentasCorrientes::findOrFail($id);
    //     $cuota->delete();

    //     return redirect()->route('clientes.cuenta_corriente', $slug) // Asegúrate de que esta ruta esté definida correctamente en routes/web.php
    //         ->with('success', 'Cuota eliminada correctamente.');
    // }

    public function destroyCuota($slug, $id)
    {
        // Buscar la cuota por ID
        $cuota = \App\Models\CuentasCorrientes::findOrFail($id);

        // Eliminar la cuota
        $cuota->delete();

        // Enviar respuesta JSON
        return response()->json(['success' => true, 'message' => 'Cuota eliminada correctamente']);
    }



    public function pesos($slug)
    {
        $cliente = \App\Models\Clientes::with('plan')->where('slug', $slug)->firstOrFail();
        $pesos = $cliente->pesos()->orderBy('created_at', 'desc')->get();
        $pesoReciente = $pesos->first();
        if (!$pesoReciente) {
            $pesoReciente = (object)[
                'created_at' => now(),
                'peso' => 0,
                'sumatoria_pliegues' => null,
                'grasa_region_superior' => null,
                'grasa_region_media' => null,
                'grasa_region_inferior' => null,
            ];
        }

        // $perimetros = $cliente->perimetros()->orderBy('created_at', 'desc')->get();
        // $perimetroReciente = $perimetros->first();

        $pesoChartData = $this->buildMetricChartData($pesos, 'peso');

        //return view('clientes.peso', compact('cliente', 'pesos', 'pesoReciente', 'perimetros', 'perimetroReciente'));
        return view('clientes.peso', compact('cliente', 'pesos', 'pesoReciente', 'pesoChartData'));
    }

    // Guardar peso + mediciones
    public function storePeso(Request $request, $slug)
    {
        $cliente = \App\Models\Clientes::where('slug', $slug)->firstOrFail();

        $request->validate([
            'peso' => 'required|numeric|min:0',
            'created_at' => 'required|date_format:Y-m-d',
            'sumatoria_pliegues' => 'nullable|numeric|min:0',
            'grasa_region_superior' => 'nullable|numeric|min:0',
            'grasa_region_media' => 'nullable|numeric|min:0',
            'grasa_region_inferior' => 'nullable|numeric|min:0',
        ]);

        $fecha = $request->input('created_at');
        $fechaHora = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $fecha . ' 13:00:00');

        $peso = new \App\Models\Pesos();
        $peso->peso = $request->peso;
        $peso->id_cliente = $cliente->id;
        $peso->sumatoria_pliegues = $request->input('sumatoria_pliegues');
        $peso->grasa_region_superior = $request->input('grasa_region_superior');
        $peso->grasa_region_media = $request->input('grasa_region_media');
        $peso->grasa_region_inferior = $request->input('grasa_region_inferior');
        $peso->created_at = $fechaHora;
        $peso->save();

        return redirect()->route('clientes.pesos', $cliente->slug)->with('success', 'Peso y mediciones registradas correctamente.');
    }

    // Guardar perímetro
    public function storePerimetro(Request $request, $slug)
    {
        $cliente = \App\Models\Clientes::where('slug', $slug)->firstOrFail();

        $request->validate([
            'cabeza' => 'nullable|numeric|min:0',
            'brazo_relajado' => 'nullable|numeric|min:0',
            'brazo_flexionado_tension' => 'nullable|numeric|min:0',
            'antebrazo' => 'nullable|numeric|min:0',
            'torax_mesoexternal' => 'nullable|numeric|min:0',
            'cintura_minima' => 'nullable|numeric|min:0',
            'caderas_maxima' => 'nullable|numeric|min:0',
            'muslo_superior' => 'nullable|numeric|min:0',
            'muslo_medial' => 'nullable|numeric|min:0',
            'pantorrilla_maxima' => 'nullable|numeric|min:0',
            'created_at' => 'nullable|date_format:Y-m-d',
        ]);

        $per = new \App\Models\Perimetro($request->only([
            'cabeza',
            'brazo_relajado',
            'brazo_flexionado_tension',
            'antebrazo',
            'torax_mesoexternal',
            'cintura_minima',
            'caderas_maxima',
            'muslo_superior',
            'muslo_medial',
            'pantorrilla_maxima'
        ]));

        $per->id_cliente = $cliente->id;

        if ($request->filled('created_at')) {
            $per->created_at = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $request->created_at . ' 13:00:00');
        }

        $per->save();

        return redirect()->route('clientes.pesos', $cliente->slug)->with('success', 'Perímetro registrado correctamente.');
    }

    // Eliminar perímetro
    public function destroyPerimetro($id)
    {
        $p = \App\Models\Perimetro::findOrFail($id);
        $slug = $p->cliente->slug;
        $p->delete();
        return redirect()->route('clientes.pesos', $slug)->with('success', 'Registro de perímetro eliminado.');
    }
    public function destroyPeso($id)
    {
        $peso = \App\Models\Pesos::findOrFail($id);
        $peso->delete();
        return redirect()->back()->with('success', 'Peso eliminado exitosamente.');
    }
    public function editPeso($id)
    {
        $peso = \App\Models\Pesos::findOrFail($id);
        return view('clientes.edit_peso', compact('peso'));
    }
    public function updatePeso(Request $request, $id)
    {
        $peso = \App\Models\Pesos::findOrFail($id);
        $request->validate([
            'peso' => 'required|numeric|min:0',
        ]);
        $peso->peso = $request->peso;
        $peso->save();
        return redirect()->route('clientes.peso', $peso->cliente->slug)->with('success', 'Peso actualizado exitosamente.');
    }
    public function imcs($slug)
    {
        $cliente = \App\Models\Clientes::with('plan')->where('slug', $slug)->firstOrFail();
        $imcs = $cliente->imcs()->orderBy('created_at', 'desc')->get();
        $imcReciente = $cliente->imcs()
            ->orderByDesc('updated_at')
            ->orderByDesc('created_at')
            ->first();
        if (!$imcReciente) {
            $imcReciente = new \App\Models\IMCS();
            $imcReciente->imc = 0; // Valor por defecto si no hay IMC
            $imcReciente->created_at = Carbon::now();
            $imcReciente->updated_at = Carbon::now();
        }

        $imcChartData = $this->buildMetricChartData($imcs, 'imc');

        return view('clientes.imc', compact('cliente', 'imcs', 'imcReciente', 'imcChartData'));
    }
    public function createImc($slug)
    {
        $cliente = \App\Models\Clientes::with('plan')->where('slug', $slug)->firstOrFail();
        return view('clientes.create_imc', compact('cliente'));
    }
    public function storeImc(Request $request, $slug)
    {
        $cliente = \App\Models\Clientes::where('slug', $slug)->firstOrFail();
        $request->validate([
            'imc' => 'required|numeric|min:0|max:100',
        ]);
        $imc = new \App\Models\IMCS();
        $imc->imc = $request->imc;
        $imc->id_cliente = $cliente->id;
        $imc->save();
        return redirect()->route('clientes.imcs', $slug)->with('success', 'IMC registrado exitosamente.');
    }
    public function destroyImc($id)
    {
        $imc = \App\Models\IMCS::findOrFail($id);
        $imc->delete();
        return redirect()->back()->with('success', 'IMC eliminado exitosamente.');
    }
    public function editImc($id)
    {
        $imc = \App\Models\IMCS::findOrFail($id);
        return view('clientes.edit_imc', compact('imc'));
    }
    public function updateImc(Request $request, $id)
    {
        $imc = \App\Models\IMCS::findOrFail($id);
        $request->validate([
            'imc' => 'required|numeric|min:0',
        ]);
        $imc->imc = $request->imc;
        $imc->save();
        return redirect()->route('clientes.imcs', $imc->cliente->slug)->with('success', 'IMC actualizado exitosamente.');
    }

    public function aguas($slug)
    {
        $cliente = \App\Models\Clientes::with('plan')->where('slug', $slug)->firstOrFail();
        $aguas = $cliente->aguas()->orderBy('created_at', 'desc')->get();
        $aguaReciente = $cliente->aguas()
            ->orderByDesc('updated_at')
            ->orderByDesc('created_at')
            ->first();
        if (!$aguaReciente) {
            $aguaReciente = new \App\Models\Aguas();
            $aguaReciente->valor = 0; // Valor por defecto si no hay
            $aguaReciente->created_at = Carbon::now();
            $aguaReciente->updated_at = Carbon::now();
        }

        $aguaChartData = $this->buildMetricChartData($aguas, 'valor');

        return view('clientes.aguas', compact('cliente', 'aguas', 'aguaReciente', 'aguaChartData'));
    }
    public function storeAgua(Request $request, $slug)
    {
        $cliente = \App\Models\Clientes::where('slug', $slug)->firstOrFail();
        $request->validate([
            'valor' => 'required|numeric|min:0|max:100', // Ajusta el rango según tus necesidades
        ]);
        $agua = new \App\Models\Aguas();
        $agua->valor = $request->valor;
        $agua->id_cliente = $cliente->id;
        $agua->save();
        return redirect()->route('clientes.aguas', $slug)->with('success', 'Agua registrada exitosamente.');
    }
    public function destroyAgua($id)
    {
        $agua = \App\Models\Aguas::findOrFail($id);
        $agua->delete();
        return redirect()->back()->with('success', 'Agua eliminado exitosamente.');
    }
    public function editAgua($id)
    {
        $agua = \App\Models\Aguas::findOrFail($id);
        return view('clientes.edit_agua', compact('agua'));
    }
    public function updateAgua(Request $request, $id)
    {
        $agua = \App\Models\Aguas::findOrFail($id);
        $request->validate([
            'valor' => 'required|numeric|min:0',
        ]);
        $agua->valor = $request->valor;
        $agua->save();
        return redirect()->route('clientes.aguas', $agua->cliente->slug)->with('success', 'Agua actualizado exitosamente.');
    }

    public function grasas($slug)
    {
        $cliente = \App\Models\Clientes::with('plan')->where('slug', $slug)->firstOrFail();
        $grasas = $cliente->grasas()->orderBy('created_at', 'desc')->get();
        $grasaReciente = $cliente->grasas()
            ->orderByDesc('updated_at')
            ->orderByDesc('created_at')
            ->first();
        if (!$grasaReciente) {
            $grasaReciente = new \App\Models\Grasas();
            $grasaReciente->valor = 0; // Valor por defecto si no hay
            $grasaReciente->created_at = Carbon::now();
            $grasaReciente->updated_at = Carbon::now();
        }

        $grasaChartData = $this->buildMetricChartData($grasas, 'valor');

        return view('clientes.pgrasa', compact('cliente', 'grasas', 'grasaReciente', 'grasaChartData'));
    }
    public function storeGrasa(Request $request, $slug)
    {
        $cliente = \App\Models\Clientes::where('slug', $slug)->firstOrFail();
        $request->validate([
            'valor' => 'required|numeric|min:0|max:100', // Ajusta el rango según tus necesidades
        ]);
        $grasa = new \App\Models\Grasas();
        $grasa->valor = $request->valor;
        $grasa->id_cliente = $cliente->id;
        $grasa->save();
        return redirect()->route('clientes.grasas', $slug)->with('success', 'Grasa registrada exitosamente.');
    }
    public function destroyGrasa($id)
    {
        $grasa = \App\Models\Grasas::findOrFail($id);
        $grasa->delete();
        return redirect()->back()->with('success', 'Grasa eliminado exitosamente.');
    }
    public function editGrasa($id)
    {
        $grasa = \App\Models\Grasas::findOrFail($id);
        return view('clientes.edit_grasa', compact('grasa'));
    }
    public function updateGrasa(Request $request, $id)
    {
        $grasa = \App\Models\Grasas::findOrFail($id);
        $request->validate([
            'valor' => 'required|numeric|min:0',
        ]);
        $grasa->valor = $request->valor;
        $grasa->save();
        return redirect()->route('clientes.grasas', $grasa->cliente->slug)->with('success', 'Grasa actualizado exitosamente.');
    }

    public function pmusculares($slug)
    {
        $cliente = \App\Models\Clientes::with('plan')->where('slug', $slug)->firstOrFail();
        $pmuscular = $cliente->pmusculares()->orderBy('created_at', 'desc')->get();
        //$pmuscular = $pmuscular->sortBy('valor');
        $pmuscularReciente = $cliente->pmusculares()
            ->orderByDesc('updated_at')
            ->orderByDesc('created_at')
            ->first();
        if (!$pmuscularReciente) {
            $pmuscularReciente = new \App\Models\Pmusculares();
            $pmuscularReciente->valor = 0; // Valor por defecto si no hay
            $pmuscularReciente->created_at = Carbon::now();
            $pmuscularReciente->updated_at = Carbon::now();
        }

        $pmuscularChartData = $this->buildMetricChartData($pmuscular, 'valor');

        return view('clientes.pmuscular', compact('cliente', 'pmuscular', 'pmuscularReciente', 'pmuscularChartData'));
    }
    public function storePmuscular(Request $request, $slug)
    {
        $cliente = \App\Models\Clientes::where('slug', $slug)->firstOrFail();
        $request->validate([
            'valor' => 'required|numeric|min:0|max:100', // Ajusta el rango según tus necesidades
        ]);
        $pmuscular = new \App\Models\Pmusculares();
        $pmuscular->valor = $request->valor;
        $pmuscular->id_cliente = $cliente->id;
        $pmuscular->save();
        return redirect()->route('clientes.pmusculares', $slug)->with('success', 'Masa muscular registrada exitosamente.');
    }
    public function destroyPmuscular($id)
    {
        $pmuscular = \App\Models\Pmusculares::findOrFail($id);
        $pmuscular->delete();
        return redirect()->back()->with('success', 'Masa muscular eliminada exitosamente.');
    }
    public function editPmuscular($id)
    {
        $pmuscular = \App\Models\Pmusculares::findOrFail($id);
        return view('clientes.edit_pmuscular', compact('pmuscular'));
    }
    public function updatePmuscular(Request $request, $id)
    {
        $pmuscular = \App\Models\Pmusculares::findOrFail($id);
        $request->validate([
            'valor' => 'required|numeric|min:0',
        ]);
        $pmuscular->valor = $request->valor;
        $pmuscular->save();
        return redirect()->route('clientes.pmusculares', $pmuscular->cliente->slug)->with('success', 'Masa muscular actualizada exitosamente.');
    }

    public function poseas($slug)
    {
        $cliente = \App\Models\Clientes::with('plan')->where('slug', $slug)->firstOrFail();
        $posea = $cliente->poseas()->orderBy('created_at', 'desc')->get();
        $poseaReciente = $cliente->poseas()
            ->orderByDesc('updated_at')
            ->orderByDesc('created_at')
            ->first();
        if (!$poseaReciente) {
            $poseaReciente = new \App\Models\Poseas();
            $poseaReciente->valor = 0; // Valor por defecto si no hay
            $poseaReciente->created_at = Carbon::now();
            $poseaReciente->updated_at = Carbon::now();
        }

        $poseaChartData = $this->buildMetricChartData($posea, 'valor');

        return view('clientes.posea', compact('cliente', 'posea', 'poseaReciente', 'poseaChartData'));
    }
    public function storePosea(Request $request, $slug)
    {
        $cliente = \App\Models\Clientes::where('slug', $slug)->firstOrFail();
        $request->validate([
            'valor' => 'required|numeric|min:0|max:100', // Ajusta el rango según tus necesidades
        ]);
        $posea = new \App\Models\Poseas();
        $posea->valor = $request->valor;
        $posea->id_cliente = $cliente->id;
        $posea->save();
        return redirect()->route('clientes.poseas', $slug)->with('success', 'Posea registrada exitosamente.');
    }
    public function destroyPosea($id)
    {
        $posea = \App\Models\Poseas::findOrFail($id);
        $posea->delete();
        return redirect()->back()->with('success', 'Posea eliminada exitosamente.');
    }
    public function editPosea($id)
    {
        $posea = \App\Models\Poseas::findOrFail($id);
        return view('clientes.edit_posea', compact('posea'));
    }
    public function updatePosea(Request $request, $id)
    {
        $posea = \App\Models\Poseas::findOrFail($id);
        $request->validate([
            'valor' => 'required|numeric|min:0',
        ]);
        $posea->valor = $request->valor;
        $posea->save();
        return redirect()->route('clientes.poseas', $posea->cliente->slug)->with('success', 'Posea actualizada exitosamente.');
    }

    public function agenda($slug)
    {
        $cliente = \App\Models\Clientes::with('plan')->where('slug', $slug)->firstOrFail();


        $agendas = \App\Models\Agendas::with([
            'cliente',
            'ejercicios'
        ])->where('id_cliente', $cliente->id)->get();


        $estados = [
            0 => 'Inactivo',
            1 => 'Activo',
            2 => 'Cancelado sin recuperación',
            3 => 'Cancelado con recuperación',
            4 => 'Realizado',
            5 => 'Reprogramado',
        ];

        $eventos = $agendas->map(function ($agenda) use ($estados) {
            $detalle_ejercicios = $agenda->ejercicios->map(function ($ejercicio) {
                return  $ejercicio->nombre . ': ' . $ejercicio->pivot->serie . ' serie(s) ' . $ejercicio->pivot->repeticiones . ' Carga ' . $ejercicio->pivot->carga . ' RIR ' . $ejercicio->pivot->rir . ' RPE ' . $ejercicio->pivot->rpe . ' RM ' . $ejercicio->pivot->rm . ' Descanso ' . $ejercicio->pivot->descanso . " \n";
            })->implode('');

            $detalle_ejercicios_iconos = '<table style="width:100%;text-align:left">';
            $detalle_ejercicios_iconos .= '<thead><td colspan="2" style="border:1px solid #d2d2d2;font-size: small">Ejercicio</td><td nowrap style="border:1px solid #d2d2d2;font-size: small">Serie(s)</td><td nowrap style="border:1px solid #d2d2d2;font-size: small">Reps</td><td nowrap style="border:1px solid #d2d2d2;font-size: small">Carga</td><td nowrap style="border:1px solid #d2d2d2;font-size: small">Descanso</td></thead>';
            $detalle_ejercicios_iconos .= '<tbody>';
            foreach ($agenda->ejercicios as $ejercicio) {
                $detalle_ejercicios_iconos .= '<tr>';
                $detalle_ejercicios_iconos .= '<td  style="border-left:1px solid #d2d2d2;border-bottom:1px solid #d2d2d2;width:40px"><img src="/adm/iconos/' . $ejercicio->icono . '" width="30"></td>';
                $detalle_ejercicios_iconos .= '<td style="border-bottom:1px solid #d2d2d2;font-size: small">' . $ejercicio->nombre . '</td>';
                $detalle_ejercicios_iconos .= '<td style="border:1px solid #d2d2d2;text-align:right;font-size: small">' . $ejercicio->pivot->serie . '</td>';
                $detalle_ejercicios_iconos .= '<td style="border:1px solid #d2d2d2;text-align:right;font-size: small">' . $ejercicio->pivot->repeticiones . '</td>';
                $detalle_ejercicios_iconos .= '<td style="border:1px solid #d2d2d2;text-align:right;font-size: small">' . $ejercicio->pivot->carga . '</td>';
                $detalle_ejercicios_iconos .= '<td  style="border:1px solid #d2d2d2;text-align:right;font-size: small">' . $ejercicio->pivot->descanso . '</td>';
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
                'title' => $agenda->cliente->nombres . '  ' . $agenda->cliente->paterno . "\n" . $detalle_ejercicios,
                'titulo' => $agenda->cliente->nombres . '  ' . $agenda->cliente->paterno,
                'start' => $agenda->fecha_inicio,
                'end'   => $agenda->fecha_fin,
                'estado' => $estados[$agenda->estado] ?? 'Desconocido',
                'color' => $color,
                'iconos' =>  $detalle_ejercicios_iconos,
            ];
        });
        return view('clientes.agenda', compact('cliente', 'eventos'));
    }

    public function evolucionClienteTabla($slug)
    {
        $cliente = \App\Models\Clientes::where('slug', $slug)->firstOrFail();
        $evolucion = $this->evolucionCliente($slug);
        //dd($evolucion);
        return view('clientes.evolucion_tabla', compact('cliente', 'evolucion'));
    }

    /****** */
    private function buildMetricChartData($registros, string $campo): array
    {
        $ordenados = $registros->sortBy('created_at')->values();

        return [
            'labels' => $ordenados->pluck('created_at')
                ->map(fn($fecha) => Carbon::parse($fecha)->format('d/m/Y'))
                ->values()
                ->all(),
            'values' => $ordenados->pluck($campo)
                ->map(fn($valor) => $valor !== null ? (float) $valor : null)
                ->values()
                ->all(),
        ];
    }

    private function buildReporteChartData($pesos, $imcs, $grasas, $posea, $pmuscular, $perimetros): array
    {
        $pesoChartData = $this->buildMetricChartData($pesos, 'peso');
        $imcChartData = $this->buildMetricChartData($imcs, 'imc');
        $grasaChartData = $this->buildMetricChartData($grasas, 'valor');
        $poseaChartData = $this->buildMetricChartData($posea, 'valor');
        $pmuscularChartData = $this->buildMetricChartData($pmuscular, 'valor');
        $perimetrosOrdenados = $perimetros->sortBy('created_at')->values();

        return [
            'pesosLabels' => $pesoChartData['labels'],
            'pesosData' => $pesoChartData['values'],
            'imcsLabels' => $imcChartData['labels'],
            'imcsData' => $imcChartData['values'],
            'grasasLabels' => $grasaChartData['labels'],
            'grasasData' => $grasaChartData['values'],
            'poseaLabels' => $poseaChartData['labels'],
            'poseaData' => $poseaChartData['values'],
            'pmuscularLabels' => $pmuscularChartData['labels'],
            'pmuscularData' => $pmuscularChartData['values'],
            'perimetros' => $perimetrosOrdenados->map(function ($perimetro) {
                return Arr::only($perimetro->toArray(), [
                    'cabeza',
                    'brazo_relajado',
                    'brazo_flexionado_tension',
                    'antebrazo',
                    'torax_mesoexternal',
                    'cintura_minima',
                    'caderas_maxima',
                    'muslo_superior',
                    'muslo_medial',
                    'pantorrilla_maxima',
                ]);
            })->all(),
            'perimetrosLabels' => $perimetrosOrdenados->pluck('created_at')
                ->map(fn($fecha) => Carbon::parse($fecha)->format('d/m/Y'))
                ->values()
                ->all(),
        ];
    }

    public function reporte($slug)
    {
        $cliente = \App\Models\Clientes::with('plan')->where('slug', $slug)->firstOrFail();
        $pesos = $cliente->pesos()->orderBy('created_at')->get();
        $imcs = $cliente->imcs()->orderBy('created_at')->get();
        $cuotas = $cliente->cuotas()->orderBy('fecha_vencimiento')->get();
        $parq = \App\Models\ParqRespuestas::where('id_cliente', $cliente->id)->with('pregunta')->get();
        $aguas = $cliente->aguas()->orderBy('created_at')->get();
        $grasas = $cliente->grasas()->orderBy('created_at')->get();
        $posea = $cliente->poseas()->orderBy('created_at')->get();
        $pmuscular = $cliente->pmusculares()->orderBy('created_at')->get();
        $perimetros = $cliente->perimetros()->orderBy('created_at')->get();
        $fitPlan = $cliente->cuestionarios()->where('id_cliente', $cliente->id)->first();
        $reportChartData = $this->buildReporteChartData($pesos, $imcs, $grasas, $posea, $pmuscular, $perimetros);

        return view('clientes.reporte', compact(
            'cliente',
            'pesos',
            'imcs',
            'cuotas',
            'parq',
            'fitPlan',
            'aguas',
            'grasas',
            'posea',
            'pmuscular',
            'perimetros',
            'reportChartData'
        ));
    }

    public function enviarReporte(Request $request, $slug)
    {
        $cliente = \App\Models\Clientes::where('slug', $slug)->firstOrFail();
        $this->generarYEnviarReportePdf($cliente);

        return back()->with('success', 'Reporte enviado correctamente.');
    }

    public function generarYEnviarReportePdf(Clientes $cliente): void
    {
        // Repite la carga de datos igual que en reporte()
        $pesos = $cliente->pesos()->orderBy('created_at')->get();
        $imcs = $cliente->imcs()->orderBy('created_at')->get();
        $cuotas = $cliente->cuotas()->orderBy('fecha_vencimiento')->get();
        $parq = \App\Models\ParqRespuestas::where('id_cliente', $cliente->id)->with('pregunta')->get();
        $aguas = $cliente->aguas()->orderBy('created_at')->get();
        $grasas = $cliente->grasas()->orderBy('created_at')->get();
        $posea = $cliente->poseas()->orderBy('created_at')->get();
        $pmuscular = $cliente->pmusculares()->orderBy('created_at')->get();
        $perimetros = $cliente->perimetros()->orderBy('created_at')->get();
        $fitPlan = $cliente->cuestionarios()->where('id_cliente', $cliente->id)->first();

        $pesoLabels = $pesos->pluck('created_at')->map(fn($d) => $d->format('d/m/Y'))->values();
        $pesoData = $pesos->pluck('peso')->values();

        $chartPesoUrl = 'https://quickchart.io/chart?c=' . urlencode(json_encode([
            'type' => 'line',
            'data' => [
                'labels' => $pesoLabels,
                'datasets' => [[
                    'label' => 'Peso (kg)',
                    'data' => $pesoData,
                    'borderColor' => 'blue',
                    'fill' => false,
                    'tension' => 0.4,
                    'pointRadius' => 4,
                    'pointBackgroundColor' => 'rgba(59,130,246,1)'
                ]]
            ]
        ]));

        $imcsLabels = $imcs->pluck('created_at')->map(fn($d) => $d->format('d/m/Y'))->values();
        $imcsData = $imcs->pluck('imc')->values();


        $chartimcsUrl = 'https://quickchart.io/chart?c=' . urlencode(json_encode([
            'type' => 'line',
            'data' => [
                'labels' => $imcsLabels,
                'datasets' => [[
                    'label' => 'IMC',
                    'data' => $imcsData,
                    'borderColor' => 'green',
                    'fill' => false,
                    'tension' => 0.4,
                    'pointRadius' => 4,
                    'pointBackgroundColor' => 'rgb(27, 119, 44)'
                ]]
            ]
        ]));

        $aguaLabels = $aguas->pluck('created_at')->map(fn($d) => $d->format('d/m/Y'))->values();
        $aguaData = $aguas->pluck('valor')->values();


        $chartAguaUrl = 'https://quickchart.io/chart?c=' . urlencode(json_encode([
            'type' => 'line',
            'data' => [
                'labels' => $aguaLabels,
                'datasets' => [[
                    'label' => '% de Agua',
                    'data' => $aguaData,
                    'borderColor' => 'green',
                    'fill' => false,
                    'tension' => 0.4,
                    'pointRadius' => 4,
                    'pointBackgroundColor' => 'rgb(27, 119, 44)'
                ]]
            ],
            'options' => [
                'plugins' => [
                    'legend' => [
                        'display' => false
                    ]
                ],
            ]
        ]));

        $grasaLabels = $grasas->pluck('created_at')->map(fn($d) => $d->format('d/m/Y'))->values();
        $grasaData = $grasas->pluck('valor')->values();


        $chartGrasaUrl = 'https://quickchart.io/chart?c=' . urlencode(json_encode([
            'type' => 'line',
            'data' => [
                'labels' => $grasaLabels,
                'datasets' => [[
                    'label' => '% de Grasa Corporal',
                    'data' => $grasaData,
                    'borderColor' => 'green',
                    'fill' => false,
                    'tension' => 0.4,
                    'pointRadius' => 4,
                    'pointBackgroundColor' => 'rgb(27, 119, 44)'
                ]]
            ]
        ]));

        $poseaLabels = $cliente->poseas()->pluck('created_at')->map(fn($d) => $d->format('d/m/Y'))->values();
        $poseaData = $cliente->poseas()->pluck('valor')->values();
        $chartPoseaUrl = 'https://quickchart.io/chart?c=' . urlencode(json_encode([
            'type' => 'line',
            'data' => [
                'labels' => $poseaLabels,
                'datasets' => [[
                    'label' => '% de Masa Osea',
                    'data' => $poseaData,
                    'borderColor' => 'green',
                    'fill' => false,
                    'tension' => 0.4,
                    'pointRadius' => 4,
                    'pointBackgroundColor' => 'rgb(27, 119, 44)'
                ]]
            ]
        ]));

        $pmuscularLabels = $cliente->pmusculares()->pluck('created_at')->map(fn($d) => $d->format('d/m/Y'))->values();
        $pmuscularData = $cliente->pmusculares()->pluck('valor')->values();
        $chartPmuscularUrl = 'https://quickchart.io/chart?c=' . urlencode(json_encode([
            'type' => 'line',
            'data' => [
                'labels' => $pmuscularLabels,
                'datasets' => [[
                    'label' => '% de Masa Muscular',
                    'data' => $pmuscularData,
                    'borderColor' => 'green',
                    'fill' => false,
                    'tension' => 0.4,
                    'pointRadius' => 4,
                    'pointBackgroundColor' => 'rgb(27, 119, 44)'
                ]]
            ]
        ]));

        // // Perímetros - Calcular promedio de cada medida
        // $perimetrosLabels = $perimetros->pluck('created_at')->map(fn($d) => $d->format('d/m/Y'))->values();
        // $perimetrosPromedios = $perimetros->map(function ($p) {
        //     $medidas = array_filter([
        //         $p->cabeza,
        //         $p->brazo_relajado,
        //         $p->brazo_flexionado_tension,
        //         $p->antebrazo,
        //         $p->torax_mesoexternal,
        //         $p->cintura_minima,
        //         $p->caderas_maxima,
        //         $p->muslo_superior,
        //         $p->muslo_medial,
        //         $p->pantorrilla_maxima
        //     ], fn($v) => !is_null($v) && $v !== '');
        //     return !empty($medidas) ? array_sum($medidas) / count($medidas) : 0;
        // })->values();

        // $chartPerimetrosUrl = 'https://quickchart.io/chart?c=' . urlencode(json_encode([
        //     'type' => 'line',
        //     'data' => [
        //         'labels' => $perimetrosLabels,
        //         'datasets' => [[
        //             'label' => 'Promedio de Perímetros (cm)',
        //             'data' => $perimetrosPromedios,
        //             'borderColor' => 'orange',
        //             'fill' => false,
        //             'tension' => 0.4,
        //             'pointRadius' => 4,
        //             'pointBackgroundColor' => 'rgb(234, 88, 12)'
        //         ]]
        //     ]
        // ]));

        // Perímetros - Mostrar cada medida individualmente (todos los disponibles)
        $perimetrosLabels = $perimetros->pluck('created_at')->map(fn($d) => $d->format('d/m/Y'))->values();

        // Definir todos los perímetros disponibles con colores distintos
        $todasLasMedidas = [
            ['key' => 'cabeza', 'label' => 'Cabeza', 'color' => 'rgb(255, 99, 132)'],
            ['key' => 'brazo_relajado', 'label' => 'Brazo Relajado', 'color' => 'rgb(54, 162, 235)'],
            ['key' => 'brazo_flexionado_tension', 'label' => 'Brazo Flexionado', 'color' => 'rgb(255, 159, 64)'],
            ['key' => 'antebrazo', 'label' => 'Antebrazo', 'color' => 'rgb(75, 192, 192)'],
            ['key' => 'torax_mesoexternal', 'label' => 'Torax', 'color' => 'rgb(153, 102, 255)'],
            ['key' => 'cintura_minima', 'label' => 'Cintura', 'color' => 'rgb(255, 205, 86)'],
            ['key' => 'caderas_maxima', 'label' => 'Caderas', 'color' => 'rgb(201, 203, 207)'],
            ['key' => 'muslo_superior', 'label' => 'Muslo Superior', 'color' => 'rgb(54, 235, 162)'],
            ['key' => 'muslo_medial', 'label' => 'Muslo Medial', 'color' => 'rgb(235, 54, 162)'],
            ['key' => 'pantorrilla_maxima', 'label' => 'Pantorrilla', 'color' => 'rgb(162, 54, 235)']
        ];

        // Crear datasets para cada perímetro
        $perimetrosDatasets = [];

        foreach ($todasLasMedidas as $medida) {
            $datos = [];
            $tieneDatos = false;

            foreach ($perimetros as $p) {
                $valor = $p->{$medida['key']};
                if (!is_null($valor) && $valor !== '') {
                    $datos[] = (float)$valor;
                    $tieneDatos = true;
                } else {
                    $datos[] = null;
                }
            }

            // Solo agregar el dataset si hay al menos un dato válido
            if ($tieneDatos) {
                $perimetrosDatasets[] = [
                    'label' => $medida['label'],
                    'data' => $datos,
                    'borderColor' => $medida['color'],
                    'backgroundColor' => str_replace('rgb', 'rgba', $medida['color']) . ', 0.1)',
                    'fill' => false,
                    'tension' => 0.4,
                    'pointRadius' => 4,
                    'pointBackgroundColor' => $medida['color'],
                    'borderWidth' => 2
                ];
            }
        }

        // Si no hay datasets, crear uno vacío
        if (empty($perimetrosDatasets)) {
            $perimetrosDatasets = [[
                'label' => 'Sin datos de perímetros',
                'data' => array_fill(0, count($perimetrosLabels), 0),
                'borderColor' => 'rgba(200, 200, 200, 0.5)',
                'fill' => false,
                'borderDash' => [5, 5]
            ]];
        }

        $chartPerimetrosUrl = 'https://quickchart.io/chart?width=800&height=400&c=' . urlencode(json_encode([
            'type' => 'line',
            'data' => [
                'labels' => $perimetrosLabels,
                'datasets' => $perimetrosDatasets
            ],
            'options' => [
                'responsive' => false,
                'maintainAspectRatio' => false,
                'plugins' => [
                    'legend' => [
                        'display' => true,
                        'position' => 'top',
                        'labels' => [
                            'boxWidth' => 12,
                            'padding' => 15,
                            'font' => [
                                'size' => 10
                            ]
                        ]
                    ]
                ],
                'scales' => [
                    'y' => [
                        'beginAtZero' => false,
                        'title' => [
                            'display' => true,
                            'text' => 'Centímetros (cm)',
                            'font' => [
                                'size' => 12
                            ]
                        ],
                        'ticks' => [
                            'font' => [
                                'size' => 10
                            ]
                        ]
                    ],
                    'x' => [
                        'ticks' => [
                            'font' => [
                                'size' => 10
                            ],
                            'maxRotation' => 45,
                            'minRotation' => 45
                        ]
                    ]
                ]
            ]
        ]));

        // Renderiza la vista como PDF
        $pdf = Pdf::loadView('clientes.reporte_pdf', compact(
            'cliente',
            'pesos',
            'imcs',
            'cuotas',
            'parq',
            'fitPlan',
            'aguas',
            'grasas',
            'posea',
            'pmuscular',
            'perimetros',
            'chartPesoUrl',
            'chartimcsUrl',
            'chartAguaUrl',
            'chartGrasaUrl',
            'chartPoseaUrl',
            'chartPmuscularUrl',
            'chartPerimetrosUrl'
        ))->setOptions(['isRemoteEnabled' => true]);

        // Envía el PDF por correo
        Mail::to($cliente->email)->send(new \App\Mail\ReporteCliente($cliente, $pdf->output()));
    }

    public function ejecutarRecordatorio($slug, ClienteLifecycleService $clienteLifecycleService)
    {
        $cliente = Clientes::where('slug', $slug)->firstOrFail();
        $fechaActual = Carbon::now()->toDateString();

        // Obtener cuotas vencidas
        DB::enableQueryLog();
        $cuotasVencidas = $cliente->cuotas()
            ->where('id_estado_pago', '<>', 2)
            ->where('fecha_vencimiento', '<=', $fechaActual)
            ->get();
        //dd(DB::getQueryLog());
        if ($cuotasVencidas->isNotEmpty()) {
            // Enviar correo
            Mail::to($cliente->email)->send(new RecordatorioCuotasVencidas($cliente, $cuotasVencidas));

            $actor = Auth::user();
            if ($actor) {
                $clienteLifecycleService->notifyMorosidadReminderSent($cliente, $actor, $cuotasVencidas->count());
            }

            return back()->with('success', 'Recordatorio enviado con éxito.');
        }
        return redirect()->back()->withErrors(['error' => 'El cliente no tiene cuotas vencidas']);
    }

    public function evolucionCliente($slug)
    {
        try {
            // Opción 1: Resultados crudos
            $evolucion = Agendas::obtenerEvolucionCliente($slug);

            // Opción 2: Resultados procesados y organizados
            $evolucionProcesada = Agendas::procesarEvolucionCliente($slug);

            /*return response()->json([
                'success' => true,
                'slug' => $slug,
                'data' => $evolucionProcesada,
                'raw_data' => $evolucion // Opcional: incluir datos crudos
            ]);*/
            return $evolucion;
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener la evolución del cliente',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Función alternativa con filtros adicionales
    public function evolucionClienteFiltrada($slug, Request $request)
    {
        $ejercicioId = $request->get('ejercicio_id');
        $mes = $request->get('mes');
        $ano = $request->get('ano');

        $query = DB::table('agendas as a')
            ->selectRaw("...") // misma consulta que arriba
            ->where('a.id_cliente', $slug);

        if ($ejercicioId) {
            $query->where('ae.id_ejercicio', $ejercicioId);
        }

        if ($mes && $ano) {
            $query->whereRaw("DATE_FORMAT(a.fecha_inicio, '%Y-%m') = ?", ["{$ano}-{$mes}"]);
        } elseif ($ano) {
            $query->whereRaw("YEAR(a.fecha_inicio) = ?", [$ano]);
        }

        $resultados = $query->get();

        return response()->json([
            'success' => true,
            'data' => $resultados
        ]);
    }

    public function enviarCorreoAcceso()
    {
        $clientesConCorreo = Clientes::whereNotNull('email')->get();
        $nCorreos = 0;
        foreach ($clientesConCorreo as $cliente) {
            $nCorreos++;
            $fechaHoy = date('YmdHis');
            $hash = hash('sha256', $cliente->ci . $fechaHoy . random_int(1, 10000));
            $pwd = substr($hash, 0, 8);
            $usuario = User::where('id_cliente', $cliente->id)->first();
            $usuario->password = bcrypt($pwd);
            if ($cliente->email != $usuario->email) {
                $usuario->email = $cliente->email;
            }
            $usuario->save();
            Mail::to($cliente->email)->send(new BienvenidaClienteMail($cliente, $pwd, true, ClienteLifecycleService::APP_DOWNLOAD_URL));
        }

        echo $nCorreos . " Correos enviados correctamente.";
    }

    public function traeDupla($id)
    {
        $cliente = Clientes::join('clientes as cd', 'clientes.id', 'cd.id_cliente_duo')
            ->where('clientes.id', $id)
            ->select('cd.id', 'cd.nombres', 'cd.paterno', 'cd.materno')
            ->first();

        if ($cliente) {
            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $cliente->id,
                    'nombres' => $cliente->nombres,
                    'paterno' => $cliente->paterno,
                    'materno' => $cliente->materno,
                ]
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Cliente no encontrado'
            ], 404);
        }
    }

    public function evolucionEjercicios($slug)
    {
        $cliente = Clientes::with('plan')->where('slug', $slug)->firstOrFail();

        // Obtener todas las agendas del cliente con sus ejercicios
        $agendas = Agendas::where('id_cliente', $cliente->id)
            ->where('estado', 4)
            ->with(['ejercicios' => function ($query) {
                $query->orderBy('nombre', 'asc');
            }])
            ->orderBy('fecha_inicio', 'asc')
            ->get();

        // Agrupar datos por ejercicio
        $datosEjercicios = [];

        foreach ($agendas as $agenda) {
            foreach ($agenda->ejercicios as $ejercicio) {
                $carga = $ejercicio->pivot->carga;

                if (!isset($datosEjercicios[$ejercicio->nombre])) {
                    $datosEjercicios[$ejercicio->nombre] = [
                        'id_ejercicio' => $ejercicio->id,
                        'fechas' => [],
                        'cargas' => [],
                        'repeticiones' => [],
                        'series' => [],
                        'metodos' => [],
                        'progresiones' => [],
                    ];
                }

                // Evitar duplicados usando fecha como clave
                $fecha = $agenda->fecha_inicio->format('Y-m-d');

                if (!in_array($fecha, $datosEjercicios[$ejercicio->nombre]['fechas'])) {
                    $datosEjercicios[$ejercicio->nombre]['fechas'][] = $fecha;
                    $datosEjercicios[$ejercicio->nombre]['cargas'][] = (float)$carga ?? 0;
                    $datosEjercicios[$ejercicio->nombre]['repeticiones'][] = $ejercicio->pivot->repeticiones ?? 0;
                    $datosEjercicios[$ejercicio->nombre]['series'][] = $ejercicio->pivot->serie ?? 0;

                    // Obtener nombres de métodos y progresiones
                    $metodo = \App\Models\Metodos::find($ejercicio->pivot->metodo);
                    $datosEjercicios[$ejercicio->nombre]['metodos'][] = $metodo->nombre ?? 'N/A';

                    $progresionNombre = match ($ejercicio->pivot->progresion) {
                        1 => 'Lineal',
                        2 => 'Doble',
                        3 => 'Ondulante',
                        default => 'N/A'
                    };
                    $datosEjercicios[$ejercicio->nombre]['progresiones'][] = $progresionNombre;
                }
            }
        }

        // Ordenar por nombre de ejercicio
        ksort($datosEjercicios);

        return view('clientes.evolucion_ejercicios', compact('cliente', 'datosEjercicios'));
    }

    public function evolucionEjerciciosPdf($slug)
    {
        $cliente = Clientes::where('slug', $slug)->firstOrFail();

        // Reutilizar la lógica para obtener los datos agrupados por ejercicio
        $agendas = Agendas::where('id_cliente', $cliente->id)
            ->where('estado', 4)
            ->with(['ejercicios' => function ($query) {
                $query->orderBy('nombre', 'asc');
            }])
            ->orderBy('fecha_inicio', 'asc')
            ->get();

        $datosEjercicios = [];
        foreach ($agendas as $agenda) {
            foreach ($agenda->ejercicios as $ejercicio) {
                $carga = $ejercicio->pivot->carga;
                if (!isset($datosEjercicios[$ejercicio->nombre])) {
                    $datosEjercicios[$ejercicio->nombre] = [
                        'id_ejercicio' => $ejercicio->id,
                        'fechas' => [],
                        'cargas' => [],
                        'repeticiones' => [],
                    ];
                }
                $fecha = $agenda->fecha_inicio->format('Y-m-d');
                if (!in_array($fecha, $datosEjercicios[$ejercicio->nombre]['fechas'])) {
                    $datosEjercicios[$ejercicio->nombre]['fechas'][] = $fecha;
                    $datosEjercicios[$ejercicio->nombre]['cargas'][] = (float)$carga ?? 0;
                    $datosEjercicios[$ejercicio->nombre]['repeticiones'][] = $ejercicio->pivot->repeticiones ?? 0;
                }
            }
        }

        // Generar URLs de QuickChart para cada ejercicio (solo gráficos, sin tablas)
        $charts = [];
        foreach ($datosEjercicios as $nombre => $datos) {
            $labels = array_map(function ($d) {
                return \Carbon\Carbon::createFromFormat('Y-m-d', $d)->format('d/m/Y');
            }, $datos['fechas']);

            $configCarga = [
                'type' => 'line',
                'data' => [
                    'labels' => $labels,
                    'datasets' => [[
                        'label' => 'Carga (kg)',
                        'data' => $datos['cargas'],
                        'borderColor' => 'rgba(59,130,246,1)',
                        'backgroundColor' => 'rgba(59,130,246,0.1)',
                        'fill' => false,
                        'tension' => 0.4,
                    ]]
                ],
                'options' => [
                    'plugins' => ['legend' => ['display' => false]],
                    'scales' => ['y' => ['beginAtZero' => true]]
                ]
            ];

            $configReps = [
                'type' => 'bar',
                'data' => [
                    'labels' => $labels,
                    'datasets' => [[
                        'label' => 'Repeticiones',
                        'data' => $datos['repeticiones'],
                        'backgroundColor' => 'rgba(16,185,129,0.8)',
                        'borderColor' => 'rgba(16,185,129,1)',
                        'borderWidth' => 1
                    ]]
                ],
                'options' => [
                    'plugins' => ['legend' => ['display' => false]],
                    'scales' => ['y' => ['beginAtZero' => true]]
                ]
            ];

            $chartCargaUrl = 'https://quickchart.io/chart?width=800&height=400&c=' . urlencode(json_encode($configCarga));
            //$chartRepsUrl = 'https://quickchart.io/chart?width=800&height=400&c=' . urlencode(json_encode($configReps));

            $charts[] = [
                'nombre' => $nombre,
                'carga_url' => $chartCargaUrl,
                //'reps_url' => $chartRepsUrl,
            ];
        }

        // Ordenar por nombre
        usort($charts, function ($a, $b) {
            return strcmp($a['nombre'], $b['nombre']);
        });

        $pdf = Pdf::loadView('clientes.evolucion_ejercicios_pdf', compact('cliente', 'charts'))
            ->setOptions(['isRemoteEnabled' => true]);

        $filename = 'evolucion_ejercicios_' . $cliente->slug . '.pdf';
        //return $pdf->download($filename);
        return $pdf->stream($filename);
    }
}
