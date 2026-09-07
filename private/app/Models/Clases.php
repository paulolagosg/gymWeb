<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Clases extends Model
{
    protected $table = 'clases';

    public const ESTADO_ACTIVA = 1;
    public const ESTADO_CANCELADA = 2;

    protected $fillable = [
        'id_gimnasio',
        'id_usuario',
        'nombre',
        'descripcion',
        'fecha_inicio',
        'fecha_fin',
        'aforo',
        'estado',
        'id_clase_origen',
    ];

    protected $casts = [
        'fecha_inicio' => 'datetime',
        'fecha_fin' => 'datetime',
        'aforo' => 'integer',
        'estado' => 'integer',
    ];

    public function gimnasio()
    {
        return $this->belongsTo(Gimnasios::class, 'id_gimnasio');
    }

    public function instructor()
    {
        return $this->belongsTo(User::class, 'id_usuario');
    }

    public function reservas()
    {
        return $this->hasMany(ClaseReserva::class, 'id_clase');
    }

    public function reservasActivas()
    {
        return $this->hasMany(ClaseReserva::class, 'id_clase')->where('estado', ClaseReserva::ESTADO_RESERVADA);
    }
}
