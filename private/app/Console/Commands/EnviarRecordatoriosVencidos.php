<?php

namespace App\Console\Commands;

use App\Mail\RecordatorioCuotasVencidas;
use App\Models\Clientes;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class EnviarRecordatoriosVencidos extends Command
{
    protected $signature = 'recordatorios:vencidas';
    protected $description = 'Envía recordatorio por correo a clientes con cuotas ya vencidas (no pagadas)';

    public function handle(): void
    {
        $hoy = Carbon::today()->toDateString();

        $clientes = Clientes::where('estado', 1)
            ->whereHas('cuotas', function ($query) use ($hoy) {
                $query->where('id_estado_pago', '<>', 2)
                    ->where('fecha_vencimiento', '<=', $hoy);
            })
            ->get();

        $enviados = 0;

        foreach ($clientes as $cliente) {
            if (! $cliente->email) {
                continue;
            }

            $cuotasVencidas = $cliente->cuotas()
                ->where('id_estado_pago', '<>', 2)
                ->where('fecha_vencimiento', '<=', $hoy)
                ->get();

            if ($cuotasVencidas->isEmpty()) {
                continue;
            }

            Mail::to($cliente->email)->send(new RecordatorioCuotasVencidas($cliente, $cuotasVencidas));
            $enviados++;
        }

        $this->info("[" . now() . "] Recordatorios de cuotas vencidas enviados: {$enviados}");
    }
}
