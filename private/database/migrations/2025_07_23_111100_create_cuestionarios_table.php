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
        Schema::create('cuestionarios', function (Blueprint $table) {
            $table->increments('id');
            $table->longText('patologias')->nullable();
            $table->longText('horario')->nullable();
            $table->longText('encantan')->nullable();
            $table->longText('no_gustan')->nullable();
            $table->longText('intolerancias')->nullable();
            $table->longText('suplemento')->nullable();
            $table->longText('duracion_entreno')->nullable();
            $table->longText('hora_gimnasio')->nullable();
            $table->longText('trabajo')->nullable();
            $table->longText('hora_acostarse')->nullable();
            $table->longText('hora_levantarse')->nullable();
            $table->longText('objetivo')->nullable();
            $table->longText('datos_interes')->nullable();
            $table->longText('dia_cualquiera')->nullable();
            $table->unsignedBigInteger('id_cliente')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cuestionarios');
    }
};
