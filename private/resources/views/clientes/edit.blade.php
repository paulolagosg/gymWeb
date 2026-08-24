<x-admin-layout>
    @php($isAdminLike = in_array((int) Auth::user()->id_tipo_usuario, [1, 10], true))
    @php($isSuperAdmin = (int) Auth::user()->id_tipo_usuario === 10)
    @php($lockedTrainerId = (int) Auth::user()->id_tipo_usuario === 2 ? Auth::user()->id : $cliente->id_usuario)
    <div class="py-4">
        <div class="">
            <div class="flex items-center justify-between mb-4 bg-white p-6 rounded-lg">
                <div class="text-gray-700">
                    <i class="fas fa-user fa-2x">&nbsp;{{ $cliente->nombres }} {{ $cliente->paterno }} {{ $cliente->materno }}</i>
                    <br><small>{{ $cliente->plan->nombre }}</small>
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
                    <div class="mx-4 my-2 rounded-lg border border-sky-200 bg-sky-50 px-4 py-3 text-sm text-sky-800">
                        Al guardar tambien se sincroniza el usuario asociado del cliente: nombre, correo, tipo cliente y gimnasio. La clave se sigue gestionando automaticamente fuera de este formulario.
                    </div>
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
                                <input type="text" name="ci" id="ci" value="{{ old('ci', $cliente->ci) }}" required class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
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
                                <label for="id_genero" class="block text-sm font-medium text-gray-700">Género</label>
                                <select name="id_genero" id="id_genero" required class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                    <option value="">Seleccionar genero</option>
                                    @foreach ($generos as $genero)
                                    <option value="{{ $genero->id }}" {{ (string) old('id_genero', $cliente->id_genero) === (string) $genero->id ? 'selected' : '' }}>
                                        {{ $genero->nombre }}
                                    </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="mb-4 sm:mb-0">
                                <label for="telefono" class="block text-sm font-medium text-gray-700">Teléfono</label>
                                <input type="text" name="telefono" id="telefono" value="{{ old('telefono', $cliente->telefono) }}" required class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                            </div>
                            <div class="mb-4 sm:mb-0">
                                <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                                <input type="email" name="email" id="email" value="{{ old('email', $cliente->email) }}" required class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
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
                                <label for="fecha_vencimiento" class="block text-sm font-medium text-gray-700">Fecha de Vencimiento</label>
                                <input type="date" name="fecha_vencimiento" id="fecha_vencimiento" value="{{ old('fecha_vencimiento', $cliente->fecha_pago) }}" required class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                            </div>
                            <div class="mb-4 sm:mb-0">
                                <label for="altura" class="block text-sm font-medium text-gray-700">Altura</label>
                                <input type="text" name="altura" id="altura" value="{{ old('altura', $cliente->altura) }}" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                            </div>
                            <div class="mb-4 sm:mb-0">
                                <label for="direccion" class="block text-sm font-medium text-gray-700">Dirección</label>
                                <input type="text" name="direccion" id="direccion" value="{{ old('direccion', $cliente->direccion) }}" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                            </div>
                            @if($isSuperAdmin)
                            <div class="mb-4 sm:mb-0">
                                <label for="id_gimnasio" class="block text-sm font-medium text-gray-700">Gimnasio</label>
                                <select name="id_gimnasio" id="id_gimnasio" required class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                    @foreach ($gimnasios as $gimnasio)
                                    <option value="{{ $gimnasio->id }}" {{ (string) old('id_gimnasio', $cliente->id_gimnasio) === (string) $gimnasio->id ? 'selected' : '' }}>
                                        {{ $gimnasio->nombre }}
                                    </option>
                                    @endforeach
                                </select>
                            </div>
                            @endif
                            <div class="mb-4 sm:mb-0">
                                <label for="id_plan" class="block text-sm font-medium text-gray-700">Plan</label>
                                <select name="id_plan" id="id_plan" onchange="activarDuo()" required
                                    class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                    @if(!$isAdminLike) disabled @endif>
                                    <option value="">Seleccionar plan</option>
                                    @foreach ($planes as $plan)
                                    <option data-tipo="{{$plan->tipo}}" data-gimnasio="{{ $plan->id_gimnasio }}" value="{{ $plan->id }}" {{ old('id_plan') == $plan->id  ? 'selected' : '' }} {{ $cliente->id_plan == $plan->id  ? 'selected' : '' }}>
                                        {{ $plan->nombre }} - ${{ $plan->valor }}
                                    </option>
                                    @endforeach
                                </select>
                                @if(!$isAdminLike)
                                <input type="hidden" name="id_plan" value="{{ $cliente->id_plan }}">
                                @endif
                            </div>
                            <div class="mb-4 sm:mb-0" id="divDuo" style="display:none;">
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
                                    @if(!$isAdminLike) disabled @endif>
                                    <option value="">Seleccionar entrenador</option>
                                    @foreach ($usuarios as $usuario)
                                    <option value="{{ $usuario->id }}" data-gimnasio="{{ $usuario->id_gimnasio }}" {{ old('id_usuario') == $usuario->id  ? 'selected' : '' }} {{ $cliente->id_usuario == $usuario->id  ? 'selected' : '' }}>
                                        {{ $usuario->name }}
                                    </option>
                                    @endforeach
                                </select>
                                @if(!$isAdminLike)
                                <input type="hidden" name="id_usuario" value="{{ $lockedTrainerId }}">
                                @endif
                                <p id="id_usuario_help" class="mt-1 text-xs text-gray-500">
                                    Selecciona el entrenador responsable del cliente.
                                </p>
                            </div>
                            <div class="mb-4 sm:mb-0">
                                <label for="tipo_cliente" class="block text-sm font-medium text-gray-700">¿Cómo llegó al gimnasio?</label>
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
                                <label for="id_tipo_usuario" class="block text-sm font-medium text-gray-700">Tipo Cliente</label>
                                <select name="id_tipo_usuario" id="id_tipo_usuario" required class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" @if(!$isAdminLike) disabled @endif>
                                    @foreach ($tipos_usuarios as $tu)
                                    <option value="{{ $tu->id }}" {{ (string) old('id_tipo_usuario', optional($cliente->user)->id_tipo_usuario) === (string) $tu->id ? 'selected' : '' }}>
                                        {{ $tu->nombre }}
                                    </option>
                                    @endforeach
                                </select>
                                @if(!$isAdminLike)
                                <input type="hidden" name="id_tipo_usuario" value="{{ optional($cliente->user)->id_tipo_usuario }}">
                                @endif
                            </div>
                            <div class="mb-4 sm:mb-0">
                                <label for="id_plan" class="block text-sm font-medium text-gray-700">Estado</label>
                                <select name="estado" id="estado" required
                                    class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                    @if(!$isAdminLike) disabled @endif>
                                    <option value="1" {{ old('estado') == '1' ? 'selected' : '' }} {{ $cliente->estado == 'Activo' ? 'selected' : '' }}>Activo</option>
                                    <option value="0" {{ old('estado') == '0' ? 'selected' : '' }} {{ $cliente->estado == 'Inactivo' ? 'selected' : '' }}>Inactivo</option>
                                </select>
                                @if(!$isAdminLike)
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
                        <div class="rounded-lg border border-slate-200 bg-slate-50 px-4 py-4">
                            <h3 class="text-sm font-semibold text-slate-800">Resumen de usuario asociado</h3>
                            <div class="mt-3 grid grid-cols-1 sm:grid-cols-3 gap-4">
                                <div>
                                    <label for="usuario_name_preview" class="block text-sm font-medium text-gray-700">Nombre de acceso</label>
                                    <input type="text" id="usuario_name_preview" readonly
                                        value="{{ trim(implode(' ', array_filter([old('nombres', $cliente->nombres), old('paterno', $cliente->paterno), old('materno', $cliente->materno)]))) }}"
                                        class="mt-1 block w-full border border-gray-300 rounded-md bg-stone-100 shadow-sm py-2 px-3 sm:text-sm">
                                </div>
                                <div>
                                    <label for="usuario_email_preview" class="block text-sm font-medium text-gray-700">Correo de acceso</label>
                                    <input type="text" id="usuario_email_preview" readonly value="{{ old('email', $cliente->email) }}"
                                        class="mt-1 block w-full border border-gray-300 rounded-md bg-stone-100 shadow-sm py-2 px-3 sm:text-sm">
                                </div>
                                <div>
                                    <label for="usuario_password_preview" class="block text-sm font-medium text-gray-700">Clave inicial</label>
                                    <input type="text" id="usuario_password_preview" readonly value="Gestionada automáticamente"
                                        class="mt-1 block w-full border border-gray-300 rounded-md bg-stone-100 shadow-sm py-2 px-3 sm:text-sm">
                                </div>
                            </div>
                        </div>
                        <div class="flex justify-start mt-6">
                            <button type="submit" class="bg-green-600 hover:bg-green-800 text-white font-bold py-2 px-4 rounded">
                                Guardar Cambios
                            </button>
                            @if(in_array((int) Auth::user()->id_tipo_usuario, [1, 2, 10], true))
                            <a href="{{ route('clientes.opciones.portada', $cliente->slug) }}" class="inline-block bg-red-500 hover:bg-red-800 text-white font-bold py-2 px-4 rounded ml-2">
                                Volver
                            </a>
                            @else
                            <a href="{{ route('clientes.agenda', $cliente->slug) }}" class="inline-block bg-red-500 hover:bg-red-800 text-white font-bold py-2 px-4 rounded ml-2">
                                Cancelar
                            </a>
                            @endif
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
        var gimnasioSelect = document.getElementById('id_gimnasio');
        var entrenadorHelp = document.getElementById('id_usuario_help');
        var nombresInput = document.getElementById('nombres');
        var paternoInput = document.getElementById('paterno');
        var maternoInput = document.getElementById('materno');
        var emailInput = document.getElementById('email');
        var usuarioNamePreview = document.getElementById('usuario_name_preview');
        var usuarioEmailPreview = document.getElementById('usuario_email_preview');

        function actualizarResumenUsuario() {
            if (usuarioNamePreview) {
                usuarioNamePreview.value = [
                    nombresInput?.value || '',
                    paternoInput?.value || '',
                    maternoInput?.value || '',
                ].filter(Boolean).join(' ').trim();
            }

            if (usuarioEmailPreview) {
                usuarioEmailPreview.value = emailInput?.value || '';
            }
        }

        function filtrarOpcionesPorGimnasio() {
            if (!gimnasioSelect) {
                activarDuo();
                return;
            }

            var gimnasioId = gimnasioSelect.value;
            ['id_plan', 'id_usuario'].forEach(function(selectId) {
                var select = document.getElementById(selectId);
                if (!select) return;

                Array.from(select.options).forEach(function(option, index) {
                    if (index === 0) {
                        option.hidden = false;
                        option.disabled = false;
                        return;
                    }

                    var optionGym = option.getAttribute('data-gimnasio') || '';
                    var visible = gimnasioId !== '' && optionGym === gimnasioId;
                    option.hidden = !visible;
                    option.disabled = !visible;
                });

                if (select.selectedIndex > 0 && select.options[select.selectedIndex] && select.options[select.selectedIndex].disabled) {
                    select.value = '';
                }
            });

            if (entrenadorHelp) {
                var entrenadorSelect = document.getElementById('id_usuario');
                var trainersDisponibles = entrenadorSelect ?
                    Array.from(entrenadorSelect.options).filter(function(option, index) {
                        return index > 0 && !option.disabled;
                    }) :
                    [];

                if (!gimnasioId) {
                    entrenadorHelp.textContent = 'Selecciona primero un gimnasio para ver entrenadores disponibles.';
                } else if (trainersDisponibles.length === 0) {
                    entrenadorHelp.textContent = 'No hay entrenadores disponibles para el gimnasio seleccionado.';
                } else {
                    entrenadorHelp.textContent = 'Selecciona el entrenador responsable del cliente.';
                }
            }

            activarDuo();
        }

        if (gimnasioSelect) {
            gimnasioSelect.addEventListener('change', filtrarOpcionesPorGimnasio);
            filtrarOpcionesPorGimnasio();
        } else {
            activarDuo();
        }

        [nombresInput, paternoInput, maternoInput, emailInput].forEach(function(input) {
            if (input) {
                input.addEventListener('input', actualizarResumenUsuario);
            }
        });

        actualizarResumenUsuario();
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
        var selectedOption = planSelect.options[planSelect.selectedIndex];
        var tipo = selectedOption ? selectedOption.getAttribute('data-tipo') : null;
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