<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OpenGymRoutineExercise extends Model
{
    protected $table = 'open_gym_rutina_ejercicios';

    protected $fillable = [
        'id_rutina',
        'id_ejercicio',
        'nombre_personalizado',
        'grupo_muscular',
        'notas',
        'orden',
        'series',
        'reps_objetivo',
        'descanso_segundos',
        'peso_objetivo',
    ];

    protected $casts = [
        'orden' => 'integer',
        'series' => 'integer',
        'reps_objetivo' => 'integer',
        'descanso_segundos' => 'integer',
        'peso_objetivo' => 'decimal:2',
    ];

    public function routine()
    {
        return $this->belongsTo(OpenGymRoutine::class, 'id_rutina');
    }

    public function ejercicio()
    {
        return $this->belongsTo(Ejercicios::class, 'id_ejercicio');
    }
}
