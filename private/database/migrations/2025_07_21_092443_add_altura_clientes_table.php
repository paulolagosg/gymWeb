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
        Schema::table('clientes', function (Blueprint $table) {
            $table->decimal('altura')->after('id_genero')->nullable()->default(null)->comment('Altura del cliente en metros');
            $table->date('fecha_baja')->after('altura')->nullable()->default(null)->comment('Fecha de baja del cliente');
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
