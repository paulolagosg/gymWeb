<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Bloqueo de acceso por facturación de plataforma (trial vencido o suspensión
     * manual por morosidad) — independiente del campo `estado` existente, que sigue
     * siendo el toggle administrativo Activo/Inactivo sin relación con esto.
     */
    public function up(): void
    {
        Schema::table('gimnasios', function (Blueprint $table) {
            if (! Schema::hasColumn('gimnasios', 'bloqueado')) {
                $table->boolean('bloqueado')->default(false)->after('plan');
            }
            if (! Schema::hasColumn('gimnasios', 'bloqueado_motivo')) {
                $table->string('bloqueado_motivo', 30)->nullable()->after('bloqueado');
            }
        });
    }

    public function down(): void
    {
        Schema::table('gimnasios', function (Blueprint $table) {
            $table->dropColumn(['bloqueado', 'bloqueado_motivo']);
        });
    }
};
