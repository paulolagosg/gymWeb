<?php

namespace App\Http\Controllers;

use App\Models\PagoEntrenador;
use App\Models\HistorialTarifaEntrenador;
use App\Models\User;
use Illuminate\Http\Request;
use Carbon\Carbon;

class PagoEntrenadorController extends Controller
{
    public function index(Request $request)
    {
        $month = $request->input('month', Carbon::now()->month);
        $year  = $request->input('year', Carbon::now()->year);

        $entrenadores = User::where('id_tipo_usuario', 2)->orderBy('name')->get();

        $registros = PagoEntrenador::where('year', $year)
            ->where('month', $month)
            ->get()
            ->keyBy('entrenador_id');

        $tarifas = HistorialTarifaEntrenador::where('year', $year)
            ->where('month', $month)
            ->get()
            ->keyBy('entrenador_id');

        return view('pagos_entrenadores.index', compact('entrenadores', 'registros', 'tarifas', 'month', 'year'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'month' => 'required|integer|min:1|max:12',
            'year'  => 'required|integer|min:2000|max:2100',
            'entrenadores' => 'required|array',
            'entrenadores.*.id' => 'required|integer|exists:users,id',
            'entrenadores.*.sesiones_individual' => 'nullable|integer|min:0',
            'entrenadores.*.sesiones_duo' => 'nullable|integer|min:0',
            'entrenadores.*.bono' => 'nullable|numeric|min:0',
            'entrenadores.*.descuento' => 'nullable|numeric|min:0',
        ]);

        foreach ($data['entrenadores'] as $item) {
            $entrenador = User::find($item['id']);
            if (!$entrenador) continue;

            $ses_ind = intval($item['sesiones_individual'] ?? 0);
            $ses_duo = intval($item['sesiones_duo'] ?? 0);
            $bono = floatval($item['bono'] ?? 0);
            $descuento = floatval($item['descuento'] ?? 0);

            $tarifa = HistorialTarifaEntrenador::where('entrenador_id', $entrenador->id)
                ->where('year', $data['year'])
                ->where('month', $data['month'])
                ->first();

            if (!$tarifa) {
                $tarifa = HistorialTarifaEntrenador::create([
                    'entrenador_id' => $entrenador->id,
                    'year' => $data['year'],
                    'month' => $data['month'],
                    'individual' => $entrenador->individual ?? 0,
                    'duo' => $entrenador->duo ?? 0,
                ]);
            }

            $valor_ind = $tarifa->individual ?? 0;
            $valor_duo = $tarifa->duo ?? 0;
            $total = round($ses_ind * $valor_ind + $ses_duo * $valor_duo + $bono - $descuento, 2);

            PagoEntrenador::updateOrCreate(
                [
                    'entrenador_id' => $entrenador->id,
                    'year' => $data['year'],
                    'month' => $data['month'],
                ],
                [
                    'sesiones_individual' => $ses_ind,
                    'sesiones_duo' => $ses_duo,
                    'valor_individual' => $valor_ind,
                    'valor_duo' => $valor_duo,
                    'bono' => $bono,
                    'descuento' => $descuento,
                    'total' => $total,
                ]
            );
        }

        return redirect()->route('pagos_entrenadores.index', ['month' => $data['month'], 'year' => $data['year']])
            ->with('success', 'Pagos guardados');
    }

    public function destroy($id)
    {
        PagoEntrenador::findOrFail($id)->delete();
        return back()->with('success', 'Registro eliminado');
    }
}
