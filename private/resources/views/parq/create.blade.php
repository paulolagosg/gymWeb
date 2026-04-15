<x-admin-layout>
    <div class="py-4">
        <div class="">
            <div class="flex items-center justify-between mb-4 bg-white p-6 rounded-lg">
                <div class="text-gray-700">
                    <i class="fas fa-user fa-2x">&nbsp;{{ $cliente->nombres }} {{ $cliente->paterno }} {{ $cliente->materno }}</i>
                    <br><small>{{ $cliente->plan->nombre ?? 'Sin plan' }}</small>
                </div>
                @if(in_array((int) Auth::user()->id_tipo_usuario, [1, 2, 10], true))
                <a href="{{ route('clientes.opciones.portada', $cliente->slug) }}" class="inline-flex items-center gap-2 rounded-lg bg-gray-800 px-3 py-2 text-sm font-semibold text-white hover:bg-gray-600">
                    <i class="fas fa-arrow-left"></i>
                    Volver
                </a>
                @endif
            </div>
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 overflow-x-auto w-full">
                    <h2 class="font-semibold text-xl text-gray-800 leading-tight mb-4">
                        Cuestionario PAR-Q
                    </h2>
                    @if(session('success'))
                    <div class="mx-4 my-2 p-3 bg-green-100 border border-green-400 text-green-700 rounded">
                        {{ session('success') }}
                    </div>
                    @endif

                    @if($errors->any())
                    <div class="mx-4 my-2 p-3 bg-red-100 border border-red-400 text-red-700 rounded">
                        <ul class="mb-0">
                            @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                    @endif

                    <form method="POST" action="{{ route('parq.store', $cliente->slug) }}">
                        @csrf
                        @foreach($preguntas as $pregunta)
                        <div class="mb-4">
                            <label class="block font-semibold mb-1">{{ $pregunta->pregunta }}</label>
                            <label><input type="radio" name="pregunta_{{ $pregunta->id }}" value="1" required> Sí</label>
                            <label class="ml-4"><input type="radio" name="pregunta_{{ $pregunta->id }}" value="0" required> No</label>
                            <textarea name="observaciones_{{ $pregunta->id }}" class="w-full border rounded mt-2" placeholder="Observaciones (opcional)"></textarea>
                        </div>
                        @endforeach
                        <button type="submit" class="bg-green-600 hover:bg-green-800 text-white font-bold py-2 px-4 rounded">Guardar Cambios</button>

                    </form>
                </div>
            </div>
        </div>
    </div>
</x-admin-layout>