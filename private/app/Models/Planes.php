<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Planes extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $fillable = [
        'nombre',
        'descripcion',
        'valor',
        'porcentaje',
        'slug',
        'estado',
        'id_gimnasio'
    ];
    protected $casts = [
        'valor' => 'integer',
        'estado' => 'integer',
    ];
    protected $hidden = [
        'created_at',
        'updated_at',
    ];
    public function getRouteKeyName()
    {
        return 'slug';
    }

    public function setSlugAttribute($value)
    {
        $this->attributes['slug'] = \Illuminate\Support\Str::slug($value);
    }
    public function setNombreAttribute($value)
    {
        $this->attributes['nombre'] = ucfirst($value);
    }
    public function getNombreAttribute($value)
    {
        return ucfirst($value);
    }
    public function setValorAttribute($value)
    {
        $this->attributes['valor'] = is_numeric($value) ? (int)$value : 0;
    }

    public function scopeActivos($query)
    {
        return $query->where('estado', 1);
    }
    public function scopeInactivos($query)
    {
        return $query->where('estado', 0);
    }

    public function gimnasio()
    {
        return $this->belongsTo(Gimnasios::class, 'id_gimnasio');
    }
}
