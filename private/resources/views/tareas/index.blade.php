<x-admin-layout>
    @php($isAdminLike = in_array((int) auth()->user()->id_tipo_usuario, [1, 10], true) || (int) auth()->user()->id_clasificacion === 3)
    @php($isSuperAdmin = (int) auth()->user()->id_tipo_usuario === 10)
    <div class="py-4">
        <div class="">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                @if($isAdminLike)
                <div class="p-4 text-gray-900 flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                    <a href="{{ route('tareas.create', $isSuperAdmin && !empty($gimnasioSeleccionado) ? ['id_gimnasio' => $gimnasioSeleccionado] : []) }}" class="bg-gray-700 hover:bg-gray-500 text-white font-bold py-2 px-4 rounded">
                        Agregar Tarea
                    </a>

                    @if($isSuperAdmin && isset($gimnasios))
                    <form method="GET" class="flex items-center gap-2">
                        <label for="id_gimnasio" class="text-sm font-semibold text-gray-700">Filtrar por gimnasio:</label>
                        <select name="id_gimnasio" id="id_gimnasio" class="border rounded px-3 py-2" onchange="this.form.submit()">
                            <option value="">Todos</option>
                            @foreach($gimnasios as $gimnasio)
                            <option value="{{ $gimnasio->id }}" {{ (string) ($gimnasioSeleccionado ?? '') === (string) $gimnasio->id ? 'selected' : '' }}>
                                {{ $gimnasio->nombre }}
                            </option>
                            @endforeach
                        </select>
                    </form>
                    @endif
                </div>
                @endif
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
                                @if($isAdminLike)
                                <th style="text-align: center;">Entrenador</th>
                                @endif
                                @if($isSuperAdmin)
                                <th style="text-align: center;">Gimnasio</th>
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
                                @if($isAdminLike)
                                <td>{{ $tarea->entrenador_nombre ?? $tarea->usuario->name ?? '-' }}</td>
                                @endif
                                @if($isSuperAdmin)
                                <td>{{ $tarea->gimnasio_nombre ?? '-' }}</td>
                                @endif
                                <td>{{ $tarea->nombre }}</td>
                                <td>{{ $tarea->fecha_limite }}</td>
                                <td>{{ $tarea->completada }}</td>
                                <td>
                                    <a href="{{ route('tareas.show', $tarea->slug) }}" class="inline-block bg-gray-700 hover:bg-gray-500 text-white font-bold py-2 px-4 rounded">
                                        Ver
                                    </a>
                                    @if($isAdminLike)
                                    <a href="{{ route('tareas.edit', $tarea->slug) }}" class="inline-block bg-gray-700 hover:bg-gray-500 text-white font-bold py-2 px-4 rounded">
                                        Editar
                                    </a>
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