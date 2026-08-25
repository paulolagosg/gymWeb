<?php

namespace App\Mail;

use App\Models\GimnasioFacturacion;
use App\Models\Gimnasios;
use App\Support\GymBranding;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;

class FacturacionGimnasioVencida extends Mailable
{
    use Queueable, SerializesModels;

    public $gimnasio;
    public $facturacion;
    public $brand;
    public $esTrial;
    public $diasDesde;

    public function __construct(Gimnasios $gimnasio, GimnasioFacturacion $facturacion)
    {
        $this->gimnasio = $gimnasio;
        $this->facturacion = $facturacion;
        $this->brand = GymBranding::resolve($gimnasio);
        $this->esTrial = $facturacion->plan === 'trial';
        $this->diasDesde = Carbon::parse($facturacion->fecha_vencimiento)->diffInDays(now());
    }

    public function build()
    {
        $subject = $this->esTrial
            ? "Tu periodo de prueba terminó - {$this->brand['display_name']}"
            : "Pago pendiente con la plataforma - {$this->brand['display_name']}";

        return GymBranding::applyToMailable($this, $this->brand)
            ->subject($subject)
            ->view('emails.facturacion_gimnasio_vencida');
    }
}
