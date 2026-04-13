<x-admin-layout>
    <div class="py-4">
        <div class="">
            <div class="flex items-center justify-between mb-4">
                @if(auth()->user()->id_clasificacion == 3 or auth()->user()->id_tipo_usuario == 1)
                <a href="{{ route('entrenadores.index') }}" class="hover:text-gray-500">
                    <i class="fas fa-circle-left fa-2x">&nbsp;</i>
                </a>
                @else
                <a href="{{ route('portada') }}" class="hover:text-gray-500">
                    <i class="fas fa-circle-left fa-2x">&nbsp;</i>
                </a>
                @endif
            </div>
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-4 text-gray-900 items-center flex justify-between text-end">
                    @if(auth()->user()->id_clasificacion == 3)
                    <a href="{{ route('tareas.create') }}" class="bg-gray-700 hover:bg-gray-500 text-white font-bold py-2 px-4 rounded">
                        Agregar Tarea
                    </a>
                    @else
                    <h2 class="text-2xl font-bold p-4">Tareas</h2>
                    @endif
                </div>
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
                    <table id="tablaDatos" class="display ">
                        <thead>
                            <tr>
                                @if(auth()->user()->id_clasificacion == 3)
                                <th style="text-align: center;">Entrenador</th>
                                @endif
                                <th style="text-align: center;">Tarea</th>
                                <th style="text-align: center;">Fecha Límite</th>
                                <th style="text-align: center;">Estado</th>
                                <th style="text-align: center;">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($tareas as $tarea)
                            <tr>
                                @if(auth()->user()->id_clasificacion == 3)
                                <td>{{ $tarea->usuario->name }}</td>
                                @endif
                                <td>{{ $tarea->nombre }}</td>
                                <td>{{ $tarea->fecha_limite }}</td>
                                <td>{{ $tarea->completada }}</td>
                                <td>
                                    <button class="bg-gray-700 hover:bg-gray-500 text-white font-bold py-2 px-4 rounded" onclick="window.location.href='{{ route('tareas.show', $tarea->slug) }}'">
                                        Ver
                                    </button>
                                    @if(auth()->user()->id_clasificacion == 3)
                                    <button class="bg-gray-700 hover:bg-gray-500 text-white font-bold py-2 px-4 rounded" onclick="window.location.href='{{ route('tareas.edit', $tarea->slug) }}'">
                                        Editar
                                    </button>
                                    <form action="{{ route('tareas.destroy', $tarea->slug) }}" method="POST" style="display:inline;">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="bg-red-500 hover:bg-red-800 text-white font-bold py-2 px-4 rounded ml-2">
                                            Eliminar
                                        </button>
                                    </form>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <span class="hidden bg-green-600 bg-green-800 hover:bg-green-800 bg-red-600 bg-red-800 hover:bg-red-800"></span>
                    <span class="hidden bg-gray-800 bg-gray-500 bg-green-100 border-green-400 text-green-700"></span>
                </div>
            </div>
        </div>
    </div>
</x-admin-layout>