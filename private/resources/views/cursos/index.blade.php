<x-admin-layout>
    <div class="py-4">
        <div class="">
            <div class="flex items-center justify-between mb-4  p-4 rounded-lg">
                <a href="{{ route('portada') }}" class="hover:text-gray-500">
                    <i class="fas fa-circle-left fa-2x">&nbsp;</i>
                </a>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="flex items-center justify-between p-4">
                    <h1 class="text-2xl font-bold">Formación Continua</h1>
                </div>
                <div class="flex items-center justify-between p-4">
                    <a href="{{ route('cursos.create') }}" class="bg-gray-700 hover:bg-gray-500 text-white font-bold py-2 px-4 rounded">
                        Agregar Formación
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
                    <table id="tablaDatos" class="display ">
                        <thead>
                            <tr>
                                <th style="text-align: center;">Fecha Inicio</th>
                                <th style="text-align: center;">Fecha Término</th>
                                <th style="text-align: center;">Curso</th>
                                <th style="text-align: center;">Institución</th>
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
                                <td style="text-align: center;">
                                    {{ $c->modalidad }}
                                </td>
                                <td style="text-align: center;">
                                    <button class="bg-gray-700 hover:bg-gray-500 text-white font-bold py-2 px-4 rounded" onclick="window.location.href='{{ route('cursos.edit', $c->slug) }}'">
                                        Editar
                                    </button>
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