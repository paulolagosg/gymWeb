<?php

namespace App\Http\Controllers;

use App\Models\Gimnasios;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class PlanesController extends Controller
{
    private function abortUnlessAdmin(): void
    {
        if (!Auth::check() || !in_array((int) Auth::user()->id_tipo_usuario, [1, 10], true)) {
            abort(403, 'No tiene acceso');
        }
    }

    public function index(Request $request)
    {
        $this->abortUnlessAdmin();

        $esSuperAdmin = (int) Auth::user()->id_tipo_usuario === 10;
        $idGimnasio = $esSuperAdmin
            ? ($request->filled('id_gimnasio') ? (int) $request->input('id_gimnasio') : null)
            : Gimnasios::gimnasioActualId();
        $gimnasios = Gimnasios::where('estado', 1)->orderBy('nombre')->get();
        $gimnasioSeleccionado = $idGimnasio;

        $planes = \App\Models\Planes::with('gimnasio')->when($idGimnasio, function ($query) use ($idGimnasio) {
            $query->where('id_gimnasio', $idGimnasio);
        })->get();

        return view('planes.index', compact('planes', 'gimnasios', 'gimnasioSeleccionado'));
    }

    public function create()
    {
        $this->abortUnlessAdmin();

        $gimnasios = Gimnasios::where('estado', 1)->orderBy('nombre')->get();
        $gimnasioSeleccionado = (int) request()->input('id_gimnasio', Gimnasios::gimnasioActualId());

        return view('planes.create', compact('gimnasios', 'gimnasioSeleccionado'));
    }

    public function store(Request $request)
    {
        $this->abortUnlessAdmin();
        $esSuperAdmin = (int) Auth::user()->id_tipo_usuario === 10;
        $idGimnasio = $esSuperAdmin ? null : Gimnasios::gimnasioActualId();

        $validatedData = $request->validate([
            'nombre' => [
                'required',
                'string',
                'max:255',
                Rule::unique('planes', 'nombre')->where(function ($query) use ($idGimnasio) {
                    if ($idGimnasio) {
                        $query->where('id_gimnasio', $idGimnasio);
                    }

                    return $query;
                }),
            ],
            'descripcion' => 'nullable|string|max:500',
            'valor' => 'required|integer|min:0',
            'porcentaje' => 'nullable|integer|min:0|max:100',
            'estado' => 'required|integer',
            'id_gimnasio' => ($esSuperAdmin ? 'required' : 'nullable') . '|exists:gimnasios,id',
        ]);

        $validatedData['slug'] = Str::slug($validatedData['nombre']);
        $validatedData['id_gimnasio'] = $esSuperAdmin
            ? (int) $validatedData['id_gimnasio']
            : Gimnasios::gimnasioActualId();

        \App\Models\Planes::create($validatedData);

        return redirect()->route('planes.index')->with('success', 'Plan creado exitosamente.');
    }

    public function edit($slug)
    {
        $this->abortUnlessAdmin();
        $esSuperAdmin = (int) Auth::user()->id_tipo_usuario === 10;
        $idGimnasio = $esSuperAdmin ? null : Gimnasios::gimnasioActualId();
        $plan = \App\Models\Planes::when($idGimnasio, function ($query) use ($idGimnasio) {
            $query->where('id_gimnasio', $idGimnasio);
        })->where('slug', $slug)->firstOrFail();
        $gimnasios = Gimnasios::where('estado', 1)->orderBy('nombre')->get();

        return view('planes.edit', compact('plan', 'gimnasios'));
    }

    public function update(Request $request, $slug)
    {
        $this->abortUnlessAdmin();
        $esSuperAdmin = (int) Auth::user()->id_tipo_usuario === 10;
        $idGimnasio = $esSuperAdmin ? null : Gimnasios::gimnasioActualId();
        $plan = \App\Models\Planes::when($idGimnasio, function ($query) use ($idGimnasio) {
            $query->where('id_gimnasio', $idGimnasio);
        })->where('slug', $slug)->firstOrFail();

        $validatedData = $request->validate([
            'nombre' => [
                'required',
                'string',
                'max:255',
                Rule::unique('planes', 'nombre')
                    ->ignore($plan->id)
                    ->where(function ($query) use ($idGimnasio) {
                        if ($idGimnasio) {
                            $query->where('id_gimnasio', $idGimnasio);
                        }

                        return $query;
                    }),
            ],
            'descripcion' => 'nullable|string|max:500',
            'valor' => 'required|integer|min:0',
            'porcentaje' => 'nullable|integer|min:0|max:100',
            'estado' => 'required|integer',
            'id_gimnasio' => 'nullable|exists:gimnasios,id',
        ]);

        $validatedData['slug'] = Str::slug($validatedData['nombre']);
        $validatedData['id_gimnasio'] = $esSuperAdmin
            ? (int) ($validatedData['id_gimnasio'] ?? $plan->id_gimnasio)
            : ($plan->id_gimnasio ?: Gimnasios::gimnasioActualId());

        $plan->update($validatedData);

        return redirect()->route('planes.index')->with('success', 'Plan actualizado exitosamente.');
    }

    public function destroy($slug)
    {
        $this->abortUnlessAdmin();
        $esSuperAdmin = (int) Auth::user()->id_tipo_usuario === 10;
        $idGimnasio = $esSuperAdmin ? null : Gimnasios::gimnasioActualId();
        $plan = \App\Models\Planes::when($idGimnasio, function ($query) use ($idGimnasio) {
            $query->where('id_gimnasio', $idGimnasio);
        })->where('slug', $slug)->firstOrFail();
        $plan->delete();

        return redirect()->route('planes.index')->with('success', 'Plan eliminado exitosamente.');
    }

    public function show($id)
    {
        $this->abortUnlessAdmin();
        $plan = \App\Models\Planes::findOrFail($id);

        return view('planes.show', compact('plan'));
    }

    public function toggleStatus($slug)
    {
        $this->abortUnlessAdmin();
        $esSuperAdmin = (int) Auth::user()->id_tipo_usuario === 10;
        $idGimnasio = $esSuperAdmin ? null : Gimnasios::gimnasioActualId();
        $plan = \App\Models\Planes::when($idGimnasio, function ($query) use ($idGimnasio) {
            $query->where('id_gimnasio', $idGimnasio);
        })->where('slug', $slug)->firstOrFail();
        DB::enableQueryLog();
        $plan->estado = $plan->estado == 0 ? 1 : 0;
        $plan->save();

        return redirect()->route('planes.index')->with('success', 'Estado del plan actualizado.');
    }
}
