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
            Schema::create('terms_and_conditions', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('id_gimnasio')->nullable();
                $table->string('titulo');
                $table->string('version', 50);
                $table->longText('contenido');
                $table->boolean('activo')->default(false);
                $table->boolean('obligatorio')->default(true);
                $table->timestamp('publicado_en')->nullable();
                $table->unsignedBigInteger('id_usuario_creador')->nullable();
                $table->unsignedBigInteger('id_usuario_actualizador')->nullable();
                $table->timestamps();

                $table->index(['id_gimnasio', 'activo']);
                $table->foreign('id_gimnasio')->references('id')->on('gimnasios')->nullOnDelete();
                $table->foreign('id_usuario_creador')->references('id')->on('users')->nullOnDelete();
                $table->foreign('id_usuario_actualizador')->references('id')->on('users')->nullOnDelete();
            });
        }

        if (! Schema::hasTable('terms_acceptances')) {
            Schema::create('terms_acceptances', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('id_terms_and_conditions');
                $table->unsignedBigInteger('id_user');
                $table->unsignedBigInteger('id_gimnasio')->nullable();
                $table->timestamp('accepted_at');
                $table->string('ip_address', 45)->nullable();
                $table->text('user_agent')->nullable();
                $table->timestamps();

                $table->unique(['id_terms_and_conditions', 'id_user'], 'terms_acceptances_terms_user_unique');
                $table->index(['id_user', 'accepted_at']);
                $table->foreign('id_terms_and_conditions', 'terms_acceptances_terms_fk')->references('id')->on('terms_and_conditions')->cascadeOnDelete();
                $table->foreign('id_user', 'terms_acceptances_user_fk')->references('id')->on('users')->cascadeOnDelete();
                $table->foreign('id_gimnasio', 'terms_acceptances_gym_fk')->references('id')->on('gimnasios')->nullOnDelete();
            });
        }

        $defaultTermsExists = DB::table('terms_and_conditions')
            ->whereNull('id_gimnasio')
            ->where('version', '1.0')
            ->exists();

        if (! $defaultTermsExists) {
            DB::table('terms_and_conditions')->insert([
                'id_gimnasio' => null,
                'titulo' => 'Términos y condiciones de uso de la app',
                'version' => '1.0',
                'contenido' => implode("\n\n", [
                    '1. El acceso a esta aplicación es personal e intransferible. Cada usuario es responsable del uso de sus credenciales y de mantener actualizados sus datos de contacto.',
                    '2. La información de entrenamientos, planes, pagos, evaluaciones y beneficios se entrega como apoyo a la gestión de la membresía y no reemplaza indicaciones médicas ni profesionales externas cuando correspondan.',
                    '3. El usuario se compromete a informar cualquier condición de salud relevante antes de ejecutar rutinas o seguir recomendaciones publicadas en la app.',
                    '4. El gimnasio puede actualizar estos términos para reflejar cambios operativos, legales o de seguridad. Cuando exista una nueva versión vigente, la app solicitará una nueva aceptación antes de continuar.',
                    '5. Los datos personales serán tratados para fines de administración interna, seguimiento deportivo, comunicaciones operativas y cumplimiento de obligaciones legales vinculadas a la prestación del servicio.',
                    '6. Si el usuario no está de acuerdo con estos términos, debe dejar de utilizar la aplicación y contactar al gimnasio para resolver su situación.',
                ]),
                'activo' => true,
                'obligatorio' => true,
                'publicado_en' => now(),
                'id_usuario_creador' => null,
                'id_usuario_actualizador' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('terms_acceptances');
        Schema::dropIfExists('terms_and_conditions');
    }
};
