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
        Schema::create('tareas', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('nombre');
            $table->longText('descripcion')->nullable();
            $table->boolean('completada')->default(false);
            $table->date('fecha_limite')->nullable();
            $table->string('slug')->unique()->nullable()->comment('Slug para URL amigable');
            $table->unsignedBigInteger('id_usuario')->comment('ID del usuario que debe hacer la tarea');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tareas');
    }
};
