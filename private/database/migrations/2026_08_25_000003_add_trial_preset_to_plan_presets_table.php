<?php

use App\Models\Gimnasios;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Plan "Prueba (7 días)": el prospecto ve el producto completo (mismas features que
     * Pro) durante el trial, sin costo. La duración de 7 días y el bloqueo al vencer se
     * calculan en código (Gimnasios::PLAN_TIERS + adminGimnasiosPlanUpdate), no aquí —
     * esta migración solo siembra la composición de features y el precio.
     */
    public function up(): void
    {
        $features = [];
        foreach (Gimnasios::FEATURE_KEYS as $key) {
            $features[$key] = true;
        }

        DB::table('plan_presets')->insertOrIgnore([
            'plan' => 'trial',
            'features' => json_encode($features),
            'precio_mensual' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('plan_presets')->where('plan', 'trial')->delete();
    }
};
