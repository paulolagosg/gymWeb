<style>
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        header {
            text-align: center;
            margin-bottom: 40px;
        }
        
        h1 {
            font-size: 2.8rem;
            margin-bottom: 10px;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
        }
        
        .description {
            font-size: 1.2rem;
            max-width: 600px;
            margin: 0 auto 30px;
            line-height: 1.6;
        }
        
        .controls {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-bottom: 30px;
            flex-wrap: wrap;
        }
        
        button {
            padding: 12px 25px;
            border: none;
            border-radius: 30px;
            background: rgba(255, 255, 255, 0.2);
            color: white;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }
        
        button:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.3);
        }
        
        button:active {
            transform: translateY(0);
        }
        
        #wordcloud {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 40px;
            min-height: 500px;
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            align-items: center;
            gap: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            margin-bottom: 30px;
            transition: all 0.5s ease;
        }
        
        .word {
            display: inline-block;
            padding: 10px 15px;
            border-radius: 50px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 600;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            position: relative;
            overflow: hidden;
        }
        
        .word:hover {
            transform: translateY(-5px) scale(1.05);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
            z-index: 10;
        }
        
        .word::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(to right, transparent, rgba(255, 255, 255, 0.2), transparent);
            transform: translateX(-100%);
        }
        
        .word:hover::after {
            transform: translateX(100%);
            transition: transform 0.6s ease;
        }
        
        .word-info {
            text-align: center;
            margin-top: 20px;
            font-size: 1.1rem;
            background: rgba(255, 255, 255, 0.1);
            padding: 15px;
            border-radius: 15px;
            backdrop-filter: blur(10px);
        }
        
        footer {
            text-align: center;
            margin-top: 40px;
            font-size: 0.9rem;
            opacity: 0.8;
        }
        
        @media (max-width: 768px) {
            h1 {
                font-size: 2.2rem;
            }
            
            .description {
                font-size: 1rem;
            }
            
            #wordcloud {
                padding: 20px;
                min-height: 400px;
            }
            
            .word {
                padding: 8px 12px;
                font-size: 0.9rem;
            }
        }
    </style>
