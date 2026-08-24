<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PuntosClientes extends Model
{
    protected $table = 'puntos_clientes';

    protected $fillable = [
        'id_cliente',
        'puntos_totales',
        'racha_actual',
        'racha_maxima',
        'ultima_fecha_sesion',
    ];

    protected $casts = [
        'ultima_fecha_sesion' => 'date',
    ];

    public function cliente()
    {
        return $this->belongsTo(Clientes::class, 'id_cliente');
    }
}
