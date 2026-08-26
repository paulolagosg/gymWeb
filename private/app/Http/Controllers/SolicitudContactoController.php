<?php

namespace App\Http\Controllers;

use App\Mail\NuevaSolicitudContacto;
use App\Models\Gimnasios;
use App\Models\SolicitudContacto;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SolicitudContactoController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nombre_gimnasio' => ['required', 'string', 'max:150'],
            'nombre_contacto' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:150'],
            'telefono' => ['nullable', 'string', 'max:30'],
            'plan' => ['required', 'in:' . implode(',', Gimnasios::PLAN_TIERS)],
            'mensaje' => ['nullable', 'string', 'max:1000'],
        ]);

        $solicitud = SolicitudContacto::create($validated);

        try {
            Mail::to(config('services.contacto.email'))->send(new NuevaSolicitudContacto($solicitud));
        } catch (\Throwable $e) {
            Log::error('No se pudo enviar el correo de solicitud de contacto', [
                'solicitud_id' => $solicitud->id,
                'error' => $e->getMessage(),
            ]);
        }

        return redirect('/#contacto')->with('lead_success', true);
    }
}
