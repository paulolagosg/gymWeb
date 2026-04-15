<?php

namespace App\Exports;

use App\Models\CuentasCorrientes;
use App\Models\Gimnasios;
use App\Models\MovimientosFinancieros;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;

class MovimientosExport implements FromCollection
{
    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        $idGimnasio = Gimnasios::gimnasioActualId();
        $authUser = Auth::user();

        $movimientos1 = MovimientosFinancieros::orderBy('fecha', 'desc')
            ->when($idGimnasio, function ($query) use ($idGimnasio) {
                $query->where('id_gimnasio', $idGimnasio);
            })
            ->when($authUser && (int) $authUser->id_tipo_usuario === 2, function ($query) use ($authUser) {
                $query->where('user_id', $authUser->id);
            })
            ->select('fecha', 'descripcion', 'tipo', 'monto');

        $cuentas = CuentasCorrientes::join('clientes', 'clientes.id', 'cuentas_corrientes.id_cliente')
            ->select(
                'cuentas_corrientes.fecha_pago as fecha',
                DB::raw("concat('Pago mensualidad: ', clientes.nombres, ' ', clientes.paterno) as descripcion"),
                DB::raw("'ingreso' as tipo"),
                DB::raw("sum(cuentas_corrientes.monto_pagado) as monto")
            )
            ->whereNotNull('cuentas_corrientes.fecha_pago')
            ->when($idGimnasio, function ($query) use ($idGimnasio) {
                $query->where('clientes.id_gimnasio', $idGimnasio);
            })
            ->when($authUser && (int) $authUser->id_tipo_usuario === 2, function ($query) use ($authUser) {
                $query->where('clientes.id_usuario', $authUser->id);
            })
            ->groupBy('cuentas_corrientes.fecha_pago', 'clientes.nombres', 'clientes.paterno')
            ->orderBy('cuentas_corrientes.fecha_pago', 'desc');
        DB::enableQueryLog();
        $movimientos = $movimientos1->union($cuentas)->orderBy('fecha', 'desc')->get();

        return $movimientos->map(function ($mov) {
            $monto = $mov->monto;
            if (strtolower($mov->tipo) === 'egreso') {
                $monto = -abs($monto);
            }
            return [
                'fecha' => $mov->fecha,
                'descripcion' => $mov->descripcion,
                'tipo' => $mov->tipo,
                'monto' => $monto,
            ];
        });
    }
}
