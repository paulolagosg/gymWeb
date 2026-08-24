<?php

namespace App\Mail;

use App\Support\GymBranding;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ReporteCliente extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public $cliente;
    public $pdf;
    public $brand;

    public function __construct($cliente, $pdf)
    {
        $this->cliente = $cliente;
        $this->pdf = $pdf;
        $this->brand = GymBranding::resolve($cliente);
    }

    public function build()
    {
        return GymBranding::applyToMailable($this, $this->brand)
            ->subject('Reporte de indicadores - ' . $this->brand['display_name'])
            ->view('emails.reporte_cliente')
            ->attachData($this->pdf, 'reporte.pdf', [
                'mime' => 'application/pdf',
            ]);
    }
}
