<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Los ejercicios personalizados de Open Gym (sin `id_ejercicio` de catálogo, como
     * los de las plantillas sugeridas) nunca guardaban el grupo muscular que el
     * cliente elige/hereda de la plantilla — al iniciar el entrenamiento, el backend
     * solo resolvía el grupo desde el catálogo (`id_ejercicio -> tipo -> grupoMuscular`)
     * y todo ejercicio sin ese vínculo quedaba clasificado como "Full Body". Esta
     * columna guarda ese texto libre como respaldo — no crea ni referencia filas
     * nuevas en el catálogo `ejercicios` (el que usan los gimnasios para clientes
     * presenciales), solo se usa para resolver el nombre ya existente en el catálogo
     * de `grupos_musculares` (una taxonomía genérica de 9 categorías, no de ejercicios).
     */
    public function up(): void
    {
        Schema::table('open_gym_rutina_ejercicios', function (Blueprint $table) {
            if (! Schema::hasColumn('open_gym_rutina_ejercicios', 'grupo_muscular')) {
                $table->string('grupo_muscular', 100)->nullable()->after('nombre_personalizado');
            }
        });
    }

    public function down(): void
    {
        Schema::table('open_gym_rutina_ejercicios', function (Blueprint $table) {
            $table->dropColumn('grupo_muscular');
        });
    }
};
