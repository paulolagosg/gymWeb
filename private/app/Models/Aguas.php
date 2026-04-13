<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Aguas extends Model
{
    protected $table = 'aguas';

    protected $fillable = [
        'valor',
        'id_cliente',
    ];

    public function cliente()
    {
        return $this->belongsTo('App\Models\Clientes', 'id_cliente');
    }
}
