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
        'es_sensible',
        'depende_pregunta_id',
        'depende_opcion_id',
        'orden',
        'estado',
    ];

    protected $casts = [
        'es_requerida' => 'boolean',
        'permite_otro' => 'boolean',
        'es_sensible' => 'boolean',
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

    public function dependePregunta()
    {
        return $this->belongsTo(EvaluacionInicialPregunta::class, 'depende_pregunta_id');
    }

    public function dependeOpcion()
    {
        return $this->belongsTo(EvaluacionInicialOpcion::class, 'depende_opcion_id');
    }
}
