<?php

namespace App\Console\Commands;

use App\Mail\FacturacionGimnasioVencida;
use App\Models\GimnasioFacturacion;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Carbon;

class VerificarFacturacionGimnasios extends Command
{
    protected $signature = 'gimnasios:verificar-facturacion';
    protected $description = 'Bloquea gimnasios con trial vencido y avisa por correo a los que tienen un pago vencido con la plataforma';

    public function handle(): void
    {
        $hoy = Carbon::today()->toDateString();

        $vencidas = GimnasioFacturacion::whereNull('fecha_pago')
            ->where('fecha_vencimiento', '<', $hoy)
            ->with('gimnasio')
            ->get();

        $bloqueados = 0;
        $avisados = 0;

        foreach ($vencidas as $facturacion) {
            $gimnasio = $facturacion->gimnasio;
            if (! $gimnasio) {
                continue;
            }

            if ($facturacion->plan === 'trial' && ! $gimnasio->bloqueado) {
                $gimnasio->update(['bloqueado' => true, 'bloqueado_motivo' => 'trial_vencido']);
                $bloqueados++;
            }

            // Planes pagos vencidos: no se bloquean solos, solo se avisa (decisión del
            // usuario) — el super-admin decide manualmente si suspende.
            if ($gimnasio->correo_electronico) {
                Mail::to($gimnasio->correo_electronico)->send(new FacturacionGimnasioVencida($gimnasio, $facturacion));
                $avisados++;
            }
        }

        $this->info("[" . now() . "] Facturación verificada. Gimnasios bloqueados por trial vencido: {$bloqueados}. Avisos enviados: {$avisados}.");
    }
}
