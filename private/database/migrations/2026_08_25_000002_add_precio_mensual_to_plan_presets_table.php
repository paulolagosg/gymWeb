<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('plan_presets', 'precio_mensual')) {
            Schema::table('plan_presets', function (Blueprint $table) {
                $table->unsignedBigInteger('precio_mensual')->default(0)->after('features');
            });
        }
    }

    public function down(): void
    {
        Schema::table('plan_presets', function (Blueprint $table) {
            $table->dropColumn('precio_mensual');
        });
    }
};
