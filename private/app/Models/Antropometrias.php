<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Antropometrias extends Model
{
    use HasFactory;

    protected $fillable = [
        'peso',
        'talla',
        'talla_sentado',
        'biacromial',
        'torax_transverso',
        'torax_anteriorposterior',
        'bi_iliocrestido',
        'humeral',
        'femoral',
        'cabeza',
        'brazo_relajado',
        'brazo_flexionado',
        'antebrazo',
        'torax_mesoesternal',
        'cintura_minima',
        'caderas_maxima',
        'muslo_superior',
        'muslo_medial',
        'pantorrilla_maxima',
        'triceps',
        'subescapular',
        'supraespinal',
        'abdominal',
        'pantorrilla'
    ];
}
