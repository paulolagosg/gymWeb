<x-admin-layout>
    <div class="py-4">
        <div class="">
            <div class="flex items-center justify-between mb-4 bg-white p-6 rounded-lg ">
                <a href="{{ route('clientes.opciones.portada', $cliente->slug) }}" class="text-gray-700 hover:text-gray-500">
                    <i class="fas fa-circle-left fa-2x">&nbsp;{{ $cliente->nombres }} {{ $cliente->paterno }} {{ $cliente->materno }}</i>
                    <br><small>{{$cliente->plan->nombre}}</small>
                </a>
            </div>
            @if(empty($datosEjercicios))
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <p class="text-gray-500 text-center">No hay datos de ejercicios registrados para este cliente.</p>
            </div>
            @else
            <!-- Selector de ejercicios -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 overflow-x-auto w-full">
                    <h2 class="font-semibold text-xl text-gray-800 leading-tight mb-4">
                        Evolución de Ejercicios
                    </h2>
                    <div class="mb-4 flex items-center gap-4">
                        <label for="ejercicio-selector" class="block text-sm font-medium text-gray-700">Selecciona ejercicio:</label>
                        <select id="ejercicio-selector" class="mt-1 block w-48 border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                            @foreach($datosEjercicios as $nombreEjercicio => $datos)
                            <option value="{{ str_replace(' ', '_', $nombreEjercicio) }}" {{ $loop->first ? 'selected' : '' }}>
                                {{ $nombreEjercicio }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                    <button type="button" class="bg-gray-800 hover:bg-gray-500 text-white font-bold py-1 px-2 rounded" onclick="window.open('{{route('clientes.evolucion_ejercicios.pdf', $cliente->slug)}}', '_blank')">Ver PDF</button>
                </div>

                <!-- Contenido de gráficos -->
                <div class="p-6">
                    @foreach($datosEjercicios as $nombreEjercicio => $datos)
                    <div id="grafico-{{ str_replace(' ', '_', $nombreEjercicio) }}" class="grafico-container {{ $loop->first ? '' : 'hidden' }}">
                        <h2 class="text-xl font-semibold text-gray-800 mb-4">{{ $nombreEjercicio }}</h2>

                        <!-- Gráfico de Carga -->
                        <div class="mb-8">
                            <h3 class="text-lg font-medium text-gray-700 mb-3">Evolución de Carga (kg)</h3>
                            <div style="position: relative; height: 300px;">
                                <canvas id="canvas-carga-{{ str_replace(' ', '_', $nombreEjercicio) }}"></canvas>
                            </div>
                        </div>

                        <!-- Gráfico de Repeticiones -->
                        <div class="mb-8">
                            <h3 class="text-lg font-medium text-gray-700 mb-3">Evolución de Repeticiones</h3>
                            <div style="position: relative; height: 300px;">
                                <canvas id="canvas-reps-{{ str_replace(' ', '_', $nombreEjercicio) }}"></canvas>
                            </div>
                        </div>

                        <!-- Tabla de detalles -->
                        <div class="mt-8 overflow-x-auto">
                            <h3 class="text-lg font-medium text-gray-700 mb-3">Detalles del Registro</h3>
                            <table class="min-w-full divide-y divide-gray-200 border border-gray-300">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-2 text-left text-sm font-semibold text-gray-900">Fecha</th>
                                        <th class="px-4 py-2 text-left text-sm font-semibold text-gray-900">Carga (kg)</th>
                                        <th class="px-4 py-2 text-left text-sm font-semibold text-gray-900">Series</th>
                                        <th class="px-4 py-2 text-left text-sm font-semibold text-gray-900">Repeticiones</th>
                                        <th class="px-4 py-2 text-left text-sm font-semibold text-gray-900">Método</th>
                                        <th class="px-4 py-2 text-left text-sm font-semibold text-gray-900">Progresión</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200">
                                    @for($i = 0; $i < count($datos['fechas']); $i++)
                                        <tr class="hover:bg-gray-50">
                                        <td class="px-4 py-2 text-sm text-gray-900">
                                            {{ \Carbon\Carbon::createFromFormat('Y-m-d', $datos['fechas'][$i])->format('d/m/Y') }}
                                        </td>
                                        <td class="px-4 py-2 text-sm text-gray-900 font-medium">
                                            {{ $datos['cargas'][$i] }}
                                        </td>
                                        <td class="px-4 py-2 text-sm text-gray-900">
                                            {{ $datos['series'][$i] }}
                                        </td>
                                        <td class="px-4 py-2 text-sm text-gray-900">
                                            {{ $datos['repeticiones'][$i] }}
                                        </td>
                                        <td class="px-4 py-2 text-sm text-gray-900">
                                            {{ $datos['metodos'][$i] }}
                                        </td>
                                        <td class="px-4 py-2 text-sm text-gray-900">
                                            {{ $datos['progresiones'][$i] }}
                                        </td>
                                        </tr>
                                        @endfor
                                </tbody>
                            </table>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif
        </div>
    </div>

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        const datosEjercicios = {
            !!json_encode($datosEjercicios) !!
        };
        const chartInstances = {};

        function formatearFecha(fecha) {
            const date = new Date(fecha + 'T00:00:00');
            return date.toLocaleDateString('es-ES', {
                day: '2-digit',
                month: '2-digit',
                year: 'numeric'
            });
        }

        function generarColores(cantidad) {
            const colores = [
                '#3B82F6', // Azul
                '#10B981', // Verde
                '#F59E0B', // Ámbar
                '#EF4444', // Rojo
                '#8B5CF6', // Púrpura
                '#EC4899', // Rosa
                '#14B8A6', // Turquesa
                '#F97316', // Naranja
            ];
            return colores.slice(0, cantidad);
        }

        function mostrarGrafico(nombreEjercicio) {
            // Ocultar todos los gráficos
            document.querySelectorAll('.grafico-container').forEach(el => {
                el.classList.add('hidden');
            });

            // Mostrar el gráfico seleccionado
            const container = document.getElementById(`grafico-${nombreEjercicio}`);
            if (container) {
                container.classList.remove('hidden');
            }

            // Crear gráficos si no existen
            const datos = datosEjercicios[nombreEjercicio.replace(/_/g, ' ')];
            if (datos) {
                crearGraficoCarga(nombreEjercicio, datos);
                crearGraficoRepeticiones(nombreEjercicio, datos);
            }
        }

        function crearGraficoCarga(nombreEjercicio, datos) {
            const canvasId = `canvas-carga-${nombreEjercicio}`;
            const canvas = document.getElementById(canvasId);

            if (!canvas) return;

            // Destruir gráfico anterior si existe
            if (chartInstances[canvasId]) {
                chartInstances[canvasId].destroy();
            }

            const ctx = canvas.getContext('2d');
            chartInstances[canvasId] = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: datos.fechas.map(f => formatearFecha(f)),
                    datasets: [{
                        label: 'Carga (kg)',
                        data: datos.cargas,
                        borderColor: '#3B82F6',
                        backgroundColor: 'rgba(59, 130, 246, 0.1)',
                        borderWidth: 3,
                        fill: true,
                        tension: 0.4,
                        pointRadius: 6,
                        pointBackgroundColor: '#3B82F6',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2,
                        pointHoverRadius: 8,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: true,
                            labels: {
                                font: {
                                    size: 12
                                },
                                padding: 20,
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                font: {
                                    size: 11
                                }
                            }
                        },
                        x: {
                            ticks: {
                                font: {
                                    size: 11
                                }
                            }
                        }
                    }
                }
            });
        }

        function crearGraficoRepeticiones(nombreEjercicio, datos) {
            const canvasId = `canvas-reps-${nombreEjercicio}`;
            const canvas = document.getElementById(canvasId);

            if (!canvas) return;

            // Destruir gráfico anterior si existe
            if (chartInstances[canvasId]) {
                chartInstances[canvasId].destroy();
            }

            const ctx = canvas.getContext('2d');
            chartInstances[canvasId] = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: datos.fechas.map(f => formatearFecha(f)),
                    datasets: [{
                        label: 'Repeticiones',
                        data: datos.repeticiones,
                        backgroundColor: 'rgba(16, 185, 129, 0.7)',
                        borderColor: '#10B981',
                        borderWidth: 2,
                        borderRadius: 4,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: true,
                            labels: {
                                font: {
                                    size: 12
                                },
                                padding: 20,
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                font: {
                                    size: 11
                                }
                            }
                        },
                        x: {
                            ticks: {
                                font: {
                                    size: 11
                                }
                            }
                        }
                    }
                }
            });
        }

        // Inicializar gráficos al cargar
        document.addEventListener('DOMContentLoaded', function() {
            if (Object.keys(datosEjercicios).length > 0) {
                const primerEjercicio = Object.keys(datosEjercicios)[0];
                mostrarGrafico(primerEjercicio.replace(/ /g, '_'));
            }

            // Agregar event listener al selector
            const selector = document.getElementById('ejercicio-selector');
            if (selector) {
                selector.addEventListener('change', function() {
                    mostrarGrafico(this.value);
                });
            }
        });
    </script>
</x-admin-layout>