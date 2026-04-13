<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PlanesController extends Controller
{
    public function index()
    {
        if (auth()->user()->id_tipo_usuario != 1) {
            abort(403);
        } else {
            $planes = \App\Models\Planes::all(); // Obtener todos los planes
            return view('planes.index', compact('planes'));
        }
    }
    public function create()
    {
        if (auth()->user()->id_tipo_usuario != 1) {
            abort(403);
        } else {
            return view('planes.create');
        }
    }
    public function store(Request $request)
    {
        // Validación y almacenamiento del plan
        $validatedData = $request->validate([
            'nombre' => 'required|string|max:255|unique:planes',
            'descripcion' => 'nullable|string|max:500',
            'valor' => 'required|integer|min:0',
            'porcentaje' => 'nullable|integer|min:0|max:100', // Validación para el porcentaje
            'estado' => 'required|integer',
        ]);

        // Generar el slug automáticamente
        $validatedData['slug'] = Str::slug($validatedData['nombre']);

        // Guardar el plan en la base de datos
        \App\Models\Planes::create($validatedData);

        return redirect()->route('planes.index')->with('success', 'Plan creado exitosamente.');
    }
    public function edit($slug)
    {
        $plan = \App\Models\Planes::where('slug', $slug)->firstOrFail();
        return view('planes.edit', compact('plan'));
    }
    public function update(Request $request, $slug)
    {
        $plan = \App\Models\Planes::where('slug', $slug)->firstOrFail();

        // Validación y actualización del plan
        $validatedData = $request->validate([
            'nombre' => 'required|string|max:255|unique:planes,nombre,' . $plan->id,
            'descripcion' => 'nullable|string|max:500',
            'valor' => 'required|integer|min:0',
            'porcentaje' => 'nullable|integer|min:0|max:100', // Validación para el porcentaje
            'estado' => 'required|integer',
        ]);

        // Generar el slug automáticamente
        $validatedData['slug'] = \Illuminate\Support\Str::slug($validatedData['nombre']);

        // Actualizar el plan en la base de datos
        $plan->update($validatedData);

        return redirect()->route('planes.index')->with('success', 'Plan actualizado exitosamente.');
    }
    public function destroy($slug)
    {
        $plan = \App\Models\Planes::where('slug', $slug)->firstOrFail();
        $plan->delete();

        return redirect()->route('planes.index')->with('success', 'Plan eliminado exitosamente.');
    }
    public function show($id)
    {
        // Aquí puedes implementar la lógica para mostrar los detalles de un plan
        $plan = \App\Models\Planes::findOrFail($id);
        return view('planes.show', compact('plan'));
    }
    public function toggleStatus($slug)
    {
        $plan = \App\Models\Planes::where('slug', $slug)->firstOrFail();
        DB::enableQueryLog();
        $plan->estado = $plan->estado == 0 ? 1 : 0; // Cambia el estado de 0 a 1 o de 1 a 
        $plan->save();

        return redirect()->route('planes.index')->with('success', 'Estado del plan actualizado.');
    }
}
