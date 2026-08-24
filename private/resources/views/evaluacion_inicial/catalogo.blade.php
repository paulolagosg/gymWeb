<x-admin-layout>
    <div class="py-4">
        <div class=" space-y-6">
            <div class="bg-white rounded-lg shadow-sm p-6">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Catálogo de evaluación inicial</h1>
                    <p class="text-sm text-gray-500 mt-1">Administra preguntas y opciones visibles en la app. Las preguntas de selección siempre conservan la opción Otro/a.</p>
                </div>
            </div>

            @if(session('success'))
            <div class="p-4 rounded-lg border border-green-300 bg-green-50 text-green-700">
                {{ session('success') }}
            </div>
            @endif

            @if($errors->any())
            <div class="p-4 rounded-lg border border-red-300 bg-red-50 text-red-700">
                <ul class="space-y-1">
                    @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
            @endif

            @foreach($secciones as $seccion)
            <section class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                    <h2 class="text-xl font-semibold text-gray-900">{{ $seccion->titulo }}</h2>
                    @if($seccion->descripcion)
                    <p class="text-sm text-gray-500 mt-1">{{ $seccion->descripcion }}</p>
                    @endif
                </div>

                <div class="p-6 space-y-6">
                    @php
                    $tiposTextoLibre = ['texto', 'textarea', 'numero', 'fecha', 'text'];
                    @endphp
                    @foreach($seccion->preguntas as $pregunta)
                    @php
                    $esTextoLibre = in_array($pregunta->tipo, $tiposTextoLibre, true);
                    $etiquetaTipo = match ($pregunta->tipo) {
                        'multiple' => 'Selección múltiple',
                        'unica', 'single' => 'Selección única',
                        'si_no' => 'Sí / No',
                        'escala' => 'Escala',
                        'numero' => 'Número',
                        'fecha' => 'Fecha',
                        default => 'Texto libre',
                    };
                    @endphp
                    <div class="rounded-lg border border-gray-200 p-5 space-y-4">
                        <form method="POST" action="{{ route('evaluacion-inicial.preguntas.update', $pregunta) }}" class="space-y-4">
                            @csrf
                            @method('PUT')
                            <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                                <div class="lg:col-span-2">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Pregunta</label>
                                    <input type="text" name="pregunta" value="{{ old('pregunta', $pregunta->pregunta) }}" class="w-full rounded-md border-gray-300 shadow-sm" required>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Tipo</label>
                                    <input type="text" value="{{ $etiquetaTipo }}" class="w-full rounded-md border-gray-200 bg-gray-100 text-gray-600" disabled>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 items-end">
                                <div class="lg:col-span-2">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Descripción</label>
                                    <textarea name="descripcion" rows="2" class="w-full rounded-md border-gray-300 shadow-sm">{{ old('descripcion', $pregunta->descripcion) }}</textarea>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Orden</label>
                                    <input type="number" name="orden" min="0" value="{{ old('orden', $pregunta->orden) }}" class="w-full rounded-md border-gray-300 shadow-sm" required>
                                </div>
                            </div>

                            <div class="flex flex-wrap items-center gap-6">
                                <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                                    <input type="hidden" name="es_requerida" value="0">
                                    <input type="checkbox" name="es_requerida" value="1" @checked($pregunta->es_requerida)>
                                    Obligatoria
                                </label>
                                <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                                    <input type="hidden" name="estado" value="0">
                                    <input type="checkbox" name="estado" value="1" @checked($pregunta->estado)>
                                    Activa
                                </label>
                                @if($pregunta->permite_otro)
                                <span class="inline-flex items-center rounded-full bg-gray-100 px-3 py-1 text-xs font-semibold text-gray-600">Incluye Otro/a</span>
                                @endif
                                <button type="submit" class="ml-auto rounded-md bg-gray-800 px-4 py-2 text-sm font-semibold text-white hover:bg-gray-700">Guardar pregunta</button>
                            </div>
                        </form>

                        @if(! $esTextoLibre)
                        <div class="space-y-3 border-t border-gray-200 pt-4">
                            <h3 class="text-sm font-semibold uppercase tracking-wide text-gray-500">Opciones</h3>

                            @foreach($pregunta->opciones as $opcion)
                            <form method="POST" action="{{ route('evaluacion-inicial.opciones.update', $opcion) }}" class="grid grid-cols-1 lg:grid-cols-5 gap-3 items-end">
                                @csrf
                                @method('PUT')
                                <div class="lg:col-span-3">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Etiqueta</label>
                                    <input type="text" name="etiqueta" value="{{ old('etiqueta', $opcion->etiqueta) }}" class="w-full rounded-md border-gray-300 shadow-sm {{ $opcion->es_otro ? 'bg-gray-100 text-gray-500' : '' }}" @disabled($opcion->es_otro)>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Orden</label>
                                    <input type="number" name="orden" min="0" value="{{ old('orden', $opcion->orden) }}" class="w-full rounded-md border-gray-300 shadow-sm" required>
                                </div>
                                <div class="flex items-center gap-3">
                                    <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                                        <input type="hidden" name="estado" value="0">
                                        <input type="checkbox" name="estado" value="1" @checked($opcion->estado)>
                                        Activa
                                    </label>
                                    @if($opcion->es_otro)
                                    <span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-700">Fija</span>
                                    @endif
                                    <button type="submit" class="rounded-md bg-slate-700 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-600">Guardar</button>
                                </div>
                            </form>
                            @endforeach

                            <form method="POST" action="{{ route('evaluacion-inicial.opciones.store', $pregunta) }}" class="grid grid-cols-1 lg:grid-cols-5 gap-3 items-end rounded-lg bg-gray-50 p-4 border border-dashed border-gray-300">
                                @csrf
                                <div class="lg:col-span-3">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Nueva opción</label>
                                    <input type="text" name="etiqueta" class="w-full rounded-md border-gray-300 shadow-sm" placeholder="Ej. Entrenamiento funcional" required>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Orden</label>
                                    <input type="number" name="orden" min="0" value="{{ $pregunta->opciones->max('orden') + 1 }}" class="w-full rounded-md border-gray-300 shadow-sm" required>
                                </div>
                                <div class="flex items-center gap-3">
                                    <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                                        <input type="hidden" name="estado" value="0">
                                        <input type="checkbox" name="estado" value="1" checked>
                                        Activa
                                    </label>
                                    <button type="submit" class="rounded-md bg-green-700 px-4 py-2 text-sm font-semibold text-white hover:bg-green-600">Agregar</button>
                                </div>
                            </form>
                        </div>
                        @endif
                    </div>
                    @endforeach
                </div>
            </section>
            @endforeach
        </div>
    </div>
</x-admin-layout>