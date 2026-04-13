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
        Schema::create('agendas_ejercicios', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('id_agenda')->comment('ID de la agenda');
            $table->unsignedBigInteger('id_ejercicio')->comment('ID del ejercicio');
            $table->integer('serie');
            $table->string('repeticiones');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agendas_ejercicios');
    }
};
