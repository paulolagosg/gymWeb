<?php

use Carbon\Carbon;

if (!function_exists('vencimientoVirtualMes')) {
    function vencimientoVirtualMes($cliente, Carbon $hoy): Carbon
    {
        $inicio = Carbon::parse($cliente->fecha_inicio);
        $diaVenc = $inicio->day;

        $baseMes = $hoy->copy()->startOfMonth();
        $ultimoDia = $baseMes->daysInMonth;

        $diaReal = min($diaVenc, $ultimoDia);

        return $baseMes->copy()->day($diaReal);
    }
}

if (!function_exists('clienteEstaMoroso')) {
    function clienteEstaMoroso($cliente, Carbon $hoy): bool
    {
        // 1️⃣ Si tiene cuotas reales → lógica normal
        if ($cliente->cuotas->isNotEmpty()) {
            return $cliente->cuotas->contains(function ($cuota) use ($hoy) {
                return $cuota->monto_pagado < $cuota->monto_pagar
                    && Carbon::parse($cuota->fecha_vencimiento)->lte($hoy);
            });
        }

        // 2️⃣ Si NO tiene cuotas → vencimiento virtual
        if (!$cliente->fecha_inicio) {
            return false;
        }

        $inicio = Carbon::parse($cliente->fecha_inicio);
        $fin    = $cliente->fecha_fin ? Carbon::parse($cliente->fecha_fin) : null;

        $vencVirtual = vencimientoVirtualMes($cliente, $hoy);

        // Debe estar vigente para ese vencimiento
        if ($inicio->gt($vencVirtual)) return false;
        if ($fin && $fin->lt($vencVirtual)) return false;

        // Moroso si hoy ya pasó el vencimiento virtual
        return $hoy->gt($vencVirtual);
    }
}
