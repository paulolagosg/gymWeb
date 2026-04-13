<?php

namespace App\Http\Controllers;

use App\Mail\BienvenidaClienteMail;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class UsuariosController extends Controller
{
    public function index()
    {

        if (auth()->user()->id_tipo_usuario == 1) {
            $usuarios = User::with('tipoUsuario')->get();
            return view('usuarios.index', compact('usuarios'));
        } else {

            abort(403);
        }
    }
    public function create()
    {
        $tipos_usuarios = \App\Models\TiposUsuarios::where('estado', 1)->get(); // Obtener tipos de usuarios activos

        if (auth()->user()->id_tipo_usuario != 1) {
            abort(403);
        } else {
            return view('usuarios.create', compact('tipos_usuarios'));
        }
    }
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'porcentaje' => 'nullable|integer|min:0', // Porcentaje opcional, entre 0 y 100
            'individual' => 'nullable|integer|min:0',
            'duo' => 'nullable|integer|min:0',
            'titulo' => 'nullable|string',
        ]);

        $pwd = $validatedData['password'];
        $validatedData['slug'] = hash('sha256', $validatedData['email'] . time());
        $validatedData['password'] = bcrypt($validatedData['password']);
        $validatedData['id_clasificacion'] = 1;


        $cliente = [];
        $cliente['nombres'] = $validatedData['name'];
        $cliente['email'] = $validatedData['email'];
        $cliente = (object) $cliente;
        try {
            DB::beginTransaction();
            User::create($validatedData);

            Mail::to($validatedData['email'])->send(new BienvenidaClienteMail($cliente, $pwd));
            DB::commit();
            return redirect()->route('usuarios.index')->with('success', 'Usuario creado exitosamente.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->withInput()->with('errors', 'Error al crear el usuario: ' . $e->getMessage());
        }
    }
    public function edit($id)
    {
        $tipos_usuarios = \App\Models\TiposUsuarios::where('estado', 1)->get(); // Obtener tipos de usuarios activos

        if (auth()->user()->id_tipo_usuario != 1) {
            abort(403);
        } else {
            $usuario = User::findOrFail($id);
            return view('usuarios.edit', compact('usuario', 'tipos_usuarios'));
        }
    }
    public function update(Request $request, $id)
    {
        $usuario = User::findOrFail($id);

        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $usuario->id,
            'password' => 'nullable|string|min:8|confirmed',
            'porcentaje' => 'nullable|integer|min:0',
            'individual' => 'nullable|integer|min:0',
            'duo' => 'nullable|integer|min:0',
            'titulo' => 'nullable|string',
        ]);

        if ($request->filled('password')) {
            $validatedData['password'] = bcrypt($validatedData['password']);
        } else {
            unset($validatedData['password']);
        }

        $usuario->update($validatedData);

        return redirect()->route('usuarios.index')->with('success', 'Usuario actualizado exitosamente.');
    }
    public function destroy($id)
    {
        $usuario = User::findOrFail($id);
        $usuario->delete();

        return redirect()->route('usuarios.index')->with('success', 'Usuario eliminado exitosamente.');
    }
    public function toggleStatus($id)
    {
        $usuario = User::findOrFail($id);
        $usuario->estado = !$usuario->estado; // Cambiar el estado
        $usuario->save();

        return redirect()->route('usuarios.index')->with('success', 'Estado del usuario actualizado exitosamente.');
    }
}
