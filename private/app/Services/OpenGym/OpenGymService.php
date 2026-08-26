<?php

namespace App\Services\OpenGym;

use App\Models\Clientes;
use App\Models\Ejercicios;
use App\Models\GrupoMuscular;
use App\Models\OpenGymRoutine;
use App\Models\OpenGymRoutineExercise;
use App\Models\OpenGymWorkout;
use App\Models\OpenGymWorkoutSet;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class OpenGymService
{
    public const OPEN_GYM_TYPE_ID = 5;
    private const STATUS_ACTIVE = 'active';
    private const STATUS_COMPLETED = 'completed';
    private const FULL_BODY_FALLBACK_ID = 9;

    // Misma fórmula y mismas constantes que `estimateCalories` en
    // src/shared/utils/workoutStats.ts (frontend, usado por cliente/entrenador) —
    // MET (equivalente metabólico) × peso(kg) × horas, escalando el MET según la
    // densidad de volumen (repeticiones totales / minuto).
    private const DEFAULT_BODY_WEIGHT_KG = 70.0;
    private const MIN_TRAINING_MET = 3.5;
    private const MAX_TRAINING_MET = 6.5;
    private const MET_PER_REP_PER_MINUTE = 0.15;

    /** Caché local (por request) de GrupoMuscular resuelto por nombre — evita N+1. */
    private array $grupoMuscularPorNombre = [];

    public function listRoutines(User $user): array
    {
        $this->resolveContext($user);

        $routines = $this->ownedRoutineQuery($user)
            ->with(['exercises.ejercicio.tipo.grupoMuscular', 'originRoutine:id,nombre'])
            ->orderByDesc('updated_at')
            ->get();

        $completedWorkouts = $this->ownedWorkoutQuery($user)
            ->where('estado', self::STATUS_COMPLETED)
            ->count();

        return [
            'rutinas' => $routines->map(fn(OpenGymRoutine $routine) => $this->serializeRoutine($routine, false))->values(),
            'plantillas' => $this->buildSuggestedTemplates(),
            'resumen' => [
                'total_rutinas' => $routines->count(),
                'entrenamientos_completados' => $completedWorkouts,
            ],
        ];
    }

    public function listExerciseCatalog(User $user, ?string $search = null): array
    {
        $this->resolveContext($user);

        $query = Ejercicios::query()
            ->with(['tipo.grupoMuscular'])
            ->where('estado', 1)
            ->orderBy('nombre');

        $term = trim((string) $search);
        if ($term !== '') {
            $query->where('nombre', 'like', '%' . $term . '%');
        }

        return [
            'ejercicios' => $query->limit(80)->get()->map(function (Ejercicios $exercise) {
                $group = $exercise->tipo?->grupoMuscular;

                return [
                    'id' => (int) $exercise->id,
                    'nombre' => $exercise->nombre,
                    'descripcion' => $exercise->descripcion,
                    'tipo' => $exercise->tipo?->nombre,
                    'grupo_muscular' => $group?->nombre ?? 'Full Body',
                    'icono_grupo' => $group?->icono,
                    'color_grupo' => $group?->color,
                ];
            })->values(),
        ];
    }

    public function getRoutine(User $user, int $routineId): array
    {
        $routine = $this->findOwnedRoutine($user, $routineId, true);

        return [
            'rutina' => $this->serializeRoutine($routine, true),
        ];
    }

    public function saveRoutine(User $user, array $payload, ?int $routineId = null): array
    {
        [$cliente, $gymId] = $this->resolveContext($user);

        $routine = $routineId !== null
            ? $this->findOwnedRoutine($user, $routineId, true)
            : new OpenGymRoutine();

        DB::transaction(function () use ($routine, $payload, $user, $cliente, $gymId) {
            $routine->fill([
                'id_user' => (int) $user->id,
                'id_cliente' => (int) $cliente->id,
                'id_gimnasio' => $gymId,
                'nombre' => trim((string) $payload['nombre']),
                'descripcion' => isset($payload['descripcion']) ? trim((string) $payload['descripcion']) : null,
                'frecuencia_semanal' => $payload['frecuencia_semanal'] ?? null,
                'duracion_estimada_minutos' => $payload['duracion_estimada_minutos'] ?? null,
                'calorias_estimadas' => $payload['calorias_estimadas'] ?? null,
                'activo' => array_key_exists('activo', $payload) ? (bool) $payload['activo'] : true,
            ]);
            $routine->save();

            $routine->exercises()->delete();

            collect($payload['ejercicios'] ?? [])->values()->each(function (array $exercise, int $index) use ($routine) {
                $routine->exercises()->create([
                    'id_ejercicio' => $exercise['id_ejercicio'] ?? null,
                    'nombre_personalizado' => isset($exercise['nombre']) ? trim((string) $exercise['nombre']) : null,
                    'grupo_muscular' => isset($exercise['grupo_muscular']) ? trim((string) $exercise['grupo_muscular']) : null,
                    'notas' => isset($exercise['notas']) ? trim((string) $exercise['notas']) : null,
                    'orden' => $index + 1,
                    'series' => (int) ($exercise['series'] ?? 3),
                    'reps_objetivo' => (int) ($exercise['reps'] ?? 10),
                    'descanso_segundos' => (int) ($exercise['descanso_segundos'] ?? 60),
                    'peso_objetivo' => $exercise['peso_objetivo'] ?? null,
                ]);
            });
        });

        return [
            'rutina' => $this->serializeRoutine($routine->fresh(['exercises.ejercicio.tipo.grupoMuscular', 'originRoutine:id,nombre']), true),
        ];
    }

    public function duplicateRoutine(User $user, int $routineId): array
    {
        $routine = $this->findOwnedRoutine($user, $routineId, true);

        $clone = DB::transaction(function () use ($routine) {
            $copy = OpenGymRoutine::query()->create([
                'id_user' => $routine->id_user,
                'id_cliente' => $routine->id_cliente,
                'id_gimnasio' => $routine->id_gimnasio,
                'nombre' => $routine->nombre . ' (copia)',
                'descripcion' => $routine->descripcion,
                'frecuencia_semanal' => $routine->frecuencia_semanal,
                'duracion_estimada_minutos' => $routine->duracion_estimada_minutos,
                'calorias_estimadas' => $routine->calorias_estimadas,
                'id_rutina_origen' => $routine->id,
                'activo' => true,
            ]);

            $routine->exercises->each(function (OpenGymRoutineExercise $exercise) use ($copy) {
                $copy->exercises()->create([
                    'id_ejercicio' => $exercise->id_ejercicio,
                    'nombre_personalizado' => $exercise->nombre_personalizado,
                    'grupo_muscular' => $exercise->grupo_muscular,
                    'notas' => $exercise->notas,
                    'orden' => $exercise->orden,
                    'series' => $exercise->series,
                    'reps_objetivo' => $exercise->reps_objetivo,
                    'descanso_segundos' => $exercise->descanso_segundos,
                    'peso_objetivo' => $exercise->peso_objetivo,
                ]);
            });

            return $copy;
        });

        return [
            'rutina' => $this->serializeRoutine($clone->fresh(['exercises.ejercicio.tipo.grupoMuscular', 'originRoutine:id,nombre']), true),
        ];
    }

    public function deleteRoutine(User $user, int $routineId): void
    {
        $routine = $this->findOwnedRoutine($user, $routineId, false);
        $routine->delete();
    }

    public function getActiveWorkout(User $user): array
    {
        $this->resolveContext($user);

        $active = $this->ownedWorkoutQuery($user)
            ->with(['sets.grupoMuscular'])
            ->where('estado', self::STATUS_ACTIVE)
            ->latest('fecha_inicio')
            ->first();

        return [
            'entrenamiento' => $active ? $this->serializeWorkout($active) : null,
        ];
    }

    public function startWorkout(User $user, int $routineId): array
    {
        [$cliente, $gymId] = $this->resolveContext($user);

        $existing = $this->ownedWorkoutQuery($user)
            ->with(['sets.grupoMuscular'])
            ->where('estado', self::STATUS_ACTIVE)
            ->latest('fecha_inicio')
            ->first();

        if ($existing) {
            return [
                'entrenamiento' => $this->serializeWorkout($existing),
                'reanudado' => true,
            ];
        }

        $routine = $this->findOwnedRoutine($user, $routineId, true);

        $workout = DB::transaction(function () use ($routine, $user, $cliente, $gymId) {
            $workout = OpenGymWorkout::query()->create([
                'id_rutina' => $routine->id,
                'id_user' => $user->id,
                'id_cliente' => $cliente->id,
                'id_gimnasio' => $gymId,
                'nombre_rutina' => $routine->nombre,
                'estado' => self::STATUS_ACTIVE,
                'fecha_inicio' => now(),
                'calorias_estimadas' => $routine->calorias_estimadas,
            ]);

            $routine->exercises->values()->each(function (OpenGymRoutineExercise $exercise, int $index) use ($workout) {
                $group = $this->resolveGrupoMuscular($exercise->ejercicio?->tipo?->grupoMuscular, $exercise->grupo_muscular);
                $groupId = $group?->id ?? self::FULL_BODY_FALLBACK_ID;

                for ($setNumber = 1; $setNumber <= (int) $exercise->series; $setNumber++) {
                    $workout->sets()->create([
                        'id_rutina_ejercicio' => $exercise->id,
                        'id_ejercicio' => $exercise->id_ejercicio,
                        'id_grupo_muscular' => $groupId,
                        'orden_ejercicio' => $index + 1,
                        'nombre_ejercicio' => $exercise->ejercicio?->nombre ?? $exercise->nombre_personalizado ?? 'Ejercicio personalizado',
                        'numero_serie' => $setNumber,
                        'reps_objetivo' => $exercise->reps_objetivo,
                        'peso_objetivo' => $exercise->peso_objetivo,
                        'descanso_segundos' => $exercise->descanso_segundos,
                    ]);
                }
            });

            return $workout;
        });

        return [
            'entrenamiento' => $this->serializeWorkout($workout->fresh(['sets.grupoMuscular'])),
            'reanudado' => false,
        ];
    }

    public function getWorkout(User $user, int $workoutId): array
    {
        $workout = $this->findOwnedWorkout($user, $workoutId);

        return [
            'entrenamiento' => $this->serializeWorkout($workout),
        ];
    }

    public function updateWorkoutSet(User $user, int $workoutId, int $setId, array $payload): array
    {
        $workout = $this->findOwnedWorkout($user, $workoutId);
        $set = $workout->sets()->findOrFail($setId);

        $previousMax = OpenGymWorkoutSet::query()
            ->where('nombre_ejercicio', $set->nombre_ejercicio)
            ->where('id', '!=', $set->id)
            ->whereNotNull('completado_en')
            ->whereHas('workout', function (Builder $query) use ($user) {
                $query->where('id_user', $user->id)->where('estado', self::STATUS_COMPLETED);
            })
            ->max('peso_real');

        $weight = array_key_exists('peso_real', $payload) && $payload['peso_real'] !== null
            ? (float) $payload['peso_real']
            : ($set->peso_real !== null ? (float) $set->peso_real : null);

        $set->fill([
            'reps_realizadas' => $payload['reps_realizadas'] ?? $set->reps_realizadas,
            'peso_real' => $payload['peso_real'] ?? $set->peso_real,
            'descanso_segundos' => $payload['descanso_segundos'] ?? $set->descanso_segundos,
            'completado_en' => ! empty($payload['completado']) || array_key_exists('peso_real', $payload) || array_key_exists('reps_realizadas', $payload)
                ? now()
                : $set->completado_en,
        ]);
        $set->es_record_personal = $weight !== null && $weight > (float) ($previousMax ?? 0);
        $set->save();

        return [
            'entrenamiento' => $this->serializeWorkout($workout->fresh(['sets.grupoMuscular'])),
        ];
    }

    public function finishWorkout(User $user, int $workoutId, array $payload): array
    {
        $workout = $this->findOwnedWorkout($user, $workoutId);

        $completedSets = $workout->sets()->whereNotNull('completado_en')->get();
        $groupSummary = $this->buildWorkoutMuscleBreakdown($completedSets);
        $prs = $completedSets->filter(fn(OpenGymWorkoutSet $set) => $set->es_record_personal)->values();
        $finishedAt = now();
        $duration = Carbon::parse($workout->fecha_inicio)->diffInSeconds($finishedAt);

        $workout->fill([
            'estado' => self::STATUS_COMPLETED,
            'fecha_fin' => $finishedAt,
            'duracion_segundos' => $duration,
            'calorias_estimadas' => $this->estimateWorkoutCalories($workout, $completedSets, $duration),
            'dificultad_percibida' => $payload['dificultad_percibida'] ?? null,
            'resumen_prs' => $prs->map(fn(OpenGymWorkoutSet $set) => [
                'ejercicio' => $set->nombre_ejercicio,
                'peso_real' => $set->peso_real,
            ])->values()->all(),
            'resumen_grupos' => $groupSummary,
        ]);
        $workout->save();

        return [
            'entrenamiento' => $this->serializeWorkout($workout->fresh(['sets.grupoMuscular'])),
            'resumen' => [
                'tiempo_total_minutos' => (int) round($duration / 60),
                'calorias' => $workout->calorias_estimadas,
                'ejercicios_completados' => $completedSets->groupBy('orden_ejercicio')->count(),
                'records' => $prs->count(),
                'grupos_musculares' => $groupSummary,
            ],
        ];
    }

    public function progress(User $user): array
    {
        $this->resolveContext($user);

        $thirtyDaysAgo = now()->subDays(30);
        $threeMonthsAgo = now()->subMonths(3)->startOfMonth();

        $completedSets = OpenGymWorkoutSet::query()
            ->with('grupoMuscular:id,nombre,icono,color')
            ->whereHas('workout', function (Builder $query) use ($user, $thirtyDaysAgo) {
                $query->where('id_user', $user->id)
                    ->where('estado', self::STATUS_COMPLETED)
                    ->whereNotNull('fecha_fin')
                    ->where('fecha_fin', '>=', $thirtyDaysAgo);
            })
            ->whereNotNull('completado_en')
            ->get();

        $groupSummary = $this->buildWorkoutMuscleBreakdown($completedSets);

        $topExerciseName = OpenGymWorkoutSet::query()
            ->select('nombre_ejercicio', DB::raw('COUNT(1) as total'))
            ->whereHas('workout', function (Builder $query) use ($user, $threeMonthsAgo) {
                $query->where('id_user', $user->id)
                    ->where('estado', self::STATUS_COMPLETED)
                    ->whereNotNull('fecha_fin')
                    ->where('fecha_fin', '>=', $threeMonthsAgo);
            })
            ->where(function (Builder $query) {
                $query->whereNotNull('peso_real')
                    ->orWhereNotNull('peso_objetivo');
            })
            ->groupBy('nombre_ejercicio')
            ->orderByDesc('total')
            ->value('nombre_ejercicio');

        $strengthSeries = collect();
        if ($topExerciseName) {
            $strengthSeries = OpenGymWorkoutSet::query()
                ->join('open_gym_entrenamientos', 'open_gym_entrenamiento_series.id_entrenamiento', '=', 'open_gym_entrenamientos.id')
                ->where('open_gym_entrenamientos.id_user', $user->id)
                ->where('open_gym_entrenamientos.estado', self::STATUS_COMPLETED)
                ->where('open_gym_entrenamiento_series.nombre_ejercicio', $topExerciseName)
                ->where(function (Builder $query) {
                    $query->whereNotNull('open_gym_entrenamiento_series.peso_real')
                        ->orWhereNotNull('open_gym_entrenamiento_series.peso_objetivo');
                })
                ->where('open_gym_entrenamientos.fecha_fin', '>=', $threeMonthsAgo)
                ->selectRaw('DATE(open_gym_entrenamientos.fecha_fin) as periodo')
                ->selectRaw('MAX(COALESCE(open_gym_entrenamiento_series.peso_real, open_gym_entrenamiento_series.peso_objetivo)) as valor')
                ->groupBy('periodo')
                ->orderBy('periodo')
                ->get()
                ->map(fn($row) => [
                    'periodo' => $row->periodo,
                    'valor' => (float) $row->valor,
                ]);
        }

        $recentWorkouts = $this->ownedWorkoutQuery($user)
            ->where('estado', self::STATUS_COMPLETED)
            ->orderByDesc('fecha_fin')
            ->limit(10)
            ->get()
            ->map(fn(OpenGymWorkout $workout) => $this->serializeHistoryItem($workout))
            ->values();

        $delta = 0;
        if ($strengthSeries->count() >= 2) {
            $delta = (float) $strengthSeries->last()['valor'] - (float) $strengthSeries->first()['valor'];
        }

        return [
            'fuerza' => [
                'ejercicio' => $topExerciseName,
                'serie' => $strengthSeries->values()->all(),
                'delta' => $delta,
            ],
            'grupos_musculares' => $groupSummary,
            'entrenamientos_recientes' => $recentWorkouts,
        ];
    }

    public function history(User $user, ?string $month = null): array
    {
        $this->resolveContext($user);

        $query = $this->ownedWorkoutQuery($user)
            ->where('estado', self::STATUS_COMPLETED)
            ->orderByDesc('fecha_fin');

        if ($month && preg_match('/^\d{4}-\d{2}$/', $month) === 1) {
            $query->whereRaw("DATE_FORMAT(fecha_fin, '%Y-%m') = ?", [$month]);
        }

        $items = $query->limit(60)->get()->map(fn(OpenGymWorkout $workout) => $this->serializeHistoryItem($workout));

        return [
            'historial' => $items->groupBy('fecha_clave')->map(function (Collection $group, string $dateKey) {
                $first = $group->first();

                return [
                    'fecha' => $dateKey,
                    'titulo_fecha' => $first['fecha_titulo'],
                    'entrenamientos' => $group->values()->all(),
                ];
            })->values()->all(),
        ];
    }

    private function resolveContext(User $user): array
    {
        if ((int) $user->id_tipo_usuario !== self::OPEN_GYM_TYPE_ID) {
            abort(403, 'Este modulo solo esta disponible para clientes Open Gym.');
        }

        $cliente = $user->cliente ?? Clientes::query()->find($user->id_cliente);

        if (! $cliente) {
            abort(404, 'No encontramos el perfil de cliente asociado.');
        }

        return [$cliente, $cliente->id_gimnasio ?? $user->id_gimnasio];
    }

    private function ownedRoutineQuery(User $user): Builder
    {
        return OpenGymRoutine::query()->where('id_user', $user->id)->where('id_cliente', $user->id_cliente);
    }

    private function ownedWorkoutQuery(User $user): Builder
    {
        return OpenGymWorkout::query()->where('id_user', $user->id)->where('id_cliente', $user->id_cliente);
    }

    private function findOwnedRoutine(User $user, int $routineId, bool $withRelations): OpenGymRoutine
    {
        $query = $this->ownedRoutineQuery($user);

        if ($withRelations) {
            $query->with(['exercises.ejercicio.tipo.grupoMuscular', 'originRoutine:id,nombre']);
        }

        return $query->findOrFail($routineId);
    }

    private function findOwnedWorkout(User $user, int $workoutId): OpenGymWorkout
    {
        return $this->ownedWorkoutQuery($user)
            ->with(['sets.grupoMuscular'])
            ->findOrFail($workoutId);
    }

    private function serializeRoutine(OpenGymRoutine $routine, bool $withExercises): array
    {
        $exercises = $routine->relationLoaded('exercises')
            ? $routine->exercises
            : $routine->exercises()->with(['ejercicio.tipo.grupoMuscular'])->get();

        $groupSummary = $this->buildRoutineMuscleBreakdown($exercises);

        $base = [
            'id' => (int) $routine->id,
            'nombre' => $routine->nombre,
            'descripcion' => $routine->descripcion,
            'frecuencia_semanal' => $routine->frecuencia_semanal,
            'duracion_estimada_minutos' => $routine->duracion_estimada_minutos,
            'calorias_estimadas' => $routine->calorias_estimadas,
            'total_ejercicios' => $exercises->count(),
            'grupos_musculares' => $groupSummary,
            'origen' => $routine->originRoutine?->nombre,
            'actualizado_en' => optional($routine->updated_at)->toIso8601String(),
        ];

        if (! $withExercises) {
            return $base;
        }

        $base['ejercicios'] = $exercises->map(function (OpenGymRoutineExercise $exercise) {
            $catalogGroup = $exercise->ejercicio?->tipo?->grupoMuscular;
            $group = $this->resolveGrupoMuscular($catalogGroup, $exercise->grupo_muscular);

            return [
                'id' => (int) $exercise->id,
                'id_ejercicio' => $exercise->id_ejercicio ? (int) $exercise->id_ejercicio : null,
                'nombre' => $exercise->ejercicio?->nombre ?? $exercise->nombre_personalizado ?? 'Ejercicio personalizado',
                'grupo_muscular' => $this->displayGrupoMuscularNombre($catalogGroup, $exercise->grupo_muscular),
                'icono_grupo' => $group?->icono,
                'color_grupo' => $group?->color,
                'series' => (int) $exercise->series,
                'reps' => (int) $exercise->reps_objetivo,
                'descanso_segundos' => (int) $exercise->descanso_segundos,
                'peso_objetivo' => $exercise->peso_objetivo !== null ? (float) $exercise->peso_objetivo : null,
                'notas' => $exercise->notas,
                'orden' => (int) $exercise->orden,
            ];
        })->values()->all();

        return $base;
    }

    private function serializeWorkout(OpenGymWorkout $workout): array
    {
        $sets = $workout->relationLoaded('sets') ? $workout->sets : $workout->sets()->with('grupoMuscular')->get();

        $exerciseGroups = $sets->groupBy('orden_ejercicio')->map(function (Collection $group) {
            /** @var OpenGymWorkoutSet $first */
            $first = $group->first();
            $completedSets = $group->filter(fn(OpenGymWorkoutSet $set) => $set->completado_en !== null)->count();

            return [
                'orden' => (int) $first->orden_ejercicio,
                'nombre' => $first->nombre_ejercicio,
                'grupo_muscular' => $first->grupoMuscular?->nombre ?? 'Full Body',
                'series_totales' => $group->count(),
                'series_completadas' => $completedSets,
                'estado' => $completedSets === $group->count() ? 'completed' : ($completedSets > 0 ? 'in_progress' : 'pending'),
                'series' => $group->map(fn(OpenGymWorkoutSet $set) => [
                    'id' => (int) $set->id,
                    'numero' => (int) $set->numero_serie,
                    'reps_objetivo' => $set->reps_objetivo,
                    'reps_realizadas' => $set->reps_realizadas,
                    'peso_objetivo' => $set->peso_objetivo !== null ? (float) $set->peso_objetivo : null,
                    'peso_real' => $set->peso_real !== null ? (float) $set->peso_real : null,
                    'descanso_segundos' => $set->descanso_segundos,
                    'completado' => $set->completado_en !== null,
                    'es_record_personal' => (bool) $set->es_record_personal,
                ])->values()->all(),
            ];
        })->values()->all();

        $completedExercises = collect($exerciseGroups)->filter(fn(array $exercise) => $exercise['estado'] === 'completed')->count();
        $totalExercises = count($exerciseGroups);
        $progress = $totalExercises > 0 ? (int) round(($completedExercises / $totalExercises) * 100) : 0;

        return [
            'id' => (int) $workout->id,
            'id_rutina' => $workout->id_rutina ? (int) $workout->id_rutina : null,
            'nombre' => $workout->nombre_rutina,
            'estado' => $workout->estado,
            'fecha_inicio' => optional($workout->fecha_inicio)->toIso8601String(),
            'fecha_fin' => optional($workout->fecha_fin)->toIso8601String(),
            'duracion_segundos' => $workout->duracion_segundos,
            'duracion_texto' => $this->formatDuration($workout->duracion_segundos),
            'calorias_estimadas' => $workout->calorias_estimadas,
            'dificultad_percibida' => $workout->dificultad_percibida,
            'progreso_porcentaje' => $progress,
            'ejercicios_completados' => $completedExercises,
            'ejercicios_totales' => $totalExercises,
            'resumen_grupos' => $workout->resumen_grupos ?? $this->buildWorkoutMuscleBreakdown($sets),
            'resumen_prs' => $workout->resumen_prs ?? [],
            'ejercicios' => $exerciseGroups,
        ];
    }

    /**
     * Resuelve el grupo muscular de un ejercicio: prioriza el vínculo de catálogo
     * (`id_ejercicio -> tipo -> grupoMuscular`, el mismo que usan los ejercicios de
     * gimnasios/clientes presenciales); si no hay vínculo (ejercicio personalizado,
     * como los de las plantillas de Open Gym), busca por nombre en el catálogo
     * `grupos_musculares` usando el texto libre guardado. No crea filas nuevas en
     * ningún catálogo — solo lee `grupos_musculares`, que ya existía.
     */
    private function resolveGrupoMuscular(?GrupoMuscular $catalogGroup, ?string $textoLibre): ?GrupoMuscular
    {
        if ($catalogGroup) {
            return $catalogGroup;
        }

        $nombre = trim((string) $textoLibre);
        if ($nombre === '') {
            return null;
        }

        if (! array_key_exists($nombre, $this->grupoMuscularPorNombre)) {
            $this->grupoMuscularPorNombre[$nombre] = GrupoMuscular::where('nombre', $nombre)->first();
        }

        return $this->grupoMuscularPorNombre[$nombre];
    }

    private function displayGrupoMuscularNombre(?GrupoMuscular $catalogGroup, ?string $textoLibre): string
    {
        $resuelto = $this->resolveGrupoMuscular($catalogGroup, $textoLibre);
        if ($resuelto) {
            return $resuelto->nombre;
        }

        $trimmed = trim((string) $textoLibre);
        return $trimmed !== '' ? $trimmed : 'Full Body';
    }

    private function buildRoutineMuscleBreakdown(Collection $exercises): array
    {
        $totalSeries = max(1, (int) $exercises->sum('series'));

        return $exercises
            ->groupBy(function (OpenGymRoutineExercise $exercise) {
                return $this->displayGrupoMuscularNombre($exercise->ejercicio?->tipo?->grupoMuscular, $exercise->grupo_muscular);
            })
            ->map(function (Collection $groupedExercises, string $name) use ($totalSeries) {
                $first = $groupedExercises->first();
                $group = $this->resolveGrupoMuscular($first?->ejercicio?->tipo?->grupoMuscular, $first?->grupo_muscular);
                $series = (int) $groupedExercises->sum('series');

                return [
                    'nombre' => $name,
                    'icono' => $group?->icono,
                    'color' => $group?->color,
                    'series' => $series,
                    'porcentaje' => (int) round(($series / $totalSeries) * 100),
                ];
            })
            ->sortByDesc('porcentaje')
            ->values()
            ->all();
    }

    private function buildWorkoutMuscleBreakdown(Collection $sets): array
    {
        $completedSets = $sets->filter(fn(OpenGymWorkoutSet $set) => $set->completado_en !== null);
        $totalSets = max(1, $completedSets->count());

        return $completedSets
            ->groupBy(fn(OpenGymWorkoutSet $set) => $set->grupoMuscular?->nombre ?? 'Full Body')
            ->map(function (Collection $groupedSets, string $name) use ($totalSets) {
                $first = $groupedSets->first();

                return [
                    'nombre' => $name,
                    'icono' => $first?->grupoMuscular?->icono,
                    'color' => $first?->grupoMuscular?->color,
                    'series' => $groupedSets->count(),
                    'porcentaje' => (int) round(($groupedSets->count() / $totalSets) * 100),
                ];
            })
            ->sortByDesc('porcentaje')
            ->values()
            ->all();
    }

    /**
     * Estima kcal quemadas con MET × peso(kg) × horas (mismo criterio que
     * `estimateCalories` del cliente normal, ver constantes arriba). El peso usa el
     * último registro real del cliente en la tabla `pesos` (la misma que ya llena
     * cualquier cliente —incluido Open Gym— vía `POST /cliente/metricas`); si nunca
     * registró uno, cae al peso de referencia, igual que el resto de la app.
     */
    private function estimateWorkoutCalories(OpenGymWorkout $workout, Collection $completedSets, int $durationSeconds): int
    {
        if ($workout->calorias_estimadas) {
            return (int) $workout->calorias_estimadas;
        }

        $totalMinutes = $durationSeconds / 60;
        if ($totalMinutes <= 0 || $completedSets->isEmpty()) {
            return max(80, (int) ($completedSets->count() * 18));
        }

        $volume = (float) $completedSets->sum(
            fn (OpenGymWorkoutSet $set) => (float) ($set->reps_realizadas ?? $set->reps_objetivo ?? 0),
        );

        $ultimoPeso = DB::table('pesos')
            ->where('id_cliente', $workout->id_cliente)
            ->orderByDesc('created_at')
            ->value('peso');
        $weightKg = $ultimoPeso !== null ? (float) $ultimoPeso : self::DEFAULT_BODY_WEIGHT_KG;

        $density = $volume / $totalMinutes;
        $met = min(self::MAX_TRAINING_MET, self::MIN_TRAINING_MET + $density * self::MET_PER_REP_PER_MINUTE);
        $hours = $totalMinutes / 60;

        return max(80, (int) round($met * $weightKg * $hours));
    }

    private function serializeHistoryItem(OpenGymWorkout $workout): array
    {
        $date = $workout->fecha_fin ?? $workout->fecha_inicio;
        $prCount = is_array($workout->resumen_prs) ? count($workout->resumen_prs) : 0;

        return [
            'id' => (int) $workout->id,
            'nombre' => $workout->nombre_rutina,
            'fecha' => optional($date)->toDateString(),
            'fecha_clave' => optional($date)->toDateString(),
            'fecha_titulo' => optional($date)->locale('es')->translatedFormat('l d \d\e F'),
            'duracion_texto' => $this->formatDuration($workout->duracion_segundos),
            'duracion_segundos' => $workout->duracion_segundos,
            'calorias_estimadas' => $workout->calorias_estimadas,
            'records' => $prCount,
            'dificultad_percibida' => $workout->dificultad_percibida,
        ];
    }

    /**
     * 21 plantillas (7 grupos × 3 niveles) para clientes autoguiados sin entrenador
     * presente: sin ejercicios que requieran observador, con máquinas priorizadas en
     * nivel básico y progresión a peso libre en medio/avanzado. `nivel` alimenta el
     * clasificador Básico/Medio/Avanzado del buscador de plantillas en la app.
     */
    private function buildSuggestedTemplates(): array
    {
        return [
            // ===================================================================
            // CUERPO COMPLETO (FULL)
            // ===================================================================
            [
                'nombre' => 'Cuerpo completo — Inicio',
                'descripcion' => 'Circuito de máquinas para todo el cuerpo, ideal para empezar. 40–45 min.',
                'nivel' => 'basico',
                'grupos' => ['Pierna', 'Espalda', 'Pecho', 'Hombro', 'Core'],
                'ejercicios' => [
                    ['nombre' => 'Prensa 45°', 'grupo_muscular' => 'Pierna', 'series' => 3, 'reps' => 12, 'descanso_segundos' => 90, 'peso_objetivo' => null, 'notas' => 'No bloquear rodillas arriba.'],
                    ['nombre' => 'Jalón al pecho en polea', 'grupo_muscular' => 'Espalda', 'series' => 3, 'reps' => 12, 'descanso_segundos' => 75, 'peso_objetivo' => null, 'notas' => 'Llevar la barra al pecho, no a la nuca.'],
                    ['nombre' => 'Press de pecho en máquina', 'grupo_muscular' => 'Pecho', 'series' => 3, 'reps' => 12, 'descanso_segundos' => 75, 'peso_objetivo' => null, 'notas' => 'Codos a 45°, no pegados al torso.'],
                    ['nombre' => 'Press de hombro en máquina', 'grupo_muscular' => 'Hombro', 'series' => 2, 'reps' => 12, 'descanso_segundos' => 60, 'peso_objetivo' => null, 'notas' => null],
                    ['nombre' => 'Curl femoral tumbado', 'grupo_muscular' => 'Pierna', 'series' => 3, 'reps' => 12, 'descanso_segundos' => 60, 'peso_objetivo' => null, 'notas' => null],
                    ['nombre' => 'Plancha frontal', 'grupo_muscular' => 'Core', 'series' => 3, 'reps' => 1, 'descanso_segundos' => 45, 'peso_objetivo' => null, 'notas' => 'Sostén 20-30 segundos. Cadera alineada, sin arquear lumbar.'],
                ],
            ],
            [
                'nombre' => 'Cuerpo completo — Progresión',
                'descripcion' => 'Progresión con mancuernas y barra para cuerpo completo. 55–60 min.',
                'nivel' => 'medio',
                'grupos' => ['Pierna', 'Pecho', 'Espalda', 'Gluteos', 'Hombro', 'Brazos', 'Core'],
                'ejercicios' => [
                    ['nombre' => 'Sentadilla goblet con mancuerna', 'grupo_muscular' => 'Pierna', 'series' => 4, 'reps' => 10, 'descanso_segundos' => 90, 'peso_objetivo' => null, 'notas' => null],
                    ['nombre' => 'Press banca con mancuernas', 'grupo_muscular' => 'Pecho', 'series' => 3, 'reps' => 10, 'descanso_segundos' => 90, 'peso_objetivo' => null, 'notas' => 'Sin observador: usar mancuernas, no barra.'],
                    ['nombre' => 'Remo con mancuerna a una mano', 'grupo_muscular' => 'Espalda', 'series' => 3, 'reps' => 10, 'descanso_segundos' => 75, 'peso_objetivo' => null, 'notas' => '10 repeticiones por lado.'],
                    ['nombre' => 'Peso muerto rumano con barra', 'grupo_muscular' => 'Gluteos', 'series' => 3, 'reps' => 10, 'descanso_segundos' => 90, 'peso_objetivo' => null, 'notas' => 'Espalda neutra, recorrido hasta media espinilla.'],
                    ['nombre' => 'Press de hombro con mancuernas sentado', 'grupo_muscular' => 'Hombro', 'series' => 3, 'reps' => 10, 'descanso_segundos' => 75, 'peso_objetivo' => null, 'notas' => null],
                    ['nombre' => 'Curl con barra Z', 'grupo_muscular' => 'Brazos', 'series' => 2, 'reps' => 12, 'descanso_segundos' => 0, 'peso_objetivo' => null, 'notas' => 'Superserie con extensión de tríceps en polea: sin descanso entre ambos.'],
                    ['nombre' => 'Extensión de tríceps en polea', 'grupo_muscular' => 'Brazos', 'series' => 2, 'reps' => 12, 'descanso_segundos' => 60, 'peso_objetivo' => null, 'notas' => 'Segunda mitad de la superserie con curl con barra Z.'],
                    ['nombre' => 'Plancha frontal', 'grupo_muscular' => 'Core', 'series' => 3, 'reps' => 1, 'descanso_segundos' => 45, 'peso_objetivo' => null, 'notas' => 'Sostén 45 segundos.'],
                ],
            ],
            [
                'nombre' => 'Cuerpo completo — Fuerza total',
                'descripcion' => 'Fuerza total con barra: sentadilla, press y dominadas. 70–80 min.',
                'nivel' => 'avanzado',
                'grupos' => ['Pierna', 'Pecho', 'Gluteos', 'Espalda', 'Hombro', 'Brazos', 'Core'],
                'ejercicios' => [
                    ['nombre' => 'Sentadilla con barra', 'grupo_muscular' => 'Pierna', 'series' => 4, 'reps' => 6, 'descanso_segundos' => 150, 'peso_objetivo' => null, 'notas' => 'Usar rack con pines de seguridad.'],
                    ['nombre' => 'Press banca con barra', 'grupo_muscular' => 'Pecho', 'series' => 4, 'reps' => 6, 'descanso_segundos' => 150, 'peso_objetivo' => null, 'notas' => 'Solo en rack con topes si entrena solo.'],
                    ['nombre' => 'Peso muerto rumano con barra', 'grupo_muscular' => 'Gluteos', 'series' => 3, 'reps' => 8, 'descanso_segundos' => 120, 'peso_objetivo' => null, 'notas' => null],
                    ['nombre' => 'Dominadas (lastradas si domina 12)', 'grupo_muscular' => 'Espalda', 'series' => 4, 'reps' => 8, 'descanso_segundos' => 120, 'peso_objetivo' => null, 'notas' => 'Alternativa: jalón agarre neutro.'],
                    ['nombre' => 'Press militar con barra de pie', 'grupo_muscular' => 'Hombro', 'series' => 3, 'reps' => 8, 'descanso_segundos' => 90, 'peso_objetivo' => null, 'notas' => null],
                    ['nombre' => 'Remo con barra', 'grupo_muscular' => 'Espalda', 'series' => 3, 'reps' => 10, 'descanso_segundos' => 90, 'peso_objetivo' => null, 'notas' => null],
                    ['nombre' => 'Curl martillo', 'grupo_muscular' => 'Brazos', 'series' => 2, 'reps' => 12, 'descanso_segundos' => 0, 'peso_objetivo' => null, 'notas' => 'Superserie con fondos en banco: sin descanso entre ambos.'],
                    ['nombre' => 'Fondos en banco', 'grupo_muscular' => 'Brazos', 'series' => 2, 'reps' => 12, 'descanso_segundos' => 60, 'peso_objetivo' => null, 'notas' => 'Segunda mitad de la superserie con curl martillo.'],
                    ['nombre' => 'Rueda abdominal (ab wheel)', 'grupo_muscular' => 'Core', 'series' => 3, 'reps' => 10, 'descanso_segundos' => 60, 'peso_objetivo' => null, 'notas' => null],
                ],
            ],

            // ===================================================================
            // PIERNAS (PIER)
            // ===================================================================
            [
                'nombre' => 'Piernas — Máquinas',
                'descripcion' => 'Piernas en máquinas, sin cargas libres. 40–45 min.',
                'nivel' => 'basico',
                'grupos' => ['Pierna', 'Gluteos'],
                'ejercicios' => [
                    ['nombre' => 'Prensa 45°', 'grupo_muscular' => 'Pierna', 'series' => 3, 'reps' => 12, 'descanso_segundos' => 90, 'peso_objetivo' => null, 'notas' => 'Pies a la altura de los hombros.'],
                    ['nombre' => 'Extensión de cuádriceps en máquina', 'grupo_muscular' => 'Pierna', 'series' => 3, 'reps' => 12, 'descanso_segundos' => 60, 'peso_objetivo' => null, 'notas' => null],
                    ['nombre' => 'Curl femoral tumbado', 'grupo_muscular' => 'Pierna', 'series' => 3, 'reps' => 12, 'descanso_segundos' => 60, 'peso_objetivo' => null, 'notas' => null],
                    ['nombre' => 'Sentadilla goblet con mancuerna', 'grupo_muscular' => 'Pierna', 'series' => 3, 'reps' => 10, 'descanso_segundos' => 90, 'peso_objetivo' => null, 'notas' => 'Bajar hasta donde controle la espalda.'],
                    ['nombre' => 'Elevación de talones de pie', 'grupo_muscular' => 'Pierna', 'series' => 3, 'reps' => 15, 'descanso_segundos' => 45, 'peso_objetivo' => null, 'notas' => 'Pausa de 1 s arriba.'],
                    ['nombre' => 'Abducción de cadera en máquina', 'grupo_muscular' => 'Gluteos', 'series' => 2, 'reps' => 15, 'descanso_segundos' => 45, 'peso_objetivo' => null, 'notas' => null],
                ],
            ],
            [
                'nombre' => 'Piernas — Peso libre',
                'descripcion' => 'Piernas con sentadilla y peso muerto con barra. 60–65 min.',
                'nivel' => 'medio',
                'grupos' => ['Pierna', 'Gluteos'],
                'ejercicios' => [
                    ['nombre' => 'Sentadilla con barra', 'grupo_muscular' => 'Pierna', 'series' => 4, 'reps' => 8, 'descanso_segundos' => 120, 'peso_objetivo' => null, 'notas' => 'Rack con pines de seguridad.'],
                    ['nombre' => 'Peso muerto rumano con barra', 'grupo_muscular' => 'Gluteos', 'series' => 3, 'reps' => 10, 'descanso_segundos' => 90, 'peso_objetivo' => null, 'notas' => null],
                    ['nombre' => 'Prensa 45°', 'grupo_muscular' => 'Pierna', 'series' => 3, 'reps' => 12, 'descanso_segundos' => 90, 'peso_objetivo' => null, 'notas' => null],
                    ['nombre' => 'Zancadas caminando con mancuernas', 'grupo_muscular' => 'Pierna', 'series' => 3, 'reps' => 10, 'descanso_segundos' => 75, 'peso_objetivo' => null, 'notas' => '10 repeticiones por pierna.'],
                    ['nombre' => 'Curl femoral sentado', 'grupo_muscular' => 'Pierna', 'series' => 3, 'reps' => 12, 'descanso_segundos' => 60, 'peso_objetivo' => null, 'notas' => null],
                    ['nombre' => 'Extensión de cuádriceps', 'grupo_muscular' => 'Pierna', 'series' => 3, 'reps' => 12, 'descanso_segundos' => 60, 'peso_objetivo' => null, 'notas' => null],
                    ['nombre' => 'Elevación de talones de pie', 'grupo_muscular' => 'Pierna', 'series' => 4, 'reps' => 12, 'descanso_segundos' => 45, 'peso_objetivo' => null, 'notas' => null],
                ],
            ],
            [
                'nombre' => 'Piernas — Volumen y fuerza',
                'descripcion' => 'Volumen y fuerza para piernas y glúteos. 75–85 min.',
                'nivel' => 'avanzado',
                'grupos' => ['Pierna', 'Gluteos'],
                'ejercicios' => [
                    ['nombre' => 'Sentadilla con barra', 'grupo_muscular' => 'Pierna', 'series' => 5, 'reps' => 5, 'descanso_segundos' => 165, 'peso_objetivo' => null, 'notas' => 'RPE 8, dejar 2 reps en reserva.'],
                    ['nombre' => 'Peso muerto rumano con barra', 'grupo_muscular' => 'Gluteos', 'series' => 4, 'reps' => 8, 'descanso_segundos' => 120, 'peso_objetivo' => null, 'notas' => null],
                    ['nombre' => 'Hack squat o prensa 45°', 'grupo_muscular' => 'Pierna', 'series' => 4, 'reps' => 10, 'descanso_segundos' => 90, 'peso_objetivo' => null, 'notas' => 'Última serie en dropset (-30%).'],
                    ['nombre' => 'Sentadilla búlgara con mancuernas', 'grupo_muscular' => 'Pierna', 'series' => 3, 'reps' => 8, 'descanso_segundos' => 90, 'peso_objetivo' => null, 'notas' => '8 repeticiones por pierna.'],
                    ['nombre' => 'Hip thrust con barra', 'grupo_muscular' => 'Gluteos', 'series' => 4, 'reps' => 8, 'descanso_segundos' => 90, 'peso_objetivo' => null, 'notas' => 'Barra almohadillada.'],
                    ['nombre' => 'Curl femoral tumbado', 'grupo_muscular' => 'Pierna', 'series' => 4, 'reps' => 10, 'descanso_segundos' => 60, 'peso_objetivo' => null, 'notas' => 'Tempo 2-1-2.'],
                    ['nombre' => 'Extensión de cuádriceps', 'grupo_muscular' => 'Pierna', 'series' => 3, 'reps' => 15, 'descanso_segundos' => 60, 'peso_objetivo' => null, 'notas' => 'Última serie rest-pause.'],
                    ['nombre' => 'Elevación de talones en prensa', 'grupo_muscular' => 'Pierna', 'series' => 4, 'reps' => 15, 'descanso_segundos' => 45, 'peso_objetivo' => null, 'notas' => null],
                ],
            ],

            // ===================================================================
            // PECHO (PECH)
            // ===================================================================
            [
                'nombre' => 'Pecho — Máquinas',
                'descripcion' => 'Pecho en máquinas y mancuernas, base segura. 35–40 min.',
                'nivel' => 'basico',
                'grupos' => ['Pecho'],
                'ejercicios' => [
                    ['nombre' => 'Press de pecho en máquina', 'grupo_muscular' => 'Pecho', 'series' => 3, 'reps' => 12, 'descanso_segundos' => 75, 'peso_objetivo' => null, 'notas' => null],
                    ['nombre' => 'Press inclinado con mancuernas', 'grupo_muscular' => 'Pecho', 'series' => 3, 'reps' => 10, 'descanso_segundos' => 75, 'peso_objetivo' => null, 'notas' => 'Banco a 30°.'],
                    ['nombre' => 'Aperturas en peck-deck', 'grupo_muscular' => 'Pecho', 'series' => 3, 'reps' => 12, 'descanso_segundos' => 60, 'peso_objetivo' => null, 'notas' => null],
                    ['nombre' => 'Flexiones de brazos (rodillas o inclinadas)', 'grupo_muscular' => 'Pecho', 'series' => 3, 'reps' => 12, 'descanso_segundos' => 60, 'peso_objetivo' => null, 'notas' => 'Máximo con buena técnica.'],
                    ['nombre' => 'Cruce de poleas desde abajo', 'grupo_muscular' => 'Pecho', 'series' => 2, 'reps' => 15, 'descanso_segundos' => 45, 'peso_objetivo' => null, 'notas' => null],
                ],
            ],
            [
                'nombre' => 'Pecho — Banca y ángulos',
                'descripcion' => 'Banca con barra y ángulos variados de pecho. 50–55 min.',
                'nivel' => 'medio',
                'grupos' => ['Pecho'],
                'ejercicios' => [
                    ['nombre' => 'Press banca con barra', 'grupo_muscular' => 'Pecho', 'series' => 4, 'reps' => 8, 'descanso_segundos' => 120, 'peso_objetivo' => null, 'notas' => 'En rack con topes si no hay observador.'],
                    ['nombre' => 'Press inclinado con mancuernas', 'grupo_muscular' => 'Pecho', 'series' => 3, 'reps' => 10, 'descanso_segundos' => 90, 'peso_objetivo' => null, 'notas' => null],
                    ['nombre' => 'Fondos en paralelas (asistidos si hace falta)', 'grupo_muscular' => 'Pecho', 'series' => 3, 'reps' => 8, 'descanso_segundos' => 90, 'peso_objetivo' => null, 'notas' => 'Torso inclinado adelante.'],
                    ['nombre' => 'Cruce de poleas desde arriba', 'grupo_muscular' => 'Pecho', 'series' => 3, 'reps' => 12, 'descanso_segundos' => 60, 'peso_objetivo' => null, 'notas' => null],
                    ['nombre' => 'Peck-deck', 'grupo_muscular' => 'Pecho', 'series' => 3, 'reps' => 12, 'descanso_segundos' => 60, 'peso_objetivo' => null, 'notas' => null],
                    ['nombre' => 'Flexiones de brazos', 'grupo_muscular' => 'Pecho', 'series' => 1, 'reps' => 15, 'descanso_segundos' => 0, 'peso_objetivo' => null, 'notas' => 'Serie final al fallo técnico.'],
                ],
            ],
            [
                'nombre' => 'Pecho — Fuerza e intensidad',
                'descripcion' => 'Fuerza e intensidad para pecho, con superseries. 65–70 min.',
                'nivel' => 'avanzado',
                'grupos' => ['Pecho'],
                'ejercicios' => [
                    ['nombre' => 'Press banca con barra', 'grupo_muscular' => 'Pecho', 'series' => 5, 'reps' => 5, 'descanso_segundos' => 180, 'peso_objetivo' => null, 'notas' => 'RPE 8.'],
                    ['nombre' => 'Press inclinado con barra', 'grupo_muscular' => 'Pecho', 'series' => 4, 'reps' => 8, 'descanso_segundos' => 120, 'peso_objetivo' => null, 'notas' => null],
                    ['nombre' => 'Fondos lastrados', 'grupo_muscular' => 'Pecho', 'series' => 4, 'reps' => 8, 'descanso_segundos' => 90, 'peso_objetivo' => null, 'notas' => null],
                    ['nombre' => 'Press declinado en máquina', 'grupo_muscular' => 'Pecho', 'series' => 3, 'reps' => 10, 'descanso_segundos' => 75, 'peso_objetivo' => null, 'notas' => null],
                    ['nombre' => 'Cruce de poleas', 'grupo_muscular' => 'Pecho', 'series' => 3, 'reps' => 15, 'descanso_segundos' => 0, 'peso_objetivo' => null, 'notas' => 'Superserie con flexiones al máximo, sin descanso entre ambos (3 rondas).'],
                    ['nombre' => 'Flexiones de brazos', 'grupo_muscular' => 'Pecho', 'series' => 3, 'reps' => 15, 'descanso_segundos' => 90, 'peso_objetivo' => null, 'notas' => 'Segunda mitad de la superserie con cruce de poleas, al máximo.'],
                    ['nombre' => 'Peck-deck', 'grupo_muscular' => 'Pecho', 'series' => 2, 'reps' => 12, 'descanso_segundos' => 60, 'peso_objetivo' => null, 'notas' => 'Dropset de -25% en la última serie.'],
                ],
            ],

            // ===================================================================
            // ESPALDA (ESPA)
            // ===================================================================
            [
                'nombre' => 'Espalda — Poleas y máquinas',
                'descripcion' => 'Espalda en poleas y máquinas, técnica simple. 35–40 min.',
                'nivel' => 'basico',
                'grupos' => ['Espalda'],
                'ejercicios' => [
                    ['nombre' => 'Jalón al pecho en polea', 'grupo_muscular' => 'Espalda', 'series' => 3, 'reps' => 12, 'descanso_segundos' => 75, 'peso_objetivo' => null, 'notas' => null],
                    ['nombre' => 'Remo sentado en polea baja', 'grupo_muscular' => 'Espalda', 'series' => 3, 'reps' => 12, 'descanso_segundos' => 75, 'peso_objetivo' => null, 'notas' => 'Sin balancear el torso.'],
                    ['nombre' => 'Remo en máquina con apoyo pectoral', 'grupo_muscular' => 'Espalda', 'series' => 3, 'reps' => 12, 'descanso_segundos' => 75, 'peso_objetivo' => null, 'notas' => null],
                    ['nombre' => 'Pull-over en polea alta', 'grupo_muscular' => 'Espalda', 'series' => 2, 'reps' => 15, 'descanso_segundos' => 60, 'peso_objetivo' => null, 'notas' => null],
                    ['nombre' => 'Extensiones lumbares en banco romano', 'grupo_muscular' => 'Espalda', 'series' => 2, 'reps' => 12, 'descanso_segundos' => 60, 'peso_objetivo' => null, 'notas' => 'Sin hiperextender.'],
                ],
            ],
            [
                'nombre' => 'Espalda — Remos y dominadas',
                'descripcion' => 'Remos y dominadas para espalda media. 55–60 min.',
                'nivel' => 'medio',
                'grupos' => ['Espalda'],
                'ejercicios' => [
                    ['nombre' => 'Dominadas asistidas (o jalón agarre supino)', 'grupo_muscular' => 'Espalda', 'series' => 4, 'reps' => 8, 'descanso_segundos' => 120, 'peso_objetivo' => null, 'notas' => null],
                    ['nombre' => 'Remo con barra', 'grupo_muscular' => 'Espalda', 'series' => 4, 'reps' => 8, 'descanso_segundos' => 120, 'peso_objetivo' => null, 'notas' => 'Torso a 45°, espalda neutra.'],
                    ['nombre' => 'Remo con mancuerna a una mano', 'grupo_muscular' => 'Espalda', 'series' => 3, 'reps' => 10, 'descanso_segundos' => 75, 'peso_objetivo' => null, 'notas' => 'Por lado.'],
                    ['nombre' => 'Jalón en polea agarre neutro', 'grupo_muscular' => 'Espalda', 'series' => 3, 'reps' => 12, 'descanso_segundos' => 60, 'peso_objetivo' => null, 'notas' => null],
                    ['nombre' => 'Face pull en polea', 'grupo_muscular' => 'Espalda', 'series' => 3, 'reps' => 15, 'descanso_segundos' => 60, 'peso_objetivo' => null, 'notas' => null],
                    ['nombre' => 'Extensiones lumbares en banco romano', 'grupo_muscular' => 'Espalda', 'series' => 3, 'reps' => 12, 'descanso_segundos' => 60, 'peso_objetivo' => null, 'notas' => null],
                ],
            ],
            [
                'nombre' => 'Espalda — Fuerza y densidad',
                'descripcion' => 'Fuerza y densidad con peso muerto y dominadas lastradas. 70–75 min.',
                'nivel' => 'avanzado',
                'grupos' => ['Espalda'],
                'ejercicios' => [
                    ['nombre' => 'Peso muerto convencional', 'grupo_muscular' => 'Espalda', 'series' => 4, 'reps' => 5, 'descanso_segundos' => 180, 'peso_objetivo' => null, 'notas' => 'Técnica antes que carga.'],
                    ['nombre' => 'Dominadas lastradas', 'grupo_muscular' => 'Espalda', 'series' => 4, 'reps' => 6, 'descanso_segundos' => 120, 'peso_objetivo' => null, 'notas' => null],
                    ['nombre' => 'Remo con barra Pendlay', 'grupo_muscular' => 'Espalda', 'series' => 4, 'reps' => 8, 'descanso_segundos' => 120, 'peso_objetivo' => null, 'notas' => null],
                    ['nombre' => 'Remo en T o remo en máquina', 'grupo_muscular' => 'Espalda', 'series' => 3, 'reps' => 10, 'descanso_segundos' => 90, 'peso_objetivo' => null, 'notas' => null],
                    ['nombre' => 'Jalón agarre cerrado neutro', 'grupo_muscular' => 'Espalda', 'series' => 3, 'reps' => 10, 'descanso_segundos' => 75, 'peso_objetivo' => null, 'notas' => null],
                    ['nombre' => 'Pull-over en polea alta', 'grupo_muscular' => 'Espalda', 'series' => 3, 'reps' => 15, 'descanso_segundos' => 60, 'peso_objetivo' => null, 'notas' => 'Última serie en dropset.'],
                    ['nombre' => 'Encogimientos con mancuernas', 'grupo_muscular' => 'Espalda', 'series' => 3, 'reps' => 15, 'descanso_segundos' => 60, 'peso_objetivo' => null, 'notas' => 'Pausa de 1 s arriba.'],
                ],
            ],

            // ===================================================================
            // HOMBROS (HOMB)
            // ===================================================================
            [
                'nombre' => 'Hombros — Iniciación',
                'descripcion' => 'Hombros con máquinas, ideal para iniciar. 30–35 min.',
                'nivel' => 'basico',
                'grupos' => ['Hombro'],
                'ejercicios' => [
                    ['nombre' => 'Press de hombro en máquina', 'grupo_muscular' => 'Hombro', 'series' => 3, 'reps' => 12, 'descanso_segundos' => 75, 'peso_objetivo' => null, 'notas' => null],
                    ['nombre' => 'Elevaciones laterales con mancuernas', 'grupo_muscular' => 'Hombro', 'series' => 3, 'reps' => 12, 'descanso_segundos' => 60, 'peso_objetivo' => null, 'notas' => 'Carga liviana, sin impulso.'],
                    ['nombre' => 'Elevaciones frontales con mancuernas', 'grupo_muscular' => 'Hombro', 'series' => 2, 'reps' => 12, 'descanso_segundos' => 60, 'peso_objetivo' => null, 'notas' => null],
                    ['nombre' => 'Deltoides posterior en peck-deck invertido', 'grupo_muscular' => 'Hombro', 'series' => 3, 'reps' => 15, 'descanso_segundos' => 60, 'peso_objetivo' => null, 'notas' => null],
                    ['nombre' => 'Face pull en polea', 'grupo_muscular' => 'Hombro', 'series' => 2, 'reps' => 15, 'descanso_segundos' => 45, 'peso_objetivo' => null, 'notas' => null],
                ],
            ],
            [
                'nombre' => 'Hombros — Tres cabezas',
                'descripcion' => 'Trabajo de las tres cabezas del hombro. 45–50 min.',
                'nivel' => 'medio',
                'grupos' => ['Hombro'],
                'ejercicios' => [
                    ['nombre' => 'Press militar con mancuernas sentado', 'grupo_muscular' => 'Hombro', 'series' => 4, 'reps' => 8, 'descanso_segundos' => 90, 'peso_objetivo' => null, 'notas' => null],
                    ['nombre' => 'Elevaciones laterales con mancuernas', 'grupo_muscular' => 'Hombro', 'series' => 4, 'reps' => 12, 'descanso_segundos' => 60, 'peso_objetivo' => null, 'notas' => null],
                    ['nombre' => 'Press Arnold', 'grupo_muscular' => 'Hombro', 'series' => 3, 'reps' => 10, 'descanso_segundos' => 75, 'peso_objetivo' => null, 'notas' => null],
                    ['nombre' => 'Deltoides posterior en peck-deck invertido', 'grupo_muscular' => 'Hombro', 'series' => 3, 'reps' => 15, 'descanso_segundos' => 60, 'peso_objetivo' => null, 'notas' => null],
                    ['nombre' => 'Face pull en polea', 'grupo_muscular' => 'Hombro', 'series' => 3, 'reps' => 15, 'descanso_segundos' => 60, 'peso_objetivo' => null, 'notas' => null],
                    ['nombre' => 'Encogimientos con mancuernas', 'grupo_muscular' => 'Hombro', 'series' => 3, 'reps' => 12, 'descanso_segundos' => 60, 'peso_objetivo' => null, 'notas' => null],
                ],
            ],
            [
                'nombre' => 'Hombros — Volumen lateral',
                'descripcion' => 'Volumen lateral de hombro con dropsets. 60–65 min.',
                'nivel' => 'avanzado',
                'grupos' => ['Hombro'],
                'ejercicios' => [
                    ['nombre' => 'Press militar con barra de pie', 'grupo_muscular' => 'Hombro', 'series' => 5, 'reps' => 5, 'descanso_segundos' => 150, 'peso_objetivo' => null, 'notas' => 'Core apretado, sin arquear lumbar.'],
                    ['nombre' => 'Press con mancuernas sentado', 'grupo_muscular' => 'Hombro', 'series' => 3, 'reps' => 8, 'descanso_segundos' => 90, 'peso_objetivo' => null, 'notas' => null],
                    ['nombre' => 'Elevaciones laterales con mancuernas', 'grupo_muscular' => 'Hombro', 'series' => 4, 'reps' => 12, 'descanso_segundos' => 60, 'peso_objetivo' => null, 'notas' => 'Última serie con triple dropset.'],
                    ['nombre' => 'Elevación lateral en polea a una mano', 'grupo_muscular' => 'Hombro', 'series' => 3, 'reps' => 15, 'descanso_segundos' => 45, 'peso_objetivo' => null, 'notas' => 'Por lado.'],
                    ['nombre' => 'Deltoides posterior en poleas cruzadas', 'grupo_muscular' => 'Hombro', 'series' => 3, 'reps' => 15, 'descanso_segundos' => 60, 'peso_objetivo' => null, 'notas' => null],
                    ['nombre' => 'Face pull en polea', 'grupo_muscular' => 'Hombro', 'series' => 3, 'reps' => 20, 'descanso_segundos' => 45, 'peso_objetivo' => null, 'notas' => null],
                    ['nombre' => 'Encogimientos con barra', 'grupo_muscular' => 'Hombro', 'series' => 4, 'reps' => 10, 'descanso_segundos' => 60, 'peso_objetivo' => null, 'notas' => null],
                ],
            ],

            // ===================================================================
            // BRAZOS (BRAZ)
            // ===================================================================
            [
                'nombre' => 'Brazos — Iniciación',
                'descripcion' => 'Bíceps y tríceps con máquinas y mancuernas. 30–35 min.',
                'nivel' => 'basico',
                'grupos' => ['Brazos'],
                'ejercicios' => [
                    ['nombre' => 'Curl con barra Z', 'grupo_muscular' => 'Brazos', 'series' => 3, 'reps' => 12, 'descanso_segundos' => 60, 'peso_objetivo' => null, 'notas' => 'Codos fijos al costado.'],
                    ['nombre' => 'Curl martillo con mancuernas', 'grupo_muscular' => 'Brazos', 'series' => 3, 'reps' => 12, 'descanso_segundos' => 60, 'peso_objetivo' => null, 'notas' => null],
                    ['nombre' => 'Extensión de tríceps en polea con cuerda', 'grupo_muscular' => 'Brazos', 'series' => 3, 'reps' => 12, 'descanso_segundos' => 60, 'peso_objetivo' => null, 'notas' => null],
                    ['nombre' => 'Press francés con barra Z', 'grupo_muscular' => 'Brazos', 'series' => 3, 'reps' => 12, 'descanso_segundos' => 60, 'peso_objetivo' => null, 'notas' => null],
                    ['nombre' => 'Curl en banco predicador (máquina)', 'grupo_muscular' => 'Brazos', 'series' => 2, 'reps' => 15, 'descanso_segundos' => 45, 'peso_objetivo' => null, 'notas' => null],
                ],
            ],
            [
                'nombre' => 'Brazos — Bíceps y tríceps',
                'descripcion' => 'Bíceps y tríceps con barra y superserie final. 45–50 min.',
                'nivel' => 'medio',
                'grupos' => ['Brazos'],
                'ejercicios' => [
                    ['nombre' => 'Curl con barra recta', 'grupo_muscular' => 'Brazos', 'series' => 4, 'reps' => 8, 'descanso_segundos' => 75, 'peso_objetivo' => null, 'notas' => null],
                    ['nombre' => 'Curl inclinado con mancuernas', 'grupo_muscular' => 'Brazos', 'series' => 3, 'reps' => 10, 'descanso_segundos' => 60, 'peso_objetivo' => null, 'notas' => 'Banco a 45°.'],
                    ['nombre' => 'Curl martillo', 'grupo_muscular' => 'Brazos', 'series' => 3, 'reps' => 12, 'descanso_segundos' => 60, 'peso_objetivo' => null, 'notas' => null],
                    ['nombre' => 'Press cerrado en banca', 'grupo_muscular' => 'Brazos', 'series' => 3, 'reps' => 10, 'descanso_segundos' => 90, 'peso_objetivo' => null, 'notas' => null],
                    ['nombre' => 'Press francés con barra Z', 'grupo_muscular' => 'Brazos', 'series' => 4, 'reps' => 10, 'descanso_segundos' => 60, 'peso_objetivo' => null, 'notas' => null],
                    ['nombre' => 'Extensión de tríceps en polea con cuerda', 'grupo_muscular' => 'Brazos', 'series' => 3, 'reps' => 12, 'descanso_segundos' => 60, 'peso_objetivo' => null, 'notas' => null],
                    ['nombre' => 'Curl en polea', 'grupo_muscular' => 'Brazos', 'series' => 2, 'reps' => 15, 'descanso_segundos' => 0, 'peso_objetivo' => null, 'notas' => 'Superserie con tríceps en polea, sin descanso entre ambos (2 rondas).'],
                    ['nombre' => 'Tríceps en polea', 'grupo_muscular' => 'Brazos', 'series' => 2, 'reps' => 15, 'descanso_segundos' => 60, 'peso_objetivo' => null, 'notas' => 'Segunda mitad de la superserie con curl en polea.'],
                ],
            ],
            [
                'nombre' => 'Brazos — Superseries',
                'descripcion' => 'Superseries intensas de brazos por bloques. 55–60 min.',
                'nivel' => 'avanzado',
                'grupos' => ['Brazos'],
                'ejercicios' => [
                    ['nombre' => 'Curl con barra', 'grupo_muscular' => 'Brazos', 'series' => 4, 'reps' => 8, 'descanso_segundos' => 0, 'peso_objetivo' => null, 'notas' => 'Superserie con press cerrado en banca, sin descanso entre ambos.'],
                    ['nombre' => 'Press cerrado en banca', 'grupo_muscular' => 'Brazos', 'series' => 4, 'reps' => 8, 'descanso_segundos' => 120, 'peso_objetivo' => null, 'notas' => 'Segunda mitad de la superserie con curl con barra.'],
                    ['nombre' => 'Curl inclinado con mancuernas', 'grupo_muscular' => 'Brazos', 'series' => 3, 'reps' => 10, 'descanso_segundos' => 0, 'peso_objetivo' => null, 'notas' => 'Superserie con press francés, sin descanso entre ambos.'],
                    ['nombre' => 'Press francés con barra Z', 'grupo_muscular' => 'Brazos', 'series' => 3, 'reps' => 10, 'descanso_segundos' => 90, 'peso_objetivo' => null, 'notas' => 'Segunda mitad de la superserie con curl inclinado.'],
                    ['nombre' => 'Curl martillo con cuerda', 'grupo_muscular' => 'Brazos', 'series' => 3, 'reps' => 12, 'descanso_segundos' => 0, 'peso_objetivo' => null, 'notas' => 'Superserie con extensión de tríceps en polea, sin descanso entre ambos.'],
                    ['nombre' => 'Extensión de tríceps en polea', 'grupo_muscular' => 'Brazos', 'series' => 3, 'reps' => 12, 'descanso_segundos' => 75, 'peso_objetivo' => null, 'notas' => 'Segunda mitad de la superserie con curl martillo con cuerda.'],
                    ['nombre' => 'Curl 21s con barra Z', 'grupo_muscular' => 'Brazos', 'series' => 2, 'reps' => 21, 'descanso_segundos' => 75, 'peso_objetivo' => null, 'notas' => '21 repeticiones: 7 parciales inferiores + 7 parciales superiores + 7 completas.'],
                    ['nombre' => 'Tríceps en máquina', 'grupo_muscular' => 'Brazos', 'series' => 2, 'reps' => 12, 'descanso_segundos' => 60, 'peso_objetivo' => null, 'notas' => 'Incluye dropset al final de cada serie.'],
                ],
            ],

            // ===================================================================
            // CORE
            // ===================================================================
            [
                'nombre' => 'Core — Estabilidad',
                'descripcion' => 'Estabilidad de core, ideal para empezar. 20 min.',
                'nivel' => 'basico',
                'grupos' => ['Core'],
                'ejercicios' => [
                    ['nombre' => 'Plancha frontal', 'grupo_muscular' => 'Core', 'series' => 3, 'reps' => 1, 'descanso_segundos' => 45, 'peso_objetivo' => null, 'notas' => 'Sostener 20-30 segundos.'],
                    ['nombre' => 'Puente de glúteos en colchoneta', 'grupo_muscular' => 'Core', 'series' => 3, 'reps' => 15, 'descanso_segundos' => 45, 'peso_objetivo' => null, 'notas' => null],
                    ['nombre' => 'Crunch abdominal', 'grupo_muscular' => 'Core', 'series' => 3, 'reps' => 15, 'descanso_segundos' => 45, 'peso_objetivo' => null, 'notas' => 'Sin tirar del cuello.'],
                    ['nombre' => 'Dead bug', 'grupo_muscular' => 'Core', 'series' => 3, 'reps' => 10, 'descanso_segundos' => 45, 'peso_objetivo' => null, 'notas' => 'Por lado. Lumbar pegada al suelo.'],
                    ['nombre' => 'Plancha lateral', 'grupo_muscular' => 'Core', 'series' => 2, 'reps' => 1, 'descanso_segundos' => 30, 'peso_objetivo' => null, 'notas' => 'Sostener 20 segundos por lado.'],
                ],
            ],
            [
                'nombre' => 'Core — Fuerza abdominal',
                'descripcion' => 'Fuerza abdominal con carga y antirrotación. 25–30 min.',
                'nivel' => 'medio',
                'grupos' => ['Core'],
                'ejercicios' => [
                    ['nombre' => 'Plancha frontal', 'grupo_muscular' => 'Core', 'series' => 3, 'reps' => 1, 'descanso_segundos' => 45, 'peso_objetivo' => null, 'notas' => 'Sostener 45-60 segundos.'],
                    ['nombre' => 'Elevación de piernas en paralelas', 'grupo_muscular' => 'Core', 'series' => 3, 'reps' => 12, 'descanso_segundos' => 60, 'peso_objetivo' => null, 'notas' => 'Sin balanceo.'],
                    ['nombre' => 'Crunch en polea alta arrodillado', 'grupo_muscular' => 'Core', 'series' => 3, 'reps' => 15, 'descanso_segundos' => 60, 'peso_objetivo' => null, 'notas' => null],
                    ['nombre' => 'Giro ruso con disco', 'grupo_muscular' => 'Core', 'series' => 3, 'reps' => 20, 'descanso_segundos' => 45, 'peso_objetivo' => null, 'notas' => '20 repeticiones totales.'],
                    ['nombre' => 'Plancha lateral con elevación de cadera', 'grupo_muscular' => 'Core', 'series' => 3, 'reps' => 12, 'descanso_segundos' => 45, 'peso_objetivo' => null, 'notas' => 'Por lado.'],
                    ['nombre' => 'Rueda abdominal de rodillas', 'grupo_muscular' => 'Core', 'series' => 3, 'reps' => 8, 'descanso_segundos' => 60, 'peso_objetivo' => null, 'notas' => null],
                ],
            ],
            [
                'nombre' => 'Core — Avanzado',
                'descripcion' => 'Core avanzado con colgado en barra y carga. 35–40 min.',
                'nivel' => 'avanzado',
                'grupos' => ['Core'],
                'ejercicios' => [
                    ['nombre' => 'Rueda abdominal (rodillas o de pie)', 'grupo_muscular' => 'Core', 'series' => 4, 'reps' => 8, 'descanso_segundos' => 75, 'peso_objetivo' => null, 'notas' => null],
                    ['nombre' => 'Elevación de piernas colgado en barra', 'grupo_muscular' => 'Core', 'series' => 4, 'reps' => 10, 'descanso_segundos' => 75, 'peso_objetivo' => null, 'notas' => 'Progresar a toes-to-bar.'],
                    ['nombre' => 'Crunch en polea alta', 'grupo_muscular' => 'Core', 'series' => 4, 'reps' => 15, 'descanso_segundos' => 60, 'peso_objetivo' => null, 'notas' => 'Tempo 2-1-2.'],
                    ['nombre' => 'Pallof press en polea', 'grupo_muscular' => 'Core', 'series' => 3, 'reps' => 12, 'descanso_segundos' => 60, 'peso_objetivo' => null, 'notas' => 'Por lado. Antirrotación, sin girar la cadera.'],
                    ['nombre' => 'Plancha lastrada con disco', 'grupo_muscular' => 'Core', 'series' => 3, 'reps' => 1, 'descanso_segundos' => 60, 'peso_objetivo' => null, 'notas' => 'Sostener 45 segundos con disco sobre la espalda.'],
                    ['nombre' => 'Limpiaparabrisas (windshield wipers)', 'grupo_muscular' => 'Core', 'series' => 3, 'reps' => 8, 'descanso_segundos' => 60, 'peso_objetivo' => null, 'notas' => null],
                    ['nombre' => 'Farmer carry con mancuernas', 'grupo_muscular' => 'Core', 'series' => 3, 'reps' => 1, 'descanso_segundos' => 60, 'peso_objetivo' => null, 'notas' => 'Caminar 40 metros por serie.'],
                ],
            ],
        ];
    }

    private function formatDuration(?int $seconds): ?string
    {
        if (! $seconds || $seconds <= 0) {
            return null;
        }

        $minutes = (int) round($seconds / 60);
        if ($minutes < 60) {
            return $minutes . ' min';
        }

        $hours = intdiv($minutes, 60);
        $remainingMinutes = $minutes % 60;

        return $remainingMinutes > 0 ? $hours . ' h ' . $remainingMinutes . ' min' : $hours . ' h';
    }
}
