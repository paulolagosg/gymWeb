<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    // Migración de la base de datos
    public function up()
    {
        Schema::create('antropometria', function (Blueprint $table) {
            $table->id();
            $table->decimal('peso', 8, 2);
            $table->decimal('talla', 5, 2);
            $table->decimal('talla_sentado', 5, 2);
            $table->decimal('biacromial', 5, 2);
            $table->decimal('torax_transverso', 5, 2);
            $table->decimal('torax_anteriorposterior', 5, 2);
            $table->decimal('bi_iliocrestido', 5, 2);
            $table->decimal('humeral', 5, 2);
            $table->decimal('femoral', 5, 2);
            $table->decimal('cabeza', 5, 2);
            $table->decimal('brazo_relajado', 5, 2);
            $table->decimal('brazo_flexionado', 5, 2);
            $table->decimal('antebrazo', 5, 2);
            $table->decimal('torax_mesoesternal', 5, 2);
            $table->decimal('cintura_minima', 5, 2);
            $table->decimal('caderas_maxima', 5, 2);
            $table->decimal('muslo_superior', 5, 2);
            $table->decimal('muslo_medial_a', 5, 2);
            $table->decimal('pantorrilla_maxima', 5, 2);
            $table->decimal('triceps', 5, 2);
            $table->decimal('subescapular', 5, 2);
            $table->decimal('supraespinal', 5, 2);
            $table->decimal('abdominal', 5, 2);
            $table->decimal('muslo_medial', 5, 2);
            $table->decimal('pantorrilla', 5, 2);
            $table->timestamps();
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('antropometrias');
    }
};
