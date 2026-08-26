<?php

namespace App\Mail;

use App\Models\SolicitudContacto;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class NuevaSolicitudContacto extends Mailable
{
    use Queueable, SerializesModels;

    public SolicitudContacto $solicitud;

    public function __construct(SolicitudContacto $solicitud)
    {
        $this->solicitud = $solicitud;
    }

    public function build()
    {
        return $this
            ->subject('Nueva solicitud — ' . $this->solicitud->nombre_gimnasio . ' (' . $this->solicitud->plan . ')')
            ->view('emails.nueva_solicitud_contacto');
    }
}
