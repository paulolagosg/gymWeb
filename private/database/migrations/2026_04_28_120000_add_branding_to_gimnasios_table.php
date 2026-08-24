<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gimnasios', function (Blueprint $table) {
            $table->string('color_primario', 7)->nullable()->after('correo_electronico');
            $table->string('color_secundario', 7)->nullable()->after('color_primario');
            $table->string('email_encabezado')->nullable()->after('color_secundario');
            $table->string('email_firma')->nullable()->after('email_encabezado');
            $table->text('email_pie')->nullable()->after('email_firma');
        });
    }

    public function down(): void
    {
        Schema::table('gimnasios', function (Blueprint $table) {
            $table->dropColumn([
                'color_primario',
                'color_secundario',
                'email_encabezado',
                'email_firma',
                'email_pie',
            ]);
        });
    }
};
