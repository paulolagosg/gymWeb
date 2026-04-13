<x-admin-layout>
    <div class="py-4">
        <div class="">
            @if($origen !== 'p')
            <div class="flex items-center justify-between mb-4 bg-white p-4 rounded-lg shadow">
                <a href="{{ route('entrenadores.opciones.portada',$entrenador->slug) }}" class="hover:text-gray-500">
                    <i class="fas fa-circle-left fa-2x">&nbsp;{{ $entrenador->name }}</i>
                </a>
            </div>
            @else
            <div class="flex items-center justify-between mb-4 p-4 rounded-lg">
                <a href="{{ route('portada') }}" class="hover:text-gray-500">
                    <i class="fas fa-circle-left fa-2x">&nbsp;</i>
                </a>
            </div>
            @endif
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-4 text-gray-900 items-center flex justify-between text-end">
                    <h1 class="text-2xl font-bold">Encuestas de Satisfacción</h1>
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
                                @if(auth()->user()->id_clasificacion == 3 and $origen !== 'p')
                                <th style="text-align: center;">Entrenador</th>
                                @endif
                                <th style="text-align: center;">Fecha</th>
                                <th style="text-align: center;">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($encuestas as $e)
                            <tr>
                                @if(auth()->user()->id_clasificacion == 3 and $origen !== 'p')
                                <td>{{ $entrenador->name }}</td>
                                @endif
                                <td>{{ $e->updated_at }}</td>
                                <td style="text-align: center;" nowrap="nowrap">
                                    <!-- <a href="{{ route('encuestas.create', $entrenador->slug) }}" class="bg-gray-700 hover:bg-gray-500 text-white font-bold py-2 px-4 rounded">
                                        <i class="fas fa-plus"></i> Nueva Encuesta
                                    </a> -->
                                    <a href="{{ route('encuestas.show', $e->slug) }}" class="bg-gray-700 hover:bg-gray-500 text-white font-bold py-2 px-4 rounded">
                                        Ver Respuestas
                                    </a>
                                    <!-- <a href="{{ route('encuestas.gracias') }}" class="bg-gray-700 hover:bg-gray-500 text-white font-bold py-2 px-4 rounded">
                                        <i class="fas fa-check"></i> Gracias
                                    </a> -->
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