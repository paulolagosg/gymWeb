<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
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
        return response()->json([
            'user' => $this->formatUser($request->user()),
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

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    private function formatUser(\App\Models\User $user): array
    {
        // Mapear id_tipo_usuario al rol esperado por la app móvil
        $roleMap = [
            1 => 'admin',
            2 => 'entrenador',
            3 => 'cliente',
            4 => 'cliente',
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
            'titulo'          => $user->titulo,
        ];
    }
}
