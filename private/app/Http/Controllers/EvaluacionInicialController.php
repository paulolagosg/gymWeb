<?php

namespace App\Http\Controllers;

use App\Models\Clientes;
use App\Models\EvaluacionInicial;
use App\Models\EvaluacionInicialOpcion;
use App\Models\EvaluacionInicialPregunta;
use App\Models\EvaluacionInicialSeccion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EvaluacionInicialController extends Controller
{
    public function index(): View
    {
        $this->abortUnlessAdmin();

        $secciones = EvaluacionInicialSeccion::with([
            'preguntas' => fn($query) => $query->orderBy('orden')->with([
                'opciones' => fn($optionQuery) => $optionQuery->orderBy('orden'),
            ]),
        ])->orderBy('orden')->get();

        return view('evaluacion_inicial.catalogo', compact('secciones'));
    }

    public function updatePregunta(Request $request, EvaluacionInicialPregunta $pregunta): RedirectResponse
    {
        $this->abortUnlessAdmin();

        $validated = $request->validate([
            'pregunta' => 'required|string|max:500',
            'descripcion' => 'nullable|string|max:2000',
            'orden' => 'required|integer|min:0|max:999',
            'es_requerida' => 'nullable|boolean',
            'estado' => 'nullable|boolean',
        ]);

        $pregunta->update([
            'pregunta' => $validated['pregunta'],
            'descripcion' => $validated['descripcion'] ?? null,
            'orden' => $validated['orden'],
            'es_requerida' => $request->boolean('es_requerida'),
            'estado' => $request->boolean('estado'),
        ]);

        return redirect()
            ->route('evaluacion-inicial.catalogo')
            ->with('success', 'Pregunta actualizada correctamente.');
    }

    public function storeOpcion(Request $request, EvaluacionInicialPregunta $pregunta): RedirectResponse
    {
        $this->abortUnlessAdmin();

        if ($pregunta->tipo === 'text') {
            return redirect()
                ->route('evaluacion-inicial.catalogo')
                ->withErrors(['error' => 'Las preguntas de texto no aceptan opciones.']);
        }

        $validated = $request->validate([
            'etiqueta' => 'required|string|max:255',
            'orden' => 'required|integer|min:0|max:999',
            'estado' => 'nullable|boolean',
        ]);

        $pregunta->opciones()->create([
            'codigo' => \Illuminate\Support\Str::slug($validated['etiqueta'], '_'),
            'etiqueta' => $validated['etiqueta'],
            'orden' => $validated['orden'],
            'estado' => $request->boolean('estado', true),
            'es_otro' => false,
        ]);

        return redirect()
            ->route('evaluacion-inicial.catalogo')
            ->with('success', 'Opción agregada correctamente.');
    }

    public function updateOpcion(Request $request, EvaluacionInicialOpcion $opcion): RedirectResponse
    {
        $this->abortUnlessAdmin();

        $validated = $request->validate([
            'etiqueta' => 'nullable|string|max:255',
            'orden' => 'required|integer|min:0|max:999',
            'estado' => 'nullable|boolean',
        ]);

        $etiqueta = $opcion->es_otro ? 'Otro/a' : ($validated['etiqueta'] ?? $opcion->etiqueta);

        $opcion->update([
            'codigo' => $opcion->es_otro ? 'otro' : \Illuminate\Support\Str::slug($etiqueta, '_'),
            'etiqueta' => $etiqueta,
            'orden' => $validated['orden'],
            'estado' => $request->boolean('estado'),
        ]);

        return redirect()
            ->route('evaluacion-inicial.catalogo')
            ->with('success', 'Opción actualizada correctamente.');
    }

    public function showCliente(Request $request, string $slug): View
    {
        $cliente = Clientes::where('slug', $slug)->firstOrFail();
        $this->abortUnlessClienteVisible($request, $cliente);

        $secciones = EvaluacionInicialSeccion::with([
            'preguntas' => fn($query) => $query
                ->where('estado', true)
                ->orderBy('orden')
                ->with([
                    'opciones' => fn($optionQuery) => $optionQuery->where('estado', true)->orderBy('orden'),
                ]),
        ])->where('estado', true)->orderBy('orden')->get();

        $evaluacion = EvaluacionInicial::with(['respuestas.opcion'])
            ->where('id_cliente', $cliente->id)
            ->first();

        $respuestasAgrupadas = [];
        foreach ($evaluacion?->respuestas ?? collect() as $respuesta) {
            $preguntaId = (int) $respuesta->pregunta_id;

            if (! isset($respuestasAgrupadas[$preguntaId])) {
                $respuestasAgrupadas[$preguntaId] = [
                    'selected_option_ids' => [],
                    'other_text' => null,
                    'text_value' => null,
                ];
            }

            if ($respuesta->opcion_id) {
                $respuestasAgrupadas[$preguntaId]['selected_option_ids'][] = (int) $respuesta->opcion_id;

                if ($respuesta->opcion?->es_otro && $respuesta->valor_texto) {
                    $respuestasAgrupadas[$preguntaId]['other_text'] = $respuesta->valor_texto;
                }
            } elseif ($respuesta->valor_texto) {
                $respuestasAgrupadas[$preguntaId]['text_value'] = $respuesta->valor_texto;
            }
        }

        return view('evaluacion_inicial.show', [
            'cliente' => $cliente,
            'evaluacion' => $evaluacion,
            'secciones' => $secciones,
            'respuestasAgrupadas' => $respuestasAgrupadas,
        ]);
    }

    private function abortUnlessAdmin(): void
    {
        if ((int) auth()->user()->id_tipo_usuario !== 1) {
            abort(403);
        }
    }

    private function abortUnlessClienteVisible(Request $request, Clientes $cliente): void
    {
        $user = $request->user();
        if (! $user) {
            abort(403);
        }

        if ((int) $user->id_tipo_usuario === 1) {
            return;
        }

        if ((int) $user->id_tipo_usuario === 2 && (int) $cliente->id_usuario === (int) $user->id) {
            return;
        }

        abort(403);
    }
}
