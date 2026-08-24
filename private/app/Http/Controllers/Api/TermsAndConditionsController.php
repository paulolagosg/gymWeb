<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TermsAcceptance;
use App\Models\TermsAndConditions;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TermsAndConditionsController extends Controller
{
    public function adminIndex(Request $request): JsonResponse
    {
        if ($err = $this->requireSuperAdmin($request)) {
            return $err;
        }

        $terms = TermsAndConditions::query()
            ->with([
                'gimnasio:id,nombre',
                'creador:id,name',
                'actualizador:id,name',
                'versionAnterior:id,version',
            ])
            ->withCount('aceptaciones')
            ->orderByRaw('CASE WHEN id_gimnasio IS NULL THEN 0 ELSE 1 END')
            ->orderBy('id_gimnasio')
            ->orderByDesc('activo')
            ->orderByDesc('publicado_en')
            ->orderByDesc('id')
            ->get();

        return response()->json([
            'terminos' => $terms->map(fn(TermsAndConditions $term) => $this->serializeAdminTerms($term))->values(),
        ]);
    }

    public function adminShow(Request $request, int $id): JsonResponse
    {
        if ($err = $this->requireSuperAdmin($request)) {
            return $err;
        }

        $term = $this->findAdminTerms($id);

        if (! $term) {
            return response()->json(['message' => 'Versión de términos no encontrada.'], 404);
        }

        $history = TermsAndConditions::query()
            ->with([
                'gimnasio:id,nombre',
                'creador:id,name',
                'actualizador:id,name',
                'versionAnterior:id,version',
            ])
            ->withCount('aceptaciones')
            ->where(function ($builder) use ($term) {
                $this->applyScopeFilter($builder, $term->id_gimnasio);
            })
            ->orderByDesc('activo')
            ->orderByDesc('publicado_en')
            ->orderByDesc('id')
            ->get();

        return response()->json([
            'termino' => $this->serializeAdminTerms($term),
            'historial' => $history->map(fn(TermsAndConditions $item) => $this->serializeAdminTerms($item))->values(),
        ]);
    }

    public function adminStore(Request $request): JsonResponse
    {
        if ($err = $this->requireSuperAdmin($request)) {
            return $err;
        }

        $validated = $this->validateAdminPayload($request);
        $scopeGymId = $this->normalizeGymId($validated['id_gimnasio'] ?? null);
        $version = trim((string) $validated['version']);

        $this->ensureVersionIsUniqueForScope($scopeGymId, $version);

        $term = DB::transaction(function () use ($request, $validated, $scopeGymId, $version) {
            $this->deactivateActiveTermsForScope($scopeGymId, (int) $request->user()->id);

            return TermsAndConditions::query()->create([
                'id_gimnasio' => $scopeGymId,
                'titulo' => trim((string) $validated['titulo']),
                'version' => $version,
                'contenido' => trim((string) $validated['contenido']),
                'resumen_cambios' => $this->normalizeChangeSummary($validated['resumen_cambios'] ?? null, false),
                'activo' => true,
                'obligatorio' => array_key_exists('obligatorio', $validated) ? (bool) $validated['obligatorio'] : true,
                'publicado_en' => now(),
                'version_anterior_id' => null,
                'id_usuario_creador' => (int) $request->user()->id,
                'id_usuario_actualizador' => (int) $request->user()->id,
            ]);
        });

        $freshTerm = $this->findAdminTerms((int) $term->id);

        return response()->json([
            'message' => 'Términos y condiciones creados correctamente.',
            'termino' => $freshTerm ? $this->serializeAdminTerms($freshTerm) : null,
        ], 201);
    }

    public function adminUpdate(Request $request, int $id): JsonResponse
    {
        if ($err = $this->requireSuperAdmin($request)) {
            return $err;
        }

        $currentTerm = TermsAndConditions::query()->find($id);

        if (! $currentTerm) {
            return response()->json(['message' => 'Versión de términos no encontrada.'], 404);
        }

        $validated = $this->validateAdminPayload($request, $currentTerm);
        $scopeGymId = $this->normalizeGymId($validated['id_gimnasio'] ?? $currentTerm->id_gimnasio);
        $version = trim((string) $validated['version']);

        if ($scopeGymId !== $this->normalizeGymId($currentTerm->id_gimnasio)) {
            throw ValidationException::withMessages([
                'id_gimnasio' => ['No puedes cambiar el alcance de una versión existente. Crea un nuevo término para otra sede o ámbito global.'],
            ]);
        }

        $this->ensureVersionIsUniqueForScope($scopeGymId, $version);

        $term = DB::transaction(function () use ($request, $validated, $currentTerm, $scopeGymId, $version) {
            $this->deactivateActiveTermsForScope($scopeGymId, (int) $request->user()->id);

            return TermsAndConditions::query()->create([
                'id_gimnasio' => $scopeGymId,
                'titulo' => trim((string) $validated['titulo']),
                'version' => $version,
                'contenido' => trim((string) $validated['contenido']),
                'resumen_cambios' => $this->normalizeChangeSummary($validated['resumen_cambios'] ?? null, true),
                'activo' => true,
                'obligatorio' => array_key_exists('obligatorio', $validated) ? (bool) $validated['obligatorio'] : (bool) $currentTerm->obligatorio,
                'publicado_en' => now(),
                'version_anterior_id' => $currentTerm->id,
                'id_usuario_creador' => (int) $request->user()->id,
                'id_usuario_actualizador' => (int) $request->user()->id,
            ]);
        });

        $freshTerm = $this->findAdminTerms((int) $term->id);

        return response()->json([
            'message' => 'Se creó una nueva versión de los términos y condiciones.',
            'termino' => $freshTerm ? $this->serializeAdminTerms($freshTerm) : null,
        ]);
    }

    public function adminDestroy(Request $request, int $id): JsonResponse
    {
        if ($err = $this->requireSuperAdmin($request)) {
            return $err;
        }

        $term = TermsAndConditions::query()->find($id);

        if (! $term) {
            return response()->json(['message' => 'Versión de términos no encontrada.'], 404);
        }

        $replacement = null;
        $scopeGymId = $this->normalizeGymId($term->id_gimnasio);

        if ($term->activo) {
            $replacement = TermsAndConditions::query()
                ->where('id', '<>', $term->id)
                ->where(function ($builder) use ($scopeGymId) {
                    $this->applyScopeFilter($builder, $scopeGymId);
                })
                ->orderByDesc('activo')
                ->orderByDesc('publicado_en')
                ->orderByDesc('id')
                ->first();

            if (! $replacement) {
                return response()->json([
                    'message' => 'No puedes eliminar la única versión vigente. Crea otra versión primero o conserva este registro como histórico activo.',
                ], 422);
            }
        }

        DB::transaction(function () use ($request, $term, $replacement) {
            if ($replacement) {
                $replacement->forceFill([
                    'activo' => true,
                    'id_usuario_actualizador' => (int) $request->user()->id,
                    'updated_at' => now(),
                ])->save();
            }

            $term->delete();
        });

        return response()->json([
            'message' => $replacement
                ? 'La versión fue eliminada y se restauró la versión anterior de ese ámbito.'
                : 'La versión fue eliminada correctamente.',
        ]);
    }

    public function current(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user instanceof User) {
            return response()->json(['message' => 'No autenticado.'], 401);
        }

        return $this->buildStatusResponse($user);
    }

    public function acceptCurrent(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user instanceof User) {
            return response()->json(['message' => 'No autenticado.'], 401);
        }

        $currentTerms = $this->findCurrentTermsForUser($user);

        if (! $currentTerms) {
            return response()->json([
                'message' => 'No existen términos y condiciones vigentes para aceptar.',
            ], 404);
        }

        TermsAcceptance::updateOrCreate(
            [
                'id_terms_and_conditions' => $currentTerms->id,
                'id_user' => $user->id,
            ],
            [
                'id_gimnasio' => $currentTerms->id_gimnasio ?? $this->resolveUserGymId($user),
                'accepted_at' => now(),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ],
        );

        return $this->buildStatusResponse($user, $currentTerms);
    }

    private function requireSuperAdmin(Request $request): ?JsonResponse
    {
        $user = $request->user();

        if (! $user instanceof User || (int) $user->id_tipo_usuario !== 10) {
            return response()->json(['message' => 'No autorizado.'], 403);
        }

        return null;
    }

    private function buildStatusResponse(User $user, ?TermsAndConditions $currentTerms = null): JsonResponse
    {
        $currentTerms = $currentTerms ?? $this->findCurrentTermsForUser($user);

        if (! $currentTerms) {
            return response()->json([
                'current_terms' => null,
                'has_pending_terms' => false,
                'accepted_at' => null,
            ]);
        }

        $acceptance = TermsAcceptance::query()
            ->where('id_terms_and_conditions', $currentTerms->id)
            ->where('id_user', $user->id)
            ->latest('accepted_at')
            ->first();

        return response()->json([
            'current_terms' => $this->serializeTerms($currentTerms),
            'has_pending_terms' => $acceptance === null && (bool) $currentTerms->obligatorio,
            'accepted_at' => $acceptance?->accepted_at?->toIso8601String(),
        ]);
    }

    private function findCurrentTermsForUser(User $user): ?TermsAndConditions
    {
        $gymId = $this->resolveUserGymId($user);

        $query = TermsAndConditions::query()
            ->with('gimnasio:id,nombre')
            ->where('activo', true)
            ->where(function ($builder) {
                $builder
                    ->whereNull('publicado_en')
                    ->orWhere('publicado_en', '<=', now());
            });

        if ($gymId) {
            $query
                ->where(function ($builder) use ($gymId) {
                    $builder
                        ->where('id_gimnasio', $gymId)
                        ->orWhereNull('id_gimnasio');
                })
                ->orderByRaw('CASE WHEN id_gimnasio = ? THEN 0 ELSE 1 END', [$gymId]);
        } else {
            $query->whereNull('id_gimnasio');
        }

        return $query
            ->orderByDesc('publicado_en')
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->first();
    }

    private function resolveUserGymId(User $user): ?int
    {
        if (! empty($user->id_gimnasio)) {
            return (int) $user->id_gimnasio;
        }

        $user->loadMissing('cliente');

        if (! empty($user->cliente?->id_gimnasio)) {
            return (int) $user->cliente->id_gimnasio;
        }

        return null;
    }

    private function serializeTerms(TermsAndConditions $terms): array
    {
        return [
            'id' => $terms->id,
            'titulo' => $terms->titulo,
            'version' => $terms->version,
            'contenido' => $terms->contenido,
            'obligatorio' => (bool) $terms->obligatorio,
            'publicado_en' => $terms->publicado_en?->toIso8601String(),
            'id_gimnasio' => $terms->id_gimnasio,
            'gimnasio' => $terms->gimnasio?->nombre,
        ];
    }

    private function serializeAdminTerms(TermsAndConditions $terms): array
    {
        return [
            'id' => $terms->id,
            'titulo' => $terms->titulo,
            'version' => $terms->version,
            'contenido' => $terms->contenido,
            'resumen_cambios' => $terms->resumen_cambios,
            'activo' => (bool) $terms->activo,
            'obligatorio' => (bool) $terms->obligatorio,
            'publicado_en' => $terms->publicado_en?->toIso8601String(),
            'id_gimnasio' => $terms->id_gimnasio,
            'gimnasio' => $terms->gimnasio?->nombre,
            'version_anterior_id' => $terms->version_anterior_id,
            'version_anterior' => $terms->versionAnterior?->version,
            'aceptaciones_count' => (int) ($terms->aceptaciones_count ?? 0),
            'creado_por' => $terms->creador?->name,
            'actualizado_por' => $terms->actualizador?->name,
            'created_at' => $terms->created_at?->toIso8601String(),
            'updated_at' => $terms->updated_at?->toIso8601String(),
        ];
    }

    private function findAdminTerms(int $id): ?TermsAndConditions
    {
        return TermsAndConditions::query()
            ->with([
                'gimnasio:id,nombre',
                'creador:id,name',
                'actualizador:id,name',
                'versionAnterior:id,version',
            ])
            ->withCount('aceptaciones')
            ->find($id);
    }

    private function validateAdminPayload(Request $request, ?TermsAndConditions $baseTerm = null): array
    {
        $validated = $request->validate([
            'titulo' => ['required', 'string', 'max:255'],
            'version' => ['required', 'string', 'max:50'],
            'contenido' => ['required', 'string', 'min:20'],
            'resumen_cambios' => ['nullable', 'string', 'max:1000'],
            'obligatorio' => ['nullable', 'boolean'],
            'id_gimnasio' => ['nullable', 'integer', 'exists:gimnasios,id'],
        ]);

        if ($baseTerm && array_key_exists('id_gimnasio', $validated)) {
            $incomingGymId = $this->normalizeGymId($validated['id_gimnasio'] ?? null);
            $currentGymId = $this->normalizeGymId($baseTerm->id_gimnasio);

            if ($incomingGymId !== $currentGymId) {
                throw ValidationException::withMessages([
                    'id_gimnasio' => ['No puedes cambiar el ámbito de un término existente.'],
                ]);
            }
        }

        return $validated;
    }

    private function ensureVersionIsUniqueForScope(?int $gymId, string $version): void
    {
        $exists = TermsAndConditions::query()
            ->where('version', $version)
            ->where(function ($builder) use ($gymId) {
                $this->applyScopeFilter($builder, $gymId);
            })
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'version' => ['Ya existe una versión con ese identificador en el mismo ámbito.'],
            ]);
        }
    }

    private function deactivateActiveTermsForScope(?int $gymId, int $actorUserId): void
    {
        TermsAndConditions::query()
            ->where('activo', true)
            ->where(function ($builder) use ($gymId) {
                $this->applyScopeFilter($builder, $gymId);
            })
            ->update([
                'activo' => false,
                'id_usuario_actualizador' => $actorUserId,
                'updated_at' => now(),
            ]);
    }

    private function applyScopeFilter($query, ?int $gymId): void
    {
        if ($gymId) {
            $query->where('id_gimnasio', $gymId);
            return;
        }

        $query->whereNull('id_gimnasio');
    }

    private function normalizeGymId(mixed $value): ?int
    {
        if ($value === null || $value === '' || $value === 'null') {
            return null;
        }

        $normalized = (int) $value;

        return $normalized > 0 ? $normalized : null;
    }

    private function normalizeChangeSummary(mixed $value, bool $isUpdate): string
    {
        if (is_string($value) && trim($value) !== '') {
            return trim($value);
        }

        return $isUpdate
            ? 'Actualización de términos y condiciones.'
            : 'Versión inicial publicada.';
    }
}
