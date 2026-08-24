<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('tipos_usuarios')->updateOrInsert(
            ['id' => 5],
            [
                'nombre' => 'Open Gym',
                'slug' => 'open-gym',
                'descripcion' => 'Cliente autoguiado que crea y registra sus propias rutinas.',
                'estado' => 1,
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );
    }

    public function down(): void
    {
        DB::table('tipos_usuarios')->where('id', 5)->delete();
    }
};