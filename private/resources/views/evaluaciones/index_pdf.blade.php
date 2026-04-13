<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Categorización - {{ $entrenador->name }}</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            color: #222;
        }

        .nivel-barra {
            background: #e5e7eb;
            border-radius: 8px;
            height: 20px;
            width: 100%;
            margin-bottom: 12px;
            overflow: hidden;
        }

        .nivel-barra-interno {
            height: 100%;
            border-radius: 8px;
        }

        .nivel-empatia {
            background: #3b82f6;
        }

        .nivel-escucha {
            background: #10b981;
        }

        .nivel-comunicacion {
            background: #eab308;
        }

        .nivel-anatomia {
            background: #eab308;
        }

        .nivel-label {
            font-weight: bold;
            margin-bottom: 2px;
        }

        .nivel-valor {
            float: right;
            font-size: 0.95em;
            margin-right: 8px;
        }

        .mb-4 {
            margin-bottom: 1rem;
        }

        .mb-6 {
            margin-bottom: 1.5rem;
        }

        .mt-4 {
            margin-top: 1rem;
        }

        .text-2xl {
            font-size: 1.5rem;
        }

        .max-w-md {
            max-width: 500px;
        }

        .border {
            border: 1px solid #ddd;
        }

        .rounded {
            border-radius: 8px;
        }

        .p-4 {
            padding: 1rem;
        }

        .bg-white {
            background: #fff;
        }

        .text-gray-900 {
            color: #222;
        }

        .text-gray-700 {
            color: #444;
        }

        .text-center {
            text-align: center;
        }

        .w-full {
            width: 100%;
        }

        .grid {
            display: block;
        }

        .col {
            display: inline-block;
            width: 32%;
            vertical-align: top;
            margin-right: 1%;
        }
    </style>
</head>

<body>
    <h1 class="text-2xl mb-4">Categorización de {{ $entrenador->name }}</h1>
    <div class="mb-4">
        <span class="nivel-label">Categoría:</span>
        <span class="nivel-label text-2xl">{{ $categorias->nombre }}</span>
    </div>
    <div class="mb-6 max-w-md mt-4">
        @if($promedios)
        <div class="nivel-label text-2xl">Evaluación General <span class="nivel-valor">{{ $promedios->total }}%</span></div>
        <div class="nivel-barra">
            <div class="nivel-barra-interno nivel-anatomia" style="width: {{ $promedios->total }}%"></div>
        </div>
        <div class="mb-4">
            <div class="w-full">
                <div class="nivel-label">Empatía <span class="nivel-valor">{{ $promedios->empatia }}/7</span></div>
                <div class="nivel-barra">
                    <div class="nivel-barra-interno nivel-empatia" style="width: {{ ($promedios->empatia/7)*100}}%"></div>
                </div>
            </div>
            <div class="w-full">
                <div class="nivel-label">Escucha activa <span class="nivel-valor">{{ $promedios->escucha_activa }}/7</span></div>
                <div class="nivel-barra">
                    <div class="nivel-barra-interno nivel-empatia" style="width: {{ ($promedios->escucha_activa/7)*100 }}%"></div>
                </div>
            </div>
            <div class="w-full">
                <div class="nivel-label">Comunicación <span class="nivel-valor">{{ $promedios->comunicacion }}/7</span></div>
                <div class="nivel-barra">
                    <div class="nivel-barra-interno nivel-empatia" style="width: {{ ($promedios->comunicacion/7)*100 }}%"></div>
                </div>
            </div>
        </div>
        <div class="mb-4">
            <div class="w-full">
                <div class="nivel-label">Anatomía Funcional y Biomecánica <span class="nivel-valor">{{ $promedios->anatomia }}/7</span></div>
                <div class="nivel-barra">
                    <div class="nivel-barra-interno nivel-empatia" style="width: {{ ($promedios->anatomia/7)*100 }}%"></div>
                </div>
            </div>
            <div class="w-full">
                <div class="nivel-label">Fisiología del Ejercicio <span class="nivel-valor">{{ $promedios->fisiologia }}/7</span></div>
                <div class="nivel-barra">
                    <div class="nivel-barra-interno nivel-empatia" style="width: {{ ($promedios->fisiologia/7)*100 }}%"></div>
                </div>
            </div>
            <div class="w-full">
                <div class="nivel-label">Programación del entrenamiento <span class="nivel-valor">{{ $promedios->programacion }}/7</span></div>
                <div class="nivel-barra">
                    <div class="nivel-barra-interno nivel-empatia" style="width: {{ ($promedios->programacion/7)*100 }}%"></div>
                </div>
            </div>
        </div>
        <div class="mb-4">
            <div class="w-full">
                <div class="nivel-label">Poblaciones especiales y Fisiología clínica <span class="nivel-valor">{{ $promedios->poblacion }}/7</span></div>
                <div class="nivel-barra">
                    <div class="nivel-barra-interno nivel-empatia" style="width: {{ ($promedios->poblacion/7)*100 }}%"></div>
                </div>
            </div>
            <div class="w-full">
                <div class="nivel-label">Psicología del deporte <span class="nivel-valor">{{ $promedios->psicologia }}/7</span></div>
                <div class="nivel-barra">
                    <div class="nivel-barra-interno nivel-empatia" style="width: {{ ($promedios->psicologia/7)*100 }}%"></div>
                </div>
            </div>
        </div>
        @else
        <div class="nivel-label">Sin evaluación</div>
        @endif
    </div>
</body>

</html>