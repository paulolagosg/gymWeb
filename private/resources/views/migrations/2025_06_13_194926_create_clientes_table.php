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
        Schema::create('clientes', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('ci')->unique();
            $table->string('nombres');
            $table->string('paterno');
            $table->string('materno')->nullable();
            $table->string('telefono');
            $table->integer('id_genero');
            $table->integer('id_plan');
            $table->string('email')->unique();
            $table->string('direccion')->nullable();
            $table->string('ciudad')->nullable();
            $table->date('fecha_nacimiento')->nullable();
            $table->date('fecha_ingreso');
            $table->date('fecha_pago');
            $table->string('slug')->unique();
            $table->integer('estado')->default(1); // 1: activo, 0: inactivo
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clientes');
    }
};
