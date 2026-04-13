<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Poseas extends Model
{
    use \Illuminate\Database\Eloquent\Factories\HasFactory;

    protected $table = 'poseas';
    public $timestamps = true;

    protected $fillable = [
        'valor',
        'id_cliente',
    ];

    protected $casts = [
        'valor' => 'decimal:2',
    ];

    public function cliente()
    {
        return $this->belongsTo(\App\Models\Clientes::class, 'id_cliente');
    }
}
