<?php

namespace App\Http\Controllers;

use App\Mail\BienvenidaClienteMail;
use App\Models\Clientes;
use App\Models\Gimnasios;
use App\Models\Planes;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;

class UsuariosController extends Controller
{
    private function abortUnlessAdmin(): void
    {
        if (!in_array((int) Auth::user()->id_tipo_usuario, [1, 10], true)) {
            abort(403, 'No tiene acceso');
        }
    }

    public function index(Request $request)
    {
        $this->abortUnlessAdmin();
        $authUser = Auth::user();
        $esSuperAdmin = (int) $authUser->id_tipo_usuario === 10;
        $idGimnasio = $esSuperAdmin
            ? ($request->filled('id_gimnasio') ? (int) $request->input('id_gimnasio') : null)
            : Gimnasios::gimnasioActualId();
        $gimnasios = Gimnasios::where('estado', 1)->orderBy('nombre')->get();
        $gimnasioSeleccionado = $idGimnasio;

        $usuarios = User::with(['tipoUsuario', 'gimnasio'])
            ->when($idGimnasio, function ($query) use ($idGimnasio) {
                $query->where('id_gimnasio', $idGimnasio);
            })
            ->orderBy('name')
            ->get();

        return view('usuarios.index', compact('usuarios', 'gimnasios', 'gimnasioSeleccionado'));
    }
    public function create()
    {
        $tipos_usuarios = \App\Models\TiposUsuarios::where('estado', 1)
            ->where('id', '!=', 10)
            ->orderBy('nombre')
            ->get();
        $gimnasios = Gimnasios::where('estado', 1)->orderBy('nombre')->get();

        $this->abortUnlessAdmin();

        return view('usuarios.create', compact('tipos_usuarios', 'gimnasios'));
    }
    public function store(Request $request)
    {
        $this->abortUnlessAdmin();

        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'id_tipo_usuario' => 'required|integer|exists:tipos_usuarios,id|not_in:10',
            'individual' => 'nullable|integer|min:0',
            'duo' => 'nullable|integer|min:0',
            'titulo' => 'nullable|string|max:255',
            'id_gimnasio' => 'required|exists:gimnasios,id',
        ]);

        $pwd = $validatedData['password'];
        $validatedData['slug'] = hash('sha256', $validatedData['email'] . time());
        $validatedData['password'] = bcrypt($validatedData['password']);
        $validatedData['id_clasificacion'] = 1;
        $validatedData['id_gimnasio'] = (int) $validatedData['id_gimnasio'];

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
        $this->abortUnlessAdmin();
        $tipos_usuarios = \App\Models\TiposUsuarios::where('estado', 1)
            ->where('id', '!=', 10)
            ->orderBy('nombre')
            ->get();
        $gimnasios = Gimnasios::where('estado', 1)->orderBy('nombre')->get();
        $idGimnasio = Gimnasios::gimnasioActualId();

        $usuario = User::when($idGimnasio, function ($query) use ($idGimnasio) {
            $query->where('id_gimnasio', $idGimnasio);
        })->findOrFail($id);

        return view('usuarios.edit', compact('usuario', 'tipos_usuarios', 'gimnasios'));
    }

    public function update(Request $request, $id)
    {
        $this->abortUnlessAdmin();
        $authUser = Auth::user();
        $idGimnasio = Gimnasios::gimnasioActualId();

        $usuario = User::when($idGimnasio, function ($query) use ($idGimnasio) {
            $query->where('id_gimnasio', $idGimnasio);
        })->findOrFail($id);

        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $usuario->id,
            'password' => 'nullable|string|min:8|confirmed',
            'id_tipo_usuario' => 'required|integer|exists:tipos_usuarios,id|not_in:10',
            'individual' => 'nullable|integer|min:0',
            'duo' => 'nullable|integer|min:0',
            'titulo' => 'nullable|string|max:255',
            'id_gimnasio' => 'nullable|exists:gimnasios,id',
        ]);

        if ($request->filled('password')) {
            $validatedData['password'] = bcrypt($validatedData['password']);
        } else {
            unset($validatedData['password']);
        }

        $validatedData['id_gimnasio'] = (int) ($validatedData['id_gimnasio'] ?? $usuario->id_gimnasio ?: Gimnasios::gimnasioActualId());

        $usuario->update($validatedData);

        if ((int) $authUser->id === (int) $usuario->id && $validatedData['id_gimnasio']) {
            session(['id_gimnasio_actual' => $validatedData['id_gimnasio']]);
        }

        return redirect()->route('usuarios.index')->with('success', 'Usuario actualizado exitosamente.');
    }

    public function destroy($id)
    {
        $this->abortUnlessAdmin();
        $authUser = Auth::user();
        $idGimnasio = (int) $authUser->id_tipo_usuario === 10 ? null : Gimnasios::gimnasioActualId();

        $usuario = User::when($idGimnasio, function ($query) use ($idGimnasio) {
            $query->where('id_gimnasio', $idGimnasio);
        })->findOrFail($id);

        DB::transaction(function () use ($usuario) {
            if ($usuario->id_cliente) {
                $this->deleteClienteRelacionado((int) $usuario->id_cliente);
            }

            if ((int) $usuario->id_tipo_usuario === 2) {
                if (Schema::hasTable('entrenadores_cursos')) {
                    DB::table('entrenadores_cursos')->where('id_entrenador', $usuario->id)->delete();
                }

                if (Schema::hasTable('tareas')) {
                    DB::table('tareas')->where('id_usuario', $usuario->id)->delete();
                }

                if (Schema::hasTable('mensajes')) {
                    DB::table('mensajes')->where('de_id', $usuario->id)->orWhere('para_id', $usuario->id)->delete();
                }

                if (Schema::hasTable('agendas')) {
                    $agendaIds = DB::table('agendas')->where('id_usuario', $usuario->id)->pluck('id');
                    if ($agendaIds->isNotEmpty() && Schema::hasTable('agendas_ejercicios')) {
                        DB::table('agendas_ejercicios')->whereIn('id_agenda', $agendaIds)->delete();
                    }
                    DB::table('agendas')->where('id_usuario', $usuario->id)->delete();
                }

                if (Schema::hasTable('pagos_entrenadores')) {
                    DB::table('pagos_entrenadores')->where('entrenador_id', $usuario->id)->delete();
                }

                if (Schema::hasTable('historial_tarifa_entrenadors')) {
                    DB::table('historial_tarifa_entrenadors')->where('entrenador_id', $usuario->id)->delete();
                }
            }

            User::where('id', $usuario->id)->delete();
        });

        return redirect()->route('usuarios.index')->with('success', 'Usuario eliminado exitosamente.');
    }

    private function deleteClienteRelacionado(int $clienteId): void
    {
        $cliente = Clientes::find($clienteId);

        if (! $cliente) {
            return;
        }

        $agendaIds = DB::table('agendas')->where('id_cliente', $cliente->id)->pluck('id');

        if ($agendaIds->isNotEmpty() && Schema::hasTable('agendas_ejercicios')) {
            DB::table('agendas_ejercicios')->whereIn('id_agenda', $agendaIds)->delete();
        }

        if (Schema::hasTable('tareas')) {
            DB::table('tareas')->where('id_cliente', $cliente->id)->delete();
        }

        if (Schema::hasTable('parq_respuestas')) {
            DB::table('parq_respuestas')->where('id_cliente', $cliente->id)->delete();
        }

        if (Schema::hasTable('cuestionarios_historicos')) {
            DB::table('cuestionarios_historicos')->where('id_cliente', $cliente->id)->delete();
        }

        $cliente->cuotas()->delete();
        $cliente->pesos()->delete();
        $cliente->imcs()->delete();
        $cliente->aguas()->delete();
        $cliente->grasas()->delete();
        $cliente->pmusculares()->delete();
        $cliente->poseas()->delete();
        $cliente->perimetros()->delete();
        $cliente->cuestionarios()->delete();
        $cliente->cuestionariosHistoricos()->delete();
        $cliente->evaluacionInicial()->delete();

        DB::table('agendas')->where('id_cliente', $cliente->id)->delete();
        User::where('id_cliente', $cliente->id)->delete();
        $cliente->delete();
    }

    public function toggleStatus($id)
    {
        $this->abortUnlessAdmin();
        $usuario = User::findOrFail($id);
        $usuario->estado = !$usuario->estado;
        $usuario->save();

        return redirect()->route('usuarios.index')->with('success', 'Estado del usuario actualizado exitosamente.');
    }
}
