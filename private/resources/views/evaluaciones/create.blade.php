<x-admin-layout>
    <div class="py-4">
        <div class="">
            <div class="flex items-center justify-between mb-4 bg-white p-4 rounded-lg shadow">
                <a href="{{ route('evaluaciones.index',$entrenador->slug) }}" class="hover:text-gray-500">
                    <i class="fas fa-circle-left fa-2x">&nbsp;{{ $entrenador->name }}</i>
                </a>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 overflow-x-auto w-full">
                    <h2 class="text-xl font-bold mb-4">Categorización</h2>
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
                    <form method="POST" action="{{ route('evaluaciones.store', $entrenador->slug) }}">
                        @csrf
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 md:gap-6">
                            <div class="mb-4 sm:mb-0">
                                <label class="font-bold">Empatía</label><br>
                                @for($i=1;$i<=7;$i++)
                                    <input type="radio" name="empatia" value="{{ $i }}" required> {{ $i }}
                                    @endfor
                            </div>
                            <div class="mb-4">
                                <label class="font-bold">Escucha activa</label><br>
                                @for($i=1;$i<=7;$i++)
                                    <input type="radio" name="escucha_activa" value="{{ $i }}" required> {{ $i }}
                                    @endfor
                            </div>
                            <div class="mb-4">
                                <label class="font-bold">Comunicación</label><br>
                                @for($i=1;$i<=7;$i++)
                                    <input type="radio" name="comunicacion" value="{{ $i }}" required> {{ $i }}
                                    @endfor
                            </div>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 md:gap-6">
                            <div class="mb-4">
                                <label class="font-bold">Anatomía Funcional y Biomecánica</label><br>
                                @for($i=1;$i<=7;$i++)
                                    <input type="radio" name="anatomia" value="{{ $i }}" required> {{ $i }}
                                    @endfor
                            </div>
                            <div class="mb-4">
                                <label class="font-bold">Fisiologia del Ejercicio</label><br>
                                @for($i=1;$i<=7;$i++)
                                    <input type="radio" name="fisiologia" value="{{ $i }}" required> {{ $i }}
                                    @endfor
                            </div>
                            <div class="mb-4">
                                <label class="font-bold">Programación del entrenamiento</label><br>
                                @for($i=1;$i<=7;$i++)
                                    <input type="radio" name="programacion" value="{{ $i }}" required> {{ $i }}
                                    @endfor
                            </div>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 md:gap-6">
                            <div class="mb-4">
                                <label class="font-bold">Poblaciones especiales y Fisiología clínica</label><br>
                                @for($i=1;$i<=7;$i++)
                                    <input type="radio" name="poblacion" value="{{ $i }}" required> {{ $i }}
                                    @endfor
                            </div>
                            <div class="mb-4">
                                <label class="font-bold">Psicología del deporte</label><br>
                                @for($i=1;$i<=7;$i++)
                                    <input type="radio" name="psicologia" value="{{ $i }}" required> {{ $i }}
                                    @endfor
                            </div>
                        </div>
                        <div class="mb-4">
                            <label class="font-bold">Categoría</label>
                            <select id="id_clasificacion" name="id_clasificacion" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                <option value="">Seleccione</option>
                                @foreach($categorias as $c)
                                <option value="{{$c->id}}">{{$c->nombre}}</option>
                                @endforeach
                            </select>
                        </div>

                        <button type="submit" class="bg-green-600 hover:bg-green-800 text-white font-bold py-2 px-4 rounded">Guardar Cambios</button>
                        <button type="button" onclick="window.location.href='{{route('evaluaciones.index',$entrenador->slug)}}'" class="bg-red-500 hover:bg-red-800 text-white font-bold py-2 px-4 rounded ml-2">
                            Cancelar
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-admin-layout>