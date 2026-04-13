<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CuentasCorrientes extends Model
{
    protected $fillable = [
        'id_cliente',
        'fecha_vencimiento',
        'monto',
        'fecha_pago',
        'monto_pagado',
        'monto_pagar',
        'descuento',
        'saldo',
        'id_forma_pago',
        'id_estado_pago',
        'comprobante',
        'id_tipo_cuota',
    ];

    public function cliente()
    {
        return $this->belongsTo(Clientes::class, 'id_cliente');
    }
    public function formaPago()
    {
        return $this->belongsTo(FormasPagos::class, 'id_forma_pago');
    }
    public function estadoPago()
    {
        return $this->belongsTo(EstadosPagos::class, 'id_estado_pago');
    }
    public function tipoCuota()
    {
        return $this->belongsTo(\App\Models\TiposCuotas::class, 'id_tipo_cuota');
    }
}
