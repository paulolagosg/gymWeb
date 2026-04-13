<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pesos extends Model
{
    protected $table = 'pesos';

    protected $fillable = [
        'peso',
        'id_cliente',
        'sumatoria_pliegues',
        'grasa_region_superior',
        'grasa_region_media',
        'grasa_region_inferior',
    ];

    public function cliente()
    {
        return $this->belongsTo('App\Models\Clientes', 'id_cliente');
    }
    public function scopeWithCliente($query)
    {
        return $query->with('cliente');
    }
    public function scopeWithClienteId($query, $clienteId)
    {
        return $query->where('id_cliente', $clienteId);
    }
}
