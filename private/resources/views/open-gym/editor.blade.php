<x-admin-layout>
    @php
    $isEdit = $mode === 'edit';
    $initialForm = [
    'nombre' => old('nombre', $routine['nombre'] ?? ''),
    'descripcion' => old('descripcion', $routine['descripcion'] ?? ''),
    'frecuencia_semanal' => old('frecuencia_semanal', $routine['frecuencia_semanal'] ?? 2),
    'duracion_estimada_minutos' => old('duracion_estimada_minutos', $routine['duracion_estimada_minutos'] ?? 45),
    'calorias_estimadas' => old('calorias_estimadas', $routine['calorias_estimadas'] ?? 320),
    ];
    @endphp

    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>

    <div class="py-4">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-3xl border border-stone-200">
            <div class="p-6 border-b border-stone-100 flex items-center justify-between gap-4">
                <div>
                    <h1 class="text-3xl font-bold text-stone-900">{{ $isEdit ? 'Editar rutina' : 'Nueva rutina' }}</h1>
                    <p class="mt-2 text-sm text-stone-500">Arrastra, reordena y guarda una rutina pensada para Open Gym.</p>
                </div>
                <a href="{{ route('open-gym.index') }}" class="inline-flex items-center rounded-2xl border border-stone-300 bg-white px-4 py-3 text-sm font-semibold text-stone-700 transition hover:border-stone-400 hover:text-stone-950">
                    Volver
                </a>
            </div>

            <div class="p-6">
                @if($errors->any())
                <div class="mb-5 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                    <ul class="list-disc pl-5">
                        @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
                @endif

                <form method="POST" action="{{ $submitRoute }}" id="routine-form" class="grid gap-6">
                    @csrf
                    @if($isEdit)
                    @method('PUT')
                    @endif

                    <div class="grid gap-4 lg:grid-cols-2">
                        <div class="rounded-3xl border border-stone-200 bg-stone-50 p-5">
                            <label class="block text-sm font-semibold text-stone-800">Nombre</label>
                            <input name="nombre" value="{{ $initialForm['nombre'] }}" class="mt-2 w-full rounded-2xl border border-stone-300 bg-white px-4 py-3 text-sm text-stone-900 focus:border-violet-400 focus:outline-none" required>

                            <label class="mt-5 block text-sm font-semibold text-stone-800">Descripción</label>
                            <textarea name="descripcion" rows="4" class="mt-2 w-full rounded-2xl border border-stone-300 bg-white px-4 py-3 text-sm text-stone-900 focus:border-violet-400 focus:outline-none">{{ $initialForm['descripcion'] }}</textarea>

                            <div class="mt-5 grid gap-4 md:grid-cols-3">
                                <div>
                                    <label class="block text-sm font-semibold text-stone-800">Frecuencia</label>
                                    <input type="number" name="frecuencia_semanal" value="{{ $initialForm['frecuencia_semanal'] }}" min="1" max="14" class="mt-2 w-full rounded-2xl border border-stone-300 bg-white px-4 py-3 text-sm text-stone-900 focus:border-violet-400 focus:outline-none">
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold text-stone-800">Duración</label>
                                    <input type="number" name="duracion_estimada_minutos" value="{{ $initialForm['duracion_estimada_minutos'] }}" min="10" max="300" class="mt-2 w-full rounded-2xl border border-stone-300 bg-white px-4 py-3 text-sm text-stone-900 focus:border-violet-400 focus:outline-none">
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold text-stone-800">Kcal</label>
                                    <input type="number" name="calorias_estimadas" value="{{ $initialForm['calorias_estimadas'] }}" min="0" max="10000" class="mt-2 w-full rounded-2xl border border-stone-300 bg-white px-4 py-3 text-sm text-stone-900 focus:border-violet-400 focus:outline-none">
                                </div>
                            </div>
                        </div>

                        <div class="rounded-3xl border border-stone-200 bg-stone-50 p-5">
                            <div class="flex items-center justify-between gap-3">
                                <h2 class="text-lg font-bold text-stone-900">Grupos musculares</h2>
                                <span class="text-xs font-semibold uppercase tracking-[0.24em] text-stone-400">Auto</span>
                            </div>
                            <div id="muscle-summary" class="mt-4 grid gap-3"></div>
                        </div>
                    </div>

                    <div class="rounded-3xl border border-stone-200 bg-stone-50 p-5">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <h2 class="text-lg font-bold text-stone-900">Ejercicios</h2>
                                <p class="mt-1 text-sm text-stone-500">Arrastra las tarjetas para ordenar la rutina.</p>
                            </div>
                            <div class="flex flex-wrap gap-2">
                                <button type="button" id="open-catalog" class="inline-flex items-center rounded-2xl bg-violet-600 px-4 py-3 text-sm font-semibold text-white transition hover:bg-violet-500">
                                    <i class="fa-solid fa-plus mr-2"></i>
                                    Agregar ejercicio
                                </button>
                                <button type="button" id="add-custom" class="inline-flex items-center rounded-2xl border border-stone-300 bg-white px-4 py-3 text-sm font-semibold text-stone-700 transition hover:border-stone-400 hover:text-stone-950">
                                    Personalizado
                                </button>
                            </div>
                        </div>

                        <div id="exercise-list" class="mt-5 grid gap-4"></div>
                    </div>

                    <div class="flex flex-wrap gap-3">
                        <button type="submit" class="inline-flex items-center rounded-2xl bg-emerald-500 px-5 py-3 text-sm font-semibold text-stone-950 transition hover:bg-emerald-400">
                            Guardar rutina
                        </button>
                        <a href="{{ route('open-gym.index') }}" class="inline-flex items-center rounded-2xl border border-stone-300 bg-white px-5 py-3 text-sm font-semibold text-stone-700 transition hover:border-stone-400 hover:text-stone-950">
                            Cancelar
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div id="catalog-modal" class="fixed inset-0 z-50 hidden bg-stone-950/60 p-4">
        <div class="mx-auto max-w-3xl rounded-3xl bg-white shadow-2xl">
            <div class="flex items-center justify-between border-b border-stone-100 px-6 py-4">
                <div>
                    <h2 class="text-xl font-bold text-stone-900">Agregar ejercicio</h2>
                    <p class="text-sm text-stone-500">Busca un ejercicio del catálogo y añádelo a la rutina.</p>
                </div>
                <button type="button" id="close-catalog" class="inline-flex h-10 w-10 items-center justify-center rounded-2xl border border-stone-200 text-stone-600 transition hover:bg-stone-100 hover:text-stone-950">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
            <div class="p-6">
                <input id="catalog-search" class="w-full rounded-2xl border border-stone-300 bg-white px-4 py-3 text-sm text-stone-900 focus:border-violet-400 focus:outline-none" placeholder="Buscar ejercicio...">
                <div id="catalog-results" class="mt-4 grid gap-3 max-h-[60vh] overflow-y-auto"></div>
            </div>
        </div>
    </div>

    <script>
        const catalog = @json($catalog);
        const initialExercises = @json($initialExercises);
        const exerciseList = document.getElementById('exercise-list');
        const muscleSummary = document.getElementById('muscle-summary');
        const catalogModal = document.getElementById('catalog-modal');
        const catalogResults = document.getElementById('catalog-results');
        const catalogSearch = document.getElementById('catalog-search');
        let exerciseState = (initialExercises || []).map((exercise, index) => ({
            key: `seed-${index}`,
            id_ejercicio: exercise.id_ejercicio ?? null,
            nombre: exercise.nombre ?? '',
            grupo_muscular: exercise.grupo_muscular ?? 'Full Body',
            series: String(exercise.series ?? 3),
            reps: String(exercise.reps ?? 10),
            descanso_segundos: String(exercise.descanso_segundos ?? 60),
            peso_objetivo: exercise.peso_objetivo ?? '',
            notas: exercise.notas ?? '',
        }));

        const escapeHtml = (value) => String(value ?? '')
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;');

        const buildSummary = () => {
            const totalSeries = Math.max(1, exerciseState.reduce((acc, exercise) => acc + Number(exercise.series || 0), 0));
            const grouped = new Map();
            exerciseState.forEach((exercise) => {
                const key = exercise.grupo_muscular || 'Full Body';
                grouped.set(key, (grouped.get(key) ?? 0) + Number(exercise.series || 0));
            });

            const summary = Array.from(grouped.entries())
                .map(([name, series]) => ({
                    name,
                    percentage: Math.round((series / totalSeries) * 100)
                }))
                .sort((left, right) => right.percentage - left.percentage);

            muscleSummary.innerHTML = summary.length ?
                summary.map((item) => `
                    <div class="grid grid-cols-[110px_minmax(0,1fr)_56px] items-center gap-3">
                        <span class="text-sm font-medium text-stone-800">${escapeHtml(item.name)}</span>
                        <div class="h-2 overflow-hidden rounded-full bg-stone-200">
                            <div class="h-full rounded-full bg-gradient-to-r from-emerald-400 to-violet-500" style="width: ${item.percentage}%"></div>
                        </div>
                        <strong class="text-xs font-bold text-violet-600">${item.percentage}%</strong>
                    </div>
                `).join('') :
                '<p class="text-sm text-stone-500">Agrega ejercicios para ver el reparto muscular.</p>';
        };

        const renderExercises = () => {
            exerciseList.innerHTML = exerciseState.length ?
                exerciseState.map((exercise, index) => `
                    <article class="rounded-3xl border border-stone-200 bg-white p-4 shadow-sm" data-key="${exercise.key}">
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex items-center gap-3">
                                <button type="button" class="drag-handle inline-flex h-10 w-10 items-center justify-center rounded-2xl border border-stone-200 text-stone-500">
                                    <i class="fa-solid fa-grip-lines"></i>
                                </button>
                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-[0.24em] text-stone-400">Ejercicio ${index + 1}</p>
                                    <input type="hidden" name="ejercicios[${index}][id_ejercicio]" value="${escapeHtml(exercise.id_ejercicio ?? '')}">
                                    <input type="text" name="ejercicios[${index}][nombre]" value="${escapeHtml(exercise.nombre)}" class="mt-2 w-full rounded-2xl border border-stone-300 bg-white px-4 py-3 text-sm text-stone-900 focus:border-violet-400 focus:outline-none" placeholder="Nombre del ejercicio">
                                    <p class="mt-2 text-xs font-semibold uppercase tracking-[0.24em] text-pink-500">${escapeHtml(exercise.grupo_muscular)}</p>
                                </div>
                            </div>
                            <button type="button" class="remove-exercise inline-flex h-10 w-10 items-center justify-center rounded-2xl border border-rose-200 bg-rose-50 text-rose-600 transition hover:bg-rose-100">
                                <i class="fa-regular fa-trash-can"></i>
                            </button>
                        </div>
                        <div class="mt-4 grid gap-3 md:grid-cols-4">
                            <input type="number" min="1" max="20" name="ejercicios[${index}][series]" value="${escapeHtml(exercise.series)}" class="rounded-2xl border border-stone-300 bg-white px-4 py-3 text-sm text-stone-900 focus:border-violet-400 focus:outline-none" placeholder="Series">
                            <input type="number" min="1" max="100" name="ejercicios[${index}][reps]" value="${escapeHtml(exercise.reps)}" class="rounded-2xl border border-stone-300 bg-white px-4 py-3 text-sm text-stone-900 focus:border-violet-400 focus:outline-none" placeholder="Reps">
                            <input type="number" min="0" max="3600" name="ejercicios[${index}][descanso_segundos]" value="${escapeHtml(exercise.descanso_segundos)}" class="rounded-2xl border border-stone-300 bg-white px-4 py-3 text-sm text-stone-900 focus:border-violet-400 focus:outline-none" placeholder="Descanso (seg)">
                            <input type="number" min="0" step="0.01" max="5000" name="ejercicios[${index}][peso_objetivo]" value="${escapeHtml(exercise.peso_objetivo)}" class="rounded-2xl border border-stone-300 bg-white px-4 py-3 text-sm text-stone-900 focus:border-violet-400 focus:outline-none" placeholder="Peso objetivo">
                        </div>
                        <textarea name="ejercicios[${index}][notas]" rows="3" class="mt-4 w-full rounded-2xl border border-stone-300 bg-white px-4 py-3 text-sm text-stone-900 focus:border-violet-400 focus:outline-none" placeholder="Notas rápidas">${escapeHtml(exercise.notas)}</textarea>
                    </article>
                `).join('') :
                '<div class="rounded-3xl border border-dashed border-stone-300 bg-stone-50 p-6 text-center text-sm text-stone-500">Agrega ejercicios desde el catálogo o crea uno personalizado.</div>';

            exerciseList.querySelectorAll('.remove-exercise').forEach((button) => {
                button.addEventListener('click', (event) => {
                    const item = event.currentTarget.closest('[data-key]');
                    exerciseState = exerciseState.filter((exercise) => exercise.key !== item.dataset.key);
                    renderExercises();
                });
            });

            exerciseList.querySelectorAll('input, textarea').forEach((field) => {
                field.addEventListener('input', (event) => {
                    const item = event.currentTarget.closest('[data-key]');
                    const current = exerciseState.find((exercise) => exercise.key === item.dataset.key);
                    if (!current) return;

                    if (field.name.endsWith('[nombre]')) current.nombre = field.value;
                    if (field.name.endsWith('[series]')) current.series = field.value;
                    if (field.name.endsWith('[reps]')) current.reps = field.value;
                    if (field.name.endsWith('[descanso_segundos]')) current.descanso_segundos = field.value;
                    if (field.name.endsWith('[peso_objetivo]')) current.peso_objetivo = field.value;
                    if (field.name.endsWith('[notas]')) current.notas = field.value;
                    buildSummary();
                });
            });

            buildSummary();
        };

        const renderCatalog = (term = '') => {
            const normalized = term.trim().toLowerCase();
            const filtered = catalog.filter((exercise) => !normalized || [exercise.nombre, exercise.descripcion ?? '', exercise.tipo ?? '', exercise.grupo_muscular ?? ''].join(' ').toLowerCase().includes(normalized));

            catalogResults.innerHTML = filtered.map((exercise) => `
                <button type="button" class="catalog-item rounded-3xl border border-stone-200 bg-stone-50 p-4 text-left transition hover:border-violet-300 hover:bg-violet-50" data-id="${exercise.id}">
                    <strong class="block text-base text-stone-900">${escapeHtml(exercise.nombre)}</strong>
                    <span class="mt-2 block text-sm text-stone-500">${escapeHtml(exercise.grupo_muscular ?? 'Full Body')} · ${escapeHtml(exercise.tipo ?? 'General')}</span>
                </button>
            `).join('');

            catalogResults.querySelectorAll('.catalog-item').forEach((button) => {
                button.addEventListener('click', () => {
                    const exercise = catalog.find((item) => String(item.id) === button.dataset.id);
                    if (!exercise) return;
                    exerciseState.push({
                        key: `catalog-${Date.now()}-${Math.random()}`,
                        id_ejercicio: exercise.id,
                        nombre: exercise.nombre,
                        grupo_muscular: exercise.grupo_muscular ?? 'Full Body',
                        series: '3',
                        reps: '10',
                        descanso_segundos: '60',
                        peso_objetivo: '',
                        notas: '',
                    });
                    renderExercises();
                    catalogModal.classList.add('hidden');
                });
            });
        };

        document.getElementById('open-catalog').addEventListener('click', () => {
            catalogModal.classList.remove('hidden');
            renderCatalog(catalogSearch.value);
        });

        document.getElementById('close-catalog').addEventListener('click', () => catalogModal.classList.add('hidden'));
        document.getElementById('add-custom').addEventListener('click', () => {
            exerciseState.push({
                key: `custom-${Date.now()}-${Math.random()}`,
                id_ejercicio: '',
                nombre: '',
                grupo_muscular: 'Full Body',
                series: '3',
                reps: '10',
                descanso_segundos: '60',
                peso_objetivo: '',
                notas: '',
            });
            renderExercises();
        });

        catalogSearch.addEventListener('input', () => renderCatalog(catalogSearch.value));

        new Sortable(exerciseList, {
            animation: 150,
            handle: '.drag-handle',
            onEnd: () => {
                const orderedKeys = Array.from(exerciseList.children).map((item) => item.dataset.key);
                exerciseState = orderedKeys.map((key) => exerciseState.find((exercise) => exercise.key === key)).filter(Boolean);
                renderExercises();
            },
        });

        renderExercises();
    </script>
</x-admin-layout>