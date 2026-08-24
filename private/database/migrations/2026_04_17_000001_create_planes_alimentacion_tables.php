<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('planes_alimentacion', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_cliente')->constrained('clientes')->cascadeOnDelete();
            $table->foreignId('id_usuario_creador')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('id_usuario_editor')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('id_plan_origen')->nullable()->constrained('planes_alimentacion')->nullOnDelete();
            $table->string('nombre');
            $table->text('objetivo_nutricional')->nullable();
            $table->date('fecha_desde')->nullable();
            $table->date('fecha_hasta')->nullable();
            $table->text('alimentos_sustitucion')->nullable();
            $table->text('notas_generales')->nullable();
            $table->text('notas_internas')->nullable();
            $table->string('estado', 20)->default('activo');
            $table->unsignedInteger('version')->default(1);
            $table->timestamps();

            $table->index(['id_cliente', 'estado']);
            $table->index(['id_usuario_creador', 'estado']);
        });

        Schema::create('planes_alimentacion_dias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_plan_alimentacion')->constrained('planes_alimentacion')->cascadeOnDelete();
            $table->unsignedTinyInteger('dia_semana');
            $table->string('nombre_dia', 40);
            $table->unsignedTinyInteger('orden')->default(1);
            $table->text('observaciones')->nullable();
            $table->timestamps();

            $table->unique(['id_plan_alimentacion', 'dia_semana'], 'plan_alim_dias_unique');
            $table->index(['id_plan_alimentacion', 'orden'], 'plan_alim_dias_order_idx');
        });

        Schema::create('planes_alimentacion_comidas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_plan_alimentacion_dia')->constrained('planes_alimentacion_dias')->cascadeOnDelete();
            $table->string('codigo_comida', 40);
            $table->string('nombre_comida', 100);
            $table->unsignedTinyInteger('orden')->default(1);
            $table->json('items')->nullable();
            $table->text('reemplazos')->nullable();
            $table->text('observaciones')->nullable();
            $table->timestamps();

            $table->unique(['id_plan_alimentacion_dia', 'codigo_comida'], 'plan_alim_comidas_unique');
            $table->index(['id_plan_alimentacion_dia', 'orden'], 'plan_alim_comidas_order_idx');
        });

        Schema::create('planes_alimentacion_versiones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_plan_alimentacion')->constrained('planes_alimentacion')->cascadeOnDelete();
            $table->unsignedInteger('version');
            $table->string('descripcion_cambio', 255)->nullable();
            $table->json('snapshot');
            $table->foreignId('id_usuario')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['id_plan_alimentacion', 'version'], 'plan_alim_versiones_unique');
        });

        Schema::create('planes_alimentacion_registros', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_plan_alimentacion')->constrained('planes_alimentacion')->cascadeOnDelete();
            $table->date('fecha');
            $table->unsignedTinyInteger('dia_semana');
            $table->string('codigo_comida', 40);
            $table->boolean('cumplido')->default(false);
            $table->text('comentario')->nullable();
            $table->timestamps();

            $table->unique(['id_plan_alimentacion', 'fecha', 'codigo_comida'], 'planes_alimentacion_registros_unique');
            $table->index(['id_plan_alimentacion', 'fecha']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('planes_alimentacion_registros');
        Schema::dropIfExists('planes_alimentacion_versiones');
        Schema::dropIfExists('planes_alimentacion_comidas');
        Schema::dropIfExists('planes_alimentacion_dias');
        Schema::dropIfExists('planes_alimentacion');
    }
};
