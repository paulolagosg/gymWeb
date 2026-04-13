@props(['href', 'active' => false])

<a href="{{ $href }}" {{ $attributes->class([
    'flex items-center px-4 py-3 text-sm transition-colors',
    'bg-gray-900 text-white' => $active,
    'text-gray-300 hover:bg-gray-700 hover:text-white' => !$active
]) }}>
    {{ $slot }}
</a>