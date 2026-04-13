<x-admin-layout>
    <div class="py-4">
        <div class="">
            <div class="flex items-center justify-between mb-4">
                <a href="{{ route('portada') }}" class="text-gray-700 hover:text-gray-500">
                    <i class="fas fa-circle-left fa-2x">&nbsp;</i>
                </a>
            </div>
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-4 text-gray-900 items-center flex justify-between text-end">
                    <a href="{{ route('usuarios.create') }}" class="bg-gray-700 hover:bg-gray-500 text-white font-bold py-2 px-4 rounded">
                        Agregar Usuario
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
                                <th style="text-align: center;">Nombre</th>
                                <th style="text-align: center;">Email</th>
                                <th style="text-align: center;">Valor Hora Individual</th>
                                <th style="text-align: center;">Valor Hora Duo</th>
                                <th style="text-align: center;">Tipo de Usuario</th>
                                <th style="text-align: center;">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($usuarios as $user)
                            <tr>
                                <td>{{ $user->name }}</td>
                                <td>{{ $user->email }}</td>
                                <td style="text-align: right;">@if($user->id_tipo_usuario == 2)${{ number_format($user->individual,0,',','.') }} @else No aplica @endif</td>
                                <td style="text-align: right;">@if($user->id_tipo_usuario == 2)${{ number_format($user->duo,0,',','.') }} @else No aplica @endif</td>
                                <td>
                                    {{ $user->tipoUsuario->nombre ?? '-' }}
                                </td>
                                <td style="text-align: center;">
                                    <button type="button" class="bg-gray-800 hover:bg-gray-500 text-white font-bold py-1 px-2 rounded" onclick="location.href='{{ route('usuarios.edit', $user->id) }}'">
                                        Editar
                                    </button>
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
</x-admin-layout>