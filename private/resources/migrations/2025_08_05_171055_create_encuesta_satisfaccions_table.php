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
        Schema::create('encuesta_satisfaccions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_cliente');
            $table->unsignedBigInteger('id_entrenador');
            $table->tinyInteger('profesionalismo');
            $table->tinyInteger('claridad');
            $table->tinyInteger('motivacion');
            $table->tinyInteger('disponibilidad');
            $table->tinyInteger('puntualidad');
            $table->text('destacaria')->nullable();
            $table->text('sugerencias')->nullable();
            $table->tinyInteger('valoracion_global');
            $table->string('slug');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('encuesta_satisfaccions');
    }
};
