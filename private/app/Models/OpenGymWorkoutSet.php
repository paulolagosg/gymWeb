<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OpenGymWorkoutSet extends Model
{
    protected $table = 'open_gym_entrenamiento_series';

    protected $fillable = [
        'id_entrenamiento',
        'id_rutina_ejercicio',
        'id_ejercicio',
        'id_grupo_muscular',
        'orden_ejercicio',
        'nombre_ejercicio',
        'numero_serie',
        'reps_objetivo',
        'reps_realizadas',
        'peso_objetivo',
        'peso_real',
        'descanso_segundos',
        'completado_en',
        'es_record_personal',
    ];

    protected $casts = [
        'orden_ejercicio' => 'integer',
        'numero_serie' => 'integer',
        'reps_objetivo' => 'integer',
        'reps_realizadas' => 'integer',
        'peso_objetivo' => 'decimal:2',
        'peso_real' => 'decimal:2',
        'descanso_segundos' => 'integer',
        'completado_en' => 'datetime',
        'es_record_personal' => 'boolean',
    ];

    public function workout()
    {
        return $this->belongsTo(OpenGymWorkout::class, 'id_entrenamiento');
    }

    public function routineExercise()
    {
        return $this->belongsTo(OpenGymRoutineExercise::class, 'id_rutina_ejercicio');
    }

    public function ejercicio()
    {
        return $this->belongsTo(Ejercicios::class, 'id_ejercicio');
    }

    public function grupoMuscular()
    {
        return $this->belongsTo(GrupoMuscular::class, 'id_grupo_muscular');
    }
}
