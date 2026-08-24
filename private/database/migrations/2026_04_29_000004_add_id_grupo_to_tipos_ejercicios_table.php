<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tipos_ejercicios', function (Blueprint $table) {
            $table->unsignedBigInteger('id_grupo')->nullable()->after('icono');
            $table->foreign('id_grupo')->references('id')->on('grupos_musculares')->nullOnDelete();
        });

        $groupIds = DB::table('grupos_musculares')->pluck('id', 'nombre');

        DB::table('tipos_ejercicios')
            ->select('id', 'nombre')
            ->orderBy('id')
            ->get()
            ->each(function ($tipo) use ($groupIds) {
                $nombre = mb_strtolower((string) $tipo->nombre);
                $groupName = null;

                if (str_contains($nombre, 'pierna')) {
                    $groupName = 'Pierna';
                } elseif (str_contains($nombre, 'pecho')) {
                    $groupName = 'Pecho';
                } elseif (str_contains($nombre, 'espalda')) {
                    $groupName = 'Espalda';
                } elseif (str_contains($nombre, 'hombro')) {
                    $groupName = 'Hombro';
                } elseif (str_contains($nombre, 'brazo') || str_contains($nombre, 'bice') || str_contains($nombre, 'trice')) {
                    $groupName = 'Brazos';
                } elseif (str_contains($nombre, 'core') || str_contains($nombre, 'abd')) {
                    $groupName = 'Core';
                } elseif (str_contains($nombre, 'glute')) {
                    $groupName = 'Gluteos';
                } elseif (str_contains($nombre, 'cardio')) {
                    $groupName = 'Cardio';
                } elseif (str_contains($nombre, 'full')) {
                    $groupName = 'Full Body';
                }

                $groupId = $groupName ? ($groupIds[$groupName] ?? null) : null;
                if ($groupId) {
                    DB::table('tipos_ejercicios')->where('id', $tipo->id)->update(['id_grupo' => $groupId]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('tipos_ejercicios', function (Blueprint $table) {
            $table->dropForeign(['id_grupo']);
            $table->dropColumn('id_grupo');
        });
    }
};
