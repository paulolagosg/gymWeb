<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TareasController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $usuario = auth()->user();
        if ($usuario->id_tipo_usuario == 1 or intval($usuario->id_clasificacion) == 3) {
            $tareas = \App\Models\Tareas::join('users', 'users.id', 'tareas.id_usuario')
                ->orderBy('tareas.created_at', 'desc')
                ->select('tareas.*', DB::raw('users.slug as entrenador'))
                ->get();
        } else {
            $tareas = \App\Models\Tareas::join('users', 'users.id', 'tareas.id_usuario')
                ->where('id_usuario', $usuario->id)
                ->select('tareas.*', DB::raw('users.slug as entrenador'))
                ->get();
        }
        $usuarios = \App\Models\User::where('id_tipo_usuario', 2)->get(); // Obtener todos los entrenadores
        $tareas->each(function ($tarea) {
            $tarea->fecha_limite = $tarea->fecha_limite
                ? Carbon::parse($tarea->fecha_limite)->format('d/m/Y')
                : null;
            $tarea->completada = $tarea->completada ? 'Completada' : 'Pendiente';
        });
        return view('tareas.index', compact('tareas', 'usuarios'));
    }
    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $usuarios = \App\Models\User::where('id_tipo_usuario', 2)->get(); // Obtener todos los entrenadores

        return view('tareas.create', compact('usuarios'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'fecha_limite' => 'nullable|date',
            'id_usuario' => 'required|exists:users,id',
        ]);

        $validated['slug'] = \Illuminate\Support\Str::slug($validated['nombre']) . '-' . uniqid();
        \App\Models\Tareas::create($validated);

        return redirect()->route('tareas.index')->with('success', 'Tarea creada exitosamente.');
    }
    /**
     * Display the specified resource.
     */
    public function show($slug)
    {
        $tarea = \App\Models\Tareas::where('slug', $slug)->firstOrFail();
        $tarea->fecha_limite = $tarea->fecha_limite
            ? Carbon::parse($tarea->fecha_limite)->format('d/m/Y')
            : null;

        //$tarea->completada = $tarea->completada ? 'Completada' : 'Pendiente';
        return view('tareas.show', compact('tarea'));
    }
    /**
     * Show the form for editing the specified resource.
     */
    public function edit($slug)
    {
        $tarea = \App\Models\Tareas::where('slug', $slug)->firstOrFail();
        $usuarios = \App\Models\User::where('id_tipo_usuario', 2)->get(); // Obtener todos los entrenadores

        return view('tareas.edit', compact('tarea', 'usuarios'));
    }
    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $slug)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'fecha_limite' => 'nullable|date',
            'id_usuario' => 'required|exists:users,id',
            'completada' => 'boolean',
        ]);
        $tarea = \App\Models\Tareas::where('slug', $slug)->firstOrFail();
        $tarea->update($validated);
        return redirect()->route('tareas.index')->with('success', 'Tarea actualizada exitosamente.');
    }
    /**
     * Remove the specified resource from storage.
     */
    public function destroy($slug)
    {
        $tarea = \App\Models\Tareas::where('slug', $slug)->firstOrFail();
        $tarea->delete();
        return redirect()->route('tareas.index')->with('success', 'Tarea eliminada exitosamente.');
    }
    public function procesarCompletar($slug)
    {
        $tarea = \App\Models\Tareas::where('slug', $slug)->firstOrFail();
        $tarea->completada = !$tarea->completada;
        $tarea->save();
        return redirect()->route('tareas.index')->with('success', 'Estado de la tarea actualizado exitosamente.');
    }
    public function misTareas()
    {
        $usuario = auth()->user();
        $tareas = \App\Models\Tareas::where('id_usuario', $usuario->id)->get();
        if ($tareas->isEmpty()) {
            return view('tareas.index', ['message' => 'No tienes tareas asignadas.']);
        }
        $tareas->each(function ($tarea) {
            $tarea->fecha_limite = $tarea->fecha_limite ? $tarea->fecha_limite->format('d/m/Y') : null;
            $tarea->completada = $tarea->completada ? 'Sí' : 'No';
        });
        return view('tareas.index', compact('tareas'));
    }
    public function tareasPorUsuario($id_usuario)
    {
        $tareas = \App\Models\Tareas::where('id_usuario', $id_usuario)->get();
        if ($tareas->isEmpty()) {
            return view('tareas.index', ['message' => 'No hay tareas asignadas a este usuario.']);
        }
        $tareas->each(function ($tarea) {
            $tarea->fecha_limite = $tarea->fecha_limite ? $tarea->fecha_limite->format('d/m/Y') : null;
            $tarea->completada = $tarea->completada ? 'Sí' : 'No';
        });
        return view('tareas.index', compact('tareas'));
    }
    public function tareasPorCliente($id_cliente)
    {
        $tareas = \App\Models\Tareas::where('id_cliente', $id_cliente)->get();
        if ($tareas->isEmpty()) {
            return view('tareas.index', ['message' => 'No hay tareas asignadas a este cliente.']);
        }
        $tareas->each(function ($tarea) {
            $tarea->fecha_limite = $tarea->fecha_limite ? $tarea->fecha_limite->format('d/m/Y') : null;
            $tarea->completada = $tarea->completada ? 'Sí' : 'No';
        });
        return view('tareas.index', compact('tareas'));
    }
}
