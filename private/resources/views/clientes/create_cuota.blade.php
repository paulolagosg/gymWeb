<x-admin-layout>
    <div class="py-4">
        <div class="">
            <div class="flex items-center justify-between mb-4 bg-white p-6 rounded-lg text-center">
                <a href="{{ route('clientes.cuenta_corriente', $cliente->slug) }}" class="text-gray-700 hover:text-gray-500">
                    <i class="fas fa-circle-left fa-2x">&nbsp;{{ $cliente->nombres }} {{ $cliente->paterno }} {{ $cliente->materno }}</i>
                </a>
            </div>
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 overflow-x-auto w-full">
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

                    <h2 class="text-xl font-bold mb-4">Agregar Cuota(s)</h2>
                    <form action="{{ route('clientes.cuotas.store', $cliente->slug) }}" method="POST">
                        @csrf
                        <div class="mb-4">
                            <label for="cantidad" class="block text-sm font-medium text-gray-700">Cantidad de Cuotas</label>
                            <select name="cantidad" id="cantidad" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" required>
                                @for($i=1;$i<=12;$i++)
                                    <option value="{{ $i }}" {{ old('cantidad') == $i ? 'selected' : '' }}>{{ $i }}</option>
                                    @endfor
                            </select>
                        </div>
                        <div class="mb-4">
                            <label for="fecha_vencimiento" class="block text-sm font-medium text-gray-700">Fecha Primer Vencimiento</label>
                            <input type="date" name="fecha_vencimiento" id="fecha_vencimiento" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" required>
                        </div>
                        <div class="mb-4">
                            <label for="monto" class="block text-sm font-medium text-gray-700">Monto</label>
                            <input type="number" name="monto" id="monto" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" required>
                        </div>
                        <div class="mb-4">
                            <label for="descuento" class="block text-sm font-medium text-gray-700">Descuento ($)</label>
                            <input type="number" name="descuento" id="descuento" step="0.01" min="0" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" value="{{ old('descuento', 0) }}">
                        </div>
                        <div class="flex justify-start">
                            <button type="submit" class="bg-green-600 hover:bg-green-800 text-white font-bold py-2 px-4 rounded">
                                Crear Cuota(s)
                            </button>
                            <button type="button" onclick="location.href='{{ route('clientes.cuenta_corriente', $cliente->slug) }}'" class="bg-red-500 hover:bg-red-800 text-white font-bold py-2 px-4 rounded ml-2">
                                Cancelar
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-admin-layout>