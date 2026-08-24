<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Antes de este cambio, los clientes se creaban con estado=0 ("pendiente de
     * activacion") y quedaban asi hasta que alguien los activara manualmente.
     * Ahora todo cliente nuevo se crea directamente con estado=1. Esto deja
     * huerfanos a los clientes creados bajo el flujo viejo que nunca fueron
     * activados: siguen existiendo, asignados a su entrenador, pero quedan
     * fuera de los contadores/listados de "clientes activos".
     *
     * Se reactivan solo los que nunca fueron dados de baja a proposito
     * (fecha_baja IS NULL); un cliente realmente desactivado por un admin via
     * toggleStatus() siempre tiene fecha_baja poblada, asi que no se toca.
     */
    public function up(): void
    {
        DB::table('clientes')
            ->where('estado', 0)
            ->whereNull('fecha_baja')
            ->update(['estado' => 1]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No reversible: no hay forma de distinguir, despues del hecho, cuales
        // filas fueron activadas por esta migracion vs. ya estaban en estado=1.
    }
};
