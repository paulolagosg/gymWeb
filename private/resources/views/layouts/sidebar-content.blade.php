@php
$panelTone = !empty($mobile) ? 'bg-stone-900 border-stone-700/80' : 'bg-white/5 border-white/10';
$sectionTone = !empty($mobile) ? 'text-stone-400' : 'text-stone-500';
$linkTone = !empty($mobile) ? 'text-stone-200 hover:bg-stone-800 hover:text-white' : 'text-stone-300 hover:bg-black hover:text-white';
$iconTone = !empty($mobile) ? 'bg-stone-800 text-stone-300 group-hover:bg-stone-700 group-hover:text-white' : 'bg-white/5 text-stone-400 group-hover:bg-white/10 group-hover:text-white';
$profileTone = !empty($mobile) ? 'text-stone-200 hover:bg-stone-800 hover:text-white' : 'text-stone-300 hover:bg-white/8 hover:text-white';
$contentWrapperTone = !empty($mobile) ? 'flex-1 px-4 py-5' : 'min-h-0 flex-1 overflow-y-auto px-4 py-5';
@endphp

<div class="border-b border-white/10 px-5 py-5">
    <div class="flex items-center justify-between gap-3">
        <a href="{{ route('portada') }}" class="flex items-center gap-3">
            <span class="flex h-12 w-12 items-center justify-center overflow-hidden rounded-2xl bg-white/10 ring-1 ring-white/10">
                <x-application-logo class="block h-10 w-10 rounded-xl object-cover" />
            </span>
        </a>

        @if (!empty($mobile))
        <button type="button" onclick="window.adminSidebar && window.adminSidebar.close()" class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-white/10 text-stone-300 transition hover:bg-white/10 hover:text-white md:hidden">
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

            <a href="{{ route($item['route']) }}"
                class="group flex items-center gap-3 rounded-2xl px-3 py-3 text-sm font-medium transition {{ $isActive ? 'bg-amber-300 text-stone-950 shadow-lg shadow-amber-500/20' : $linkTone }}"
                @if (!empty($mobile)) onclick="window.adminSidebar && window.adminSidebar.close()" @endif>
                <span class="inline-flex h-10 w-10 items-center justify-center rounded-xl {{ $isActive ? 'bg-stone-950/10 text-stone-950' : $iconTone }}">
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

<div class="border-t border-white/10 p-4">
    <a href="{{ route('profile.edit') }}" class="mb-2 flex items-center gap-3 rounded-2xl px-3 py-3 text-sm font-medium transition {{ $profileTone }}">
        <span class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-white/5 text-stone-400">
            <i class="fa-solid fa-user"></i>
        </span>
        <span>Mi perfil</span>
    </a>

    <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button type="submit" class="flex w-full items-center gap-3 rounded-2xl px-3 py-3 text-left text-sm font-medium text-rose-200 transition hover:bg-rose-500/10 hover:text-rose-100">
            <span class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-rose-500/10 text-rose-300">
                <i class="fa-solid fa-right-from-bracket"></i>
            </span>
            <span>Cerrar sesion</span>
        </button>
    </form>
</div>