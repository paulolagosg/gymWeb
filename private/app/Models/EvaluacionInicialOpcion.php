<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EvaluacionInicialOpcion extends Model
{
    use HasFactory;

    protected $table = 'evaluacion_inicial_opciones';

    protected $fillable = [
        'pregunta_id',
        'codigo',
        'etiqueta',
        'orden',
        'es_otro',
        'estado',
    ];

    protected $casts = [
        'es_otro' => 'boolean',
        'estado' => 'boolean',
    ];

    public function pregunta()
    {
        return $this->belongsTo(EvaluacionInicialPregunta::class, 'pregunta_id');
    }
}
