<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\MovimientosExport;
use App\Models\CuentasCorrientes;
use App\Models\MovimientosFinancieros;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;


class MovimientosFinancierosController extends Controller
{

    public function index()
    {
        //$this->authorize('viewCaja'); // Puedes usar un policy o simplemente un if en la vista
        $movimientos1 = MovimientosFinancieros::orderBy('fecha', 'desc')
            ->select('fecha', 'descripcion', 'tipo', 'monto');

        $cuentas = CuentasCorrientes::join('clientes', 'clientes.id', 'cuentas_corrientes.id_cliente')
            ->select(
                'cuentas_corrientes.fecha_pago as fecha',
                DB::raw("concat('Pago mensualidad: ', clientes.nombres, ' ', clientes.paterno) as descripcion"),
                DB::raw("'ingreso' as tipo"), // Corregido: debe ser 'tipo', no 'descripcion'
                DB::raw("sum(cuentas_corrientes.monto_pagado) as monto")
            )
            ->whereNotNull('cuentas_corrientes.fecha_pago')
            ->groupBy('cuentas_corrientes.fecha_pago', 'clientes.nombres', 'clientes.paterno')
            ->orderBy('cuentas_corrientes.fecha_pago', 'desc');
        DB::enableQueryLog();
        $movimientos = $movimientos1->union($cuentas)->orderBy('fecha', 'desc')->get();
        //dd(DB::getQueryLog());

        $saldo = $movimientos->reduce(function ($acumulado, $item) {
            return $acumulado + (($item->tipo === 'ingreso') ? $item->monto : -$item->monto);
        }, 0);
        return view('movimientos.index', compact('movimientos', 'saldo'));
    }

    public function create()
    {
        //$this->authorize('viewCaja');
        return view('movimientos.create');
    }

    public function store(Request $request)
    {
        //$this->authorize('viewCaja');
        $request->validate([
            'fecha' => 'required|date',
            'descripcion' => 'required|string',
            'tipo' => 'required|in:ingreso,egreso',
            'monto' => 'required|numeric|min:0.01',
        ]);
        MovimientosFinancieros::create([
            'fecha' => $request->fecha,
            'descripcion' => $request->descripcion,
            'tipo' => $request->tipo,
            'monto' => $request->monto,
            'user_id' => Auth::id(),
        ]);
        return redirect()->route('caja.index')->with('success', 'Movimiento registrado');
    }

    public function exportExcel()
    {
        //$this->authorize('viewCaja');
        $fecha_actual = now()->format('Ymd_His');
        return Excel::download(new MovimientosExport, 'cartola_' . $fecha_actual . '.xlsx');
    }

    public function exportPdf()
    {
        //$this->authorize('viewCaja');
        $fecha_actual = now()->format('Ymd_His');

        $movimientos1 = MovimientosFinancieros::orderBy('fecha', 'desc')
            ->select('fecha', 'descripcion', 'tipo', 'monto');

        $cuentas = CuentasCorrientes::join('clientes', 'clientes.id', 'cuentas_corrientes.id_cliente')
            ->select(
                'cuentas_corrientes.fecha_pago as fecha',
                DB::raw("concat('Pago mensualidad: ', clientes.nombres, ' ', clientes.paterno) as descripcion"),
                DB::raw("'ingreso' as tipo"), // Corregido: debe ser 'tipo', no 'descripcion'
                DB::raw("sum(cuentas_corrientes.monto_pagado) as monto")
            )
            ->whereNotNull('cuentas_corrientes.fecha_pago')
            ->groupBy('cuentas_corrientes.fecha_pago', 'clientes.nombres', 'clientes.paterno')
            ->orderBy('cuentas_corrientes.fecha_pago', 'desc');
        DB::enableQueryLog();
        $movimientos = $movimientos1->union($cuentas)->orderBy('fecha', 'desc')->get();

        $saldo = $movimientos->reduce(function ($acumulado, $item) {
            return $acumulado + (($item->tipo === 'ingreso') ? $item->monto : -$item->monto);
        }, 0);
        $pdf = Pdf::loadView('movimientos.pdf', compact('movimientos', 'saldo'));
        return $pdf->download('cartola_' . $fecha_actual . '.pdf');
    }
}
