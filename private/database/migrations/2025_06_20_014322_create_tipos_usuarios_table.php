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
        Schema::create('tipos_usuarios', function (Blueprint $table) {
            $table->increments('id');
            $table->string('nombre', 50)->unique()->comment('Nombre del tipo de usuario');
            $table->string('slug', 50)->unique()->comment('Slug del tipo de usuario');
            $table->string('descripcion', 255)->nullable()->comment('Descripción del tipo de usuario');
            $table->integer('estado')->default(1); // 1: activo, 0: inactivo
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tipos_usuarios');
    }
};
