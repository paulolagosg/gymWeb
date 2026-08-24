<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $defaultGymId = DB::table('gimnasios')->orderBy('id')->value('id');

        Schema::create('tiendas_aliadas', function (Blueprint $table) use ($defaultGymId) {
            $table->id();
            $table->foreignId('id_gimnasio')->default($defaultGymId)->constrained('gimnasios')->cascadeOnUpdate()->restrictOnDelete();
            $table->string('nombre_comercial');
            $table->string('rubro', 50)->default('otros');
            $table->string('correo_contacto')->nullable();
            $table->string('telefono', 50)->nullable();
            $table->string('direccion')->nullable();
            $table->string('instagram')->nullable();
            $table->string('facebook')->nullable();
            $table->string('web')->nullable();
            $table->string('whatsapp')->nullable();
            $table->string('logo_path')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->index(['id_gimnasio', 'activo'], 'tiendas_aliadas_gym_active_idx');
            $table->index(['id_gimnasio', 'rubro'], 'tiendas_aliadas_gym_rubro_idx');
        });

        Schema::create('beneficios', function (Blueprint $table) use ($defaultGymId) {
            $table->id();
            $table->foreignId('id_gimnasio')->default($defaultGymId)->constrained('gimnasios')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('id_tienda')->constrained('tiendas_aliadas')->cascadeOnDelete();
            $table->foreignId('id_usuario_creador')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('id_usuario_editor')->nullable()->constrained('users')->nullOnDelete();
            $table->string('titulo');
            $table->text('descripcion');
            $table->string('tipo', 40)->default('porcentaje');
            $table->decimal('valor', 10, 2)->nullable();
            $table->text('condicion')->nullable();
            $table->json('promocion_cantidad')->nullable();
            $table->date('fecha_inicio');
            $table->date('fecha_fin');
            $table->string('codigo_promocional', 100)->nullable();
            $table->text('terminos_condiciones')->nullable();
            $table->string('estado', 20)->default('pendiente');
            $table->timestamps();

            $table->index(['id_gimnasio', 'estado'], 'beneficios_gym_status_idx');
            $table->index(['id_tienda', 'estado'], 'beneficios_store_status_idx');
            $table->index(['fecha_inicio', 'fecha_fin'], 'beneficios_date_range_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('beneficios');
        Schema::dropIfExists('tiendas_aliadas');
    }
};
