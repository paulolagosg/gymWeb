<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->prepend(\Illuminate\Http\Middleware\HandleCors::class);
        $middleware->api(prepend: [
            \App\Http\Middleware\AttachRequestId::class,
        ]);
        $middleware->alias([
            'admin' => \App\Http\Middleware\EnsureAdmin::class,
            'api.auth' => \App\Http\Middleware\EnsureApiAuthenticated::class,
            'feature' => \App\Http\Middleware\EnsureFeatureEnabled::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (\Throwable $e, Request $request) {
            if (! $request->expectsJson() && ! $request->is('api/*')) {
                return null;
            }

            $requestId = $request->attributes->get('request_id') ?? (string) Str::uuid();

            // Validación y aborts explícitos (`abort(403, '...')`, etc.): el
            // mensaje ya lo escribió el propio código pensando en el usuario,
            // se muestra tal cual.
            if ($e instanceof ValidationException) {
                return response()->json([
                    'message' => $e->getMessage(),
                    'errors' => $e->errors(),
                    'request_id' => $requestId,
                ], $e->status);
            }

            if ($e instanceof HttpExceptionInterface) {
                return response()->json([
                    'message' => $e->getMessage() ?: 'Error en la petición.',
                    'request_id' => $requestId,
                ], $e->getStatusCode());
            }

            // Cualquier otra excepción es un bug no previsto: en producción no
            // se expone mensaje/clase/archivo real, solo el ID para buscarlo
            // en los logs del servidor. En desarrollo se deja el mensaje real
            // para no perder velocidad de diagnóstico local.
            Log::error($e->getMessage(), [
                'request_id' => $requestId,
                'exception' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            $message = app()->isProduction()
                ? 'Ocurrió un error inesperado. Si persiste, comparte el ID de esta solicitud con soporte.'
                : $e->getMessage();

            return response()->json([
                'message' => $message,
                'request_id' => $requestId,
            ], 500);
        });
    })->create();
