<?php
// app/Http/Controllers/MensajeController.php
namespace App\Http\Controllers;

use App\Models\Gimnasios;
use App\Models\Mensaje;
use App\Models\MensajeArchivo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Mail\NuevoMensajeNotification;
use Illuminate\Support\Facades\Mail;

class MensajeController extends Controller
{
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

        if ($esAdminLike) {
            $mensajes = Mensaje::with(['remitente.gimnasio', 'destinatario', 'archivos'])
                ->when($idGimnasio, function ($query) use ($idGimnasio) {
                    $query->whereHas('remitente', function ($userQuery) use ($idGimnasio) {
                        $userQuery->where('id_gimnasio', $idGimnasio);
                    });
                })
                ->orderBy('created_at', 'desc')
                ->get();
        } else {
            $mensajes = Mensaje::with(['remitente.gimnasio', 'destinatario', 'archivos'])
                ->where('para_id', Auth::id())
                ->orderBy('created_at', 'desc')
                ->get();
        }
        return view('mensajes.index', compact('mensajes', 'gimnasios', 'gimnasioSeleccionado'));
    }

    public function create()
    {
        $usuario = Auth::user();
        $tipo_usuario = (int) $usuario->id_tipo_usuario;
        $idGimnasio = Gimnasios::gimnasioActualId();

        $baseQuery = \App\Models\User::query()
            ->where('id', '!=', Auth::id())
            ->when($idGimnasio, function ($query) use ($idGimnasio) {
                $query->where('id_gimnasio', $idGimnasio);
            });

        switch ($tipo_usuario) {
            case 1:
            case 10:
                $usuarios = (clone $baseQuery)->orderBy('name')->get();
                break;
            case 2:
                $usuarios = (clone $baseQuery)
                    ->whereIn('id_tipo_usuario', [2, 3, 4])
                    ->orderBy('name')
                    ->get();
                break;
            case 3:
            case 4:
                $usuarios = (clone $baseQuery)
                    ->whereIn('id_tipo_usuario', [1, 2, 10])
                    ->orderBy('name')
                    ->get();
                break;
            default:
                return redirect()->route('mensajes.index')->with('error', 'Tipo de usuario no permitido.');
        }

        if ($usuarios->isEmpty()) {
            return redirect()->route('mensajes.index')->with('error', 'No hay usuarios disponibles para enviar mensajes.');
        }

        return view('mensajes.create', compact('usuarios'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'para_id' => 'required|exists:users,id',
            'contenido' => 'required|string|max:1000',
            'archivos.*' => 'nullable|file|max:10240', // 10MB por archivo
        ]);

        $idGimnasio = Gimnasios::gimnasioActualId();
        $destinatarioQuery = \App\Models\User::where('id', $request->para_id);

        if ($idGimnasio) {
            $destinatarioQuery->where('id_gimnasio', $idGimnasio);
        }

        $destinatario = $destinatarioQuery->first();

        if (! $destinatario) {
            abort(403, 'No tiene acceso');
        }

        $mensaje = Mensaje::create([
            'de_id' => Auth::id(),
            'para_id' => $destinatario->id,
            'contenido' => $request->contenido,
        ]);

        if ($request->hasFile('archivos')) {
            foreach ($request->file('archivos') as $archivo) {
                $path = $archivo->store('mensajes', 'public');
                $mensaje->archivos()->create([
                    'archivo' => $path,
                    'tipo' => $archivo->getMimeType(),
                ]);
            }
        }
        $remitente = Auth::user();

        Mail::to($destinatario->email)->send(new NuevoMensajeNotification($remitente, $mensaje));

        return redirect()->route('mensajes.index')->with('success', 'Mensaje enviado');
    }
}
