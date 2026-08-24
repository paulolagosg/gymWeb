<x-admin-layout>
    <div class="py-4">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-3xl border border-stone-200">
            <div class="p-6 border-b border-stone-100 flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-stone-900">Mis rutinas Open Gym</h1>
                    <p class="mt-2 text-sm text-stone-500">{{ $resumen['total_rutinas'] ?? count($rutinas) }} rutinas activas y {{ $resumen['entrenamientos_completados'] ?? 0 }} entrenamientos completados.</p>
                </div>
                <a href="{{ route('open-gym.create') }}" class="inline-flex items-center justify-center rounded-2xl bg-violet-600 px-5 py-3 text-sm font-semibold text-white shadow-lg shadow-violet-600/25 transition hover:bg-violet-500">
                    <i class="fa-solid fa-plus mr-2"></i>
                    Nueva rutina
                </a>
            </div>

            <div class="p-6 grid gap-6">
                @if(session('success'))
                <div class="rounded-2xl border border-emerald-300 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700">
                    {{ session('success') }}
                </div>
                @endif

                @if($activeWorkout)
                <section class="rounded-3xl border border-emerald-200 bg-gradient-to-r from-emerald-50 to-white p-5">
                    <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.3em] text-emerald-700">Entrenamiento activo</p>
                            <h2 class="mt-2 text-2xl font-bold text-stone-900">{{ $activeWorkout['nombre'] }}</h2>
                            <p class="mt-2 text-sm text-stone-500">{{ $activeWorkout['ejercicios_completados'] }} de {{ $activeWorkout['ejercicios_totales'] }} ejercicios completados.</p>
                        </div>
                        <a href="{{ route('open-gym.workouts.show', $activeWorkout['id']) }}" class="inline-flex items-center justify-center rounded-2xl bg-emerald-500 px-5 py-3 text-sm font-semibold text-stone-950 transition hover:bg-emerald-400">
                            Continuar entrenamiento
                        </a>
                    </div>
                </section>
                @endif

                <section>
                    <div class="flex items-center justify-between gap-3">
                        <h2 class="text-xl font-bold text-stone-900">Plantillas sugeridas</h2>
                        <span class="text-sm text-stone-400">Inspiración rápida</span>
                    </div>
                    <div class="mt-4 grid gap-4 md:grid-cols-3">
                        @foreach($plantillas as $plantilla)
                        <div class="rounded-3xl border border-violet-200 bg-gradient-to-br from-violet-50 via-white to-pink-50 p-5">
                            <h3 class="text-lg font-semibold text-stone-900">{{ $plantilla['nombre'] }}</h3>
                            <p class="mt-2 text-sm text-stone-500">{{ $plantilla['descripcion'] }}</p>
                            <p class="mt-3 text-xs font-semibold uppercase tracking-[0.24em] text-pink-500">{{ implode(' · ', $plantilla['grupos'] ?? []) }}</p>
                        </div>
                        @endforeach
                    </div>
                </section>

                <section class="grid gap-4">
                    @forelse($rutinas as $rutina)
                    <article class="rounded-3xl border border-stone-200 bg-stone-50 p-5">
                        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                            <div>
                                <h2 class="text-2xl font-bold text-stone-900">{{ $rutina['nombre'] }}</h2>
                                <p class="mt-2 text-sm text-stone-500">{{ $rutina['descripcion'] ?: ($rutina['total_ejercicios'] . ' ejercicios listos para usar.') }}</p>
                                <div class="mt-4 flex flex-wrap gap-2 text-xs font-medium text-stone-600">
                                    <span class="rounded-full bg-white px-3 py-2">{{ $rutina['total_ejercicios'] }} ejercicios</span>
                                    <span class="rounded-full bg-white px-3 py-2">{{ $rutina['duracion_estimada_minutos'] ?: 'Duración flexible' }} min</span>
                                    <span class="rounded-full bg-white px-3 py-2">{{ $rutina['calorias_estimadas'] ?: 'Kcal estimadas' }} kcal</span>
                                </div>
                            </div>
                            <div class="flex flex-wrap gap-2">
                                <form method="POST" action="{{ route('open-gym.workouts.start', $rutina['id']) }}">
                                    @csrf
                                    <button type="submit" class="inline-flex items-center rounded-2xl bg-emerald-500 px-4 py-3 text-sm font-semibold text-stone-950 transition hover:bg-emerald-400">
                                        <i class="fa-solid fa-play mr-2"></i>
                                        Iniciar
                                    </button>
                                </form>
                                <form method="POST" action="{{ route('open-gym.duplicate', $rutina['id']) }}">
                                    @csrf
                                    <button type="submit" class="inline-flex items-center rounded-2xl border border-stone-300 bg-white px-4 py-3 text-sm font-semibold text-stone-700 transition hover:border-stone-400 hover:text-stone-950">
                                        <i class="fa-regular fa-copy mr-2"></i>
                                        Clonar
                                    </button>
                                </form>
                                <a href="{{ route('open-gym.edit', $rutina['id']) }}" class="inline-flex items-center rounded-2xl border border-stone-300 bg-white px-4 py-3 text-sm font-semibold text-stone-700 transition hover:border-stone-400 hover:text-stone-950">
                                    <i class="fa-solid fa-pen mr-2"></i>
                                    Editar
                                </a>
                                <form method="POST" action="{{ route('open-gym.destroy', $rutina['id']) }}" onsubmit="return confirm('¿Eliminar esta rutina?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="inline-flex items-center rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-semibold text-rose-700 transition hover:bg-rose-100">
                                        <i class="fa-regular fa-trash-can mr-2"></i>
                                        Eliminar
                                    </button>
                                </form>
                            </div>
                        </div>

                        @if(!empty($rutina['grupos_musculares']))
                        <div class="mt-5 grid gap-3 md:grid-cols-2 xl:grid-cols-4">
                            @foreach($rutina['grupos_musculares'] as $grupo)
                            <div class="rounded-2xl bg-white p-4 shadow-sm">
                                <div class="flex items-center justify-between gap-3">
                                    <span class="text-sm font-semibold text-stone-900">{{ $grupo['nombre'] }}</span>
                                    <span class="text-xs font-bold text-violet-600">{{ $grupo['porcentaje'] }}%</span>
                                </div>
                                <div class="mt-3 h-2 overflow-hidden rounded-full bg-stone-100">
                                    <div class="h-full rounded-full" style="width: {{ $grupo['porcentaje'] }}%; background: {{ $grupo['color'] ?? '#6C63FF' }}"></div>
                                </div>
                            </div>
                            @endforeach
                        </div>
                        @endif
                    </article>
                    @empty
                    <div class="rounded-3xl border border-dashed border-stone-300 bg-stone-50 p-8 text-center text-stone-500">
                        Todavía no tienes rutinas Open Gym. Crea la primera para empezar a registrar tu progreso.
                    </div>
                    @endforelse
                </section>
            </div>
        </div>
    </div>
</x-admin-layout>