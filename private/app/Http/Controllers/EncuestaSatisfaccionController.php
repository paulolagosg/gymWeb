<?php

namespace App\Http\Controllers;

use App\Models\EncuestaSatisfaccion;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EncuestaSatisfaccionController extends Controller
{
    public function index($slug, $origen)
    {
        $entrenador = User::where('slug', $slug)->firstOrFail();
        $encuestas = EncuestaSatisfaccion::where('id_entrenador', $entrenador->id)->get();

        $resumen = [];
        $respuestas_encuestas = EncuestaSatisfaccion::where('id_entrenador', $entrenador->id);
        $count = $respuestas_encuestas->count();
        if ($count > 0) {
            $resumen = [
                'entrenador' => $entrenador,
                'profesionalismo' => round($respuestas_encuestas->avg('profesionalismo'), 2),
                'claridad' => round($respuestas_encuestas->avg('claridad'), 2),
                'motivacion' => round($respuestas_encuestas->avg('motivacion'), 2),
                'disponibilidad' => round($respuestas_encuestas->avg('disponibilidad'), 2),
                'puntualidad' => round($respuestas_encuestas->avg('puntualidad'), 2),
                'valoracion_global' => round($respuestas_encuestas->avg('valoracion_global'), 2),
            ];
        }

        return view('encuestas.index', compact('entrenador', 'encuestas', 'origen', 'resumen'));
    }

    public function create($slug)
    {
        $entrenador = User::where('slug', $slug)->firstOrFail();
        return view('encuestas.create', compact('entrenador'));
    }

    public function store(Request $request, $slug)
    {
        $request->validate([
            'profesionalismo' => 'required|integer|min:1|max:5',
            'claridad' => 'required|integer|min:1|max:5',
            'motivacion' => 'required|integer|min:1|max:5',
            'disponibilidad' => 'required|integer|min:1|max:5',
            'puntualidad' => 'required|integer|min:1|max:5',
            'destacaria' => 'nullable|string|max:4000',
            'sugerencias' => 'nullable|string|max:4000',
            'valoracion_global' => 'required|integer|min:1|max:10',
        ]);
        $entrenador = User::where('slug', $slug)->firstOrFail();

        $slug = hash('sha256', $entrenador->id . Auth::id() . uniqid());

        EncuestaSatisfaccion::create([
            'id_cliente' => Auth::id(),
            'id_entrenador' => $entrenador->id,
            'profesionalismo' => $request->profesionalismo,
            'claridad' => $request->claridad,
            'motivacion' => $request->motivacion,
            'disponibilidad' => $request->disponibilidad,
            'puntualidad' => $request->puntualidad,
            'destacaria' => $request->destacaria,
            'sugerencias' => $request->sugerencias,
            'valoracion_global' => $request->valoracion_global,
            'slug' => $slug,
        ]);

        return view('encuestas.gracias');
    }

    public function gracias()
    {
        return view('encuestas.gracias');
    }

    public function show($slug)
    {
        $encuesta = EncuestaSatisfaccion::where('slug', $slug)->firstOrFail();
        $entrenador = User::where('id', $encuesta->id_entrenador)->firstOrFail();

        return view('encuestas.show', compact('encuesta', 'entrenador'));
    }
}
