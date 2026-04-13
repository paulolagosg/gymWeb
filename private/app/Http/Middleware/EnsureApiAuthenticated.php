<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureApiAuthenticated
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::guard('sanctum')->check()) {
            Auth::shouldUse('sanctum');

            return $next($request);
        }

        if (Auth::guard('web')->check()) {
            Auth::shouldUse('web');

            return $next($request);
        }

        return response()->json(['message' => 'Unauthenticated.'], 401);
    }
}
