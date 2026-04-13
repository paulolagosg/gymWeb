<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('pagos_entrenadores', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('entrenador_id');
            $table->unsignedSmallInteger('year');
            $table->unsignedTinyInteger('month');
            $table->unsignedInteger('sesiones_individual')->default(0);
            $table->unsignedInteger('sesiones_duo')->default(0);
            $table->decimal('valor_individual', 10, 2)->default(0);
            $table->decimal('valor_duo', 10, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);
            $table->timestamps();

            $table->foreign('entrenador_id')->references('id')->on('users')->onDelete('cascade');
            $table->unique(['entrenador_id', 'year', 'month']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pagos_entrenadores');
    }
};
