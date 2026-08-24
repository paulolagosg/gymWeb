<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;

class Clientes extends Model
{
    //
    use Notifiable;
    protected $table = 'clientes';
    protected $fillable = [
        'ci',
        'nombres',
        'paterno',
        'materno',
        'telefono',
        'id_genero',
        'altura',
        'fecha_baja',
        'email',
        'direccion',
        'ciudad',
        'fecha_nacimiento',
        'fecha_ingreso',
        'fecha_pago',
        'fecha_fin',
        'slug',
        'id_plan',
        'id_usuario',
        'estado',
        'id_motivo_ingreso',
        'id_motivo_egreso',
        'otro_ingreso',
        'otro_egreso',
        'perfil',
        'id_cliente_duo',
        'id_gimnasio',
        'foto_path',
    ];
    protected $casts = [
        'fecha_nacimiento' => 'date',
        'fecha_ingreso' => 'date',
        'fecha_pago' => 'date',
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
    public function setFechaNacimientoAttribute($value)
    {
        $this->attributes['fecha_nacimiento'] = $value ? \Carbon\Carbon::parse($value) : null;
    }
    public function setFechaIngresoAttribute($value)
    {
        $this->attributes['fecha_ingreso'] = \Carbon\Carbon::parse($value);
    }
    public function setFechaPagoAttribute($value)
    {
        $this->attributes['fecha_pago'] = \Carbon\Carbon::parse($value);
    }
    public function getFechaNacimientoAttribute($value)
    {
        return $value ? \Carbon\Carbon::parse($value)->format('Y-m-d') : null;
    }
    public function getFechaIngresoAttribute($value)
    {
        return \Carbon\Carbon::parse($value)->format('Y-m-d');
    }
    public function getFechaPagoAttribute($value)
    {
        return \Carbon\Carbon::parse($value)->format('Y-m-d');
    }
    public function genero()
    {
        return $this->belongsTo(\App\Models\Generos::class, 'id_genero');
    }
    public function scopeActivos($query)
    {
        return $query->where('estado', 1);
    }
    public function scopeInactivos($query)
    {
        return $query->where('estado', 0);
    }

    public function cuotas()
    {
        return $this->hasMany(CuentasCorrientes::class, 'id_cliente');
    }

    public function plan()
    {
        return $this->belongsTo(Planes::class, 'id_plan');
    }

    public function tipoUsuario()
    {
        return $this->hasOneThrough(
            TiposUsuarios::class,
            User::class,
            'id_cliente',
            'id',
            'id',
            'id_tipo_usuario'
        );
    }

    public function tipos_usuarios()
    {
        return $this->tipoUsuario();
    }

    public function gimnasio()
    {
        return $this->belongsTo(Gimnasios::class, 'id_gimnasio');
    }

    public function user()
    {
        //return $this->belongsTo(User::class, 'id_usuario');
        return $this->hasOne(User::class, 'id_cliente');
    }
    public function pesos()
    {
        return $this->hasMany(Pesos::class, 'id_cliente');
    }
    public function imcs()
    {
        return $this->hasMany(IMCS::class, 'id_cliente');
    }
    public function aguas()
    {
        return $this->hasMany(Aguas::class, 'id_cliente');
    }
    public function grasas()
    {
        return $this->hasMany(Grasas::class, 'id_cliente');
    }
    public function pmusculares()
    {
        return $this->hasMany(Pmusculares::class, 'id_cliente');
    }
    public function poseas()
    {
        return $this->hasMany(Poseas::class, 'id_cliente');
    }
    public function cuestionarios()
    {
        return $this->hasMany(Cuestionarios::class, 'id_cliente');
    }
    public function cuestionariosHistoricos()
    {
        return $this->hasMany(CuestionariosHistoricos::class, 'id_cliente');
    }

    public function entrenador()
    {
        return $this->belongsTo(User::class, 'id_usuario');
    }
    public function motivo_ingreso()
    {
        return $this->belongsTo(Motivos::class, 'id_motivo_ingreso');
    }
    public function motivo_egreso()
    {
        return $this->belongsTo(Motivos::class, 'id_motivo_egreso');
    }
    public function perimetros()
    {
        return $this->hasMany(\App\Models\Perimetro::class, 'id_cliente');
    }

    public function evaluacionInicial()
    {
        return $this->hasOne(EvaluacionInicial::class, 'id_cliente');
    }

    public function planesAlimentacion()
    {
        return $this->hasMany(PlanAlimentacion::class, 'id_cliente');
    }

    public function planAlimentacionActivo()
    {
        return $this->hasOne(PlanAlimentacion::class, 'id_cliente')
            ->where('estado', 'activo')
            ->latest('updated_at');
    }

    public function isOpenGym(): bool
    {
        return (int) optional($this->user)->id_tipo_usuario === 5;
    }
}
