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
    $roleNameFromRelation = trim((string) optional($currentUser->tipoUsuario)->nombre);
    $trainerClassification = trim((string) $currentUser->nombre_clasificacion);
    $roleLabel = match ((int) $currentUser->id_tipo_usuario) {
    1 => $roleNameFromRelation !== '' ? $roleNameFromRelation : 'Administrador',
    2 => $trainerClassification !== '' ? $trainerClassification : 'Entrenador',
    3 => $roleNameFromRelation !== '' ? $roleNameFromRelation : 'Cliente presencial',
    4 => $roleNameFromRelation !== '' ? $roleNameFromRelation : 'Cliente online',
    5 => $roleNameFromRelation !== '' ? $roleNameFromRelation : 'Open Gym',
    10 => $roleNameFromRelation !== '' ? $roleNameFromRelation : 'Super administrador',
    default => $roleNameFromRelation !== '' ? $roleNameFromRelation : 'Usuario',
    };
    $gimnasioLabel = optional($currentUser->gimnasio)->nombre ?? optional(\App\Models\Gimnasios::gimnasioActual())->nombre ?? 'Sin gimnasio';
    $isAdmin = (int) $currentUser->id_tipo_usuario === 1;
    $isSuperAdmin = (int) $currentUser->id_tipo_usuario === 10;
    $canManageAdminModules = $isAdmin || $isSuperAdmin;
    $isTrainer = (int) $currentUser->id_tipo_usuario === 2;
    $isClient = in_array((int) $currentUser->id_tipo_usuario, [3, 4, 5], true);
    $canSeeOperationalModules = $canManageAdminModules || $isTrainer;
    $clientProfile = $isClient ? ($currentUser->cliente ?? \App\Models\Clientes::with('entrenador')->find($currentUser->id_cliente)) : null;
    $clientSlug = optional($clientProfile)->slug;
    $trainerSidebarSlug = optional(optional($clientProfile)->entrenador)->slug;
    $hasParq = $clientProfile ? \App\Models\ParqRespuestas::where('id_cliente', $clientProfile->id)->exists() : false;
    $hasFitPlan = $clientProfile ? \App\Models\Cuestionarios::where('id_cliente', $clientProfile->id)->exists() : false;
    $homeRoute = ((int) $currentUser->id_tipo_usuario === 5)
    ? route('open-gym.index')
    : (($isClient && $clientSlug)
    ? route('clientes.agenda', $clientSlug)
    : (($canManageAdminModules || $isTrainer) ? route('dashboard') : route('portada')));

    if ($isClient && $clientSlug) {
    if ((int) $currentUser->id_tipo_usuario === 5) {
    $menuSections = [
    [
    'title' => 'Open Gym',
    'items' => [
    ['label' => 'Rutinas', 'route' => 'open-gym.index', 'icon' => 'fa-dumbbell', 'patterns' => ['open-gym.index', 'open-gym.create', 'open-gym.edit']],
    ['label' => 'Progreso', 'route' => 'open-gym.progress', 'icon' => 'fa-chart-line', 'patterns' => ['open-gym.progress']],
    ['label' => 'Historial', 'route' => 'open-gym.history', 'icon' => 'fa-clock-rotate-left', 'patterns' => ['open-gym.history', 'open-gym.workouts.*']],
    ['label' => 'Mi perfil', 'route' => 'profile.edit', 'icon' => 'fa-user-pen', 'patterns' => ['profile.*']],
    ],
    ],
    ];
    } else {
    $menuSections = [
    [
    'title' => 'Mi espacio',
    'items' => [
    ['label' => 'Mi ficha', 'route' => 'clientes.edit', 'params' => [$clientSlug], 'icon' => 'fa-user-pen', 'patterns' => ['clientes.edit']],
    ['label' => 'Mi agenda', 'route' => 'clientes.agenda', 'params' => [$clientSlug], 'icon' => 'fa-calendar-days', 'patterns' => ['clientes.agenda']],
    ['label' => 'Cuenta corriente', 'route' => 'clientes.cuenta_corriente', 'params' => [$clientSlug], 'icon' => 'fa-wallet', 'patterns' => ['clientes.cuenta_corriente*']],
    ['label' => 'Mensajeria', 'route' => 'mensajes.index', 'icon' => 'fa-envelope', 'patterns' => ['mensajes.*']],
    ],
    ],
    [
    'title' => 'Seguimiento',
    'items' => [
    ['label' => 'Evolucion entrenamiento', 'route' => 'clientes.evolucion_ejercicios', 'params' => [$clientSlug], 'icon' => 'fa-chart-line', 'patterns' => ['clientes.evolucion_ejercicios*']],
    ['label' => 'Entrenamientos por estado', 'route' => 'agendas.cliente_por_mes', 'params' => [$clientSlug], 'icon' => 'fa-square-poll-vertical', 'patterns' => ['agendas.cliente_por_mes']],
    ['label' => 'Peso', 'route' => 'clientes.pesos', 'params' => [$clientSlug], 'icon' => 'fa-weight-scale', 'patterns' => ['clientes.pesos*']],
    ['label' => 'IMC', 'route' => 'clientes.imcs', 'params' => [$clientSlug], 'icon' => 'fa-gauge-high', 'patterns' => ['clientes.imcs*']],
    ['label' => 'Perimetros', 'route' => 'clientes.perimetros', 'params' => [$clientSlug], 'icon' => 'fa-ruler-combined', 'patterns' => ['clientes.perimetros*']],
    ['label' => 'Grasa corporal', 'route' => 'clientes.grasas', 'params' => [$clientSlug], 'icon' => 'fa-percent', 'patterns' => ['clientes.grasas*']],
    ['label' => 'Masa osea', 'route' => 'clientes.poseas', 'params' => [$clientSlug], 'icon' => 'fa-bone', 'patterns' => ['clientes.poseas*']],
    ['label' => 'Masa muscular', 'route' => 'clientes.pmusculares', 'params' => [$clientSlug], 'icon' => 'fa-dumbbell', 'patterns' => ['clientes.pmusculares*']],
    ],
    ],
    [
    'title' => 'Cuestionarios',
    'items' => [
    ['label' => 'Par-Q', 'route' => $hasParq ? 'parq.show' : 'parq.create', 'params' => [$clientSlug], 'icon' => 'fa-clipboard-question', 'patterns' => ['parq.*']],
    ['label' => 'Fit Plan Evolution', 'route' => $hasFitPlan ? 'fitplan.edit' : 'fitplan.create', 'params' => [$clientSlug], 'icon' => 'fa-heart-pulse', 'patterns' => ['fitplan.*']],
    ['label' => 'Evalua a tu entrenador', 'route' => 'encuestas.create', 'params' => [$trainerSidebarSlug], 'icon' => 'fa-star', 'patterns' => ['encuestas.*'], 'can' => !empty($trainerSidebarSlug)],
    ['label' => 'Evalua al gimnasio', 'route' => 'survey.show', 'params' => [$clientSlug], 'icon' => 'fa-building-circle-check', 'patterns' => ['survey.*']],
    ],
    ],
    ];
    }
    } else {
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
    ['label' => 'Caja', 'route' => 'caja.index', 'icon' => 'fa-wallet', 'patterns' => ['caja.*'], 'can' => $isAdmin],
    ['label' => 'Mensajeria', 'route' => 'mensajes.index', 'icon' => 'fa-envelope', 'patterns' => ['mensajes.*'], 'can' => $canSeeOperationalModules],
    ['label' => 'Tareas', 'route' => 'tareas.index', 'icon' => 'fa-list-check', 'patterns' => ['tareas.*'], 'can' => $canSeeOperationalModules],
    ['label' => 'Cursos', 'route' => 'cursos.index', 'icon' => 'fa-graduation-cap', 'patterns' => ['cursos.*'], 'can' => $canSeeOperationalModules],
    ['label' => 'Entrenadores', 'route' => 'entrenadores.index', 'icon' => 'fa-user-group', 'patterns' => ['entrenadores.*'], 'can' => $canManageAdminModules],
    ],
    ],
    [
    'title' => 'Administracion',
    'items' => [
    ['label' => 'Planes', 'route' => 'planes.index', 'icon' => 'fa-layer-group', 'patterns' => ['planes.*'], 'can' => $canManageAdminModules],
    ['label' => 'Gimnasios', 'route' => 'gimnasios.index', 'icon' => 'fa-building', 'patterns' => ['gimnasios.*'], 'can' => $isSuperAdmin],
    ['label' => 'Términos', 'route' => 'terminos.index', 'icon' => 'fa-file-contract', 'patterns' => ['terminos.*'], 'can' => $isSuperAdmin],
    ['label' => 'Usuarios', 'route' => 'usuarios.index', 'icon' => 'fa-user-gear', 'patterns' => ['usuarios.*'], 'can' => $canManageAdminModules],
    ['label' => 'Pagos entrenadores', 'route' => 'pagos_entrenadores.index', 'icon' => 'fa-money-check-dollar', 'patterns' => ['pagos_entrenadores.*'], 'can' => $isAdmin],
    ['label' => 'Evaluacion inicial', 'route' => 'evaluacion-inicial.catalogo', 'icon' => 'fa-clipboard-list', 'patterns' => ['evaluacion-inicial.*'], 'can' => $isAdmin],
    ],
    ],
    ];
    }

    $currentMenuItem = collect($menuSections)
    ->flatMap(fn ($section) => $section['items'])
    ->first(function ($item) {
    return request()->routeIs(...$item['patterns']);
    });

    $currentSectionLabel = $currentMenuItem['label'] ?? 'Panel';
    @endphp

    <div class="min-h-screen bg-[radial-gradient(circle_at_top,_rgba(245,158,11,0.14),_transparent_28%),linear-gradient(180deg,_#f5f5f4_0%,_#fafaf9_24%,_#f5f5f4_100%)]">
        <div id="mobile-sidebar-shell" class="fixed inset-0 z-50 hidden overflow-y-auto overscroll-y-contain md:hidden" style="-webkit-overflow-scrolling: touch;">
            <div id="sidebar-backdrop" class="absolute inset-0 bg-stone-950/70"></div>
            <div class="relative min-h-full">
                <aside id="mobile-sidebar" class="relative z-10 flex min-h-[100dvh] w-72 max-w-[88vw] flex-col border-r border-stone-200 bg-white text-stone-900 shadow-[0_24px_60px_rgba(0,0,0,0.25)]" style="touch-action: pan-y;">
                    @include('layouts.sidebar-content', ['mobile' => true, 'currentUser' => $currentUser, 'roleLabel' => $roleLabel, 'menuSections' => $menuSections, 'homeRoute' => $homeRoute])
                </aside>
            </div>
        </div>

        <div class="flex min-h-screen">
            <aside class="hidden h-screen w-72 shrink-0 flex-col overflow-hidden border-r border-stone-200 bg-white text-stone-100 shadow-2xl md:fixed md:inset-y-0 md:left-0 md:flex">
                @include('layouts.sidebar-content', ['mobile' => false, 'currentUser' => $currentUser, 'menuSections' => $menuSections, 'homeRoute' => $homeRoute])
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
                                Rol: {{ $roleLabel }}
                            </div>
                            <div class="rounded-full border border-amber-200 bg-amber-50 px-3 py-2 text-sm font-medium text-amber-700">
                                Gimnasio: {{ $gimnasioLabel }}
                            </div>

                            <div id="notifications-shell" class="relative">
                                <button type="button" id="notifications-trigger" class="inline-flex h-11 w-11 items-center justify-center rounded-2xl border border-stone-200 bg-white text-stone-700 shadow-sm transition hover:border-stone-300 hover:text-stone-950 focus:outline-none">
                                    <i class="fa-regular fa-bell text-base"></i>
                                    <span id="notifications-badge" class="absolute -right-1 -top-1 hidden min-w-[1.25rem] rounded-full bg-red-500 px-1.5 py-0.5 text-center text-[11px] font-semibold leading-none text-white"></span>
                                </button>

                                <div id="notifications-panel" class="absolute right-0 top-14 hidden w-96 overflow-hidden rounded-3xl border border-stone-200 bg-white shadow-[0_30px_80px_rgba(0,0,0,0.18)]">
                                    <div class="flex items-center justify-between border-b border-stone-100 px-4 py-3">
                                        <div>
                                            <p class="text-sm font-semibold text-stone-900">Notificaciones</p>
                                            <p class="text-xs text-stone-500">Avisos recientes de tu cuenta</p>
                                        </div>
                                        <button type="button" id="notifications-read-all" class="text-xs font-semibold text-amber-700 hover:text-amber-900">
                                            Marcar todas como leidas
                                        </button>
                                    </div>
                                    <div id="notifications-list" class="max-h-[26rem] overflow-y-auto bg-stone-50"></div>
                                </div>
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
            };

            const closeSidebar = () => {
                shell.classList.add('hidden');
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
                shell.classList.add('hidden');
            };

            syncSidebar();

            if (typeof desktopQuery.addEventListener === 'function') {
                desktopQuery.addEventListener('change', syncSidebar);
            } else if (typeof desktopQuery.addListener === 'function') {
                desktopQuery.addListener(syncSidebar);
            }

            const notificationsShell = document.getElementById('notifications-shell');
            const notificationsTrigger = document.getElementById('notifications-trigger');
            const notificationsPanel = document.getElementById('notifications-panel');
            const notificationsBadge = document.getElementById('notifications-badge');
            const notificationsList = document.getElementById('notifications-list');
            const notificationsReadAll = document.getElementById('notifications-read-all');
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';

            if (!notificationsShell || !notificationsTrigger || !notificationsPanel || !notificationsBadge || !notificationsList || !notificationsReadAll) {
                return;
            }

            const formatDate = (value) => {
                if (!value) return '';

                const date = new Date(value);
                if (Number.isNaN(date.getTime())) return '';

                return new Intl.DateTimeFormat('es-CL', {
                    day: '2-digit',
                    month: '2-digit',
                    hour: '2-digit',
                    minute: '2-digit',
                }).format(date);
            };

            const setBadge = (count) => {
                if (!count) {
                    notificationsBadge.classList.add('hidden');
                    notificationsBadge.textContent = '';
                    return;
                }

                notificationsBadge.classList.remove('hidden');
                notificationsBadge.textContent = count > 99 ? '99+' : String(count);
            };

            const postNotificationAction = async (url) => {
                await fetch(url, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                    },
                });
            };

            const renderNotifications = (payload) => {
                const items = payload?.notifications ?? [];
                setBadge(payload?.unread_count ?? 0);

                if (!items.length) {
                    notificationsList.innerHTML = '<div class="px-4 py-8 text-center text-sm text-stone-500">No tienes notificaciones por ahora.</div>';
                    return;
                }

                notificationsList.innerHTML = items.map((item) => {
                    const isUnread = !item.read_at;
                    const title = item.title ?? 'Notificacion';
                    const message = item.message ?? '';
                    const createdAt = formatDate(item.created_at);
                    const unreadMarker = isUnread ? '<span class="inline-flex h-2.5 w-2.5 rounded-full bg-amber-500"></span>' : '<span class="inline-flex h-2.5 w-2.5 rounded-full bg-stone-300"></span>';

                    return `
                        <button type="button"
                            class="notification-item flex w-full items-start gap-3 border-b border-stone-100 px-4 py-3 text-left transition hover:bg-white ${isUnread ? 'bg-white' : 'bg-stone-50'}"
                            data-id="${item.id}"
                            data-read-url="/notificaciones/${item.id}/leer"
                            data-action-url="${item.action_url_web ?? ''}">
                            <div class="mt-1">${unreadMarker}</div>
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center justify-between gap-3">
                                    <p class="truncate text-sm font-semibold text-stone-900">${title}</p>
                                    <span class="shrink-0 text-[11px] text-stone-400">${createdAt}</span>
                                </div>
                                <p class="mt-1 text-xs leading-5 text-stone-600">${message}</p>
                            </div>
                        </button>
                    `;
                }).join('');

                notificationsList.querySelectorAll('.notification-item').forEach((element) => {
                    element.addEventListener('click', async () => {
                        const readUrl = element.getAttribute('data-read-url');
                        const actionUrl = element.getAttribute('data-action-url');

                        if (readUrl) {
                            await postNotificationAction(readUrl);
                        }

                        if (actionUrl) {
                            window.location.href = actionUrl;
                            return;
                        }

                        await loadNotifications();
                    });
                });
            };

            const loadNotifications = async () => {
                try {
                    const response = await fetch('/notificaciones', {
                        headers: {
                            'Accept': 'application/json',
                        },
                    });

                    if (!response.ok) {
                        throw new Error('No se pudieron cargar las notificaciones.');
                    }

                    renderNotifications(await response.json());
                } catch (error) {
                    notificationsList.innerHTML = '<div class="px-4 py-8 text-center text-sm text-red-500">No se pudieron cargar las notificaciones.</div>';
                }
            };

            notificationsTrigger.addEventListener('click', async () => {
                const isHidden = notificationsPanel.classList.contains('hidden');
                notificationsPanel.classList.toggle('hidden');

                if (isHidden) {
                    await loadNotifications();
                }
            });

            notificationsReadAll.addEventListener('click', async () => {
                await postNotificationAction('/notificaciones/leer-todas');
                await loadNotifications();
            });

            document.addEventListener('click', function(event) {
                if (!notificationsShell.contains(event.target)) {
                    notificationsPanel.classList.add('hidden');
                }
            });

            loadNotifications();
        });
    </script>
</body>

</html>