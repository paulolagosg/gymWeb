<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FormasPagos extends Model
{
    protected $table = 'formas_pagos';

    protected $fillable = [
        'nombre',
        'slug',
        'icono',
        'color',
        'estado', // 1: activo, 0: inactivo
    ];

    public function pagos()
    {
        return $this->hasMany(CuentasCorrientes::class, 'forma_pago_id');
    }
    public function cuotas()
    {
        return $this->hasMany(CuentasCorrientes::class, 'id_forma_pago');
    }
}
