<x-admin-layout>
    <div class="py-4">
        <div class="">
            <div class="flex items-center justify-between mb-4 p-4 rounded-lg">
                <a href="{{ route('portada') }}" class="hover:text-gray-500">
                    <i class="fas fa-circle-left fa-2x">&nbsp;</i>
                </a>
            </div>
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="container py-5">
                    <div class="card text-center">
                        <div class="card-header bg-success text-white">
                            <h2>¡Gracias por tu feedback!</h2>
                        </div>
                        <div class="card-body">
                            <p class="lead">Tu opinión es muy valiosa para nosotros y nos ayuda a mejorar continuamente.</p>
                            <p>Hemos registrado tus respuestas y las tendremos en cuenta para ofrecerte un mejor servicio.</p>
                            <a href="{{ url('/') }}" class="btn btn-primary">Volver al inicio</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-admin-layout>