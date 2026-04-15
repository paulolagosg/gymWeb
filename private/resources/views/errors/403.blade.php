<x-admin-layout>
    <div class="py-4">
        <div class="">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">

                <div class="p-6 text-gray-900">
                    <div class="container py-5 text-center">
                        <h1 class="text-4xl">403 - Acceso denegado</h1>
                        <p class="lead">No tiene acceso a esta funcionalidad.</p>
                    </div>
                    <div class="items-center justify-between mb-4 bg-white p-6 rounded-lg text-center">
                        <a href="{{ route('portada') }}" class="text-gray-700 hover:text-gray-500">
                            <i class="fas fa-circle-left fa-2x">&nbsp;Volver</i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-admin-layout>