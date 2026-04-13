<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TipoEjercicio extends Model
{
    protected $table = 'tipos_ejercicios';

    protected $fillable = [
        'nombre',
        'icono'
    ];

    public function ejercicios()
    {
        return $this->hasMany(Ejercicios::class, 'id_tipo');
    }
}
