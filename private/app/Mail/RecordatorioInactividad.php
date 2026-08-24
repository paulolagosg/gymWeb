<?php

namespace App\Mail;

use App\Support\GymBranding;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class RecordatorioInactividad extends Mailable
{
    use Queueable, SerializesModels;

    public $cliente;
    public $diasSinEntrenar;
    public $brand;

    public function __construct($cliente, int $diasSinEntrenar)
    {
        $this->cliente = $cliente;
        $this->diasSinEntrenar = $diasSinEntrenar;
        $this->brand = GymBranding::resolve($cliente);
    }

    public function build()
    {
        return GymBranding::applyToMailable($this, $this->brand)
            ->subject('Te echamos de menos en ' . $this->brand['display_name'])
            ->view('emails.recordatorio_inactividad');
    }
}
