<?php

namespace App\Http\Controllers;

use App\Models\Clientes;
use App\Models\ParqPreguntas;
use App\Models\ParqRespuestas;
use Illuminate\Http\Request;

class ParqController extends Controller
{
    public function create($slug)
    {
        $cliente = Clientes::where('slug', $slug)->firstOrFail();
        $preguntas = ParqPreguntas::where('activa', true)->get();
        return view('parq.create', compact('cliente', 'preguntas'));
    }

    public function store(Request $request, $slug)
    {
        $cliente = Clientes::where('slug', $slug)->firstOrFail();
        $preguntas = ParqPreguntas::where('activa', true)->get();

        foreach ($preguntas as $pregunta) {
            ParqRespuestas::create([
                'id_cliente' => $cliente->id,
                'id_pregunta' => $pregunta->id,
                'respuesta' => $request->input('pregunta_' . $pregunta->id),
                'observaciones' => $request->input('observaciones_' . $pregunta->id),
            ]);
        }

        return redirect()->route('clientes.opciones.portada', $cliente->slug)
            ->with('success', 'Cuestionario PAR-Q registrado correctamente.');
    }

    public function show($slug)
    {
        $cliente = Clientes::where('slug', $slug)->firstOrFail();
        $respuestas = ParqRespuestas::with('pregunta')
            ->where('id_cliente', $cliente->id)
            ->get()
            ->groupBy('id_pregunta');
        return view('parq.show', compact('cliente', 'respuestas'));
    }
}
