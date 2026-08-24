<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ejercicios_videos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_ejercicio')->constrained('ejercicios')->cascadeOnDelete();
            $table->string('archivo');
            $table->unsignedInteger('orden')->default(1);
            $table->timestamps();

            $table->unique(['id_ejercicio', 'archivo']);
            $table->index('id_ejercicio');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ejercicios_videos');
    }
};
