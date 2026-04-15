<?php

namespace App\Http\Controllers;

use App\Models\Gimnasios;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class GimnasiosController extends Controller
{
    private function autorizarAdmin(): void
    {
        if (!Auth::check() || (int) Auth::user()->id_tipo_usuario !== 10) {
            abort(403, 'No tiene acceso');
        }
    }

    public function index()
    {
        $this->autorizarAdmin();

        $gimnasios = Gimnasios::orderByDesc('estado')
            ->orderBy('nombre')
            ->get();

        return view('gimnasios.index', compact('gimnasios'));
    }

    public function create()
    {
        $this->autorizarAdmin();

        return view('gimnasios.create');
    }

    public function store(Request $request)
    {
        $this->autorizarAdmin();

        $validatedData = $request->validate([
            'nombre' => 'required|string|max:255|unique:gimnasios,nombre',
            'direccion' => 'nullable|string|max:255',
            'descripcion' => 'nullable|string|max:2000',
            'telefono' => 'nullable|string|max:50',
            'correo_electronico' => 'nullable|email|max:255',
            'sitio_web' => 'nullable|url|max:255',
            'instagram' => 'nullable|string|max:255',
            'facebook' => 'nullable|string|max:255',
            'tiktok' => 'nullable|string|max:255',
            'estado' => 'required|in:0,1',
        ]);

        $validatedData['slug'] = Str::slug($request->input('slug', $validatedData['nombre']));

        Gimnasios::create($validatedData);

        return redirect()->route('gimnasios.index')->with('success', 'Gimnasio creado exitosamente.');
    }

    public function edit($slug)
    {
        $this->autorizarAdmin();

        $gimnasio = Gimnasios::where('slug', $slug)->firstOrFail();

        return view('gimnasios.edit', compact('gimnasio'));
    }

    public function update(Request $request, $slug)
    {
        $this->autorizarAdmin();

        $gimnasio = Gimnasios::where('slug', $slug)->firstOrFail();

        $validatedData = $request->validate([
            'nombre' => 'required|string|max:255|unique:gimnasios,nombre,' . $gimnasio->id,
            'direccion' => 'nullable|string|max:255',
            'descripcion' => 'nullable|string|max:2000',
            'telefono' => 'nullable|string|max:50',
            'correo_electronico' => 'nullable|email|max:255',
            'sitio_web' => 'nullable|url|max:255',
            'instagram' => 'nullable|string|max:255',
            'facebook' => 'nullable|string|max:255',
            'tiktok' => 'nullable|string|max:255',
            'estado' => 'required|in:0,1',
        ]);

        if ((int) Auth::user()->id_tipo_usuario === 2) {
            $validatedData['estado'] = $gimnasio->estado;
        }

        $validatedData['slug'] = Str::slug($request->input('slug', $validatedData['nombre']));

        $gimnasio->update($validatedData);

        return redirect()->route('gimnasios.index')->with('success', 'Gimnasio actualizado exitosamente.');
    }

    public function destroy($slug)
    {
        $this->autorizarAdmin();

        $gimnasio = Gimnasios::where('slug', $slug)->firstOrFail();

        $tieneRelaciones = $gimnasio->clientes()->exists()
            || $gimnasio->agendas()->exists()
            || $gimnasio->movimientosFinancieros()->exists()
            || $gimnasio->planes()->exists()
            || $gimnasio->users()->exists();

        if ($tieneRelaciones) {
            return redirect()->route('gimnasios.index')->with('error', 'No se puede eliminar el gimnasio porque tiene información asociada.');
        }

        $gimnasio->delete();

        return redirect()->route('gimnasios.index')->with('success', 'Gimnasio eliminado exitosamente.');
    }

    public function toggleStatus($slug)
    {
        $this->autorizarAdmin();

        $gimnasio = Gimnasios::where('slug', $slug)->firstOrFail();
        $gimnasio->estado = $gimnasio->estado == 1 ? 0 : 1;
        $gimnasio->save();

        return redirect()->route('gimnasios.index')->with('success', 'Estado del gimnasio actualizado exitosamente.');
    }
}
