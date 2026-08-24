<?php

namespace App\Http\Controllers;

use App\Services\OpenGym\OpenGymService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class OpenGymWebController extends Controller
{
    public function __construct(private readonly OpenGymService $service) {}

    public function index(): View
    {
        $user = $this->authorizeOpenGym();
        $data = $this->service->listRoutines($user);
        $activeWorkout = $this->service->getActiveWorkout($user)['entrenamiento'];

        return view('open-gym.index', [
            'rutinas' => $data['rutinas'],
            'plantillas' => $data['plantillas'],
            'resumen' => $data['resumen'],
            'activeWorkout' => $activeWorkout,
        ]);
    }

    public function create(): View
    {
        $user = $this->authorizeOpenGym();
        $catalog = $this->service->listExerciseCatalog($user)['ejercicios'];

        return view('open-gym.editor', [
            'mode' => 'create',
            'submitRoute' => route('open-gym.store'),
            'routine' => null,
            'catalog' => $catalog,
            'initialExercises' => old('ejercicios', []),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $this->authorizeOpenGym();
        $payload = $this->validateRoutinePayload($request);
        $this->service->saveRoutine($user, $payload);

        return redirect()->route('open-gym.index')->with('success', 'Rutina creada correctamente.');
    }

    public function edit(int $id): View
    {
        $user = $this->authorizeOpenGym();
        $routine = $this->service->getRoutine($user, $id)['rutina'];
        $catalog = $this->service->listExerciseCatalog($user)['ejercicios'];

        return view('open-gym.editor', [
            'mode' => 'edit',
            'submitRoute' => route('open-gym.update', $id),
            'routine' => $routine,
            'catalog' => $catalog,
            'initialExercises' => old('ejercicios', collect($routine['ejercicios'] ?? [])->map(function (array $exercise) {
                return [
                    'id_ejercicio' => $exercise['id_ejercicio'] ?? null,
                    'nombre' => $exercise['nombre'] ?? '',
                    'grupo_muscular' => $exercise['grupo_muscular'] ?? 'Full Body',
                    'series' => $exercise['series'] ?? 3,
                    'reps' => $exercise['reps'] ?? 10,
                    'descanso_segundos' => $exercise['descanso_segundos'] ?? 60,
                    'peso_objetivo' => $exercise['peso_objetivo'] ?? null,
                    'notas' => $exercise['notas'] ?? '',
                ];
            })->values()->all()),
        ]);
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $user = $this->authorizeOpenGym();
        $payload = $this->validateRoutinePayload($request);
        $this->service->saveRoutine($user, $payload, $id);

        return redirect()->route('open-gym.index')->with('success', 'Rutina actualizada correctamente.');
    }

    public function duplicate(int $id): RedirectResponse
    {
        $user = $this->authorizeOpenGym();
        $this->service->duplicateRoutine($user, $id);

        return redirect()->route('open-gym.index')->with('success', 'Rutina duplicada correctamente.');
    }

    public function destroy(int $id): RedirectResponse
    {
        $user = $this->authorizeOpenGym();
        $this->service->deleteRoutine($user, $id);

        return redirect()->route('open-gym.index')->with('success', 'Rutina eliminada correctamente.');
    }

    public function progress(): View
    {
        $user = $this->authorizeOpenGym();
        $progress = $this->service->progress($user);

        return view('open-gym.progress', compact('progress'));
    }

    public function history(Request $request): View
    {
        $user = $this->authorizeOpenGym();
        $history = $this->service->history($user, $request->query('mes'))['historial'];

        return view('open-gym.history', [
            'history' => $history,
            'selectedMonth' => $request->query('mes'),
        ]);
    }

    public function startWorkout(int $routineId): RedirectResponse
    {
        $user = $this->authorizeOpenGym();
        $result = $this->service->startWorkout($user, $routineId);

        return redirect()->route('open-gym.workouts.show', $result['entrenamiento']['id']);
    }

    public function showWorkout(int $id): View
    {
        $user = $this->authorizeOpenGym();
        $workout = $this->service->getWorkout($user, $id)['entrenamiento'];

        return view('open-gym.workout', compact('workout'));
    }

    public function updateWorkoutSet(Request $request, int $id, int $setId): RedirectResponse
    {
        $user = $this->authorizeOpenGym();
        $validated = $request->validate([
            'reps_realizadas' => ['nullable', 'integer', 'min:0', 'max:500'],
            'peso_real' => ['nullable', 'numeric', 'min:0', 'max:5000'],
            'descanso_segundos' => ['nullable', 'integer', 'min:0', 'max:3600'],
        ]);

        $this->service->updateWorkoutSet($user, $id, $setId, [
            'reps_realizadas' => $validated['reps_realizadas'] ?? null,
            'peso_real' => $validated['peso_real'] ?? null,
            'descanso_segundos' => $validated['descanso_segundos'] ?? null,
            'completado' => true,
        ]);

        return redirect()->route('open-gym.workouts.show', $id)->with('success', 'Serie actualizada correctamente.');
    }

    public function finishWorkout(Request $request, int $id): RedirectResponse
    {
        $user = $this->authorizeOpenGym();
        $validated = $request->validate([
            'dificultad_percibida' => ['nullable', 'string', 'in:facil,normal,dificil,maximo'],
        ]);

        $result = $this->service->finishWorkout($user, $id, $validated);

        return redirect()->route('open-gym.progress')
            ->with('success', 'Entrenamiento finalizado correctamente.')
            ->with('open_gym_summary', $result['resumen']);
    }

    private function authorizeOpenGym()
    {
        $user = Auth::user();

        if (! $user || (int) $user->id_tipo_usuario !== OpenGymService::OPEN_GYM_TYPE_ID) {
            abort(403, 'No tienes acceso al modulo Open Gym.');
        }

        return $user;
    }

    private function validateRoutinePayload(Request $request): array
    {
        $payload = $request->validate([
            'nombre' => ['required', 'string', 'max:120'],
            'descripcion' => ['nullable', 'string', 'max:1000'],
            'frecuencia_semanal' => ['nullable', 'integer', 'min:1', 'max:14'],
            'duracion_estimada_minutos' => ['nullable', 'integer', 'min:10', 'max:300'],
            'calorias_estimadas' => ['nullable', 'integer', 'min:0', 'max:10000'],
            'ejercicios' => ['required', 'array', 'min:1'],
            'ejercicios.*.id_ejercicio' => ['nullable', 'integer', 'exists:ejercicios,id'],
            'ejercicios.*.nombre' => ['nullable', 'string', 'max:120'],
            'ejercicios.*.series' => ['required', 'integer', 'min:1', 'max:20'],
            'ejercicios.*.reps' => ['required', 'integer', 'min:1', 'max:100'],
            'ejercicios.*.descanso_segundos' => ['nullable', 'integer', 'min:0', 'max:3600'],
            'ejercicios.*.peso_objetivo' => ['nullable', 'numeric', 'min:0', 'max:5000'],
            'ejercicios.*.notas' => ['nullable', 'string', 'max:500'],
        ]);

        $missingName = collect($payload['ejercicios'] ?? [])->contains(function (array $exercise) {
            return empty($exercise['id_ejercicio']) && trim((string) ($exercise['nombre'] ?? '')) === '';
        });

        if ($missingName) {
            throw ValidationException::withMessages([
                'ejercicios' => ['Cada ejercicio debe seleccionar un ejercicio del catalogo o definir un nombre personalizado.'],
            ]);
        }

        return $payload;
    }
}
