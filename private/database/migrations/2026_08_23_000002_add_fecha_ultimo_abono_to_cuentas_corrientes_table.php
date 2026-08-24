<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Fecha del abono parcial más reciente de una cuota. Distinta de
     * `fecha_pago`, que sigue significando "fecha en que la cuota quedó
     * 100% pagada" (el significado que ya usan todas las queries de
     * moroso con `whereNull('fecha_pago')`).
     */
    public function up(): void
    {
        Schema::table('cuentas_corrientes', function (Blueprint $table) {
            $table->date('fecha_ultimo_abono')->nullable()->after('fecha_pago');
        });
    }

    public function down(): void
    {
        Schema::table('cuentas_corrientes', function (Blueprint $table) {
            $table->dropColumn('fecha_ultimo_abono');
        });
    }
};
