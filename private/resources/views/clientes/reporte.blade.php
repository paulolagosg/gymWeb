<style>
    .hr-container {
        display: flex;
        align-items: center;
        justify-content: center;
        /* Centra horizontalmente */
        width: 100%;
    }

    .hr-container hr {
        flex-grow: 1;
        /* La línea horizontal ocupa el espacio restante */
        margin: 0;
        /* Elimina márgenes predeterminados */
    }

    .hr-container span.fa {
        margin: 0 10px;
        /* Espacio entre la línea y el icono */
    }
</style>
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
                <div class="p-6 text-gray-900 overflow-x-auto w-full">
                    <div class="flex items-center justify-between mb-4">
                        <form method="POST" action="{{ route('clientes.reporte.enviar', $cliente->slug) }}" id="fEnviarReporte">
                            @csrf
                            <button id="btnEnviar" type="button" onclick="enviarReporte()" class="bg-green-600 hover:bg-green-800 text-white font-bold py-2 px-4 rounded">Enviar por correo (PDF)</button>
                        </form>
                    </div>
                    <h2 class="font-semibold text-xl text-gray-800 leading-tight mb-4">
                        Reporte
                    </h2>
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
                    <div class="hr-container">
                        <hr>
                        <span class="fa fa-diamond"></span>
                        <hr>
                    </div>
                    <h3 class="text-lg font-semibold mt-6">Evolución del Peso</h3>
                    <div class="grid grid-cols-1 md:grid-cols-1 gap-4 mt-6">
                        <div class="bg-white rounded-lg shadow p-4 flex items-center justify-center h-72 w-full">
                            <canvas id="graficoPeso" height="100"></canvas>
                        </div>
                        <div class="bg-white rounded-lg shadow p-4 w-full">
                            <table class="table-auto w-full mt-2 tabla_datos">
                                <thead>
                                    <tr>
                                        <th>Fecha</th>
                                        <th>Peso (kg)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($pesos as $p)
                                    <tr>
                                        <td>{{ $p->created_at->format('d/m/Y') }}</td>
                                        <td>{{ $p->peso }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                    {{-- IMC --}}
                    <div class="hr-container">
                        <hr>
                        <span class="fa fa-diamond"></span>
                        <hr>
                    </div>
                    <h3 class="text-lg font-semibold mt-6">Evolución del IMC</h3>
                    <div class="grid grid-cols-1 md:grid-cols-1 gap-4 mt-6">
                        <div class="bg-white rounded-lg shadow p-4 flex items-center justify-center h-72 w-full">
                            <canvas id="graficoImc" height="100"></canvas>
                        </div>
                        <div class="bg-white rounded-lg shadow p-4 w-full">
                            <table class="table-auto w-full mt-2 tabla_datos">
                                <thead>
                                    <tr>
                                        <th>Fecha</th>
                                        <th>IMC</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($imcs as $i)
                                    <tr>
                                        <td>{{ $i->created_at->format('d/m/Y') }}</td>
                                        <td>{{ $i->imc }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                    {{-- Agua --}}
                    <!-- 
<div class="hr-container">
                        <hr>
                        <span class="fa fa-diamond"></span>
                        <hr>
                    </div>
                    <h3 class="text-lg font-semibold mt-6">Evolución del % de Agua</h3>
                    <div class="grid grid-cols-1 md:grid-cols-1 gap-4 mt-6">
                        <div class="bg-white rounded-lg shadow p-4 flex items-center justify-center h-72 w-full">
                            <canvas id="graficoAgua" height="100"></canvas>
                        </div>
                        <div class="bg-white rounded-lg shadow p-4 w-full">
                            <table class="table-auto w-full mt-2 tabla_datos">
                                <thead>
                                    <tr>
                                        <th>Fecha</th>
                                        <th>Valor</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($aguas as $i)
                                    <tr>
                                        <td>{{ $i->created_at->format('d/m/Y') }}</td>
                                        <td>{{ $i->valor }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
 -->
                    {{-- % grasa --}}
                    <div class="hr-container">
                        <hr>
                        <span class="fa fa-diamond"></span>
                        <hr>
                    </div>
                    <h3 class="text-lg font-semibold mt-6">Evolución del % de Grasa Corporal</h3>
                    <div class="grid grid-cols-1 md:grid-cols-1 gap-4 mt-6">
                        <div class="bg-white rounded-lg shadow p-4 flex items-center justify-center h-72 w-full">
                            <canvas id="graficoGrasa" height="100"></canvas>
                        </div>
                        <div class="bg-white rounded-lg shadow p-4 w-full">
                            <table class="table-auto w-full mt-2 tabla_datos">
                                <thead>
                                    <tr>
                                        <th>Fecha</th>
                                        <th>Valor</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($grasas as $i)
                                    <tr>
                                        <td>{{ $i->created_at->format('d/m/Y') }}</td>
                                        <td>{{ $i->valor }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                    {{-- % masa osea --}}
                    <div class="hr-container">
                        <hr>
                        <span class="fa fa-diamond"></span>
                        <hr>
                    </div>
                    <h3 class="text-lg font-semibold mt-6">Evolución del % de Masa Ósea</h3>
                    <div class="grid grid-cols-1 md:grid-cols-1 gap-4 mt-6">
                        <div class="bg-white rounded-lg shadow p-4 flex items-center justify-center h-72 w-full">
                            <canvas id="graficoMasaOsea" height="100"></canvas>
                        </div>
                        <div class="bg-white rounded-lg shadow p-4 w-full">
                            <table class="table-auto w-full mt-2 tabla_datos">
                                <thead>
                                    <tr>
                                        <th>Fecha</th>
                                        <th>Valor</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($posea as $i)
                                    <tr>
                                        <td>{{ $i->created_at->format('d/m/Y') }}</td>
                                        <td>{{ $i->valor }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                    {{-- % masa muscular --}}
                    <div class="hr-container">
                        <hr>
                        <span class="fa fa-diamond"></span>
                        <hr>
                    </div>
                    <h3 class="text-lg font-semibold mt-6">Evolución del % de Masa Muscular</h3>
                    <div class="grid grid-cols-1 md:grid-cols-1 gap-4 mt-6">
                        <div class="bg-white rounded-lg shadow p-4 flex items-center justify-center h-72 w-full">
                            <canvas id="graficoMasaMuscular" height="100"></canvas>
                        </div>
                        <div class="bg-white rounded-lg shadow p-4 w-full">
                            <table class="table-auto w-full mt-2 tabla_datos">
                                <thead>
                                    <tr>
                                        <th>Fecha</th>
                                        <th>Valor</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($pmuscular as $i)
                                    <tr>
                                        <td>{{ $i->created_at->format('d/m/Y') }}</td>
                                        <td>{{ $i->valor }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                    {{-- Perímetros --}}
                    <div class="hr-container">
                        <hr>
                        <span class="fa fa-diamond"></span>
                        <hr>
                    </div>
                    <h3 class="text-lg font-semibold mt-6">Evolución de los Perímetros</h3>
                    <div class="grid grid-cols-1 md:grid-cols-1 gap-4 mt-6">
                        <div class="bg-white rounded-lg shadow p-4 flex items-center justify-center h-72 w-full">
                            <canvas id="graficoPerimetros" height="100"></canvas>
                        </div>
                        <div class="bg-white rounded-lg shadow p-4 w-full overflow-x-auto">
                            <table class="table-auto w-full mt-2 tabla_datos">
                                <thead>
                                    <tr>
                                        <th>Fecha</th>
                                        <th>Cabeza</th>
                                        <th>Brazo</th>
                                        <th>Torax</th>
                                        <th>Cintura</th>
                                        <th>Cadera</th>
                                        <th>Muslo</th>
                                        <th>Pantorrilla</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($perimetros as $p)
                                    <tr>
                                        <td>{{ $p->created_at->format('d/m/Y') }}</td>
                                        <td>{{ $p->cabeza ?? '-' }}</td>
                                        <td>{{ $p->brazo_relajado ?? '-' }}</td>
                                        <td>{{ $p->torax_mesoexternal ?? '-' }}</td>
                                        <td>{{ $p->cintura_minima ?? '-' }}</td>
                                        <td>{{ $p->caderas_maxima ?? '-' }}</td>
                                        <td>{{ $p->muslo_superior ?? '-' }}</td>
                                        <td>{{ $p->pantorrilla_maxima ?? '-' }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                    {{-- Cuenta Corriente --}}
                    <div class="hr-container">
                        <hr>
                        <span class="fa fa-diamond"></span>
                        <hr>
                    </div>
                    <h3 class="text-lg font-semibold mt-6">Cuenta Corriente</h3>
                    <table id="tablaDatos" class="table-auto w-full mt-2">
                        <thead>
                            <tr>
                                <th>Vencimiento</th>
                                <th>Monto</th>
                                <th>Pagado</th>
                                <th>Fecha Pago</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($cuotas as $c)
                            <tr>
                                <td>{{ \Carbon\Carbon::parse($c->fecha_vencimiento)->format('d/m/Y') }}</td>
                                <td>${{ number_format($c->monto, 0, ',', '.') }}</td>
                                <td>${{ number_format($c->monto_pagado, 0, ',', '.') }}</td>
                                <td>{{ $c->fecha_pago }}</td>
                                <td>{{ $c->id_estado_pago == 2 ? 'Pagada' : 'Pendiente' }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>

                    {{-- PAR-Q --}}
                    <div class="hr-container">
                        <hr>
                        <span class="fa fa-diamond"></span>
                        <hr>
                    </div>
                    <h3 class="text-lg font-semibold mt-6">Cuestionario PAR-Q</h3>
                    <table class="table-auto w-full mt-2 tabla_datos">
                        <thead>
                            <tr>
                                <th>Pregunta</th>
                                <th>Respuesta</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($parq as $r)
                            <tr>
                                <td>{{ $r->pregunta->pregunta }}</td>
                                <td>{{ $r->respuesta ? 'Sí' : 'No' }}
                                    @if($r->observaciones)
                                    <br><em><strong>Obs:</strong> {{ $r->observaciones }}</em>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>

                    {{-- Fit plan--}}
                    <div class="hr-container">
                        <hr>
                        <span class="fa fa-diamond"></span>
                        <hr>
                    </div>
                    <h3 class="text-lg font-semibold mt-6">Cuestionario Acondicionamiento Físico</h3>
                    <table class="table-auto w-full mt-2 tabla_datos">
                        <thead>
                            <tr>
                                <th>Pregunta</th>
                                <th>Respuesta</th>
                            </tr>
                        </thead>
                        @if($fitPlan)
                        <tbody>
                            <tr>
                                <td>Alergias, enfermedades patológicas conocidas</td>
                                <td style="text-align:left">{{ $fitPlan->patologias }}</td>
                            </tr>
                            <tr>
                                <td>Intolerancias Alimentarias</td>
                                <td style="text-align:left">{{ $fitPlan->intolerancias }}</td>
                            </tr>
                            <tr>
                                <td>Alimentos que NO te gustan</td>
                                <td style="text-align:left">{{ $fitPlan->no_gustan }}</td>
                            </tr>
                            <tr>
                                <td>Alimentos que ENCANTAN</td>
                                <td style="text-align:left">{{ $fitPlan->encantan }}</td>
                            </tr>
                            <tr>
                                <td>Horario de comidas</td>
                                <td style="text-align:left">{{ $fitPlan->horario }}</td>
                            </tr>
                            <tr>
                                <td>Hora a la que te sueles levantar</td>
                                <td style="text-align:left">{{ $fitPlan->hora_levantarse }}</td>
                            </tr>
                            <tr>
                                <td>Hora a la que te sueles acostar</td>
                                <td style="text-align:left">{{ $fitPlan->hora_acostarse }}</td>
                            </tr>
                            <tr>
                                <td>Descripción de tu trabajo</td>
                                <td style="text-align:left">{{ $fitPlan->trabajo }}</td>
                            </tr>
                            <tr>
                                <td>Horario en que vas al gimnasio</td>
                                <td style="text-align:left">{{ $fitPlan->hora_gimnasio }}</td>
                            </tr>
                            <tr>
                                <td>Duración del entreno</td>
                                <td style="text-align:left">{{ $fitPlan->duracion_entreno }}</td>
                            </tr>
                            <tr>
                                <td>Suplementación Actual (sólo si aplica)</td>
                                <td style="text-align:left">{{ $fitPlan->suplemento }}</td>
                            </tr>
                            <tr>
                                <td>Descríbeme lo que sueles comer actualmente un día cualquiera</td>
                                <td style="text-align:left">{{ $fitPlan->dia_cualquiera }}</td>
                            </tr>
                            <tr>
                                <td>Datos que puedas creer que son de intereses relacionados con tu preparación</td>
                                <td style="text-align:left">{{ $fitPlan->datos_interes }}</td>
                            </tr>
                            <tr>
                                <td>Objetivo del acondicionamiento físico</td>
                                <td style="text-align:left">{{ $fitPlan->objetivo }}</td>
                            </tr>
                        </tbody>
                        @endif
                    </table>
                </div>
            </div>
        </div>
    </div>






    {{-- Chart.js --}}
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script id="reporteChartData" type="application/json">
        @json($reportChartData)
    </script>
    <script>
        const reportChartData = JSON.parse(document.getElementById('reporteChartData').textContent);
        const ctx = document.getElementById('graficoPeso').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: reportChartData.pesosLabels ?? [],
                datasets: [{
                    label: 'Peso (kg)',
                    data: reportChartData.pesosData ?? [],
                    borderColor: 'rgba(59,130,246,1)',
                    backgroundColor: 'rgba(59,130,246,0.2)',
                    fill: false,
                    tension: 0.4,
                    pointRadius: 4,
                    pointBackgroundColor: 'rgba(59,130,246,1)'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
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

        // IMC
        const ctx2 = document.getElementById('graficoImc').getContext('2d');
        new Chart(ctx2, {
            type: 'line',
            data: {
                labels: reportChartData.imcsLabels ?? [],
                datasets: [{
                    label: 'IMC',
                    data: reportChartData.imcsData ?? [],
                    borderColor: 'green',
                    backgroundColor: 'rgba(59,130,246,0.2)',
                    fill: false,
                    tension: 0.4,
                    pointRadius: 4,
                    pointBackgroundColor: 'green'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
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
        // Agua
        // const ctagua = document.getElementById('graficoAgua').getContext('2d');
        //         new Chart(ctagua, {
        //             type: 'line',
        //             data: {
        //                 labels: {!!json_encode($aguas->pluck('created_at')->map(fn($d) => $d->format('d/m/Y'))) !!},
        //                 datasets: [{
        //                     label: '% de Agua',
        //                     data: {!!json_encode($aguas->pluck('valor')) !!},
        //                     borderColor: 'green',
        //                     backgroundColor: 'rgba(59,130,246,0.2)',
        //                     fill: false,
        //                     tension: 0.4,
        //                     pointRadius: 4,
        //                     pointBackgroundColor: 'green'
        //                 }]
        //             },
        //             options: {
        //                 responsive: true,
        //                 maintainAspectRatio: true,
        //                 plugins: {
        //                     legend: {
        //                         display: false
        //                     }
        //                 },
        //                 scales: {
        //                     y: {
        //                         beginAtZero: false
        //                     }
        //                 }
        //             }
        //         });
        // Grasa
        const ctgrasa = document.getElementById('graficoGrasa').getContext('2d');
        new Chart(ctgrasa, {
            type: 'line',
            data: {
                labels: reportChartData.grasasLabels ?? [],
                datasets: [{
                    label: '% de Grasa Corporal',
                    data: reportChartData.grasasData ?? [],
                    borderColor: 'green',
                    backgroundColor: 'rgba(59,130,246,0.2)',
                    fill: false,
                    tension: 0.4,
                    pointRadius: 4,
                    pointBackgroundColor: 'green'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
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
        // Masa ósea
        const cposea = document.getElementById('graficoMasaOsea').getContext('2d');
        new Chart(cposea, {
            type: 'line',
            data: {
                labels: reportChartData.poseaLabels ?? [],
                datasets: [{
                    label: '% de Masa Ósea',
                    data: reportChartData.poseaData ?? [],
                    borderColor: 'green',
                    backgroundColor: 'rgba(59,130,246,0.2)',
                    fill: false,
                    tension: 0.4,
                    pointRadius: 4,
                    pointBackgroundColor: 'green'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
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

        // Masa ósea
        const cpmuscular = document.getElementById('graficoMasaMuscular').getContext('2d');
        new Chart(cpmuscular, {
            type: 'line',
            data: {
                labels: reportChartData.pmuscularLabels ?? [],
                datasets: [{
                    label: '% de Masa Muscular',
                    data: reportChartData.pmuscularData ?? [],
                    borderColor: 'green',
                    backgroundColor: 'rgba(59,130,246,0.2)',
                    fill: false,
                    tension: 0.4,
                    pointRadius: 4,
                    pointBackgroundColor: 'green'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
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

        // Perímetros
        // const ctperimetros = document.getElementById('graficoPerimetros').getContext('2d');
        //         const perimetrosData = {!!json_encode($perimetros) !!};
        //         const perimetrosPromedios = perimetrosData.map(p => {
        //             const valores = [
        //                 p.cabeza, p.brazo_relajado, p.brazo_flexionado_tension, p.antebrazo,
        //                 p.torax_mesoexternal, p.cintura_minima, p.caderas_maxima, p.muslo_superior,
        //                 p.muslo_medial, p.pantorrilla_maxima
        //             ].filter(v => v !== null && v !== '');
        //             return valores.length > 0 ? (valores.reduce((a, b) => parseFloat(a) + parseFloat(b), 0) / valores.length).toFixed(2) : 0;
        //         });
        // 
        //         new Chart(ctperimetros, {
        //             type: 'line',
        //             data: {
        //                 labels: {!!json_encode($perimetros->pluck('created_at')->map(fn($d) => $d->format('d/m/Y'))) !!},
        //                 datasets: [{
        //                     label: 'Promedio de Perímetros (cm)',
        //                     data: perimetrosPromedios,
        //                     borderColor: 'rgba(234, 88, 12, 1)',
        //                     backgroundColor: 'rgba(234, 88, 12, 0.2)',
        //                     fill: false,
        //                     tension: 0.4,
        //                     pointRadius: 4,
        //                     pointBackgroundColor: 'rgba(234, 88, 12, 1)'
        //                 }]
        //             },
        //             options: {
        //                 responsive: true,
        //                 maintainAspectRatio: true,
        //                 plugins: {
        //                     legend: {
        //                         display: false
        //                     }
        //                 },
        //                 scales: {
        //                     y: {
        //                         beginAtZero: false
        //                     }
        //                 }
        //             }
        //         });

        // Perímetros
        const ctperimetros = document.getElementById('graficoPerimetros').getContext('2d');
        const perimetrosData = reportChartData.perimetros ?? [];

        // Definir etiquetas para cada perímetro
        const perimetrosConfig = [{
                key: 'cabeza',
                label: 'Cabeza',
                color: 'rgba(255, 99, 132, 1)'
            },
            {
                key: 'brazo_relajado',
                label: 'Brazo Relajado',
                color: 'rgba(54, 162, 235, 1)'
            },
            {
                key: 'brazo_flexionado_tension',
                label: 'Brazo Flexionado',
                color: 'rgba(255, 206, 86, 1)'
            },
            {
                key: 'antebrazo',
                label: 'Antebrazo',
                color: 'rgba(75, 192, 192, 1)'
            },
            {
                key: 'torax_mesoexternal',
                label: 'Tórax',
                color: 'rgba(153, 102, 255, 1)'
            },
            {
                key: 'cintura_minima',
                label: 'Cintura',
                color: 'rgba(255, 159, 64, 1)'
            },
            {
                key: 'caderas_maxima',
                label: 'Caderas',
                color: 'rgba(255, 99, 255, 1)'
            },
            {
                key: 'muslo_superior',
                label: 'Muslo Superior',
                color: 'rgba(50, 205, 50, 1)'
            },
            {
                key: 'muslo_medial',
                label: 'Muslo Medial',
                color: 'rgba(70, 130, 180, 1)'
            },
            {
                key: 'pantorrilla_maxima',
                label: 'Pantorrilla',
                color: 'rgba(210, 105, 30, 1)'
            }
        ];

        // Crear datasets para cada perímetro
        const datasets = perimetrosConfig.map(perimetro => {
            const data = perimetrosData.map(p => {
                const value = p[perimetro.key];
                return (value !== null && value !== '') ? parseFloat(value) : null;
            });

            return {
                label: perimetro.label,
                data: data,
                borderColor: perimetro.color,
                backgroundColor: perimetro.color.replace('1)', '0.2)'),
                fill: false,
                tension: 0.4,
                pointRadius: 4,
                pointBackgroundColor: perimetro.color,
                borderWidth: 2
            };
        });

        // Filtrar solo los perímetros que tienen datos (evitar datasets vacíos)
        const filteredDatasets = datasets.filter(dataset => {
            return dataset.data.some(value => value !== null && !isNaN(value));
        });

        new Chart(ctperimetros, {
            type: 'line',
            data: {
                labels: reportChartData.perimetrosLabels ?? [],
                datasets: filteredDatasets
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                interaction: {
                    mode: 'index',
                    intersect: false,
                },
                plugins: {
                    legend: {
                        display: true,
                        position: 'top',
                        labels: {
                            boxWidth: 12,
                            padding: 15,
                            font: {
                                size: 11
                            }
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return `${context.dataset.label}: ${context.parsed.y} cm`;
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: false,
                        title: {
                            display: true,
                            text: 'Centímetros (cm)'
                        },
                        ticks: {
                            callback: function(value) {
                                return value + ' cm';
                            }
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: 'Fecha'
                        }
                    }
                }
            }
        });

        function enviarReporte() {
            Swal.fire({
                title: 'Enviar reporte de evolución',
                text: "¿Estás seguro de que deseas enviar el reporte a este cliente?",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: "Si, enviar",
                cancelButtonText: "Cancelar",
                confirmButtonColor: "#3085d6",
                cancelButtonColor: "#d33",
            }).then((result) => {
                if (result.isConfirmed) {
                    $("#btnEnviar").prop('disabled', true);
                    $("#btnEnviar").text('Enviando...');
                    $("#fEnviarReporte").submit();
                }
            });
        }
    </script>
</x-admin-layout>