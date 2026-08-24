<x-admin-layout>
    @php($esSuperAdmin = (int) Auth::user()->id_tipo_usuario === 10)
    @php($puedeGestionarClientes = in_array((int) Auth::user()->id_tipo_usuario, [1, 10], true) || (int) Auth::user()->id_clasificacion === 3)
    <div class="py-4">
        <div class="">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                @if($puedeGestionarClientes)
                <div class="p-4 text-gray-900 items-center flex flex-col gap-3 md:flex-row md:justify-between text-end">
                    <a href="{{ route('clientes.create', $esSuperAdmin && !empty($gimnasioSeleccionado) ? ['id_gimnasio' => $gimnasioSeleccionado] : []) }}" class="bg-gray-700 hover:bg-gray-500 text-white font-bold py-2 px-4 rounded">
                        Agregar Cliente
                    </a>
                    <a href="{{ route('clientes.importar', $esSuperAdmin && !empty($gimnasioSeleccionado) ? ['id_gimnasio' => $gimnasioSeleccionado] : []) }}" class="bg-gray-700 hover:bg-gray-500 text-white font-bold py-2 px-4 rounded">
                        Importar CSV
                    </a>

                    @if($esSuperAdmin && isset($gimnasios))
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
                    @endif
                </div>
                @endif
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
                                <th style="text-align: center;">Teléfono</th>
                                <th style="text-align: center;">Email</th>
                                <th style="text-align: center;">Estado</th>
                                <th style="text-align: center;">Plan</th>
                                <th style="text-align: center;">Inicio</th>
                                <th style="text-align: center;">Fin</th>
                                <th style="text-align: center;">Tipo</th>
                                <th style="text-align: center;">Entrenador</th>
                                @if($esSuperAdmin)
                                <th style="text-align: center;">Gimnasio</th>
                                @endif
                                <th style="text-align: center;">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($clientes as $cliente)
                            <tr>
                                <td>@if($clientesMorosos->contains('id', $cliente->id))
                                    <i class="fa-solid fa-circle-exclamation text-red-600" data-tippy-content="Atrasado"></i>
                                    @endif {{ $cliente->nombres }} {{ $cliente->paterno }} {{ $cliente->materno }}
                                </td>
                                <td>{{ $cliente->telefono }}</td>
                                <td>{{ $cliente->email }}</td>
                                <td style="text-align: center;">
                                    @if((int) $cliente->getRawOriginal('estado') === 1)
                                    <span class="inline-flex rounded-full bg-green-100 px-3 py-1 text-xs font-semibold text-green-700">Activo</span>
                                    @else
                                    <span class="inline-flex rounded-full bg-stone-200 px-3 py-1 text-xs font-semibold text-stone-700">Inactivo</span>
                                    @endif
                                </td>
                                <td>{{ $cliente->plan->nombre ?? '-' }}</td>
                                <td>{{ \Carbon\Carbon::parse($cliente->fecha_ingreso)->format('d/m/Y') }}</td>
                                <td>{{ \Carbon\Carbon::parse($cliente->fecha_fin)->format('d/m/Y') }}</td>
                                <td>{{ $cliente->tipo_usuario ?? '-' }}</td>
                                <td>{{$cliente->entrenador->name ?? '-' }}</td>
                                @if($esSuperAdmin)
                                <td>{{ $cliente->gimnasio->nombre ?? 'Sin gimnasio' }}</td>
                                @endif
                                <td style="text-align: center;" nowrap="nowrap">
                                    @if($clientesMorosos->contains('id', $cliente->id))
                                    <button type="button" id="btnRecordatorio{{ $cliente->slug }}" class="bg-gray-800 hover:bg-gray-500 text-white font-bold py-1 px-2 rounded" onclick="enviarRecrdatorioPago('{{ $cliente->slug }}')">
                                        Enviar recordatorio pago
                                    </button>
                                    @endif

                                    @if(in_array((int) Auth::user()->id_tipo_usuario, [1, 10], true))
                                    <a href="{{ route('clientes.edit', $cliente->slug) }}" class="inline-block bg-gray-800 hover:bg-gray-500 text-white font-bold py-1 px-2 rounded">
                                        Editar
                                    </a>

                                    @if($puedeGestionarClientes)
                                    <a href="{{ route('clientes.cambiarEstado', $cliente->slug) }}" class="inline-block {{ (int) $cliente->getRawOriginal('estado') === 1 ? 'bg-red-500 hover:bg-red-800' : 'bg-green-600 hover:bg-green-800' }} text-white font-bold py-1 px-2 rounded ml-2">
                                        {{ (int) $cliente->getRawOriginal('estado') === 1 ? 'Dar de baja' : 'Activar' }}
                                    </a>
                                    @endif
                                    @elseif(!$esSuperAdmin)
                                    <a href="{{ route('clientes.opciones.portada', $cliente->slug) }}" class="inline-block bg-gray-800 hover:bg-gray-500 text-white font-bold py-1 px-2 rounded">
                                        Opciones
                                    </a>
                                    @endif

                                    @if($esSuperAdmin && $puedeGestionarClientes)
                                    <form action="{{ route('clientes.destroy', $cliente->id) }}" method="POST" style="display:inline;" onsubmit="return confirm('¿Eliminar este cliente y sus datos asociados?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="bg-red-500 hover:bg-red-800 text-white font-bold py-1 px-2 rounded ml-2">
                                            Eliminar
                                        </button>
                                    </form>
                                    @endif
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
    <script>
        function enviarRecrdatorioPago(clienteSlug) {
            Swal.fire({
                title: 'Enviar recordatorio de pago',
                text: "¿Estás seguro de que deseas enviar un recordatorio de pago a este cliente?",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Sí, enviar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    $("#btnRecordatorio" + clienteSlug).text("Enviando...");
                    $("#btnRecordatorio" + clienteSlug).prop("disabled", true);
                    axios.get("/clientes/recordatorio/" + clienteSlug)
                        .then(response => {
                            Swal.fire(
                                'Enviado',
                                'El recordatorio de pago ha sido enviado exitosamente.',
                                'success'
                            );
                            $("#btnRecordatorio" + clienteSlug).text("Enviar recordatorio pago");
                            $("#btnRecordatorio" + clienteSlug).prop("disabled", false);
                        })
                        .catch(error => {
                            Swal.fire(
                                'Error',
                                'No se pudo enviar el recordatorio de pago. Por favor, inténtalo de nuevo más tarde.',
                                error
                            );
                            $("#btnRecordatorio" + clienteSlug).text("Enviar recordatorio pago");
                            $("#btnRecordatorio" + clienteSlug).prop("disabled", false);
                        });
                }
            });
        }
    </script>
</x-admin-layout>