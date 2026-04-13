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
        Schema::table('cuentas_corrientes', function (Blueprint $table) {
            $table->integer('descuento')->after('monto')->default(0);
            $table->integer('monto_pagar')->after('descuento')->default(0);
            $table->integer('id_estado_pago')->after('monto_pagar')->default(1);
            $table->integer('id_forma_pago')->after('id_estado_pago')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
