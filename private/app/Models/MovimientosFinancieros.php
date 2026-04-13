<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MovimientosFinancieros extends Model
{
    protected $fillable = ['fecha', 'descripcion', 'tipo', 'monto', 'user_id'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
