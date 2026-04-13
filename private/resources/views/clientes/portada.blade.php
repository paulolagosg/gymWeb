<x-admin-layout>
    <div class="py-4">
        <div class="max-w-7xl mx-auto">
            <div class="flex items-center justify-between mb-4">
                <a href="{{ route('portada') }}" class="text-gray-700 hover:text-gray-500">
                    <i class="fas fa-circle-left fa-2x">&nbsp;</i>
                </a>
            </div>
            <!-- div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8" -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 md:gap-6 mb-8">
                <div class="bg-white p-6 rounded-lg shadow text-center border-t-4 border-black" data-tippy-content="Ver Clientes">
                    <a href="{{ route('clientes.index') }}" class="hover:text-gray-500">
                        <i class="fa-solid fa-users fa-10x"></i>
                        <div class="text-gray-700 font-semibold block md:hidden">Ver Clientes</div>
                    </a>
                </div>
                <div class="bg-white p-6 rounded-lg shadow text-center border-t-4 border-black" data-tippy-content="Agregar Cliente">
                    <a href="{{ route('clientes.create') }}" class="hover:text-gray-500">
                        <i class="fa-solid fa-user-plus fa-10x"></i>
                        <div class="text-gray-700 font-semibold block md:hidden">Agregar Cliente</div>
                    </a>
                </div>
            </div>
        </div>
    </div>
</x-admin-layout>