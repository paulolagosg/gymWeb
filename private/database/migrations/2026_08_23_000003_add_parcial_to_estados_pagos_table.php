<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Agrega el estado "Parcial" al catálogo de estados de pago, para
     * cuotas con un abono registrado pero saldo pendiente. Guardado por
     * slug (no asume un id fijo) — el código que lo usa lo resuelve en
     * runtime buscando por slug, no por id hardcodeado.
     */
    public function up(): void
    {
        if (! Schema::hasTable('estados_pagos')) {
            return;
        }

        $exists = DB::table('estados_pagos')->where('slug', 'parcial')->exists();
        if ($exists) {
            return;
        }

        DB::table('estados_pagos')->insert([
            'nombre' => 'Parcial',
            'slug' => 'parcial',
            'color' => '#f97316',
            'icono' => 'alert-half',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('estados_pagos')->where('slug', 'parcial')->delete();
    }
};
