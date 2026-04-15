<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MovimientosFinancieros extends Model
{
    protected $fillable = ['fecha', 'descripcion', 'tipo', 'monto', 'user_id', 'id_gimnasio'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function gimnasio()
    {
        return $this->belongsTo(Gimnasios::class, 'id_gimnasio');
    }
}
