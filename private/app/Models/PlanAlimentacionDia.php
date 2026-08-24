<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlanAlimentacionDia extends Model
{
    protected $table = 'planes_alimentacion_dias';

    protected $fillable = [
        'id_plan_alimentacion',
        'dia_semana',
        'nombre_dia',
        'orden',
        'observaciones',
    ];

    protected $casts = [
        'dia_semana' => 'integer',
        'orden' => 'integer',
    ];

    public function plan()
    {
        return $this->belongsTo(PlanAlimentacion::class, 'id_plan_alimentacion');
    }

    public function comidas()
    {
        return $this->hasMany(PlanAlimentacionComida::class, 'id_plan_alimentacion_dia')
            ->orderBy('orden')
            ->orderBy('id');
    }
}
