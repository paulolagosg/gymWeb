<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OpenGymRoutine extends Model
{
    protected $table = 'open_gym_rutinas';

    protected $fillable = [
        'id_user',
        'id_cliente',
        'id_gimnasio',
        'nombre',
        'descripcion',
        'frecuencia_semanal',
        'duracion_estimada_minutos',
        'calorias_estimadas',
        'id_rutina_origen',
        'activo',
    ];

    protected $casts = [
        'frecuencia_semanal' => 'integer',
        'duracion_estimada_minutos' => 'integer',
        'calorias_estimadas' => 'integer',
        'activo' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'id_user');
    }

    public function cliente()
    {
        return $this->belongsTo(Clientes::class, 'id_cliente');
    }

    public function gimnasio()
    {
        return $this->belongsTo(Gimnasios::class, 'id_gimnasio');
    }

    public function originRoutine()
    {
        return $this->belongsTo(self::class, 'id_rutina_origen');
    }

    public function exercises()
    {
        return $this->hasMany(OpenGymRoutineExercise::class, 'id_rutina')->orderBy('orden');
    }

    public function workouts()
    {
        return $this->hasMany(OpenGymWorkout::class, 'id_rutina');
    }
}
