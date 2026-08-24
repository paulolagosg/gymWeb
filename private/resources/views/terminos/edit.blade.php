<x-admin-layout>
    <div class="py-4">
        <div class="">
            <div class="flex items-center justify-between mb-4">
                <a href="{{ route('terminos.index') }}" class="text-gray-700 hover:text-gray-500">
                    <i class="fas fa-circle-left fa-2x">&nbsp;</i>
                </a>
            </div>
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-4">
                <h2 class="text-2xl font-bold mb-4">Crear nueva versión a partir de {{ $termino->version }}</h2>

                <div class="mb-4 rounded border border-amber-300 bg-amber-50 p-4 text-sm text-amber-800">
                    Esta pantalla no edita la versión actual en sitio. Al guardar, se publicará una nueva versión activa y la versión {{ $termino->version }} quedará en el historial como inactiva.
                </div>

                <div class="p-6 text-gray-900">
                    <form action="{{ route('terminos.update', $termino->id) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="mb-4 sm:mb-0">
                            <label class="block text-gray-700">Ámbito</label>
                            <input type="text" value="{{ $termino->gimnasio?->nombre ?? 'Global' }}" class="mt-1 block w-full border border-gray-200 bg-gray-50 rounded-md shadow-sm py-2 px-3 sm:text-sm" disabled>
                            <input type="hidden" name="id_gimnasio" value="{{ old('id_gimnasio', $termino->id_gimnasio) }}">
                            @error('id_gimnasio')
                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 md:gap-6">
                            <div class="mb-4 sm:mb-0">
                                <label for="titulo" class="block text-gray-700">Título</label>
                                <input type="text" id="titulo" name="titulo" value="{{ old('titulo', $termino->titulo) }}" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" required>
                                @error('titulo')
                                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                            <div class="mb-4 sm:mb-0">
                                <label for="version" class="block text-gray-700">Nueva versión</label>
                                <input type="text" id="version" name="version" value="{{ old('version', $versionSugerida) }}" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" required>
                                @error('version')
                                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <div class="mb-4 sm:mb-0">
                            <label for="resumen_cambios" class="block text-gray-700">Resumen de cambios</label>
                            <textarea id="resumen_cambios" name="resumen_cambios" rows="3" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" placeholder="Describe qué cambia respecto de la versión {{ $termino->version }}.">{{ old('resumen_cambios') }}</textarea>
                            @error('resumen_cambios')
                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="mb-4 sm:mb-0">
                            <label for="contenido" class="block text-gray-700">Contenido</label>
                            <textarea id="contenido" name="contenido" rows="14" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" required>{{ old('contenido', $termino->contenido) }}</textarea>
                            @error('contenido')
                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="mb-4 sm:mb-0">
                            <label for="obligatorio" class="block text-gray-700">Aceptación obligatoria</label>
                            <select id="obligatorio" name="obligatorio" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                <option value="1" {{ (string) old('obligatorio', $termino->obligatorio ? '1' : '0') === '1' ? 'selected' : '' }}>Sí</option>
                                <option value="0" {{ (string) old('obligatorio', $termino->obligatorio ? '1' : '0') === '0' ? 'selected' : '' }}>No</option>
                            </select>
                            @error('obligatorio')
                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="mt-4">
                            <button type="submit" class="bg-green-600 hover:bg-green-800 text-white font-bold py-2 px-4 rounded">
                                Publicar nueva versión
                            </button>
                            <a href="{{ route('terminos.index') }}" class="inline-block bg-red-500 hover:bg-red-800 text-white font-bold py-2 px-4 rounded ml-2">
                                Cancelar
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-admin-layout>