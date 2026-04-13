<x-admin-layout>
    <div class="py-4">
        <div class="">
            <div class="flex items-center justify-between mb-4 p-4 rounded-lg">
                <a href="{{ route('encuestas.index',[$entrenador->slug,'p']) }}" class="hover:text-gray-500">
                    <i class="fas fa-circle-left fa-2x">&nbsp;</i>
                </a>
            </div>
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 overflow-x-auto w-full">

                    <h2 class="text-xl font-bold mb-4">Respuestas Encuesta de Satisfacción</h2>
                    <div class="mb-4">
                        <label>1.1. Profesionalismo y conocimiento técnico</label><br>
                        @for($i=1;$i<=5;$i++)
                            <input type="radio" name="profesionalismo" value="{{$i}}" @if(isset($encuesta) && $encuesta->profesionalismo == $i) checked @endif required> {{ $i }}
                            @endfor
                    </div>
                    <div class="mb-4">
                        <label>1.2. Claridad en las instrucciones y explicaciones</label><br>
                        @for($i=1;$i<=5;$i++)
                            <input type="radio" name="claridad" value="{{ $i }}" @if(isset($encuesta) && $encuesta->claridad == $i) checked @endif required> {{ $i }}
                            @endfor
                    </div>
                    <div class="mb-4">
                        <label>1.3. Motivación y trato personalizado</label><br>
                        @for($i=1;$i<=5;$i++)
                            <input type="radio" name="motivacion" value="{{ $i }}" @if(isset($encuesta) && $encuesta->motivacion == $i) checked @endif> {{ $i }}
                            @endfor
                    </div>
                    <div class="mb-4">
                        <label>1.4. Disponibilidad para resolver dudas</label><br>
                        @for($i=1;$i<=5;$i++)
                            <input type="radio" name="disponibilidad" value="{{ $i }}" @if(isset($encuesta) && $encuesta->disponibilidad == $i) checked @endif> {{ $i }}
                            @endfor
                    </div>
                    <div class="mb-4">
                        <label>1.5. Puntualidad y organización de las sesiones</label><br>
                        @for($i=1;$i<=5;$i++)
                            <input type="radio" name="puntualidad" value="{{ $i }}" @if(isset($encuesta) && $encuesta->puntualidad == $i) checked @endif> {{ $i }}
                            @endfor
                    </div>
                    <div class="mb-4">
                        <label>2. ¿Qué destacarías de tu entrenador/a?</label>
                        <textarea name="destacaria" class="mt-1 block text-black w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">{{$encuesta->destacaria}}</textarea>
                    </div>
                    <div class="mb-4">
                        <label>3. ¿Qué sugerencias de mejora le darías?</label>
                        <textarea name="sugerencias" class="mt-1 block text-black w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">{{$encuesta->sugerencias}}</textarea>
                    </div>
                    <div class="mb-4">
                        <label>4. Valoración global: <br> Del 1 al 10, ¿cuánto recomendarías a tu entrenador/a?</label><br>
                        @for($i=1;$i<=10;$i++)
                            <input type="radio" name="valoracion_global" value="{{ $i }}" @if(isset($encuesta) && $encuesta->valoracion_global == $i) checked @endif> {{ $i }}
                            @endfor
                    </div>
                    <button type="button" onclick="window.location.href='{{ route('encuestas.index',[$entrenador->slug,'p']) }}'" class="bg-gray-700 hover:bg-gray-500 text-white font-bold py-2 px-4 rounded">Volver</button>
                </div>
            </div>
        </div>
    </div>
</x-admin-layout>