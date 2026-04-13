<x-admin-layout>
    <div class="py-4">
        <div class="">
            <div class="flex items-center justify-between mb-4 bg-white p-4 rounded-lg shadow">
                <a href="{{ route('entrenadores.opciones.portada',$entrenador->slug) }}" class="hover:text-gray-500">
                    <i class="fas fa-circle-left fa-2x">&nbsp;{{ $entrenador->name }}</i>
                </a>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="flex items-center justify-between p-4">
                    <h1 class="text-2xl font-bold">Formación Continua</h1>
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
                                <th style="text-align: center;">Curso</th>
                                <th style="text-align: center;">Institución</th>
                                <th style="text-align: center;">Modalidad</th>
                                <th style="text-align: center;">Fecha Inicio</th>
                                <th style="text-align: center;">Fecha Término</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($cursos as $c)
                            <tr>
                                <td style="text-align: center;">
                                    {{ $c->curso }}
                                </td>
                                <td style="text-align: center;">
                                    {{ $c->institucion }}
                                </td>
                                <td style="text-align: center;">
                                    {{ $c->modalidad }}
                                </td>
                                <td style="text-align: center;" data-order="{{  \Carbon\Carbon::parse($c->fecha_inicio)->format('Y-m-d')  }}">
                                    {{ $c->fecha_inicio }}
                                </td>
                                <td style="text-align: center;" data-order="{{  \Carbon\Carbon::parse($c->fecha_inicio)->format('Y-m-d')  }}">
                                    {{ $c->fecha_fin }}
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