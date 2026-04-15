<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Evolución Ejercicios</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            margin: 80px 30px 60px 30px;
        }

        .page {
            page-break-after: always;
            margin-bottom: 20px;
        }

        .title {
            font-size: 18px;
            font-weight: 700;
            margin: 10px 0;
        }

        .chart {
            text-align: center;
            margin-bottom: 18px;
        }

        .exercise {
            margin-bottom: 30px;
        }

        img {
            max-width: 100%;
            height: auto;
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

        .section {
            page-break-inside: avoid;
            margin-bottom: 25px;
        }

        .clearfix {
            clear: both;
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
        <h2>Evolución de Ejercicios - {{ $cliente->nombres }} {{ $cliente->paterno }} {{ $cliente->materno }}</h2>

        @foreach($charts as $chart)
        <div class="exercise page">
            <div class="title">{{ $chart['nombre'] }}</div>
            <div class="chart">
                <img src="{{ $chart['carga_url'] }}" alt="Carga - {{ $chart['nombre'] }}">
            </div>
        </div>
        @endforeach
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