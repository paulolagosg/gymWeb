<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IMCS extends Model
{
    protected $table = 'imcs';

    protected $fillable = [
        'imc',
        'id_cliente',
    ];

    public function cliente()
    {
        return $this->belongsTo('App\Models\Clientes', 'id_cliente');
    }
}
