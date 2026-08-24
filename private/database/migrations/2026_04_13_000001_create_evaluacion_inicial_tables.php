<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Idempotente a propósito: el esquema de este módulo existió en producción antes
     * de estar cubierto por migraciones (creado con un script DDL manual, ver
     * 3columnas.txt). Si esta migración llegara a correr sobre un entorno donde las
     * tablas ya existen por esa vía, cada bloque se salta en vez de fallar con "table
     * already exists" — nunca vuelve a aplicarse DDL a mano en producción a partir de
     * ahora, pero esta migración queda a prueba de ese historial.
     */
    public function up(): void
    {
        if (! Schema::hasTable('evaluacion_inicial_secciones')) {
            Schema::create('evaluacion_inicial_secciones', function (Blueprint $table) {
                $table->id();
                $table->string('codigo')->unique();
                $table->string('titulo');
                $table->text('descripcion')->nullable();
                $table->unsignedInteger('orden')->default(0);
                $table->boolean('estado')->default(true);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('evaluacion_inicial_preguntas')) {
            Schema::create('evaluacion_inicial_preguntas', function (Blueprint $table) {
                $table->id();
                $table->foreignId('seccion_id')->constrained('evaluacion_inicial_secciones')->cascadeOnDelete();
                $table->string('codigo')->unique();
                $table->string('pregunta', 500);
                $table->text('descripcion')->nullable();
                $table->string('tipo', 20);
                $table->boolean('es_requerida')->default(true);
                $table->boolean('permite_otro')->default(false);
                $table->unsignedInteger('orden')->default(0);
                $table->boolean('estado')->default(true);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('evaluacion_inicial_opciones')) {
            Schema::create('evaluacion_inicial_opciones', function (Blueprint $table) {
                $table->id();
                $table->foreignId('pregunta_id')->constrained('evaluacion_inicial_preguntas')->cascadeOnDelete();
                $table->string('codigo')->nullable();
                $table->string('etiqueta');
                $table->unsignedInteger('orden')->default(0);
                $table->boolean('es_otro')->default(false);
                $table->boolean('estado')->default(true);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('evaluaciones_iniciales')) {
            Schema::create('evaluaciones_iniciales', function (Blueprint $table) {
                $table->id();
                $table->foreignId('id_cliente')->unique()->constrained('clientes')->cascadeOnDelete();
                $table->timestamp('completada_en')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('evaluacion_inicial_respuestas')) {
            Schema::create('evaluacion_inicial_respuestas', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('id_gimnasio');
                $table->foreignId('evaluacion_inicial_id')->constrained('evaluaciones_iniciales')->cascadeOnDelete();
                $table->foreignId('pregunta_id')->constrained('evaluacion_inicial_preguntas')->cascadeOnDelete();
                $table->foreignId('opcion_id')->nullable()->constrained('evaluacion_inicial_opciones')->nullOnDelete();
                $table->text('valor_texto')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('evaluacion_inicial_respuestas');
        Schema::dropIfExists('evaluaciones_iniciales');
        Schema::dropIfExists('evaluacion_inicial_opciones');
        Schema::dropIfExists('evaluacion_inicial_preguntas');
        Schema::dropIfExists('evaluacion_inicial_secciones');
    }
};
