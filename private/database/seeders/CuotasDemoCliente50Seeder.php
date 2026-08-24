<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Cuotas ficticias para el cliente id=50, pensadas para probar de punta a
 * punta lo del Bloque 16 (pago parcial + aging 30/60/90): una cuota pagada
 * completa, una con abono parcial, cuatro vencidas repartidas una por cada
 * bucket de aging, y una pendiente aún no vencida. No borra cuotas
 * existentes del cliente — solo agrega estas 7 filas nuevas, así que es
 * seguro correrlo más de una vez (cada corrida agrega otra tanda).
 */
class CuotasDemoCliente50Seeder extends Seeder
{
    public function run(): void
    {
        $idCliente = 50;

        if (! DB::table('clientes')->where('id', $idCliente)->exists()) {
            $this->command?->error("Cliente id={$idCliente} no existe. Aborta sin crear nada.");
            return;
        }

        $idFormaPago = DB::table('formas_pagos')->where('estado', 1)->value('id');
        if (! $idFormaPago) {
            $this->command?->error('No hay ninguna forma de pago activa en formas_pagos. Aborta.');
            return;
        }

        $idEstadoParcial = DB::table('estados_pagos')->where('slug', 'parcial')->value('id');
        if (! $idEstadoParcial) {
            $this->command?->warn("No existe el estado 'parcial' en estados_pagos — corre primero la migración del Bloque 16 (2026_08_23_000003). Se sembrará igual, pero la cuota con abono parcial quedará con id_estado_pago=1.");
        }

        $hoy = now();
        $monto = 45000.0;
        $now = $hoy->toDateTimeString();

        $rows = [
            // 1. Pagada completa, hace 20 días.
            [
                'id_cliente' => $idCliente,
                'fecha_vencimiento' => $hoy->copy()->subDays(20)->toDateString(),
                'monto' => $monto,
                'monto_pagar' => $monto,
                'monto_pagado' => $monto,
                'saldo' => 0,
                'fecha_pago' => $hoy->copy()->subDays(19)->toDateString(),
                'id_estado_pago' => 2,
                'id_forma_pago' => $idFormaPago,
            ],
            // 2. Vencida hace 10 días, con abono parcial (bucket 0-30).
            [
                'id_cliente' => $idCliente,
                'fecha_vencimiento' => $hoy->copy()->subDays(10)->toDateString(),
                'monto' => $monto,
                'monto_pagar' => $monto,
                'monto_pagado' => 20000,
                'saldo' => 25000,
                'fecha_pago' => null,
                'fecha_ultimo_abono' => $hoy->copy()->subDays(2)->toDateString(),
                'id_estado_pago' => $idEstadoParcial ?? 1,
                'id_forma_pago' => $idFormaPago,
            ],
            // 3. Vencida hace 15 días, sin pago (bucket 0-30).
            [
                'id_cliente' => $idCliente,
                'fecha_vencimiento' => $hoy->copy()->subDays(15)->toDateString(),
                'monto' => $monto,
                'monto_pagar' => $monto,
                'monto_pagado' => 0,
                'saldo' => $monto,
                'id_estado_pago' => 1,
            ],
            // 4. Vencida hace 45 días, sin pago (bucket 31-60).
            [
                'id_cliente' => $idCliente,
                'fecha_vencimiento' => $hoy->copy()->subDays(45)->toDateString(),
                'monto' => $monto,
                'monto_pagar' => $monto,
                'monto_pagado' => 0,
                'saldo' => $monto,
                'id_estado_pago' => 1,
            ],
            // 5. Vencida hace 75 días, sin pago (bucket 61-90).
            [
                'id_cliente' => $idCliente,
                'fecha_vencimiento' => $hoy->copy()->subDays(75)->toDateString(),
                'monto' => $monto,
                'monto_pagar' => $monto,
                'monto_pagado' => 0,
                'saldo' => $monto,
                'id_estado_pago' => 1,
            ],
            // 6. Vencida hace 120 días, sin pago (bucket 90+).
            [
                'id_cliente' => $idCliente,
                'fecha_vencimiento' => $hoy->copy()->subDays(120)->toDateString(),
                'monto' => $monto,
                'monto_pagar' => $monto,
                'monto_pagado' => 0,
                'saldo' => $monto,
                'id_estado_pago' => 1,
            ],
            // 7. Pendiente, vence en 10 días (no debe aparecer como morosa).
            [
                'id_cliente' => $idCliente,
                'fecha_vencimiento' => $hoy->copy()->addDays(10)->toDateString(),
                'monto' => $monto,
                'monto_pagar' => $monto,
                'monto_pagado' => 0,
                'saldo' => $monto,
                'id_estado_pago' => 1,
            ],
        ];

        foreach ($rows as $row) {
            DB::table('cuentas_corrientes')->insert(array_merge($row, [
                'created_at' => $now,
                'updated_at' => $now,
            ]));
        }

        $this->command?->info('Se crearon 7 cuotas ficticias para el cliente id=50: 1 pagada, 1 con abono parcial, 3 vencidas (0-30/31-60/61-90), 1 vencida hace 120 días (90+) y 1 pendiente a futuro.');
    }
}
