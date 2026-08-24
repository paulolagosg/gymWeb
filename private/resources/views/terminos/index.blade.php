<x-admin-layout>
    <div class="py-4">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-4 text-gray-900 flex justify-between items-center gap-4">
                <div>
                    <h2 class="text-2xl font-bold">Términos y Condiciones</h2>
                    <p class="text-sm text-gray-500 mt-1">Gestiona versiones globales y por gimnasio. Cada nueva versión publicada reemplaza la vigente del mismo ámbito y conserva el historial.</p>
                </div>
                <a href="{{ route('terminos.create') }}" class="bg-gray-700 hover:bg-gray-500 text-white font-bold py-2 px-4 rounded whitespace-nowrap">
                    Publicar nueva versión
                </a>
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
                    <ul class="mb-0 list-disc pl-5">
                        @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
                @endif

                <table id="tablaDatos" class="display w-full min-w-max">
                    <thead>
                        <tr>
                            <th>Ámbito</th>
                            <th>Título</th>
                            <th>Versión</th>
                            <th>Estado</th>
                            <th>Obligatorio</th>
                            <th>Aceptaciones</th>
                            <th>Historial</th>
                            <th>Publicado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($terminos as $termino)
                        <tr>
                            <td>{{ $termino->gimnasio?->nombre ?? 'Global' }}</td>
                            <td>
                                <div class="font-semibold">{{ $termino->titulo }}</div>
                                @if($termino->resumen_cambios)
                                <div class="text-xs text-gray-500 mt-1">{{ $termino->resumen_cambios }}</div>
                                @endif
                            </td>
                            <td>{{ $termino->version }}</td>
                            <td>
                                <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $termino->activo ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600' }}">
                                    {{ $termino->activo ? 'Activa' : 'Histórica' }}
                                </span>
                            </td>
                            <td>{{ $termino->obligatorio ? 'Sí' : 'No' }}</td>
                            <td>{{ $termino->aceptaciones_count }}</td>
                            <td>
                                @if($termino->versionAnterior)
                                Basada en {{ $termino->versionAnterior->version }}
                                @else
                                Versión inicial
                                @endif
                            </td>
                            <td>{{ optional($termino->publicado_en)->format('d-m-Y H:i') ?? optional($termino->created_at)->format('d-m-Y H:i') }}</td>
                            <td class="text-center whitespace-nowrap">
                                <button class="bg-gray-800 hover:bg-gray-500 text-white font-bold py-1 px-2 rounded" onclick="location.href='{{ route('terminos.edit', $termino->id) }}'">
                                    Nueva versión
                                </button>

                                <form action="{{ route('terminos.destroy', $termino->id) }}" method="POST" style="display:inline;" onsubmit="return confirm('¿Seguro que deseas eliminar esta versión?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="ml-2 bg-red-700 hover:bg-red-900 text-white font-bold py-1 px-2 rounded">
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
</x-admin-layout>