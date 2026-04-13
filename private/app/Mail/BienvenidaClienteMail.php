<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class BienvenidaClienteMail extends Mailable
{
    use Queueable, SerializesModels;

    public $cliente;
    public $pwd;

    public function __construct($cliente, $pwd)
    {
        $this->cliente = $cliente;
        $this->pwd = $pwd;
    }

    public function build()
    {
        return $this->subject('Bienvenido a Max Fitness & Health')
            ->markdown('emails.bienvenida_cliente');
    }
}
