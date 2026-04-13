<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }}</title>
    <script src="//code.jquery.com/jquery-3.6.0.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <!--link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}"-->
    <link rel="icon" type="image/avif" href="{{ asset('logo.png') }}">
    <!-- Fonts -->
    <link rel="preconnect" href="//fonts.bunny.net">
    <link href="//fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="//cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="//cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <!-- tool tips -->
    <link rel="stylesheet" href="https://unpkg.com/tippy.js@6/dist/tippy.css" />
    <script src="https://unpkg.com/@popperjs/core@2"></script>
    <script src="https://unpkg.com/tippy.js@6"></script>
</head>

<body class="font-sans antialiased">
    <div class="relative min-h-screen md:flex" x-data="{ open: false }">
        <!-- Botón para abrir el sidebar (solo en móvil) -->
        <button
            type="button"
            @click="open = true"
            class="md:hidden fixed top-4 left-4 z-50 inline-flex p-2 items-center justify-center rounded-md bg-gray-700 text-white hover:bg-gray-900 focus:outline-none"
            x-show="!open">
            <!-- Icono hamburguesa -->
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="block h-6 w-6">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
            </svg>
        </button>
        <!-- sidebar -->
        <aside
            :class="{ '-translate-x-full': !open }"
            class="z-10 bg-gray-500 text-white w-48 px-2 py-4 absolute inset-0 left-0 transform -translate-x-full 
            md:relative md:translate-x-0 transition-transform duration-300 ease-in-out">

            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center space-x-1">
                    <a href="{{ url('/') }}">
                        <x-application-logo class="block h-9 w-auto fill-current text-white" />
                    </a>
                </div>

                <button
                    type="button"
                    @click="open = false"
                    class="md:hidden inline-flex p-2 items-center justify-center rounded-md text-white hover:bg-gray-700 focus:outline-none">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="block h-6 w-6">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <nav>
                <x-side-nav-link href="{{ route('dashboard') }}" :active="request()->routeIs('dashboard')">
                    Dashboard
                </x-side-nav-link>
                <x-side-nav-link href="{{ route('agendas.index') }}" :active="request()->routeIs('agendas.*')">
                    Agenda
                </x-side-nav-link>
                <x-side-nav-link href="{{ route('planes.index') }}" :active="request()->routeIs('planes.*')">
                    Planes
                </x-side-nav-link>
                <x-side-nav-link href="{{ route('clientes.index') }}" :active="request()->routeIs('clientes.*')">
                    Clientes
                </x-side-nav-link>
                <x-side-nav-link href="{{ route('ejercicios.index') }}" :active="request()->routeIs('ejercicios.*')">
                    Ejercicios
                </x-side-nav-link>
                <x-side-nav-link href="{{ route('usuarios.index') }}" :active="request()->routeIs('usuarios.*')">
                    Usuarios
                </x-side-nav-link>

            </nav>
        </aside>
        <!-- main content -->
        <main class="flex-1 bg-gray-100 h-screen">
            <nav class="bg-white shadow px-4 py-2 flex justify-between items-end">
                <!-- ... -->
                <div class="mx-auto px-2 sm:px-6 lg:px-8">
                    <div>
                        <x-dropdown align="right" width="48">
                            <x-slot name="trigger">
                                <button class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 bg-white hover:text-gray-700 focus:outline-none transition ease-in-out duration-150">
                                    <div>{{ Auth::user()->name }}</div>

                                    <div class="ms-1">
                                        <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                        </svg>
                                    </div>
                                </button>
                            </x-slot>

                            <x-slot name="content">
                                <x-dropdown-link :href="route('profile.edit')">
                                    {{ __('Profile') }}
                                </x-dropdown-link>

                                <!-- Authentication -->
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf

                                    <x-dropdown-link :href="route('logout')"
                                        onclick="event.preventDefault();
                                                this.closest('form').submit();">
                                        {{ __('Log Out') }}
                                    </x-dropdown-link>
                                </form>
                            </x-slot>
                        </x-dropdown>
                    </div>
                </div>
            </nav>
            <div>
                {{$slot}}
            </div>
        </main>
    </div>
</body>

</html>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        tippy('[data-tippy-content]');
    });
</script>