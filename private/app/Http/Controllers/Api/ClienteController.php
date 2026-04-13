<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
        $cliente = DB::table('clientes')->where('id_usuario', $user->id)->first();

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

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    private function getCliente(object $user): ?object
    {
        $cliente = DB::table('clientes')->where('id_usuario', $user->id)->first();

        if (! $cliente && $user->id_cliente) {
            $cliente = DB::table('clientes')->where('id', $user->id_cliente)->first();
        }

        return $cliente;
    }
}
