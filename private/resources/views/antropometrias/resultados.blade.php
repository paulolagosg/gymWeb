<x-admin-layout>
    <div class="py-4">
        <div class="">
            <div class="flex items-center justify-between mb-4 bg-white p-6 rounded-lg text-center">

            </div>
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 overflow-x-auto w-full">
                    <h2 class="text-xl font-bold mb-4">Resultados</h2>
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
                    <!-- resources/views/resultados.blade.php -->
                    <h3>Informe de Antropometría</h3>
                    <table>
                        <tr>
                            <td>Peso:</td>
                            <td>{{ $peso }} kg</td>
                        </tr>
                        <tr>
                            <td>Talla:</td>
                            <td>{{ $talla }} cm</td>
                        </tr>
                        <tr>
                            <td>IMC:</td>
                            <td>{{ $imc }} - {{ $clasificacion_imc }}</td>
                        </tr>
                        <tr>
                            <td>Sumatoria de Pliegues:</td>
                            <td>{{ $sumatoria_pliegues }} mm - {{ $clasificacion_masa_adiposa }}</td>
                        </tr>
                        <tr>
                            <td>Masa Muscular:</td>
                            <td>{{ $masa_muscular }}%</td>
                        </tr>
                    </table>

                    <!-- Gráfico de Distribución de Grasa Corporal -->
                    <canvas id="graficoDistribucion" width="400" height="200"></canvas>
                    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
                    <script>
                        var ctx = document.getElementById('graficoDistribucion').getContext('2d');
                        var graficoDistribucion = new Chart(ctx, {
                            type: 'bar',
                            data: {
                                labels: ['Región Superior', 'Región Media', 'Región Inferior'],
                                datasets: [{
                                    label: 'Distribución de Grasa Corporal',
                                    data: [{
                                        {
                                            $distribucion_grasa['superior']
                                        }
                                    }, {
                                        {
                                            $distribucion_grasa['media']
                                        }
                                    }, {
                                        {
                                            $distribucion_grasa['inferior']
                                        }
                                    }],
                                    backgroundColor: ['rgba(54, 162, 235, 0.2)', 'rgba(255, 99, 132, 0.2)', 'rgba(75, 192, 192, 0.2)'],
                                    borderColor: ['rgba(54, 162, 235, 1)', 'rgba(255, 99, 132, 1)', 'rgba(75, 192, 192, 1)'],
                                    borderWidth: 1
                                }]
                            }
                        });
                    </script>

                    <!-- Gráfico de Score-Z para Perímetros -->
                    <canvas id="graficoScoreZPerimetros" width="400" height="200"></canvas>
                    <script>
                        var ctx = document.getElementById('graficoScoreZPerimetros').getContext('2d');
                        var graficoScoreZPerimetros = new Chart(ctx, {
                            type: 'bar',
                            data: {
                                labels: ['Cintura', 'Muslo Superior'],
                                datasets: [{
                                    label: 'Score-Z Perímetros',
                                    data: [{
                                        {
                                            $score_z_perimetros['cintura']
                                        }
                                    }, {
                                        {
                                            $score_z_perimetros['muslo_superior']
                                        }
                                    }],
                                    backgroundColor: ['rgba(255, 159, 64, 0.2)', 'rgba(153, 102, 255, 0.2)'],
                                    borderColor: ['rgba(255, 159, 64, 1)', 'rgba(153, 102, 255, 1)'],
                                    borderWidth: 1
                                }]
                            }
                        });
                    </script>

                    <!-- Gráfico de Score-Z para Pliegues -->
                    <canvas id="graficoScoreZPliegues" width="400" height="200"></canvas>
                    <script>
                        var ctx = document.getElementById('graficoScoreZPliegues').getContext('2d');
                        var graficoScoreZPliegues = new Chart(ctx, {
                            type: 'bar',
                            data: {
                                labels: ['Tríceps', 'Subescapular'],
                                datasets: [{
                                    label: 'Score-Z Pliegues',
                                    data: [{
                                        {
                                            $score_z_pliegues['triceps']
                                        }
                                    }, {
                                        {
                                            $score_z_pliegues['subescapular']
                                        }
                                    }],
                                    backgroundColor: ['rgba(255, 99, 132, 0.2)', 'rgba(54, 162, 235, 0.2)'],
                                    borderColor: ['rgba(255, 99, 132, 1)', 'rgba(54, 162, 235, 1)'],
                                    borderWidth: 1
                                }]
                            }
                        });
                    </script>
                </div>
            </div>
        </div>
    </div>
</x-admin-layout>