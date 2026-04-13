<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cuestionarios extends Model
{
    protected $table = 'cuestionarios';

    protected $fillable = [
        'patologias',
        'horario',
        'encantan',
        'no_gustan',
        'intolerancias',
        'suplemento',
        'duracion_entreno',
        'hora_gimnasio',
        'trabajo',
        'hora_acostarse',
        'hora_levantarse',
        'objetivo',
        'datos_interes',
        'dia_cualquiera',
        'id_cliente',
    ];
    public function cliente()
    {
        return $this->belongsTo(\App\Models\Clientes::class, 'id_cliente');
    }
}
