<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\CuentaCorriente;
use App\Models\Cliente;
use App\Models\CuentasCorrientes;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class EnviarRecordatoriosPago extends Command
{
    protected $signature = 'recordatorios:enviar';
    protected $description = 'Envía recordatorios de pago a clientes con cuotas próximas a vencer';

    public function handle()
    {
        $hoy = Carbon::today();
        $tresDiasDespues = $hoy->copy()->addDays(3)->format('Y-m-d');

        $cuotasProximas = CuentasCorrientes::with('cliente')
            ->whereDate('fecha_vencimiento', $tresDiasDespues)
            ->where('id_estado_pago', '!=', 2) // Asumiendo que 2 es "Pagado"
            ->get();

        foreach ($cuotasProximas as $cuota) {
            $this->enviarCorreoRecordatorio($cuota);
        }

        $this->info('Recordatorios enviados: ' . $cuotasProximas->count());
    }

    protected function enviarCorreoRecordatorio($cuota)
    {
        $cliente = $cuota->cliente;
        $datosPago = [
            'fecha_vencimiento' => $cuota->fecha_vencimiento,
            'monto_pagar' => $cuota->monto_pagar,
            'forma_pago' => optional($cuota->formaPago)->nombre ?? 'No especificado',
        ];

        Mail::to($cliente->email)->send(new \App\Mail\RecordatorioPago($cliente, $datosPago));
    }
}
