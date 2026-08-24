<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OpenGymWorkout extends Model
{
    protected $table = 'open_gym_entrenamientos';

    protected $fillable = [
        'id_rutina',
        'id_user',
        'id_cliente',
        'id_gimnasio',
        'nombre_rutina',
        'estado',
        'fecha_inicio',
        'fecha_fin',
        'duracion_segundos',
        'calorias_estimadas',
        'dificultad_percibida',
        'resumen_prs',
        'resumen_grupos',
    ];

    protected $casts = [
        'fecha_inicio' => 'datetime',
        'fecha_fin' => 'datetime',
        'duracion_segundos' => 'integer',
        'calorias_estimadas' => 'integer',
        'resumen_prs' => 'array',
        'resumen_grupos' => 'array',
    ];

    public function routine()
    {
        return $this->belongsTo(OpenGymRoutine::class, 'id_rutina');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'id_user');
    }

    public function cliente()
    {
        return $this->belongsTo(Clientes::class, 'id_cliente');
    }

    public function sets()
    {
        return $this->hasMany(OpenGymWorkoutSet::class, 'id_entrenamiento')
            ->orderBy('orden_ejercicio')
            ->orderBy('numero_serie');
    }
}
