<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * `saldo` no se mantenía de forma consistente (algunos paths de pago
     * no lo actualizaban). Antes de empezar a usarlo para aging/pago
     * parcial, se recalcula para todas las filas existentes a partir de
     * lo que sí es confiable: monto a pagar menos lo ya pagado.
     */
    public function up(): void
    {
        if (! Schema::hasTable('cuentas_corrientes')) {
            return;
        }

        DB::statement(
            "UPDATE cuentas_corrientes
             SET saldo = GREATEST(0, COALESCE(monto_pagar, monto, 0) - COALESCE(monto_pagado, 0))"
        );
    }

    public function down(): void
    {
        // Intencionalmente vacío: no hay un estado previo confiable al que volver.
    }
};
