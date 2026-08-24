<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('puntos_clientes', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedBigInteger('id_cliente')->unique();
            $table->unsignedInteger('puntos_totales')->default(0);
            $table->unsignedInteger('racha_actual')->default(0);
            $table->unsignedInteger('racha_maxima')->default(0);
            $table->date('ultima_fecha_sesion')->nullable();
            $table->foreign('id_cliente')->references('id')->on('clientes');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('puntos_clientes');
    }
};
