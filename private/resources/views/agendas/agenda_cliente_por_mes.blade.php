<x-admin-layout>
    <div class="py-4">
        <div class="max-w-7xl mx-auto">
            <div class="flex items-center justify-between mb-4 bg-white p-6 rounded-lg text-center">
                <a href="{{ route('clientes.opciones.portada', $cliente->slug) }}" class="text-gray-700 hover:text-gray-500">
                    <i class="fas fa-circle-left fa-2x">&nbsp;{{ $cliente->nombres }} {{ $cliente->paterno }} {{ $cliente->materno }}</i>
                </a>
            </div>
            <div class="bg-white p-6 rounded-lg shadow mb-6">
                <form method="GET" class="flex items-center gap-4">
                    <label for="ano" class="text-gray-700 font-semibold">Seleccionar Año:</label>
                    <select name="ano" id="ano" class="px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        @foreach($anosDisponibles as $anoDisponible)
                        <option value="{{ $anoDisponible }}" {{ $anoDisponible == $ano ? 'selected' : '' }}>
                            {{ $anoDisponible }}
                        </option>
                        @endforeach
                    </select>
                    <button type="submit" class="bg-gray-800 hover:bg-gray-500 text-white font-bold py-1 px-2 rounded">
                        Filtrar
                    </button>
                </form>
            </div>

            <!-- Tabla de agendas -->
            <div class="bg-white rounded-lg shadow overflow-x-auto">
                <table class="w-full border-collapse">
                    <thead>
                        <tr class="bg-gray-100 border-b-2 border-gray-300">
                            <th class="px-4 py-3 text-left font-semibold text-gray-700 border">Mes</th>
                            @foreach($estados as $id => $nombre)
                            <th class="px-4 py-3 text-center font-semibold text-gray-700 border">
                                {{ $nombre }}
                            </th>
                            @endforeach
                            <th class="px-4 py-3 text-center font-semibold text-gray-700 border bg-gray-50">
                                Total
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($tablaAgendas as $fila)
                        <tr class="border-b border-gray-200 hover:bg-gray-50 transition">
                            <td class="px-4 py-3 font-semibold text-gray-800 border">
                                {{ $fila['mes'] }}
                            </td>

                            @foreach($estados as $id => $nombre)
                            <td class="px-4 py-3 text-center border">
                                <a href="{{ route('clientes.agenda',$cliente->slug) }}"><span class="inline-flex items-center justify-center px-3 py-1 rounded-full text-sm font-semibold"
                                        style="background-color:
                                            @if($fila[$nombre] > 0)
                                                @if($nombre === 'Agendado')
                                                    #10b981;color:#fff;
                                                @elseif($nombre === 'Cancelado sin recuperación')
                                                    #ef4444;color:#333;
                                                @elseif($nombre === 'Cancelado con recuperación')
                                                    #ef4444;color:#333;
                                                @elseif($nombre === 'Realizado')
                                                    #6366f1;color:#fff;
                                                @elseif($nombre === 'Reagendado')
                                                    #f59e0b;color:#fff;
                                                @endif
                                            @else
                                                bg-gray-100 text-gray-600
                                            @endif
                                            ">
                                        {{ $fila[$nombre] }}
                                    </span></a>
                            </td>
                            @endforeach

                            <td class="px-4 py-3 text-center border bg-gray-50 font-bold text-gray-900">
                                {{ $fila['total'] }}
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="{{ count($estados) + 2 }}" class="px-4 py-6 text-center text-gray-500">
                                No hay agendas registradas para este año.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>

                    <!-- Fila de totales -->
                    <tfoot>
                        <tr class="bg-gray-100 border-t-2 border-gray-300 font-bold">
                            <td class="px-4 py-3 text-gray-800 border">Total Año {{ $ano }}</td>
                            @foreach($estados as $id => $nombre)
                            @php
                            $totalEstado = collect($tablaAgendas)->sum($nombre);
                            @endphp
                            <td class="px-4 py-3 text-center border text-gray-800">
                                {{ $totalEstado }}
                            </td>
                            @endforeach
                            @php
                            $granTotal = collect($tablaAgendas)->sum('total');
                            @endphp
                            <td class="px-4 py-3 text-center border bg-gray-200 text-gray-900">
                                {{ $granTotal }}
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <!-- Resumen estadístico -->
            <div class="mt-6 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
                @foreach($estados as $id => $nombre)
                @php
                $total = collect($tablaAgendas)->sum($nombre);
                $color = match($nombre) {
                'Agendado' => 'background-color: #10b981; color: #fff;',
                'Cancelado sin recuperación' => 'background-color: #ef4444; color: #333;',
                'Cancelado con recuperación' => 'background-color: #ef4444; color: #333;',
                'Realizado' => 'background-color: #6366f1; color: #fff;',
                'Reagendado' => 'background-color: #f59e0b; color: #fff;',
                default => 'background-color: #d2d2d2; color: #000;'
                };
                @endphp
                <div class="p-4 rounded-lg border-2" style=" {{ $color }}">
                    <p class="text-sm font-semibold opacity-75">{{ $nombre }}</p>
                    <p class="text-3xl font-bold">{{ $total }}</p>
                </div>
                @endforeach
            </div>
        </div>
    </div>

    <style>
        table {
            border-collapse: collapse;
        }

        thead tr {
            background-color: #f3f4f6;
        }

        tbody tr:hover {
            background-color: #f9fafb;
        }

        @media (max-width: 768px) {
            .px-4 {
                padding-left: 0.5rem;
                padding-right: 0.5rem;
            }

            .text-sm {
                font-size: 0.75rem;
            }
        }
    </style>
</x-admin-layout>