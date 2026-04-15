<x-admin-layout>
    @php($isSuperAdmin = (int) Auth::user()->id_tipo_usuario === 10)
    <div class="py-4">
        <div class="">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-4 text-gray-900 flex flex-col gap-3 md:flex-row md:justify-between text-end">
                    <a href="{{ route('planes.create', $isSuperAdmin && !empty($gimnasioSeleccionado) ? ['id_gimnasio' => $gimnasioSeleccionado] : []) }}" class="bg-gray-700 hover:bg-gray-500 text-white font-bold py-2 px-4 rounded">
                        Agregar Plan
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
                    <table id="tablaDatos" class="display w-full min-w-max">
                        <thead>
                            <tr>
                                <th style="text-align: center;">Nombre</th>
                                <th style="text-align: center;">Valor</th>
                                <th style="text-align: center;">Porcentaje para Entrenador</th>
                                @if($isSuperAdmin)
                                <th style="text-align: center;">Gimnasio</th>
                                @endif
                                <th style="text-align: center;">Estado</th>
                                <th style="text-align: center;">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($planes as $plan)
                            <tr>
                                <td>{{ $plan->nombre }}</td>
                                <td style="text-align: right;">${{ $plan->valor }}</td>
                                <td style="text-align: right;">{{ $plan->porcentaje }}% (${{ round(($plan->valor*($plan->porcentaje/100)),0)}})</td>
                                @if($isSuperAdmin)
                                <td>{{ $plan->gimnasio->nombre ?? 'Sin gimnasio' }}</td>
                                @endif
                                <td>@if($plan->estado == 1) Activo @else Inactivo @endif</td>
                                <td>
                                    <a href="{{ route('planes.edit', $plan->slug) }}" class="inline-block bg-gray-800 hover:bg-gray-500 text-white font-bold py-1 px-2 rounded">
                                        Editar
                                    </a>
                                    <form action="{{ route('planes.cambiarEstado', $plan->slug) }}" method="POST" style="display:inline;">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="ml-2 {{ $plan->estado == 1 ? 'bg-red-600 hover:bg-red-800' : 'bg-green-600 hover:bg-green-800' }} text-white font-bold py-1 px-2 rounded">
                                            {{ $plan->estado == 1 ? 'Desactivar' : 'Activar' }}
                                        </button>
                                    </form>
                                    <form action="{{ route('planes.destroy', $plan->slug) }}" method="POST" style="display:inline;" onsubmit="return confirm('¿Eliminar este plan?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="ml-2 bg-red-500 hover:bg-red-800 text-white font-bold py-1 px-2 rounded">
                                            Eliminar
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <span class="hidden bg-green-600 bg-green-800 hover:bg-green-800 bg-red-600 bg-red-800 hover:bg-red-800"></span>
                    <span class="hidden bg-green-100 border-green-400 text-green-700"></span>
                </div>
            </div>
        </div>
    </div>
</x-admin-layout>