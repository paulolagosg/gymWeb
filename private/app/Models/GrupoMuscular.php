<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GrupoMuscular extends Model
{
    protected $table = 'grupos_musculares';

    protected $fillable = [
        'nombre',
        'icono',
        'color',
        'estado',
    ];

    protected $casts = [
        'estado' => 'integer',
    ];

    public function tiposEjercicio()
    {
        return $this->hasMany(TipoEjercicio::class, 'id_grupo');
    }
}
