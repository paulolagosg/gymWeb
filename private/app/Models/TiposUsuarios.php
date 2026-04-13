<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TiposUsuarios extends Model
{
    protected $table = 'tipos_usuarios';

    protected $fillable = [
        'nombre',
        'slug',
        'descripcion',
        'estado',
    ];

    protected $casts = [
        'estado' => 'integer',
    ];

    public function users()
    {
        return $this->hasMany(User::class, 'id_tipo_usuario');
    }
}
