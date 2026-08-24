<?php

namespace App\Mail;

use App\Support\GymBranding;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class NuevoMensajeNotification extends Mailable
{
    use Queueable, SerializesModels;

    public $remitente;
    public $mensaje;
    public $brand;

    public function __construct($remitente, $mensaje)
    {
        $this->remitente = $remitente;
        $this->mensaje = $mensaje;
        $this->brand = GymBranding::resolve($remitente);
    }

    public function build()
    {
        return GymBranding::applyToMailable($this, $this->brand)
            ->subject('Tienes un nuevo mensaje en ' . $this->brand['display_name'])
            ->view('emails.nuevo_mensaje');
    }
}
