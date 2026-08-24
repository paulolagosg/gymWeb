<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * El servidor tenía `evaluaciones_iniciales` con un esquema de una iteración
     * anterior de esta funcionalidad (columnas `estado`, `fecha_completada`,
     * `revisada_por`, alertas, autorización médica, consentimiento, etc.) que no
     * coincide con lo que el código actual usa (`id_cliente`, `completada_en`).
     * Eso provocaba un 500 en el dashboard del entrenador al consultar
     * `completada_en`, columna inexistente. Se aborta si llegara a haber datos
     * reales para no perderlos; se confirmó vacía antes de escribir esto.
     */
    public function up(): void
    {
        if (!Schema::hasTable('evaluaciones_iniciales')) {
            return;
        }

        $count = DB::table('evaluaciones_iniciales')->count();

        if ($count > 0) {
            throw new \RuntimeException(
                "Abortando: 'evaluaciones_iniciales' tiene {$count} fila(s). Esta migración recrea la tabla y borraría datos reales; revisar antes de continuar."
            );
        }

        Schema::disableForeignKeyConstraints();

        try {
            Schema::dropIfExists('evaluaciones_iniciales');

            Schema::create('evaluaciones_iniciales', function (Blueprint $table) {
                $table->id();
                $table->foreignId('id_cliente')->unique()->constrained('clientes')->cascadeOnDelete();
                $table->timestamp('completada_en')->nullable();
                $table->timestamps();
            });
        } finally {
            Schema::enableForeignKeyConstraints();
        }
    }

    public function down(): void
    {
        // Intencionalmente vacío: revertir recrearía el esquema legacy que se está reemplazando.
    }
};
