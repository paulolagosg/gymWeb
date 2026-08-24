<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Ampaya — Software para administrar tu gimnasio</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        :root {
            --primary: #489ddf;
            --secondary: #3f8ac4;
        }

        body {
            font-family: 'Figtree', ui-sans-serif, system-ui, sans-serif;
        }

        .btn-primary {
            background-color: var(--primary);
            transition: background-color .15s ease;
        }

        .btn-primary:hover {
            background-color: var(--secondary);
        }

        .hero-gradient {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
        }

        .text-brand {
            color: var(--primary);
        }

        .feature-icon {
            background-color: #eaf4fc;
            color: var(--secondary);
        }
    </style>
</head>

<body class="text-gray-900 antialiased">

    <!-- Nav -->
    <header class="border-b border-gray-100">
        <div class="max-w-6xl mx-auto px-6 py-4 flex items-center justify-between">
            <span class="text-xl font-extrabold text-brand">Ampaya</span>
            <a href="{{ route('login') }}"
                class="btn-primary text-white text-sm font-semibold py-2 px-5 rounded-lg">
                Iniciar sesión
            </a>
        </div>
    </header>

    <!-- Hero -->
    <section class="hero-gradient">
        <div class="max-w-6xl mx-auto px-6 py-20 md:py-28 text-center">
            <h1 class="text-3xl md:text-5xl font-extrabold text-white leading-tight max-w-3xl mx-auto">
                La app todo-en-uno para administrar tu gimnasio
            </h1>
            <p class="mt-6 text-lg text-white/90 max-w-2xl mx-auto">
                Agenda, cobros, seguimiento de clientes y gamificación en una sola plataforma.
                Para gimnasios que quieren dejar las planillas de Excel atrás.
            </p>
            <div class="mt-10">
                <a href="{{ route('login') }}"
                    class="inline-block bg-white text-gray-900 font-bold py-3 px-8 rounded-lg shadow-sm hover:shadow-md transition-shadow">
                    Iniciar sesión
                </a>
            </div>
        </div>
    </section>

    <!-- Features -->
    <section class="max-w-6xl mx-auto px-6 py-20">
        <div class="text-center mb-14">
            <h2 class="text-2xl md:text-3xl font-extrabold text-gray-900">Todo lo que necesita tu gimnasio</h2>
            <p class="mt-3 text-gray-600 max-w-xl mx-auto">Sin planillas sueltas, sin WhatsApp perdido entre chats.</p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-8">

            <div>
                <div class="feature-icon w-12 h-12 rounded-lg flex items-center justify-center mb-4">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                </div>
                <h3 class="font-bold text-gray-900 mb-1">Agenda inteligente</h3>
                <p class="text-gray-600 text-sm">
                    Calendario con grilla horaria, arrastrar para reprogramar, y recordatorios
                    automáticos por correo y WhatsApp antes de cada vencimiento.
                </p>
            </div>

            <div>
                <div class="feature-icon w-12 h-12 rounded-lg flex items-center justify-center mb-4">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                    </svg>
                </div>
                <h3 class="font-bold text-gray-900 mb-1">Cuenta corriente al día</h3>
                <p class="text-gray-600 text-sm">
                    Cuotas, pagos parciales y morosos con aging automático (30/60/90 días).
                    Sabes quién debe y desde cuándo, sin buscar en ningún cuaderno.
                </p>
            </div>

            <div>
                <div class="feature-icon w-12 h-12 rounded-lg flex items-center justify-center mb-4">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                </div>
                <h3 class="font-bold text-gray-900 mb-1">Gamificación</h3>
                <p class="text-gray-600 text-sm">
                    Tus clientes ven su racha de entrenamiento y puntos acumulados directo en
                    la app — más motivación, más retención.
                </p>
            </div>

            <div>
                <div class="feature-icon w-12 h-12 rounded-lg flex items-center justify-center mb-4">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m6-1.13a4 4 0 10-4-4 4 4 0 004 4zm6 0a4 4 0 10-4-4" />
                    </svg>
                </div>
                <h3 class="font-bold text-gray-900 mb-1">Un perfil para cada rol</h3>
                <p class="text-gray-600 text-sm">
                    Clientes, entrenadores y administradores, cada uno con su propia vista desde
                    el celular — sin confundir permisos ni pantallas.
                </p>
            </div>

            <div>
                <div class="feature-icon w-12 h-12 rounded-lg flex items-center justify-center mb-4">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2M5 21h2m0 0h10M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 6v-3a1 1 0 011-1h0a1 1 0 011 1v3" />
                    </svg>
                </div>
                <h3 class="font-bold text-gray-900 mb-1">Multi-gimnasio</h3>
                <p class="text-gray-600 text-sm">
                    Administra uno o varios gimnasios desde la misma cuenta, cada uno con sus
                    propios colores, planes y funcionalidades activadas.
                </p>
            </div>

            <div>
                <div class="feature-icon w-12 h-12 rounded-lg flex items-center justify-center mb-4">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                    </svg>
                </div>
                <h3 class="font-bold text-gray-900 mb-1">Planes flexibles</h3>
                <p class="text-gray-600 text-sm">
                    Starter, Estándar o Pro: activa solo las funcionalidades que tu gimnasio
                    necesita, y súmalas cuando quieras.
                </p>
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

    <footer class="border-t border-gray-100">
        <div class="max-w-6xl mx-auto px-6 py-8 text-center text-sm text-gray-500">
            © {{ date('Y') }} Ampaya
        </div>
    </footer>

</body>

</html>
