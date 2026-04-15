<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $defaultGymId = DB::table('gimnasios')->where('slug', 'gimnasio-ampaya')->value('id')
            ?? DB::table('gimnasios')->orderBy('id')->value('id');

        if (Schema::hasTable('clientes') && !Schema::hasColumn('clientes', 'id_gimnasio')) {
            Schema::table('clientes', function (Blueprint $table) use ($defaultGymId) {
                $table->unsignedBigInteger('id_gimnasio')->default($defaultGymId);
                $table->foreign('id_gimnasio')->references('id')->on('gimnasios')->cascadeOnUpdate()->restrictOnDelete();
            });
        }

        if (Schema::hasTable('agendas') && !Schema::hasColumn('agendas', 'id_gimnasio')) {
            Schema::table('agendas', function (Blueprint $table) use ($defaultGymId) {
                $table->unsignedBigInteger('id_gimnasio')->default($defaultGymId);
                $table->foreign('id_gimnasio')->references('id')->on('gimnasios')->cascadeOnUpdate()->restrictOnDelete();
            });
        }

        if (Schema::hasTable('movimientos_financieros') && !Schema::hasColumn('movimientos_financieros', 'id_gimnasio')) {
            Schema::table('movimientos_financieros', function (Blueprint $table) use ($defaultGymId) {
                $table->unsignedBigInteger('id_gimnasio')->default($defaultGymId);
                $table->foreign('id_gimnasio')->references('id')->on('gimnasios')->cascadeOnUpdate()->restrictOnDelete();
            });
        }

        if (Schema::hasTable('planes') && !Schema::hasColumn('planes', 'id_gimnasio')) {
            Schema::table('planes', function (Blueprint $table) use ($defaultGymId) {
                $table->unsignedBigInteger('id_gimnasio')->default($defaultGymId);
                $table->foreign('id_gimnasio')->references('id')->on('gimnasios')->cascadeOnUpdate()->restrictOnDelete();
            });
        }

        if (Schema::hasTable('users') && !Schema::hasColumn('users', 'id_gimnasio')) {
            Schema::table('users', function (Blueprint $table) use ($defaultGymId) {
                $table->unsignedBigInteger('id_gimnasio')->default($defaultGymId);
                $table->foreign('id_gimnasio')->references('id')->on('gimnasios')->cascadeOnUpdate()->restrictOnDelete();
            });
        }

        DB::table('clientes')->whereNull('id_gimnasio')->update(['id_gimnasio' => $defaultGymId]);
        DB::table('agendas')->whereNull('id_gimnasio')->update(['id_gimnasio' => $defaultGymId]);
        DB::table('movimientos_financieros')->whereNull('id_gimnasio')->update(['id_gimnasio' => $defaultGymId]);
        DB::table('planes')->whereNull('id_gimnasio')->update(['id_gimnasio' => $defaultGymId]);
        DB::table('users')->whereNull('id_gimnasio')->update(['id_gimnasio' => $defaultGymId]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('clientes') && Schema::hasColumn('clientes', 'id_gimnasio')) {
            Schema::table('clientes', function (Blueprint $table) {
                $table->dropForeign(['id_gimnasio']);
                $table->dropColumn('id_gimnasio');
            });
        }

        if (Schema::hasTable('agendas') && Schema::hasColumn('agendas', 'id_gimnasio')) {
            Schema::table('agendas', function (Blueprint $table) {
                $table->dropForeign(['id_gimnasio']);
                $table->dropColumn('id_gimnasio');
            });
        }

        if (Schema::hasTable('movimientos_financieros') && Schema::hasColumn('movimientos_financieros', 'id_gimnasio')) {
            Schema::table('movimientos_financieros', function (Blueprint $table) {
                $table->dropForeign(['id_gimnasio']);
                $table->dropColumn('id_gimnasio');
            });
        }

        if (Schema::hasTable('planes') && Schema::hasColumn('planes', 'id_gimnasio')) {
            Schema::table('planes', function (Blueprint $table) {
                $table->dropForeign(['id_gimnasio']);
                $table->dropColumn('id_gimnasio');
            });
        }

        if (Schema::hasTable('users') && Schema::hasColumn('users', 'id_gimnasio')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropForeign(['id_gimnasio']);
                $table->dropColumn('id_gimnasio');
            });
        }
    }
};
