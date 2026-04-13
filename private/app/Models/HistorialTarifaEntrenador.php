<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HistorialTarifaEntrenador extends Model
{
    protected $table = 'historial_tarifas_entrenadores';

    protected $fillable = [
        'entrenador_id',
        'year',
        'month',
        'individual',
        'duo'
    ];

    public function entrenador()
    {
        return $this->belongsTo(User::class, 'entrenador_id');
    }
}
