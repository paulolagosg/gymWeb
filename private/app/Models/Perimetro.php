<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Perimetro extends Model
{
    protected $table = 'perimetros';

    protected $fillable = [
        'id_cliente',
        'cabeza',
        'brazo_relajado',
        'brazo_flexionado_tension',
        'antebrazo',
        'torax_mesoexternal',
        'cintura_minima',
        'caderas_maxima',
        'muslo_superior',
        'muslo_medial',
        'pantorrilla_maxima',
    ];

    public function cliente()
    {
        return $this->belongsTo(Clientes::class, 'id_cliente');
    }
}
