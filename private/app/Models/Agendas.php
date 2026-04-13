<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Agendas extends Model
{
    protected $table = 'agendas';

    protected $fillable = [
        'id_cliente',
        'id_usuario',
        'fecha_inicio',
        'fecha_fin',
        'titulo',
        'descripcion',
        'slug'
    ];
    protected $casts = [
        'fecha_inicio' => 'datetime',
        'fecha_fin' => 'datetime',
    ];

    public function cliente()
    {
        return $this->belongsTo('App\Models\Clientes', 'id_cliente');
    }

    public function usuario()
    {
        return $this->belongsTo('App\Models\User', 'id_usuario');
    }
    // public function ejercicios()
    // {
    //     return $this->belongsToMany(Ejercicios::class, 'agendas_ejercicios', 'id_agenda', 'id_ejercicio')
    //         ->withPivot('serie', 'repeticiones', 'rir', 'rpe', 'rm', 'metodo', 'progresion', 'fundamento', 'carga', 'descanso')
    //         ->withTimestamps();
    // }

    public function ejercicios()
    {
        return $this->belongsToMany(\App\Models\Ejercicios::class, 'agendas_ejercicios', 'id_agenda', 'id_ejercicio')
            ->withPivot([
                'serie',
                'repeticiones',
                'rir',
                'rpe',
                'rm',
                'metodo',
                'progresion',
                'fundamento',
                'carga',
                'descanso'
            ]);
    }

    public static function obtenerEvolucionCliente($slug)
    {
        return DB::table('agendas as a')
            ->selectRaw("
            a.id_cliente,
            DATE_FORMAT(a.fecha_inicio, '%Y-%m') as mes,
            concat('<img src=\"/iconos/',e.icono,'\"  />') as imagen,
            e.nombre as ejercicio,
            e.id as id_ejercicio,
            
            -- Estadísticas de SERIES
            COUNT(ae.id) as total_series,
            ROUND(AVG(ae.serie),2) as serie_promedio,
            MAX(ae.serie) as serie_maxima,
            
            -- Estadísticas de REPETICIONES
            ROUND(AVG(ae.repeticiones),2) as repeticiones_promedio,
            MAX(ae.repeticiones) as repeticiones_maximas,
            MIN(ae.repeticiones) as repeticiones_minimas,
            
            -- Estadísticas de RIR
            ROUND(AVG(ae.rir),2) as rir_promedio,
            MIN(ae.rir) as rir_minimo,
            MAX(ae.rir) as rir_maximo,
            
            -- Estadísticas de RPE
            ROUND(AVG(ae.rpe),2) as rpe_promedio,
            MIN(ae.rpe) as rpe_minimo,
            MAX(ae.rpe) as rpe_maximo,
            
            -- Estadísticas de RM
            ROUND(AVG(ae.rm),2) as rm_promedio,
            MAX(ae.rm) as rm_maximo,
            MIN(ae.rm) as rm_minimo,
            
            -- TENDENCIAS
            CASE 
                WHEN AVG(ae.serie) > LAG(AVG(ae.serie)) OVER (PARTITION BY a.id_cliente, ae.id_ejercicio ORDER BY DATE_FORMAT(a.fecha_inicio, '%Y-%m')) THEN '📈 Aumenta'
                WHEN AVG(ae.serie) < LAG(AVG(ae.serie)) OVER (PARTITION BY a.id_cliente, ae.id_ejercicio ORDER BY DATE_FORMAT(a.fecha_inicio, '%Y-%m')) THEN '📉 Disminuye'
                WHEN LAG(AVG(ae.serie)) OVER (PARTITION BY a.id_cliente, ae.id_ejercicio ORDER BY DATE_FORMAT(a.fecha_inicio, '%Y-%m')) IS NULL THEN '🆕 Nuevo'
                ELSE '➡️ Estable'
            END as tendencia_series,
            
            CASE 
                WHEN AVG(ae.repeticiones) > LAG(AVG(ae.repeticiones)) OVER (PARTITION BY a.id_cliente, ae.id_ejercicio ORDER BY DATE_FORMAT(a.fecha_inicio, '%Y-%m')) THEN '📈 Aumenta'
                WHEN AVG(ae.repeticiones) < LAG(AVG(ae.repeticiones)) OVER (PARTITION BY a.id_cliente, ae.id_ejercicio ORDER BY DATE_FORMAT(a.fecha_inicio, '%Y-%m')) THEN '📉 Disminuye'
                WHEN LAG(AVG(ae.repeticiones)) OVER (PARTITION BY a.id_cliente, ae.id_ejercicio ORDER BY DATE_FORMAT(a.fecha_inicio, '%Y-%m')) IS NULL THEN '🆕 Nuevo'
                ELSE '➡️ Estable'
            END as tendencia_repeticiones,
            
            CASE 
                WHEN AVG(ae.rir) < LAG(AVG(ae.rir)) OVER (PARTITION BY a.id_cliente, ae.id_ejercicio ORDER BY DATE_FORMAT(a.fecha_inicio, '%Y-%m')) THEN '💪 Mayor intensidad'
                WHEN AVG(ae.rir) > LAG(AVG(ae.rir)) OVER (PARTITION BY a.id_cliente, ae.id_ejercicio ORDER BY DATE_FORMAT(a.fecha_inicio, '%Y-%m')) THEN '😌 Menor intensidad'
                WHEN LAG(AVG(ae.rir)) OVER (PARTITION BY a.id_cliente, ae.id_ejercicio ORDER BY DATE_FORMAT(a.fecha_inicio, '%Y-%m')) IS NULL THEN '🆕 Nuevo'
                ELSE '➡️ Estable'
            END as tendencia_rir,
            
            CASE 
                WHEN AVG(ae.rpe) > LAG(AVG(ae.rpe)) OVER (PARTITION BY a.id_cliente, ae.id_ejercicio ORDER BY DATE_FORMAT(a.fecha_inicio, '%Y-%m')) THEN '🔥 Mayor esfuerzo'
                WHEN AVG(ae.rpe) < LAG(AVG(ae.rpe)) OVER (PARTITION BY a.id_cliente, ae.id_ejercicio ORDER BY DATE_FORMAT(a.fecha_inicio, '%Y-%m')) THEN '🌿 Menor esfuerzo'
                WHEN LAG(AVG(ae.rpe)) OVER (PARTITION BY a.id_cliente, ae.id_ejercicio ORDER BY DATE_FORMAT(a.fecha_inicio, '%Y-%m')) IS NULL THEN '🆕 Nuevo'
                ELSE '➡️ Estable'
            END as tendencia_rpe,
            
            CASE 
                WHEN AVG(ae.rm) > LAG(AVG(ae.rm)) OVER (PARTITION BY a.id_cliente, ae.id_ejercicio ORDER BY DATE_FORMAT(a.fecha_inicio, '%Y-%m')) THEN '💪 Mayor RM'
                WHEN AVG(ae.rm) < LAG(AVG(ae.rm)) OVER (PARTITION BY a.id_cliente, ae.id_ejercicio ORDER BY DATE_FORMAT(a.fecha_inicio, '%Y-%m')) THEN '📉 Menor RM'
                WHEN LAG(AVG(ae.rm)) OVER (PARTITION BY a.id_cliente, ae.id_ejercicio ORDER BY DATE_FORMAT(a.fecha_inicio, '%Y-%m')) IS NULL THEN '🆕 Nuevo'
                ELSE '➡️ Estable'
            END as tendencia_rm
        ")
            ->join('clientes as c', 'a.id_cliente', 'c.id')
            ->join('agendas_ejercicios as ae', 'a.id', '=', 'ae.id_agenda')
            ->join('ejercicios as e', 'ae.id_ejercicio', '=', 'e.id')
            ->where('c.slug', $slug)
            ->groupBy('a.id_cliente', DB::raw("DATE_FORMAT(a.fecha_inicio, '%Y-%m')"), 'ae.id_ejercicio', 'e.nombre', 'e.id')
            ->orderBy(DB::raw("DATE_FORMAT(a.fecha_inicio, '%Y-%m')"))
            ->orderBy('e.nombre')
            ->get();
    }

    // Función para procesar los resultados de forma más organizada
    public static function procesarEvolucionCliente($slug)
    {
        $resultados = self::obtenerEvolucionCliente($slug);

        return $resultados->groupBy('mes')->map(function ($mesData, $mes) {
            return [
                'mes' => $mes,
                'ejercicios' => $mesData->map(function ($ejercicio) {
                    return [
                        'ejercicio_id' => $ejercicio->id_ejercicio,
                        'ejercicio_nombre' => $ejercicio->ejercicio,
                        'metricas' => [
                            'series' => [
                                'total' => (int) $ejercicio->total_series,
                                'promedio' => round($ejercicio->serie_promedio, 2),
                                'maxima' => (int) $ejercicio->serie_maxima,
                                'tendencia' => $ejercicio->tendencia_series
                            ],
                            'repeticiones' => [
                                'promedio' => round($ejercicio->repeticiones_promedio, 2),
                                'maxima' => (int) $ejercicio->repeticiones_maximas,
                                'minima' => (int) $ejercicio->repeticiones_minimas,
                                'tendencia' => $ejercicio->tendencia_repeticiones
                            ],
                            'rir' => [
                                'promedio' => round($ejercicio->rir_promedio, 2),
                                'minimo' => (int) $ejercicio->rir_minimo,
                                'maximo' => (int) $ejercicio->rir_maximo,
                                'tendencia' => $ejercicio->tendencia_rir
                            ],
                            'rpe' => [
                                'promedio' => round($ejercicio->rpe_promedio, 2),
                                'minimo' => (int) $ejercicio->rpe_minimo,
                                'maximo' => (int) $ejercicio->rpe_maximo,
                                'tendencia' => $ejercicio->tendencia_rpe
                            ],
                            'rm' => [
                                'promedio' => round($ejercicio->rm_promedio, 2),
                                'maximo' => (int) $ejercicio->rm_maximo,
                                'minimo' => (int) $ejercicio->rm_minimo,
                                'tendencia' => $ejercicio->tendencia_rm
                            ]
                        ]
                    ];
                })
            ];
        })->values();
    }
}
