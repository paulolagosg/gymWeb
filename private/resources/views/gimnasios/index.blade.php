<x-admin-layout>
    <div class="py-4">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-4 text-gray-900 flex justify-between text-end">
                @if((int) Auth::user()->id_tipo_usuario === 10)
                <a href="{{ route('gimnasios.create') }}" class="bg-gray-700 hover:bg-gray-500 text-white font-bold py-2 px-4 rounded">
                    Agregar gimnasio
                </a>
                @endif
            </div>

            <div class="p-6 text-gray-900 overflow-x-auto w-full">
                @if(session('success'))
                <div class="mx-4 my-2 p-3 bg-green-100 border border-green-400 text-green-700 rounded">
                    {{ session('success') }}
                </div>
                @endif

                @if(session('error'))
                <div class="mx-4 my-2 p-3 bg-red-100 border border-red-400 text-red-700 rounded">
                    {{ session('error') }}
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
                            <th>Nombre</th>
                            <th>Slug</th>
                            <th>Contacto</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($gimnasios as $gimnasio)
                        <tr>
                            <td>{{ $gimnasio->nombre }}</td>
                            <td>{{ $gimnasio->slug }}</td>
                            <td>
                                <div>{{ $gimnasio->telefono ?: 'Sin teléfono' }}</div>
                                <div>{{ $gimnasio->correo_electronico ?: 'Sin correo' }}</div>
                            </td>
                            <td>{{ $gimnasio->estado == 1 ? 'Activo' : 'Inactivo' }}</td>
                            <td class="text-center whitespace-nowrap">
                                <button class="bg-gray-800 hover:bg-gray-500 text-white font-bold py-1 px-2 rounded" onclick="location.href='{{ route('gimnasios.edit', $gimnasio->slug) }}'">
                                    Editar
                                </button>

                                @if((int) Auth::user()->id_tipo_usuario === 10)
                                <form action="{{ route('gimnasios.cambiarEstado', $gimnasio->slug) }}" method="POST" style="display:inline;">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="ml-2 {{ $gimnasio->estado == 1 ? 'bg-red-600 hover:bg-red-800' : 'bg-green-600 hover:bg-green-800' }} text-white font-bold py-1 px-2 rounded">
                                        {{ $gimnasio->estado == 1 ? 'Desactivar' : 'Activar' }}
                                    </button>
                                </form>

                                <form action="{{ route('gimnasios.destroy', $gimnasio->slug) }}" method="POST" style="display:inline;" onsubmit="return confirm('¿Seguro que deseas eliminar este gimnasio?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="ml-2 bg-red-700 hover:bg-red-900 text-white font-bold py-1 px-2 rounded">
                                        Eliminar
                                    </button>
                                </form>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-admin-layout>