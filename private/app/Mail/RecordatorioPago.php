<?php

namespace App\Mail;

use App\Support\GymBranding;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class RecordatorioPago extends Mailable
{
    use Queueable, SerializesModels;

    public $cliente;
    public $datosPago;
    public $brand;

    public function __construct($cliente, $datosPago)
    {
        $this->cliente = $cliente;
        $this->datosPago = $datosPago;
        $this->brand = GymBranding::resolve($cliente);
    }

    public function build()
    {
        return GymBranding::applyToMailable($this, $this->brand)
            ->subject('Recordatorio de pago - ' . $this->brand['display_name'])
            ->view('emails.recordatorio_pago');
    }
}
