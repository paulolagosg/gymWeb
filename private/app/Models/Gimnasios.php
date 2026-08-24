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
        'color_primario',
        'color_secundario',
        'email_encabezado',
        'email_firma',
        'email_pie',
        'sitio_web',
        'instagram',
        'facebook',
        'tiktok',
        'estado',
        'features',
        'plan',
    ];

    protected $casts = [
        'estado' => 'integer',
        'features' => 'array',
    ];

    /**
     * Claves de funcionalidades opcionales administrables por el super-admin
     * (id_tipo_usuario=10), apagadas por defecto en gimnasios nuevos.
     */
    public const FEATURE_KEYS = [
        'beneficios',
        'plan_alimentacion',
        'open_gym',
        'gamificacion',
        'compartir_progreso',
        'encuestas',
        'biblioteca_videos',
        'metricas_perimetros',
        'reporte_pdf',
        'reporte_agendas',
        'pagos_entrenadores',
    ];

    /**
     * Paquetes comerciales que encienden un conjunto de FEATURE_KEYS de una vez, en
     * vez de marcar las 11 casillas sueltas por gimnasio (3columnas.txt, Columna 2).
     * `plan = null` significa "personalizado": el gimnasio tiene una mezcla de flags
     * que no corresponde a ningún paquete completo. La composición de cada paquete
     * vive en la tabla `plan_presets` (editable por el super-admin desde "Planes
     * comerciales"), no está hardcodeada aquí.
     */
    public const PLAN_TIERS = ['starter', 'estandar', 'pro'];

    /**
     * Devuelve las claves de FEATURE_KEYS activas para $plan, o null si $plan no es
     * un paquete válido (equivalente a "personalizado", sin preset que aplicar).
     */
    public static function featurePresetForPlan(?string $plan): ?array
    {
        if (! in_array($plan, self::PLAN_TIERS, true)) {
            return null;
        }

        $stored = PlanPreset::where('plan', $plan)->value('features') ?? [];

        return array_values(array_filter(
            self::FEATURE_KEYS,
            fn (string $key) => (bool) ($stored[$key] ?? false),
        ));
    }

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

    public static function featuresActivas(?int $idGimnasio = null): array
    {
        $idGimnasio ??= static::gimnasioActualId();
        $stored = $idGimnasio ? (static::find($idGimnasio)?->features ?? []) : [];

        return array_reduce(self::FEATURE_KEYS, function (array $carry, string $key) use ($stored) {
            $carry[$key] = (bool) ($stored[$key] ?? false);
            return $carry;
        }, []);
    }

    public static function tieneFeature(string $key, ?int $idGimnasio = null): bool
    {
        if (! in_array($key, self::FEATURE_KEYS, true)) {
            return false;
        }

        return static::featuresActivas($idGimnasio)[$key] ?? false;
    }
}
