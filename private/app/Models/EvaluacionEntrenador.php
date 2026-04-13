<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EvaluacionEntrenador extends Model
{
    protected $fillable = [
        'id_entrenador',
        'evaluador_id',
        'empatia',
        'escucha_activa',
        'comunicacion',
        'anatomia',
        'fisiologia',
        'programacion',
        'poblacion',
        'psicologia',
    ];

    public function entrenador()
    {
        return $this->belongsTo(User::class, 'id_entrenador');
    }
}
