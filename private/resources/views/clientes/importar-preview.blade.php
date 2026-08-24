<x-admin-layout>
    <div class="py-4">
        <div class="">
            <div class="flex items-center justify-between mb-4">
                <a href="{{ route('clientes.importar') }}" class="text-gray-700 hover:text-gray-500">
                    <i class="fas fa-circle-left fa-2x">&nbsp;</i>
                </a>
            </div>
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 overflow-x-auto w-full">
                    <h2 class="font-semibold text-xl text-gray-800 leading-tight mb-4">
                        Previsualización de la importación
                    </h2>

                    <div class="mx-4 my-2 rounded-lg border border-sky-200 bg-sky-50 px-4 py-3 text-sm text-sky-800">
                        {{ count($validas) }} de {{ $totalFilas }} fila(s) están listas para importar.
                        @if(count($invalidas) > 0)
                            {{ count($invalidas) }} fila(s) tienen errores y se van a omitir — revísalas abajo.
                        @endif
                        Todavía no se ha creado ningún cliente: esto es solo una vista previa.
                    </div>

                    @if(count($validas) > 0)
                    <form action="{{ route('clientes.importar.store') }}" method="POST" class="mx-4 my-4">
                        @csrf
                        <input type="hidden" name="uuid" value="{{ session('import_clientes.uuid') }}">
                        <button type="submit" class="bg-green-700 hover:bg-green-800 text-white font-bold py-2 px-4 rounded"
                            onclick="return confirm('¿Confirmar la importación de {{ count($validas) }} cliente(s)? No se enviará ningún correo automáticamente.');">
                            Confirmar e importar {{ count($validas) }} cliente(s)
                        </button>
                    </form>
                    @else
                    <div class="mx-4 my-2 p-3 bg-red-100 border border-red-400 text-red-700 rounded">
                        Ninguna fila quedó lista para importar. Corrige los errores de abajo y vuelve a subir el archivo.
                    </div>
                    @endif

                    @if(count($invalidas) > 0)
                    <div class="mx-4 my-4">
                        <h3 class="text-sm font-semibold text-red-700 mb-2">Filas con errores (no se van a importar)</h3>
                        <div class="overflow-x-auto">
                            <table class="min-w-full text-sm border">
                                <thead class="bg-red-50">
                                    <tr>
                                        <th class="px-3 py-2 text-left border">Fila</th>
                                        <th class="px-3 py-2 text-left border">Nombre</th>
                                        <th class="px-3 py-2 text-left border">Errores</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($invalidas as $fila)
                                    <tr>
                                        <td class="px-3 py-1 border">{{ $fila['fila'] }}</td>
                                        <td class="px-3 py-1 border">{{ $fila['nombre'] ?: '—' }}</td>
                                        <td class="px-3 py-1 border text-red-700">{{ implode(' ', $fila['errores']) }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                    @endif

                    @if(count($validas) > 0)
                    <div class="mx-4 my-4">
                        <h3 class="text-sm font-semibold text-gray-700 mb-2">Filas listas para importar</h3>
                        <div class="overflow-x-auto">
                            <table class="min-w-full text-sm border">
                                <thead class="bg-gray-100">
                                    <tr>
                                        <th class="px-3 py-2 text-left border">Fila</th>
                                        <th class="px-3 py-2 text-left border">Nombre</th>
                                        <th class="px-3 py-2 text-left border">Email</th>
                                        <th class="px-3 py-2 text-left border">CI</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($validas as $fila)
                                    <tr>
                                        <td class="px-3 py-1 border">{{ $fila['fila'] }}</td>
                                        <td class="px-3 py-1 border">{{ $fila['nombres'] }} {{ $fila['paterno'] }}</td>
                                        <td class="px-3 py-1 border">{{ $fila['email'] }}</td>
                                        <td class="px-3 py-1 border">{{ $fila['ci'] }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-admin-layout>
