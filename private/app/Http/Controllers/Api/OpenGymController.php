<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\OpenGym\OpenGymService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OpenGymController extends Controller
{
    public function __construct(private readonly OpenGymService $service) {}

    public function routinesIndex(Request $request): JsonResponse
    {
        return response()->json($this->service->listRoutines($request->user()));
    }

    public function catalogExercises(Request $request): JsonResponse
    {
        return response()->json($this->service->listExerciseCatalog($request->user(), $request->query('q')));
    }

    public function routineShow(Request $request, int $id): JsonResponse
    {
        return response()->json($this->service->getRoutine($request->user(), $id));
    }

    public function routineStore(Request $request): JsonResponse
    {
        $payload = $this->validateRoutinePayload($request);

        return response()->json($this->service->saveRoutine($request->user(), $payload), 201);
    }

    public function routineUpdate(Request $request, int $id): JsonResponse
    {
        $payload = $this->validateRoutinePayload($request);

        return response()->json($this->service->saveRoutine($request->user(), $payload, $id));
    }

    public function routineDuplicate(Request $request, int $id): JsonResponse
    {
        return response()->json($this->service->duplicateRoutine($request->user(), $id));
    }

    public function routineDestroy(Request $request, int $id): JsonResponse
    {
        $this->service->deleteRoutine($request->user(), $id);

        return response()->json(['message' => 'Rutina eliminada correctamente.']);
    }

    public function activeWorkout(Request $request): JsonResponse
    {
        return response()->json($this->service->getActiveWorkout($request->user()));
    }

    public function workoutStart(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'id_rutina' => ['required', 'integer'],
        ]);

        return response()->json($this->service->startWorkout($request->user(), (int) $validated['id_rutina']), 201);
    }

    public function workoutShow(Request $request, int $id): JsonResponse
    {
        return response()->json($this->service->getWorkout($request->user(), $id));
    }

    public function workoutSetUpdate(Request $request, int $id, int $setId): JsonResponse
    {
        $validated = $request->validate([
            'reps_realizadas' => ['nullable', 'integer', 'min:0', 'max:500'],
            'peso_real' => ['nullable', 'numeric', 'min:0', 'max:5000'],
            'descanso_segundos' => ['nullable', 'integer', 'min:0', 'max:3600'],
            'completado' => ['nullable', 'boolean'],
        ]);

        return response()->json($this->service->updateWorkoutSet($request->user(), $id, $setId, $validated));
    }

    public function workoutFinish(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'dificultad_percibida' => ['nullable', 'string', 'in:facil,normal,dificil,maximo'],
        ]);

        return response()->json($this->service->finishWorkout($request->user(), $id, $validated));
    }

    public function progress(Request $request): JsonResponse
    {
        return response()->json($this->service->progress($request->user()));
    }

    public function history(Request $request): JsonResponse
    {
        return response()->json($this->service->history($request->user(), $request->query('mes')));
    }

    private function validateRoutinePayload(Request $request): array
    {
        return $request->validate([
            'nombre' => ['required', 'string', 'max:120'],
            'descripcion' => ['nullable', 'string', 'max:1000'],
            'frecuencia_semanal' => ['nullable', 'integer', 'min:1', 'max:14'],
            'duracion_estimada_minutos' => ['nullable', 'integer', 'min:10', 'max:300'],
            'calorias_estimadas' => ['nullable', 'integer', 'min:0', 'max:10000'],
            'activo' => ['nullable', 'boolean'],
            'ejercicios' => ['required', 'array', 'min:1'],
            'ejercicios.*.id_ejercicio' => ['nullable', 'integer', 'exists:ejercicios,id'],
            'ejercicios.*.nombre' => ['nullable', 'string', 'max:120'],
            'ejercicios.*.series' => ['required', 'integer', 'min:1', 'max:20'],
            'ejercicios.*.reps' => ['required', 'integer', 'min:1', 'max:100'],
            'ejercicios.*.descanso_segundos' => ['nullable', 'integer', 'min:0', 'max:3600'],
            'ejercicios.*.peso_objetivo' => ['nullable', 'numeric', 'min:0', 'max:5000'],
            'ejercicios.*.notas' => ['nullable', 'string', 'max:500'],
        ]);
    }
}
