<x-admin-layout>
    <div class="py-4">
        <div class="">
            <div class="flex items-center justify-between mb-4">
                <a href="{{ route('clientes.index') }}" class="text-gray-700 hover:text-gray-500">
                    <i class="fas fa-circle-left fa-2x">&nbsp;</i>
                </a>
            </div>
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 overflow-x-auto w-full">
                    <h2 class="font-semibold text-xl text-gray-800 leading-tight mb-4">
                        Agregar Cliente
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

                    <form action="{{ route('clientes.store') }}" method="POST" class="space-y-6">
                        @csrf
                        <div class="grid grid-cols-1 sm:grid-cols-1 gap-4 md:gap-6">
                            <div class="mb-4 sm:mb-0">
                                <label for="perfil" class="block text-sm font-medium text-gray-700">Perfil</label>
                                <textarea name="perfil" id="perfil" placeholder="Información importante, por ejemplo, hipertenso, diabético, otros"
                                    class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">{{old('perfil')}}</textarea>
                            </div>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 md:gap-6">
                            <div class="mb-4 sm:mb-0">
                                <label for="nombre" class="block text-sm font-medium text-gray-700">Nombre</label>
                                <input type="text" name="nombres" id="nombres" value="{{ old('nombres') }}" required
                                    class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                            </div>
                            <div class="mb-4 sm:mb-0">
                                <label for="paterno" class="block text-sm font-medium text-gray-700">Apellido Paterno</label>
                                <input type="text" name="paterno" id="paterno" value="{{ old('paterno') }}" required
                                    class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                            </div>
                            <div>
                                <label for="materno" class="block text-sm font-medium text-gray-700">Apellido Materno</label>
                                <input type="text" name="materno" id="materno" value="{{ old('materno') }}"
                                    class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                            </div>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 md:gap-6">
                            <div class="mb-4 sm:mb-0">
                                <label for="ci" class="block text-sm font-medium text-gray-700">Cédula de Identidad</label>
                                <input type="text" name="ci" id="ci" value="{{ old('ci') }}"
                                    class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                            </div>
                            <div class="mb-4">
                                <label for="id_genero" class="block text-sm font-medium text-gray-700">Género</label>
                                <select name="id_genero" id="id_genero" required
                                    class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                    <option value="">Seleccionar genero</option>
                                    @foreach ($generos as $genero)
                                    <option value="{{ $genero->id }}" {{ old('id_genero') == $genero->id  ? 'selected' : '' }}>
                                        {{ $genero->nombre }}
                                    </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="mb-4 sm:mb-0">
                                <label for="fecha_nacimiento" class="block text-sm font-medium text-gray-700">Fecha de Nacimiento</label>
                                <input type="date" name="fecha_nacimiento" id="fecha_nacimiento" value="{{ old('fecha_nacimiento') }}" required
                                    class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                            </div>
                            <div class="mb-4 sm:mb-0">
                                <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                                <input type="email" name="email" id="email" value="{{ old('email') }}"
                                    class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                            </div>
                            <div class="mb-4 sm:mb-0">
                                <label for="telefono" class="block text-sm font-medium text-gray-700">Teléfono</label>
                                <input type="text" name="telefono" id="telefono" value="{{ old('telefono') }}"
                                    class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                            </div>
                            <div class="mb-4 sm:mb-0">
                                <label for=" direccion" class="block text-sm font-medium text-gray-700">Dirección</label>
                                <input type="text" name="direccion" id="direccion" value="{{ old('direccion') }}"
                                    class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                            </div>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 md:gap-6">
                            <div class="mb-4">
                                <label for="id_plan" class="block text-sm font-medium text-gray-700">Incluir Activación</label>
                                <select name="activacion" id="activacion" required
                                    class="select2 mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                    <option value="">Seleccionar </option>
                                    <option value="1" {{ old('activacion') == 1 ? 'selected' : '' }}>Si</option>
                                    <option value="0" {{ old('activacion') == 0 ? 'selected' : '' }}>No</option>
                                </select>
                            </div>
                            <div class="mb-4">
                                <label for="id_plan" class="block text-sm font-medium text-gray-700">Plan</label>
                                <select name="id_plan" id="id_plan" required
                                    class="select2 mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                    <option value="">Seleccionar plan</option>
                                    @foreach ($planes as $plan)
                                    <option value="{{ $plan->id }}" {{ old('id_plan') == $plan->id  ? 'selected' : '' }}>
                                        {{ $plan->nombre }} - ${{ $plan->valor }}
                                    </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="mb-4 sm:mb-0">
                                <label for="descuento" class="block text-sm font-medium text-gray-700">Descuento ($)</label>
                                <input type="number" name="descuento" id="descuento" step="0.01" min="0" value="{{ old('descuento', 0) }}"
                                    class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                            </div>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
                            <div class="mb-4">
                                <label for="id_usuario" class="block text-sm font-medium text-gray-700">Entrenador</label>
                                <select name="id_usuario" id="id_usuario" required
                                    class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                    <option value="">Seleccionar</option>
                                    @foreach ($usuarios as $usuario)
                                    <option value="{{ $usuario->id }}" {{ old('id_usuario') == $usuario->id ? 'selected' : '' }}>
                                        {{ $usuario->name }}
                                    </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="mb-4">
                                <label for="fecha_registro" class="block text-sm font-medium text-gray-700">Fecha de Inicio</label>
                                <input type="date" name="fecha_registro" id="fecha_registro" value="{{ old('fecha_registro', now()->format('Y-m-d')) }}"
                                    class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                            </div>
                            <div class="mb-4">
                                <label for="fecha_fin" class="block text-sm font-medium text-gray-700">Fecha de Fin</label>
                                <input type="date" name="fecha_fin" id="fecha_fin" value="{{ old('fecha_fin', now()->format('Y-m-d')) }}"
                                    class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                            </div>
                            <div class="mb-4">
                                <label for="fecha_vencimiento" class="block text-sm font-medium text-gray-700">Fecha de Vencimiento</label>
                                <input type="date" name="fecha_vencimiento" id="fecha_vencimiento" value="{{ old('fecha_vencimiento ', now()->format('Y-m-d')) }}"
                                    class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                            </div>

                            <div class="mb-4 sm:mb-0">
                                <label for="altura" class="block text-sm font-medium text-gray-700">Altura</label>
                                <input type="text" name="altura" id="altura" value="{{ old('altura') }}"
                                    class="w-full mt-1 block border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                            </div>

                            <div class="mb-4 sm:mb-0">
                                <label for="tipo_cliente" class="block text-sm font-medium text-gray-700">¿Cómo llegó a Max?</label>
                                <select name="id_motivo_ingreso" id="id_motivo_ingreso" required
                                    class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                    <option value="">Seleccionar motivo</option>
                                    @foreach ($motivos as $m)
                                    <option value="{{ $m->id }}" {{ old('id_motivo_ingreso') == $m->id ? 'selected' : '' }}>
                                        {{ $m->nombre }}
                                    </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="mb-4 sm:mb-0" style="display:none" id="divOtro">
                                <label for="otro_ingreso" class="block text-sm font-medium text-gray-700" style="display:none">¿Cuál?</label>
                                <input type="text" name="otro_ingreso" id="otro_ingreso" value="{{ old('altura') }}"
                                    class="w-full mt-1 block border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                            </div>
                            <div class="mb-4 sm:mb-0">
                                <label for="tipo_cliente" class="block text-sm font-medium text-gray-700">Tipo Cliente</label>
                                <select name="id_tipo_usuario" id="id_tipo_usuario" required
                                    class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                    <option value="">Seleccionar tipo</option>
                                    @foreach ($tipos_usuarios as $tu)
                                    <option value="{{ $tu->id }}" {{ old('id_tipo_usuario') == $tu->id ? 'selected' : '' }}>
                                        {{ $tu->nombre }}
                                    </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="mb-4 sm:mb-0">
                                <label for=" estado" class="block text-sm font-medium text-gray-700">Estado</label>
                                <select name="estado" id="estado"
                                    class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                    <option value="1" {{ old('estado') == '1' ? 'selected' : '' }}>Activo</option>
                                    <option value="0" {{ old('estado') == '0' ? 'selected' : '' }}>Inactivo</option>
                                </select>
                            </div>
                        </div>
                        <span class="hidden bg-green-600 bg-green-800 hover:bg-green-800 bg-red-600 bg-red-800 hover:bg-red-800"></span>
                        <span class="hidden bg-gray-800 bg-gray-500 bg-green-100 border-green-400 text-green-700"></span>
                        <span class="hidden bg-gray-700 bg-gray-500 bg-red-100 border-red-400 text-red-700"></span>

                        <div class="flex justify-start mt-6">
                            <button type="submit" class="bg-green-600 hover:bg-green-800 text-white font-bold py-2 px-4 rounded">
                                Guardar Cambios
                            </button>
                            <button type="button" onclick="location.href='{{ route('clientes.index') }}'" class="bg-red-500 hover:bg-red-800 text-white font-bold py-2 px-4 rounded ml-2">
                                Cancelar
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-admin-layout>
<script>
    document.getElementById('id_motivo_ingreso').addEventListener('change', function() {
        var otroDiv = document.getElementById('divOtro');
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
</script>