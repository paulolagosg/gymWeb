<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlanAlimentacionRegistro extends Model
{
    protected $table = 'planes_alimentacion_registros';

    protected $fillable = [
        'id_plan_alimentacion',
        'fecha',
        'dia_semana',
        'codigo_comida',
        'cumplido',
        'comentario',
    ];

    protected $casts = [
        'fecha' => 'date',
        'dia_semana' => 'integer',
        'cumplido' => 'boolean',
    ];

    public function plan()
    {
        return $this->belongsTo(PlanAlimentacion::class, 'id_plan_alimentacion');
    }
}
