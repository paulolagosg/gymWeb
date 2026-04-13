<x-admin-layout>
    <div class="py-4">
        <div class="">
            <div class="flex items-center justify-between mb-4 bg-white p-6 rounded-lg text-center">
                <a href="{{ route('clientes.opciones.portada', $cliente->slug) }}" class="text-gray-700 hover:text-gray-500">
                    <i class="fas fa-circle-left fa-2x">&nbsp;{{ $cliente->nombres }} {{ $cliente->paterno }} {{ $cliente->materno }}</i>
                </a>
            </div>
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h2 class="text-lg font-semibold">Peso del Cliente</h2>
                    <div class="mt-4">
                        <p><strong>Fecha de Registro:</strong> {{ $pesoReciente->created_at->format('d/m/Y') }}</p>
                        <p><strong>Peso Actual:</strong> {{ $pesoReciente->peso }} kg</p>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-6">
                        <!-- Gráfico de evolución del peso -->
                        <div class="bg-white rounded-lg shadow p-4 flex items-center justify-center h-72 w-full">
                            <canvas id="graficoPeso"></canvas>
                        </div>
                        <!-- Tabla de pesos -->
                        <div id="tablaPesos" class="bg-white rounded-lg shadow p-4 w-full">
                            <h3 class="text-md font-semibold">Historial de Pesos</h3>
                            @if($pesos->isEmpty())
                            <p>No hay registros de peso.</p>
                            @else
                            <table id="tablaDatos" class="min-w-full divide-y divide-gray-200">
                                <thead>
                                    <tr>
                                        <th style="text-align: center;">Fecha</th>
                                        <th style="text-align: center;">Peso (kg)</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($pesos as $peso)
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap" data-order="{{ \Carbon\Carbon::parse($peso->created_at)->format('Y-m-d') }}">{{ $peso->created_at->format('d/m/Y') }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap">{{ $peso->peso }} kg</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                            @endif
                        </div>
                    </div>
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
                    <form action="{{ route('clientes.pesos.store', $cliente->slug) }}" method="POST" class="mb-6">
                        @csrf
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 md:gap-6">
                            <div class="mb-4">
                                <label for="peso" class="block text-sm font-medium text-gray-700">Nuevo Peso (kg)</label>
                                <input type="number" step="0.01" name="peso" id="peso" required
                                    class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                            </div>
                            <div class="mb-4">
                                <label for="fecha" class="block text-sm font-medium text-gray-700">Fecha</label>
                                <input type="date" name="created_at" id="created_at" value="{{ now()->format('Y-m-d') }}" required
                                    class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                            </div>
                        </div>

                        <div>
                            <button type="submit" class="bg-green-600 hover:bg-green-800 text-white font-bold py-2 px-4 rounded">
                                Registrar Peso
                            </button>
                            <button type="button" onclick="location.href='{{ route('clientes.opciones.portada', $cliente->slug) }}'" class="bg-red-500 hover:bg-red-800 text-white font-bold py-2 px-4 rounded ml-2">
                                Cancelar
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        const ctx = document.getElementById('graficoPeso').getContext('2d');
        const labels = {
            !!json_encode($pesos - > sortBy('created_at') - > pluck('created_at') - > map(fn($d) => \Carbon\ Carbon::parse($d) - > format('d/m/Y'))) !!
        };
        const data = {
            !!json_encode($pesos - > sortBy('created_at') - > pluck('peso')) !!
        };

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Peso (kg)',
                    data: data,
                    borderColor: 'rgba(59,130,246,1)',
                    backgroundColor: 'rgba(59,130,246,0.2)',
                    fill: true,
                    tension: 0.4,
                    pointRadius: 4,
                    pointBackgroundColor: 'rgba(59,130,246,1)'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: false
                    }
                }
            }
        });
    </script>
</x-admin-layout>