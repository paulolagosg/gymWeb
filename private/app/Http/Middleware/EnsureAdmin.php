<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var \App\Models\User|null $user */
        $user = $request->user();

        if (! $user || $user->id_tipo_usuario !== 1) {
            return response()->json(['message' => 'No autorizado.'], 403);
        }

        return $next($request);
    }
}