<x-admin-layout>
    <div class="py-4">
        <div class="max-w-7xl mx-auto">
            <div class="flex items-center justify-between mb-4">
                <a href="{{ route('portada') }}" class="text-gray-700 hover:text-gray-500">
                <i class="fas fa-circle-left fa-2x">&nbsp;</i>
                </a>
            </div>
            <!-- div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8" -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 md:gap-6 mb-8">
                <!-- Total Clientes -->
                <div class="bg-green-100 p-6 rounded-lg shadow text-center border-t-4 border-green-500">
                	<a href="{{ route('clientes.index') }}" class="">
                   		<div class="text-xl text-gray-700 font-semibold">Total<br>Clientes</div>
                    	<div class="font-extrabold text-blue-600 mt-4" style="font-size:xxx-large">{{ $totalClientes }}</div>
                   		<!--div class="flex  justify-center">
	                    	<img src="/iconos/usuarios.png" >
                    	</div-->
                   	</a>
                </div>
                <!-- Clientes al día -->
                <!--div class="bg-green-100 p-6 rounded-lg shadow text-center border-t-4 border-green-500">
                    <div class="text-xl text-gray-700 font-semibold">Clientes<br>al día</div>
                    <div class="font-extrabold text-green-600 mt-4" style="font-size:xxx-large">{{ $clientesAlDia }}</div>
                    	<!--div class="flex  justify-center">
	                    	<img src="/iconos/aldia.png" >
                    	</div-->
                </div-->
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
                <div class="p-6 rounded-lg shadow border-t-4" style="background-color: lightyellow;border-top-color: yellow;">
                    <h2 class="text-xl  font-bold mb-4 text-gray-800 ">Clientes por género</h2>
                    <table class="w-full">
                        @foreach($clientesPorGenero as $genero => $cantidad)
                        <tr><td style="text-align:left">{{ $genero }}</td><td>&nbsp;</td><th style="text-align:right">{{ $cantidad }}</th></tr>
                        <!-- li>{{ $genero }}: <span class="font-bold">{{ $cantidad }}</span></li -->
                        @endforeach
                    </table>
                    <!--div class="flex  justify-center">
	                    <img src="/iconos/genero.png" >
                    </div-->
                </div>
                <div class="p-6 rounded-lg shadow  border-t-4" style="background-color: #63D5F8;border-top-color: #099BC8;">
                    <h2 class="text-xl font-bold mb-4 text-white">Clientes por rango de edad</h2>
                    <table class="w-full">
                        @foreach($clientesPorEdad as $rango => $cantidad)
                        <tr><td class="text-white text-left">{{ $rango }}</td><td>&nbsp;</td><th class="text-white text-left font-bold"">{{ $cantidad }}</th></tr>
                        @endforeach
                    </table>
                    <!--div class="flex  justify-center">
	                    <img src="/iconos/edad.png" >
                    </div-->
                </div>
                <div class="bg-green-100 p-6 rounded-lg shadow text-center border-t-4 border-green-500">
                    <h2 class="text-xl font-bold mb-4 text-gray-800">Motivos de Ingreso de Clientes</h2>
                    <table class="w-full">
                        @foreach($motivosIngreso as $m)
                        <tr><td style="text-align:left">{{ $m->motivo }}</td><td>&nbsp;</td><th style="text-align:right">{{ $m->cantidad }}</th></tr>
                        @endforeach
                    </table>
                </div>
                <div class="bg-red-100 p-6 rounded-lg shadow text-center border-t-4 border-red-500">
                    <h2 class="text-xl font-bold mb-4 text-gray-800">Motivos de Egreso de Clientes</h2>
                    <table class="w-full">
                        @foreach($motivosEgreso as $m)
                        <tr><td style="text-align:left">{{ $m->motivo }}</td><td>&nbsp;</td><th style="text-align:right">{{ $m->cantidad }}</th></tr>
                        @endforeach
                    </table>
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
                    <h2 class="text-lg font-bold mb-4 text-gray-800">Ingresos mensuales ({{ $añoSeleccionado }})</h2>
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
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-4">
            	<h2 class="text-2xl font-bold mb-4 text-gray-800">Encuesta de Satisfacción del Gimnasio</h2>
           		<div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-4">
			@foreach($servqualAverages as $label => $promedio)
    			<div class="mb-2">
        			<span class="font-bold">{{ $servqualLabelNames[$label] ?? $label }}:</span>
        			@if($promedio)
            			<span>{{ $promedio }}/5</span>
            			<span>
                			@for($i = 1; $i <= 5; $i++)
                    			@if($promedio >= $i)
                        			<i class="fa fa-star" style="color:#EFB810"></i>
                    			@elseif($promedio > $i-1)
                        			<i class="fa fa-star-half-alt" style="color:#EFB810"></i>
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
    			<h3 class="font-semibold mt-4 mb-2 text-xl">Palabras frecuentes en respuestas abiertas</h3>
    			<div class="container">
        <!-- header>
            <h1>Nube de Palabras Interactiva</h1>
            <p class="description">Visualización de palabras con diferentes pesos y frecuencias. Interactúa con las palabras para ver detalles o cambia el diseño con los botones.</p>
        </header>
        
        <div class="controls">
            <button id="layoutCircle">Diseño Circular</button>
            <button id="layoutSpiral">Diseño Espiral</button>
            <button id="layoutRandom">Diseño Aleatorio</button>
            <button id="colorVibrant">Colores Vibrantes</button>
            <button id="colorPastel">Colores Pastel</button>
            <button id="colorMonochrome">Monocromático</button>
        </div-->
        
        			<div id="wordcloud"></div>
    			</div>
    		</div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/chartjs-plugin-datalabels/2.2.0/chartjs-plugin-datalabels.min.js" 
		integrity="sha512-JPcRR8yFa8mmCsfrw4TNte1ZvF1e3+1SdGMslZvmrzDYxS69J7J49vkFL8u6u8PlPJK+H3voElBtUCzaXj+6ig==" 
		crossorigin="anonymous" referrerpolicy="no-referrer">
	</script>
    <script>
    	/******* */
		const words = @json($topWords);
        
        const container = document.getElementById('wordcloud');
        const wordInfo = document.getElementById('wordInfo');
        let maxCount = 1;
        
        // Encontrar el valor máximo
        for (let word in words) {
            if (words[word] > maxCount) maxCount = words[word];
        }
        
        let colorPalette = [
            '#FF6B6B', '#4ECDC4', '#FFE66D', '#6B5B95', '#88D8B0', 
            '#F984EF', '#00BBF9', '#FDCA40', '#9B5DE5', '#00F5D4'
        ];
        
        function generatePastelColors() {
            return [
                '#FFB3BA', '#FFDFBA', '#FFFFBA', '#BAFFC9', '#BAE1FF',
                '#D9BBFF', '#FFCEE5', '#B5EAD7', '#C7CEEA', '#F8B195'
            ];
        }
        
        function generateMonochromeColors() {
            const baseHue = Math.floor(Math.random() * 360);
            return [
                `hsl(${baseHue}, 70%, 60%)`,
                `hsl(${baseHue}, 65%, 55%)`,
                `hsl(${baseHue}, 60%, 50%)`,
                `hsl(${baseHue}, 75%, 65%)`,
                `hsl(${baseHue}, 80%, 70%)`
            ];
        }
        
        function getColor(index) {
            return colorPalette[index % colorPalette.length];
        }
        
        function arrangeRandom() {
            const words = container.querySelectorAll('.word');
            const containerWidth = container.offsetWidth;
            const containerHeight = container.offsetHeight;
            
            words.forEach(word => {
                const x = Math.random() * (containerWidth - word.offsetWidth);
                const y = Math.random() * (containerHeight - word.offsetHeight);
                
                word.style.position = 'absolute';
                word.style.left = `${x}px`;
                word.style.top = `${y}px`;
            });
        }
        
        function createWordCloud() {
            container.innerHTML = '';
            let nPalabras = 0;
            Object.entries(words).forEach(([word, count], index) => {
                const span = document.createElement('span');
                span.className = 'word';
                span.textContent = word;
                
                const fontSize = 0.8 + (2.5 * count / maxCount);
                span.style.fontSize = `${fontSize}em`;
                
                span.style.background = getColor(index);
                
                span.addEventListener('click', () => {
                    wordInfo.textContent = `Palabra: "${word}" - Frecuencia: ${count} (${Math.round((count / maxCount) * 100)}% del máximo)`;
                });
                
                container.appendChild(span);
                nPalabras++;
            });
            if(nPalabras == 0){
            	container.innerHTML = 'Sin respuestas';
            }
            
            arrangeRandom();
        }
        
        createWordCloud();
        /*
        document.getElementById('layoutCircle').addEventListener('click', arrangeCircular);
        document.getElementById('layoutSpiral').addEventListener('click', arrangeSpiral);
        document.getElementById('layoutRandom').addEventListener('click', arrangeRandom);
        
        document.getElementById('colorVibrant').addEventListener('click', () => {
            colorPalette = [
                '#FF6B6B', '#4ECDC4', '#FFE66D', '#6B5B95', '#88D8B0', 
                '#F984EF', '#00BBF9', '#FDCA40', '#9B5DE5', '#00F5D4'
            ];
            createWordCloud();
        });
        
        document.getElementById('colorPastel').addEventListener('click', () => {
            colorPalette = generatePastelColors();
            createWordCloud();
        });
        
        document.getElementById('colorMonochrome').addEventListener('click', () => {
            colorPalette = generateMonochromeColors();
            createWordCloud();
        });
        
        window.addEventListener('resize', arrangeCircular);
        
    	/******* */
    
    
            // Generar la nube de palabras solo con CSS y JS simple
        /*const words2 = @json($topWords);
        const container2 = document.getElementById('wordcloud');
        let max = 1;
        for (let w in words2) if (words2[w] > max) max = words2[w];
        Object.entries(words2).forEach(([word, count]) => {
            const span = document.createElement('span');
            span.textContent = word + ' ';
            span.style.fontSize = (0.8 + (2 * count / max)) + 'em';
            span.style.margin = '0 6px';
            span.style.display = 'inline-block';
            span.style.color = `hsl(${Math.floor(200 + 120 * Math.random())},70%,40%)`;
            container2.appendChild(span);
        });*/
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
                        data: ingresosTotales,
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