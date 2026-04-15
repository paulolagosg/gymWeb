<x-admin-layout>
    <div class="py-4">
        <div class="">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-4 text-gray-900 flex justify-between text-end">
                    <a href="{{ route('ejercicios.create') }}" class="bg-gray-700 hover:bg-gray-500 text-white font-bold py-2 px-4 rounded">
                        Agregar Ejercicio
                    </a>
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
                                <th colspan="2" style="text-align: center;">Nombre</th>
                                <!-- <th style="text-align: center;">Descripción</th> -->
                                <th style="text-align: center;">Estado</th>
                                <th style="text-align: center;">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($ejercicios as $ejercicio)
                            <tr>
                                <td><img src="/iconos/{{ $ejercicio->tipo->icono }}" style="width:32px" /></td>
                                <td> {{ $ejercicio->nombre }}</td>
                                <!-- <td>{{ $ejercicio->descripcion }}</td> -->
                                <td>@if($ejercicio->estado == 1) Activo @else Inactivo @endif</td>
                                <td style="text-align: center;">
                                    <button class="bg-gray-800 hover:bg-gray-500 text-white font-bold py-1 px-2 rounded" onclick="location.href='{{ route('ejercicios.edit', $ejercicio->slug) }}'">
                                        Editar
                                    </button>
                                    <form action="{{ route('ejercicios.cambiarEstado', $ejercicio->slug) }}" method="POST" style="display:inline;">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="ml-2 {{ $ejercicio->estado == 1 ? 'bg-red-600 hover:bg-red-800' : 'bg-green-600 hover:bg-green-800' }} text-white font-bold py-1 px-2 rounded">
                                            {{ $ejercicio->estado == 1 ? 'Desactivar' : 'Activar' }}
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