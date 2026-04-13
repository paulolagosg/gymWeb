<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EstadosPagos extends Model
{
    protected $table = 'estados_pagos';

    protected $fillable = [
        'nombre',
        'slug',
        'color',
        'icono',
    ];

    public function pagos()
    {
        return $this->hasMany(CuentasCorrientes::class, 'estado_pago_id');
    }
}
