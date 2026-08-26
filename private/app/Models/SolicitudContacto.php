<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SolicitudContacto extends Model
{
    protected $table = 'solicitudes_contacto';

    protected $fillable = [
        'nombre_gimnasio',
        'nombre_contacto',
        'email',
        'telefono',
        'plan',
        'mensaje',
    ];
}
