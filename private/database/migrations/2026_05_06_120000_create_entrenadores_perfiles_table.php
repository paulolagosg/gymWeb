<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('entrenadores_perfiles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_entrenador')->unique();
            $table->string('instagram', 150)->nullable();
            $table->string('foto')->nullable();
            $table->timestamps();

            $table->foreign('id_entrenador')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('entrenadores_perfiles');
    }
};
