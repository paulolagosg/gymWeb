<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('historial_tarifas_entrenadores', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('entrenador_id');
            $table->unsignedSmallInteger('year');
            $table->unsignedTinyInteger('month');
            $table->decimal('individual', 10, 2);
            $table->decimal('duo', 10, 2);
            $table->timestamps();

            $table->foreign('entrenador_id')->references('id')->on('users')->onDelete('cascade');
            $table->unique(['entrenador_id', 'year', 'month']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('historial_tarifas_entrenadores');
    }
};
