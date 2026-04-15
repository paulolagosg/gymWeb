<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class NuevoMensajeNotification extends Mailable
{
    use Queueable, SerializesModels;

    public $remitente;
    public $mensaje;

    public function __construct($remitente, $mensaje)
    {
        $this->remitente = $remitente;
        $this->mensaje = $mensaje;
    }

    public function build()
    {
        return $this->subject('Tienes un nuevo mensaje en Ampaya Gym')
            ->markdown('emails.nuevo_mensaje');
    }
}
