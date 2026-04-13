<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('pagos_entrenadores', function (Blueprint $table) {
            $table->bigInteger('bono')->default(0)->after('valor_duo');
        });
    }

    public function down()
    {
        Schema::table('pagos_entrenadores', function (Blueprint $table) {
            $table->dropColumn('bono');
        });
    }
};
