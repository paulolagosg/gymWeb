<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Motivos extends Model
{
    protected $table = 'motivos';

    protected $fillable = [
        'nombre',
        'slug',
        'tipo',
        'estado',
    ];
}
