<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EvaluacionInicialSeccion extends Model
{
    use HasFactory;

    protected $table = 'evaluacion_inicial_secciones';

    protected $fillable = [
        'codigo',
        'titulo',
        'descripcion',
        'orden',
        'estado',
    ];

    public function preguntas()
    {
        return $this->hasMany(EvaluacionInicialPregunta::class, 'seccion_id');
    }
}
