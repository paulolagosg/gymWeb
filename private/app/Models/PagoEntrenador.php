<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PagoEntrenador extends Model
{
    protected $table = 'pagos_entrenadores';

    protected $fillable = [
        'entrenador_id',
        'year',
        'month',
        'sesiones_individual',
        'sesiones_duo',
        'valor_individual',
        'valor_duo',
        'bono',
        'descuento',
        'total'
    ];

    public function entrenador()
    {
        return $this->belongsTo(User::class, 'entrenador_id');
    }

    public function getTarifaVigente()
    {
        // Obtiene la tarifa vigente para el mes/año de este pago
        return HistorialTarifaEntrenador::where('entrenador_id', $this->entrenador_id)
            ->where('year', $this->year)
            ->where('month', $this->month)
            ->first();
    }
}
