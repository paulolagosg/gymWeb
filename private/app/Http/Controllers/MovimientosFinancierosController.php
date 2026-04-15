<?php

namespace App\Http\Controllers;

use App\Models\Gimnasios;
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
    private function abortUnlessAdmin(): void
    {
        if (!Auth::check() || (int) Auth::user()->id_tipo_usuario !== 1) {
            abort(403, 'No tiene acceso');
        }
    }

    public function index()
    {
        $this->abortUnlessAdmin();
        $idGimnasio = Gimnasios::gimnasioActualId();
        $authUser = Auth::user();
        $movimientos1 = MovimientosFinancieros::orderBy('fecha', 'desc')
            ->when($idGimnasio, function ($query) use ($idGimnasio) {
                $query->where('id_gimnasio', $idGimnasio);
            })
            ->when((int) $authUser->id_tipo_usuario === 2, function ($query) use ($authUser) {
                $query->where('user_id', $authUser->id);
            })
            ->select('fecha', 'descripcion', 'tipo', 'monto');

        $cuentas = CuentasCorrientes::join('clientes', 'clientes.id', 'cuentas_corrientes.id_cliente')
            ->select(
                'cuentas_corrientes.fecha_pago as fecha',
                DB::raw("concat('Pago mensualidad: ', clientes.nombres, ' ', clientes.paterno) as descripcion"),
                DB::raw("'ingreso' as tipo"), // Corregido: debe ser 'tipo', no 'descripcion'
                DB::raw("sum(cuentas_corrientes.monto_pagado) as monto")
            )
            ->whereNotNull('cuentas_corrientes.fecha_pago')
            ->when($idGimnasio, function ($query) use ($idGimnasio) {
                $query->where('clientes.id_gimnasio', $idGimnasio);
            })
            ->when((int) $authUser->id_tipo_usuario === 2, function ($query) use ($authUser) {
                $query->where('clientes.id_usuario', $authUser->id);
            })
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
        $this->abortUnlessAdmin();
        return view('movimientos.create');
    }

    public function store(Request $request)
    {
        $this->abortUnlessAdmin();
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
            'id_gimnasio' => Gimnasios::gimnasioActualId(),
        ]);
        return redirect()->route('caja.index')->with('success', 'Movimiento registrado');
    }

    public function exportExcel()
    {
        $this->abortUnlessAdmin();
        $fecha_actual = now()->format('Ymd_His');
        return Excel::download(new MovimientosExport, 'cartola_' . $fecha_actual . '.xlsx');
    }

    public function exportPdf()
    {
        $this->abortUnlessAdmin();
        $fecha_actual = now()->format('Ymd_His');
        $idGimnasio = Gimnasios::gimnasioActualId();
        $authUser = Auth::user();

        $movimientos1 = MovimientosFinancieros::orderBy('fecha', 'desc')
            ->when($idGimnasio, function ($query) use ($idGimnasio) {
                $query->where('id_gimnasio', $idGimnasio);
            })
            ->when((int) $authUser->id_tipo_usuario === 2, function ($query) use ($authUser) {
                $query->where('user_id', $authUser->id);
            })
            ->select('fecha', 'descripcion', 'tipo', 'monto');

        $cuentas = CuentasCorrientes::join('clientes', 'clientes.id', 'cuentas_corrientes.id_cliente')
            ->select(
                'cuentas_corrientes.fecha_pago as fecha',
                DB::raw("concat('Pago mensualidad: ', clientes.nombres, ' ', clientes.paterno) as descripcion"),
                DB::raw("'ingreso' as tipo"), // Corregido: debe ser 'tipo', no 'descripcion'
                DB::raw("sum(cuentas_corrientes.monto_pagado) as monto")
            )
            ->whereNotNull('cuentas_corrientes.fecha_pago')
            ->when($idGimnasio, function ($query) use ($idGimnasio) {
                $query->where('clientes.id_gimnasio', $idGimnasio);
            })
            ->when((int) $authUser->id_tipo_usuario === 2, function ($query) use ($authUser) {
                $query->where('clientes.id_usuario', $authUser->id);
            })
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
