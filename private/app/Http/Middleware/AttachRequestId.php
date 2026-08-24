<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Genera un id único por petición, lo deja disponible para el resto del
 * request (excepciones, logs) y lo agrega como header a toda respuesta —
 * éxito o error, sin importar si el controlador la construyó a mano o vino
 * de una excepción. Es la base para que un usuario pueda decir "error tal,
 * ID tal" y eso sea buscable en los logs del servidor.
 */
class AttachRequestId
{
    public function handle(Request $request, Closure $next): Response
    {
        $requestId = (string) Str::uuid();

        $request->attributes->set('request_id', $requestId);
        Log::withContext(['request_id' => $requestId]);

        $response = $next($request);
        $response->headers->set('X-Request-Id', $requestId);

        return $response;
    }
}
