<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Beneficio extends Model
{
    protected $table = 'beneficios';

    protected $fillable = [
        'id_gimnasio',
        'id_tienda',
        'id_usuario_creador',
        'id_usuario_editor',
        'titulo',
        'descripcion',
        'tipo',
        'valor',
        'condicion',
        'promocion_cantidad',
        'fecha_inicio',
        'fecha_fin',
        'codigo_promocional',
        'terminos_condiciones',
        'estado',
    ];

    protected $casts = [
        'valor' => 'decimal:2',
        'promocion_cantidad' => 'array',
        'fecha_inicio' => 'date',
        'fecha_fin' => 'date',
    ];

    public function gimnasio()
    {
        return $this->belongsTo(Gimnasios::class, 'id_gimnasio');
    }

    public function tienda()
    {
        return $this->belongsTo(TiendaAliada::class, 'id_tienda');
    }

    public function creador()
    {
        return $this->belongsTo(User::class, 'id_usuario_creador');
    }

    public function editor()
    {
        return $this->belongsTo(User::class, 'id_usuario_editor');
    }
}
