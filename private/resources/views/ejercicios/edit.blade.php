<x-admin-layout>
    <div class="py-4">
        <div class="">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 overflow-x-auto w-full">
                    @if(session('success'))
                    <div class="mx-4 my-2 p-3 bg-green-100 border border-green-400 text-green-700 rounded">
                        {{ session('success') }}
                    </div>
                    @endif

                    @if($errors->any())
                    <div class="mx-4 my-2 p-3 bg-red-100 border border-red-400 text-red-700 rounded">
                        <ul class="mb-0">
                            @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                    @endif
                    <h2 class="text-xl font-bold mb-4">Editar Ejercicio</h2>
                    <form action="{{ route('ejercicios.update', $ejercicio->slug) }}" method="POST" class="space-y-4">
                        @csrf
                        @method('PUT')
                        <div class="grid  md:gap-6">
                            <div class="mb-4 sm:mb-0">
                                <label for="nombre" class="block text-sm font-medium text-gray-700">Nombre del Ejercicio</label>
                                <input type="text" name="nombre" id="nombre" value="{{ old('nombre', $ejercicio->nombre) }}" required class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                            </div>
                            <div class="mb-4 sm:mb-0">
                                <label for="descripcion" class="block text-sm font-medium text-gray-700">Descripción</label>
                                <textarea name="descripcion" id="descripcion" rows="3" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">{{ old('descripcion', $ejercicio->descripcion) }}</textarea>
                            </div>
                            <div class="mb-4">
                                <label for="id_tipo" class="block text-gray-700">Tipo Ejercicio</label>
                                <select required id="id_tipo" name="id_tipo" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                    <option value="">Seleccione</option>
                                    @foreach($tipos as $tipo)
                                    <option value="{{ $tipo->id }}" {{ old('id_tipo', $ejercicio->id_tipo) == $tipo->id ? 'selected' : '' }}>{{ $tipo->nombre }}</option>
                                    @endforeach
                                </select>
                                @error('id_tipo')
                                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                            <div class="mb-4 sm:mb-0">
                                <label for="id_plan" class="block text-sm font-medium text-gray-700">Estado</label>
                                <select name="estado" id="estado"
                                    class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                    <option value="1" {{ old('estado') == '1' ? 'selected' : '' }} {{ $ejercicio->estado == '1' ? 'selected' : '' }}>Activo</option>
                                    <option value="0" {{ old('estado') == '0' ? 'selected' : '' }} {{ $ejercicio->estado == '0' ? 'selected' : '' }}>Inactivo</option>
                                </select>
                            </div>
                        </div>
                        <div class="mt-4">
                            <button type="submit" class="bg-green-600 hover:bg-green-800 text-white font-bold py-2 px-4 rounded">
                                Guardar Cambios
                            </button>
                            <button type="button" onclick="location.href='{{ route('ejercicios.index') }}'" class="bg-red-500 hover:bg-red-800 text-white font-bold py-2 px-4 rounded ml-2">
                                Cancelar
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-admin-layout>