<x-admin-layout>
    <div class="py-4">
        <div class="max-w-7xl mx-auto">
            <div class="flex items-center justify-between mb-4">
                <a href="{{ route('portada') }}" class="text-gray-700 hover:text-gray-500">
                <i class="fas fa-circle-left fa-2x">&nbsp;</i>
                </a>
            </div>
            <!-- div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8" -->
            <div class="grid grid-cols-1 sm:grid-cols-5 gap-4 md:gap-6 mb-8">
                <!-- Total Clientes -->
                <div class="bg-white p-6 rounded-lg shadow text-center border-t-4 border-blue-500">
                	<a href="{{ route('clientes.index') }}" class="">
                   		<div class="text-xl text-gray-700 font-semibold">Total<br>Clientes</div>
                    	<div class="font-extrabold text-blue-600 mt-4" style="font-size:xxx-large">{{ $totalClientes }}</div>
                   		<!--div class="flex  justify-center">
	                    	<img src="/iconos/usuarios.png" >
                    	</div-->
                   	</a>
                </div>
                <!-- Clientes al día -->
                <div class="bg-green-100 p-6 rounded-lg shadow text-center border-t-4 border-green-500">
                    <div class="text-xl text-gray-700 font-semibold">Clientes<br>al día</div>
                    <div class="font-extrabold text-green-600 mt-4" style="font-size:xxx-large">{{ $clientesAlDia }}</div>
                    	<!--div class="flex  justify-center">
	                    	<img src="/iconos/aldia.png" >
                    	</div-->
                </div>
                <!-- Clientes morosos -->
                <div class="bg-red-100 p-6 rounded-lg shadow text-center border-t-4 border-red-500">
                	<a href="{{ route('clientes.morosos') }}" class="">
                    	<div class="text-xl text-gray-700 font-semibold">Clientes<br>morosos</div>
                    	<div class="font-extrabold text-red-600 mt-4" style="font-size:xxx-large">{{ $clientesMorosos }}</div>
                    	<!--div class="flex  justify-center">
                    		<img src="/iconos/morosos.png" >
                    	</div-->
					</a>
                </div>
                <div class="bg-white p-6 rounded-lg shadow border-t-4 border-black">
                    <h2 class="text-xl  font-bold mb-4 text-gray-800 ">Clientes por género</h2>
                    <ul>
                        @foreach($clientesPorGenero as $genero => $cantidad)
                        <li>{{ $genero }}: <span class="font-bold">{{ $cantidad }}</span></li>
                        @endforeach
                    </ul>
                    <!--div class="flex  justify-center">
	                    <img src="/iconos/genero.png" >
                    </div-->
                </div>
                <div class="bg-white p-6 rounded-lg shadow  border-t-4 border-gray-800">
                    <h2 class="text-xl font-bold mb-4 text-gray-800">Clientes por rango de edad</h2>
                    <ul>
                        @foreach($clientesPorEdad as $rango => $cantidad)
                        <li>{{ $rango }}: <span class="font-bold">{{ $cantidad }}</span></li>
                        @endforeach
                    </ul>
                    <!--div class="flex  justify-center">
	                    <img src="/iconos/edad.png" >
                    </div-->
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
			@if(auth()->user()->id_tipo_usuario == 1)
            <div class="w-full grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="bg-white p-2 rounded-lg shadow mb-8" style="height: 400px; width: 100%;">
                    <h2 class="text-lg font-bold mb-4 text-gray-800">Ingresos por concepto de clientes ({{ $añoSeleccionado }})</h2>
                    <canvas id="ingresosChart"></canvas>
                </div>
               
                <div class="bg-white p-2 rounded-lg shadow mb-8" style="height: 400px; width: 100%;">
                    <h2 class="text-lg font-bold mb-4 text-gray-800">Formas de pago mensuales por concepto de clientes ({{ $añoSeleccionado }})</h2>
                    <canvas id="formasPagoMensualChart"></canvas>
                </div>
            </div>
            @else
            <div class="w-full grid grid-cols-1 gap-4">
                <div class="bg-white p-2 rounded-lg shadow mb-8" style="height: 400px; width: 100%;">
                    <h2 class="text-lg font-bold mb-4 text-gray-800">Ingresos por concepto de clientes ({{ $añoSeleccionado }})</h2>
                    <canvas id="ingresosChart"></canvas>
                </div>
                <div class="bg-white p-2 rounded-lg shadow mb-8" style="height: 400px; width: 100%; display:none">
                    <h2 class="text-lg font-bold mb-4 text-gray-800">Formas de pago mensuales por concepto de clientes ({{ $añoSeleccionado }})</h2>
                    <canvas id="formasPagoMensualChart"></canvas>
                </div>
            </div>
            @endif

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
            @if(auth()->user()->id_tipo_usuario == 1 or auth()->user()->id_clasificacion == 3)
            <div class="w-full grid grid-cols-1 md:grid-cols-1 gap-4">
                <div class="bg-white p-2 rounded-lg shadow mb-8" style="height: 400px;">
                    <h2 class="text-lg font-bold mb-4 text-gray-800">Clientes por Entrenador ({{ $añoSeleccionado }})</h2>
                    <canvas id="clientesEntrenador"></canvas>
                </div>
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                	<div class="p-6 text-gray-900 overflow-x-auto w-full">
		                <table class="table display dataTable tabla_datos">
						    <thead>
						        <tr>
						            <th rowspan="2">Entrenador</th>
						            <th colspan="10">Meses</th>
						            <th colspan="2"><small>N = Clientes nuevos<br/>F = Clientes terminan su plan<br/>B = Clientes dados de baja</small></th>
						        </tr>
						        <tr>
						            @foreach(['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'] as $mes)
					                <th>{{ $mes }}</th>
						            @endforeach
						        </tr>
						    </thead>
						    <tbody>
					        @foreach($reporte->groupBy('usuario_name') as $entrenador => $registros)
					            <tr>
					                <td>{{ $entrenador }}</td>
					                @for($i = 1; $i <= 12; $i++)
				                    @php
                				        $mesData = $registros->firstWhere('mes', $i);
				                    @endphp
                				    <td>
				                        N: {{ $mesData['clientes_nuevos'] ?? 0 }}<br>
				                        F: {{ $mesData['clientes_fin_membresia'] ?? 0 }}<br>
				                        B: {{ $mesData['clientes_baja'] ?? 0 }}
                				    </td>
				                @endfor
					            </tr>
				        @endforeach
					    	</tbody>
						</table>
               		</div>
            	</div>
            </div>
            @else
            <div class="w-full grid grid-cols-1 md:grid-cols-1 gap-4">
                <div class="bg-white p-2 rounded-lg shadow mb-8" style="display:none;">
                    <h2 class="text-lg font-bold mb-4 text-gray-800">Clientes por Entrenador ({{ $añoSeleccionado }})</h2>
                    <canvas id="clientesEntrenador"></canvas>
                </div>
                <!--div class="bg-white p-2 rounded-lg shadow mb-8" style="height: 400px; width: 100%;">
                    <h2 class="text-lg font-bold mb-4 text-gray-800">Tasa de retención mensual por entrenador (%) ({{ $añoSeleccionado }})</h2>
                    <canvas id="graficoRetencion"></canvas>
                </div -->
            </div>
            @endif
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
			@foreach($servqualAverages as $label => $promedio)
    			<div class="mb-2">
        			<span class="font-bold">{{ $servqualLabelNames[$label] ?? $label }}:</span>
        			@if($promedio)
            			<span>{{ $promedio }}/5</span>
            			<span>
                			@for($i = 1; $i <= 5; $i++)
                    			@if($promedio >= $i)
                        			<i class="fa fa-star text-yellow-400" style="color:#EFB810"></i>
                    			@elseif($promedio > $i-1)
                        			<i class="fa fa-star-half-alt text-yellow-400" style="color:yellow"></i>
                    			@else
                        			<i class="fa fa-star text-gray-300"></i>
                    			@endif
                			@endfor
            			</span>
            
       				@else
            			<span class="text-gray-400">Sin respuestas</span>
        			@endif
        			<div class="text-sm mt-1">
            		@foreach($servqualSummary[$label] as $valor => $cantidad)
               			<span class="mr-2">
                    		{{ $servqualValueNames[$valor] ?? $valor }}: {{ $cantidad }}
                		</span>
            		@endforeach
       				</div>
    			</div>
			@endforeach
    		</div>
    		</div>

    <h3 class="font-semibold mt-8 mb-2">Nube de palabras (respuestas abiertas)</h3>
    <div id="wordcloud" class="bg-gray-50 p-4 rounded shadow" style="min-height:200px"></div>

    <script>
        // Generar la nube de palabras solo con CSS y JS simple
        const words = @json($topWords);
        const container = document.getElementById('wordcloud');
        let max = 1;
        for (let w in words) if (words[w] > max) max = words[w];
        Object.entries(words).forEach(([word, count]) => {
            const span = document.createElement('span');
            span.textContent = word + ' ';
            span.style.fontSize = (0.8 + (2 * count / max)) + 'em';
            span.style.margin = '0 6px';
            span.style.display = 'inline-block';
            span.style.color = `hsl(${Math.floor(200 + 120 * Math.random())},70%,40%)`;
            container.appendChild(span);
        });
    </script>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/chartjs-plugin-datalabels/2.2.0/chartjs-plugin-datalabels.min.js" 
		integrity="sha512-JPcRR8yFa8mmCsfrw4TNte1ZvF1e3+1SdGMslZvmrzDYxS69J7J49vkFL8u6u8PlPJK+H3voElBtUCzaXj+6ig==" 
		crossorigin="anonymous" referrerpolicy="no-referrer">
	</script>
    <script>
        // Ingresos mensuales

        const ingresosMeses = {!!json_encode(collect($ingresosMensuales)->pluck('mes')) !!};
        const ingresosTotales = {!!json_encode(collect($ingresosMensuales)->pluck('total')) !!};
        //const ingresosProyectados = {!!json_encode(collect($ingresosProyectados)->pluck('total')) !!};
        const ingresosProyectados = {!!json_encode(collect($ingresosProyectados)->pluck('total')) !!};

        new Chart(document.getElementById('ingresosChart').getContext('2d'), {
            type: 'bar',
            data: {
                labels: ingresosMeses,
                datasets: [/*{
                        label: 'Ingresos reales',
                        data: ingresosTotales,
                        backgroundColor: 'rgba(59, 130, 246, 0.5)',
                        borderColor: 'rgba(59, 130, 246, 1)',
                        borderWidth: 1
                    },*/
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
        const meses = {!!json_encode($meses) !!};
        const formasPagoNombres = {!!json_encode(array_keys($formasPagoMensual)) !!};
        const formasPagoDatasets = [];

        @foreach($formasPagoMensual as $nombre => $valores)
        formasPagoDatasets.push({
            label: "{{ $nombre }}",
            data: {!!json_encode(array_values($valores)) !!},
            borderWidth: 2,
            fill: false,
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
        const picosLabels = {!!json_encode(array_keys($picosHorarios)) !!};
        const picosData = {!!json_encode(array_values($picosHorarios)) !!};
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
        const nombrePlanLabels = {!!json_encode($clientesPorNombrePlan->keys()) !!};
        const nombrePlanData = {!!json_encode($clientesPorNombrePlan->values()) !!};
        new Chart(document.getElementById('clientesTipoPlanChart').getContext('2d'), {
            type: 'pie',
            data: {
                labels: nombrePlanLabels,
                datasets: [{
                    data: nombrePlanData,
                    /*backgroundColor: [
                        'rgba(59,130,246,0.5)',
                        'rgba(16,185,129,0.5)',
                        'rgba(239,68,68,0.5)',
                        'rgba(245,158,11,0.5)'
                    ]*/
                }]
            },
            options: {
                responsive: true,
            },
            plugins:[ChartDataLabels]
        });
        

        const cpe = document.getElementById('clientesEntrenador').getContext('2d');
    
    	const chart = new Chart(cpe, {
        type: 'bar',
        data: {
            labels: @json($mesesClientes),
            datasets: @json($clientesPorEntrenador)
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Clientes Acumulados'
                    }
                },
                x: {
                    title: {
                        display: true,
                        text: 'Meses'
                    }
                }
            },
            plugins: {
               //  title: {
//                     display: true,
//                     text: 'Clientes Acumulados por Entrenador ({{ $añoSeleccionado }})'
//                 },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return `${context.dataset.label}: ${context.raw} clientes`;
                        }
                    }
                },

            }
        }
    });
const ctxr = document.getElementById('graficoRetencion').getContext('2d');
new Chart(ctxr, {
    type: 'line',
    data: {
        labels: {!! json_encode($mesesRetencion) !!},
        datasets: {!! json_encode($datasetsRetencion) !!}
    },
    options: {
        responsive: true,
        plugins: {
            title: { display: true, text: 'Tasa de retención mensual por entrenador (%)' }
        },
        scales: {
            y: { min: 0, max: 200, title: { display: true, text: '%' } }
        }
    }
});
    </script>
</x-admin-layout>