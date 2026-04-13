<x-admin-layout>
    <div class="py-4">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <h2 class="text-xl font-bold mb-4">Detalle de la Agenda</h2>
                <p><strong>Título:</strong> {{ $agenda->titulo }}</p>
                <p><strong>Cliente:</strong> {{ $agenda->cliente->nombres }} {{ $agenda->cliente->paterno }}</p>
                <p><strong>Entrenador:</strong> {{ $agenda->entrenador->name }}</p>
                <p><strong>Fecha inicio:</strong> {{ $agenda->fecha_inicio }}</p>
                <p><strong>Fecha fin:</strong> {{ $agenda->fecha_fin }}</p>
                <p><strong>Descripción:</strong> {{ $agenda->descripcion }}</p>
                <p><strong>Ejercicios:</strong></p>
                <ul>
                    @foreach($agenda->ejercicios as $ejercicio)
                    <li>
                        {{ $ejercicio->nombre }}: {{ $ejercicio->pivot->series }} serie(s), {{ $ejercicio->pivot->repeticiones }}
                    </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
</x-admin-layout>