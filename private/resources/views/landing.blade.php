<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Ampaya — Software para administrar tu gimnasio</title>
    <meta name="description" content="Agenda, cobros, seguimiento de clientes y gamificación en una sola plataforma para gimnasios.">
    <link rel="icon" href="/logo.png" type="image/png">
    <link rel="shortcut icon" href="/logo.png" type="image/png">

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        :root {
            --primary: #489ddf;
            --secondary: #3f8ac4;
            --primary-dark: #2f6ea3;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            font-family: 'Figtree', ui-sans-serif, system-ui, sans-serif;
        }

        .btn-primary {
            background-color: var(--primary);
            transition: background-color .15s ease, transform .15s ease;
        }

        .btn-primary:hover {
            background-color: var(--primary-dark);
            transform: translateY(-1px);
        }

        .btn-outline {
            border: 1.5px solid var(--primary);
            color: var(--primary-dark);
            transition: background-color .15s ease;
        }

        .btn-outline:hover {
            background-color: #eef6fc;
        }

        .hero-gradient {
            background: radial-gradient(circle at 15% 15%, #5aa8e5 0%, transparent 45%), linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
        }

        .text-brand {
            color: var(--primary);
        }

        .bg-brand-soft {
            background-color: #eaf4fc;
        }

        .feature-icon {
            background-color: #eaf4fc;
            color: var(--primary-dark);
        }

        .badge-soft {
            background-color: rgba(255, 255, 255, .16);
            border: 1px solid rgba(255, 255, 255, .3);
        }

        .card-hover {
            transition: transform .18s ease, box-shadow .18s ease;
        }

        .card-hover:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 24px -8px rgba(17, 24, 39, .12);
        }

        .plan-featured {
            border: 2px solid var(--primary);
        }

        /* Mockup de la app (ilustrativo, no es una captura real) */
        .phone-frame {
            background: #0f172a;
            border-radius: 2.25rem;
            padding: .6rem;
            box-shadow: 0 30px 60px -20px rgba(15, 23, 42, .45);
        }

        .phone-screen {
            background: #f7fafc;
            border-radius: 1.75rem;
            overflow: hidden;
        }

        .ring-progress {
            background: conic-gradient(var(--primary) 0deg 252deg, #e2e8f0 252deg 360deg);
        }
    </style>
</head>

<body class="text-gray-900 antialiased">

    <!-- Nav -->
    <header class="fixed top-0 inset-x-0 z-30 bg-white/90 backdrop-blur border-b border-gray-100">
        <div class="max-w-6xl mx-auto px-6 py-3 flex items-center justify-between">
            <a href="/" class="flex items-center gap-2">
                <img src="/logo.png" alt="Ampaya" class="h-9 w-auto" onerror="this.style.display='none'">
                <span class="text-xl font-extrabold text-brand tracking-tight">Ampaya</span>
            </a>
            <nav class="hidden md:flex items-center gap-8 text-sm font-medium text-gray-600">
                <a href="#funcionalidades" class="hover:text-gray-900">Funcionalidades</a>
                <a href="#planes" class="hover:text-gray-900">Planes</a>
            </nav>
            <a href="{{ route('login') }}"
                class="btn-primary text-white text-sm font-semibold py-2 px-5 rounded-lg">
                Iniciar sesión
            </a>
        </div>
    </header>

    <!-- Hero -->
    <section class="hero-gradient pt-32 pb-24 md:pt-40 md:pb-32">
        <div class="max-w-6xl mx-auto px-6 grid lg:grid-cols-2 gap-16 items-center">
            <div>
                <span class="badge-soft text-white text-xs font-semibold tracking-wide uppercase py-1.5 px-3 rounded-full">
                    Software para gimnasios
                </span>
                <h1 class="mt-6 text-4xl md:text-5xl font-extrabold text-white leading-tight">
                    Menos planillas.<br>Más gimnasio.
                </h1>
                <p class="mt-6 text-lg text-white/90 max-w-lg">
                    Agenda, cuenta corriente, seguimiento de clientes y gamificación en una sola
                    plataforma — para administradores, entrenadores y clientes, desde el celular.
                </p>
                <div class="mt-10 flex flex-wrap items-center gap-4">
                    <a href="{{ route('login') }}"
                        class="inline-block bg-white text-gray-900 font-bold py-3 px-8 rounded-lg shadow-sm hover:shadow-md transition-shadow">
                        Iniciar sesión
                    </a>
                    <a href="#funcionalidades"
                        class="inline-block text-white font-semibold py-3 px-6 rounded-lg border border-white/40 hover:bg-white/10 transition-colors">
                        Ver funcionalidades ↓
                    </a>
                </div>
            </div>

            <!-- Mockup ilustrativo de la app -->
            <div class="flex justify-center lg:justify-end">
                <div class="phone-frame w-64">
                    <div class="phone-screen p-4 space-y-3">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-[11px] text-gray-400">Hola,</p>
                                <p class="text-sm font-bold text-gray-900">Javiera</p>
                            </div>
                            <div class="w-8 h-8 rounded-full bg-brand-soft"></div>
                        </div>

                        <div class="bg-white rounded-xl p-3 shadow-sm flex items-center gap-3">
                            <div class="w-12 h-12 rounded-full ring-progress flex items-center justify-center">
                                <div class="w-9 h-9 rounded-full bg-white flex items-center justify-center text-[10px] font-bold text-brand">
                                    70%
                                </div>
                            </div>
                            <div>
                                <p class="text-[11px] text-gray-500">Progreso semanal</p>
                                <p class="text-xs font-bold text-gray-900">5 de 7 sesiones</p>
                            </div>
                        </div>

                        <div class="bg-white rounded-xl p-3 shadow-sm">
                            <p class="text-[11px] text-gray-500 mb-1">Racha de entrenamiento 🔥</p>
                            <div class="flex items-center gap-1">
                                @for ($i = 0; $i < 7; $i++)
                                <div class="flex-1 h-1.5 rounded-full {{ $i < 5 ? 'bg-[var(--primary)]' : 'bg-gray-200' }}"></div>
                                @endfor
                            </div>
                            <p class="text-[10px] text-gray-400 mt-1">12 días seguidos</p>
                        </div>

                        <div class="bg-white rounded-xl p-3 shadow-sm">
                            <p class="text-[11px] text-gray-500 mb-1">Próxima sesión</p>
                            <p class="text-xs font-bold text-gray-900">Hoy · 18:30</p>
                            <p class="text-[11px] text-gray-500">Piernas y core</p>
                        </div>

                        <div class="bg-brand-soft rounded-xl p-3 flex items-center justify-between">
                            <span class="text-[11px] font-semibold text-brand-dark" style="color: var(--primary-dark)">Cuenta corriente</span>
                            <span class="text-[11px] font-bold text-emerald-600">Al día</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- El problema -->
    <section class="bg-white py-16 border-b border-gray-100">
        <div class="max-w-4xl mx-auto px-6 text-center">
            <h2 class="text-xl md:text-2xl font-bold text-gray-900">
                Planilla de Excel para las cuotas, WhatsApp para la agenda, cuaderno para los morosos.
            </h2>
            <p class="mt-4 text-gray-600">
                Administrar un gimnasio con herramientas sueltas significa horas perdidas cada semana
                y datos que nadie encuentra cuando hacen falta. Ampaya junta todo en un solo lugar,
                accesible desde el celular por administradores, entrenadores y clientes.
            </p>
        </div>
    </section>

    <!-- Features -->
    <section id="funcionalidades" class="max-w-6xl mx-auto px-6 py-20 scroll-mt-20">
        <div class="text-center mb-14">
            <span class="text-brand text-xs font-bold uppercase tracking-wide">Funcionalidades</span>
            <h2 class="mt-2 text-2xl md:text-3xl font-extrabold text-gray-900">Todo lo que necesita tu gimnasio</h2>
            <p class="mt-3 text-gray-600 max-w-xl mx-auto">Una plataforma, tres perfiles: administrador, entrenador y cliente.</p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">

            @php
            $features = [
                [
                    'icon' => 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z',
                    'title' => 'Agenda inteligente',
                    'desc' => 'Calendario con grilla horaria, arrastrar para reprogramar, y recordatorios automáticos por correo y WhatsApp antes de cada vencimiento.',
                ],
                [
                    'icon' => 'M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z',
                    'title' => 'Cuenta corriente al día',
                    'desc' => 'Cuotas, pagos parciales y morosos con aging automático (30/60/90 días). Sabes quién debe y desde cuándo, sin buscar en ningún cuaderno.',
                ],
                [
                    'icon' => 'M13 10V3L4 14h7v7l9-11h-7z',
                    'title' => 'Gamificación',
                    'desc' => 'Tus clientes ven su racha de entrenamiento y puntos acumulados directo en la app — más motivación, más retención.',
                ],
                [
                    'icon' => 'M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m6-1.13a4 4 0 10-4-4 4 4 0 004 4zm6 0a4 4 0 10-4-4',
                    'title' => 'Un perfil para cada rol',
                    'desc' => 'Cliente, entrenador y administrador, cada uno con su propia vista desde el celular — sin confundir permisos ni pantallas.',
                ],
                [
                    'icon' => 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2M5 21h2m0 0h10M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 6v-3a1 1 0 011-1h0a1 1 0 011 1v3',
                    'title' => 'Multi-gimnasio',
                    'desc' => 'Administra uno o varios gimnasios desde la misma cuenta, cada uno con sus propios colores y funcionalidades activadas.',
                ],
                [
                    'icon' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
                    'title' => 'Evaluación y seguimiento',
                    'desc' => 'Evaluación inicial completa, métricas corporales, evolución de carga y biblioteca de ejercicios para cada cliente.',
                ],
            ];
            @endphp

            @foreach ($features as $f)
            <div class="card-hover bg-white border border-gray-100 rounded-2xl p-6">
                <div class="feature-icon w-12 h-12 rounded-lg flex items-center justify-center mb-4">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="{{ $f['icon'] }}" />
                    </svg>
                </div>
                <h3 class="font-bold text-gray-900 mb-1">{{ $f['title'] }}</h3>
                <p class="text-gray-600 text-sm">{{ $f['desc'] }}</p>
            </div>
            @endforeach

        </div>
    </section>

    <!-- Planes -->
    <section id="planes" class="bg-gray-50 py-20 scroll-mt-20 border-y border-gray-100">
        <div class="max-w-6xl mx-auto px-6">
            <div class="text-center mb-14">
                <span class="text-brand text-xs font-bold uppercase tracking-wide">Planes</span>
                <h2 class="mt-2 text-2xl md:text-3xl font-extrabold text-gray-900">Activa lo que tu gimnasio necesita</h2>
                <p class="mt-3 text-gray-600 max-w-xl mx-auto">
                    Todos los planes incluyen agenda, cuenta corriente y cuotas — el núcleo de la
                    operación diaria. Los superiores suman módulos adicionales.
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 items-start">

                <div class="card-hover bg-white rounded-2xl border border-gray-200 p-8">
                    <h3 class="font-extrabold text-lg text-gray-900">Starter</h3>
                    <p class="text-sm text-gray-500 mt-1">Lo esencial para operar</p>
                    <ul class="mt-6 space-y-3 text-sm text-gray-700">
                        <li class="flex gap-2"><span class="text-emerald-600">✓</span> Agenda y calendario</li>
                        <li class="flex gap-2"><span class="text-emerald-600">✓</span> Cuenta corriente y cobros</li>
                        <li class="flex gap-2"><span class="text-emerald-600">✓</span> Clientes, entrenadores y evaluación inicial</li>
                        <li class="flex gap-2"><span class="text-emerald-600">✓</span> Recordatorios automáticos</li>
                    </ul>
                </div>

                <div class="card-hover plan-featured bg-white rounded-2xl p-8 relative">
                    <span class="absolute -top-3 left-1/2 -translate-x-1/2 bg-[var(--primary)] text-white text-xs font-bold py-1 px-3 rounded-full">
                        Más elegido
                    </span>
                    <h3 class="font-extrabold text-lg text-gray-900">Estándar</h3>
                    <p class="text-sm text-gray-500 mt-1">Más cercanía con tus clientes</p>
                    <ul class="mt-6 space-y-3 text-sm text-gray-700">
                        <li class="flex gap-2"><span class="text-emerald-600">✓</span> Todo lo de Starter</li>
                        <li class="flex gap-2"><span class="text-emerald-600">✓</span> Gamificación (racha y puntos)</li>
                        <li class="flex gap-2"><span class="text-emerald-600">✓</span> Compartir progreso y entrenamientos</li>
                        <li class="flex gap-2"><span class="text-emerald-600">✓</span> Catálogo de beneficios y convenios</li>
                    </ul>
                </div>

                <div class="card-hover bg-white rounded-2xl border border-gray-200 p-8">
                    <h3 class="font-extrabold text-lg text-gray-900">Pro</h3>
                    <p class="text-sm text-gray-500 mt-1">La plataforma completa</p>
                    <ul class="mt-6 space-y-3 text-sm text-gray-700">
                        <li class="flex gap-2"><span class="text-emerald-600">✓</span> Todo lo de Estándar</li>
                        <li class="flex gap-2"><span class="text-emerald-600">✓</span> Open Gym y plan de alimentación</li>
                        <li class="flex gap-2"><span class="text-emerald-600">✓</span> Métricas de perímetros y reportes en PDF</li>
                        <li class="flex gap-2"><span class="text-emerald-600">✓</span> Encuestas, videos y reportes de agenda</li>
                    </ul>
                </div>

            </div>
        </div>
    </section>

    <!-- CTA final -->
    <section class="hero-gradient">
        <div class="max-w-4xl mx-auto px-6 py-16 text-center">
            <h2 class="text-2xl md:text-3xl font-extrabold text-white">¿Ya tienes acceso a tu gimnasio en Ampaya?</h2>
            <p class="mt-3 text-white/90">Entra con tu correo y contraseña.</p>
            <div class="mt-8">
                <a href="{{ route('login') }}"
                    class="inline-block bg-white text-gray-900 font-bold py-3 px-8 rounded-lg shadow-sm hover:shadow-md transition-shadow">
                    Iniciar sesión
                </a>
            </div>
        </div>
    </section>

    <footer class="bg-white">
        <div class="max-w-6xl mx-auto px-6 py-12 grid sm:grid-cols-3 gap-8">
            <div>
                <div class="flex items-center gap-2">
                    <img src="/logo.png" alt="Ampaya" class="h-7 w-auto" onerror="this.style.display='none'">
                    <span class="text-lg font-extrabold text-brand">Ampaya</span>
                </div>
                <p class="mt-2 text-sm text-gray-500">Software de administración para gimnasios.</p>
            </div>
            <div>
                <p class="text-xs font-bold text-gray-400 uppercase tracking-wide mb-3">Producto</p>
                <ul class="space-y-2 text-sm text-gray-600">
                    <li><a href="#funcionalidades" class="hover:text-gray-900">Funcionalidades</a></li>
                    <li><a href="#planes" class="hover:text-gray-900">Planes</a></li>
                </ul>
            </div>
            <div>
                <p class="text-xs font-bold text-gray-400 uppercase tracking-wide mb-3">Cuenta</p>
                <ul class="space-y-2 text-sm text-gray-600">
                    <li><a href="{{ route('login') }}" class="hover:text-gray-900">Iniciar sesión</a></li>
                </ul>
            </div>
        </div>
        <div class="border-t border-gray-100">
            <div class="max-w-6xl mx-auto px-6 py-6 text-center text-sm text-gray-500">
                © {{ date('Y') }} Ampaya
            </div>
        </div>
    </footer>

</body>

</html>
