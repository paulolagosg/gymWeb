<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EntrenadorPerfil;
use App\Models\EntrenadoresCursos;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class EntrenadorPerfilController extends Controller
{
    private const TIPO_CURSO_LABELS = [
        'curso' => 'Curso',
        'certificacion' => 'Certificación',
        'otro' => 'Otro',
    ];

    private const MODALIDAD_LABELS = [
        1 => 'Presencial',
        2 => 'On-line',
        3 => 'Híbrido',
    ];

    public function show(Request $request): JsonResponse
    {
        $entrenador = $this->resolveAuthenticatedTrainer($request);
        if ($entrenador instanceof JsonResponse) {
            return $entrenador;
        }

        return response()->json([
            'perfil' => $this->buildTrainerProfileResponse((int) $entrenador->id),
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $entrenador = $this->resolveAuthenticatedTrainer($request);
        if ($entrenador instanceof JsonResponse) {
            return $entrenador;
        }

        $validated = $request->validate([
            'instagram' => 'nullable|string|max:150',
            'foto' => 'nullable|image|max:5120',
            'remove_foto' => 'nullable|boolean',
        ]);

        $perfil = EntrenadorPerfil::firstOrNew([
            'id_entrenador' => (int) $entrenador->id,
        ]);

        $currentPhotoPath = $perfil->foto ?? null;

        if ($request->boolean('remove_foto')) {
            $this->deleteTrainerPhoto($currentPhotoPath);
            $perfil->foto = null;
            $currentPhotoPath = null;
        }

        $photoFile = $this->resolveTrainerPhotoUpload($request);
        if ($photoFile) {
            $this->deleteTrainerPhoto($currentPhotoPath);

            $photoFilename = sprintf(
                'entrenador-%d-%s-%s.%s',
                (int) $entrenador->id,
                now()->format('YmdHis'),
                Str::lower(Str::random(8)),
                $photoFile->getClientOriginalExtension()
            );

            $perfil->foto = $photoFile->storeAs('entrenadores/perfiles', $photoFilename, 'public');
        }

        $perfil->instagram = $this->normalizeInstagram($validated['instagram'] ?? null);
        $perfil->save();

        return response()->json([
            'message' => 'Perfil actualizado correctamente.',
            'perfil' => $this->buildTrainerProfileResponse((int) $entrenador->id),
        ]);
    }

    public function storeCurso(Request $request): JsonResponse
    {
        $entrenador = $this->resolveAuthenticatedTrainer($request);
        if ($entrenador instanceof JsonResponse) {
            return $entrenador;
        }

        $validated = $this->validateCoursePayload($request);

        $curso = EntrenadoresCursos::create([
            'id_entrenador' => (int) $entrenador->id,
            'curso' => $validated['curso'],
            'tipo' => $validated['tipo'],
            'fecha_inicio' => $validated['fecha_inicio'],
            'fecha_fin' => $validated['fecha_fin'],
            'institucion' => $validated['institucion'],
            'modalidad' => (int) $validated['modalidad'],
            'slug' => $this->buildCourseSlug($validated['curso']),
        ]);

        return response()->json([
            'message' => 'Registro agregado correctamente.',
            'curso' => $this->mapCourse($curso),
        ], 201);
    }

    public function updateCurso(Request $request, string $slug): JsonResponse
    {
        $entrenador = $this->resolveAuthenticatedTrainer($request);
        if ($entrenador instanceof JsonResponse) {
            return $entrenador;
        }

        $curso = EntrenadoresCursos::query()
            ->where('id_entrenador', (int) $entrenador->id)
            ->where('slug', $slug)
            ->first();

        if (! $curso) {
            return response()->json(['message' => 'Registro no encontrado.'], 404);
        }

        $validated = $this->validateCoursePayload($request);

        $curso->fill([
            'curso' => $validated['curso'],
            'tipo' => $validated['tipo'],
            'fecha_inicio' => $validated['fecha_inicio'],
            'fecha_fin' => $validated['fecha_fin'],
            'institucion' => $validated['institucion'],
            'modalidad' => (int) $validated['modalidad'],
        ]);
        $curso->save();

        return response()->json([
            'message' => 'Registro actualizado correctamente.',
            'curso' => $this->mapCourse($curso),
        ]);
    }

    public function destroyCurso(Request $request, string $slug): JsonResponse
    {
        $entrenador = $this->resolveAuthenticatedTrainer($request);
        if ($entrenador instanceof JsonResponse) {
            return $entrenador;
        }

        $curso = EntrenadoresCursos::query()
            ->where('id_entrenador', (int) $entrenador->id)
            ->where('slug', $slug)
            ->first();

        if (! $curso) {
            return response()->json(['message' => 'Registro no encontrado.'], 404);
        }

        $curso->delete();

        return response()->json([
            'message' => 'Registro eliminado correctamente.',
        ]);
    }

    private function resolveAuthenticatedTrainer(Request $request)
    {
        $user = $request->user();
        if (! $user || (int) ($user->id_tipo_usuario ?? 0) !== 2) {
            return response()->json(['message' => 'No autorizado.'], 403);
        }

        $trainer = $this->getTrainerRecord((int) $user->id);
        if (! $trainer) {
            return response()->json(['message' => 'Perfil de entrenador no encontrado.'], 404);
        }

        return $trainer;
    }

    private function getTrainerRecord(int $trainerId): ?object
    {
        return DB::table('users')
            ->leftJoin('entrenadores_perfiles', 'users.id', '=', 'entrenadores_perfiles.id_entrenador')
            ->leftJoin('clasificaciones_entrenadores', 'users.id_clasificacion', '=', 'clasificaciones_entrenadores.id')
            ->where('users.id', $trainerId)
            ->where('users.id_tipo_usuario', 2)
            ->select(
                'users.id',
                'users.name',
                'users.email',
                'users.slug',
                'users.titulo',
                'users.id_clasificacion',
                'clasificaciones_entrenadores.nombre as categoria',
                'entrenadores_perfiles.instagram',
                'entrenadores_perfiles.foto'
            )
            ->first();
    }

    private function buildTrainerProfileResponse(int $trainerId): array
    {
        $trainer = $this->getTrainerRecord($trainerId);
        $courses = EntrenadoresCursos::query()
            ->where('id_entrenador', $trainerId)
            ->orderByDesc('fecha_fin')
            ->orderByDesc('created_at')
            ->get();

        $instagram = $this->normalizeInstagram($trainer->instagram ?? null);

        return array_merge([
            'id' => (int) $trainer->id,
            'name' => $trainer->name,
            'email' => $trainer->email,
            'slug' => $trainer->slug,
            'instagram' => $instagram,
            'instagram_url' => $instagram ? 'https://www.instagram.com/' . $instagram : null,
            'titulo' => $trainer->titulo ?? null,
            'categoria' => $trainer->categoria ?? null,
            'cursos' => $courses->map(fn(EntrenadoresCursos $course) => $this->mapCourse($course))->values()->all(),
        ], $this->buildTrainerPhotoPayload($trainer->foto ?? null));
    }

    private function mapCourse(EntrenadoresCursos $course): array
    {
        $tipo = $course->tipo ?: 'curso';
        $modalidad = $course->modalidad !== null ? (int) $course->modalidad : null;

        return [
            'id' => (int) $course->id,
            'slug' => $course->slug,
            'curso' => $course->curso,
            'tipo' => $tipo,
            'tipo_label' => self::TIPO_CURSO_LABELS[$tipo] ?? ucfirst($tipo),
            'institucion' => $course->institucion,
            'modalidad' => $modalidad,
            'modalidad_label' => $modalidad ? (self::MODALIDAD_LABELS[$modalidad] ?? null) : null,
            'fecha_inicio' => $course->fecha_inicio,
            'fecha_fin' => $course->fecha_fin,
        ];
    }

    private function validateCoursePayload(Request $request): array
    {
        return $request->validate([
            'curso' => 'required|string|max:255',
            'tipo' => 'required|in:curso,certificacion,otro',
            'institucion' => 'required|string|max:255',
            'modalidad' => 'required|in:1,2,3',
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date|after_or_equal:fecha_inicio',
        ]);
    }

    private function buildCourseSlug(string $courseName): string
    {
        return hash('sha256', $courseName . '-' . Str::uuid()->toString());
    }

    private function normalizeInstagram(?string $instagram): ?string
    {
        if (! is_string($instagram)) {
            return null;
        }

        $trimmed = trim($instagram);
        if ($trimmed === '') {
            return null;
        }

        if (Str::startsWith(Str::lower($trimmed), ['http://', 'https://'])) {
            $path = trim((string) parse_url($trimmed, PHP_URL_PATH), '/');
            $segment = explode('/', $path)[0] ?? '';
            $trimmed = $segment !== '' ? $segment : $trimmed;
        }

        $trimmed = ltrim($trimmed, '@');

        return $trimmed !== '' ? $trimmed : null;
    }

    private function buildTrainerPhotoPayload(?string $photoPath): array
    {
        if (! $photoPath) {
            return [
                'foto_path' => null,
                'foto_url' => null,
                'avatar_url' => null,
                'image_url' => null,
            ];
        }

        $relativePath = ltrim($photoPath, '/');
        $publicUrl = url('/storage/' . $relativePath);

        return [
            'foto_path' => $relativePath,
            'foto_url' => $publicUrl,
            'avatar_url' => $publicUrl,
            'image_url' => $publicUrl,
        ];
    }

    private function resolveTrainerPhotoUpload(Request $request)
    {
        foreach (['foto', 'avatar', 'image'] as $field) {
            if ($request->hasFile($field)) {
                return $request->file($field);
            }
        }

        return null;
    }

    private function deleteTrainerPhoto(?string $photoPath): void
    {
        if (! $photoPath) {
            return;
        }

        $relativePath = ltrim($photoPath, '/');
        if (Storage::disk('public')->exists($relativePath)) {
            Storage::disk('public')->delete($relativePath);
        }
    }
}
