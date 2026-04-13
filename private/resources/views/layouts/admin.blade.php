<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }}</title>

    <!-- jQuery PRIMERO -->
    <script src="https://code.jquery.com/jquery-3.7.1.js"></script>

    <!-- Select2 CSS y JS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>
    <!-- El resto de librerías -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- Vite DESPUÉS -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- Más librerías -->
    <link rel="stylesheet" href="https://unpkg.com/tippy.js@6/dist/tippy.css" />
    <script src="https://unpkg.com/@popperjs/core@2"></script>
    <script src="https://unpkg.com/tippy.js@6"></script>

</head>

<body class="font-sans antialiased bg-stone-100 text-stone-900">
    @php
    $currentUser = Auth::user();
    $roleLabel = $currentUser->id_tipo_usuario == 2
    ? $currentUser->nombre_clasificacion
    : optional($currentUser->tipoUsuario)->nombre;

    $menuSections = [
    [
    'title' => 'Principal',
    'items' => [
    ['label' => 'Panel de control', 'route' => 'dashboard', 'icon' => 'fa-chart-line', 'patterns' => ['dashboard', 'panel']],
    ['label' => 'Clientes', 'route' => 'clientes.index', 'icon' => 'fa-users', 'patterns' => ['clientes.*']],
    ['label' => 'Agendas', 'route' => 'agendas.index', 'icon' => 'fa-calendar-days', 'patterns' => ['agendas.*']],
    ['label' => 'Ejercicios', 'route' => 'ejercicios.index', 'icon' => 'fa-dumbbell', 'patterns' => ['ejercicios.*']],
    ],
    ],
    [
    'title' => 'Operacion',
    'items' => [
    ['label' => 'Caja', 'route' => 'caja.index', 'icon' => 'fa-wallet', 'patterns' => ['caja.*'], 'can' => $currentUser->id_tipo_usuario == 1],
    ['label' => 'Mensajeria', 'route' => 'mensajes.index', 'icon' => 'fa-envelope', 'patterns' => ['mensajes.*']],
    ['label' => 'Tareas', 'route' => 'tareas.index', 'icon' => 'fa-list-check', 'patterns' => ['tareas.*']],
    ['label' => 'Cursos', 'route' => 'cursos.index', 'icon' => 'fa-graduation-cap', 'patterns' => ['cursos.*']],
    ['label' => 'Entrenadores', 'route' => 'entrenadores.index', 'icon' => 'fa-user-group', 'patterns' => ['entrenadores.*'], 'can' => $currentUser->id_tipo_usuario <= 2 || $currentUser->id_clasificacion == 3],
        ],
        ],
        [
        'title' => 'Administracion',
        'items' => [
        ['label' => 'Planes', 'route' => 'planes.index', 'icon' => 'fa-layer-group', 'patterns' => ['planes.*'], 'can' => $currentUser->id_tipo_usuario == 1],
        ['label' => 'Usuarios', 'route' => 'usuarios.index', 'icon' => 'fa-user-gear', 'patterns' => ['usuarios.*'], 'can' => $currentUser->id_tipo_usuario == 1],
        ['label' => 'Pagos entrenadores', 'route' => 'pagos_entrenadores.index', 'icon' => 'fa-money-check-dollar', 'patterns' => ['pagos_entrenadores.*'], 'can' => $currentUser->id_tipo_usuario == 1],
        ['label' => 'Evaluacion inicial', 'route' => 'evaluacion-inicial.catalogo', 'icon' => 'fa-clipboard-list', 'patterns' => ['evaluacion-inicial.*'], 'can' => $currentUser->id_tipo_usuario == 1],
        ],
        ],
        ];

        $currentMenuItem = collect($menuSections)
        ->flatMap(fn ($section) => $section['items'])
        ->first(function ($item) {
        return request()->routeIs(...$item['patterns']);
        });

        $currentSectionLabel = $currentMenuItem['label'] ?? 'Panel';
        @endphp

        <div class="min-h-screen bg-[radial-gradient(circle_at_top,_rgba(245,158,11,0.14),_transparent_28%),linear-gradient(180deg,_#f5f5f4_0%,_#fafaf9_24%,_#f5f5f4_100%)]">
            <div id="mobile-sidebar-shell" class="fixed inset-0 z-50 hidden overflow-hidden md:hidden">
                <div id="sidebar-backdrop" class="absolute inset-0 bg-stone-950/70"></div>
                <aside id="mobile-sidebar" class="absolute inset-y-0 left-0 z-10 flex h-[100dvh] w-72 max-w-[85vw] flex-col overflow-y-auto overscroll-contain border-r border-stone-800 bg-white text-stone-100 shadow-[0_0_0_1px_rgba(255,255,255,0.04),0_24px_60px_rgba(0,0,0,0.65)]" style="-webkit-overflow-scrolling: touch;">
                    @include('layouts.sidebar-content', ['mobile' => true, 'currentUser' => $currentUser, 'roleLabel' => $roleLabel, 'menuSections' => $menuSections])
                </aside>
            </div>

            <div class="flex min-h-screen">
                <aside class="hidden h-screen w-72 shrink-0 flex-col overflow-hidden border-r border-stone-200 bg-white text-stone-100 shadow-2xl md:fixed md:inset-y-0 md:left-0 md:flex">
                    @include('layouts.sidebar-content', ['mobile' => false, 'currentUser' => $currentUser, 'menuSections' => $menuSections])
                </aside>

                <div class="flex min-h-screen w-full flex-1 flex-col md:pl-72">
                    <header class="sticky top-0 z-30 border-b border-stone-200/80 bg-white/85 backdrop-blur-xl">
                        <div class="mx-auto flex w-full max-w-7xl items-center justify-between gap-4 px-4 py-4 sm:px-6 lg:px-8 xl:px-10">
                            <div class="flex min-w-0 items-center gap-3">
                                <button type="button" onclick="window.adminSidebar && window.adminSidebar.open()" class="inline-flex h-11 w-11 items-center justify-center rounded-2xl border border-stone-200 bg-white text-stone-700 shadow-sm transition hover:border-stone-300 hover:text-stone-950 md:hidden">
                                    <i class="fa-solid fa-bars"></i>
                                </button>

                                <div class="min-w-0">
                                    <h2 class="truncate text-lg font-semibold text-stone-900 sm:text-xl">{{ $currentSectionLabel }}</h2>
                                </div>
                            </div>

                            <div class="hidden items-center gap-3 md:flex">
                                <div class="rounded-full border border-stone-200 bg-stone-50 px-3 py-2 text-sm font-medium text-stone-600">
                                    {{ $roleLabel }}
                                </div>

                                <x-dropdown align="right" width="56">
                                    <x-slot name="trigger">
                                        <button class="inline-flex items-center gap-3 rounded-2xl border border-stone-200 bg-white px-3 py-2 text-sm font-medium text-stone-700 shadow-sm transition hover:border-stone-300 hover:text-stone-950 focus:outline-none">
                                            <span class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-stone-900 text-sm font-semibold text-white">
                                                {{ strtoupper(substr($currentUser->name, 0, 1)) }}
                                            </span>
                                            <span class="hidden text-left sm:block">
                                                <span class="block font-semibold">{{ $currentUser->name }}</span>
                                                <span class="block text-xs text-stone-500">{{ $currentUser->email }}</span>
                                            </span>
                                            <i class="fa-solid fa-chevron-down text-xs text-stone-400"></i>
                                        </button>
                                    </x-slot>

                                    <x-slot name="content">
                                        <x-dropdown-link :href="route('profile.edit')">
                                            Perfil
                                        </x-dropdown-link>

                                        <form method="POST" action="{{ route('logout') }}">
                                            @csrf
                                            <x-dropdown-link :href="route('logout')" onclick="event.preventDefault(); this.closest('form').submit();">
                                                Cerrar sesion
                                            </x-dropdown-link>
                                        </form>
                                    </x-slot>
                                </x-dropdown>
                            </div>
                        </div>
                    </header>

                    <main class="flex-1">
                        <div class="mx-auto w-full px-4 py-6 sm:px-6 lg:px-8 xl:px-10">
                            {{ $slot }}
                        </div>
                    </main>
                </div>
            </div>
        </div>

        <script>
            document.addEventListener('DOMContentLoaded', function() {
                tippy('[data-tippy-content]');

                const shell = document.getElementById('mobile-sidebar-shell');
                const backdrop = document.getElementById('sidebar-backdrop');
                const desktopQuery = window.matchMedia('(min-width: 768px)');

                if (!shell || !backdrop) {
                    return;
                }

                const openSidebar = () => {
                    if (desktopQuery.matches) {
                        return;
                    }

                    shell.classList.remove('hidden');
                    document.body.classList.add('overflow-hidden');
                };

                const closeSidebar = () => {
                    shell.classList.add('hidden');
                    document.body.classList.remove('overflow-hidden');
                };

                window.adminSidebar = {
                    open: openSidebar,
                    close: closeSidebar,
                };

                backdrop.addEventListener('click', closeSidebar);

                document.addEventListener('keydown', function(event) {
                    if (event.key === 'Escape') {
                        closeSidebar();
                    }
                });

                const syncSidebar = () => {
                    if (desktopQuery.matches) {
                        shell.classList.add('hidden');
                        document.body.classList.remove('overflow-hidden');
                    } else {
                        shell.classList.add('hidden');
                        document.body.classList.remove('overflow-hidden');
                    }
                };

                syncSidebar();

                if (typeof desktopQuery.addEventListener === 'function') {
                    desktopQuery.addEventListener('change', syncSidebar);
                } else if (typeof desktopQuery.addListener === 'function') {
                    desktopQuery.addListener(syncSidebar);
                }
            });
        </script>
</body>

</html>