<?php

namespace App\Mail;

use App\Support\GymBranding;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;

class RecordatorioCuotasVencidas extends Mailable
{
    use Queueable, SerializesModels;

    public $cliente;
    public $cuotasVencidas;
    public $brand;
    public $montoTotal;
    public $diasDesde;

    public function __construct($cliente, $cuotasVencidas)
    {
        $this->cliente = $cliente;
        $this->cuotasVencidas = $cuotasVencidas;
        $this->brand = GymBranding::resolve($cliente);
        $this->montoTotal = $cuotasVencidas->sum(fn($cuota) => $cuota->saldo ?? $cuota->monto_pagar ?? $cuota->monto ?? 0);
        $primeraVencida = $cuotasVencidas->min('fecha_vencimiento');
        $this->diasDesde = $primeraVencida ? Carbon::parse($primeraVencida)->diffInDays(now()) : 0;
    }

    public function build()
    {
        $count = $this->cuotasVencidas->count();
        $subject = $count > 1
            ? "Tienes {$count} cuotas vencidas - {$this->brand['display_name']}"
            : "Tienes una cuota vencida - {$this->brand['display_name']}";

        return GymBranding::applyToMailable($this, $this->brand)
            ->subject($subject)
            ->view('emails.recordatorio_cuotas_vencidas');
    }
}
