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
                $group = $exercise->ejercicio?->tipo?->grupoMuscular;
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
            $group = $exercise->ejercicio?->tipo?->grupoMuscular;

            return [
                'id' => (int) $exercise->id,
                'id_ejercicio' => $exercise->id_ejercicio ? (int) $exercise->id_ejercicio : null,
                'nombre' => $exercise->ejercicio?->nombre ?? $exercise->nombre_personalizado ?? 'Ejercicio personalizado',
                'grupo_muscular' => $group?->nombre ?? 'Full Body',
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

    private function buildRoutineMuscleBreakdown(Collection $exercises): array
    {
        $totalSeries = max(1, (int) $exercises->sum('series'));

        return $exercises
            ->groupBy(function (OpenGymRoutineExercise $exercise) {
                $group = $exercise->ejercicio?->tipo?->grupoMuscular;
                return $group?->nombre ?? 'Full Body';
            })
            ->map(function (Collection $groupedExercises, string $name) use ($totalSeries) {
                $first = $groupedExercises->first();
                $group = $first?->ejercicio?->tipo?->grupoMuscular;
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

    private function buildSuggestedTemplates(): array
    {
        return [
            [
                'nombre' => 'Pierna Fuerte',
                'descripcion' => 'Base para cuadriceps, gluteos y femorales.',
                'grupos' => ['Pierna', 'Gluteos'],
                'ejercicios' => [
                    [
                        'nombre' => 'Sentadilla libre',
                        'grupo_muscular' => 'Pierna',
                        'series' => 4,
                        'reps' => 8,
                        'descanso_segundos' => 90,
                        'peso_objetivo' => null,
                        'notas' => 'Controla la bajada y mantén el core firme.',
                    ],
                    [
                        'nombre' => 'Peso muerto rumano',
                        'grupo_muscular' => 'Gluteos',
                        'series' => 4,
                        'reps' => 10,
                        'descanso_segundos' => 75,
                        'peso_objetivo' => null,
                        'notas' => 'Busca tensión en femorales durante todo el recorrido.',
                    ],
                    [
                        'nombre' => 'Zancadas caminando',
                        'grupo_muscular' => 'Pierna',
                        'series' => 3,
                        'reps' => 12,
                        'descanso_segundos' => 60,
                        'peso_objetivo' => null,
                        'notas' => 'Mantén pasos largos y estables.',
                    ],
                ],
            ],
            [
                'nombre' => 'Push',
                'descripcion' => 'Empuje para pecho, hombro y triceps.',
                'grupos' => ['Pecho', 'Hombro', 'Brazos'],
                'ejercicios' => [
                    [
                        'nombre' => 'Press de banca',
                        'grupo_muscular' => 'Pecho',
                        'series' => 4,
                        'reps' => 8,
                        'descanso_segundos' => 90,
                        'peso_objetivo' => null,
                        'notas' => 'Pausa breve en el pecho y sube con control.',
                    ],
                    [
                        'nombre' => 'Press militar',
                        'grupo_muscular' => 'Hombro',
                        'series' => 4,
                        'reps' => 10,
                        'descanso_segundos' => 75,
                        'peso_objetivo' => null,
                        'notas' => 'Evita arquear la espalda en exceso.',
                    ],
                    [
                        'nombre' => 'Fondos en paralelas',
                        'grupo_muscular' => 'Brazos',
                        'series' => 3,
                        'reps' => 12,
                        'descanso_segundos' => 60,
                        'peso_objetivo' => null,
                        'notas' => 'Mantén hombros estables y codos cerca del cuerpo.',
                    ],
                ],
            ],
            [
                'nombre' => 'Cardio + Core',
                'descripcion' => 'Circuito corto para cardio y abdomen.',
                'grupos' => ['Cardio', 'Core'],
                'ejercicios' => [
                    [
                        'nombre' => 'Remo ergómetro',
                        'grupo_muscular' => 'Cardio',
                        'series' => 3,
                        'reps' => 8,
                        'descanso_segundos' => 45,
                        'peso_objetivo' => null,
                        'notas' => 'Trabaja por intervalos intensos de un minuto.',
                    ],
                    [
                        'nombre' => 'Plancha frontal',
                        'grupo_muscular' => 'Core',
                        'series' => 4,
                        'reps' => 1,
                        'descanso_segundos' => 30,
                        'peso_objetivo' => null,
                        'notas' => 'Sostén entre 30 y 45 segundos por serie.',
                    ],
                    [
                        'nombre' => 'Mountain climbers',
                        'grupo_muscular' => 'Core',
                        'series' => 3,
                        'reps' => 20,
                        'descanso_segundos' => 30,
                        'peso_objetivo' => null,
                        'notas' => 'Mantén una cadencia constante sin perder técnica.',
                    ],
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
