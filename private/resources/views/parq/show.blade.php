<x-admin-layout>
    <div class="py-4">
        <div class="">
            <div class="flex items-center justify-between mb-4 bg-white p-6 rounded-lg text-center">
                <a href="{{ route('clientes.opciones.portada', $cliente->slug) }}" class="text-gray-700 hover:text-gray-500">
                    <i class="fas fa-circle-left fa-2x">&nbsp;{{ $cliente->nombres }} {{ $cliente->paterno }} {{ $cliente->materno }}</i>
                </a>
            </div>
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 overflow-x-auto w-full">
                    <h2 class="font-semibold text-xl text-gray-800 leading-tight mb-4">
                        Par-Q
                    </h2>
                    @if($respuestas->isNotEmpty())
                    <ul class="mb-4">
                        @foreach($respuestas as $pregunta_id => $respuestaGroup)
                        @php $respuesta = $respuestaGroup->first(); @endphp
                        <li>
                            {{ $respuesta->pregunta->pregunta }}<br>
                            <strong>{{ $respuesta->respuesta ? 'Sí' : 'No' }}</strong>
                            @if($respuesta->observaciones)
                            <br><em><strong>Obs:</strong> {{ $respuesta->observaciones }}</em>
                            @endif
                            <br>
                            <br>
                        </li>
                        @endforeach
                    </ul>
                    @else
                    <p>No hay cuestionario registrado.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-admin-layout>