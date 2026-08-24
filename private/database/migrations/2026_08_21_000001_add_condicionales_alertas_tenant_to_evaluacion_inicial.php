<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('evaluacion_inicial_secciones', function (Blueprint $table) {
            // Sin FK a `gimnasios`: el catálogo base usa id_gimnasio=0 como centinela
            // (fila que no existe en `gimnasios`), pensado para un futuro catálogo
            // personalizable por gimnasio. `codigo` sigue siendo unique() global por
            // ahora ya que solo existe el catálogo base (id_gimnasio=0).
            $table->unsignedInteger('id_gimnasio')->default(0)->after('orden');
        });

        Schema::table('evaluacion_inicial_preguntas', function (Blueprint $table) {
            $table->boolean('es_sensible')->default(false)->after('permite_otro');
            $table->foreignId('depende_pregunta_id')->nullable()->after('es_sensible')
                ->constrained('evaluacion_inicial_preguntas')->nullOnDelete();
            $table->foreignId('depende_opcion_id')->nullable()->after('depende_pregunta_id')
                ->constrained('evaluacion_inicial_opciones')->nullOnDelete();
            $table->unsignedInteger('id_gimnasio')->default(0)->after('depende_opcion_id');
        });

        Schema::table('evaluacion_inicial_opciones', function (Blueprint $table) {
            $table->boolean('genera_alerta')->default(false)->after('es_otro');
            $table->unsignedInteger('id_gimnasio')->default(0)->after('genera_alerta');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('evaluacion_inicial_preguntas', function (Blueprint $table) {
            $table->dropConstrainedForeignId('depende_opcion_id');
            $table->dropConstrainedForeignId('depende_pregunta_id');
            $table->dropColumn(['es_sensible', 'id_gimnasio']);
        });

        Schema::table('evaluacion_inicial_opciones', function (Blueprint $table) {
            $table->dropColumn(['genera_alerta', 'id_gimnasio']);
        });

        Schema::table('evaluacion_inicial_secciones', function (Blueprint $table) {
            $table->dropColumn('id_gimnasio');
        });
    }
};
