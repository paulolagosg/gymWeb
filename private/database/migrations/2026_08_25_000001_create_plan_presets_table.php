<?php

use App\Models\Gimnasios;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Composición editable de los planes comerciales (Starter/Estándar/Pro), antes
     * hardcodeada en Gimnasios::featurePresetForPlan(). Se siembra con los mismos
     * valores que tenía esa función, para que migrar no cambie el comportamiento de
     * ningún gimnasio ya asignado a un plan.
     */
    public function up(): void
    {
        if (! Schema::hasTable('plan_presets')) {
            Schema::create('plan_presets', function (Blueprint $table) {
                $table->id();
                $table->string('plan', 20)->unique();
                $table->json('features');
                $table->timestamps();
            });
        }

        $defaults = [
            'starter' => [],
            'estandar' => ['beneficios', 'gamificacion', 'compartir_progreso'],
            'pro' => Gimnasios::FEATURE_KEYS,
        ];

        foreach ($defaults as $plan => $activas) {
            $features = [];
            foreach (Gimnasios::FEATURE_KEYS as $key) {
                $features[$key] = in_array($key, $activas, true);
            }

            DB::table('plan_presets')->insertOrIgnore([
                'plan' => $plan,
                'features' => json_encode($features),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('plan_presets');
    }
};
