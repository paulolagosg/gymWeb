<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlanAlimentacionComida extends Model
{
    protected $table = 'planes_alimentacion_comidas';

    protected $fillable = [
        'id_plan_alimentacion_dia',
        'codigo_comida',
        'nombre_comida',
        'orden',
        'items',
        'reemplazos',
        'observaciones',
    ];

    protected $casts = [
        'orden' => 'integer',
        'items' => 'array',
    ];

    public function dia()
    {
        return $this->belongsTo(PlanAlimentacionDia::class, 'id_plan_alimentacion_dia');
    }
}
