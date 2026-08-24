<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EntrenadoresCursos extends Model
{
    protected $table = 'entrenadores_cursos';

    protected $fillable = [
        'id_entrenador',
        'curso',
        'tipo',
        'fecha_inicio',
        'fecha_fin',
        'institucion',
        'modalidad',
        'slug',
    ];

    public function entrenador()
    {
        return $this->belongsTo(User::class, 'id_entrenador');
    }
}
