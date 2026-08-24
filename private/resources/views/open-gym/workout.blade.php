<x-admin-layout>
    <div class="py-4 grid gap-6">
        @if(session('success'))
        <div class="rounded-2xl border border-emerald-300 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700">
            {{ session('success') }}
        </div>
        @endif

        <section class="rounded-3xl border border-stone-200 bg-white p-6 shadow-sm">
            <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-stone-900">{{ $workout['nombre'] }}</h1>
                    <p class="mt-2 text-sm text-stone-500">{{ $workout['ejercicios_completados'] }} de {{ $workout['ejercicios_totales'] }} ejercicios completados.</p>
                </div>
                <div class="text-sm font-semibold text-violet-600">{{ $workout['progreso_porcentaje'] }}%</div>
            </div>
            <div class="mt-5 h-3 overflow-hidden rounded-full bg-stone-100">
                <div class="h-full rounded-full bg-gradient-to-r from-violet-500 to-emerald-400" style="width: {{ $workout['progreso_porcentaje'] }}%"></div>
            </div>
        </section>

        @foreach($workout['ejercicios'] as $exercise)
        <section class="rounded-3xl border border-stone-200 bg-white p-6 shadow-sm">
            <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                <div>
                    <h2 class="text-2xl font-bold text-stone-900">{{ $exercise['nombre'] }}</h2>
                    <p class="mt-2 text-sm text-stone-500">{{ $exercise['grupo_muscular'] }} · {{ $exercise['series_completadas'] }}/{{ $exercise['series_totales'] }} series</p>
                </div>
                <span class="rounded-full px-3 py-2 text-xs font-bold {{ $exercise['estado'] === 'completed' ? 'bg-emerald-50 text-emerald-700' : ($exercise['estado'] === 'in_progress' ? 'bg-amber-50 text-amber-700' : 'bg-stone-100 text-stone-600') }}">
                    {{ $exercise['estado'] === 'completed' ? 'Completado' : ($exercise['estado'] === 'in_progress' ? 'En curso' : 'Próximo') }}
                </span>
            </div>

            <div class="mt-5 grid gap-4">
                @foreach($exercise['series'] as $set)
                <form method="POST" action="{{ route('open-gym.workouts.sets.update', ['id' => $workout['id'], 'setId' => $set['id']]) }}" class="rounded-2xl border border-stone-200 bg-stone-50 p-4">
                    @csrf
                    @method('PUT')
                    <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                        <div>
                            <p class="text-sm font-semibold text-stone-900">Serie {{ $set['numero'] }}</p>
                            <p class="mt-1 text-xs text-stone-500">Objetivo: {{ $set['peso_objetivo'] ?? 0 }} kg · {{ $set['reps_objetivo'] ?? 0 }} reps</p>
                        </div>
                        @if($set['es_record_personal'])
                        <span class="rounded-full bg-pink-50 px-3 py-2 text-xs font-bold text-pink-600">PR</span>
                        @endif
                    </div>
                    <div class="mt-4 grid gap-3 md:grid-cols-3">
                        <input type="number" step="0.01" min="0" max="5000" name="peso_real" value="{{ $set['peso_real'] ?? $set['peso_objetivo'] }}" class="rounded-2xl border border-stone-300 bg-white px-4 py-3 text-sm text-stone-900 focus:border-violet-400 focus:outline-none" {{ $set['completado'] ? 'readonly' : '' }}>
                        <input type="number" min="0" max="500" name="reps_realizadas" value="{{ $set['reps_realizadas'] ?? $set['reps_objetivo'] }}" class="rounded-2xl border border-stone-300 bg-white px-4 py-3 text-sm text-stone-900 focus:border-violet-400 focus:outline-none" {{ $set['completado'] ? 'readonly' : '' }}>
                        <input type="number" min="0" max="3600" name="descanso_segundos" value="{{ $set['descanso_segundos'] ?? 60 }}" class="rounded-2xl border border-stone-300 bg-white px-4 py-3 text-sm text-stone-900 focus:border-violet-400 focus:outline-none" {{ $set['completado'] ? 'readonly' : '' }}>
                    </div>
                    @if(!$set['completado'])
                    <button type="submit" class="mt-4 inline-flex items-center rounded-2xl bg-emerald-500 px-4 py-3 text-sm font-semibold text-stone-950 transition hover:bg-emerald-400">
                        Marcar serie como completada
                    </button>
                    @else
                    <p class="mt-4 text-sm font-semibold text-emerald-700">Serie completada.</p>
                    @endif
                </form>
                @endforeach
            </div>
        </section>
        @endforeach

        @if($workout['estado'] !== 'completed')
        <form method="POST" action="{{ route('open-gym.workouts.finish', $workout['id']) }}" class="rounded-3xl border border-stone-200 bg-white p-6 shadow-sm">
            @csrf
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <h2 class="text-2xl font-bold text-stone-900">Terminar entrenamiento</h2>
                    <p class="mt-2 text-sm text-stone-500">Registra cómo te sentiste para guardar el resumen final.</p>
                </div>
                <div class="flex flex-wrap gap-3">
                    <select name="dificultad_percibida" class="rounded-2xl border border-stone-300 bg-white px-4 py-3 text-sm text-stone-900 focus:border-violet-400 focus:outline-none">
                        <option value="facil">Fácil</option>
                        <option value="normal" selected>Normal</option>
                        <option value="dificil">Difícil</option>
                        <option value="maximo">Máximo</option>
                    </select>
                    <button type="submit" class="inline-flex items-center rounded-2xl bg-violet-600 px-5 py-3 text-sm font-semibold text-white transition hover:bg-violet-500">
                        Guardar entrenamiento
                    </button>
                </div>
            </div>
        </form>
        @endif
    </div>
</x-admin-layout>