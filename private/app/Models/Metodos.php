<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Metodos extends Model
{
    protected $table = 'metodos';

    protected $fillable = [
        'nombre',
        'slug',
        'estado'
    ];
}
