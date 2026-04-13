<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('ejercicios', function (Blueprint $table) {
            $table->unsignedBigInteger('id_tipo')->nullable()->after('id');
            $table->foreign('id_tipo')->references('id')->on('tipos_ejercicios')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::table('ejercicios', function (Blueprint $table) {
            $table->dropForeign(['id_tipo']);
            $table->dropColumn('id_tipo');
        });
    }
};
