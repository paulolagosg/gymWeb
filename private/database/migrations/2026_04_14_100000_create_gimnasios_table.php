<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('gimnasios', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('nombre');
            $table->string('slug')->unique();
            $table->string('direccion')->nullable();
            $table->text('descripcion')->nullable();
            $table->string('telefono', 50)->nullable();
            $table->string('correo_electronico')->nullable();
            $table->string('sitio_web')->nullable();
            $table->string('instagram')->nullable();
            $table->string('facebook')->nullable();
            $table->string('tiktok')->nullable();
            $table->integer('estado')->default(1);
            $table->timestamps();
        });

        DB::table('gimnasios')->insert([
            'nombre' => 'Gimnasio Ampaya',
            'slug' => Str::slug('Gimnasio Ampaya'),
            'direccion' => 'Dirección referencial 123',
            'descripcion' => 'Gimnasio principal configurado como registro inicial para soporte multigimnasio.',
            'telefono' => '+56 9 1234 5678',
            'correo_electronico' => 'contacto@ampaya.cl',
            'sitio_web' => 'https://www.ampaya.cl',
            'instagram' => '@gimnasioampaya',
            'facebook' => 'gimnasioampaya',
            'tiktok' => '@gimnasioampaya',
            'estado' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gimnasios');
    }
};
