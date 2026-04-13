<x-admin-layout>
    <div class="py-4">
        <div class="max-w-7xl mx-auto">
            <div class="flex items-center justify-between mb-4">
                <a href="{{ route('dashboard') }}" class="text-gray-700 hover:text-gray-500">
                    <i class="fas fa-circle-left fa-2x">&nbsp;</i>
                </a>
            </div>
            <!-- div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8" -->
            <div class="grid grid-cols-1 sm:grid-cols-5 gap-4 md:gap-6 mb-8">
                <!-- Total Clientes -->
                <div class="bg-white p-6 rounded-lg shadow text-center border-t-4 border-blue-500">
                    <div class="text-4xl font-extrabold text-blue-600 mb-2">{{ $totalClientes }}</div>
                    <div class="text-gray-700 font-semibold">Total Clientes</div>
                </div>
                <!-- Clientes al día -->
                <div class="bg-green-100 p-6 rounded-lg shadow text-center border-t-4 border-green-500">
                    <div class="text-4xl font-extrabold text-green-600 mb-2">{{ $clientesAlDia }}</div>
                    <div class="text-gray-700 font-semibold">Clientes al día</div>
                </div>
                <!-- Clientes morosos -->
                <div class="bg-red-100 p-6 rounded-lg shadow text-center border-t-4 border-red-500">
                <a href="{ route('clientes.morosos') }}" class="">
                    <div class="text-4xl font-extrabold text-red-600 mb-2">{{ $clientesMorosos }}</div>
                    <div class="text-gray-700 font-semibold">Clientes morosos qwewqewq</div>
                </a>
                </div>
                <div class="bg-white p-6 rounded-lg shadow border-t-4 border-black">
                    <h2 class="text-lg font-bold mb-4 text-gray-800">Clientes por género</h2>
                    <ul>
                        @foreach($clientesPorGenero as $genero => $cantidad)
                        <li>{{ $genero }}: <span class="font-bold">{{ $cantidad }}</span></li>
                        @endforeach
                    </ul>
                </div>
                <div class="bg-white p-6 rounded-lg shadow  border-t-4 border-gray-800">
                    <h2 class="text-lg font-bold mb-4 text-gray-800">Clientes por rango de edad</h2>
                    <ul>
                        @foreach($clientesPorEdad as $rango => $cantidad)
                        <li>{{ $rango }}: <span class="font-bold">{{ $cantidad }}</span></li>
                        @endforeach
                    </ul>
                </div>
            </div>
            <form method="GET" class="mb-4">
                <label for="anio" class="mr-2 font-bold">Año:</label>
                <select name="anio" id="anio" onchange="this.form.submit()" class="border rounded px-2 py-1">
                    @foreach($anios as $anio)
                    <option value="{{ $anio }}" {{ $añoSeleccionado == $anio ? 'selected' : '' }}>{{ $anio }}</option>
                    @endforeach
                </select>
            </form>

            <div class="w-full grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="bg-white p-2 rounded-lg shadow mb-8" style="height: 400px; width: 100%;">
                    <h2 class="text-lg font-bold mb-4 text-gray-800">Ingresos mensuales ({{ $añoSeleccionado }})</h2>
                    <canvas id="ingresosChart"></canvas>
                </div>
                <div class="bg-white p-2 rounded-lg shadow mb-8" style="height: 400px; width: 100%;">
                    <h2 class="text-lg font-bold mb-4 text-gray-800">Formas de pago mensuales ({{ $añoSeleccionado }})</h2>
                    <canvas id="formasPagoMensualChart"></canvas>
                </div>
            </div>

            <div class="w-full grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="bg-white p-6 rounded-lg shadow mb-8">
                    <h2 class="text-lg font-bold mb-4 text-gray-800">Horarios peak</h2>
                    <canvas id="picosHorariosChart" height="80"></canvas>
                </div>
                <div class="bg-white p-6 rounded-lg shadow mb-8">
                    <h2 class="text-lg font-bold mb-4 text-gray-800">Cantidad de clientes por tipo de plan</h2>
                    <canvas id="clientesTipoPlanChart" height="80"></canvas>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Ingresos mensuales

        const ingresosMeses = {
            !!json_encode(collect($ingresosMensuales) - > pluck('mes')) !!
        };
        const ingresosTotales = {
            !!json_encode(collect($ingresosMensuales) - > pluck('total')) !!
        };
        const ingresosProyectados = {
            !!json_encode(collect($ingresosProyectados) - > pluck('total')) !!
        };

        new Chart(document.getElementById('ingresosChart').getContext('2d'), {
            type: 'bar',
            data: {
                labels: ingresosMeses,
                datasets: [{
                        label: 'Ingresos reales',
                        data: ingresosTotales,
                        backgroundColor: 'rgba(59, 130, 246, 0.5)',
                        borderColor: 'rgba(59, 130, 246, 1)',
                        borderWidth: 1
                    },
                    {
                        label: 'Ingresos proyectados',
                        data: ingresosProyectados,
                        backgroundColor: 'rgba(16, 185, 129, 0.3)',
                        borderColor: 'rgba(16, 185, 129, 1)',
                        borderWidth: 1
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        // Planes por tipo
        // const planesTipos = json_encode($planesPorTipo->pluck('tipo')) ;
        //     const planesCantidades = json_encode($planesPorTipo->pluck('cantidad'));
        //     new Chart(document.getElementById('planesChart').getContext('2d'), {
        //         type: 'pie',
        //         data: {
        //             labels: planesTipos,
        //             datasets: [{
        //                 label: 'Cantidad',
        //                 data: planesCantidades,
        //                 backgroundColor: [
        //                     'rgba(59, 130, 246, 0.5)',
        //                     'rgba(16, 185, 129, 0.5)',
        //                     'rgba(239, 68, 68, 0.5)',
        //                     'rgba(245, 158, 11, 0.5)'
        //                 ]
        //             }]
        //         },
        //         options: { responsive: true }
        //     });

        // Formas de pago: cantidad y monto
        // Datos para el gráfico de formas de pago mensual
        const meses = {
            !!json_encode($meses) !!
        };
        const formasPagoNombres = {
            !!json_encode(array_keys($formasPagoMensual)) !!
        };
        const formasPagoDatasets = [];

        @foreach($formasPagoMensual as $nombre => $valores)
        formasPagoDatasets.push({
            label: "{{ $nombre }}",
            data: {
                !!json_encode(array_values($valores)) !!
            },
            borderWidth: 2,
            fill: false,
            // Puedes personalizar los colores aquí si lo deseas
        });
        @endforeach

        new Chart(document.getElementById('formasPagoMensualChart').getContext('2d'), {
            type: 'bar',
            data: {
                labels: meses,
                datasets: formasPagoDatasets
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        display: true
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
        const picosLabels = {
            !!json_encode(array_keys($picosHorarios)) !!
        };
        const picosData = {
            !!json_encode(array_values($picosHorarios)) !!
        };
        new Chart(document.getElementById('picosHorariosChart').getContext('2d'), {
            type: 'line',
            data: {
                labels: picosLabels,
                datasets: [{
                    label: 'Cantidad de agendas',
                    data: picosData,
                    borderColor: 'rgba(59,130,246,1)',
                    backgroundColor: 'rgba(59,130,246,0.2)',
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true
            }
        });
        const nombrePlanLabels = {
            !!json_encode($clientesPorNombrePlan - > keys()) !!
        };
        const nombrePlanData = {
            !!json_encode($clientesPorNombrePlan - > values()) !!
        };
        new Chart(document.getElementById('clientesTipoPlanChart').getContext('2d'), {
            type: 'pie',
            data: {
                labels: nombrePlanLabels,
                datasets: [{
                    data: nombrePlanData,
                    backgroundColor: [
                        'rgba(59,130,246,0.5)',
                        'rgba(16,185,129,0.5)',
                        'rgba(239,68,68,0.5)',
                        'rgba(245,158,11,0.5)'
                    ]
                }]
            },
            options: {
                responsive: true
            }
        });
    </script>
</x-admin-layout>