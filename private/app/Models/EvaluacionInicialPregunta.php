<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EvaluacionInicialPregunta extends Model
{
    use HasFactory;

    protected $table = 'evaluacion_inicial_preguntas';

    protected $fillable = [
        'seccion_id',
        'codigo',
        'pregunta',
        'descripcion',
        'tipo',
        'es_requerida',
        'permite_otro',
        'orden',
        'estado',
    ];

    protected $casts = [
        'es_requerida' => 'boolean',
        'permite_otro' => 'boolean',
        'estado' => 'boolean',
    ];

    public function seccion()
    {
        return $this->belongsTo(EvaluacionInicialSeccion::class, 'seccion_id');
    }

    public function opciones()
    {
        return $this->hasMany(EvaluacionInicialOpcion::class, 'pregunta_id');
    }

    public function respuestas()
    {
        return $this->hasMany(EvaluacionInicialRespuesta::class, 'pregunta_id');
    }
}
