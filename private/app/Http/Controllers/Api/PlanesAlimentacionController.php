<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PlanAlimentacion;
use App\Models\PlanAlimentacionComida;
use App\Models\PlanAlimentacionDia;
use App\Models\PlanAlimentacionRegistro;
use App\Models\PlanAlimentacionVersion;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class PlanesAlimentacionController extends Controller
{
    private const DIAS_SEMANA = [
        1 => 'Lunes',
        2 => 'Martes',
        3 => 'Miercoles',
        4 => 'Jueves',
        5 => 'Viernes',
        6 => 'Sabado',
        7 => 'Domingo',
    ];

    private const COMIDAS = [
        'desayuno' => ['nombre' => 'Desayuno', 'orden' => 1],
        'colacion_am' => ['nombre' => 'Colacion AM', 'orden' => 2],
        'almuerzo' => ['nombre' => 'Almuerzo', 'orden' => 3],
        'colacion_pm' => ['nombre' => 'Colacion PM', 'orden' => 4],
        'post_entreno' => ['nombre' => 'Post-entreno', 'orden' => 5],
        'cena' => ['nombre' => 'Cena', 'orden' => 6],
    ];

    private function requireAdminOrEntrenador(Request $request): ?JsonResponse
    {
        $user = $request->user();

        if (! $user || ! in_array((int) $user->id_tipo_usuario, [1, 2, 10], true)) {
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

    private function applyPlanScope(Builder $query, object $user): Builder
    {
        $tipoUsuario = (int) $user->id_tipo_usuario;

        if ($tipoUsuario === 2) {
            $clienteIds = $this->getScopedClienteIdsForUser($user);

            if (empty($clienteIds)) {
                return $query->whereRaw('1 = 0');
            }

            return $query->whereIn('planes_alimentacion.id_cliente', $clienteIds);
        }

        if ($tipoUsuario === 1 && ! empty($user->id_gimnasio)) {
            return $query->whereHas('cliente', fn(Builder $clienteQuery) => $clienteQuery->where('id_gimnasio', (int) $user->id_gimnasio));
        }

        return $query;
    }

    private function findScopedCliente(Request $request, string $identifier): ?object
    {
        $identifier = trim($identifier);

        if ($identifier === '' || in_array($identifier, ['undefined', 'null'], true)) {
            return null;
        }

        $query = DB::table('clientes');

        if ((int) $request->user()->id_tipo_usuario === 2) {
            $clienteIds = $this->getScopedClienteIdsForUser($request->user());

            if (empty($clienteIds)) {
                return null;
            }

            $query->whereIn('clientes.id', $clienteIds);
        } elseif ((int) $request->user()->id_tipo_usuario === 1 && ! empty($request->user()->id_gimnasio)) {
            $query->where('clientes.id_gimnasio', (int) $request->user()->id_gimnasio);
        }

        $query->where(function ($clienteQuery) use ($identifier) {
            $clienteQuery->where('clientes.slug', $identifier);

            if (ctype_digit($identifier)) {
                $clienteQuery->orWhere('clientes.id', (int) $identifier);
            }
        });

        return $query->first();
    }

    private function findScopedPlan(Request $request, int $planId): ?PlanAlimentacion
    {
        $query = PlanAlimentacion::query()
            ->with([
                'cliente:id,slug,nombres,paterno,materno,ci',
                'dias.comidas',
                'versiones.usuario:id,name',
            ]);

        $this->applyPlanScope($query, $request->user());

        return $query->where('planes_alimentacion.id', $planId)->first();
    }

    private function resolveClientePorUsuario(object $user): ?object
    {
        // Primero intenta por id_cliente del usuario (asociación correcta para clientes)
        if (isset($user->id_cliente) && $user->id_cliente) {
            $cliente = DB::table('clientes')->where('id', (int) $user->id_cliente)->first();
            if ($cliente) {
                return $cliente;
            }
        }

        // Luego intenta por id_usuario (para compatibilidad con otros roles)
        $cliente = DB::table('clientes')->where('id_usuario', $user->id)->first();

        return $cliente;
    }

    private function validatePlanPayload(Request $request): array
    {
        return $request->validate([
            'id_cliente' => 'required|integer|exists:clientes,id',
            'nombre' => 'required|string|max:255',
            'objetivo_nutricional' => 'nullable|string',
            'fecha_desde' => 'nullable|date',
            'fecha_hasta' => 'nullable|date|after_or_equal:fecha_desde',
            'alimentos_sustitucion' => 'nullable|string',
            'notas_generales' => 'nullable|string',
            'notas_internas' => 'nullable|string',
            'estado' => ['required', 'string', Rule::in(['borrador', 'activo', 'archivado'])],
            'descripcion_cambio' => 'nullable|string|max:255',
            'dias' => 'required|array|min:1',
            'dias.*.dia_semana' => 'required|integer|min:1|max:7',
            'dias.*.nombre_dia' => 'required|string|max:40',
            'dias.*.orden' => 'nullable|integer|min:1|max:7',
            'dias.*.observaciones' => 'nullable|string',
            'dias.*.comidas' => 'required|array|min:1',
            'dias.*.comidas.*.codigo_comida' => ['required', 'string', Rule::in(array_keys(self::COMIDAS))],
            'dias.*.comidas.*.nombre_comida' => 'required|string|max:100',
            'dias.*.comidas.*.orden' => 'nullable|integer|min:1|max:10',
            'dias.*.comidas.*.items' => 'nullable|array',
            'dias.*.comidas.*.items.*.nombre' => 'required|string|max:150',
            'dias.*.comidas.*.items.*.cantidad' => 'nullable|string|max:120',
            'dias.*.comidas.*.items.*.proteinas' => 'nullable|numeric|min:0|max:9999',
            'dias.*.comidas.*.items.*.carbohidratos' => 'nullable|numeric|min:0|max:9999',
            'dias.*.comidas.*.items.*.grasas' => 'nullable|numeric|min:0|max:9999',
            'dias.*.comidas.*.reemplazos' => 'nullable|string',
            'dias.*.comidas.*.observaciones' => 'nullable|string',
        ]);
    }

    private function normalizeItems(array $items): array
    {
        return collect($items)
            ->map(function (array $item) {
                $nombre = trim((string) ($item['nombre'] ?? ''));
                $cantidad = trim((string) ($item['cantidad'] ?? ''));

                if ($nombre === '' && $cantidad === '') {
                    return null;
                }

                return [
                    'nombre' => $nombre,
                    'cantidad' => $cantidad !== '' ? $cantidad : null,
                    'proteinas' => $item['proteinas'] !== null && $item['proteinas'] !== '' ? (float) $item['proteinas'] : null,
                    'carbohidratos' => $item['carbohidratos'] !== null && $item['carbohidratos'] !== '' ? (float) $item['carbohidratos'] : null,
                    'grasas' => $item['grasas'] !== null && $item['grasas'] !== '' ? (float) $item['grasas'] : null,
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    private function syncDiasYComidas(PlanAlimentacion $plan, array $diasPayload): void
    {
        $plan->dias()->delete();

        $dias = collect($diasPayload)
            ->sortBy(fn(array $dia) => [
                (int) ($dia['orden'] ?? $dia['dia_semana']),
                (int) $dia['dia_semana'],
            ])
            ->values();

        foreach ($dias as $diaPayload) {
            $dia = $plan->dias()->create([
                'dia_semana' => (int) $diaPayload['dia_semana'],
                'nombre_dia' => $diaPayload['nombre_dia'] ?: (self::DIAS_SEMANA[(int) $diaPayload['dia_semana']] ?? 'Dia'),
                'orden' => (int) ($diaPayload['orden'] ?? $diaPayload['dia_semana']),
                'observaciones' => $diaPayload['observaciones'] ?? null,
            ]);

            $comidas = collect($diaPayload['comidas'] ?? [])
                ->sortBy(fn(array $comida) => [
                    (int) ($comida['orden'] ?? (self::COMIDAS[$comida['codigo_comida']]['orden'] ?? 99)),
                    (string) $comida['codigo_comida'],
                ])
                ->values();

            foreach ($comidas as $comidaPayload) {
                $configComida = self::COMIDAS[$comidaPayload['codigo_comida']] ?? ['nombre' => 'Comida', 'orden' => 99];

                $dia->comidas()->create([
                    'codigo_comida' => $comidaPayload['codigo_comida'],
                    'nombre_comida' => $comidaPayload['nombre_comida'] ?: $configComida['nombre'],
                    'orden' => (int) ($comidaPayload['orden'] ?? $configComida['orden']),
                    'items' => $this->normalizeItems($comidaPayload['items'] ?? []),
                    'reemplazos' => $comidaPayload['reemplazos'] ?? null,
                    'observaciones' => $comidaPayload['observaciones'] ?? null,
                ]);
            }
        }
    }

    private function archiveActivePlansForCliente(int $clienteId, ?int $exceptId = null): void
    {
        $query = PlanAlimentacion::query()
            ->where('id_cliente', $clienteId)
            ->where('estado', 'activo');

        if ($exceptId) {
            $query->where('id', '!=', $exceptId);
        }

        $query->update([
            'estado' => 'archivado',
            'updated_at' => now(),
        ]);
    }

    private function formatDate($value): ?string
    {
        if (! $value) {
            return null;
        }

        return $value instanceof Carbon
            ? $value->format('Y-m-d')
            : Carbon::parse($value)->format('Y-m-d');
    }

    private function serializeVersion(PlanAlimentacionVersion $version): array
    {
        return [
            'id' => (int) $version->id,
            'version' => (int) $version->version,
            'descripcion_cambio' => $version->descripcion_cambio,
            'created_at' => optional($version->created_at)?->toISOString(),
            'usuario' => $version->usuario?->name,
        ];
    }

    private function normalizeLookupValue(?string $value): string
    {
        return (string) Str::of((string) $value)
            ->ascii()
            ->lower()
            ->replaceMatches('/[^a-z0-9]+/', ' ')
            ->trim();
    }

    private function resolveDiaSemana(?int $diaSemana, ?int $orden, ?string $nombreDia): int
    {
        if ($diaSemana !== null && isset(self::DIAS_SEMANA[$diaSemana])) {
            return $diaSemana;
        }

        if ($orden !== null && isset(self::DIAS_SEMANA[$orden])) {
            return $orden;
        }

        $normalizedName = $this->normalizeLookupValue($nombreDia);

        foreach (self::DIAS_SEMANA as $index => $label) {
            if ($normalizedName !== '' && $normalizedName === $this->normalizeLookupValue($label)) {
                return $index;
            }
        }

        return 1;
    }

    private function resolveCodigoComida(?string $codigoComida, ?int $orden, ?string $nombreComida): string
    {
        $codigoComida = (string) $codigoComida;

        if (isset(self::COMIDAS[$codigoComida])) {
            return $codigoComida;
        }

        if ($orden !== null) {
            foreach (self::COMIDAS as $code => $config) {
                if ((int) $config['orden'] === $orden) {
                    return $code;
                }
            }
        }

        $normalizedName = $this->normalizeLookupValue($nombreComida ?: $codigoComida);

        foreach (self::COMIDAS as $code => $config) {
            if ($normalizedName !== '' && $normalizedName === $this->normalizeLookupValue((string) $config['nombre'])) {
                return $code;
            }
        }

        return 'desayuno';
    }

    private function normalizePlanComidaPayload(array $comida): array
    {
        $codigoComida = $this->resolveCodigoComida(
            $comida['codigo_comida'] ?? null,
            isset($comida['orden']) ? (int) $comida['orden'] : null,
            $comida['nombre_comida'] ?? null,
        );
        $configComida = self::COMIDAS[$codigoComida];

        return [
            'id' => isset($comida['id']) ? (int) $comida['id'] : null,
            'codigo_comida' => $codigoComida,
            'nombre_comida' => (string) ($comida['nombre_comida'] ?? $configComida['nombre']),
            'orden' => (int) $configComida['orden'],
            'items' => collect($comida['items'] ?? [])
                ->filter(fn($item) => is_array($item))
                ->values()
                ->map(fn(array $item) => [
                    'nombre' => (string) ($item['nombre'] ?? ''),
                    'cantidad' => $item['cantidad'] ?? null,
                    'proteinas' => $item['proteinas'] ?? null,
                    'carbohidratos' => $item['carbohidratos'] ?? null,
                    'grasas' => $item['grasas'] ?? null,
                ])
                ->all(),
            'reemplazos' => $comida['reemplazos'] ?? null,
            'observaciones' => $comida['observaciones'] ?? null,
        ];
    }

    private function normalizePlanDiaPayload(array $dia): array
    {
        $diaSemana = $this->resolveDiaSemana(
            isset($dia['dia_semana']) ? (int) $dia['dia_semana'] : null,
            isset($dia['orden']) ? (int) $dia['orden'] : null,
            $dia['nombre_dia'] ?? null,
        );

        return [
            'id' => isset($dia['id']) ? (int) $dia['id'] : null,
            'dia_semana' => $diaSemana,
            'nombre_dia' => (string) ($dia['nombre_dia'] ?? self::DIAS_SEMANA[$diaSemana]),
            'orden' => $diaSemana,
            'observaciones' => $dia['observaciones'] ?? null,
            'comidas' => collect($dia['comidas'] ?? [])
                ->filter(fn($comida) => is_array($comida))
                ->map(fn(array $comida) => $this->normalizePlanComidaPayload($comida))
                ->sortBy(fn(array $comida) => [(int) $comida['orden'], (string) $comida['codigo_comida']])
                ->values()
                ->all(),
        ];
    }

    private function serializeDia(PlanAlimentacionDia $dia): array
    {
        $dia->loadMissing('comidas');

        return $this->normalizePlanDiaPayload([
            'id' => (int) $dia->id,
            'dia_semana' => (int) $dia->dia_semana,
            'nombre_dia' => $dia->nombre_dia,
            'orden' => (int) $dia->orden,
            'observaciones' => $dia->observaciones,
            'comidas' => $dia->comidas
                ->sortBy(fn(PlanAlimentacionComida $comida) => [(int) $comida->orden, (string) $comida->codigo_comida])
                ->values()
                ->map(fn(PlanAlimentacionComida $comida) => [
                    'id' => (int) $comida->id,
                    'codigo_comida' => $comida->codigo_comida,
                    'nombre_comida' => $comida->nombre_comida,
                    'orden' => (int) $comida->orden,
                    'items' => collect($comida->items ?? [])
                        ->filter(fn($item) => is_array($item))
                        ->map(fn(array $item) => [
                            'nombre' => $item['nombre'] ?? '',
                            'cantidad' => $item['cantidad'] ?? null,
                            'proteinas' => $item['proteinas'] ?? null,
                            'carbohidratos' => $item['carbohidratos'] ?? null,
                            'grasas' => $item['grasas'] ?? null,
                        ])->values(),
                    'reemplazos' => $comida->reemplazos,
                    'observaciones' => $comida->observaciones,
                ])
                ->all(),
        ]);
    }

    private function diasHaveContent(array $dias): bool
    {
        foreach ($dias as $dia) {
            foreach (($dia['comidas'] ?? []) as $comida) {
                foreach (($comida['items'] ?? []) as $item) {
                    $nombre = trim((string) ($item['nombre'] ?? ''));
                    $cantidad = trim((string) ($item['cantidad'] ?? ''));

                    if (
                        $nombre !== ''
                        || $cantidad !== ''
                        || (($item['proteinas'] ?? null) !== null && ($item['proteinas'] ?? '') !== '')
                        || (($item['carbohidratos'] ?? null) !== null && ($item['carbohidratos'] ?? '') !== '')
                        || (($item['grasas'] ?? null) !== null && ($item['grasas'] ?? '') !== '')
                    ) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    private function normalizeSnapshotDias(array $dias): array
    {
        return collect($dias)
            ->filter(fn($dia) => is_array($dia))
            ->map(fn(array $dia) => $this->normalizePlanDiaPayload($dia))
            ->sortBy(fn(array $dia) => [(int) $dia['orden'], (int) $dia['dia_semana']])
            ->values()
            ->all();
    }

    private function resolveEffectivePlanDias(PlanAlimentacion $plan): array
    {
        $currentDias = $plan->dias
            ->sortBy(fn(PlanAlimentacionDia $dia) => [(int) $dia->orden, (int) $dia->dia_semana])
            ->values()
            ->map(fn(PlanAlimentacionDia $dia) => $this->serializeDia($dia))
            ->all();

        if ($this->diasHaveContent($currentDias)) {
            return $currentDias;
        }

        $plan->loadMissing('versiones');

        foreach ($plan->versiones->sortByDesc('version') as $version) {
            $snapshotDias = $this->normalizeSnapshotDias($version->snapshot['dias'] ?? []);

            if ($this->diasHaveContent($snapshotDias)) {
                return $snapshotDias;
            }
        }

        return $currentDias;
    }

    private function buildPlanSnapshot(PlanAlimentacion $plan): array
    {
        $plan->loadMissing([
            'cliente:id,slug,nombres,paterno,materno,ci',
            'dias.comidas',
        ]);

        return [
            'id' => (int) $plan->id,
            'id_cliente' => (int) $plan->id_cliente,
            'cliente' => [
                'id' => (int) $plan->cliente?->id,
                'slug' => $plan->cliente?->slug,
                'nombre_completo' => trim(implode(' ', array_filter([
                    $plan->cliente?->nombres,
                    $plan->cliente?->paterno,
                    $plan->cliente?->materno,
                ]))),
                'ci' => $plan->cliente?->ci,
            ],
            'id_plan_origen' => $plan->id_plan_origen ? (int) $plan->id_plan_origen : null,
            'nombre' => $plan->nombre,
            'objetivo_nutricional' => $plan->objetivo_nutricional,
            'fecha_desde' => $this->formatDate($plan->fecha_desde),
            'fecha_hasta' => $this->formatDate($plan->fecha_hasta),
            'alimentos_sustitucion' => $plan->alimentos_sustitucion,
            'notas_generales' => $plan->notas_generales,
            'notas_internas' => $plan->notas_internas,
            'estado' => $plan->estado,
            'version' => (int) $plan->version,
            'dias' => $this->resolveEffectivePlanDias($plan),
        ];
    }

    private function serializePlanSummary(PlanAlimentacion $plan): array
    {
        $plan->loadMissing('cliente:id,slug,nombres,paterno,materno,ci');

        return [
            'id' => (int) $plan->id,
            'id_cliente' => (int) $plan->id_cliente,
            'cliente_slug' => $plan->cliente?->slug,
            'cliente_nombre' => trim(implode(' ', array_filter([
                $plan->cliente?->nombres,
                $plan->cliente?->paterno,
                $plan->cliente?->materno,
            ]))),
            'cliente_ci' => $plan->cliente?->ci,
            'nombre' => $plan->nombre,
            'objetivo_nutricional' => $plan->objetivo_nutricional,
            'fecha_desde' => $this->formatDate($plan->fecha_desde),
            'fecha_hasta' => $this->formatDate($plan->fecha_hasta),
            'estado' => $plan->estado,
            'version' => (int) $plan->version,
            'updated_at' => optional($plan->updated_at)?->toISOString(),
        ];
    }

    private function serializePlanDetail(PlanAlimentacion $plan): array
    {
        $plan->loadMissing([
            'cliente:id,slug,nombres,paterno,materno,ci',
            'dias.comidas',
        ]);

        return array_merge($this->buildPlanSnapshot($plan), [
            'created_at' => optional($plan->created_at)?->toISOString(),
            'updated_at' => optional($plan->updated_at)?->toISOString(),
        ]);
    }

    private function buildPlanSnapshotForCliente(PlanAlimentacion $plan): array
    {
        $snapshot = $this->buildPlanSnapshot($plan);
        unset($snapshot['notas_internas']);

        return $snapshot;
    }

    private function storeVersionSnapshot(PlanAlimentacion $plan, int $userId, ?string $descripcionCambio): void
    {
        $plan->loadMissing(['cliente:id,slug,nombres,paterno,materno,ci', 'dias.comidas']);

        PlanAlimentacionVersion::create([
            'id_plan_alimentacion' => $plan->id,
            'version' => $plan->version,
            'descripcion_cambio' => $descripcionCambio,
            'snapshot' => $this->buildPlanSnapshot($plan),
            'id_usuario' => $userId,
        ]);
    }

    public function adminIndex(Request $request): JsonResponse
    {
        if ($err = $this->requireAdminOrEntrenador($request)) {
            return $err;
        }

        $busqueda = trim((string) $request->query('q', ''));
        $busquedaCi = str_replace(['.', '-', ' '], '', mb_strtolower($busqueda));

        $query = PlanAlimentacion::query()
            ->with('cliente:id,slug,nombres,paterno,materno,ci');

        $this->applyPlanScope($query, $request->user());

        if ($busqueda !== '') {
            $query->where(function (Builder $planQuery) use ($busqueda, $busquedaCi) {
                $planQuery->where('nombre', 'like', "%{$busqueda}%")
                    ->orWhere('objetivo_nutricional', 'like', "%{$busqueda}%")
                    ->orWhereHas('cliente', function (Builder $clienteQuery) use ($busqueda, $busquedaCi) {
                        $clienteQuery->where('nombres', 'like', "%{$busqueda}%")
                            ->orWhere('paterno', 'like', "%{$busqueda}%")
                            ->orWhere('materno', 'like', "%{$busqueda}%");

                        if ($busquedaCi !== '') {
                            $clienteQuery->orWhereRaw(
                                "REPLACE(REPLACE(REPLACE(LOWER(COALESCE(ci, '')), '.', ''), '-', ''), ' ', '') like ?",
                                ["%{$busquedaCi}%"]
                            );
                        }
                    });
            });
        }

        $planes = $query
            ->orderByRaw("FIELD(estado, 'activo', 'borrador', 'archivado')")
            ->orderByDesc('updated_at')
            ->get()
            ->map(fn(PlanAlimentacion $plan) => $this->serializePlanSummary($plan))
            ->values();

        return response()->json(['planes' => $planes]);
    }

    public function adminClienteIndex(Request $request, string $slug): JsonResponse
    {
        if ($err = $this->requireAdminOrEntrenador($request)) {
            return $err;
        }

        $busqueda = trim((string) $request->query('q', ''));
        $cliente = $this->findScopedCliente($request, $slug);

        if (! $cliente) {
            return response()->json(['message' => 'Cliente no encontrado.'], 404);
        }

        $planesQuery = PlanAlimentacion::query()
            ->with('cliente:id,slug,nombres,paterno,materno,ci')
            ->where('id_cliente', $cliente->id)
            ->orderByRaw("FIELD(estado, 'activo', 'borrador', 'archivado')")
            ->orderByDesc('updated_at');

        if ($busqueda !== '') {
            $planesQuery->where(function (Builder $planQuery) use ($busqueda) {
                $planQuery->where('nombre', 'like', "%{$busqueda}%")
                    ->orWhere('objetivo_nutricional', 'like', "%{$busqueda}%");
            });
        }

        $planes = $planesQuery
            ->get()
            ->map(fn(PlanAlimentacion $plan) => $this->serializePlanSummary($plan))
            ->values();

        return response()->json([
            'cliente' => [
                'id' => (int) $cliente->id,
                'slug' => $cliente->slug,
                'nombre_completo' => trim(implode(' ', array_filter([
                    $cliente->nombres,
                    $cliente->paterno,
                    $cliente->materno,
                ]))),
                'ci' => $cliente->ci,
            ],
            'planes' => $planes,
        ]);
    }

    public function adminShow(Request $request, int $planId): JsonResponse
    {
        if ($err = $this->requireAdminOrEntrenador($request)) {
            return $err;
        }

        $plan = $this->findScopedPlan($request, $planId);

        if (! $plan) {
            return response()->json(['message' => 'Plan no encontrado.'], 404);
        }

        $historialQuery = PlanAlimentacion::query()
            ->with('cliente:id,slug,nombres,paterno,materno,ci')
            ->where('id_cliente', $plan->id_cliente)
            ->where('id', '!=', $plan->id)
            ->orderByDesc('updated_at');

        $this->applyPlanScope($historialQuery, $request->user());

        return response()->json([
            'plan' => $this->serializePlanDetail($plan),
            'historial' => $historialQuery
                ->get()
                ->map(fn(PlanAlimentacion $historyPlan) => $this->serializePlanSummary($historyPlan))
                ->values(),
            'versiones' => $plan->versiones
                ->sortByDesc('version')
                ->values()
                ->map(fn(PlanAlimentacionVersion $version) => $this->serializeVersion($version))
                ->all(),
        ]);
    }

    public function adminStore(Request $request): JsonResponse
    {
        if ($err = $this->requireAdminOrEntrenador($request)) {
            return $err;
        }

        $payload = $this->validatePlanPayload($request);
        $cliente = $this->findScopedCliente($request, (string) $payload['id_cliente']);

        if (! $cliente) {
            return response()->json(['message' => 'Cliente no disponible para este usuario.'], 404);
        }

        $plan = DB::transaction(function () use ($payload, $request) {
            if ($payload['estado'] === 'activo') {
                $this->archiveActivePlansForCliente((int) $payload['id_cliente']);
            }

            $plan = PlanAlimentacion::create([
                'id_cliente' => (int) $payload['id_cliente'],
                'id_usuario_creador' => $request->user()->id,
                'id_usuario_editor' => $request->user()->id,
                'nombre' => $payload['nombre'],
                'objetivo_nutricional' => $payload['objetivo_nutricional'] ?? null,
                'fecha_desde' => $payload['fecha_desde'] ?? null,
                'fecha_hasta' => $payload['fecha_hasta'] ?? null,
                'alimentos_sustitucion' => $payload['alimentos_sustitucion'] ?? null,
                'notas_generales' => $payload['notas_generales'] ?? null,
                'notas_internas' => $payload['notas_internas'] ?? null,
                'estado' => $payload['estado'],
                'version' => 1,
            ]);

            $this->syncDiasYComidas($plan, $payload['dias']);
            $plan->refresh()->load(['cliente:id,slug,nombres,paterno,materno,ci', 'dias.comidas']);
            $this->storeVersionSnapshot($plan, (int) $request->user()->id, $payload['descripcion_cambio'] ?? 'Version inicial');

            return $plan;
        });

        return response()->json([
            'message' => 'Plan de alimentación creado correctamente.',
            'plan' => $this->serializePlanDetail($plan),
        ], 201);
    }

    public function adminUpdate(Request $request, int $planId): JsonResponse
    {
        if ($err = $this->requireAdminOrEntrenador($request)) {
            return $err;
        }

        $plan = $this->findScopedPlan($request, $planId);

        if (! $plan) {
            return response()->json(['message' => 'Plan no encontrado.'], 404);
        }

        $payload = $this->validatePlanPayload($request);
        $cliente = $this->findScopedCliente($request, (string) $payload['id_cliente']);

        if (! $cliente) {
            return response()->json(['message' => 'Cliente no disponible para este usuario.'], 404);
        }

        $updatedPlan = DB::transaction(function () use ($plan, $payload, $request) {
            if ($payload['estado'] === 'activo') {
                $this->archiveActivePlansForCliente((int) $payload['id_cliente'], (int) $plan->id);
            }

            $plan->fill([
                'id_cliente' => (int) $payload['id_cliente'],
                'id_usuario_editor' => $request->user()->id,
                'nombre' => $payload['nombre'],
                'objetivo_nutricional' => $payload['objetivo_nutricional'] ?? null,
                'fecha_desde' => $payload['fecha_desde'] ?? null,
                'fecha_hasta' => $payload['fecha_hasta'] ?? null,
                'alimentos_sustitucion' => $payload['alimentos_sustitucion'] ?? null,
                'notas_generales' => $payload['notas_generales'] ?? null,
                'notas_internas' => $payload['notas_internas'] ?? null,
                'estado' => $payload['estado'],
                'version' => ((int) $plan->version) + 1,
            ]);
            $plan->save();

            $this->syncDiasYComidas($plan, $payload['dias']);
            $plan->refresh()->load(['cliente:id,slug,nombres,paterno,materno,ci', 'dias.comidas']);
            $this->storeVersionSnapshot($plan, (int) $request->user()->id, $payload['descripcion_cambio'] ?? 'Actualizacion del plan');

            return $plan;
        });

        return response()->json([
            'message' => 'Plan de alimentación actualizado correctamente.',
            'plan' => $this->serializePlanDetail($updatedPlan),
        ]);
    }

    public function adminDuplicate(Request $request, int $planId): JsonResponse
    {
        if ($err = $this->requireAdminOrEntrenador($request)) {
            return $err;
        }

        $sourcePlan = $this->findScopedPlan($request, $planId);

        if (! $sourcePlan) {
            return response()->json(['message' => 'Plan no encontrado.'], 404);
        }

        $validated = $request->validate([
            'id_cliente' => 'nullable|integer|exists:clientes,id',
            'nombre' => 'nullable|string|max:255',
            'estado' => ['nullable', 'string', Rule::in(['borrador', 'activo', 'archivado'])],
            'descripcion_cambio' => 'nullable|string|max:255',
        ]);

        $targetClienteId = (int) ($validated['id_cliente'] ?? $sourcePlan->id_cliente);
        $cliente = $this->findScopedCliente($request, (string) $targetClienteId);

        if (! $cliente) {
            return response()->json(['message' => 'Cliente no disponible para este usuario.'], 404);
        }

        $duplicatePlan = DB::transaction(function () use ($sourcePlan, $validated, $request, $targetClienteId) {
            $sourcePlan->loadMissing('dias.comidas');
            $sourceDiasPayload = $this->resolveEffectivePlanDias($sourcePlan);

            $estado = $validated['estado'] ?? 'borrador';
            if ($estado === 'activo') {
                $this->archiveActivePlansForCliente($targetClienteId);
            }

            $plan = PlanAlimentacion::create([
                'id_cliente' => $targetClienteId,
                'id_usuario_creador' => $request->user()->id,
                'id_usuario_editor' => $request->user()->id,
                'id_plan_origen' => $sourcePlan->id,
                'nombre' => $validated['nombre'] ?? ($sourcePlan->nombre . ' (Copia)'),
                'objetivo_nutricional' => $sourcePlan->objetivo_nutricional,
                'fecha_desde' => $this->formatDate($sourcePlan->fecha_desde),
                'fecha_hasta' => $this->formatDate($sourcePlan->fecha_hasta),
                'alimentos_sustitucion' => $sourcePlan->alimentos_sustitucion,
                'notas_generales' => $sourcePlan->notas_generales,
                'notas_internas' => $sourcePlan->notas_internas,
                'estado' => $estado,
                'version' => 1,
            ]);

            $this->syncDiasYComidas($plan, $sourceDiasPayload);
            $plan->refresh()->load(['cliente:id,slug,nombres,paterno,materno,ci', 'dias.comidas']);
            $this->storeVersionSnapshot($plan, (int) $request->user()->id, $validated['descripcion_cambio'] ?? 'Plan duplicado');

            return $plan;
        });

        return response()->json([
            'message' => 'Plan duplicado correctamente.',
            'plan' => $this->serializePlanDetail($duplicatePlan),
        ], 201);
    }

    public function adminDestroy(Request $request, int $planId): JsonResponse
    {
        if ($err = $this->requireAdminOrEntrenador($request)) {
            return $err;
        }

        $plan = $this->findScopedPlan($request, $planId);

        if (! $plan) {
            return response()->json(['message' => 'Plan no encontrado.'], 404);
        }

        if ($plan->estado !== 'borrador') {
            return response()->json(['message' => 'Solo se pueden eliminar planes en estado borrador.'], 422);
        }

        DB::transaction(function () use ($plan) {
            $plan->loadMissing('dias.comidas');

            $plan->registros()->delete();
            $plan->versiones()->delete();

            foreach ($plan->dias as $dia) {
                $dia->comidas()->delete();
            }

            $plan->dias()->delete();
            $plan->delete();
        });

        return response()->json([
            'message' => 'Plan de alimentación eliminado correctamente.',
        ]);
    }

    private function buildResumenSemana(PlanAlimentacion $plan, Collection $registros, Carbon $fechaReferencia): array
    {
        $weekStart = $fechaReferencia->copy()->startOfWeek(Carbon::MONDAY);

        return $plan->dias
            ->sortBy(fn(PlanAlimentacionDia $dia) => [(int) $dia->orden, (int) $dia->dia_semana])
            ->values()
            ->map(function (PlanAlimentacionDia $dia) use ($registros, $weekStart) {
                $fecha = $weekStart->copy()->addDays(((int) $dia->dia_semana) - 1)->format('Y-m-d');
                $registrosDia = $registros->where('dia_semana', (int) $dia->dia_semana);

                return [
                    'dia_semana' => (int) $dia->dia_semana,
                    'nombre_dia' => $dia->nombre_dia,
                    'fecha' => $fecha,
                    'comidas_total' => $dia->comidas->count(),
                    'comidas_cumplidas' => $registrosDia->where('cumplido', true)->unique('codigo_comida')->count(),
                ];
            })
            ->all();
    }

    public function clienteActivo(Request $request): JsonResponse
    {
        $cliente = $this->resolveClientePorUsuario($request->user());

        if (! $cliente) {
            return response()->json(['message' => 'Perfil de cliente no encontrado.'], 404);
        }

        $fechaReferencia = $request->query('fecha')
            ? Carbon::parse((string) $request->query('fecha'))
            : now();

        $query = PlanAlimentacion::query()
            ->with(['dias.comidas'])
            ->where('id_cliente', $cliente->id)
            ->where('estado', 'activo')
            ->where(function (Builder $builder) use ($fechaReferencia) {
                $builder->whereNull('fecha_desde')
                    ->orWhereDate('fecha_desde', '<=', $fechaReferencia->toDateString());
            })
            ->where(function (Builder $builder) use ($fechaReferencia) {
                $builder->whereNull('fecha_hasta')
                    ->orWhereDate('fecha_hasta', '>=', $fechaReferencia->toDateString());
            })
            ->orderByDesc('updated_at');

        $plan = $query->first();

        if (! $plan) {
            return response()->json(['plan' => null]);
        }

        $weekStart = $fechaReferencia->copy()->startOfWeek(Carbon::MONDAY);
        $weekEnd = $fechaReferencia->copy()->endOfWeek(Carbon::SUNDAY);

        $registros = PlanAlimentacionRegistro::query()
            ->where('id_plan_alimentacion', $plan->id)
            ->whereBetween('fecha', [$weekStart->toDateString(), $weekEnd->toDateString()])
            ->orderBy('fecha')
            ->get();

        return response()->json([
            'plan' => array_merge($this->buildPlanSnapshotForCliente($plan), [
                'fecha_referencia' => $fechaReferencia->format('Y-m-d'),
                'dia_actual' => $fechaReferencia->dayOfWeekIso,
                'resumen_semana' => $this->buildResumenSemana($plan, $registros, $fechaReferencia),
                'registros_semana' => $registros->map(fn(PlanAlimentacionRegistro $registro) => [
                    'id' => (int) $registro->id,
                    'fecha' => $this->formatDate($registro->fecha),
                    'dia_semana' => (int) $registro->dia_semana,
                    'codigo_comida' => $registro->codigo_comida,
                    'cumplido' => (bool) $registro->cumplido,
                    'comentario' => $registro->comentario,
                ])->values()->all(),
            ]),
        ]);
    }

    public function clienteRegistrar(Request $request, int $planId): JsonResponse
    {
        $cliente = $this->resolveClientePorUsuario($request->user());

        if (! $cliente) {
            return response()->json(['message' => 'Perfil de cliente no encontrado.'], 404);
        }

        $validated = $request->validate([
            'fecha' => 'required|date',
            'codigo_comida' => ['required', 'string', Rule::in(array_keys(self::COMIDAS))],
            'cumplido' => 'required|boolean',
            'comentario' => 'nullable|string',
        ]);

        $fecha = Carbon::parse($validated['fecha']);

        $plan = PlanAlimentacion::query()
            ->with('dias.comidas')
            ->where('id', $planId)
            ->where('id_cliente', $cliente->id)
            ->first();

        if (! $plan) {
            return response()->json(['message' => 'Plan no encontrado.'], 404);
        }

        $diaSemana = $fecha->dayOfWeekIso;
        $comidaExiste = $plan->dias
            ->where('dia_semana', $diaSemana)
            ->flatMap(fn(PlanAlimentacionDia $dia) => $dia->comidas)
            ->contains(fn(PlanAlimentacionComida $comida) => $comida->codigo_comida === $validated['codigo_comida']);

        if (! $comidaExiste) {
            return response()->json(['message' => 'La comida seleccionada no existe en el plan.'], 422);
        }

        $registro = PlanAlimentacionRegistro::updateOrCreate(
            [
                'id_plan_alimentacion' => $plan->id,
                'fecha' => $fecha->format('Y-m-d'),
                'codigo_comida' => $validated['codigo_comida'],
            ],
            [
                'dia_semana' => $diaSemana,
                'cumplido' => (bool) $validated['cumplido'],
                'comentario' => $validated['comentario'] ?? null,
            ]
        );

        return response()->json([
            'message' => 'Seguimiento del plan actualizado correctamente.',
            'registro' => [
                'id' => (int) $registro->id,
                'fecha' => $this->formatDate($registro->fecha),
                'dia_semana' => (int) $registro->dia_semana,
                'codigo_comida' => $registro->codigo_comida,
                'cumplido' => (bool) $registro->cumplido,
                'comentario' => $registro->comentario,
            ],
        ]);
    }
}
