<x-admin-layout>
    @php($clienteActual = auth()->user()?->cliente)
    @php($isSuperAdmin = (int) auth()->user()->id_tipo_usuario === 10)
    @php($isAdminLike = in_array((int) auth()->user()->id_tipo_usuario, [1, 10], true) || (int) auth()->user()->id_clasificacion === 3)
    <div class="py-4">
        <div class="">
            @if($clienteActual)
            <div class="flex items-center justify-between mb-4 bg-white p-6 rounded-lg">
                <div class="text-gray-700">
                    <i class="fas fa-user fa-2x">&nbsp;{{ $clienteActual->nombres }} {{ $clienteActual->paterno }} {{ $clienteActual->materno }}</i>
                    <br><small>{{ $clienteActual->plan->nombre ?? 'Sin plan' }}</small>
                </div>
            </div>
            @endif
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-4">
                @if($isSuperAdmin && isset($gimnasios))
                <div class="mb-4 flex justify-end">
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
                </div>
                @endif
                <!-- Modal y tabla dentro de un mismo x-data -->
                <div x-data="{ show: false, mensajeId: null }">
                    <table class="min-w-full divide-y divide-gray-200 tabla_datos">
                        <thead>
                            <tr>
                                <th class="px-4 py-2 text-left">De</th>
                                @if($isAdminLike)
                                <th class="px-4 py-2 text-left">Para</th>
                                @endif
                                @if($isSuperAdmin)
                                <th class="px-4 py-2 text-left">Gimnasio</th>
                                @endif
                                <th class="px-4 py-2 text-left">Fecha</th>
                                <th class="px-4 py-2 text-left">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($mensajes as $mensaje)
                            <tr class="border-b">
                                <td class="px-4 py-2">{{ $mensaje->remitente->name }}</td>
                                @if($isAdminLike)
                                <td class="px-4 py-2">{{ $mensaje->destinatario->name ?? '-' }}</td>
                                @endif
                                @if($isSuperAdmin)
                                <td class="px-4 py-2">{{ $mensaje->remitente->gimnasio->nombre ?? 'Sin gimnasio' }}</td>
                                @endif
                                <td class="px-4 py-2">{{ $mensaje->created_at->format('d/m/Y H:i') }}</td>
                                <td class="px-4 py-2">
                                    <button type="button"
                                        class="bg-gray-700 hover:bg-gray-500 text-white font-bold py-2 px-4 rounded"
                                        @click="show = true; mensajeId = {{ $mensaje->id }}">Ver</button>
                                    <button
                                        class="bg-gray-700 hover:bg-gray-500 text-white font-bold py-2 px-4 rounded"
                                        @click="show = false; $nextTick(() => { $dispatch('abrir-respuesta', { paraId: {{ $mensaje->remitente->id }}, paraNombre: '{{ $mensaje->remitente->name }}' }) })">
                                        Responder
                                    </button>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>

                    <!-- Modal para mostrar el mensaje -->
                    <div x-show="show" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
                        <div class="bg-white p-6 rounded shadow-lg max-w-lg w-auto relative">
                            <button class=" top-2 right-2 text-gray-500" @click="show = false"><i class="fa fa-circle-xmark fa-2x"></i></button>
                            <template x-if="mensajeId">
                                <div>
                                    @foreach($mensajes as $mensaje)
                                    <div x-show="mensajeId === {{ $mensaje->id }}">
                                        <h2 class="font-bold">Mensaje:</h2> {{ $mensaje->contenido }}<br>
                                        <h2 class="font-bold mt-4 mb-4">Archivos Adjuntos:</h2>
                                        @if($mensaje->archivos->isEmpty())
                                        <p>No hay archivos adjuntos.</p>
                                        @else
                                        @foreach($mensaje->archivos as $archivo)
                                        @if(Str::startsWith($archivo->tipo, 'video/'))
                                        <video controls width="250">
                                            <source src="{{ asset('storage/' . $archivo->archivo) }}" type="{{ $archivo->tipo }}">
                                            Tu navegador no soporta el video.
                                        </video>
                                        @elseif(Str::startsWith($archivo->tipo, 'image/'))
                                        <img src="{{ asset('storage/' . $archivo->archivo) }}" alt="Imagen adjunta" style="max-width:200px;">
                                        @else
                                        <a href="{{ asset('storage/' . $archivo->archivo) }}" target="_blank" class="text-blue-600 underline">Ver archivo adjunto</a><br>
                                        @endif
                                        @endforeach
                                        @endif
                                    </div>
                                    @endforeach
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
                <div
                    x-data="{ mostrarRespuesta: false, paraId: null, paraNombre: '', contenido: '' }"
                    @abrir-respuesta.window="
        mostrarRespuesta = true;
        paraId = $event.detail.paraId;
        paraNombre = $event.detail.paraNombre;
        contenido = '';
    "
                    x-show="mostrarRespuesta"
                    class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
                    style="display: none;">
                    <div class="bg-white p-6 rounded shadow-lg max-w-lg w-auto relative">
                        <button class=" top-2 right-2 text-gray-500" @click="mostrarRespuesta = false"><i class="fa fa-circle-xmark fa-2x"></i></button>
                        <h2 class="font-bold mb-2">Responder a: <span x-text="paraNombre"></span></h2>
                        <form method="POST" action="{{ route('mensajes.store') }}" enctype="multipart/form-data">
                            @csrf
                            <input type="hidden" name="para_id" :value="paraId">
                            <textarea name="contenido" x-model="contenido" required class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm mb-4"></textarea>
                            <input type="file" name="archivos[]" multiple class="mb-2">
                            <button type="submit" class="bg-gray-700 hover:bg-gray-500 text-white font-bold py-2 px-4 rounded">Enviar respuesta</button>
                        </form>
                    </div>
                </div>
                <div class="mt-4">
                    <a href="{{ route('mensajes.create') }}" class="bg-gray-700 hover:bg-gray-500 text-white font-bold py-2 px-4 rounded">Nuevo mensaje</a>
                </div>
            </div>
        </div>
    </div>
</x-admin-layout>