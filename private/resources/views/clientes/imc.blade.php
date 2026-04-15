<x-admin-layout>
    <div class="py-4">
        <div class="">
            <div class="flex items-center justify-between mb-4 bg-white p-6 rounded-lg">
                <div class="text-gray-700">
                    <i class="fas fa-user fa-2x">&nbsp;{{ $cliente->nombres }} {{ $cliente->paterno }} {{ $cliente->materno }}</i>
                    <br><small>{{ $cliente->plan->nombre ?? 'Sin plan' }}</small>
                </div>
                @if(in_array((int) Auth::user()->id_tipo_usuario, [1, 2, 10], true))
                <a href="{{ route('clientes.opciones.portada', $cliente->slug) }}" class="inline-flex items-center gap-2 rounded-lg bg-gray-800 px-3 py-2 text-sm font-semibold text-white hover:bg-gray-600">
                    <i class="fas fa-arrow-left"></i>
                    Volver
                </a>
                @endif
            </div>
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h2 class="text-lg font-semibold">Peso del Cliente</h2>
                    <div class="mt-4">
                        <p><strong>Fecha de Registro:</strong> {{ $imcReciente->created_at->format('d/m/Y') }}</p>
                        <p><strong>IMC Actual:</strong> {{ $imcReciente->imc }} </p>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-6">
                        <!-- Gráfico de evolución del peso -->
                        <div class="bg-white rounded-lg shadow p-4 flex items-center justify-center h-72 w-full">
                            <canvas id="graficoIMC"></canvas>
                        </div>
                        <!-- Tabla de pesos -->
                        <div id="tablaPesos" class="bg-white rounded-lg shadow p-4 w-full">
                            <h3 class="text-md font-semibold">Historial de IMC</h3>
                            @if($imcs->isEmpty())
                            <p>No hay registros de IMC.</p>
                            @else
                            <table id="tablaDatos" class="min-w-full divide-y divide-gray-200">
                                <thead>
                                    <tr>
                                        <th style="text-align: center;">Fecha</th>
                                        <th style="text-align: center;">IMC</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($imcs as $imc)
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap" data-order="{{ \Carbon\Carbon::parse($imc->created_at)->format('Y-m-d') }}">{{ $imc->created_at->format('d/m/Y') }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap">{{ $imc->imc }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                            @endif
                        </div>
                    </div>
                    <form action="{{ route('clientes.imcs.store', $cliente->slug) }}" method="POST" class="mb-6">
                        @csrf
                        <div class="flex flex-col sm:flex-row gap-4 items-end">
                            <div>
                                <label for="peso" class="block text-sm font-medium text-gray-700">Nuevo IMC</label>
                                <input type="number" step="0.01" name="imc" id="imc" required
                                    class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                            </div>
                            <button type="submit" class="bg-green-600 hover:bg-green-800 text-white font-bold py-2 px-4 rounded">
                                Registrar IMC
                            </button>

                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script id="graficoImcData" type="application/json">
        @json($imcChartData)
    </script>
    <script>
        const ctx = document.getElementById('graficoIMC').getContext('2d');
        const imcChartData = JSON.parse(document.getElementById('graficoImcData').textContent);
        const labels = imcChartData.labels ?? [];
        const data = imcChartData.values ?? [];

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