<x-admin-layout>
    <div class="py-4">
        <div class="">
            <div class="flex items-center justify-between mb-4 p-4 rounded-lg">
                <a href="{{ route('cursos.index') }}" class="hover:text-gray-500">
                    <i class="fas fa-circle-left fa-2x">&nbsp;</i>
                </a>
            </div>
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">

                <div class="p-6 text-gray-900">
                    <form action="{{ route('cursos.store') }}" method="POST">
                        @csrf
                        <h2 class="text-2xl font-bold mb-4">Agregar Formación</h2>
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
                        <div class="mt-4">
                            <label for="curso" class="block text-sm font-medium text-gray-700">Curso</label>
                            <input id="curso" type="text" name="curso" required
                                class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" />
                        </div>
                        <div class="mt-4">
                            <label for="institucion" class="block text-sm font-medium text-gray-700">Institución</label>
                            <input id="institucion" type="text" name="institucion" required
                                class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" />
                        </div>
                        <div class="mt-4">
                            <label for="modalidad" class="block text-sm font-medium text-gray-700">Modalidad</label>
                            <select id="modalidad" name="modalidad" required
                                class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                <option value="1">Presencial</option>
                                <option value="2">Online</option>
                                <option value="3">Híbrido</option>
                            </select>
                        </div>
                        <div class="flex flex-row gap-4">
                            <div class="mt-4 flex-1">
                                <label for="fecha_inicio" class="block text-sm font-medium text-gray-700">Fecha Inicio</label>
                                <input id="fecha_inicio" type="date" name="fecha_inicio"
                                    class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" />
                            </div>
                            <div class="mt-4 flex-1">
                                <label for="fecha_termino" class="block text-sm font-medium text-gray-700">Fecha Término</label>
                                <input id="fecha_fin" type="date" name="fecha_fin"
                                    class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" />
                            </div>
                        </div>
                        <div class="mt-4">
                            <button type="submit" class="bg-green-600 hover:bg-green-800 text-white font-bold py-2 px-4 rounded">
                                Guardar Cambios
                            </button>
                            <button type="button" onclick="location.href='{{ route('cursos.index') }}'" class="bg-red-500 hover:bg-red-800 text-white font-bold py-2 px-4 rounded ml-2">
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