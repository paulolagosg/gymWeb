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
        Schema::create('entrenadores_cursos', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('id_entrenador')->comment('ID del entrenador');
            $table->text('curso')->comment('Curso');
            $table->date('fecha_inicio')->comment('Fecha de inicio del curso');
            $table->date('fecha_fin')->comment('Fecha de finalización del curso');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('entrenadores_cursos');
    }
};
