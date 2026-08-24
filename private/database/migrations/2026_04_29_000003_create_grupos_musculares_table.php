<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('grupos_musculares', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('nombre')->unique();
            $table->string('icono')->nullable();
            $table->string('color', 20)->nullable();
            $table->unsignedTinyInteger('estado')->default(1);
            $table->timestamps();
        });

        $timestamp = now();

        DB::table('grupos_musculares')->insert([
            ['id' => 1, 'nombre' => 'Pierna', 'icono' => 'walk-outline', 'color' => '#2ECC71', 'estado' => 1, 'created_at' => $timestamp, 'updated_at' => $timestamp],
            ['id' => 2, 'nombre' => 'Pecho', 'icono' => 'fitness-outline', 'color' => '#FF6584', 'estado' => 1, 'created_at' => $timestamp, 'updated_at' => $timestamp],
            ['id' => 3, 'nombre' => 'Espalda', 'icono' => 'body-outline', 'color' => '#3498DB', 'estado' => 1, 'created_at' => $timestamp, 'updated_at' => $timestamp],
            ['id' => 4, 'nombre' => 'Hombro', 'icono' => 'accessibility-outline', 'color' => '#9B59B6', 'estado' => 1, 'created_at' => $timestamp, 'updated_at' => $timestamp],
            ['id' => 5, 'nombre' => 'Brazos', 'icono' => 'barbell-outline', 'color' => '#F39C12', 'estado' => 1, 'created_at' => $timestamp, 'updated_at' => $timestamp],
            ['id' => 6, 'nombre' => 'Core', 'icono' => 'flame-outline', 'color' => '#E67E22', 'estado' => 1, 'created_at' => $timestamp, 'updated_at' => $timestamp],
            ['id' => 7, 'nombre' => 'Gluteos', 'icono' => 'pulse-outline', 'color' => '#16A085', 'estado' => 1, 'created_at' => $timestamp, 'updated_at' => $timestamp],
            ['id' => 8, 'nombre' => 'Cardio', 'icono' => 'heart-outline', 'color' => '#E74C3C', 'estado' => 1, 'created_at' => $timestamp, 'updated_at' => $timestamp],
            ['id' => 9, 'nombre' => 'Full Body', 'icono' => 'apps-outline', 'color' => '#6C63FF', 'estado' => 1, 'created_at' => $timestamp, 'updated_at' => $timestamp],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('grupos_musculares');
    }
};
