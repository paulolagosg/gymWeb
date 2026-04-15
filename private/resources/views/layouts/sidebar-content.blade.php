@php
$panelTone = !empty($mobile) ? 'bg-stone-50 border-stone-200' : 'bg-stone-50 border-stone-200';
$sectionTone = 'text-stone-500';
$linkTone = 'text-stone-700 hover:!bg-black hover:!text-white';
$iconTone = 'bg-stone-100 text-stone-500';
$profileTone = 'text-stone-700 hover:!bg-black hover:!text-white';
$isProfileActive = request()->routeIs('profile.*');
$contentWrapperTone = !empty($mobile) ? 'px-4 py-5 pb-24' : 'min-h-0 flex-1 overflow-y-auto px-4 py-5';
@endphp

<style>
    .sidebar-nav-link:hover,
    .sidebar-nav-link.is-active,
    .sidebar-profile-link:hover,
    .sidebar-profile-link.is-active {
        background: #000 !important;
        color: #fff !important;
    }

    .sidebar-nav-link:hover .sidebar-nav-icon,
    .sidebar-nav-link.is-active .sidebar-nav-icon,
    .sidebar-profile-link:hover .sidebar-nav-icon,
    .sidebar-profile-link.is-active .sidebar-nav-icon {
        background: rgba(255, 255, 255, 0.15) !important;
        color: #fff !important;
    }
</style>

<div class="shrink-0 border-b border-white/10 px-5 py-5">
    <div class="flex items-center justify-between gap-3">
        <a href="{{ $homeRoute ?? route('portada') }}" class="flex items-center gap-3">
            <span class="flex h-12 w-12 items-center justify-center overflow-hidden rounded-2xl bg-white/10 ring-white/10">
                <x-application-logo class="block h-10 w-10 rounded-xl object-cover" />
            </span>
        </a>

        @if (!empty($mobile))
        <button type="button" onclick="window.adminSidebar && window.adminSidebar.close()" class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-stone-200 text-stone-600 transition hover:bg-stone-100 hover:text-stone-900 md:hidden">
            <i class="fa-solid fa-xmark text-lg"></i>
        </button>
        @endif
    </div>

    <!-- <div class="mt-5 rounded-2xl border p-4 {{ $panelTone }}">
        <p class="text-sm font-semibold text-white">{{ $currentUser->name }}</p>
        <p class="mt-1 text-sm text-stone-300">{{ $currentUser->email }}</p>
        <div class="mt-3 inline-flex rounded-full border border-amber-400/30 bg-amber-300/10 px-3 py-1 text-xs font-medium uppercase tracking-[0.24em] text-amber-200">
            {{ $roleLabel }}
        </div>
    </div> -->
</div>

<div class="{{ $contentWrapperTone }}">
    @foreach ($menuSections as $section)
    @php
    $visibleItems = collect($section['items'])->filter(fn ($item) => $item['can'] ?? true);
    @endphp

    @if ($visibleItems->isNotEmpty())
    <section class="mb-7">
        <p class="mb-3 px-3 text-xs font-semibold uppercase tracking-[0.28em] {{ $sectionTone }}">{{ $section['title'] }}</p>

        <div class="space-y-1.5">
            @foreach ($visibleItems as $item)
            @php
            $isActive = request()->routeIs(...$item['patterns']);
            @endphp

            <a href="{{ route($item['route'], $item['params'] ?? []) }}"
                class="sidebar-nav-link group flex items-center gap-3 rounded-2xl px-3 py-3 text-sm font-medium transition {{ $isActive ? 'is-active bg-black text-white shadow-lg shadow-stone-950/20' : $linkTone }}"
                @if (!empty($mobile)) onclick="window.adminSidebar && window.adminSidebar.close()" @endif>
                <span class="sidebar-nav-icon inline-flex h-10 w-10 items-center justify-center rounded-xl {{ $isActive ? 'bg-white/15 text-white' : $iconTone }}">
                    <i class="fa-solid {{ $item['icon'] }}"></i>
                </span>
                <span class="truncate">{{ $item['label'] }}</span>
            </a>
            @endforeach
        </div>
    </section>
    @endif
    @endforeach
</div>

<div class="shrink-0 border-t border-white/10 p-4">
    <a href="{{ route('profile.edit') }}" class="sidebar-profile-link group mb-2 flex items-center gap-3 rounded-2xl px-3 py-3 text-sm font-medium transition {{ $isProfileActive ? 'is-active bg-black text-white shadow-lg shadow-stone-950/20' : $profileTone }}">
        <span class="sidebar-nav-icon inline-flex h-10 w-10 items-center justify-center rounded-xl {{ $isProfileActive ? 'bg-white/15 text-white' : 'bg-stone-100 text-stone-500' }}">
            <i class="fa-solid fa-user"></i>
        </span>
        <span>Mi perfil</span>
    </a>

    <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button type="submit" class="flex w-full items-center gap-3 rounded-2xl px-3 py-3 text-left text-sm font-medium text-rose-600 transition hover:bg-rose-50 hover:text-rose-700">
            <span class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-rose-100 text-rose-500">
                <i class="fa-solid fa-right-from-bracket"></i>
            </span>
            <span>Cerrar sesion</span>
        </button>
    </form>
</div>