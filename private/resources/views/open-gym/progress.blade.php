<x-admin-layout>
    @php
    $strengthSeries = $progress['fuerza']['serie'] ?? [];
    $maxStrength = collect($strengthSeries)->max('valor') ?: 1;
    $points = collect($strengthSeries)->values()->map(function ($point, $index) use ($strengthSeries, $maxStrength) {
    $x = 20 + ($index * 220 / max(count($strengthSeries) - 1, 1));
    $y = 110 - (($point['valor'] ?? 0) / $maxStrength) * 80;
    return $x . ',' . $y;
    })->implode(' ');
    $summary = session('open_gym_summary');
    @endphp

    <div class="py-4 grid gap-6">
        @if(session('success'))
        <div class="rounded-2xl border border-emerald-300 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700">
            {{ session('success') }}
        </div>
        @endif

        @if($summary)
        <div class="rounded-3xl border border-violet-200 bg-gradient-to-br from-violet-50 via-white to-pink-50 p-6">
            <h2 class="text-2xl font-bold text-stone-900">Resumen del entrenamiento</h2>
            <p class="mt-2 text-sm text-stone-500">Tiempo total: {{ $summary['tiempo_total_minutos'] ?? 0 }} min · Calorías: {{ $summary['calorias'] ?? 0 }}</p>
            <p class="mt-2 text-sm text-stone-500">Ejercicios completados: {{ $summary['ejercicios_completados'] ?? 0 }} · Récords: {{ $summary['records'] ?? 0 }}</p>
        </div>
        @endif

        <div class="grid gap-6 xl:grid-cols-[1.1fr_0.9fr]">
            <section class="rounded-3xl border border-stone-200 bg-white p-6 shadow-sm">
                <h1 class="text-2xl font-bold text-stone-900">Evolución de fuerza</h1>
                <p class="mt-2 text-sm text-stone-500">{{ $progress['fuerza']['ejercicio'] ?? 'Sin ejercicio dominante todavía' }}</p>

                <svg viewBox="0 0 260 130" class="mt-6 w-full overflow-visible">
                    <path d="M20 110 H240" stroke="#d6d3d1" stroke-width="2" fill="none"></path>
                    <polyline points="{{ $points }}" fill="none" stroke="#6C63FF" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"></polyline>
                </svg>

                <p class="mt-4 text-sm font-semibold text-violet-600">Δ {{ $progress['fuerza']['delta'] ?? 0 }} kg en el período analizado</p>
            </section>

            <section class="rounded-3xl border border-stone-200 bg-white p-6 shadow-sm">
                <h2 class="text-2xl font-bold text-stone-900">Grupos musculares</h2>
                <div class="mt-5 grid gap-4">
                    @forelse($progress['grupos_musculares'] ?? [] as $grupo)
                    <div class="grid grid-cols-[110px_minmax(0,1fr)_52px] items-center gap-3">
                        <span class="text-sm font-medium text-stone-800">{{ $grupo['nombre'] }}</span>
                        <div class="h-2 overflow-hidden rounded-full bg-stone-100">
                            <div class="h-full rounded-full" style="width: {{ $grupo['porcentaje'] }}%; background: {{ $grupo['color'] ?? '#2ECC71' }}"></div>
                        </div>
                        <strong class="text-xs font-bold text-stone-900">{{ $grupo['porcentaje'] }}%</strong>
                    </div>
                    @empty
                    <p class="text-sm text-stone-500">Aún no hay series completadas para calcular el reparto muscular.</p>
                    @endforelse
                </div>
            </section>
        </div>

        <section class="rounded-3xl border border-stone-200 bg-white p-6 shadow-sm">
            <h2 class="text-2xl font-bold text-stone-900">Entrenamientos recientes</h2>
            <div class="mt-5 grid gap-3">
                @forelse($progress['entrenamientos_recientes'] ?? [] as $entrenamiento)
                <article class="rounded-2xl border border-stone-200 bg-stone-50 p-4">
                    <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                        <div>
                            <h3 class="text-lg font-semibold text-stone-900">{{ $entrenamiento['nombre'] }}</h3>
                            <p class="mt-1 text-sm text-stone-500">{{ $entrenamiento['duracion_texto'] ?? 'Sin duración' }} · {{ $entrenamiento['calorias_estimadas'] ?? 0 }} kcal</p>
                        </div>
                        <span class="rounded-full bg-pink-50 px-3 py-2 text-xs font-bold text-pink-600">{{ $entrenamiento['records'] ?? 0 }} PR</span>
                    </div>
                </article>
                @empty
                <p class="text-sm text-stone-500">Todavía no hay entrenamientos suficientes para mostrar actividad reciente.</p>
                @endforelse
            </div>
        </section>
    </div>
</x-admin-layout>