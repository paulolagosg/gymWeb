<?php

namespace App\Http\Controllers;

use App\Models\Gimnasios;
use App\Models\TermsAndConditions;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TermsAndConditionsWebController extends Controller
{
    private function autorizarSuperAdmin(): void
    {
        if (! Auth::check() || (int) Auth::user()->id_tipo_usuario !== 10) {
            abort(403, 'No tiene acceso');
        }
    }

    public function index(): View
    {
        $this->autorizarSuperAdmin();

        $terminos = TermsAndConditions::query()
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

        return view('terminos.index', compact('terminos'));
    }

    public function create(Request $request): View
    {
        $this->autorizarSuperAdmin();

        $gimnasios = Gimnasios::query()->where('estado', 1)->orderBy('nombre')->get();
        $gimnasioSeleccionado = $this->normalizeGymId($request->query('id_gimnasio'));
        $versionSugerida = $this->suggestNextVersion($gimnasioSeleccionado);

        return view('terminos.create', compact('gimnasios', 'gimnasioSeleccionado', 'versionSugerida'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->autorizarSuperAdmin();

        $validated = $this->validatePayload($request);
        $scopeGymId = $this->normalizeGymId($validated['id_gimnasio'] ?? null);
        $version = trim((string) $validated['version']);

        $this->ensureVersionIsUniqueForScope($scopeGymId, $version);

        DB::transaction(function () use ($validated, $scopeGymId, $version) {
            $this->deactivateActiveTermsForScope($scopeGymId, (int) Auth::id());

            TermsAndConditions::query()->create([
                'id_gimnasio' => $scopeGymId,
                'titulo' => trim((string) $validated['titulo']),
                'version' => $version,
                'contenido' => trim((string) $validated['contenido']),
                'resumen_cambios' => $this->normalizeChangeSummary($validated['resumen_cambios'] ?? null, false),
                'activo' => true,
                'obligatorio' => array_key_exists('obligatorio', $validated) ? (bool) $validated['obligatorio'] : true,
                'publicado_en' => now(),
                'version_anterior_id' => null,
                'id_usuario_creador' => (int) Auth::id(),
                'id_usuario_actualizador' => (int) Auth::id(),
            ]);
        });

        return redirect()->route('terminos.index')->with('success', 'Términos y condiciones publicados correctamente.');
    }

    public function edit(int $id): View
    {
        $this->autorizarSuperAdmin();

        $termino = TermsAndConditions::query()
            ->with(['gimnasio:id,nombre', 'versionAnterior:id,version'])
            ->findOrFail($id);

        $gimnasios = Gimnasios::query()->where('estado', 1)->orderBy('nombre')->get();
        $versionSugerida = $this->suggestNextVersion($this->normalizeGymId($termino->id_gimnasio), $termino->version);

        return view('terminos.edit', compact('termino', 'gimnasios', 'versionSugerida'));
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $this->autorizarSuperAdmin();

        $termino = TermsAndConditions::query()->findOrFail($id);
        $validated = $this->validatePayload($request);
        $scopeGymId = $this->normalizeGymId($validated['id_gimnasio'] ?? $termino->id_gimnasio);
        $currentScopeGymId = $this->normalizeGymId($termino->id_gimnasio);
        $version = trim((string) $validated['version']);

        if ($scopeGymId !== $currentScopeGymId) {
            throw ValidationException::withMessages([
                'id_gimnasio' => ['No puedes cambiar el ámbito de una versión existente.'],
            ]);
        }

        $this->ensureVersionIsUniqueForScope($scopeGymId, $version);

        DB::transaction(function () use ($validated, $scopeGymId, $version, $termino) {
            $this->deactivateActiveTermsForScope($scopeGymId, (int) Auth::id());

            TermsAndConditions::query()->create([
                'id_gimnasio' => $scopeGymId,
                'titulo' => trim((string) $validated['titulo']),
                'version' => $version,
                'contenido' => trim((string) $validated['contenido']),
                'resumen_cambios' => $this->normalizeChangeSummary($validated['resumen_cambios'] ?? null, true),
                'activo' => true,
                'obligatorio' => array_key_exists('obligatorio', $validated) ? (bool) $validated['obligatorio'] : (bool) $termino->obligatorio,
                'publicado_en' => now(),
                'version_anterior_id' => $termino->id,
                'id_usuario_creador' => (int) Auth::id(),
                'id_usuario_actualizador' => (int) Auth::id(),
            ]);
        });

        return redirect()->route('terminos.index')->with('success', 'Se creó una nueva versión de los términos y condiciones.');
    }

    public function destroy(int $id): RedirectResponse
    {
        $this->autorizarSuperAdmin();

        $termino = TermsAndConditions::query()->findOrFail($id);
        $scopeGymId = $this->normalizeGymId($termino->id_gimnasio);
        $replacement = null;

        if ($termino->activo) {
            $replacement = TermsAndConditions::query()
                ->where('id', '<>', $termino->id)
                ->where(function ($builder) use ($scopeGymId) {
                    $this->applyScopeFilter($builder, $scopeGymId);
                })
                ->orderByDesc('activo')
                ->orderByDesc('publicado_en')
                ->orderByDesc('id')
                ->first();

            if (! $replacement) {
                return redirect()->route('terminos.index')->with('error', 'No puedes eliminar la única versión vigente. Publica otra versión antes de eliminar esta.');
            }
        }

        DB::transaction(function () use ($termino, $replacement) {
            if ($replacement) {
                $replacement->forceFill([
                    'activo' => true,
                    'id_usuario_actualizador' => (int) Auth::id(),
                    'updated_at' => now(),
                ])->save();
            }

            $termino->delete();
        });

        return redirect()->route('terminos.index')->with('success', $replacement
            ? 'La versión se eliminó y se restauró la versión anterior del mismo ámbito.'
            : 'La versión se eliminó correctamente.');
    }

    private function validatePayload(Request $request): array
    {
        return $request->validate([
            'titulo' => ['required', 'string', 'max:255'],
            'version' => ['required', 'string', 'max:50'],
            'contenido' => ['required', 'string', 'min:20'],
            'resumen_cambios' => ['nullable', 'string', 'max:1000'],
            'obligatorio' => ['required', 'in:0,1'],
            'id_gimnasio' => ['nullable', 'integer', 'exists:gimnasios,id'],
        ]);
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

        return $isUpdate ? 'Actualización de términos y condiciones.' : 'Versión inicial publicada.';
    }

    private function suggestNextVersion(?int $gymId, ?string $fallback = null): string
    {
        $currentVersion = TermsAndConditions::query()
            ->where(function ($builder) use ($gymId) {
                $this->applyScopeFilter($builder, $gymId);
            })
            ->orderByDesc('activo')
            ->orderByDesc('publicado_en')
            ->orderByDesc('id')
            ->value('version');

        return $this->incrementVersion($currentVersion ?: $fallback ?: '1.0');
    }

    private function incrementVersion(string $version): string
    {
        $normalized = trim($version);

        if ($normalized === '') {
            return '1.0';
        }

        $segments = explode('.', $normalized);
        $lastSegment = $segments[count($segments) - 1] ?? '0';
        $allNumeric = collect($segments)->every(fn($segment) => preg_match('/^\d+$/', $segment) === 1);

        if ($allNumeric && preg_match('/^\d+$/', $lastSegment) === 1) {
            $segments[count($segments) - 1] = (string) (((int) $lastSegment) + 1);
            return implode('.', $segments);
        }

        if (preg_match('/^\d+$/', $normalized) === 1) {
            return (string) (((int) $normalized) + 1);
        }

        return $normalized . '.1';
    }
}
