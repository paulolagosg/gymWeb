<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class EjerciciosController extends Controller
{
    public function index()
    {
        $ejercicios = \App\Models\Ejercicios::all(); // Obtener todos los ejercicios
        return view('ejercicios.index', compact('ejercicios'));
    }

    public function create()
    {
        $tipos = \App\Models\TipoEjercicio::orderBy('nombre')->get();
        return view('ejercicios.create', compact('tipos'));
    }

    public function store(Request $request)
    {
        // Lógica para almacenar un nuevo ejercicio
        $validatedData = $request->validate([
            'nombre' => 'required|string|max:255|unique:ejercicios,nombre',
            'descripcion' => 'nullable|string|max:500',
            'id_tipo' => 'required|integer|exists:tipos_ejercicios,id',
            'estado' => 'required|integer', // 1: activo, 0: inactivo
        ]);

        // Obtener el tipo de ejercicio para copiar su icono
        $tipo = \App\Models\TipoEjercicio::findOrFail($validatedData['id_tipo']);
        $validatedData['icono'] = $tipo->icono;
        $validatedData['slug'] = \Illuminate\Support\Str::slug($validatedData['nombre']);

        // Guardar el ejercicio en la base de datos
        \App\Models\Ejercicios::create($validatedData);

        return redirect()->route('ejercicios.index')->with('success', 'Ejercicio creado exitosamente.');
    }

    public function edit($slug)
    {
        $ejercicio = \App\Models\Ejercicios::where('slug', $slug)->firstOrFail();
        $tipos = \App\Models\TipoEjercicio::orderBy('nombre')->get();
        return view('ejercicios.edit', compact('slug', 'ejercicio', 'tipos'));
    }

    public function update(Request $request, $slug)
    {
        // Lógica para actualizar un ejercicio específico
        $ejercicio = \App\Models\Ejercicios::where('slug', $slug)->firstOrFail();

        $validatedData = $request->validate([
            'nombre' => 'required|string|max:255|unique:ejercicios,nombre,' . $ejercicio->id,
            'descripcion' => 'nullable|string|max:500',
            'id_tipo' => 'required|integer|exists:tipos_ejercicios,id',
            'estado' => 'required|integer', // 1: activo, 0: inactivo
        ]);

        // Obtener el tipo de ejercicio para copiar su icono
        $tipo = \App\Models\TipoEjercicio::findOrFail($validatedData['id_tipo']);
        $validatedData['icono'] = $tipo->icono;

        // Actualizar el ejercicio en la base de datos
        $ejercicio->update($validatedData);

        return redirect()->route('ejercicios.index')->with('success', 'Ejercicio actualizado exitosamente.');
    }

    public function destroy($slug)
    {
        $ejercicio = \App\Models\Ejercicios::where('slug', $slug)->firstOrFail();
        $ejercicio->delete();

        return redirect()->route('ejercicios.index')->with('success', 'Ejercicio eliminado exitosamente.');
    }

    public function toggleStatus($slug)
    {
        // Lógica para cambiar el estado de un ejercicio específico
        $ejercicio = \App\Models\Ejercicios::where('slug', $slug)->firstOrFail();
        $ejercicio->estado = !$ejercicio->estado; // Cambiar el estado (1 a 0 o 0 a 1)
        $ejercicio->save();

        return redirect()->route('ejercicios.index')->with('success', 'Estado del ejercicio actualizado exitosamente.');
    }

    // Endpoint AJAX: devuelve ejercicios por tipo
    public function porTipo($id)
    {
        $ejercicios = \App\Models\Ejercicios::where('id_tipo', $id)->where('estado', 1)->orderBy('nombre')->get(['id', 'nombre']);
        return response()->json(['success' => true, 'data' => $ejercicios]);
    }
}
