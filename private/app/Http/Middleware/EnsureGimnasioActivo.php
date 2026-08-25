<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureGimnasioActivo
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var \App\Models\User|null $user */
        $user = $request->user() ?? Auth::user();

        if (! $user || (int) $user->id_tipo_usuario === 10) {
            return $next($request);
        }

        $gimnasio = $user->gimnasio ?? $user->cliente?->gimnasio;

        if ($gimnasio && $gimnasio->bloqueado) {
            $mensaje = $gimnasio->mensajeBloqueo((int) $user->id_tipo_usuario);

            if ($request->wantsJson()) {
                return response()->json([
                    'message' => $mensaje,
                    'code' => 'gimnasio_bloqueado',
                    'motivo' => $gimnasio->bloqueado_motivo,
                ], 403);
            }

            return response()->view('bloqueado', ['mensaje' => $mensaje], 403);
        }

        return $next($request);
    }
}
