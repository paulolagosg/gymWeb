<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EntrenadorPerfil extends Model
{
    protected $table = 'entrenadores_perfiles';

    protected $fillable = [
        'id_entrenador',
        'instagram',
        'foto',
    ];

    public function entrenador()
    {
        return $this->belongsTo(User::class, 'id_entrenador');
    }
}
