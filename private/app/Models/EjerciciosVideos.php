<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EjerciciosVideos extends Model
{
    protected $table = 'ejercicios_videos';

    protected $fillable = [
        'id_ejercicio',
        'archivo',
        'orden',
    ];

    protected $casts = [
        'id_ejercicio' => 'integer',
        'orden' => 'integer',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    public function ejercicio()
    {
        return $this->belongsTo(Ejercicios::class, 'id_ejercicio');
    }
}
