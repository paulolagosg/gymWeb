<a {{$attributes}} class="flex items-center px-3 py-2 text-sm font-medium text-white rounded-lg hover:bg-gray-100 hover:text-gray-700 dark:text-white dark:hover:bg-gray-700 group transition-colors duration-200 ease-in-out {{ $active ? 'bg-gray-700 text-white' : 'text-gray-400' }}">
    {{$slot}}
</a>