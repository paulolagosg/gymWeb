<x-admin-layout>
    <div class="py-4">
        <div class="">
            <div class="flex items-center justify-between mb-4">
                <a href="{{ route('agendas.index') }}" class="text-gray-700 hover:text-gray-500">
                    <i class="fas fa-circle-left fa-2x">&nbsp;</i>
                </a>
            </div>
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <h2 class="font-semibold text-xl text-gray-800 leading-tight mb-4">
                        {{ __('Editar entrenamiento') }}
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
                    <form method="POST" action="{{ route('agendas.update', $agenda->slug) }}">
                        @csrf
                        @method('PUT')
                        <div class="mt-4">
                            <label for="id_cliente" class="block text-sm font-medium text-gray-700">Cliente</label>
                            <select id="id_cliente" name="id_cliente" required class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                <option value="">Seleccionar cliente</option>
                                @foreach ($clientes as $cliente)
                                <option value="{{ $cliente->id }}" {{ $agenda->id_cliente == $cliente->id ? 'selected' : '' }}>
                                    {{ $cliente->nombres }} {{ $cliente->paterno }} {{ $cliente->materno }}
                                </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mt-4">
                            <label for="id_usuario" class="block text-sm font-medium text-gray-700">Usuario</label>
                            <select id="id_usuario" name="id_usuario" required class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                <option value="">Seleccionar usuario</option>
                                @foreach ($usuarios as $usuario)
                                <option value="{{ $usuario->id }}" {{ $agenda->id_usuario == $usuario->id ? 'selected' : '' }}>
                                    {{ $usuario->name }}
                                </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mt-4">
                            <label for="fecha_inicio" class="block text-sm font-medium text-gray-700">Fecha de inicio</label>
                            <input id="fecha_inicio" type="datetime-local" name="fecha_inicio"
                                value="{{ $agenda->fecha_inicio->format('Y-m-d\TH:i') }}" required
                                class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" />
                        </div>
                        <div class="mt-4">
                            <label for="fecha_fin" class="block text-sm font-medium text-gray-700">Fecha de fin</label>
                            <input id="fecha_fin" type="datetime-local" name="fecha_fin"
                                value="{{ $agenda->fecha_fin->format('Y-m-d\TH:i') }}" required
                                class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" />
                        </div>
                        <label class="block text-sm font-bold text-gray-700 mt-4 ">Ejercicios</label>
                        <div class="mt-4" id="ejercicios-container">
                            @foreach($agenda->ejercicios as $ejercicio)
                            <div class="ejercicio-item flex flex-col gap-2 mb-2 w-full">
                                <label class="block text-sm font-medium text-gray-700">Tipo de ejercicio</label>
                                <select class="tipo-selector mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none sm:text-sm" data-current-tipo="{{ $ejercicio->id_tipo ?? '' }}">
                                    <option value="">Seleccionar tipo</option>
                                    @foreach($tipos as $tipo)
                                    <option value="{{ $tipo->id }}" {{ ($ejercicio->id_tipo ?? null) == $tipo->id ? 'selected' : '' }}>{{ $tipo->nombre }}</option>
                                    @endforeach
                                </select>
                                <select name="ejercicios[]" class="ejercicio-select border rounded px-2 py-1 w-full sm:w-auto" required>
                                    <option value="">Selecciona ejercicio</option>
                                    @foreach($ejercicios as $e)
                                    <option value="{{ $e->id }}" {{ $e->id == $ejercicio->id ? 'selected' : '' }}>{{ $ejercicio->nombre }}</option>
                                    @endforeach
                                </select>
                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 md:gap-6">
                                    <select name="metodo[]" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                        <option value="">Selecciona método</option>
                                        @foreach($metodos as $metodo)
                                        <option value="{{ $metodo->id }}" @if($ejercicio->pivot->metodo == $metodo->id ) selected @endif>
                                            {{ $metodo->nombre }}
                                        </option>
                                        @endforeach
                                    </select>
                                    <select name="progresion[]" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                        <option value="">Selecciona progresión</option>
                                        <option value="1" @if($ejercicio->pivot->progresion == 1) selected @endif>Lineal</option>
                                        <option value="2" @if($ejercicio->pivot->progresion == 2) selected @endif>Doble</option>
                                        <option value="3" @if($ejercicio->pivot->progresion == 3) selected @endif>Ondulante</option>
                                    </select>
                                    <input type="text" name="fundamento[]" value="{{$ejercicio->pivot->fundamento}}" placeholder="Fundamento biomecánico" class="border rounded px-2 py-1 w-full">
                                </div>
                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 md:gap-6">
                                    <input type="text" name="series[]" placeholder="Series" value="{{ $ejercicio->pivot->serie }}" class="border rounded px-2 py-1 w-full" required>
                                    <input type="text" name="repeticiones[]" placeholder="Repeticiones" value="{{ $ejercicio->pivot->repeticiones }}" class="border rounded px-2 py-1 w-full" required>
                                    <input type="text" name="carga[]" placeholder="Carga" value="{{ $ejercicio->pivot->carga }}" class="border rounded px-2 py-1 w-full" required>
                                </div>
                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 md:gap-6">
                                    <input type="text" name="rir[]" placeholder="RIR" value="{{ $ejercicio->pivot->rir }}" class="border rounded px-2 py-1 w-full">
                                    <input type="text" name="rpe[]" placeholder="RPE" value="{{ $ejercicio->pivot->rpe }}" class="border rounded px-2 py-1 w-full">
                                    <input type="text" name="rm[]" placeholder="% 1RM" value="{{ $ejercicio->pivot->rm }}" class="border rounded px-2 py-1 w-full">
                                    <input type="text" name="descanso[]" placeholder="Descanso" value="{{ $ejercicio->pivot->descanso }}" class="border rounded px-2 py-1 w-full" required>
                                </div>
                                <button type="button" class="remove-exercise bg-red-500 hover:bg-red-800 text-white font-bold py-2 px-4 rounded">
                                    Eliminar Ejercicio
                                </button>
                            </div>
                            @endforeach
                        </div>
                        <button type="button" id="add-exercise" class="bg-gray-700 text-white px-4 py-2 rounded">
                            <i class="fa-solid fa-plus mr-2"></i>
                            Agregar ejercicio
                        </button>
                        <br>
                        <div class="mt-4">
                            <label class="inline-flex items-center">
                                <input type="checkbox" name="modificar_futuros" value="1" class="form-checkbox">
                                <span class="ml-2 text-sm text-gray-700">Modificar todos los entrenamientos futuros de este cliente en el mismo día y hora</span>
                            </label>
                        </div>
                        <button type="submit" class="bg-green-600 hover:bg-green-800 text-white font-bold py-2 px-4 rounded mt-4">
                            Guardar cambios
                        </button>
                        <button type="button" onclick="location.href='{{ route('agendas.index') }}'" class="bg-red-500 hover:bg-red-800 text-white font-bold py-2 px-4 rounded ml-2 mt-4">
                            Cancelar
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('add-exercise').addEventListener('click', function() {
                let container = document.getElementById('ejercicios-container');
                let newExercise = document.createElement('div');
                newExercise.classList.add('ejercicio-item', 'flex', 'flex-col', 'gap-2', 'mb-2', 'w-full');
                newExercise.innerHTML = `
                    <label class="block text-sm font-medium text-gray-700">Tipo de ejercicio</label>
                    <select class="tipo-selector mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none sm:text-sm">
                        <option value="">Seleccionar tipo</option>
                        <option value="1">Pecho</option>
                        <option value="2">Espalda</option>
                        <option value="3">Hombros</option>
                        <option value="4">Piernas</option>
                        <option value="5">Bíceps</option>
                        <option value="6">Tríceps</option>
                        <option value="7">Abdominales</option>
                        <option value="8">Cardio</option>
                    </select>
                    <select name="ejercicios[]" class="ejercicio-select border rounded px-2 py-1 w-full sm:w-auto" required>
                        <option value="">Selecciona ejercicio</option>
                    </select>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 md:gap-6">
                        <select name="metodo[]" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" >
                            <option value="">Selecciona método</option>
                            <option value="1">Serie lineal</option>
                            <option value="2">Excéntrica acentuada</option>
                            <option value="3">Alta intensidad</option>
                        </select>
                        <select name="progresion[]" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" >
                            <option value="">Selecciona progresión</option>
                            <option value="1">Lineal</option>
                            <option value="2">Doble</option>
                            <option value="3">Ondulante</option>
                        </select>
                        <input type="text" name="fundamento[]" value="" placeholder="Fundamento biomecánico" class="border rounded px-2 py-1 w-full" >
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 md:gap-6">
                        <input type="text" name="series[]" placeholder="Series" value="" class="border rounded px-2 py-1 w-full" required>
                        <input type="text" name="repeticiones[]" placeholder="Repeticiones" value="" class="border rounded px-2 py-1 w-full" required>
                        <input type="text" name="carga[]" placeholder="Carga" value="" class="border rounded px-2 py-1 w-full" required>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 md:gap-6">
                        <input type="text" name="rir[]" placeholder="RIR" value="" class="border rounded px-2 py-1 w-full" >
                        <input type="text" name="rpe[]" placeholder="RPE" value="" class="border rounded px-2 py-1 w-full" >
                        <input type="text" name="rm[]" placeholder="% 1RM" value="" class="border rounded px-2 py-1 w-full" >
                        <input type="text" name="descanso[]" placeholder="Descanso" value="" class="border rounded px-2 py-1 w-full" required>
                    </div>
                    <button type="button" class="remove-exercise bg-red-500 hover:bg-red-800 text-white font-bold py-2 px-4 rounded">
                        Eliminar Ejercicio
                    </button>
                `;
                container.appendChild(newExercise);

                // Agregar listener al nuevo tipo-selector
                let tipoSel = newExercise.querySelector('.tipo-selector');
                let ejercicioSelect = newExercise.querySelector('.ejercicio-select');
                if (tipoSel) {
                    tipoSel.addEventListener('change', function() {
                        cargarEjerciciosPorTipo(this.value, ejercicioSelect);
                    });
                }

                // Agregar listener al remove button
                newExercise.querySelector('.remove-exercise').addEventListener('click', attachRemoveEvent);
            });

            // Listeners para ejercicios existentes
            document.querySelectorAll('.remove-exercise').forEach(button => {
                button.addEventListener('click', attachRemoveEvent);
            });

            // Listeners para los selectores de tipo existentes
            document.querySelectorAll('.tipo-selector').forEach(function(tipoSel) {
                tipoSel.addEventListener('change', function() {
                    let ejercicioSelect = this.closest('.ejercicio-item').querySelector('.ejercicio-select');
                    cargarEjerciciosPorTipo(this.value, ejercicioSelect);
                });
            });
        });

        function attachRemoveEvent(event) {
            event.preventDefault();
            let items = document.querySelectorAll('.ejercicio-item');
            if (items.length > 1) {
                event.target.closest('.ejercicio-item').remove();
            } else {
                alert('Debe haber al menos un ejercicio.');
            }
        }

        // Cargar ejercicios vía AJAX según tipo - para un select específico
        function cargarEjerciciosPorTipo(tipoId, selectEjercicios) {
            if (!tipoId || !selectEjercicios) return;
            fetch('/ejercicios/por-tipo/' + tipoId)
                .then(res => res.json())
                .then(resp => {
                    if (resp.success) {
                        const opciones = ['<option value="">Selecciona ejercicio</option>'].concat(resp.data.map(e => `<option value="${e.id}">${e.nombre}</option>`)).join('');
                        selectEjercicios.innerHTML = opciones;
                    }
                }).catch(err => console.error(err));
        }
    </script>
</x-admin-layout>