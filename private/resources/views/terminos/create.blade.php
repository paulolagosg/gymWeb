<x-admin-layout>
    <div class="py-4">
        <div class="">
            <div class="flex items-center justify-between mb-4">
                <a href="{{ route('terminos.index') }}" class="text-gray-700 hover:text-gray-500">
                    <i class="fas fa-circle-left fa-2x">&nbsp;</i>
                </a>
            </div>
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-4">
                <h2 class="text-2xl font-bold mb-4">Publicar nueva versión</h2>

                <div class="p-6 text-gray-900">
                    <form action="{{ route('terminos.store') }}" method="POST">
                        @csrf
                        <div class="mb-4 sm:mb-0">
                            <label for="id_gimnasio" class="block text-gray-700">Ámbito</label>
                            <select id="id_gimnasio" name="id_gimnasio" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                <option value="">Global</option>
                                @foreach($gimnasios as $gimnasio)
                                <option value="{{ $gimnasio->id }}" {{ (string) old('id_gimnasio', $gimnasioSeleccionado) === (string) $gimnasio->id ? 'selected' : '' }}>
                                    {{ $gimnasio->nombre }}
                                </option>
                                @endforeach
                            </select>
                            @error('id_gimnasio')
                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 md:gap-6">
                            <div class="mb-4 sm:mb-0">
                                <label for="titulo" class="block text-gray-700">Título</label>
                                <input type="text" id="titulo" name="titulo" value="{{ old('titulo', 'Términos y condiciones de uso de la app') }}" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" required>
                                @error('titulo')
                                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                            <div class="mb-4 sm:mb-0">
                                <label for="version" class="block text-gray-700">Versión</label>
                                <input type="text" id="version" name="version" value="{{ old('version', $versionSugerida) }}" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" required>
                                @error('version')
                                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <div class="mb-4 sm:mb-0">
                            <label for="resumen_cambios" class="block text-gray-700">Resumen de cambios</label>
                            <textarea id="resumen_cambios" name="resumen_cambios" rows="3" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" placeholder="Describe qué cambia en esta versión.">{{ old('resumen_cambios') }}</textarea>
                            @error('resumen_cambios')
                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="mb-4 sm:mb-0">
                            <label for="contenido" class="block text-gray-700">Contenido</label>
                            <textarea id="contenido" name="contenido" rows="14" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" required>{{ old('contenido') }}</textarea>
                            @error('contenido')
                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="mb-4 sm:mb-0">
                            <label for="obligatorio" class="block text-gray-700">Aceptación obligatoria</label>
                            <select id="obligatorio" name="obligatorio" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                <option value="1" {{ old('obligatorio', '1') === '1' ? 'selected' : '' }}>Sí</option>
                                <option value="0" {{ old('obligatorio') === '0' ? 'selected' : '' }}>No</option>
                            </select>
                            @error('obligatorio')
                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="mt-4">
                            <button type="submit" class="bg-green-600 hover:bg-green-800 text-white font-bold py-2 px-4 rounded">
                                Publicar versión
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