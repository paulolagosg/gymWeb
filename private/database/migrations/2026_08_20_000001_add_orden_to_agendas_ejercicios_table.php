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
        Schema::table('agendas_ejercicios', function (Blueprint $table) {
            $table->unsignedInteger('orden')->default(0)->after('id_ejercicio');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('agendas_ejercicios', function (Blueprint $table) {
            $table->dropColumn('orden');
        });
    }
};
