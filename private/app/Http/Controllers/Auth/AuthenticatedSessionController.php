<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\Gimnasios;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        if (Auth::check()) {
            $idGimnasio = Auth::user()->id_gimnasio
                ?: Gimnasios::query()->where('estado', 1)->orderBy('id')->value('id')
                ?: Gimnasios::query()->orderBy('id')->value('id');

            $request->session()->put('id_gimnasio_actual', $idGimnasio);
            $request->session()->put('perfil_actual', Auth::user()->id_tipo_usuario);
        }

        $redirectRoute = Auth::check() && in_array((int) Auth::user()->id_tipo_usuario, [1, 10], true)
            ? 'dashboard'
            : 'portada';

        return redirect()->intended(route($redirectRoute, absolute: false));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
