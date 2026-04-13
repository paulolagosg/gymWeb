<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\CuentaCorriente;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;
use App\Mail\RecordatorioPago;
use App\Models\CuentasCorrientes;

class EnviarRecordatoriosCommand extends Command
{
    protected $signature = 'recordatorios:enviar';
    protected $description = 'Envía recordatorios de pagos próximos a vencer';

    public function handle()
    {
        $fechaRecordatorio = Carbon::today()->addDays(3)->format('Y-m-d');

        $cuotas = CuentasCorrientes::with(['cliente', 'formaPago'])
            ->whereDate('fecha_vencimiento', $fechaRecordatorio)
            ->where('id_estado_pago', '!=', 2) // Asumiendo que 2 es "Pagado"
            ->get();

        foreach ($cuotas as $cuota) {
            if ($cuota->cliente && $cuota->cliente->email) {
                Mail::to($cuota->cliente->email)
                    ->send(new RecordatorioPago(
                        $cuota->cliente,
                        [
                            'fecha_vencimiento' => $cuota->fecha_vencimiento,
                            'monto_pagar' => $cuota->monto_pagar,
                            'forma_pago' => $cuota->formaPago->nombre ?? 'No especificado'
                        ]
                    ));
            }
        }

        $this->info("[" . now() . "] Recordatorios enviados: " . $cuotas->count());
    }
}
