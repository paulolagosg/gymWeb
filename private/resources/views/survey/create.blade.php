<x-admin-layout>
    <div class="py-4">
        <div class="">
            <div class="flex items-center justify-between mb-4 p-4 rounded-lg">
                <a href="{{ route('portada') }}" class="hover:text-gray-500">
                    <i class="fas fa-circle-left fa-2x">&nbsp;</i>
                </a>
            </div>
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 overflow-x-auto w-full">

                    <h2 class="text-xl font-bold mb-4">Encuesta de Satisfacción</h2>
                    <form method="POST" action="{{ route('encuestas.store', $entrenador->slug) }}">
                        @csrf
                        <p class="mb-2">En <b><i>Ampaya Gym</i></b> siempre queremos mejorar tu experiencia.<br>
                            Es por esto que por favor te pedimos que evalúes a tu entrenador <b>{{ $entrenador->name }}</b> en una escala de 1 al 5, en donde 1 = Muy insatisfecho, 5 = Muy satisfecho.</p>
                        <div class="mb-4">
                            <label>1.1. Profesionalismo y conocimiento técnico</label><br>
                            @for($i=1;$i<=5;$i++)
                                <input type="radio" name="profesionalismo" value="{{ $i }}" required> {{ $i }}
                                @endfor
                        </div>
                        <div class="mb-4">
                            <label>1.2. Claridad en las instrucciones y explicaciones</label><br>
                            @for($i=1;$i<=5;$i++)
                                <input type="radio" name="claridad" value="{{ $i }}" required> {{ $i }}
                                @endfor
                        </div>
                        <div class="mb-4">
                            <label>1.3. Motivación y trato personalizado</label><br>
                            @for($i=1;$i<=5;$i++)
                                <input type="radio" name="motivacion" value="{{ $i }}" required> {{ $i }}
                                @endfor
                        </div>
                        <div class="mb-4">
                            <label>1.4. Disponibilidad para resolver dudas</label><br>
                            @for($i=1;$i<=5;$i++)
                                <input type="radio" name="disponibilidad" value="{{ $i }}" required> {{ $i }}
                                @endfor
                        </div>
                        <div class="mb-4">
                            <label>1.5. Puntualidad y organización de las sesiones</label><br>
                            @for($i=1;$i<=5;$i++)
                                <input type="radio" name="puntualidad" value="{{ $i }}" required> {{ $i }}
                                @endfor
                        </div>
                        <div class="mb-4">
                            <label>2. ¿Qué destacarías de tu entrenador/a?</label>
                            <textarea name="destacaria" class="mt-1 block text-black w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"></textarea>
                        </div>
                        <div class="mb-4">
                            <label>3. ¿Qué sugerencias de mejora le darías?</label>
                            <textarea name="sugerencias" class="mt-1 block text-black w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"></textarea>
                        </div>
                        <div class="mb-4">
                            <label>4. Valoración global: <br> Del 1 al 10, ¿cuánto recomendarías a tu entrenador/a?</label><br>
                            @for($i=1;$i<=10;$i++)
                                <input type="radio" name="valoracion_global" value="{{ $i }}" required> {{ $i }}
                                @endfor
                        </div>
                        <button type="submit" class="bg-green-600 hover:bg-green-800 text-white font-bold py-2 px-4 rounded">Enviar</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-admin-layout>