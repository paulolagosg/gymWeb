<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ParqPreguntas extends Model
{
    protected $fillable = ['pregunta', 'activa'];

    public function respuestas()
    {
        return $this->hasMany(ParqRespuestas::class, 'pregunta_id');
    }
}
