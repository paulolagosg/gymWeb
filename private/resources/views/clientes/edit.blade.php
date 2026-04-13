<x-admin-layout>
    <div class="py-4">
        <div class="">
            <div class="flex items-center justify-between mb-4 bg-white p-6 rounded-lg">
                <a href="{{ route('clientes.opciones.portada', $cliente->slug) }}" class="text-gray-700 hover:text-gray-500">
                    <i class="fas fa-circle-left fa-2x">&nbsp;{{ $cliente->nombres }} {{ $cliente->paterno }} {{ $cliente->materno }}</i>
                    <br><small>{{$cliente->plan->nombre}}</small>
                </a>
            </div>
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 overflow-x-auto w-full">
                    <h2 class="text-xl font-bold mb-4">Modificar Cliente</h2>
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
                    <form action="{{ route('clientes.update', $cliente->slug) }}" method="POST" class="space-y-4">
                        @csrf
                        @method('PUT')
                        <div class="grid grid-cols-1 sm:grid-cols-1 gap-4 md:gap-6">
                            <div class="mb-4 sm:mb-0">
                                <label for="perfil" class="block text-sm font-medium text-gray-700">Perfil</label>
                                <textarea name="perfil" id="perfil" placeholder="Información importante, por ejemplo, hipertenso, diabético, otros"
                                    class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">{{old('perfil',$cliente->perfil)}}</textarea>
                            </div>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 md:gap-6">
                            <div class="mb-4 sm:mb-0">
                                <label for="ci" class="block text-sm font-medium text-gray-700">Cédula de Identidad</label>
                                <input type="text" name="ci" id="ci" value="{{ old('ci', $cliente->ci) }}" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                            </div>
                            <div class="mb-4 sm:mb-0">
                                <label for="nombres" class="block text-sm font-medium text-gray-700">Nombres</label>
                                <input type="text" name="nombres" id="nombres" value="{{ old('nombres', $cliente->nombres) }}" required class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                            </div>
                            <div class="mb-4 sm:mb-0">
                                <label for="paterno" class="block text-sm font-medium text-gray-700">Apellido Paterno</label>
                                <input type="text" name="paterno" id="paterno" value="{{ old('paterno', $cliente->paterno) }}" required class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                            </div>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 md:gap-6">
                            <div class="mb-4 sm:mb-0">
                                <label for="materno" class="block text-sm font-medium text-gray-700">Apellido Materno</label>
                                <input type="text" name="materno" id="materno" value="{{ old('materno', $cliente->materno) }}" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                            </div>
                            <div class="mb-4 sm:mb-0">
                                <label for="telefono" class="block text-sm font-medium text-gray-700">Teléfono</label>
                                <input type="text" name="telefono" id="telefono" value="{{ old('telefono', $cliente->telefono) }}" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                            </div>
                            <div class="mb-4 sm:mb-0">
                                <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                                <input type="email" name="email" id="email" value="{{ old('email', $cliente->email) }}" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                            </div>
                            <div class="mb-4 sm:mb-0">
                                <label for="fecha_nacimiento" class="block text-sm font-medium text-gray-700">Fecha de Nacimiento</label>
                                <input type="date" name="fecha_nacimiento" id="fecha_nacimiento" value="{{ old('fecha_nacimiento', $cliente->fecha_nacimiento) }}" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                            </div>
                            <div class="mb-4 sm:mb-0">
                                <label for="fecha_ingreso" class="block text-sm font-medium text-gray-700">Fecha de Inicio</label>
                                <input type="date" name="fecha_ingreso" id="fecha_ingreso" value="{{ old('fecha_ingreso', $cliente->fecha_ingreso) }}" required class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                            </div>
                            <div class="mb-4 sm:mb-0">
                                <label for="fecha_fin" class="block text-sm font-medium text-gray-700">Fecha de Fin</label>
                                <input type="date" name="fecha_fin" id="fecha_fin" value="{{ old('fecha_fin', $cliente->fecha_fin) }}" required class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                            </div>
                            <div class="mb-4 sm:mb-0">
                                <label for="altura" class="block text-sm font-medium text-gray-700">Altura</label>
                                <input type="text" name="altura" id="altura" value="{{ old('altura', $cliente->altura) }}" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                            </div>
                            <div class="mb-4 sm:mb-0">
                                <label for="direccion" class="block text-sm font-medium text-gray-700">Dirección</label>
                                <input type="text" name="direccion" id="direccion" value="{{ old('direccion', $cliente->direccion) }}" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                            </div>
                            <div class="mb-4 sm:mb-0">
                                <label for="id_plan" class="block text-sm font-medium text-gray-700">Plan</label>
                                <select name="id_plan" id="id_plan" onchange="activarDuo()" required
                                    class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                    @if(Auth::user()->id_tipo_usuario > 2) disabled @endif>
                                    <option value="">Seleccionar plan</option>
                                    @foreach ($planes as $plan)
                                    <option data-tipo="{{$plan->tipo}}" value="{{ $plan->id }}" {{ old('id_plan') == $plan->id  ? 'selected' : '' }} {{ $cliente->id_plan == $plan->id  ? 'selected' : '' }}>
                                        {{ $plan->nombre }} - ${{ $plan->valor }}
                                    </option>
                                    @endforeach
                                </select>
                                @if(Auth::user()->id_tipo_usuario != 1)
                                <input type="hidden" name="id_plan" value="{{ $cliente->id_plan }}">
                                @endif
                            </div>
                            <div class="mb-4 sm:mb-0" id="divDuo" style="display: {{ (old('id_plan',$cliente->id_plan) && isset($plan) && $plan->tipo == 2) ? 'block' : 'none' }};">
                                <label for="id_plan" class="block text-sm font-medium text-gray-700">Dupla</label>
                                <select name="id_cliente_duo" id="id_cliente_duo"
                                    class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                    <option value="">Seleccionar dupla</option>
                                    @foreach($clientes_duos as $c)
                                    <option value="{{ $c->id }}" {{ old('id_cliente_duo') == $c->id  ? 'selected' : '' }} {{ $cliente->id_cliente_duo == $c->id  ? 'selected' : '' }}>
                                        {{ $c->nombres }} {{ $c->paterno }} {{ $c->materno }}
                                    </option>
                                    @endforeach
                                </select>

                            </div>
                            <div class="mb-4 sm:mb-0">
                                <label for="id_plan" class="block text-sm font-medium text-gray-700">Entrenador</label>
                                <select name="id_usuario" id="id_usuario" required
                                    class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                    @if(Auth::user()->id_tipo_usuario != 1) disabled @endif>
                                    <option value="">Seleccionar entrenador</option>
                                    @foreach ($usuarios as $usuario)
                                    <option value="{{ $usuario->id }}" {{ old('id_usuario') == $usuario->id  ? 'selected' : '' }} {{ $cliente->id_usuario == $usuario->id  ? 'selected' : '' }}>
                                        {{ $usuario->name }}
                                    </option>
                                    @endforeach
                                </select>
                                @if(Auth::user()->id_tipo_usuario != 1)
                                <input type="hidden" name="id_usuario" value="{{ Auth::user()->id }}">
                                @endif
                            </div>
                            <div class="mb-4 sm:mb-0">
                                <label for="tipo_cliente" class="block text-sm font-medium text-gray-700">¿Cómo llegó a Max?</label>
                                <select name="id_motivo_ingreso" id="id_motivo_ingreso" required
                                    class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                    <option value="">Seleccionar motivo</option>
                                    @foreach ($motivosi as $m)
                                    <option value="{{ $m->id }}" {{ old('id_motivo_ingreso') == $m->id ? 'selected' : '' }} {{ $cliente->id_motivo_ingreso == $m->id ? 'selected' : '' }}>
                                        {{ $m->nombre }}
                                    </option>
                                    @endforeach
                                </select>
                                <input type="hidden" id="h_motivo_ingreso" value="{{$cliente->id_motivo_ingreso}}" />

                            </div>
                            <div class="mb-4 sm:mb-0" style="display:none" id="divOtroIngreso">
                                <label id="lblOtroIngreso" for="otro_ingreso" class="block text-sm font-medium text-gray-700" style="display:none">¿Cuál?</label>
                                <input id="inputOtroIngreso" type="text" name="otro_ingreso" id="otro_ingreso" value="{{ old('otro_ingreso',$cliente->otro_ingreso) }}"
                                    class="w-full mt-1 block border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                            </div>
                            <div class="mb-4 sm:mb-0">
                                <label for="id_plan" class="block text-sm font-medium text-gray-700">Estado</label>
                                <select name="estado" id="estado"
                                    class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                    @if(Auth::user()->id_tipo_usuario != 1) disabled @endif>
                                    <option value="1" {{ old('estado') == '1' ? 'selected' : '' }} {{ $cliente->estado == 'Activo' ? 'selected' : '' }}>Activo</option>
                                    <option value="0" {{ old('estado') == '0' ? 'selected' : '' }} {{ $cliente->estado == 'Inactivo' ? 'selected' : '' }}>Inactivo</option>
                                </select>
                                @if(Auth::user()->id_tipo_usuario != 1)
                                <input type="hidden" name="estado" value="{{ $cliente->estado =='Activo' ? '1' : '0' }}">
                                @endif
                            </div>
                            <div class="mb-4 sm:mb-0" id="divMotivo" style="display:none">
                                <label for="id_motivo_egreso" class="block text-sm font-medium text-gray-700">Motivo Egreso</label>
                                <select name="id_motivo_egreso" id="id_motivo_egreso"
                                    class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                    <option value="">Seleccionar motivo</option>
                                    @foreach ($motivos as $m)
                                    <option value="{{ $m->id }}" {{ old('id_motivo_egreso') == $m->id ? 'selected' : '' }}>
                                        {{ $m->nombre }}
                                    </option>
                                    @endforeach
                                </select>
                                <input type="hidden" id="h_motivo" value="{{$cliente->id_motivo_egreso}}" />
                            </div>
                            <div class="mb-4 sm:mb-0" style="display:block" id="divOtro">
                                <label for="otro_egreso" class="block text-sm font-medium text-gray-700" style="display:none">¿Cuál?</label>
                                <input type="text" name="otro_egreso" id="otro_egreso" value="{{ old('otro_egreso',$cliente->otro_egreso) }}"
                                    class="w-full mt-1 block border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                            </div>
                        </div>
                        <div class="flex justify-start mt-6">
                            <button type="submit" class="bg-green-600 hover:bg-green-800 text-white font-bold py-2 px-4 rounded">
                                Guardar Cambios
                            </button>
                            <button type="button" onclick="location.href='{{ route('clientes.opciones.portada', $cliente->slug) }}'" class="bg-red-500 hover:bg-red-800 text-white font-bold py-2 px-4 rounded ml-2">
                                Cancelar
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
</x-admin-layout>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        var estadoSelect = document.getElementById('estado');
        var motivoIngreso = document.getElementById('id_motivo_ingreso');
        if (estadoSelect.value == 0) {
            var otroMotivo = document.getElementById('divMotivo');
            otroMotivo.style.display = 'block';
            otroMotivo.querySelector('select').setAttribute('required', 'required');
            sMotivo = document.getElementById('h_motivo').value;
            otroMotivo.querySelector('select').value = sMotivo;
            otroMotivo.style.display = 'block';
            var otroDiv = document.getElementById('divOtro');
            var otroLabel = otroDiv.querySelector('label');
            otroDiv.style.display = 'block';
            otroLabel.style.display = 'block';

        } else {
            var otroMotivo = document.getElementById('divMotivo');
            otroMotivo.style.display = 'none';
            otroMotivo.querySelector('select').removeAttribute('required');
            otroMotivo.querySelector('select').value = '';
            var otroDiv = document.getElementById('divOtro');
            var otroLabel = otroDiv.querySelector('label');
            otroDiv.style.display = 'none';
            otroLabel.style.display = 'none';
        }
        console.log(document.getElementById('h_motivo_ingreso').value);
        if (document.getElementById('h_motivo_ingreso').value == 3) {
            document.getElementById('divOtroIngreso').style.display = 'block';
            document.getElementById('lblOtroIngreso').style.display = 'block';
            document.getElementById('inputOtroIngreso').style.display = 'block';
            document.getElementById('inputOtroIngreso').setAttribute('required', 'required');
        }

        var planSelect = document.getElementById('id_plan');
        var duoSelect = document.getElementById('id_usuario');
        var selectedOption = planSelect.options[planSelect.selectedIndex];
        var tipo = selectedOption.getAttribute('data-tipo');
        var usuario_duo = document.getElementById('id_cliente_duo');
        var divDuo = document.getElementById('divDuo');
        if (tipo == 2) {
            divDuo.style.display = 'block';
        } else {
            divDuo.style.display = 'none';
            usuario_duo.value = '';
        }
    });

    document.getElementById('estado').addEventListener('change', function() {
        var motivoDiv = document.getElementById('divMotivo');
        var motivoLabel = motivoDiv.querySelector('label');
        var motivoSelect = motivoDiv.querySelector('select');
        var otroDiv = document.getElementById('divOtro');
        var otroLabel = otroDiv.querySelector('label');
        var otroInput = otroDiv.querySelector('input');
        if (this.options[this.selectedIndex].text === 'Inactivo') {
            motivoDiv.style.display = 'block';
            motivoLabel.style.display = 'block';
            motivoSelect.setAttribute('required', 'required');
            if (motivoSelect.value == 9) {
                otroDiv.style.display = 'block';
                otroLabel.style.display = 'block';
                otroInput.setAttribute('required', 'required');
            }
        } else {
            motivoDiv.style.display = 'none';
            motivoLabel.style.display = 'none';
            motivoSelect.removeAttribute('required');
            motivoSelect.value = '';
            otroDiv.style.display = 'none';
            otroLabel.style.display = 'none';
            otroInput.removeAttribute('required');
            otroInput.value = '';
        }
    });
    document.getElementById('id_motivo_egreso').addEventListener('change', function() {
        var otroDiv = document.getElementById('divOtro');
        var otroLabel = otroDiv.querySelector('label');
        var otroInput = otroDiv.querySelector('input');
        if (this.options[this.selectedIndex].text === 'Otro Motivo') {
            otroDiv.style.display = 'block';
            otroLabel.style.display = 'block';
            otroInput.setAttribute('required', 'required');
        } else {
            otroDiv.style.display = 'none';
            otroLabel.style.display = 'none';
            otroInput.removeAttribute('required');
        }
    });

    document.getElementById('id_motivo_ingreso').addEventListener('change', function() {
        var otroDiv = document.getElementById('divOtroIngreso');
        var otroLabel = otroDiv.querySelector('label');
        var otroInput = otroDiv.querySelector('input');
        if (this.options[this.selectedIndex].text === 'Otro') {
            otroDiv.style.display = 'block';
            otroLabel.style.display = 'block';
            otroInput.setAttribute('required', 'required');
        } else {
            otroDiv.style.display = 'none';
            otroLabel.style.display = 'none';
            otroInput.removeAttribute('required');
            otroInput.value = '';
        }
    });

    function activarDuo() {
        var planSelect = document.getElementById('id_plan');
        var duoSelect = document.getElementById('id_usuario');
        var selectedOption = planSelect.options[planSelect.selectedIndex];
        var tipo = selectedOption.getAttribute('data-tipo');
        var usuario_duo = document.getElementById('id_cliente_duo');
        var divDuo = document.getElementById('divDuo');
        if (tipo == 2) {
            divDuo.style.display = 'block';
        } else {
            divDuo.style.display = 'none';
            usuario_duo.value = '';
        }
    }
</script>