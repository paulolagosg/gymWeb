<x-admin-layout>
    <div class="py-4">
        <div class="">
            <div class="flex items-center justify-between mb-4">
                <a href="{{ route('caja.index') }}" class="text-gray-700 hover:text-gray-500">
                    <i class="fas fa-circle-left fa-2x">&nbsp;</i>
                </a>
            </div>
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-4">
                <h2 class="text-xl font-bold mb-4">Agregar Movimiento</h2>
                <form method="POST" action="{{ route('caja.store') }}">
                    @csrf
                    <div class="mb-4">
                        <label>Fecha:</label>
                        <input type="date" name="fecha" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" required>
                    </div>
                    <div class="mb-4">
                        <label>Descripción:</label>
                        <input type="text" name="descripcion" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" required>
                    </div>
                    <div class="mb-4">
                        <label>Tipo:</label>
                        <select name="tipo" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" required>
                            <option value="ingreso">Ingreso</option>
                            <option value="egreso">Egreso</option>
                        </select>
                    </div>
                    <div class="mb-4">
                        <label>Monto:</label>
                        <input type="number" name="monto" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" step="0.01" required>
                    </div>
                    <button type="submit" class="bg-green-600 hover:bg-green-800 text-white font-bold py-2 px-4 rounded">Guardar Cambios</button>&nbsp;
                    <button type="button" onclick="location.href='{{ route('caja.index') }}'" class="bg-red-500 hover:bg-red-800 text-white font-bold py-2 px-4 rounded">Cancelar</button>
                </form>
            </div>
        </div>
    </div>
</x-admin-layout>