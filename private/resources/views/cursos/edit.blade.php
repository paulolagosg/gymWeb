<x-admin-layout>
    @php($isAdminLike = $esAdminLike ?? false)
    <div class="py-4">
        <div class="">
            <div class="flex items-center justify-between mb-4 p-4 rounded-lg">
                <a href="{{ route('cursos.index') }}" class="hover:text-gray-500">
                    <i class="fas fa-circle-left fa-2x">&nbsp;</i>
                </a>
            </div>
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
                    <h2 class="text-xl font-bold mb-4">Editar Formación</h2>
                    <form action="{{ route('cursos.update', $curso->slug) }}" method="POST">
                        @if($isAdminLike)
                        <div class="mt-4">
                            <label for="id_entrenador" class="block text-sm font-medium text-gray-700">Entrenador</label>
                            <select id="id_entrenador" name="id_entrenador" required class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                @foreach($usuarios as $usuario)
                                <option value="{{ $usuario->id }}" {{ (string) old('id_entrenador', $curso->id_entrenador) === (string) $usuario->id ? 'selected' : '' }}>{{ $usuario->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        @endif
                        @csrf
                        @method('PUT')
                        <div class="mt-4">
                            <label for="curso" class="block text-sm font-medium text-gray-700">Curso</label>
                            <input id="curso" type="text" name="curso" value="{{ old('curso', $curso->curso) }}" required
                                class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" />
                        </div>
                        <div class="mt-4">
                            <label for="institucion" class="block text-sm font-medium text-gray-700">Institución</label>
                            <input id="institucion" type="text" name="institucion" value="{{ old('institucion', $curso->institucion) }}" required
                                class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" />
                        </div>
                        <div class="mt-4">
                            <label for="modalidad" class="block text-sm font-medium text-gray-700">Modalidad</label>
                            <select id="modalidad" name="modalidad" required
                                class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                <option value="1" {{ $curso->modalidad == 1 ? 'selected' : '' }}>Presencial</option>
                                <option value="2" {{ $curso->modalidad == 2 ? 'selected' : '' }}>Online</option>
                                <option value="3" {{ $curso->modalidad == 3 ? 'selected' : '' }}>Híbrido</option>
                            </select>
                        </div>
                        <div class="flex flex-row gap-4">
                            <div class="mt-4 flex-1">
                                <label for="fecha_inicio" class="block text-sm font-medium text-gray-700">Fecha Inicio</label>
                                <input id="fecha_inicio" type="date" name="fecha_inicio"
                                    value="{{ old('fecha_inicio', $curso->fecha_inicio)  }}"
                                    class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" />
                            </div>
                            <div class="mt-4 flex-1">
                                <label for="fecha_fin" class="block text-sm font-medium text-gray-700">Fecha Término</label>
                                <input id="fecha_fin" type="date" name="fecha_fin"
                                    value="{{ old('fecha_fin', $curso->fecha_fin ) }}"
                                    class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" />
                            </div>
                        </div>
                </div>
                <div class="mt-4 p-4">
                    <button type="submit" class="bg-green-600 hover:bg-green-800 text-white font-bold py-2 px-4 rounded">
                        Guardar Cambios
                    </button>
                    <a href="{{ route('cursos.index') }}" class="inline-block bg-red-500 hover:bg-red-800 text-white font-bold py-2 px-4 rounded ml-2">
                        Cancelar
                    </a>
                </div>
                </form>
            </div>
        </div>
    </div>
    </div>
</x-admin-layout>