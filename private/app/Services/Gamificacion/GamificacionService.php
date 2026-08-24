<?php

namespace App\Services\Gamificacion;

use App\Models\PuntosClientes;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class GamificacionService
{
    private const PUNTOS_POR_SESION = 10;

    /**
     * Estados de agenda que cuentan para la racha (agendado, realizado o
     * reprogramado). Cancelado (2 y 3) queda excluido: una sesión cancelada
     * no suma ni corta la racha.
     */
    private const ESTADOS_TRACKEABLES = [1, 4, 5];
    private const ESTADO_REALIZADO = 4;

    /**
     * Recalcula puntos y racha del cliente comparando lo agendado contra lo
     * realizado (no días calendario): la racha se corta si una sesión pasada
     * quedó agendada/reprogramada sin marcarse "Realizado", y sigue si el
     * cliente no ha faltado a ninguna. Se recalcula desde cero en lugar de
     * incrementar, para mantenerse correcto ante correcciones de estado
     * hechas por el entrenador/admin.
     */
    public function recalcularPuntos(int $idCliente): PuntosClientes
    {
        $sesiones = DB::table('agendas')
            ->where('id_cliente', $idCliente)
            ->whereIn('estado', self::ESTADOS_TRACKEABLES)
            ->where('fecha_inicio', '<=', now())
            ->orderBy('fecha_inicio')
            ->get(['fecha_inicio', 'estado']);

        $sesionesRealizadas = $sesiones->filter(
            fn($sesion) => (int) $sesion->estado === self::ESTADO_REALIZADO,
        );

        [$rachaActual, $rachaMaxima] = $this->calcularRachas($sesiones);

        $ultimaSesionRealizada = $sesionesRealizadas->last();

        return PuntosClientes::updateOrCreate(
            ['id_cliente' => $idCliente],
            [
                'puntos_totales' => $sesionesRealizadas->count() * self::PUNTOS_POR_SESION,
                'racha_actual' => $rachaActual,
                'racha_maxima' => $rachaMaxima,
                'ultima_fecha_sesion' => $ultimaSesionRealizada
                    ? Carbon::parse($ultimaSesionRealizada->fecha_inicio)->toDateString()
                    : null,
            ]
        );
    }

    /**
     * @param Collection<int, object{fecha_inicio: string, estado: int}> $sesiones ordenadas ascendente por fecha_inicio
     * @return array{0: int, 1: int} [rachaActual, rachaMaxima]
     */
    private function calcularRachas(Collection $sesiones): array
    {
        $rachaMaxima = 0;
        $rachaEnCurso = 0;

        foreach ($sesiones as $sesion) {
            if ((int) $sesion->estado === self::ESTADO_REALIZADO) {
                $rachaEnCurso++;
                $rachaMaxima = max($rachaMaxima, $rachaEnCurso);
            } else {
                $rachaEnCurso = 0;
            }
        }

        // Tras recorrer todo en orden cronológico, lo que queda en $rachaEnCurso
        // es la racha vigente (la que llega hasta la última sesión pasada).
        return [$rachaEnCurso, $rachaMaxima];
    }
}
