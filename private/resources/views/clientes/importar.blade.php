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
                        Importar clientes desde CSV
                    </h2>

                    <div class="mx-4 my-2 rounded-lg border border-sky-200 bg-sky-50 px-4 py-3 text-sm text-sky-800">
                        Se crea el registro del cliente y su cuenta de acceso (con clave generada), pero <strong>no se
                        envía ningún correo automáticamente</strong>. Para activar el acceso a la app de un cliente
                        importado, usa el botón "Enviar acceso" desde el listado, cuando quieras.
                    </div>
                    <div class="mx-4 my-2 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                        Solo acepta archivos <strong>CSV</strong> (no .xlsx). Si tienes el archivo en Excel: Archivo →
                        Guardar como → "CSV (delimitado por comas)".
                        <a href="{{ route('clientes.importar.plantilla') }}" class="underline font-semibold">Descargar plantilla de ejemplo</a>.
                    </div>

                    @if($errors->any())
                    <div class="mx-4 my-2 p-3 bg-red-100 border border-red-400 text-red-700 rounded">
                        <ul class="mb-0">
                            @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                    @endif

                    <div class="mx-4 my-4">
                        <h3 class="text-sm font-semibold text-gray-700 mb-2">Columnas del archivo</h3>
                        <div class="overflow-x-auto">
                            <table class="min-w-full text-sm border">
                                <thead class="bg-gray-100">
                                    <tr>
                                        <th class="px-3 py-2 text-left border">Columna</th>
                                        <th class="px-3 py-2 text-left border">¿Obligatoria?</th>
                                        <th class="px-3 py-2 text-left border">Formato</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr><td class="px-3 py-1 border">nombres</td><td class="px-3 py-1 border">Sí</td><td class="px-3 py-1 border">texto</td></tr>
                                    <tr><td class="px-3 py-1 border">paterno</td><td class="px-3 py-1 border">Sí</td><td class="px-3 py-1 border">texto (apellido)</td></tr>
                                    <tr><td class="px-3 py-1 border">materno</td><td class="px-3 py-1 border">No</td><td class="px-3 py-1 border">texto</td></tr>
                                    <tr><td class="px-3 py-1 border">email</td><td class="px-3 py-1 border">Sí</td><td class="px-3 py-1 border">correo, único</td></tr>
                                    <tr><td class="px-3 py-1 border">telefono</td><td class="px-3 py-1 border">Sí</td><td class="px-3 py-1 border">texto</td></tr>
                                    <tr><td class="px-3 py-1 border">ci</td><td class="px-3 py-1 border">Sí</td><td class="px-3 py-1 border">RUT/cédula, único</td></tr>
                                    <tr><td class="px-3 py-1 border">genero</td><td class="px-3 py-1 border">Sí</td><td class="px-3 py-1 border">Femenino / Masculino / Otro (o el ID: 1, 2, 3)</td></tr>
                                    <tr><td class="px-3 py-1 border">fecha_nacimiento</td><td class="px-3 py-1 border">No</td><td class="px-3 py-1 border">AAAA-MM-DD o DD-MM-AAAA</td></tr>
                                    <tr><td class="px-3 py-1 border">fecha_ingreso</td><td class="px-3 py-1 border">No</td><td class="px-3 py-1 border">AAAA-MM-DD o DD-MM-AAAA (si falta, se usa hoy)</td></tr>
                                    <tr><td class="px-3 py-1 border">direccion</td><td class="px-3 py-1 border">No</td><td class="px-3 py-1 border">texto</td></tr>
                                    <tr><td class="px-3 py-1 border">ciudad</td><td class="px-3 py-1 border">No</td><td class="px-3 py-1 border">texto</td></tr>
                                    <tr><td class="px-3 py-1 border">altura</td><td class="px-3 py-1 border">No</td><td class="px-3 py-1 border">metros, ej: 1.70</td></tr>
                                    <tr><td class="px-3 py-1 border">motivo_ingreso</td><td class="px-3 py-1 border">No</td><td class="px-3 py-1 border">texto — si no coincide con el catálogo, se deja vacío (no falla la fila)</td></tr>
                                    <tr><td class="px-3 py-1 border">plan</td><td class="px-3 py-1 border">No*</td><td class="px-3 py-1 border">nombre exacto del plan del gimnasio</td></tr>
                                    <tr><td class="px-3 py-1 border">entrenador</td><td class="px-3 py-1 border">No</td><td class="px-3 py-1 border">nombre exacto del entrenador — si no coincide, se deja sin asignar</td></tr>
                                </tbody>
                            </table>
                        </div>
                        <p class="text-xs text-gray-500 mt-2">
                            *"plan" es obligatorio por fila solo si no eliges un plan por defecto abajo para todo el
                            archivo.
                        </p>
                    </div>

                    <form action="{{ route('clientes.importar.previsualizar') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
                        @csrf

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 md:gap-6 px-4">
                            @if($esSuperAdmin && isset($gimnasios))
                            <div>
                                <label for="id_gimnasio" class="block text-sm font-medium text-gray-700">Gimnasio destino *</label>
                                <select name="id_gimnasio" id="id_gimnasio" required
                                    class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                    <option value="">Seleccionar...</option>
                                    @foreach($gimnasios as $gimnasio)
                                    <option value="{{ $gimnasio->id }}" {{ (string) $idGimnasio === (string) $gimnasio->id ? 'selected' : '' }}>
                                        {{ $gimnasio->nombre }}
                                    </option>
                                    @endforeach
                                </select>
                            </div>
                            @endif

                            <div>
                                <label for="id_plan_defecto" class="block text-sm font-medium text-gray-700">Plan por defecto</label>
                                <select name="id_plan_defecto" id="id_plan_defecto"
                                    class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                    <option value="">Sin plan por defecto (cada fila debe traer el suyo)</option>
                                    @foreach($planes as $plan)
                                    <option value="{{ $plan->id }}">{{ $plan->nombre }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label for="archivo" class="block text-sm font-medium text-gray-700">Archivo CSV *</label>
                                <input type="file" name="archivo" id="archivo" accept=".csv,text/csv" required
                                    class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                            </div>
                        </div>

                        <div class="px-4">
                            <button type="submit" class="bg-gray-700 hover:bg-gray-500 text-white font-bold py-2 px-4 rounded">
                                Previsualizar
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-admin-layout>
