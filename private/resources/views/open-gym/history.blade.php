<x-admin-layout>
    <div class="py-4 grid gap-6">
        <div class="rounded-3xl border border-stone-200 bg-white p-6 shadow-sm">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-stone-900">Historial de entrenamientos</h1>
                    <p class="mt-2 text-sm text-stone-500">Revisa tus sesiones pasadas y cómo rindió cada jornada.</p>
                </div>
                <form method="GET" action="{{ route('open-gym.history') }}" class="flex flex-wrap gap-3">
                    <input type="month" name="mes" value="{{ $selectedMonth }}" class="rounded-2xl border border-stone-300 bg-white px-4 py-3 text-sm text-stone-900 focus:border-violet-400 focus:outline-none">
                    <button type="submit" class="inline-flex items-center rounded-2xl bg-violet-600 px-4 py-3 text-sm font-semibold text-white transition hover:bg-violet-500">
                        Filtrar
                    </button>
                </form>
            </div>
        </div>

        @forelse($history as $day)
        <section class="rounded-3xl border border-stone-200 bg-white p-6 shadow-sm">
            <h2 class="text-xl font-bold text-stone-900">{{ $day['titulo_fecha'] }}</h2>
            <div class="mt-5 grid gap-3">
                @foreach($day['entrenamientos'] as $workout)
                <article class="rounded-2xl border border-stone-200 bg-stone-50 p-4">
                    <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                        <div>
                            <h3 class="text-lg font-semibold text-stone-900">{{ $workout['nombre'] }}</h3>
                            <p class="mt-1 text-sm text-stone-500">{{ $workout['duracion_texto'] ?? 'Sin duración' }} · {{ $workout['calorias_estimadas'] ?? 0 }} kcal</p>
                            <p class="mt-1 text-sm text-stone-500">Dificultad: {{ $workout['dificultad_percibida'] ?? 'sin registrar' }}</p>
                        </div>
                        <span class="rounded-full bg-pink-50 px-3 py-2 text-xs font-bold text-pink-600">{{ $workout['records'] ?? 0 }} PR</span>
                    </div>
                </article>
                @endforeach
            </div>
        </section>
        @empty
        <div class="rounded-3xl border border-dashed border-stone-300 bg-stone-50 p-8 text-center text-stone-500">
            Aún no hay entrenamientos completados para este período.
        </div>
        @endforelse
    </div>
</x-admin-layout>