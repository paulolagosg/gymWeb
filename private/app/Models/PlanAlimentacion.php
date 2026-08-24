<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlanAlimentacion extends Model
{
    protected $table = 'planes_alimentacion';

    protected $fillable = [
        'id_cliente',
        'id_usuario_creador',
        'id_usuario_editor',
        'id_plan_origen',
        'nombre',
        'objetivo_nutricional',
        'fecha_desde',
        'fecha_hasta',
        'alimentos_sustitucion',
        'notas_generales',
        'notas_internas',
        'estado',
        'version',
    ];

    protected $casts = [
        'fecha_desde' => 'date',
        'fecha_hasta' => 'date',
        'version' => 'integer',
    ];

    public function cliente()
    {
        return $this->belongsTo(Clientes::class, 'id_cliente');
    }

    public function creador()
    {
        return $this->belongsTo(User::class, 'id_usuario_creador');
    }

    public function editor()
    {
        return $this->belongsTo(User::class, 'id_usuario_editor');
    }

    public function planOrigen()
    {
        return $this->belongsTo(self::class, 'id_plan_origen');
    }

    public function dias()
    {
        return $this->hasMany(PlanAlimentacionDia::class, 'id_plan_alimentacion')
            ->orderBy('orden')
            ->orderBy('dia_semana');
    }

    public function versiones()
    {
        return $this->hasMany(PlanAlimentacionVersion::class, 'id_plan_alimentacion')
            ->orderByDesc('version');
    }

    public function registros()
    {
        return $this->hasMany(PlanAlimentacionRegistro::class, 'id_plan_alimentacion');
    }
}
