<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EvaluacionInicialRespuesta extends Model
{
    use HasFactory;

    protected $table = 'evaluacion_inicial_respuestas';

    protected $fillable = [
        'evaluacion_inicial_id',
        'pregunta_id',
        'opcion_id',
        'valor_texto',
        'id_gimnasio',
    ];

    public function evaluacion()
    {
        return $this->belongsTo(EvaluacionInicial::class, 'evaluacion_inicial_id');
    }

    public function pregunta()
    {
        return $this->belongsTo(EvaluacionInicialPregunta::class, 'pregunta_id');
    }

    public function opcion()
    {
        return $this->belongsTo(EvaluacionInicialOpcion::class, 'opcion_id');
    }
}
