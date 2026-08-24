<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('open_gym_rutinas', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('id_user');
            $table->unsignedBigInteger('id_cliente');
            $table->unsignedBigInteger('id_gimnasio')->nullable();
            $table->string('nombre');
            $table->text('descripcion')->nullable();
            $table->unsignedTinyInteger('frecuencia_semanal')->nullable();
            $table->unsignedSmallInteger('duracion_estimada_minutos')->nullable();
            $table->unsignedInteger('calorias_estimadas')->nullable();
            $table->unsignedBigInteger('id_rutina_origen')->nullable();
            $table->unsignedTinyInteger('activo')->default(1);
            $table->timestamps();

            $table->foreign('id_user')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('id_cliente')->references('id')->on('clientes')->cascadeOnDelete();
            $table->foreign('id_gimnasio')->references('id')->on('gimnasios')->nullOnDelete();
            $table->foreign('id_rutina_origen')->references('id')->on('open_gym_rutinas')->nullOnDelete();
            $table->index(['id_user', 'activo']);
        });

        Schema::create('open_gym_rutina_ejercicios', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('id_rutina');
            $table->unsignedBigInteger('id_ejercicio')->nullable();
            $table->string('nombre_personalizado')->nullable();
            $table->text('notas')->nullable();
            $table->unsignedInteger('orden');
            $table->unsignedTinyInteger('series')->default(3);
            $table->unsignedSmallInteger('reps_objetivo')->default(10);
            $table->unsignedSmallInteger('descanso_segundos')->default(60);
            $table->decimal('peso_objetivo', 8, 2)->nullable();
            $table->timestamps();

            $table->foreign('id_rutina')->references('id')->on('open_gym_rutinas')->cascadeOnDelete();
            $table->foreign('id_ejercicio')->references('id')->on('ejercicios')->nullOnDelete();
            $table->index(['id_rutina', 'orden']);
        });

        Schema::create('open_gym_entrenamientos', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('id_rutina')->nullable();
            $table->unsignedBigInteger('id_user');
            $table->unsignedBigInteger('id_cliente');
            $table->unsignedBigInteger('id_gimnasio')->nullable();
            $table->string('nombre_rutina');
            $table->string('estado', 20)->default('active');
            $table->timestamp('fecha_inicio');
            $table->timestamp('fecha_fin')->nullable();
            $table->unsignedInteger('duracion_segundos')->nullable();
            $table->unsignedInteger('calorias_estimadas')->nullable();
            $table->string('dificultad_percibida', 20)->nullable();
            $table->json('resumen_prs')->nullable();
            $table->json('resumen_grupos')->nullable();
            $table->timestamps();

            $table->foreign('id_rutina')->references('id')->on('open_gym_rutinas')->nullOnDelete();
            $table->foreign('id_user')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('id_cliente')->references('id')->on('clientes')->cascadeOnDelete();
            $table->foreign('id_gimnasio')->references('id')->on('gimnasios')->nullOnDelete();
            $table->index(['id_user', 'estado']);
            $table->index(['id_user', 'fecha_inicio']);
        });

        Schema::create('open_gym_entrenamiento_series', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('id_entrenamiento');
            $table->unsignedBigInteger('id_rutina_ejercicio')->nullable();
            $table->unsignedBigInteger('id_ejercicio')->nullable();
            $table->unsignedBigInteger('id_grupo_muscular')->nullable();
            $table->unsignedInteger('orden_ejercicio');
            $table->string('nombre_ejercicio');
            $table->unsignedTinyInteger('numero_serie');
            $table->unsignedSmallInteger('reps_objetivo')->nullable();
            $table->unsignedSmallInteger('reps_realizadas')->nullable();
            $table->decimal('peso_objetivo', 8, 2)->nullable();
            $table->decimal('peso_real', 8, 2)->nullable();
            $table->unsignedSmallInteger('descanso_segundos')->nullable();
            $table->timestamp('completado_en')->nullable();
            $table->unsignedTinyInteger('es_record_personal')->default(0);
            $table->timestamps();

            $table->foreign('id_entrenamiento')->references('id')->on('open_gym_entrenamientos')->cascadeOnDelete();
            $table->foreign('id_rutina_ejercicio')->references('id')->on('open_gym_rutina_ejercicios')->nullOnDelete();
            $table->foreign('id_ejercicio')->references('id')->on('ejercicios')->nullOnDelete();
            $table->foreign('id_grupo_muscular')->references('id')->on('grupos_musculares')->nullOnDelete();
            $table->index(['id_entrenamiento', 'orden_ejercicio'], 'oges_entrenamiento_orden_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('open_gym_entrenamiento_series');
        Schema::dropIfExists('open_gym_entrenamientos');
        Schema::dropIfExists('open_gym_rutina_ejercicios');
        Schema::dropIfExists('open_gym_rutinas');
    }
};
