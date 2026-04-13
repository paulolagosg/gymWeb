<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tareas extends Model
{
    protected $table = 'tareas';

    protected $fillable = [
        'nombre',
        'descripcion',
        'completada',
        'fecha_limite',
        'id_usuario',
        'slug'
    ];

    public function usuario()
    {
        return $this->belongsTo(User::class, 'id_usuario');
    }
}
