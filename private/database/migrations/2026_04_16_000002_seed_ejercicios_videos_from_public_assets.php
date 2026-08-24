<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    private const VIDEO_FILES = [
        'biceps-estiramiento.mp4',
        'chest-press-machine-1.mp4',
        'deficit-push-up-1.mp4',
        'dumbbell-curl.mp4',
        'gymleco-hack-squat-1.mp4',
        'hip-press-1.mp4',
        'pull-up-cluster-1.mp4',
        'quad-extention-1.mp4',
        'rdl-belt-squat-1.mp4',
        't-bar-row-machine-1.mp4',
        'triceps-push-down-1.gif',
    ];

    public function up(): void
    {
        $ejercicios = DB::table('ejercicios')
            ->whereNotNull('slug')
            ->pluck('id', 'slug')
            ->map(fn($id, $slug) => ['id' => (int) $id, 'slug' => (string) $slug])
            ->sortByDesc(fn(array $ejercicio) => strlen($ejercicio['slug']))
            ->values();

        if ($ejercicios->isEmpty()) {
            return;
        }

        $timestamp = now();
        $rows = [];

        foreach (self::VIDEO_FILES as $archivo) {
            $stem = Str::slug(pathinfo($archivo, PATHINFO_FILENAME));
            $ejercicio = $ejercicios->first(fn(array $item) => Str::startsWith($stem, $item['slug']));

            if (! $ejercicio) {
                continue;
            }

            $orden = 1;
            if (preg_match('/-(\d+)$/', $stem, $matches) === 1) {
                $orden = max(1, (int) $matches[1]);
            }

            $rows[] = [
                'id_ejercicio' => $ejercicio['id'],
                'archivo' => $archivo,
                'orden' => $orden,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ];
        }

        if (empty($rows)) {
            return;
        }

        DB::table('ejercicios_videos')->upsert(
            $rows,
            ['id_ejercicio', 'archivo'],
            ['orden', 'updated_at']
        );
    }

    public function down(): void
    {
        DB::table('ejercicios_videos')
            ->whereIn('archivo', self::VIDEO_FILES)
            ->delete();
    }
};
