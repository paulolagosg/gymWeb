<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TermsAndConditions extends Model
{
    use HasFactory;

    protected $table = 'terms_and_conditions';

    protected $fillable = [
        'id_gimnasio',
        'titulo',
        'version',
        'contenido',
        'resumen_cambios',
        'activo',
        'obligatorio',
        'publicado_en',
        'version_anterior_id',
        'id_usuario_creador',
        'id_usuario_actualizador',
    ];

    protected $casts = [
        'activo' => 'boolean',
        'obligatorio' => 'boolean',
        'publicado_en' => 'datetime',
    ];

    public function gimnasio()
    {
        return $this->belongsTo(Gimnasios::class, 'id_gimnasio');
    }

    public function creador()
    {
        return $this->belongsTo(User::class, 'id_usuario_creador');
    }

    public function actualizador()
    {
        return $this->belongsTo(User::class, 'id_usuario_actualizador');
    }

    public function versionAnterior()
    {
        return $this->belongsTo(self::class, 'version_anterior_id');
    }

    public function versionesSiguientes()
    {
        return $this->hasMany(self::class, 'version_anterior_id');
    }

    public function aceptaciones()
    {
        return $this->hasMany(TermsAcceptance::class, 'id_terms_and_conditions');
    }
}
