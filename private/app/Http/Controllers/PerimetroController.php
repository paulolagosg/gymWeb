<?php

namespace App\Http\Controllers;

use App\Models\Clientes;
use App\Models\Perimetro;
use Illuminate\Http\Request;

class PerimetroController extends Controller
{
    public function index($slug)
    {
        $cliente = Clientes::where('slug', $slug)->first();
        $perimetros = Perimetro::join('clientes', 'clientes.id', 'perimetros.id_cliente')
            ->where('clientes.slug', $slug)
            ->select('perimetros.*')
            ->orderBy('perimetros.created_at', 'desc')
            ->get();
        $perimetroReciente = $perimetros->first();
        return view('clientes.perimetros', compact('cliente', 'perimetros', 'perimetroReciente'));
    }

    public function storePerimetro(Request $request, $slug)
    {
        $cliente = \App\Models\Clientes::where('slug', $slug)->firstOrFail();

        $request->validate([
            'cabeza' => 'nullable|numeric|min:0',
            'brazo_relajado' => 'nullable|numeric|min:0',
            'brazo_flexionado_tension' => 'nullable|numeric|min:0',
            'antebrazo' => 'nullable|numeric|min:0',
            'torax_mesoexternal' => 'nullable|numeric|min:0',
            'cintura_minima' => 'nullable|numeric|min:0',
            'caderas_maxima' => 'nullable|numeric|min:0',
            'muslo_superior' => 'nullable|numeric|min:0',
            'muslo_medial' => 'nullable|numeric|min:0',
            'pantorrilla_maxima' => 'nullable|numeric|min:0',
            'created_at' => 'nullable|date_format:Y-m-d',
        ]);

        $per = new \App\Models\Perimetro($request->only([
            'cabeza',
            'brazo_relajado',
            'brazo_flexionado_tension',
            'antebrazo',
            'torax_mesoexternal',
            'cintura_minima',
            'caderas_maxima',
            'muslo_superior',
            'muslo_medial',
            'pantorrilla_maxima'
        ]));

        $per->id_cliente = $cliente->id;

        if ($request->filled('created_at')) {
            $per->created_at = \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $request->created_at . ' 13:00:00');
        }

        $per->save();

        return redirect()->route('clientes.perimetros', $cliente->slug)->with('success', 'Perímetro registrado correctamente.');
    }
}
