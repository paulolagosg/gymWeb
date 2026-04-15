<?php

namespace App\Http\Controllers;

use App\Models\Gimnasios;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CursosController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $usuario = Auth::user();
        $esAdminLike = in_array((int) $usuario->id_tipo_usuario, [1, 10], true) || (int) $usuario->id_clasificacion === 3;
        $esSuperAdmin = (int) $usuario->id_tipo_usuario === 10;
        $idGimnasio = $esSuperAdmin
            ? ($request->filled('id_gimnasio') ? (int) $request->input('id_gimnasio') : null)
            : Gimnasios::gimnasioActualId();
        $gimnasios = Gimnasios::where('estado', 1)->orderBy('nombre')->get();
        $gimnasioSeleccionado = $idGimnasio;
        $usuarios = User::where('id_tipo_usuario', 2)
            ->when($idGimnasio, function ($query) use ($idGimnasio) {
                $query->where('id_gimnasio', $idGimnasio);
            })
            ->orderBy('name')
            ->get();

        if ($esAdminLike) {
            $cursos = \App\Models\EntrenadoresCursos::join('users', 'users.id', '=', 'entrenadores_cursos.id_entrenador')
                ->leftJoin('gimnasios', 'gimnasios.id', '=', 'users.id_gimnasio')
                ->when($idGimnasio, function ($query) use ($idGimnasio) {
                    $query->where('users.id_gimnasio', $idGimnasio);
                })
                ->orderBy('entrenadores_cursos.created_at', 'desc')
                ->select(
                    'entrenadores_cursos.*',
                    DB::raw("case entrenadores_cursos.modalidad when 1 then 'Presencial' when 2 then 'On-line' else 'Híbrido' end as modalidad_label"),
                    DB::raw('users.name as entrenador_nombre'),
                    DB::raw('gimnasios.nombre as gimnasio_nombre')
                )
                ->get();
        } else {
            $cursos = \App\Models\EntrenadoresCursos::where('id_entrenador', $usuario->id)
                ->orderBy('created_at', 'desc')
                ->select(
                    'id',
                    'curso',
                    'fecha_inicio',
                    'fecha_fin',
                    DB::raw("case modalidad when 1 then 'Presencial' when 2 then 'On-line' else 'Híbrido' end as modalidad_label"),
                    'institucion',
                    'slug'
                )
                ->get();
        }
        return view('cursos.index', compact('cursos', 'gimnasios', 'gimnasioSeleccionado', 'usuarios'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $usuario = Auth::user();
        $esAdminLike = in_array((int) $usuario->id_tipo_usuario, [1, 10], true) || (int) $usuario->id_clasificacion === 3;
        $idGimnasio = (int) $usuario->id_tipo_usuario === 10
            ? (request()->filled('id_gimnasio') ? (int) request()->input('id_gimnasio') : null)
            : Gimnasios::gimnasioActualId();
        $usuarios = $esAdminLike
            ? User::where('id_tipo_usuario', 2)->when($idGimnasio, function ($query) use ($idGimnasio) {
                $query->where('id_gimnasio', $idGimnasio);
            })->orderBy('name')->get()
            : collect([$usuario]);
        $gimnasios = Gimnasios::where('estado', 1)->orderBy('nombre')->get();
        $gimnasioSeleccionado = $idGimnasio;

        return view('cursos.create', compact('usuarios', 'gimnasios', 'gimnasioSeleccionado', 'esAdminLike'));
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
            'id_entrenador' => 'nullable|exists:users,id',
        ]);
        $usuario = Auth::user();
        $esAdminLike = in_array((int) $usuario->id_tipo_usuario, [1, 10], true) || (int) $usuario->id_clasificacion === 3;
        $validated['id_entrenador'] = $esAdminLike ? (int) $validated['id_entrenador'] : $usuario->id;
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
        $usuario = Auth::user();
        $esAdminLike = in_array((int) $usuario->id_tipo_usuario, [1, 10], true) || (int) $usuario->id_clasificacion === 3;
        $gimnasios = Gimnasios::where('estado', 1)->orderBy('nombre')->get();
        $usuarios = $esAdminLike ? User::where('id_tipo_usuario', 2)->orderBy('name')->get() : collect([$usuario]);
        return view('cursos.edit', compact('curso', 'usuarios', 'gimnasios', 'esAdminLike'));
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
            'id_entrenador' => 'nullable|exists:users,id',
        ]);
        $usuario = Auth::user();
        $esAdminLike = in_array((int) $usuario->id_tipo_usuario, [1, 10], true) || (int) $usuario->id_clasificacion === 3;
        $validated['id_entrenador'] = $esAdminLike ? (int) $validated['id_entrenador'] : $curso->id_entrenador;
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
