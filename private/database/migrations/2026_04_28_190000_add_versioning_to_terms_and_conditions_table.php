<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('terms_and_conditions')) {
            return;
        }

        Schema::table('terms_and_conditions', function (Blueprint $table) {
            if (! Schema::hasColumn('terms_and_conditions', 'resumen_cambios')) {
                $table->text('resumen_cambios')->nullable()->after('contenido');
            }

            if (! Schema::hasColumn('terms_and_conditions', 'version_anterior_id')) {
                $table->unsignedBigInteger('version_anterior_id')->nullable()->after('publicado_en');
                $table->foreign('version_anterior_id', 'terms_version_anterior_fk')
                    ->references('id')
                    ->on('terms_and_conditions')
                    ->nullOnDelete();
            }
        });

        DB::table('terms_and_conditions')
            ->whereNull('resumen_cambios')
            ->update([
                'resumen_cambios' => 'Versión inicial publicada.',
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        if (! Schema::hasTable('terms_and_conditions')) {
            return;
        }

        Schema::table('terms_and_conditions', function (Blueprint $table) {
            if (Schema::hasColumn('terms_and_conditions', 'version_anterior_id')) {
                $table->dropForeign('terms_version_anterior_fk');
                $table->dropColumn('version_anterior_id');
            }

            if (Schema::hasColumn('terms_and_conditions', 'resumen_cambios')) {
                $table->dropColumn('resumen_cambios');
            }
        });
    }
};
