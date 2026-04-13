<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AgendasEjercicios extends Model
{
    protected $table = 'agendas_ejercicios';

    protected $fillable = [
        'id_agenda',
        'id_ejercicio',
        'serie',
        'repeticiones',
        'rir',
        'rpe',
        'rm',
        'metodo',
        'progresion',
        'fundamento',
        'carga',
        'descanso'

    ];

    public function agenda()
    {
        return $this->belongsTo(Agendas::class, 'id_agenda');
    }

    public function ejercicio()
    {
        return $this->belongsTo(Ejercicios::class, 'id_ejercicio');
    }
}
