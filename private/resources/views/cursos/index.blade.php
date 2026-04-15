<x-admin-layout>
    @php($isAdminLike = in_array((int) auth()->user()->id_tipo_usuario, [1, 10], true) || (int) auth()->user()->id_clasificacion === 3)
    @php($isSuperAdmin = (int) auth()->user()->id_tipo_usuario === 10)
    <div class="py-4">
        <div class="">


            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="flex flex-col gap-3 p-4 md:flex-row md:items-center md:justify-between">
                    <a href="{{ route('cursos.create', $isSuperAdmin && !empty($gimnasioSeleccionado) ? ['id_gimnasio' => $gimnasioSeleccionado] : []) }}" class="bg-gray-700 hover:bg-gray-500 text-white font-bold py-2 px-4 rounded">
                        Agregar Formación
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
                                <th style="text-align: center;">Fecha Inicio</th>
                                <th style="text-align: center;">Fecha Término</th>
                                <th style="text-align: center;">Curso</th>
                                <th style="text-align: center;">Institución</th>
                                @if($isAdminLike)
                                <th style="text-align: center;">Entrenador</th>
                                @endif
                                @if($isSuperAdmin)
                                <th style="text-align: center;">Gimnasio</th>
                                @endif
                                <th style="text-align: center;">Modalidad</th>
                                <th style="text-align: center;">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($cursos as $c)
                            <tr>
                                <td style="text-align: center;" data-order="{{  \Carbon\Carbon::parse($c->fecha_inicio)->format('Y-m-d')  }}">
                                    {{ \Carbon\Carbon::parse($c->fecha_inicio)->format('d/m/Y') }}
                                </td>
                                <td style="text-align: center;">
                                    {{ \Carbon\Carbon::parse($c->fecha_fin)->format('d/m/Y') }}
                                </td>
                                <td style="text-align: center;">
                                    {{ $c->curso }}
                                </td>
                                <td style="text-align: center;">
                                    {{ $c->institucion }}
                                </td>
                                @if($isAdminLike)
                                <td style="text-align: center;">
                                    {{ $c->entrenador_nombre ?? '-' }}
                                </td>
                                @endif
                                @if($isSuperAdmin)
                                <td style="text-align: center;">
                                    {{ $c->gimnasio_nombre ?? '-' }}
                                </td>
                                @endif
                                <td style="text-align: center;">
                                    {{ $c->modalidad_label ?? $c->modalidad }}
                                </td>
                                <td style="text-align: center;">
                                    <a href="{{ route('cursos.edit', $c->slug) }}" class="inline-block bg-gray-700 hover:bg-gray-500 text-white font-bold py-2 px-4 rounded">
                                        Editar
                                    </a>
                                    <form action="{{ route('cursos.destroy', $c->slug) }}" method="POST" style="display:inline;">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="bg-red-500 hover:bg-red-800 text-white font-bold py-2 px-4 rounded ml-2">
                                            Eliminar
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-admin-layout>