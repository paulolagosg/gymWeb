<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Leads capturados desde el selector de plan de la landing pública — todavía no
     * hay pasarela de pago ni alta de gimnasio self-service, así que el clic en
     * "Contratar" genera una solicitud de contacto en vez de una compra real.
     */
    public function up(): void
    {
        if (Schema::hasTable('solicitudes_contacto')) {
            return;
        }

        Schema::create('solicitudes_contacto', function (Blueprint $table) {
            $table->id();
            $table->string('nombre_gimnasio', 150);
            $table->string('nombre_contacto', 150);
            $table->string('email', 150);
            $table->string('telefono', 30)->nullable();
            $table->string('plan', 20);
            $table->text('mensaje')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('solicitudes_contacto');
    }
};
