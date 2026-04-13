<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Generos extends Model
{
    //
    protected $table = 'generos';
    protected $fillable = [
        'nombre',
        'slug',
        'estado'
    ];
    protected $casts = [
        'estado' => 'boolean',
    ];
    protected $hidden = [
        'created_at',
        'updated_at',
    ];
    public function getRouteKeyName()
    {
        return 'slug';
    }
    public function getEstadoAttribute($value)
    {
        return $value == 1 ? 'Activo' : 'Inactivo';
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
    public function clientes()
    {
        return $this->hasMany(Clientes::class, 'id_genero', 'id');
    }
    public function scopeActivos($query)
    {
        return $query->where('estado', 1);
    }
    public function scopeInactivos($query)
    {
        return $query->where('estado', 0);
    }
}
