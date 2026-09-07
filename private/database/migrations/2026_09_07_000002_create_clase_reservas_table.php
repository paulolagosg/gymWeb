<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Una fila por reserva de 1 cliente en 1 clase. El cupo ocupado se calcula
     * siempre por COUNT(*) sobre estado=1, nunca por un contador desnormalizado.
     * El UNIQUE evita que un cliente tenga 2 filas para la misma clase — reservar
     * de nuevo tras cancelar reutiliza (UPDATE) la fila existente.
     */
    public function up(): void
    {
        if (Schema::hasTable('clase_reservas')) {
            return;
        }

        Schema::create('clase_reservas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_clase')->constrained('clases')->cascadeOnDelete();
            $table->foreignId('id_cliente')->constrained('clientes')->cascadeOnDelete();
            $table->foreignId('id_gimnasio')->constrained('gimnasios')->cascadeOnDelete();
            $table->tinyInteger('estado')->default(1); // 1=Reservada, 2=Cancelada, 3=Asistio, 4=No-show
            $table->foreignId('creado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['id_clase', 'id_cliente']);
            $table->index(['id_clase', 'estado']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clase_reservas');
    }
};
