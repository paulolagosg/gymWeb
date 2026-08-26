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
                <a href="#contacto" class="hover:text-gray-900">Contacto</a>
            </nav>
            <a href="https://play.google.com/store/apps/details?id=gym.ampaya.cl" target="_blank" rel="noopener"
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
                    <a href="https://play.google.com/store/apps/details?id=gym.ampaya.cl" target="_blank" rel="noopener"
                        class="inline-block bg-white text-gray-900 font-bold py-3 px-8 rounded-lg shadow-sm hover:shadow-md transition-shadow">
                        Iniciar sesión
                    </a>
                    <a href="#funcionalidades"
                        class="inline-block text-white font-semibold py-3 px-6 rounded-lg border border-white/40 hover:bg-white/10 transition-colors">
                        Ver funcionalidades ↓
                    </a>
                </div>
            </div>

            <!-- Captura real de la app -->
            <div class="flex justify-center lg:justify-end">
                <div class="phone-frame w-64">
                    <img src="/screenshots/portada-cliente.png" alt="Portada del cliente en Ampaya"
                        class="phone-screen w-full block">
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
                    'icon' => 'M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z',
                    'title' => 'Beneficios y convenios',
                    'desc' => 'Catálogo de descuentos en tiendas y servicios aliados, disponible para tus clientes directo en la app.',
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

    <!-- Galería de capturas reales -->
    <section class="bg-white py-20">
        <div class="max-w-6xl mx-auto px-6">
            <div class="text-center mb-14">
                <span class="text-brand text-xs font-bold uppercase tracking-wide">Así se ve por dentro</span>
                <h2 class="mt-2 text-2xl md:text-3xl font-extrabold text-gray-900">Capturas reales de la app</h2>
                <p class="mt-3 text-gray-600 max-w-xl mx-auto">Sin maquetas — así se ve Ampaya funcionando hoy.</p>
            </div>

            @php
            $screenshots = [
                ['file' => 'dashboard.png', 'label' => 'Dashboard del entrenador'],
                ['file' => 'cuenta-corriente.png', 'label' => 'Cuenta corriente'],
                ['file' => 'agenda.png', 'label' => 'Agenda'],
                ['file' => 'calendario.png', 'label' => 'Calendario con grilla horaria'],
                ['file' => 'evaluacion-inicial.png', 'label' => 'Evaluación inicial'],
            ];
            @endphp

            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-6 items-start">
                @foreach ($screenshots as $s)
                <div class="text-center">
                    <div class="phone-frame w-full max-w-[160px] mx-auto">
                        <img src="/screenshots/{{ $s['file'] }}" alt="{{ $s['label'] }}" class="phone-screen w-full block">
                    </div>
                    <p class="mt-3 text-xs font-medium text-gray-600">{{ $s['label'] }}</p>
                </div>
                @endforeach
            </div>
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

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 items-start">

                <div class="card-hover bg-white rounded-2xl border-2 border-dashed border-gray-300 p-8">
                    <h3 class="font-extrabold text-lg text-gray-900">Prueba gratis</h3>
                    <p class="text-sm text-gray-500 mt-1">7 días, sin costo</p>
                    <p class="mt-4 text-3xl font-extrabold text-gray-900">
                        $0
                    </p>
                    <ul class="mt-6 space-y-3 text-sm text-gray-700">
                        <li class="flex gap-2"><span class="text-emerald-600">✓</span> Acceso completo por 7 días</li>
                        <li class="flex gap-2"><span class="text-emerald-600">✓</span> Todas las funcionalidades de Pro</li>
                        <li class="flex gap-2"><span class="text-emerald-600">✓</span> Sin tarjeta ni compromiso</li>
                    </ul>
                    <button type="button" onclick="openLeadModal('trial', 'Prueba gratis')"
                        class="mt-6 w-full py-3 rounded-lg font-bold border-2 border-dashed border-gray-300 text-gray-700 hover:border-gray-400 transition-colors">
                        Empezar prueba gratis
                    </button>
                </div>

                <div class="card-hover bg-white rounded-2xl border border-gray-200 p-8">
                    <h3 class="font-extrabold text-lg text-gray-900">Starter</h3>
                    <p class="text-sm text-gray-500 mt-1">Lo esencial para operar</p>
                    <p class="mt-4 text-3xl font-extrabold text-gray-900">
                        ${{ number_format($precios['starter'] ?? 0, 0, ',', '.') }}<span class="text-sm font-medium text-gray-500">/mes</span>
                    </p>
                    <ul class="mt-6 space-y-3 text-sm text-gray-700">
                        <li class="flex gap-2"><span class="text-emerald-600">✓</span> Agenda y calendario</li>
                        <li class="flex gap-2"><span class="text-emerald-600">✓</span> Cuenta corriente y cobros</li>
                        <li class="flex gap-2"><span class="text-emerald-600">✓</span> Clientes, entrenadores y evaluación inicial</li>
                        <li class="flex gap-2"><span class="text-emerald-600">✓</span> Recordatorios automáticos</li>
                    </ul>
                    <button type="button" onclick="openLeadModal('starter', 'Starter')"
                        class="mt-6 w-full py-3 rounded-lg font-bold border border-gray-300 text-gray-900 hover:border-gray-400 transition-colors">
                        Contratar Starter
                    </button>
                </div>

                <div class="card-hover plan-featured bg-white rounded-2xl p-8 relative">
                    <span class="absolute -top-3 left-1/2 -translate-x-1/2 bg-[var(--primary)] text-white text-xs font-bold py-1 px-3 rounded-full">
                        Más elegido
                    </span>
                    <h3 class="font-extrabold text-lg text-gray-900">Estándar</h3>
                    <p class="text-sm text-gray-500 mt-1">Más cercanía con tus clientes</p>
                    <p class="mt-4 text-3xl font-extrabold text-gray-900">
                        ${{ number_format($precios['estandar'] ?? 0, 0, ',', '.') }}<span class="text-sm font-medium text-gray-500">/mes</span>
                    </p>
                    <ul class="mt-6 space-y-3 text-sm text-gray-700">
                        <li class="flex gap-2"><span class="text-emerald-600">✓</span> Todo lo de Starter</li>
                        <li class="flex gap-2"><span class="text-emerald-600">✓</span> Gamificación (racha y puntos)</li>
                        <li class="flex gap-2"><span class="text-emerald-600">✓</span> Compartir progreso y entrenamientos</li>
                        <li class="flex gap-2"><span class="text-emerald-600">✓</span> Catálogo de beneficios y convenios</li>
                    </ul>
                    <button type="button" onclick="openLeadModal('estandar', 'Estándar')"
                        class="mt-6 w-full py-3 rounded-lg font-bold bg-[var(--primary)] text-white hover:opacity-90 transition-opacity">
                        Contratar Estándar
                    </button>
                </div>

                <div class="card-hover bg-white rounded-2xl border border-gray-200 p-8">
                    <h3 class="font-extrabold text-lg text-gray-900">Pro</h3>
                    <p class="text-sm text-gray-500 mt-1">La plataforma completa</p>
                    <p class="mt-4 text-3xl font-extrabold text-gray-900">
                        ${{ number_format($precios['pro'] ?? 0, 0, ',', '.') }}<span class="text-sm font-medium text-gray-500">/mes</span>
                    </p>
                    <ul class="mt-6 space-y-3 text-sm text-gray-700">
                        <li class="flex gap-2"><span class="text-emerald-600">✓</span> Todo lo de Estándar</li>
                        <li class="flex gap-2"><span class="text-emerald-600">✓</span> Open Gym y plan de alimentación</li>
                        <li class="flex gap-2"><span class="text-emerald-600">✓</span> Métricas de perímetros y reportes en PDF</li>
                        <li class="flex gap-2"><span class="text-emerald-600">✓</span> Encuestas, videos y reportes de agenda</li>
                    </ul>
                    <button type="button" onclick="openLeadModal('pro', 'Pro')"
                        class="mt-6 w-full py-3 rounded-lg font-bold border border-gray-300 text-gray-900 hover:border-gray-400 transition-colors">
                        Contratar Pro
                    </button>
                </div>

            </div>
        </div>
    </section>

    <!-- Contacto -->
    <section id="contacto" class="max-w-4xl mx-auto px-6 py-20 scroll-mt-20 text-center">
        <span class="text-brand text-xs font-bold uppercase tracking-wide">Contacto</span>
        <h2 class="mt-2 text-2xl md:text-3xl font-extrabold text-gray-900">¿Quieres llevar Ampaya a tu gimnasio?</h2>
        <p class="mt-3 text-gray-600 max-w-lg mx-auto">Escríbenos o llámanos y te ayudamos a partir.</p>

        <div class="mt-10 flex flex-col sm:flex-row gap-6 justify-center">
            <a href="mailto:contacto@ampaya.cl" class="card-hover flex items-center gap-3 bg-white border border-gray-100 rounded-2xl p-6 sm:w-72">
                <div class="feature-icon w-12 h-12 rounded-lg flex items-center justify-center shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                    </svg>
                </div>
                <div class="text-left">
                    <p class="text-xs text-gray-500">Correo</p>
                    <p class="font-semibold text-gray-900">contacto@ampaya.cl</p>
                </div>
            </a>

            <a href="https://wa.me/56992803469" target="_blank" rel="noopener" class="card-hover flex items-center gap-3 bg-white border border-gray-100 rounded-2xl p-6 sm:w-72">
                <div class="feature-icon w-12 h-12 rounded-lg flex items-center justify-center shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                    </svg>
                </div>
                <div class="text-left">
                    <p class="text-xs text-gray-500">Teléfono / WhatsApp</p>
                    <p class="font-semibold text-gray-900">+56 9 9280 3469</p>
                </div>
            </a>
        </div>
    </section>

    <!-- CTA final -->
    <section class="hero-gradient">
        <div class="max-w-4xl mx-auto px-6 py-16 text-center">
            <h2 class="text-2xl md:text-3xl font-extrabold text-white">¿Ya tienes acceso a tu gimnasio en Ampaya?</h2>
            <p class="mt-3 text-white/90">Descarga la app y entra con tu correo y contraseña.</p>
            <div class="mt-8">
                <a href="https://play.google.com/store/apps/details?id=gym.ampaya.cl" target="_blank" rel="noopener"
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
                <p class="text-xs font-bold text-gray-400 uppercase tracking-wide mb-3">Contacto</p>
                <ul class="space-y-2 text-sm text-gray-600">
                    <li><a href="mailto:contacto@ampaya.cl" class="hover:text-gray-900">contacto@ampaya.cl</a></li>
                    <li><a href="https://wa.me/56992803469" target="_blank" rel="noopener" class="hover:text-gray-900">+56 9 9280 3469</a></li>
                    <li><a href="https://play.google.com/store/apps/details?id=gym.ampaya.cl" target="_blank" rel="noopener" class="hover:text-gray-900">Iniciar sesión</a></li>
                </ul>
            </div>
        </div>
        <div class="border-t border-gray-100">
            <div class="max-w-6xl mx-auto px-6 py-6 text-center text-sm text-gray-500">
                © {{ date('Y') }} Ampaya
            </div>
        </div>
    </footer>

    <!-- Modal de solicitud de contacto (selector de plan) -->
    <div id="leadModal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-start justify-center px-4 py-10 overflow-y-auto">
        <div class="bg-white rounded-2xl p-8 max-w-md w-full relative">
            <button type="button" onclick="closeLeadModal()" aria-label="Cerrar"
                class="absolute top-4 right-4 text-gray-400 hover:text-gray-600 text-2xl leading-none">&times;</button>

            <h3 class="text-xl font-extrabold text-gray-900">Quiero <span id="leadModalPlanLabel"></span></h3>
            <p class="mt-1 text-sm text-gray-600">Déjanos tus datos y te contactamos para partir.</p>

            @if(session('lead_success'))
                <p id="leadSuccessBanner" class="mt-4 rounded-lg bg-emerald-50 text-emerald-700 text-sm p-3">
                    ¡Listo! Recibimos tu solicitud, te contactaremos pronto.
                </p>
            @endif

            @if($errors->any())
                <ul id="leadErrorsBanner" class="mt-4 rounded-lg bg-red-50 text-red-700 text-sm p-3 space-y-1">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            @endif

            <form method="POST" action="{{ route('solicitudes-contacto.store') }}" class="mt-4 space-y-3">
                @csrf
                <input type="hidden" name="plan" id="leadModalPlanInput" value="{{ old('plan') }}">

                <input type="text" name="nombre_gimnasio" placeholder="Nombre del gimnasio" required
                    value="{{ old('nombre_gimnasio') }}"
                    class="w-full rounded-lg border border-gray-300 px-4 py-2 text-sm">

                <input type="text" name="nombre_contacto" placeholder="Tu nombre" required
                    value="{{ old('nombre_contacto') }}"
                    class="w-full rounded-lg border border-gray-300 px-4 py-2 text-sm">

                <input type="email" name="email" placeholder="Correo" required
                    value="{{ old('email') }}"
                    class="w-full rounded-lg border border-gray-300 px-4 py-2 text-sm">

                <input type="text" name="telefono" placeholder="Teléfono (opcional)"
                    value="{{ old('telefono') }}"
                    class="w-full rounded-lg border border-gray-300 px-4 py-2 text-sm">

                <textarea name="mensaje" placeholder="Cuéntanos algo más (opcional)" rows="3"
                    class="w-full rounded-lg border border-gray-300 px-4 py-2 text-sm">{{ old('mensaje') }}</textarea>

                <button type="submit"
                    class="w-full py-3 rounded-lg font-bold bg-[var(--primary)] text-white hover:opacity-90 transition-opacity">
                    Enviar
                </button>
            </form>
        </div>
    </div>

    <script>
        // Abre el modal para que el usuario empiece una solicitud nueva — por eso
        // limpia cualquier aviso de éxito/error que haya quedado de un envío anterior
        // (si no, reabrir el modal para OTRO plan seguía mostrando el "¡Listo!" viejo).
        function openLeadModal(plan, label) {
            document.getElementById('leadSuccessBanner')?.remove();
            document.getElementById('leadErrorsBanner')?.remove();
            document.getElementById('leadModalPlanInput').value = plan;
            document.getElementById('leadModalPlanLabel').textContent = label;
            document.getElementById('leadModal').classList.remove('hidden');
        }

        function closeLeadModal() {
            document.getElementById('leadModal').classList.add('hidden');
        }

        @php
            $leadPlanLabels = ['trial' => 'Prueba gratis', 'starter' => 'Starter', 'estandar' => 'Estándar', 'pro' => 'Pro'];
            $leadReopenPlan = old('plan');
        @endphp

        @if(session('lead_success') || $errors->any())
            // Reabre el modal para mostrar el resultado del envío que acaba de ocurrir
            // (redirect de vuelta tras el POST) — a propósito NO pasa por openLeadModal()
            // de arriba, para no borrar el aviso que justo queremos mostrar.
            document.addEventListener('DOMContentLoaded', function () {
                document.getElementById('leadModalPlanInput').value = {{ Illuminate\Support\Js::from($leadReopenPlan ?? '') }};
                document.getElementById('leadModalPlanLabel').textContent = {{ Illuminate\Support\Js::from($leadPlanLabels[$leadReopenPlan] ?? '') }};
                document.getElementById('leadModal').classList.remove('hidden');
            });
        @endif
    </script>

</body>

</html>
