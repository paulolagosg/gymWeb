<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\ResetPasswordAppMail;
use App\Models\Clientes;
use App\Models\EntrenadorPerfil;
use App\Models\User;
use App\Support\GymBranding;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Login mobile — devuelve un token Sanctum junto con los datos del usuario.
     */
    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email'    => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials)) {
            throw ValidationException::withMessages([
                'email' => ['Las credenciales proporcionadas son incorrectas.'],
            ]);
        }

        /** @var \App\Models\User $user */
        $user = Auth::user();

        // Bloquear acceso a clientes inactivos (estado = 0 en tabla clientes)
        if ($user->id_cliente && $user->cliente) {
            $estadoRaw = $user->cliente->getRawOriginal('estado');
            if ((int) $estadoRaw === 0) {
                Auth::logout();
                return response()->json(
                    ['message' => 'Tu cuenta está inactiva. Consulta con tu gimnasio.'],
                    403
                );
            }
        }

        if ($bloqueo = $this->gimnasioBloqueadoResponse($user)) {
            Auth::logout();
            return $bloqueo;
        }

        // Revocar tokens previos del mismo dispositivo si se reusa el nombre
        $deviceName = $request->input('device_name', 'mobile');
        $user->tokens()->where('name', $deviceName)->delete();

        $token = $user->createToken($deviceName)->plainTextToken;

        return response()->json([
            'token' => $token,
            'user'  => $this->formatUser($user),
        ]);
    }

    /**
     * Devuelve el usuario autenticado (ruta protegida).
     */
    public function me(Request $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        // Bloquear acceso a clientes inactivos (estado = 0 en tabla clientes)
        if ($user->id_cliente && $user->cliente) {
            $estadoRaw = $user->cliente->getRawOriginal('estado');
            if ((int) $estadoRaw === 0) {
                $user->currentAccessToken()->delete();
                return response()->json(['message' => 'Tu cuenta está inactiva.'], 403);
            }
        }

        if ($bloqueo = $this->gimnasioBloqueadoResponse($user)) {
            $user->currentAccessToken()->delete();
            return $bloqueo;
        }

        return response()->json([
            'user' => $this->formatUser($user),
        ]);
    }

    /**
     * Revoca el token actual (logout móvil).
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Sesión cerrada correctamente.']);
    }

    /**
     * Cambia la contraseña del usuario autenticado.
     */
    public function changePassword(Request $request): JsonResponse
    {
        $request->validate([
            'current_password'      => ['required', 'string'],
            'new_password'          => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        /** @var \App\Models\User $user */
        $user = $request->user();

        if (! Hash::check($request->current_password, $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['La contraseña actual es incorrecta.'],
            ]);
        }

        $user->update(['password' => Hash::make($request->new_password)]);

        return response()->json(['message' => 'Contraseña actualizada correctamente.']);
    }

    /**
     * Elimina la cuenta del usuario autenticado (requisito 5.1.1(v) de Apple:
     * self-service account deletion). No se borra físicamente el registro de
     * `clientes` porque varias tablas dependen de él sin CASCADE (pesos, imcs,
     * puntos_clientes) y el gimnasio necesita conservar su historial contable —
     * en vez de eso se anonimiza la información personal identificable y se
     * desactiva el acceso: correo/CI/nombre se reemplazan por un valor no
     * identificable, la clave se aleatoriza, se revocan todos los tokens y
     * (para clientes) se marca `estado = 0`, el mismo campo que ya bloquea el
     * login de clientes inactivos en este mismo controlador.
     */
    public function eliminarCuenta(Request $request): JsonResponse
    {
        $request->validate([
            'current_password' => ['required', 'string'],
        ]);

        /** @var \App\Models\User $user */
        $user = $request->user();

        if (! Hash::check($request->current_password, $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['La contraseña actual es incorrecta.'],
            ]);
        }

        DB::transaction(function () use ($user) {
            if ($user->id_cliente) {
                $cliente = Clientes::find($user->id_cliente);
                if ($cliente) {
                    if ($cliente->foto_path) {
                        Storage::disk('public')->delete($cliente->foto_path);
                    }
                    $cliente->update([
                        'nombres' => 'Cliente eliminado',
                        'paterno' => '',
                        'materno' => null,
                        'email' => "eliminado-{$cliente->id}@ampaya.cl",
                        'telefono' => '-',
                        'direccion' => null,
                        'ciudad' => null,
                        'ci' => 'ELIMINADO-' . $cliente->id,
                        'foto_path' => null,
                        'estado' => 0,
                    ]);
                }
            }

            $perfilEntrenador = EntrenadorPerfil::where('id_entrenador', $user->id)->first();
            if ($perfilEntrenador) {
                if ($perfilEntrenador->foto) {
                    Storage::disk('public')->delete($perfilEntrenador->foto);
                }
                $perfilEntrenador->update(['instagram' => null, 'foto' => null]);
            }

            $user->tokens()->delete();
            $user->update([
                'name' => 'Usuario eliminado',
                'email' => "eliminado-{$user->id}@ampaya.cl",
                'password' => Hash::make(Str::random(40)),
            ]);
        });

        return response()->json(['message' => 'Tu cuenta fue eliminada correctamente.']);
    }

    /**
     * Solicita el envío del enlace de recuperación de clave (app móvil).
     * Reutiliza el broker de tokens de Laravel (misma tabla password_reset_tokens
     * que usa el panel web), pero envía un correo con un enlace que abre la app
     * en vez del enlace a la vista web de Laravel.
     */
    public function forgotPassword(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'string', 'email'],
        ]);

        Password::sendResetLink(
            $request->only('email'),
            function (User $user, string $token) {
                // Se enlaza a una página https (no al esquema personalizado directo)
                // porque los clientes de correo (Gmail, etc.) eliminan los enlaces
                // con esquemas no estándar de los botones. Esa página intermedia
                // redirige al esquema de la app (ver routes/web.php).
                $resetUrl = route('app.reset-password.redirect', [
                    'token' => $token,
                    'email' => $user->email,
                ]);

                Mail::to($user->email)->send(new ResetPasswordAppMail($user, $resetUrl));
            }
        );

        // Respuesta genérica siempre, sin importar si el correo existe o no,
        // para no revelar qué cuentas están registradas.
        return response()->json([
            'message' => 'Si el correo está registrado, se enviará un enlace de recuperación.',
        ]);
    }

    /**
     * Restablece la clave a partir del token recibido por correo.
     */
    public function resetPassword(Request $request): JsonResponse
    {
        $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string', 'confirmed', Rules\Password::defaults()],
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user) use ($request) {
                $user->forceFill(['password' => Hash::make($request->password)])->save();
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages([
                'email' => [__($status)],
            ]);
        }

        return response()->json(['message' => 'Contraseña actualizada correctamente.']);
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    /**
     * Bloqueo por facturación de plataforma (trial vencido o suspensión manual) —
     * afecta a todo el gimnasio, todos los roles, salvo el super-admin (necesita poder
     * entrar para desbloquearlo). Ver App\Http\Middleware\EnsureGimnasioActivo, que
     * cubre el resto de la API una vez que ya hay sesión — este chequeo cubre login/me
     * porque un 403 genérico ahí borra la sesión local en la app (useRestoreSession),
     * así que el mensaje correcto tiene que aparecer aquí para que se vea.
     */
    private function gimnasioBloqueadoResponse(\App\Models\User $user): ?JsonResponse
    {
        if ((int) $user->id_tipo_usuario === 10) {
            return null;
        }

        $gimnasio = $user->gimnasio ?? $user->cliente?->gimnasio;
        if (! $gimnasio || ! $gimnasio->bloqueado) {
            return null;
        }

        return response()->json([
            'message' => $gimnasio->mensajeBloqueo((int) $user->id_tipo_usuario),
            'code' => 'gimnasio_bloqueado',
            'motivo' => $gimnasio->bloqueado_motivo,
        ], 403);
    }

    private function formatUser(\App\Models\User $user): array
    {
        $user->loadMissing('gimnasio', 'cliente.gimnasio', 'tipoUsuario');

        $gimnasio = $user->gimnasio ?? $user->cliente?->gimnasio;
        $gymBranding = GymBranding::resolve($gimnasio ?? $user);
        $isOpenGym = (int) $user->id_tipo_usuario === 5;

        // Mapear id_tipo_usuario al rol esperado por la app móvil
        $roleMap = [
            1 => 'admin',
            2 => 'entrenador',
            3 => 'cliente',
            4 => 'cliente',
            5 => 'cliente',
        ];

        return [
            'id'              => $user->id,
            'name'            => $user->name,
            'email'           => $user->email,
            'role'            => $roleMap[$user->id_tipo_usuario] ?? 'cliente',
            'id_tipo_usuario' => $user->id_tipo_usuario,
            'slug'            => $user->slug,
            'porcentaje'      => $user->porcentaje,
            'id_cliente'      => $user->id_cliente,
            'tipo_usuario'    => $user->tipoUsuario?->nombre,
            'is_open_gym'     => $isOpenGym,
            'titulo'          => $user->titulo,
            'id_gimnasio'     => $gimnasio?->id ?? $user->id_gimnasio,
            'gimnasio'        => $gimnasio?->nombre,
            'gym_branding'    => [
                'name' => $gymBranding['display_name'],
                'color_primario' => $gymBranding['primary_color'],
                'color_secundario' => $gymBranding['secondary_color'],
            ],
            'features'        => \App\Models\Gimnasios::featuresActivas($gimnasio?->id),
        ];
    }
}
