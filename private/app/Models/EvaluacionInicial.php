<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EvaluacionInicial extends Model
{
    use HasFactory;

    protected $table = 'evaluaciones_iniciales';

    protected $fillable = [
        'id_cliente',
        'completada_en',
    ];

    protected $casts = [
        'completada_en' => 'datetime',
    ];

    public function cliente()
    {
        return $this->belongsTo(Clientes::class, 'id_cliente');
    }

    public function respuestas()
    {
        return $this->hasMany(EvaluacionInicialRespuesta::class, 'evaluacion_inicial_id');
    }
}
