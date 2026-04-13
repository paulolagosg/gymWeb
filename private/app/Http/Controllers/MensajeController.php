<?php
// app/Http/Controllers/MensajeController.php
namespace App\Http\Controllers;

use App\Models\Mensaje;
use App\Models\MensajeArchivo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Mail\NuevoMensajeNotification;
use Illuminate\Support\Facades\Mail;

class MensajeController extends Controller
{
    public function index()
    {
        $mensajes = Mensaje::where('para_id', Auth::id())->orderBy('created_at', 'desc')->get();
        return view('mensajes.index', compact('mensajes'));
    }

    public function create()
    {
        $usuario = auth()->user();
        $tipo_usuario = $usuario->id_tipo_usuario;
        $usuarios = \App\Models\User::where('id', '!=', Auth::id())->get();
        if ($usuarios->isEmpty()) {
            return redirect()->route('mensajes.index')->with('error', 'No hay usuarios disponibles para enviar mensajes.');
        }
        switch ($tipo_usuario) {
            case 1: // Administrador
                $usuarios = \App\Models\User::where('id', '!=', Auth::id())->get();
                break;
            case 2: // Usuario entrenador
                $usuarios = \App\Models\User::whereIn('id_tipo_usuario', [2, 3, 4])->where('id', '!=', Auth::id())->get();
                break;
            case 3:
            case 4: // Usuario cliente
                $usuarios = \App\Models\User::whereIn('id_tipo_usuario', [2])->where('id', '!=', Auth::id())->get();
                break;
            default:
                return redirect()->route('mensajes.index')->with('error', 'Tipo de usuario no permitido.');
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

        $mensaje = Mensaje::create([
            'de_id' => Auth::id(),
            'para_id' => $request->para_id,
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
        $destinatario = \App\Models\User::find($request->para_id);
        $remitente = Auth::user();

        Mail::to($destinatario->email)->send(new NuevoMensajeNotification($remitente, $mensaje));

        return redirect()->route('mensajes.index')->with('success', 'Mensaje enviado');
    }
}
