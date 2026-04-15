<x-admin-layout>
    <div class="py-4">
        <div class="">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
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
                                <th style="text-align: center;">Plan</th>
                                <th style="text-align: center;">Inicio</th>
                                <th style="text-align: center;">Fin</th>
                                <th style="text-align: center;">Entrenador</th>
                                <th style="text-align: center;">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($clientesMorosos as $cliente)
                            <tr>
                                <td>@if($clientesMorosos->contains('id', $cliente->id))
                                    <i class="fa-solid fa-circle-exclamation text-red-600" data-tippy-content="Atrasado"></i>
                                    @endif {{ $cliente->nombres }} {{ $cliente->paterno }} {{ $cliente->materno }}
                                </td>
                                <td>{{ $cliente->telefono }}</td>
                                <td>{{ $cliente->email }}</td>
                                <td>{{ $cliente->plan->nombre ?? '-' }}</td>
                                <td>{{ \Carbon\Carbon::parse($cliente->fecha_ingreso)->format('d/m/Y') }}</td>
                                <td>{{ \Carbon\Carbon::parse($cliente->fecha_fin)->format('d/m/Y') }}</td>
                                <td>{{ $cliente->user->name ?? '-' }}</td>
                                <td style="text-align: center;">
                                    <button type="button" id="btnRecordatorio{{$cliente->slug}}" class="bg-gray-800 hover:bg-gray-500 text-white font-bold py-1 px-2 rounded mt-2" onclick="enviarRecordatorioPago('{{ $cliente->slug }}')">
                                        Enviar recordatorio pago
                                    </button>
                                    <button type="button" class="bg-gray-800 hover:bg-gray-500 text-white font-bold py-1 px-2 rounded mt-2" onclick="location.href='{{ route('clientes.opciones.portada', $cliente->slug) }}'">
                                        Opciones
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
    </div>
    <script>
        function enviarRecordatorioPago(clienteSlug) {
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
                                'error'
                            );
                        });
                }
            });
        }
    </script>
</x-admin-layout>