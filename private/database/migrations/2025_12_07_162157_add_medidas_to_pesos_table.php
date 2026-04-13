<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('pesos', function (Blueprint $table) {
            $table->float('sumatoria_pliegues')->nullable()->after('peso');
            $table->float('grasa_region_superior')->nullable()->after('sumatoria_pliegues');
            $table->float('grasa_region_media')->nullable()->after('grasa_region_superior');
            $table->float('grasa_region_inferior')->nullable()->after('grasa_region_media');
        });
    }

    public function down()
    {
        Schema::table('pesos', function (Blueprint $table) {
            $table->dropColumn([
                'sumatoria_pliegues',
                'grasa_region_superior',
                'grasa_region_media',
                'grasa_region_inferior',
            ]);
        });
    }
};
