<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('perimetros', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_cliente');
            $table->float('cabeza')->nullable();
            $table->float('brazo_relajado')->nullable();
            $table->float('brazo_flexionado_tension')->nullable();
            $table->float('antebrazo')->nullable();
            $table->float('torax_mesoexternal')->nullable();
            $table->float('cintura_minima')->nullable();
            $table->float('caderas_maxima')->nullable();
            $table->float('muslo_superior')->nullable();
            $table->float('muslo_medial')->nullable();
            $table->float('pantorrilla_maxima')->nullable();
            $table->timestamps();

            $table->foreign('id_cliente')->references('id')->on('clientes')->onDelete('cascade');
            $table->index('id_cliente');
        });
    }

    public function down()
    {
        Schema::dropIfExists('perimetros');
    }
};
