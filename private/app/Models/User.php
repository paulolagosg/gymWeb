<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'id_tipo_usuario',
        'porcentaje',
        'id_cliente',
        'id_clasificacion',
        'slug',
        'individual',
        'duo',
        'titulo',
        'id_gimnasio'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /*public function tipoUsuario()
    {
        return $this->belongsTo(TiposUsuarios::class, 'id_tipo_usuario');
    }*/

    public function tipoUsuario()
    {
        return $this->belongsTo(TiposUsuarios::class, 'id_tipo_usuario')->withDefault([
            'nombre' => 'Invitado' // Valor por defecto
        ]);
    }
    public function cliente()
    {
        return $this->belongsTo(Clientes::class, 'id_cliente');
    }

    public function gimnasio()
    {
        return $this->belongsTo(Gimnasios::class, 'id_gimnasio');
    }
    public function clasificacion()
    {
        return $this->belongsTo(ClasificacionesEntrenadores::class, 'id_clasificacion');
    }
    public function getNombreClasificacionAttribute()
    {
        return $this->clasificacion ? $this->clasificacion->nombre : null;
    }

    public function clientesNuevos()
    {
        return $this->hasMany(Clientes::class, 'id_usuario');
    }

    public function clientesFinMembresia()
    {
        return $this->hasMany(Clientes::class, 'id_usuario')
            ->whereNotNull('fecha_fin');
    }

    public function clientesBaja()
    {
        return $this->hasMany(Clientes::class, 'id_usuario')
            ->whereNotNull('fecha_baja');
    }

    public function clientesActivos()
    {
        return $this->hasMany(Clientes::class, 'id_usuario')
            ->whereNull('fecha_baja')
            ->where('estado', 1);
    }

    public function tasaRetencion()
    {
        $clientesIniciales = $this->clientesActivos()->count();
        $clientesBaja = $this->clientesBaja()->count();

        return $clientesIniciales > 0 ? ($clientesIniciales - $clientesBaja) / $clientesIniciales * 100 : 0;
    }
}
