<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EjerciciosVideos;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EjerciciosVideosController extends Controller
{
    public function clienteIndex(Request $request): JsonResponse
    {
        $cliente = $this->getClientePorUsuario($request->user());

        if (! $cliente) {
            return response()->json(['message' => 'Perfil de cliente no encontrado.'], 404);
        }

        $ejerciciosAgenda = DB::table('agendas_ejercicios')
            ->join('agendas', 'agendas.id', '=', 'agendas_ejercicios.id_agenda')
            ->where('agendas.id_cliente', $cliente->id)
            ->whereNotNull('agendas.fecha_inicio')
            ->distinct()
            ->pluck('agendas_ejercicios.id_ejercicio');

        $ejerciciosOpenGymRutinas = DB::table('open_gym_rutina_ejercicios')
            ->join('open_gym_rutinas', 'open_gym_rutinas.id', '=', 'open_gym_rutina_ejercicios.id_rutina')
            ->where('open_gym_rutinas.id_cliente', $cliente->id)
            ->whereNotNull('open_gym_rutina_ejercicios.id_ejercicio')
            ->distinct()
            ->pluck('open_gym_rutina_ejercicios.id_ejercicio');

        $ejerciciosOpenGymEntrenamientos = DB::table('open_gym_entrenamiento_series')
            ->join('open_gym_entrenamientos', 'open_gym_entrenamientos.id', '=', 'open_gym_entrenamiento_series.id_entrenamiento')
            ->where('open_gym_entrenamientos.id_cliente', $cliente->id)
            ->whereNotNull('open_gym_entrenamiento_series.id_ejercicio')
            ->distinct()
            ->pluck('open_gym_entrenamiento_series.id_ejercicio');

        $ejerciciosRealizados = $ejerciciosAgenda
            ->merge($ejerciciosOpenGymRutinas)
            ->merge($ejerciciosOpenGymEntrenamientos)
            ->filter(fn($idEjercicio) => $idEjercicio !== null)
            ->unique()
            ->values();

        if ($ejerciciosRealizados->isEmpty()) {
            return response()->json(['videos' => []]);
        }

        $videos = EjerciciosVideos::query()
            ->join('ejercicios', 'ejercicios.id', '=', 'ejercicios_videos.id_ejercicio')
            ->whereIn('ejercicios_videos.id_ejercicio', $ejerciciosRealizados)
            ->orderBy('ejercicios.nombre')
            ->orderBy('ejercicios_videos.orden')
            ->orderBy('ejercicios_videos.archivo')
            ->get([
                'ejercicios_videos.id',
                'ejercicios_videos.id_ejercicio',
                'ejercicios_videos.archivo',
                'ejercicios_videos.orden',
                'ejercicios.nombre as ejercicio_nombre',
                'ejercicios.slug as ejercicio_slug',
                'ejercicios.descripcion as ejercicio_descripcion',
            ])
            ->map(fn($video) => [
                'id' => (int) $video->id,
                'id_ejercicio' => (int) $video->id_ejercicio,
                'nombre' => $video->ejercicio_nombre,
                'slug' => $video->ejercicio_slug,
                'descripcion' => $video->ejercicio_descripcion,
                'archivo' => $video->archivo,
                'src' => '/videos/' . $video->archivo,
                'orden' => (int) $video->orden,
            ])
            ->values();

        return response()->json(['videos' => $videos]);
    }

    private function getClientePorUsuario(object $user): ?object
    {
        $cliente = DB::table('clientes')->where('id_usuario', $user->id)->first();

        if (! $cliente && isset($user->id_cliente) && $user->id_cliente) {
            $cliente = DB::table('clientes')->where('id', $user->id_cliente)->first();
        }

        return $cliente;
    }
}
