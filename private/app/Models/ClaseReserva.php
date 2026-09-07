<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClaseReserva extends Model
{
    protected $table = 'clase_reservas';

    public const ESTADO_RESERVADA = 1;
    public const ESTADO_CANCELADA = 2;
    public const ESTADO_ASISTIO = 3;
    public const ESTADO_NO_SHOW = 4;

    protected $fillable = [
        'id_clase',
        'id_cliente',
        'id_gimnasio',
        'estado',
        'creado_por',
    ];

    protected $casts = [
        'estado' => 'integer',
    ];

    public function clase()
    {
        return $this->belongsTo(Clases::class, 'id_clase');
    }

    public function cliente()
    {
        return $this->belongsTo(Clientes::class, 'id_cliente');
    }

    public function creadoPor()
    {
        return $this->belongsTo(User::class, 'creado_por');
    }
}
