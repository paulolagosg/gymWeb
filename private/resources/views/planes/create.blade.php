<x-admin-layout>
    <div class="py-4">
        <div class="">
            <div class="flex items-center justify-between mb-4">
                <a href="{{ route('planes.index') }}" class="text-gray-700 hover:text-gray-500">
                    <i class="fas fa-circle-left fa-2x">&nbsp;</i>
                </a>
            </div>
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-4">
                <h2 class="text-2xl font-bold mb-4">Agregar Plan</h2>

                <div class="p-6 text-gray-900">
                    <form action="{{ route('planes.store') }}" method="POST">
                        @csrf
                        <div class="mb-4 sm:mb-0">
                            <label for="nombre" class="block text-gray-700">Nombre del Plan</label>
                            <input type="text" id="nombre" name="nombre" value="{{ old('nombre') }}" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" required>
                            @error('nombre')
                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                        <div class="mb-4 sm:mb-0">
                            <label for="descripcion" class="block text-gray-700">Descripción del Plan</label>
                            <textarea id="descripcion" name="descripcion" rows="4" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" required>{{ old('descripcion') }}</textarea>
                            @error('descripcion')
                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 md:gap-6">
                            <div class="mb-4 sm:mb-0">
                                <label for="valor" class="block text-gray-700">Valor del Plan</label>
                                <input type="number" id="valor" name="valor" value="{{ old('valor') }}" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" required>
                                @error('valor')
                                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                            <div class="mb-4 sm:mb-0">
                                <label for="porcentaje" class="block text-gray-700">Porcentaje para Entrenador</label>
                                <input type="number" id="porcentaje" name="porcentaje" value="{{ old('porcentaje') }}" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                @error('porcentaje')
                                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="mb-4 sm:mb-0">
                                <label for="estado" class="block text-gray-700">Estado</label>
                                <select id="estado" name="estado" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                    <option value="1" {{ old('estado') == 'activo' ? 'selected' : '' }}>Activo</option>
                                    <option value="0" {{ old('estado') == 'inactivo' ? 'selected' : '' }}>Inactivo</option>
                                </select>
                                @error('estado')
                                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                        <div class="mt-4">
                            <button type="submit" class="bg-green-600 hover:bg-green-800 text-white font-bold py-2 px-4 rounded">
                                Guardar Cambios
                            </button>
                            <button type="button" onclick="location.href='{{ route('planes.index') }}'" class="bg-red-500 hover:bg-red-800 text-white font-bold py-2 px-4 rounded ml-2">
                                Cancelar
                            </button>
                        </div>
                        <span class="hidden bg-green-600 bg-green-800 hover:bg-green-800 bg-red-600 bg-red-800 hover:bg-red-800"></span>
                        <span class="hidden bg-green-100 border-green-400 text-green-700"></span>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-admin-layout>