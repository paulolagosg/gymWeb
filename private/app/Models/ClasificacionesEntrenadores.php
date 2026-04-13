<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClasificacionesEntrenadores extends Model
{
    protected $table = 'clasificaciones_entrenadores';

    protected $fillable = [
        'nombre',
        'slug',
        'descripcion',
        'estado',
    ];

    public function entrenadores()
    {
        return $this->hasMany(User::class, 'id_clasificacion');
    }
}
