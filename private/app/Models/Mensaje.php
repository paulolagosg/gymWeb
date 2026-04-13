<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Mensaje extends Model
{
    protected $fillable = ['de_id', 'para_id', 'contenido', 'leido'];

    public function remitente()
    {
        return $this->belongsTo(User::class, 'de_id');
    }
    public function destinatario()
    {
        return $this->belongsTo(User::class, 'para_id');
    }
    public function archivos()
    {
        return $this->hasMany(MensajeArchivo::class);
    }
}
