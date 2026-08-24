<?php

namespace App\Http\Middleware;

use App\Models\Gimnasios;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureFeatureEnabled
{
    public function handle(Request $request, Closure $next, string $feature): Response
    {
        /** @var \App\Models\User|null $user */
        $user = $request->user();

        if (! $user) {
            return response()->json(['message' => 'No autorizado.'], 403);
        }

        // El super-admin nunca se auto-bloquea por flags de un gimnasio.
        if ((int) $user->id_tipo_usuario === 10) {
            return $next($request);
        }

        if (! Gimnasios::tieneFeature($feature, $user->id_gimnasio)) {
            return response()->json(['message' => 'Funcionalidad no disponible para tu gimnasio.'], 403);
        }

        return $next($request);
    }
}
