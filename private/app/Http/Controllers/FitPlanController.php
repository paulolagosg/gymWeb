<?php

namespace App\Http\Controllers;

use App\Models\Clientes;
use App\Models\Cuestionarios;
use App\Models\CuestionariosHistoricos;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FitPlanController extends Controller
{
    public function create($slug)
    {
        // Aquí puedes cargar el cliente y mostrar el formulario para crear un plan de acondicionamiento físico
        $cliente = \App\Models\Clientes::where('slug', $slug)->firstOrFail();
        return view('fit_evolution.create', compact('cliente'));
    }

    public function store(Request $request, $slug)
    {
        //
        $cliente = Clientes::where('slug', $slug)->firstOrFail();
        try {
            DB::beginTransaction();
            // Validar los datos del formulario
            $request->validate([
                'patologias' => 'nullable|string|max:255',
                'horario' => 'nullable|string|max:255',
                'encantan' => 'nullable|string|max:255',
                'no_gustan' => 'nullable|string|max:255',
                'intolerancias' => 'nullable|string|max:255',
                'suplemento' => 'nullable|string|max:255',
                'duracion_entreno' => 'nullable|string|max:255',
                'hora_gimnasio' => 'nullable|string|max:255',
                'trabajo' => 'nullable|string|max:255',
                'hora_acostarse' => 'nullable|string|max:255',
                'hora_levantarse' => 'nullable|string|max:255',
                'objetivo' => 'nullable|string|max:255',
                'datos_interes' => 'nullable|string|max:255',
                'dia_cualquiera' => 'nullable|string|max:255',
            ]);
            DB::enableQueryLog();
            // Crear el cuestionario y el histórico
            $cuestionario = Cuestionarios::where('id_cliente', $cliente->id)->first();
            $redirect = route('fitplan.create', ['slug' => $cliente->slug]);
            if ($cuestionario) {
                $redirect = route('fitplan.edit', ['slug' => $cliente->slug]);
                // Actualizar el cuestionario existente
                $cuestionario->update([
                    'patologias' => $request->input('patologias'),
                    'horario' => $request->input('horario'),
                    'encantan' => $request->input('encantan'),
                    'no_gustan' => $request->input('no_gustan'),
                    'intolerancias' => $request->input('intolerancias'),
                    'suplemento' => $request->input('suplemento'),
                    'duracion_entreno' => $request->input('duracion_entreno'),
                    'hora_gimnasio' => $request->input('hora_gimnasio'),
                    'trabajo' => $request->input('trabajo'),
                    'hora_acostarse' => $request->input('hora_acostarse'),
                    'hora_levantarse' => $request->input('hora_levantarse'),
                    'objetivo' => $request->input('objetivo'),
                    'datos_interes' => $request->input('datos_interes'),
                    'dia_cualquiera' => $request->input('dia_cualquiera'),
                ]);
            } else {
                // Crear un nuevo cuestionario
                $cuestionario = Cuestionarios::create([
                    'patologias' => $request->input('patologias'),
                    'horario' => $request->input('horario'),
                    'encantan' => $request->input('encantan'),
                    'no_gustan' => $request->input('no_gustan'),
                    'intolerancias' => $request->input('intolerancias'),
                    'suplemento' => $request->input('suplemento'),
                    'duracion_entreno' => $request->input('duracion_entreno'),
                    'hora_gimnasio' => $request->input('hora_gimnasio'),
                    'trabajo' => $request->input('trabajo'),
                    'hora_acostarse' => $request->input('hora_acostarse'),
                    'hora_levantarse' => $request->input('hora_levantarse'),
                    'objetivo' => $request->input('objetivo'),
                    'datos_interes' => $request->input('datos_interes'),
                    'dia_cualquiera' => $request->input('dia_cualquiera'),
                    'id_cliente' => $cliente->id,
                ]);
            }
            // Crear un registro histórico del cuestionario
            CuestionariosHistoricos::create([
                'id_cuestionario' => $cuestionario->id,
                'patologias' => $request->input('patologias'),
                'horario' => $request->input('horario'),
                'encantan' => $request->input('encantan'),
                'no_gustan' => $request->input('no_gustan'),
                'intolerancias' => $request->input('intolerancias'),
                'suplemento' => $request->input('suplemento'),
                'duracion_entreno' => $request->input('duracion_entreno'),
                'hora_gimnasio' => $request->input('hora_gimnasio'),
                'trabajo' => $request->input('trabajo'),
                'hora_acostarse' => $request->input('hora_acostarse'),
                'hora_levantarse' => $request->input('hora_levantarse'),
                'objetivo' => $request->input('objetivo'),
                'datos_interes' => $request->input('datos_interes'),
                'dia_cualquiera' => $request->input('dia_cualquiera'),
                'id_cliente' => $cliente->id,
            ]);
            //dd('cuestionario historico creado');


            DB::commit();
            // Redirigir a la página de edición del cuestionario
            return redirect()->route('fitplan.edit', ['slug' => $cliente->slug])->with('success', 'Cuestionario registrado correctamente.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Error al registrar elcuestionario: ' . $e->getMessage());
        }
    }
    public function edit($slug)
    {
        // Aquí puedes cargar el plan de acondicionamiento físico del cliente para editar
        $cliente = \App\Models\Clientes::where('slug', $slug)->firstOrFail();
        $cuestionario = Cuestionarios::where('id_cliente', $cliente->id)->first();
        if (!$cuestionario) {
            return redirect()->route('fitplan.create', ['slug' => $cliente->slug])->with('error', 'No se encontró un cuestionario para este cliente.');
        }
        return view('fit_evolution.edit', compact('cliente', 'cuestionario'));
    }
    public function update(Request $request, $slug)
    {
        // Aquí puedes actualizar el plan de acondicionamiento físico del cliente
        $cliente = \App\Models\Clientes::where('slug', $slug)->firstOrFail();
        $cuestionario = Cuestionarios::where('id_cliente', $cliente->id)->first();
        if (!$cuestionario) {
            return redirect()->route('fitplan.create', ['slug' => $cliente->slug])->with('error', 'No se encontró un cuestionario  para este cliente.');
        }

        try {
            DB::beginTransaction();
            // Validar los datos del formulario
            $request->validate([
                'patologias' => 'nullable|string|max:255',
                'horario' => 'nullable|string|max:255',
                'encantan' => 'nullable|string|max:255',
                'no_gustan' => 'nullable|string|max:255',
                'intolerancias' => 'nullable|string|max:255',
                'suplemento' => 'nullable|string|max:255',
                'duracion_entreno' => 'nullable|string|max:255',
                'hora_gimnasio' => 'nullable|string|max:255',
                'trabajo' => 'nullable|string|max:255',
                'hora_acostarse' => 'nullable|string|max:255',
                'hora_levantarse' => 'nullable|string|max:255',
                'objetivo' => 'nullable|string|max:255',
                'datos_interes' => 'nullable|string|max:255',
                'dia_cualquiera' => 'nullable|string|max:255',
            ]);

            // Actualizar el cuestionario existente
            $cuestionario->update([
                'patologias' => $request->input('patologias'),
                'horario' => $request->input('horario'),
                'encantan' => $request->input('encantan'),
                'no_gustan' => $request->input('no_gustan'),
                'intolerancias' => $request->input('intolerancias'),
                'suplemento' => $request->input('suplemento'),
                'duracion_entreno' => $request->input('duracion_entreno'),
                'hora_gimnasio' => $request->input('hora_gimnasio'),
                'trabajo' => $request->input('trabajo'),
                'hora_acostarse' => $request->input('hora_acostarse'),
                'hora_levantarse' => $request->input('hora_levantarse'),
                'objetivo' => $request->input('objetivo'),
                'datos_interes' => $request->input('datos_interes'),
                'dia_cualquiera' => $request->input('dia_cualquiera'),
            ]);
            // Crear un registro histórico del cuestionario
            CuestionariosHistoricos::create([
                'id_cuestionario' => $cuestionario->id,
                'patologias' => $request->input('patologias'),
                'horario' => $request->input('horario'),
                'encantan' => $request->input('encantan'),
                'no_gustan' => $request->input('no_gustan'),
                'intolerancias' => $request->input('intolerancias'),
                'suplemento' => $request->input('suplemento'),
                'duracion_entreno' => $request->input('duracion_entreno'),
                'hora_gimnasio' => $request->input('hora_gimnasio'),
                'trabajo' => $request->input('trabajo'),
                'hora_acostarse' => $request->input('hora_acostarse'),
                'hora_levantarse' => $request->input('hora_levantarse'),
                'objetivo' => $request->input('objetivo'),
                'datos_interes' => $request->input('datos_interes'),
                'dia_cualquiera' => $request->input('dia_cualquiera'),
                'id_cliente' => $cliente->id,
            ]);
            DB::commit();
            return redirect()->route('fitplan.edit', ['slug' => $cliente->slug])->with('success', 'Cuestionario actualizado correctamente.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Error al actualizar el cuestionario: ' . $e->getMessage());
        }
    }

    public function show($slug)
    {
        // Aquí puedes mostrar el plan de acondicionamiento físico del cliente
    }
}
