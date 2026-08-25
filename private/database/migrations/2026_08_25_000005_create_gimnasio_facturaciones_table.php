<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ledger de ciclos de facturación de cada gimnasio hacia la plataforma — mismo
     * patrón que `cuentas_corrientes` (cuotas de clientes), pero a nivel de gimnasio.
     * `fecha_pago IS NULL` = ciclo sin pagar (invariante: solo hay una fila así por
     * gimnasio a la vez, mantenida en adminGimnasiosPlanUpdate/adminGimnasioMarcarPago).
     */
    public function up(): void
    {
        if (Schema::hasTable('gimnasio_facturaciones')) {
            return;
        }

        Schema::create('gimnasio_facturaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_gimnasio')->constrained('gimnasios')->cascadeOnDelete();
            $table->string('plan', 20);
            $table->bigInteger('monto')->default(0);
            $table->date('fecha_inicio');
            $table->date('fecha_vencimiento');
            $table->date('fecha_pago')->nullable();
            $table->unsignedInteger('id_estado_pago')->default(1);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gimnasio_facturaciones');
    }
};
