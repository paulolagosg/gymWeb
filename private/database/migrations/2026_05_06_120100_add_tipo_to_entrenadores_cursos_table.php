<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('entrenadores_cursos', 'tipo')) {
            Schema::table('entrenadores_cursos', function (Blueprint $table) {
                $table->string('tipo', 50)->nullable()->default('curso')->after('curso');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('entrenadores_cursos', 'tipo')) {
            Schema::table('entrenadores_cursos', function (Blueprint $table) {
                $table->dropColumn('tipo');
            });
        }
    }
};
