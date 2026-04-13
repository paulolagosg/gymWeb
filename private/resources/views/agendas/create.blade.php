<x-admin-layout>
    <div class="py-4">
        <div class="">
            <div class="flex items-center justify-between mb-4">
                <a href="{{ route('agendas.index') }}" class="text-gray-700 hover:text-gray-500">
                    <i class="fas fa-circle-left fa-2x">&nbsp;</i>
                </a>
            </div>
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                        {{ __('Agregar Entrenamiento') }}
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
                    <form method="POST" action="{{ route('agendas.store') }}">
                        @csrf
                        <div class="mt-4">
                            <label for="id_cliente" class="block text-sm font-medium text-gray-700">Cliente</label>
                            <select onchange="activarDuo(this.value)" id="id_cliente" name="id_cliente" required class="select2 mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                <option value="">Seleccionar cliente</option>
                                @foreach ($clientes as $cliente)
                                <option data-tipo="{{$cliente->plan->tipo}}" value="{{ $cliente->id }}" {{ old('id_cliente', $agenda->id_cliente ?? '') == $cliente->id ? 'selected' : '' }}>{{ $cliente->nombres }} {{ $cliente->paterno }} {{ $cliente->materno }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mt-4 sm:mb-0" id="divDuo" style="display: {{ (old('id_plan',$cliente->id_plan) && isset($plan) && $plan->tipo == 2) ? 'block' : 'none' }};">
                            <label for="id_plan" class="block text-sm font-medium text-gray-700">Seleccione si quiere definir el mismo entrenamiento para la dupla</label>
                            <select name="id_cliente_duo" id="id_cliente_duo"
                                class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                <option value="">Seleccionar dupla</option>
                            </select>

                        </div>
                        <div class="mt-4">
                            <label for="id_usuario" class="block text-sm font-medium text-gray-700">Entrenador</label>

                            <select id="id_usuario" name="id_usuario" required class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                <option value="">Seleccionar entrenador</option>
                                @foreach ($usuarios as $usuario)
                                <option value="{{ $usuario->id }}" @if($usuario->id == Auth::user()->id) selected @endif>{{ $usuario->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mt-4">
                            <label for="fecha_inicio" class="block text-sm font-medium text-gray-700">Fecha de inicio</label>
                            <input value="{{ old('fecha_inicio', isset($agenda) ? \Carbon\Carbon::parse($agenda->fecha_inicio)->format('Y-m-d\TH:i') : '') }}" id="fecha_inicio" type="datetime-local" name="fecha_inicio" required class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" />
                        </div>

                        <div class="mt-4">
                            <label for="fecha_fin" class="block text-sm font-medium text-gray-700">Fecha de fin</label>
                            <input value="{{ old('fecha_fin', isset($agenda) ? \Carbon\Carbon::parse($agenda->fecha_fin)->format('Y-m-d\TH:i') : '') }}" id="fecha_fin" type="datetime-local" name="fecha_fin" required class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" />
                        </div>
                        <div class="mt-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Días de la semana:</label>
                            <div class="flex flex-wrap gap-4">
                                @foreach(['0'=>'Domingo','1'=>'Lunes','2'=>'Martes','3'=>'Miércoles','4'=>'Jueves','5'=>'Viernes','6'=>'Sábado'] as $num => $dia)
                                <label>
                                    <input type="checkbox" name="dias_semana[]" value="{{ $num }}"
                                        {{ (is_array(old('dias_semana')) && in_array($num, old('dias_semana'))) ? 'checked' : '' }}>
                                    {{ $dia }}
                                </label>
                                @endforeach
                            </div>
                            <small class="text-gray-500">Selecciona uno o más días para repetir la agenda.</small>
                        </div>
                        <div class="mt-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Repetir agenda:</label>
                            <div class="flex flex-col sm:flex-row gap-4">
                                <label>
                                    <input type="radio" name="recurrencia" value="ninguna" checked>
                                    No repetir
                                </label>
                                <label>
                                    <input type="radio" name="recurrencia" value="mensual">
                                    1 mes
                                </label>
                                <label>
                                    <input type="radio" name="recurrencia" value="trimestral">
                                    1 Trimestre
                                </label>
                                <label>
                                    <input type="radio" name="recurrencia" value="semestral">
                                    1 Semestre
                                </label>
                                <label>
                                    <input type="radio" name="recurrencia" value="anual">
                                    1 Año
                                </label>
                            </div>
                        </div>
                        <label class="block text-sm font-bold text-gray-700 mt-4 ">Ejercicios</label>

                        <div class="mt-4" id="ejercicios-container">
                            @foreach($agenda->ejercicios ?? [] as $i => $ejercicio)
                            <div class="ejercicio-item flex flex-col gap-2 mb-2 w-full">
                                <label class="block text-sm font-medium text-gray-700">Tipo de ejercicio</label>
                                <select class="tipo-selector mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none sm:text-sm" data-current-tipo="{{ $ejercicio->id_tipo ?? '' }}">
                                    <option value="">Seleccionar tipo</option>
                                    @foreach($tipos as $tipo)
                                    <option value="{{ $tipo->id }}" {{ ($ejercicio->id_tipo ?? null) == $tipo->id ? 'selected' : '' }}>{{ $tipo->nombre }}</option>
                                    @endforeach
                                </select>
                                <select name="ejercicios[]" class="ejercicio-select border rounded px-2 py-1 w-full" required>
                                    @foreach($ejercicios as $ej)
                                    <option value="{{ $ej->id }}" {{ $ejercicio->id == $ej->id ? 'selected' : '' }}>
                                        {{ $ej->nombre }}
                                    </option>
                                    @endforeach
                                </select>
                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 md:gap-6">
                                    <select name="metodo[]" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                        <option value="">Selecciona método</option>
                                        @foreach($metodos as $metodo)
                                        <option value="{{ $metodo->id }}" {{ $ejercicio->pivot->metodo == $metodo->id ? 'selected' : '' }}>
                                            {{ $metodo->nombre }}
                                        </option>
                                        @endforeach
                                    </select>
                                    <select name="progresion[]" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                        <option value="">Selecciona progresión</option>
                                        <option value="1">Lineal</option>
                                        <option value="2">Doble</option>
                                        <option value="3">Ondulante</option>
                                    </select>
                                    <input type="text" name="fundamento[]" placeholder="Fundamento biomecánico" class="border rounded px-2 py-1 w-full">
                                </div>
                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 md:gap-6">
                                    <input type="text" name="series[]" value="{{ $ejercicio->pivot->serie }}" class="border rounded px-2 py-1 w-full" required>
                                    <input type="text" name="repeticiones[]" placeholder="Repeticiones" value="{{ $ejercicio->pivot->repeticiones }}" class="border rounded px-2 py-1  w-full" required>
                                    <input type="text" name="carga[]" placeholder="Carga" value="{{ $ejercicio->pivot->carga }}" class="border rounded px-2 py-1  w-full" required>
                                </div>
                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 md:gap-6">
                                    <input type="text" name="rir[]" placeholder="RIREE" value="{{ $ejercicio->pivot->rir }}" class="border rounded px-2 py-1  w-full ">
                                    <input type="text" name="rpe[]" placeholder="RPE" value="{{ $ejercicio->pivot->rpe }}" class="border rounded px-2 py-1  w-full ">
                                    <input type="text" name="rm[]" placeholder="% 1RM" value="{{ $ejercicio->pivot->rm }}" class="border rounded px-2 py-1  w-full ">
                                    <input type="text" name="descanso[]" placeholder="Descanso" value="{{ $ejercicio->pivot->descanso }}" class="border rounded px-2 py-1  w-full " required>
                                </div>
                                <button type="button" class="remove-exercise  bg-red-500 hover:bg-red-800 text-white font-bold py-2 px-4 rounded">
                                    Eliminar Ejercicio
                                </button>
                            </div>
                            @endforeach

                            <div class="ejercicio-item flex flex-col gap-2 mb-2 w-full">
                                <label class="block text-sm font-medium text-gray-700">Tipo de ejercicio</label>
                                <select class="tipo-selector mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none sm:text-sm">
                                    <option value="">Seleccionar tipo</option>
                                    @foreach($tipos as $tipo)
                                    <option value="{{ $tipo->id }}">{{ $tipo->nombre }}</option>
                                    @endforeach
                                </select>
                                <select name="ejercicios[]" class="ejercicio-select mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" required>
                                    <option value="">Selecciona ejercicio</option>
                                    @foreach($ejercicios as $ejercicio)
                                    <option value="{{ $ejercicio->id }}">{{ $ejercicio->nombre }}</option>
                                    @endforeach
                                </select>
                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 md:gap-6">
                                    <select name="metodo[]" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                        <option value="">Selecciona método</option>
                                        @foreach($metodos as $metodo)
                                        <option value="{{ $metodo->id }}">
                                            {{ $metodo->nombre }}
                                        </option>
                                        @endforeach
                                    </select>
                                    <select name="progresion[]" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                        <option value="">Selecciona progresión</option>
                                        <option value="1">Lineal</option>
                                        <option value="2">Doble</option>
                                        <option value="3">Ondulante</option>
                                    </select>
                                    <input type="text" name="fundamento[]" placeholder="Fundamento biomecánico" class="border rounded px-2 py-1 w-full">
                                </div>
                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 md:gap-6">
                                    <input type="text" name="series[]" placeholder="Series" class="border rounded px-2 py-1 w-full" required>
                                    <input type="text" name="repeticiones[]" placeholder="Repeticiones" class="border rounded px-2 py-1 w-full" required>
                                    <input type="text" name="carga[]" placeholder="Carga" class="border rounded px-2 py-1  w-full" required>
                                </div>
                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 md:gap-6">
                                    <input type="text" name="rir[]" placeholder="RIR" class="border rounded px-2 py-1  w-full">
                                    <input type="text" name="rpe[]" placeholder="RPE" class="border rounded px-2 py-1  w-full ">
                                    <input type="text" name="rm[]" placeholder="% 1RM" class="border rounded px-2 py-1  w-full">
                                    <input type="text" name="descanso[]" placeholder="Descanso" class="border rounded px-2 py-1  w-full" required>
                                </div>
                                <button type="button" class="remove-exercise bg-red-500 hover:bg-red-800 text-white font-bold py-2 px-4 rounded">
                                    Eliminar Ejercicio
                                </button>
                            </div>
                        </div>
                        <button type="button" id="add-ejercicio" class="bg-gray-800 text-white px-3 py-1 rounded mb-4">+ Agregar ejercicio</button>

                        <div class="mt-4">
                            <button type="submit" class="bg-green-600 hover:bg-green-800 text-white font-bold py-2 px-4 rounded">
                                Guardar Cambios
                            </button>
                            <button type="button" onclick="location.href='{{ route('agendas.index') }}'" class="bg-red-500 hover:bg-red-800 text-white font-bold py-2 px-4 rounded ml-2">
                                Cancelar
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Agregar ejercicio
            document.getElementById('add-ejercicio').addEventListener('click', function() {
                let container = document.getElementById('ejercicios-container');
                let items = container.getElementsByClassName('ejercicio-item');
                let newItem = items[0].cloneNode(true);

                // Limpia los valores del nuevo item
                newItem.querySelectorAll('select, input').forEach(el => el.value = '');

                // Agregar listener al nuevo tipo-selector
                let tipoSel = newItem.querySelector('.tipo-selector');
                if (tipoSel) {
                    tipoSel.addEventListener('change', function() {
                        let ejercicioSelect = this.closest('.ejercicio-item').querySelector('.ejercicio-select');
                        cargarEjerciciosPorTipo(this.value, ejercicioSelect);
                    });
                }

                container.appendChild(newItem);
            });

            // Eliminar ejercicio - event delegation
            document.getElementById('ejercicios-container').addEventListener('click', function(e) {
                if (e.target.classList.contains('remove-exercise')) {
                    let items = document.querySelectorAll('#ejercicios-container .ejercicio-item');
                    if (items.length > 1) {
                        e.target.closest('.ejercicio-item').remove();
                    } else {
                        alert('Debe haber al menos un ejercicio.');
                    }
                }
            });

            // Listeners para los selectores de tipo existentes
            document.querySelectorAll('.tipo-selector').forEach(function(tipoSel) {
                tipoSel.addEventListener('change', function() {
                    let ejercicioSelect = this.closest('.ejercicio-item').querySelector('.ejercicio-select');
                    cargarEjerciciosPorTipo(this.value, ejercicioSelect);
                });
            });
        });

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

        function activarDuo(idCliente) {

            var planSelect = document.getElementById('id_cliente');
            var selectedOption = planSelect.options[planSelect.selectedIndex];
            var tipo = selectedOption.getAttribute('data-tipo');
            var usuario_duo = document.getElementById('id_cliente_duo');
            var divDuo = document.getElementById('divDuo');
            if (tipo == 2) {
                usuario_duo.innerHTML = '';
                var optVacio = document.createElement('option');
                optVacio.value = "";
                optVacio.innerHTML = 'Seleccionar dupla';
                usuario_duo.appendChild(optVacio);
                $.ajax({
                    url: '/clientes/traeDupla/' + idCliente,
                    type: 'GET',
                    dataType: 'json',
                    success: function(data) {
                        if (data.success) {
                            const cliente = data.data;
                            var opt = document.createElement('option');
                            opt.value = cliente.id;
                            opt.innerHTML = cliente.nombres + ' ' + cliente.paterno;
                            usuario_duo.appendChild(opt);
                            divDuo.style.display = 'block';

                        } else {
                            divDuo.style.display = 'none';
                            usuario_duo.value = '';
                            console.error('Error:', data.message);
                        }
                    },
                    error: function(xhr, status, error) {
                        divDuo.style.display = 'none';
                        usuario_duo.value = '';
                        console.error('Error:', error);
                    }
                });

            } else {
                divDuo.style.display = 'none';
                usuario_duo.value = '';
            }
        }
    </script>
</x-admin-layout>