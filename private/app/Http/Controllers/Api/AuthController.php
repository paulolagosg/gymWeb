<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\ResetPasswordAppMail;
use App\Models\User;
use App\Support\GymBranding;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
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
