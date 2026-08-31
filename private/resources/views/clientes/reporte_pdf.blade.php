<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Reporte de Indicadores</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            margin: 80px 30px 60px 30px;
        }

        header {
            position: fixed;
            top: -60px;
            left: 0;
            right: 0;
            height: 60px;
            text-align: center;
        }

        footer {
            position: fixed;
            bottom: -40px;
            left: 0;
            right: 0;
            height: 40px;
            text-align: center;
            font-size: 13px;
            color: #555;
        }

        .pagenum:before {
            content: counter(page);
        }

        .totalpages:before {
            content: counter(pages);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        th,
        td {
            border: 1px solid #ccc;
            padding: 4px;
            font-size: 11px;
        }

        h2,
        h3 {
            margin-top: 20px;
            page-break-after: avoid;
        }

        .chart-container {
            width: 100%;
            margin-bottom: 15px;
            page-break-inside: avoid;
        }

        .chart-img {
            width: 100%;
            height: 250px;
            object-fit: contain;
            margin-bottom: 10px;
        }

        .section {
            page-break-inside: avoid;
            margin-bottom: 25px;
        }

        .clearfix {
            clear: both;
        }

        /* Para tablas grandes en perímetros */
        .table-perimetros {
            font-size: 10px;
        }

        .table-perimetros th,
        .table-perimetros td {
            padding: 3px;
        }
    </style>
</head>

<body>
    <header>
        <img src="https://static.wixstatic.com/media/6d10e7_ec01ef38f649435295345bd8aa178ff9~mv2.png/v1/fill/w_169,h_89,al_c,q_85,usm_0.66_1.00_0.01,enc_avif,quality_auto/Logo%20MAX%202023.png"
            alt="Logo"
            style="max-width: 180px; margin-bottom: 16px;">
    </header>

    <footer>
        Equipo Ampaya Gym
    </footer>

    <main>
        <h2>Reporte de {{ $cliente->nombres }} {{$cliente->paterno }} {{$cliente->materno }}</h2>

        {{-- Peso --}}
        <div class="section">
            <h3>Evolución del Peso</h3>
            <div class="chart-container">
                <img src="{{ $chartPesoUrl }}"
                    alt="Gráfico de Peso"
                    class="chart-img">
            </div>
            <table>
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
                        <td style="text-align: right;">{{ $p->peso }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- IMC --}}
        <div class="section">
            <h3>Evolución del IMC</h3>
            <div class="chart-container">
                <img src="{{ $chartimcsUrl }}"
                    alt="Gráfico de IMC"
                    class="chart-img">
            </div>
            <table>
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
                        <td style="text-align: right;">{{ $i->imc }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Grasas --}}
        <div class="section">
            <h3>Evolución del % de Grasa Corporal</h3>
            <div class="chart-container">
                <img src="{{ $chartGrasaUrl }}"
                    alt="Gráfico de % de Grasa Corporal"
                    class="chart-img">
            </div>
            <table>
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
                        <td style="text-align: right;">{{ $i->valor }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Porcentaje masa ósea --}}
        <div class="section">
            <h3>Evolución del % de Masa Ósea</h3>
            <div class="chart-container">
                <img src="{{ $chartPoseaUrl }}"
                    alt="Gráfico de % de masa Ósea"
                    class="chart-img">
            </div>
            <table>
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
                        <td style="text-align: right;">{{ $i->valor }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        {{-- Porcentaje masa muscular --}}
        <div class="section">
            <h3>Evolución del % de Masa Muscular</h3>
            <div class="chart-container">
                <img src="{{ $chartPmuscularUrl }}"
                    alt="Gráfico de % de masa muscular"
                    class="chart-img">
            </div>
            <table>
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
                        <td style="text-align: right;">{{ $i->valor }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>


        {{-- Perímetros --}}
        <div class="section">
            <h3>Evolución de los Perímetros</h3>
            <div class="chart-container">
                <img src="{{ $chartPerimetrosUrl }}"
                    alt="Gráfico de Perímetros"
                    class="chart-img">
            </div>
            <table class="table-perimetros">
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
                        <td style="text-align: center;">{{ $p->created_at->format('d/m/Y') }}</td>
                        <td style="text-align: right;">{{ $p->cabeza ?? '-' }}</td>
                        <td style="text-align: right;">{{ $p->brazo_relajado ?? '-' }}</td>
                        <td style="text-align: right;">{{ $p->torax_mesoexternal ?? '-' }}</td>
                        <td style="text-align: right;">{{ $p->cintura_minima ?? '-' }}</td>
                        <td style="text-align: right;">{{ $p->caderas_maxima ?? '-' }}</td>
                        <td style="text-align: right;">{{ $p->muslo_superior ?? '-' }}</td>
                        <td style="text-align: right;">{{ $p->pantorrilla_maxima ?? '-' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Evolución de carga por ejercicio --}}
        <div class="section">
            <h3>Evolución de Carga por Ejercicio</h3>
            @forelse($exerciseCharts as $chart)
            <div class="chart-container">
                <p style="font-weight: bold; margin-bottom: 4px;">{{ $chart['nombre'] }}</p>
                <img src="{{ $chart['carga_url'] }}"
                    alt="Carga - {{ $chart['nombre'] }}"
                    class="chart-img">
            </div>
            @empty
            <p>Sin historial de cargas registrado.</p>
            @endforelse
        </div>
    </main>

    @if(app()->runningInConsole() || app()->environment('production'))
    <script type="text/php">
        if (isset($pdf)) {
            $pdf->page_script('
                if ($PAGE_COUNT > 1) {
                    $font = $fontMetrics->get_font("DejaVu Sans", "normal");
                    $size = 10;
                    $pageText = "Página " . $PAGE_NUM . " / " . $PAGE_COUNT;
                    $y = 820;
                    $x = 520;
                    $pdf->text($x, $y, $pageText, $font, $size);
                }
            ');
        }
    </script>
    @endif
</body>

</html>