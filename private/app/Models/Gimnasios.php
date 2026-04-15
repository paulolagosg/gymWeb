<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class Gimnasios extends Model
{
    use HasFactory;

    protected $table = 'gimnasios';

    protected $fillable = [
        'nombre',
        'slug',
        'direccion',
        'descripcion',
        'telefono',
        'correo_electronico',
        'sitio_web',
        'instagram',
        'facebook',
        'tiktok',
        'estado',
    ];

    protected $casts = [
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
        $this->attributes['slug'] = Str::slug($value);
    }

    public function clientes()
    {
        return $this->hasMany(Clientes::class, 'id_gimnasio');
    }

    public function agendas()
    {
        return $this->hasMany(Agendas::class, 'id_gimnasio');
    }

    public function movimientosFinancieros()
    {
        return $this->hasMany(MovimientosFinancieros::class, 'id_gimnasio');
    }

    public function planes()
    {
        return $this->hasMany(Planes::class, 'id_gimnasio');
    }

    public function users()
    {
        return $this->hasMany(User::class, 'id_gimnasio');
    }

    public static function gimnasioActualId(): ?int
    {
        try {
            if (app()->bound('session') && session()->has('id_gimnasio_actual')) {
                return (int) session('id_gimnasio_actual');
            }

            if (Auth::check() && Auth::user()->id_gimnasio) {
                if (app()->bound('session')) {
                    session(['id_gimnasio_actual' => (int) Auth::user()->id_gimnasio]);
                }

                return (int) Auth::user()->id_gimnasio;
            }

            return static::query()
                ->where('estado', 1)
                ->orderBy('id')
                ->value('id')
                ?? static::query()->orderBy('id')->value('id');
        } catch (\Throwable $th) {
            return null;
        }
    }

    public static function gimnasioActual(): ?self
    {
        $idGimnasio = static::gimnasioActualId();

        return $idGimnasio ? static::find($idGimnasio) : null;
    }
}
