<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GimnasioFacturacion extends Model
{
    protected $table = 'gimnasio_facturaciones';

    protected $fillable = [
        'id_gimnasio',
        'plan',
        'monto',
        'fecha_inicio',
        'fecha_vencimiento',
        'fecha_pago',
        'id_estado_pago',
    ];

    protected $casts = [
        'fecha_inicio' => 'date',
        'fecha_vencimiento' => 'date',
        'fecha_pago' => 'date',
    ];

    public function gimnasio()
    {
        return $this->belongsTo(Gimnasios::class, 'id_gimnasio');
    }

    public function estadoPago()
    {
        return $this->belongsTo(EstadosPagos::class, 'id_estado_pago');
    }
}
