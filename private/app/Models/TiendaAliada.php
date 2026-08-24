<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TiendaAliada extends Model
{
    protected $table = 'tiendas_aliadas';

    protected $fillable = [
        'id_gimnasio',
        'nombre_comercial',
        'rubro',
        'correo_contacto',
        'telefono',
        'direccion',
        'instagram',
        'facebook',
        'web',
        'whatsapp',
        'logo_path',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    public function gimnasio()
    {
        return $this->belongsTo(Gimnasios::class, 'id_gimnasio');
    }

    public function beneficios()
    {
        return $this->hasMany(Beneficio::class, 'id_tienda');
    }
}
