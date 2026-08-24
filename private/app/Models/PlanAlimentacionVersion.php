<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlanAlimentacionVersion extends Model
{
    protected $table = 'planes_alimentacion_versiones';

    protected $fillable = [
        'id_plan_alimentacion',
        'version',
        'descripcion_cambio',
        'snapshot',
        'id_usuario',
    ];

    protected $casts = [
        'version' => 'integer',
        'snapshot' => 'array',
    ];

    public function plan()
    {
        return $this->belongsTo(PlanAlimentacion::class, 'id_plan_alimentacion');
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'id_usuario');
    }
}
