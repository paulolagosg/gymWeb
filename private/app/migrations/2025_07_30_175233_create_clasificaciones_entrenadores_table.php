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
        Schema::create('clasificaciones_entrenadores', function (Blueprint $table) {
            $table->increments('id');
            $table->string('nombre', 50)->unique()->comment('Nombre de la clasificacion');
            $table->string('slug', 50)->unique()->comment('Slug de la clasificacion');
            $table->string('descripcion', 255)->nullable()->comment('Descripción de la clasificacion');
            $table->integer('estado')->default(1); // 1: activo, 0: inactivo
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clasificaciones_entrenadores');
    }
};
