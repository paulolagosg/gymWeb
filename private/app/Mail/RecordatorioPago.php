<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class RecordatorioPago extends Mailable
{
    use Queueable, SerializesModels;

    public $cliente;
    public $datosPago;

    public function __construct($cliente, $datosPago)
    {
        $this->cliente = $cliente;
        $this->datosPago = $datosPago;
    }

    public function build()
    {
        return $this->subject('Recordatorio de pago próximo a vencer')
            ->markdown('emails.recordatorio_pago');
    }
}
