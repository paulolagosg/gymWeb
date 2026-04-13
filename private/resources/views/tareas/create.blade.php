<x-admin-layout>
    <div class="py-4">
        <div class="">
            <div class="flex items-center justify-between mb-4">
                <a href="{{ route('tareas.index') }}" class="hover:text-gray-500">
                    <i class="fas fa-circle-left fa-2x">&nbsp;</i>
                </a>
            </div>
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <h2 class="text-2xl font-bold p-4">Agregar Tarea</h2>
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

                    <form method="POST" action="{{ route('tareas.store') }}" class="mb-4">
                        @csrf
                        <div class="mt-4">
                            <label for="id_usuario" class="block text-sm font-medium text-gray-700">Entrenador</label>
                            <select id="id_usuario" name="id_usuario" required class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                <option value="">Seleccionar usuario</option>
                                @foreach ($usuarios as $usuario)
                                <option value="{{ $usuario->id }}">{{ $usuario->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mt-4">
                            <label for="nombre" class="block text-sm font-medium text-gray-700">Nombre de la Tarea</label>
                            <input id="nombre" type="text" name="nombre" required
                                class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" />
                        </div>
                        <div class="mt-4">
                            <label for="descripcion" class="block text-sm font-medium text-gray-700">Descripción</label>
                            <textarea id="descripcion" name="descripcion" rows="3" required
                                class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"></textarea>
                        </div>
                        <div class="flex flex-row gap-4">
                            <div class="mt-4 flex-1">
                                <label for="fecha_limite" class="block text-sm font-medium text-gray-700">Fecha Límite</label>
                                <input id="fecha_limite" type="date" name="fecha_limite"
                                    class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" />
                            </div>
                            <div class="mt-4 flex-1">
                                <label for="completada" class="block text-sm font-medium text-gray-700">Completada</label>
                                <select id="completada" name="completada" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                    <option value="0">No</option>
                                    <option value="1">Sí</option>
                                </select>
                            </div>
                        </div>
                        <div class="mt-4">
                            <button type="submit" class="bg-green-600 hover:bg-green-800 text-white font-bold py-2 px-4 rounded">
                                Guardar Cambios
                            </button>
                            <button type="button" onclick="location.href='{{ route('tareas.index') }}'" class="bg-red-500 hover:bg-red-800 text-white font-bold py-2 px-4 rounded ml-2">
                                Cancelar
                            </button>
                        </div>

                    </form>
                </div>
            </div>
        </div>
    </div>
</x-admin-layout>