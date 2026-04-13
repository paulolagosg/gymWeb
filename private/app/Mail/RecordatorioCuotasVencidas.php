<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class RecordatorioCuotasVencidas extends Mailable
{
    use Queueable, SerializesModels;

    public $cliente;
    public $cuotasVencidas;

    public function __construct($cliente, $cuotasVencidas)
    {
        $this->cliente = $cliente;
        $this->cuotasVencidas = $cuotasVencidas;
    }

    public function build()
    {
        $count = $this->cuotasVencidas->count();
        $subject = $count > 1
            ? "Tienes {$count} cuotas vencidas - Max Fitness & Health"
            : "Tienes una cuota vencida - Max Fitness & Health";

        return $this->subject($subject)
            ->markdown('emails.recordatorio_cuotas_vencidas');
    }
}
