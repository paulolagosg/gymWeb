<x-admin-layout>

    <head>
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <!-- Otros metadatos y recursos -->
    </head>

    <div class="py-4">
        <div class="">
            <div class="flex items-center justify-between mb-4 bg-white p-6 rounded-lg text-center">
                <a href="{{ route('clientes.opciones.portada', $cliente->slug) }}" class="text-gray-700 hover:text-gray-500">
                    <i class="fas fa-circle-left fa-2x">&nbsp;{{ $cliente->nombres }} {{ $cliente->paterno }} {{ $cliente->materno }}</i>
                </a>
            </div>
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 overflow-x-auto w-full">
                    <h2 class="text-xl font-bold mb-4">Cuenta Corriente</h2>
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
                    <form action="{{ route('clientes.cuenta_corriente.pagar_multiple', $cliente->slug) }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div x-data="{ seleccionadas: 0 }">
                            <table id="tablaDatos" class="display ">
                                <thead>
                                    <tr>
                                        <th></th>
                                        <th class="border px-2 py-1" style="text-align: center;">Tipo de cuota</th>
                                        <th class="border px-2 py-1" style="text-align: center;">Vencimiento</th>
                                        <th class="border px-2 py-1" style="text-align: center;">Monto</th>
                                        <th class="border px-2 py-1" style="text-align: center;">Descuento</th>
                                        <th class="border px-2 py-1" style="text-align: center;">Monto Cuota</th>
                                        <th class="border px-2 py-1" style="text-align: center;">Pagado</th>
                                        <th class="border px-2 py-1" style="text-align: center;">Fecha Pago</th>
                                        <th class="border px-2 py-1" style="text-align: center;">Saldo</th>
                                        <th class="border px-2 py-1" style="text-align: center;">
                                            @if(Auth::user()->id_tipo_usuario <= 2)
                                                Acciones
                                                @else
                                                Estado
                                                @endif
                                                </th>
                                    </tr>
                                    <tr class="font-bold bg-gray-100">
                                        <td colspan="3" class="border px-2 py-1" style="text-align:right">Totales</td>
                                        <td style="text-align:right" class="border px-2 py-1 {{ $total_monto > 0 ? 'text-blue-600' : 'text-red-600' }}">
                                            ${{ number_format($total_monto, 0, ',', '.') }}</td>
                                        <td style="text-align:right" class="border px-2 py-1 ">
                                            ${{ number_format($descuento, 0, ',', '.') }}
                                        </td>
                                        <td style="text-align:right" class="border px-2 py-1 {{ ($total_monto - $descuento) > 0 ? 'text-blue-600' : 'text-red-600' }}">
                                            ${{ number_format($total_monto - $descuento , 0, ',', '.') }}</td>
                                        <td style="text-align:right" class="border px-2 py-1{{ $total_pagado > 0 ? 'text-blue-600' : 'text-red-600' }}">
                                            ${{ number_format($total_pagado, 0, ',', '.') }}</td>
                                        <td class="border px-2 py-1"></td>
                                        <td style="text-align:right" class="border px-2 py-1 {{ ($total_pagado - $total_monto ) > 0 ? 'text-blue-600' : 'text-red-600' }}">
                                            ${{ number_format(($total_pagado - ($total_monto - $descuento)), 0, ',', '.') }}</td>
                                        <td class="border px-2 py-1" style="text-align: center;">
                                            &nbsp;
                                        </td>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($cuotas as $cuota)
                                    <tr>
                                        <td>
                                            @if($cuota->id_estado_pago != 2)
                                            <input type="checkbox" name="cuotas[]" value="{{ $cuota->id }}"
                                                @change="seleccionadas = document.querySelectorAll('input[name=&quot;cuotas[]&quot;]:checked').length">
                                            @endif
                                        </td>
                                        <td class="border px-2 py-1">
                                            {{ $cuota->tipoCuota->nombre ?? ($cuota->id_tipo_cuota == 1 ? 'Activación' : ($cuota->id_tipo_cuota == 2 ? 'Mensualidad' : '')) }}
                                        </td>
                                        <!-- td class="border px-2 py-1" style="text-align: center;">{{ \Carbon\Carbon::parse($cuota->fecha_vencimiento)->format('d/m/Y') }}</td -->
                                        <td class="border px-2 py-1" style="text-align: center;" data-order="{{ \Carbon\Carbon::parse($cuota->fecha_vencimiento)->format('Y-m-d') }}">
                                            {{ \Carbon\Carbon::parse($cuota->fecha_vencimiento)->format('d/m/Y') }}
                                        </td>
                                        <td class="border px-2 py-1" style="text-align: right;">${{ number_format($cuota->monto, 0, ',', '.') }}</td>
                                        <td class="border px-2 py-1" style="text-align: right;">${{ number_format($cuota->descuento, 0, ',', '.') }}</td>
                                        <td class="border px-2 py-1" style="text-align: right;">${{ number_format($cuota->monto_pagar, 0, ',', '.') }}</td>
                                        <td class="border px-2 py-1" style="text-align: right;">${{ number_format($cuota->monto_pagado, 0, ',', '.') }}</td>
                                        <td class="border px-2 py-1" style="text-align: center;">{{ $cuota->fecha_pago ? \Carbon\Carbon::parse($cuota->fecha_pago)->format('d/m/Y') : '-' }}</td>
                                        <td class="border px-2 py-1" style="text-align: right;">${{ number_format(($cuota->monto_pagado - $cuota->monto_pagar), 0, ',', '.') }}</td>

                                        <td>
                                            @if(Auth::user()->id_tipo_usuario <= 2)
                                                @if($cuota->id_estado_pago != 2 ) <!-- Si no está pagada -->
                                                <div class="flex gap-2 flex-wrap">
                                                    <button type="button" class="bg-gray-800 hover:bg-gray-500 text-white font-bold py-1 px-2 rounded" onclick="location.href='{{ route('clientes.cuenta_corriente.pagar', $cuota->id) }}'">
                                                        Pagar
                                                    </button>
                                                    <button type="button" class="bg-red-600 hover:bg-red-800 text-white font-bold py-1 px-2 rounded" onclick="eliminarCuota('{{ route('clientes.cuenta_corriente.eliminar', [$cliente->slug, $cuota->id]) }}')">
                                                        Eliminar
                                                    </button>
                                                </div>
                                                @else
                                                <span class="text-green-600 font-bold">Pagada</span>
                                                @if($cuota->formaPago && $cuota->formaPago->icono)
                                                <i class="{{ $cuota->formaPago->icono }}" data-tippy-content="{{ $cuota->formaPago->nombre }}"></i>
                                                @endif
                                                @if($cuota->comprobante)
                                                <a href="{{ asset('storage/' . $cuota->comprobante) }}" target="_blank" class="text-black underline">
                                                    <i class="fas fa-receipt" data-tippy-content="Ver comprobante"></i>
                                                </a>
                                                @endif
                                                @endif
                                                @else
                                                @if($cuota->id_estado_pago != 2 ) <!-- Si no está pagada -->
                                                <span class="text-red-600 font-bold">Pendiente</span>
                                                @else
                                                <span class="text-green-600 font-bold">Pagada</span>
                                                @if($cuota->formaPago && $cuota->formaPago->icono)
                                                <i class="{{ $cuota->formaPago->icono }}" data-tippy-content="{{ $cuota->formaPago->nombre }}"></i>
                                                @endif
                                                @endif
                                                @endif
                                        </td>

                                    </tr>
                                    @endforeach

                                </tbody>
                            </table>
                            @if(Auth::user()->id_tipo_usuario <= 2)
                                <div class="mt-4 flex items-end gap-2" x-show="seleccionadas > 0" x-transition>
                                <div>
                                    <select name="id_forma_pago" id="id_forma_pago"
                                        class="mt-1 block w-48 border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                        <option value="">Seleccione una forma de pago</option>
                                        @foreach($formas_pagos as $forma)
                                        <option value="{{ $forma->id }}">{{ $forma->nombre }}</option>
                                        @endforeach
                                    </select>
                                    <br>
                                    <label for="comprobante" class="font-bold">Adjuntar comprobante</label>
                                    <input type="file" name="comprobante" class="mb-2">
                                    <br>
                                    <button type="submit" class="bg-green-800 hover:bg-green-600 text-white font-bold py-2 px-4 rounded ml-2">
                                        Pagar seleccionadas
                                    </button>
                                </div>

                        </div>
                        @endif
                </div>
                </form>
                <br>
                &nbsp;
                <button type="button" onclick="location.href='{{ route('clientes.cuotas.create', $cliente->slug) }}'" class="bg-gray-800 hover:bg-gray-500 text-white font-bold py-2 px-4 rounded">
                    Agregar Cuota(s)
                </button>
            </div>
        </div>
    </div>
    </div>
    <script>
        function eliminarCuota(url) {
            if (confirm('¿Está seguro de que desea eliminar esta cuota?')) {
                fetch(url, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        }
                    })
                    .then(response => response.json()) // Esperamos una respuesta JSON
                    .then(data => {
                        if (data.success) {
                            alert(data.message);
                            location.reload(); // Recarga la página para reflejar el cambio
                        } else {
                            alert('Hubo un error al eliminar la cuota');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Hubo un error al eliminar la cuota');
                    });
            }
        }
    </script>
</x-admin-layout>