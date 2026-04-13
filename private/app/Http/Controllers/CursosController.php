<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CursosController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $usuario = auth()->user();
        $cursos = \App\Models\EntrenadoresCursos::where('id_entrenador', $usuario->id)
            ->orderBy('created_at', 'desc')
            ->select(
                'id',
                'curso',
                'fecha_inicio',
                'fecha_fin',
                DB::raw("case modalidad when 1 then 'Presencial' when 2 then 'On-line' else 'Híbrido' end modalidad"),
                'institucion',
                'slug'
            )
            ->get();
        return view('cursos.index', compact('cursos'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('cursos.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'curso' => 'required|string|max:255',
            'fecha_inicio' => 'nullable|date',
            'fecha_fin' => 'nullable|date|after_or_equal:fecha_inicio',
            'modalidad' => 'required|in:1,2,3',
            'institucion' => 'required|string|max:255',
        ]);
        $usuario = auth()->user();
        $validated['id_entrenador'] = $usuario->id;
        $validated['slug'] = hash('sha256', $validated['curso'] .  '-' . uniqid());

        \App\Models\EntrenadoresCursos::create($validated);

        return redirect()->route('cursos.index')->with('success', 'Curso creado exitosamente.');
    }
    /**
     * Show the form for editing the specified resource.
     */
    public function edit($slug)
    {
        $curso = \App\Models\EntrenadoresCursos::where('slug', $slug)->firstOrFail();
        return view('cursos.edit', compact('curso'));
    }
    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $slug)
    {
        $curso = \App\Models\EntrenadoresCursos::where('slug', $slug)->firstOrFail();
        $validated = $request->validate([
            'curso' => 'required|string|max:255',
            'fecha_inicio' => 'nullable|date',
            'fecha_fin' => 'nullable|date|after_or_equal:fecha_inicio',
            'modalidad' => 'required|in:1,2,3',
            'institucion' => 'required|string|max:255',
        ]);
        $validated['slug'] = hash('sha256', $validated['curso'] . '-' . uniqid());
        $curso->update($validated);
        return redirect()->route('cursos.index')->with('success', 'Curso actualizado exitosamente.');
    }
    /**
     * Remove the specified resource from storage.
     */
    public function destroy($slug)
    {
        $curso = \App\Models\EntrenadoresCursos::where('slug', $slug)->firstOrFail();
        $curso->delete();
        return redirect()->route('cursos.index')->with('success', 'Curso eliminado exitosamente.');
    }
    /**
     * Display the courses of a specific trainer.
     */
    public function cursos($slug)
    {
        $entrenador = \App\Models\User::where('slug', $slug)->firstOrFail();
        $cursos = \App\Models\EntrenadoresCursos::where('id_entrenador', $entrenador->id)
            ->select(
                'curso',
                'institucion',
                'fecha_inicio',
                'fecha_fin',
                DB::raw("case modalidad when 1 then 'Presencial' when 2 then 'Online' else 'Híbrido' end as modalidad")
            )
            ->get();
        return view('entrenadores.cursos', compact('entrenador', 'cursos'));
    }
}
