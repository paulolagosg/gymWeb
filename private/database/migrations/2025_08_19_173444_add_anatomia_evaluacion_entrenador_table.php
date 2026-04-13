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
        Schema::table('evaluacion_entrenadors', function (Blueprint $table) {
            $table->tinyInteger('anatomia')->nullable();
            $table->tinyInteger('fisiologia')->nullable();
            $table->tinyInteger('programacion')->nullable();
            $table->tinyInteger('poblacion')->nullable();
            $table->tinyInteger('psicologia')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
