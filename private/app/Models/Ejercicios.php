<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ejercicios extends Model
{
    protected $fillable = [
        'nombre',
        'icono',
        'id_tipo',
        'slug',
        'descripcion',
        'estado',
    ];
    protected $casts = [
        'estado' => 'integer',
    ];
    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    public function tipo()
    {
        return $this->belongsTo(TipoEjercicio::class, 'id_tipo');
    }
}
