<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TiposCuotas extends Model
{
    protected $table = 'tipos_cuotas';

    protected $fillable = [
        'nombre',
        'slug',
        'estado', // 1: activo, 0: inactivo
    ];

    public function cuentasCorrientes()
    {
        return $this->hasMany(CuentasCorrientes::class, 'id_tipo_cuota');
    }
}
