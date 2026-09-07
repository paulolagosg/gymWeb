<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Clases grupales con cupo (aforo). Cada fila es una ocurrencia concreta ya
     * agendada — la recurrencia (ej. "todos los lunes, 8 semanas") se expande en N
     * filas al crear, mismo patrón que ya usa AgendasController::store() para las
     * sesiones 1 a 1, no una regla evaluada en cada lectura.
     */
    public function up(): void
    {
        if (Schema::hasTable('clases')) {
            return;
        }

        Schema::create('clases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_gimnasio')->constrained('gimnasios')->cascadeOnDelete();
            $table->foreignId('id_usuario')->constrained('users')->cascadeOnDelete();
            $table->string('nombre', 150);
            $table->text('descripcion')->nullable();
            $table->dateTime('fecha_inicio');
            $table->dateTime('fecha_fin');
            $table->unsignedInteger('aforo');
            $table->tinyInteger('estado')->default(1); // 1=Activa, 2=Cancelada
            $table->foreignId('id_clase_origen')->nullable()->constrained('clases')->nullOnDelete();
            $table->timestamps();

            $table->index(['id_gimnasio', 'fecha_inicio']);
            $table->index('id_clase_origen');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clases');
    }
};
