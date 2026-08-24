<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class ClienteController extends Controller
{
    /**
     * Portada personal del cliente autenticado.
     * Retorna el perfil, estado de cuenta, último peso/IMC y próximas sesiones.
     */
    public function portada(Request $request): JsonResponse
    {
        $user = $request->user();

        // Buscar el registro de cliente asociado al usuario
        $cliente = DB::table('clientes')->where('clientes.id_usuario', $user->id)->first();

        if (! $cliente) {
            // Intentar por id_cliente guardado en el user (por si el FK está en users)
            if ($user->id_cliente) {
                $cliente = DB::table('clientes')->where('id', $user->id_cliente)->first();
            }
        }

        if (! $cliente) {
            return response()->json(['message' => 'Perfil de cliente no encontrado.'], 404);
        }

        $plan = DB::table('planes')->where('id', $cliente->id_plan)->first();

        $ultimoPeso = DB::table('pesos')
            ->where('id_cliente', $cliente->id)
            ->orderBy('created_at', 'desc')
            ->first();

        $ultimoImc = DB::table('imcs')
            ->where('id_cliente', $cliente->id)
            ->orderBy('created_at', 'desc')
            ->first();

        $ultimaAgua = DB::table('aguas')
            ->where('id_cliente', $cliente->id)
            ->orderBy('created_at', 'desc')
            ->first();

        $moroso = DB::table('cuentas_corrientes')
            ->where('id_cliente', $cliente->id)
            ->whereNull('fecha_pago')
            ->where('fecha_vencimiento', '<', now())
            ->exists();

        $proximaSesion = DB::table('agendas')
            ->select('id', 'titulo', 'fecha_inicio', 'estado')
            ->where('id_cliente', $cliente->id)
            ->where('fecha_inicio', '>=', now())
            ->orderBy('fecha_inicio')
            ->first();

        $totalCuotasPendientes = DB::table('cuentas_corrientes')
            ->where('id_cliente', $cliente->id)
            ->whereNull('fecha_pago')
            ->count();

        $fotoPath = $this->getClientePhotoPath($cliente);
        $fotoUrl = $this->buildPublicStorageUrl($fotoPath);

        return response()->json([
            'cliente' => [
                'id'             => $cliente->id,
                'nombres'        => $cliente->nombres,
                'paterno'        => $cliente->paterno,
                'materno'        => $cliente->materno ?? null,
                'email'          => $cliente->email,
                'telefono'       => $cliente->telefono,
                'direccion'      => $cliente->direccion ?? null,
                'slug'           => $cliente->slug,
                'estado'         => (int) $cliente->estado,
                'fecha_ingreso'  => $cliente->fecha_ingreso,
                'fecha_fin'      => $cliente->fecha_fin,
                'plan'           => $plan ? $plan->nombre : null,
                'plan_valor'     => $plan ? (float) $plan->valor : null,
                'altura'         => $cliente->altura ? (float) $cliente->altura : null,
                'foto_path'      => $fotoPath,
                'foto_url'       => $fotoUrl,
                'avatar_url'     => $fotoUrl,
                'image_url'      => $fotoUrl,
            ],
            'ultimo_peso'             => $ultimoPeso ? (float) $ultimoPeso->peso : null,
            'ultimo_imc'              => $ultimoImc ? (float) $ultimoImc->imc : null,
            'ultimo_consumo_agua'     => $ultimaAgua ? (float) $ultimaAgua->valor : null,
            'moroso'                  => $moroso,
            'cuotas_pendientes'       => $totalCuotasPendientes,
            'proxima_sesion'          => $proximaSesion,
        ]);
    }

    /**
     * Historial de cuotas del cliente.
     */
    public function cuotas(Request $request): JsonResponse
    {
        $user = $request->user();
        $cliente = $this->getCliente($user);

        if (! $cliente) {
            return response()->json(['message' => 'Perfil de cliente no encontrado.'], 404);
        }

        $cuotas = DB::table('cuentas_corrientes')
            ->leftJoin('formas_pagos', 'cuentas_corrientes.id_forma_pago', '=', 'formas_pagos.id')
            ->select(
                'cuentas_corrientes.id',
                'cuentas_corrientes.monto_pagar',
                'cuentas_corrientes.monto_pagado',
                'cuentas_corrientes.saldo',
                'cuentas_corrientes.fecha_vencimiento',
                'cuentas_corrientes.fecha_pago',
                'cuentas_corrientes.fecha_ultimo_abono',
                'cuentas_corrientes.id_estado_pago',
                'cuentas_corrientes.id_tipo_cuota',
                'cuentas_corrientes.id_forma_pago',
                'cuentas_corrientes.comprobante',
                'formas_pagos.nombre as forma_pago'
            )
            ->where('id_cliente', $cliente->id)
            ->orderBy('fecha_vencimiento', 'desc')
            ->get()
            ->map(fn($cuota) => [
                'id' => (int) $cuota->id,
                'monto_pagar' => (float) $cuota->monto_pagar,
                'monto_pagado' => $cuota->monto_pagado !== null ? (float) $cuota->monto_pagado : null,
                'saldo' => $cuota->saldo !== null ? (float) $cuota->saldo : null,
                'fecha_vencimiento' => $cuota->fecha_vencimiento,
                'fecha_pago' => $cuota->fecha_pago,
                'fecha_ultimo_abono' => $cuota->fecha_ultimo_abono,
                'id_estado_pago' => $cuota->id_estado_pago !== null ? (int) $cuota->id_estado_pago : null,
                'id_tipo_cuota' => $cuota->id_tipo_cuota !== null ? (int) $cuota->id_tipo_cuota : null,
                'id_forma_pago' => $cuota->id_forma_pago !== null ? (int) $cuota->id_forma_pago : null,
                'forma_pago' => $cuota->forma_pago,
                'comprobante' => $cuota->comprobante,
                'comprobante_url' => $cuota->comprobante ? url('/storage/' . ltrim($cuota->comprobante, '/')) : null,
            ]);

        return response()->json(['cuotas' => $cuotas]);
    }

    /**
     * Historial de peso del cliente.
     */
    public function pesos(Request $request): JsonResponse
    {
        $user = $request->user();
        $cliente = $this->getCliente($user);

        if (! $cliente) {
            return response()->json(['message' => 'Perfil de cliente no encontrado.'], 404);
        }

        $pesos = DB::table('pesos')
            ->select('id', 'peso', 'created_at')
            ->where('id_cliente', $cliente->id)
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(fn($p) => [
                'id'    => $p->id,
                'peso'  => (float) $p->peso,
                'fecha' => $p->created_at,
            ]);

        return response()->json(['pesos' => $pesos]);
    }

    /**
     * Próximas sesiones del cliente.
     */
    public function agenda(Request $request): JsonResponse
    {
        $user = $request->user();
        $cliente = $this->getCliente($user);

        if (! $cliente) {
            return response()->json(['message' => 'Perfil de cliente no encontrado.'], 404);
        }

        $sesiones = DB::table('agendas')
            ->select('id', 'titulo', 'descripcion', 'fecha_inicio', 'fecha_fin', 'estado')
            ->where('id_cliente', $cliente->id)
            ->orderBy('fecha_inicio', 'asc')
            ->limit(20)
            ->get();

        return response()->json(['agenda' => $sesiones]);
    }

    /**
     * Puntos y racha de constancia del cliente.
     */
    public function gamificacion(Request $request): JsonResponse
    {
        $user = $request->user();
        $cliente = $this->getCliente($user);

        if (! $cliente) {
            return response()->json(['message' => 'Perfil de cliente no encontrado.'], 404);
        }

        $puntos = DB::table('puntos_clientes')->where('id_cliente', $cliente->id)->first();

        return response()->json([
            'puntos_totales' => $puntos ? (int) $puntos->puntos_totales : 0,
            'racha_actual' => $puntos ? (int) $puntos->racha_actual : 0,
            'racha_maxima' => $puntos ? (int) $puntos->racha_maxima : 0,
            'ultima_fecha_sesion' => $puntos->ultima_fecha_sesion ?? null,
        ]);
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    private function getCliente(object $user): ?object
    {
        $userId = isset($user->id) ? (int) $user->id : 0;
        $cliente = null;

        if ($userId > 0) {
            $cliente = DB::table('clientes')->where('clientes.id_usuario', $userId)->first();
        }

        $clienteIdFromToken = (int) ($user->id_cliente ?? $user->idCliente ?? $user->cliente_id ?? 0);
        if (! $cliente && $clienteIdFromToken > 0) {
            $cliente = DB::table('clientes')->where('clientes.id', $clienteIdFromToken)->first();
        }

        if (! $cliente && $userId > 0) {
            $clienteIdFromUser = (int) DB::table('users')->where('id', $userId)->value('id_cliente');
            if ($clienteIdFromUser > 0) {
                $cliente = DB::table('clientes')->where('clientes.id', $clienteIdFromUser)->first();
            }
        }

        $email = isset($user->email) ? trim((string) $user->email) : '';
        if (! $cliente && $email !== '') {
            $cliente = DB::table('clientes')->where('clientes.email', $email)->first();
        }

        return $cliente;
    }

    private function getClientePhotoPath(object $cliente): ?string
    {
        if (! Schema::hasColumn('clientes', 'foto_path')) {
            return null;
        }

        return property_exists($cliente, 'foto_path') && is_string($cliente->foto_path) && $cliente->foto_path !== ''
            ? $cliente->foto_path
            : null;
    }

    private function buildPublicStorageUrl(?string $path): ?string
    {
        if (! is_string($path) || $path === '') {
            return null;
        }

        $relativeUrl = Storage::url($path);
        $request = request();

        if ($request) {
            return rtrim($request->getSchemeAndHttpHost(), '/') . $relativeUrl;
        }

        return url($relativeUrl);
    }
}
