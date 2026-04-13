<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EncuestaSatisfaccion extends Model
{
    protected $fillable = [
        'id_cliente',
        'id_entrenador',
        'profesionalismo',
        'claridad',
        'motivacion',
        'disponibilidad',
        'puntualidad',
        'destacaria',
        'sugerencias',
        'valoracion_global',
        'slug'
    ];

    public function cliente()
    {
        return $this->belongsTo(User::class, 'cliente_id');
    }
    public function entrenador()
    {
        return $this->belongsTo(User::class, 'entrenador_id');
    }
}
