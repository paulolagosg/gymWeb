<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * No-op. Originalmente borraba el catálogo corto (6 secciones/22 preguntas) que
     * sembraba la migración 2026_04_13_000001_create_evaluacion_inicial_tables antes
     * de que esa siembra se moviera a EvaluacionInicialSeeder (ver Bloque 15). Como esa
     * migración ya no siembra nada, aquí no queda nada que limpiar — se deja vacía en
     * vez de eliminar el archivo para no romper el historial de migraciones ya
     * aplicadas en producción.
     */
    public function up(): void
    {
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No reversible: el catálogo corto original queda reemplazado por el
        // sembrado vía EvaluacionInicialSeeder. Volver a correr la migración
        // original crearía duplicados de tabla, no de datos.
    }
};
