<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MensajeArchivo extends Model
{
    protected $fillable = ['mensaje_id', 'archivo', 'tipo'];

    public function mensaje()
    {
        return $this->belongsTo(Mensaje::class);
    }
}
