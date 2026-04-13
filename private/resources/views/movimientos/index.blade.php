<x-admin-layout>
    <div class="py-4">
        <div class="">
            <div class="flex items-center justify-between mb-4">
                <a href="{{ route('portada') }}" class="text-gray-700 hover:text-gray-500">
                    <i class="fas fa-circle-left fa-2x">&nbsp;</i>
                </a>
            </div>
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-4">
                <h2 class="text-2xl font-bold mb-4">Cartola
                </h2>
                <div class="mb-4 flex items-center space-x-2 justify-start">
                    <a href="{{ route('caja.create') }}" class="bg-gray-700 hover:bg-gray-500 text-white font-bold py-2 px-4 rounded">Registrar Movimiento</a>&nbsp;
                    <a href="{{ route('caja.export.excel') }}" class="bg-gray-700 hover:bg-gray-500 text-white font-bold py-2 px-4 rounded">Exportar a Excel</a>&nbsp;
                    <a href="{{ route('caja.export.pdf') }}" class="bg-gray-700 hover:bg-gray-500 text-white font-bold py-2 px-4 rounded">Exportar a PDF</a>

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
                    <div class="mt-4 font-bold text-lg">
                        Saldo actual: <span style="color:{{ $saldo > 0 ? 'green' : 'red' }}">${{ number_format($saldo, 0, ',', '.') }}</span>
                    </div>
                    <table class="table-auto w-full mt-2 tabla_datos">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Descripción</th>
                                <th>Tipo</th>
                                <th>Monto</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($movimientos as $m)
                            <tr>
                                <td data-order="{{ \Carbon\Carbon::parse($m->fecha)->format('Y-m-d') }}">{{ \Carbon\Carbon::parse($m->fecha)->format('d/m/Y') }}</td>
                                <td>{{ $m->descripcion }}</td>
                                <td>{{ ucfirst($m->tipo) }}</td>
                                <td style="color:{{ $m->tipo == 'ingreso' ? 'green' : 'red' }}">
                                    {{ $m->tipo == 'ingreso' ? '+' : '-' }}${{ number_format($m->monto, 0, ',', '.') }}
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