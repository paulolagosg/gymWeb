<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ClaseReserva;
use App\Models\Clases;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ClasesController extends Controller
{
    private function requireAdminOrEntrenador(Request $request): ?JsonResponse
    {
        $user = $request->user();

        if (! $user || ! in_array((int) $user->id_tipo_usuario, [1, 2, 10], true)) {
            return response()->json(['message' => 'No autorizado.'], 403);
        }

        return null;
    }

    private function resolveClientePorUsuario(object $user): ?object
    {
        if (isset($user->id_cliente) && $user->id_cliente) {
            $cliente = DB::table('clientes')->where('id', (int) $user->id_cliente)->first();
            if ($cliente) {
                return $cliente;
            }
        }

        return DB::table('clientes')->where('id_usuario', $user->id)->first();
    }

    private function resolveIdGimnasio(Request $request): int
    {
        $user = $request->user();
        $esSuperAdmin = (int) $user->id_tipo_usuario === 10;

        return $esSuperAdmin
            ? max(0, (int) $request->query('id_gimnasio', 0))
            : (int) ($user->id_gimnasio ?? 0);
    }

    private function serializeClase(Clases $clase, ?int $idClienteActual = null): array
    {
        $ocupados = $clase->reservasActivas()->count();

        $data = [
            'id' => $clase->id,
            'nombre' => $clase->nombre,
            'descripcion' => $clase->descripcion,
            'fecha_inicio' => optional($clase->fecha_inicio)->toIso8601String(),
            'fecha_fin' => optional($clase->fecha_fin)->toIso8601String(),
            'aforo' => $clase->aforo,
            'cupos_ocupados' => $ocupados,
            'cupos_disponibles' => max(0, $clase->aforo - $ocupados),
            'estado' => $clase->estado,
            'instructor_nombre' => $clase->instructor?->name,
            'id_clase_origen' => $clase->id_clase_origen,
        ];

        if ($idClienteActual !== null) {
            $reserva = ClaseReserva::where('id_clase', $clase->id)
                ->where('id_cliente', $idClienteActual)
                ->where('estado', ClaseReserva::ESTADO_RESERVADA)
                ->first();
            $data['mi_reserva'] = $reserva ? ['id' => $reserva->id] : null;
        }

        return $data;
    }

    // ===================================================================
    // ADMIN / ENTRENADOR
    // ===================================================================

    public function adminIndex(Request $request): JsonResponse
    {
        if ($err = $this->requireAdminOrEntrenador($request)) {
            return $err;
        }

        $idGimnasio = $this->resolveIdGimnasio($request);

        $query = Clases::query()->with('instructor')->orderBy('fecha_inicio');

        if ($idGimnasio > 0) {
            $query->where('id_gimnasio', $idGimnasio);
        } elseif ((int) $request->user()->id_tipo_usuario !== 10) {
            $query->whereRaw('1 = 0');
        }

        if ($request->filled('desde')) {
            $query->where('fecha_fin', '>=', Carbon::parse($request->query('desde'))->startOfDay());
        }
        if ($request->filled('hasta')) {
            $query->where('fecha_inicio', '<=', Carbon::parse($request->query('hasta'))->endOfDay());
        }
        if ($request->filled('id_usuario')) {
            $query->where('id_usuario', (int) $request->query('id_usuario'));
        }

        $clases = $query->get()->map(fn (Clases $clase) => $this->serializeClase($clase))->values();

        return response()->json(['clases' => $clases]);
    }

    public function adminStore(Request $request): JsonResponse
    {
        if ($err = $this->requireAdminOrEntrenador($request)) {
            return $err;
        }

        $user = $request->user();
        $idGimnasio = (int) ($user->id_gimnasio ?? 0);
        if ((int) $user->id_tipo_usuario === 10) {
            $idGimnasio = (int) $request->input('id_gimnasio', 0);
        }
        if ($idGimnasio <= 0) {
            return response()->json(['message' => 'Falta indicar el gimnasio.'], 422);
        }

        $validated = $request->validate([
            'id_usuario' => 'required|integer|exists:users,id',
            'nombre' => 'required|string|max:150',
            'descripcion' => 'nullable|string',
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date|after:fecha_inicio',
            'aforo' => 'required|integer|min:1',
            'dias_semana' => 'nullable|array',
            'dias_semana.*' => 'integer|min:0|max:6',
            'semanas' => 'nullable|integer|min:1|max:52',
        ]);

        $instructorValido = User::where('id', $validated['id_usuario'])
            ->where('id_tipo_usuario', 2)
            ->where('id_gimnasio', $idGimnasio)
            ->exists();
        if (! $instructorValido) {
            return response()->json(['message' => 'El instructor no pertenece a este gimnasio.'], 422);
        }

        $fechaInicio = Carbon::parse($validated['fecha_inicio']);
        $fechaFin = Carbon::parse($validated['fecha_fin']);
        $duracionSegundos = $fechaFin->getTimestamp() - $fechaInicio->getTimestamp();

        $diasSemana = $validated['dias_semana'] ?? [];
        if (! in_array($fechaInicio->dayOfWeek, $diasSemana, true)) {
            $diasSemana[] = $fechaInicio->dayOfWeek;
        }
        $semanas = (int) ($validated['semanas'] ?? 1);

        $ocurrencias = [];
        for ($semana = 0; $semana < $semanas; $semana++) {
            foreach ($diasSemana as $diaSemana) {
                $inicioSemana = $fechaInicio->copy()->addWeeks($semana)->startOfWeek(Carbon::SUNDAY);
                $fecha = $inicioSemana->copy()->addDays((int) $diaSemana)->setTimeFrom($fechaInicio);

                if ($fecha->lt($fechaInicio)) {
                    continue; // no generar ocurrencias antes de la fecha/hora de inicio elegida
                }

                $ocurrencias[] = $fecha;
            }
        }

        if (empty($ocurrencias)) {
            $ocurrencias[] = $fechaInicio;
        }

        usort($ocurrencias, fn (Carbon $a, Carbon $b) => $a <=> $b);

        $clasesCreadas = DB::transaction(function () use ($ocurrencias, $duracionSegundos, $validated, $idGimnasio) {
            $idOrigen = null;
            $creadas = [];

            foreach ($ocurrencias as $fecha) {
                $clase = Clases::create([
                    'id_gimnasio' => $idGimnasio,
                    'id_usuario' => $validated['id_usuario'],
                    'nombre' => $validated['nombre'],
                    'descripcion' => $validated['descripcion'] ?? null,
                    'fecha_inicio' => $fecha,
                    'fecha_fin' => $fecha->copy()->addSeconds($duracionSegundos),
                    'aforo' => $validated['aforo'],
                    'estado' => Clases::ESTADO_ACTIVA,
                    'id_clase_origen' => $idOrigen,
                ]);

                $idOrigen ??= $clase->id;
                $creadas[] = $clase;
            }

            return $creadas;
        });

        return response()->json([
            'message' => count($clasesCreadas) > 1
                ? 'Se crearon ' . count($clasesCreadas) . ' clases.'
                : 'Clase creada.',
            'clases' => collect($clasesCreadas)->map(fn (Clases $clase) => $this->serializeClase($clase))->values(),
        ], 201);
    }

    public function adminCancelar(Request $request, int $id): JsonResponse
    {
        if ($err = $this->requireAdminOrEntrenador($request)) {
            return $err;
        }

        $clase = Clases::find($id);
        if (! $clase) {
            return response()->json(['message' => 'Clase no encontrada.'], 404);
        }

        $cancelarFuturas = $request->boolean('futuras');

        if ($cancelarFuturas) {
            $idOrigen = $clase->id_clase_origen ?? $clase->id;
            Clases::where(function (Builder $query) use ($idOrigen) {
                $query->where('id', $idOrigen)->orWhere('id_clase_origen', $idOrigen);
            })
                ->where('fecha_inicio', '>=', $clase->fecha_inicio)
                ->update(['estado' => Clases::ESTADO_CANCELADA]);
        } else {
            $clase->update(['estado' => Clases::ESTADO_CANCELADA]);
        }

        return response()->json(['message' => 'Clase cancelada.']);
    }

    public function adminRoster(Request $request, int $id): JsonResponse
    {
        if ($err = $this->requireAdminOrEntrenador($request)) {
            return $err;
        }

        $clase = Clases::find($id);
        if (! $clase) {
            return response()->json(['message' => 'Clase no encontrada.'], 404);
        }

        $reservas = ClaseReserva::with('cliente:id,nombres,paterno,materno,slug')
            ->where('id_clase', $id)
            ->where('estado', ClaseReserva::ESTADO_RESERVADA)
            ->get()
            ->map(fn (ClaseReserva $reserva) => [
                'id' => $reserva->id,
                'id_cliente' => $reserva->id_cliente,
                'cliente_nombre' => trim(implode(' ', array_filter([
                    $reserva->cliente?->nombres,
                    $reserva->cliente?->paterno,
                    $reserva->cliente?->materno,
                ]))),
                'cliente_slug' => $reserva->cliente?->slug,
            ])
            ->values();

        return response()->json([
            'clase' => $this->serializeClase($clase),
            'reservas' => $reservas,
        ]);
    }

    public function adminReservarManual(Request $request, int $id): JsonResponse
    {
        if ($err = $this->requireAdminOrEntrenador($request)) {
            return $err;
        }

        $validated = $request->validate([
            'id_cliente' => 'required|integer|exists:clientes,id',
        ]);

        try {
            $this->reservarCupo($id, (int) $validated['id_cliente'], (int) $request->user()->id);
        } catch (ValidationException $e) {
            return response()->json(['message' => collect($e->errors())->flatten()->first()], 422);
        }

        return response()->json(['message' => 'Reserva registrada.']);
    }

    // ===================================================================
    // CLIENTE
    // ===================================================================

    public function clienteIndex(Request $request): JsonResponse
    {
        $cliente = $this->resolveClientePorUsuario($request->user());
        if (! $cliente) {
            return response()->json(['message' => 'Perfil de cliente no encontrado.'], 404);
        }

        $query = Clases::query()
            ->with('instructor')
            ->where('id_gimnasio', $cliente->id_gimnasio)
            ->where('estado', Clases::ESTADO_ACTIVA)
            ->orderBy('fecha_inicio');

        $desde = $request->filled('desde') ? Carbon::parse($request->query('desde')) : Carbon::today();
        $hasta = $request->filled('hasta') ? Carbon::parse($request->query('hasta')) : $desde->copy()->addDays(13);

        $query->where('fecha_fin', '>=', $desde->copy()->startOfDay())
            ->where('fecha_inicio', '<=', $hasta->copy()->endOfDay());

        $clases = $query->get()
            ->map(fn (Clases $clase) => $this->serializeClase($clase, (int) $cliente->id))
            ->values();

        return response()->json(['clases' => $clases]);
    }

    public function clienteReservar(Request $request, int $id): JsonResponse
    {
        $cliente = $this->resolveClientePorUsuario($request->user());
        if (! $cliente) {
            return response()->json(['message' => 'Perfil de cliente no encontrado.'], 404);
        }

        try {
            $this->reservarCupo($id, (int) $cliente->id, (int) $request->user()->id);
        } catch (ValidationException $e) {
            return response()->json(['message' => collect($e->errors())->flatten()->first()], 422);
        }

        return response()->json(['message' => 'Reserva confirmada.']);
    }

    public function clienteCancelarReserva(Request $request, int $id): JsonResponse
    {
        $cliente = $this->resolveClientePorUsuario($request->user());
        if (! $cliente) {
            return response()->json(['message' => 'Perfil de cliente no encontrado.'], 404);
        }

        $reserva = ClaseReserva::where('id_clase', $id)
            ->where('id_cliente', $cliente->id)
            ->where('estado', ClaseReserva::ESTADO_RESERVADA)
            ->first();

        if (! $reserva) {
            return response()->json(['message' => 'No tienes una reserva activa en esta clase.'], 422);
        }

        $reserva->update(['estado' => ClaseReserva::ESTADO_CANCELADA]);

        return response()->json(['message' => 'Reserva cancelada.']);
    }

    // ===================================================================
    // Núcleo compartido — reservar un cupo, protegido contra condición de carrera
    // ===================================================================

    private function reservarCupo(int $idClase, int $idCliente, ?int $creadoPor): ClaseReserva
    {
        return DB::transaction(function () use ($idClase, $idCliente, $creadoPor) {
            /** @var Clases|null $clase */
            $clase = Clases::where('id', $idClase)->lockForUpdate()->first();

            if (! $clase) {
                throw ValidationException::withMessages(['clase' => 'Clase no encontrada.']);
            }

            if ($clase->estado !== Clases::ESTADO_ACTIVA) {
                throw ValidationException::withMessages(['clase' => 'Esta clase fue cancelada.']);
            }

            $existente = ClaseReserva::where('id_clase', $idClase)
                ->where('id_cliente', $idCliente)
                ->first();

            if ($existente && $existente->estado === ClaseReserva::ESTADO_RESERVADA) {
                throw ValidationException::withMessages(['clase' => 'Ya tienes una reserva en esta clase.']);
            }

            $ocupados = ClaseReserva::where('id_clase', $idClase)
                ->where('estado', ClaseReserva::ESTADO_RESERVADA)
                ->count();

            if ($ocupados >= $clase->aforo) {
                throw ValidationException::withMessages(['clase' => 'No quedan cupos disponibles.']);
            }

            if ($existente) {
                $existente->update(['estado' => ClaseReserva::ESTADO_RESERVADA, 'creado_por' => $creadoPor]);
                return $existente;
            }

            return ClaseReserva::create([
                'id_clase' => $idClase,
                'id_cliente' => $idCliente,
                'id_gimnasio' => $clase->id_gimnasio,
                'estado' => ClaseReserva::ESTADO_RESERVADA,
                'creado_por' => $creadoPor,
            ]);
        });
    }
}
