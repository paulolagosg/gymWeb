<?php

namespace App\Models;

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ParqRespuestas extends Model
{
    protected $fillable = ['id_cliente', 'id_pregunta', 'respuesta', 'observaciones'];

    public function pregunta()
    {
        return $this->belongsTo(ParqPreguntas::class, 'id_pregunta');
    }

    public function cliente()
    {
        return $this->belongsTo(Clientes::class, 'id_cliente');
    }
}
