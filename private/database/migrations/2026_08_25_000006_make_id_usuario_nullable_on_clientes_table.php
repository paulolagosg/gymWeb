<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * `clientes.id_usuario` (entrenador asignado) se creó como NOT NULL
     * (2025_06_19_213009_add_id_plan_clientes.php), pero el resto del código ya lo
     * trata como opcional en todos lados: validación `nullable` al crear/editar
     * cliente desde la app, `whereNotNull` explícito antes de usarlo en reportes,
     * `$cliente->id_usuario ?: null` al leerlo. Nunca se topó en la práctica porque
     * los formularios existentes siempre mandaban algún entrenador — hasta el
     * importador CSV, donde un cliente sin entrenador que matchee es un caso válido
     * y esperado (no debe fallar la fila). Sin este cambio, `Clientes::create()`
     * revienta con "Column 'id_usuario' cannot be null" para cualquier fila sin
     * match de entrenador, aunque la previsualización la haya marcado como válida.
     */
    public function up(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->integer('id_usuario')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->integer('id_usuario')->nullable(false)->change();
        });
    }
};
