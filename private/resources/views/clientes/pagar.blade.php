<x-admin-layout>
    <div class="py-4">
        <div class="">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 overflow-x-auto w-full">
                    <h2 class="text-xl font-bold mb-4">Pagar Cuota del {{\Carbon\Carbon::parse($datos->fecha_vencimiento)->format('d/m/Y')}} de {{ $datos->nombres }} {{ $datos->paterno }}</h2>
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

                    <form action="{{ route('clientes.cuenta_corriente.pagar.store', $datos->id) }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="mb-4">
                            <label for="monto" class="block text-sm font-medium text-gray-700">Monto a Pagar</label>
                            <input type="number" name="monto" id="monto" value="{{$datos->monto_pagar - $datos->monto_pagado}}" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" required>
                            <input type="hidden" name="monto_pagar" id="monto_pagar" value="{{$datos->monto_pagar}}">
                        </div>
                        <div class="mb-4">
                            <label for="fecha_pago" class="block text-sm font-medium text-gray-700">Fecha de Pago</label>
                            <input type="date" value="{{ \Carbon\Carbon::now()->format('Y-m-d') }}" name="fecha_pago" id="fecha_pago" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" required>
                        </div>
                        <div class="mb-4">
                            <label for="forma_pago" class="block text-sm font-medium text-gray-700">Forma de Pago</label>
                            <select name="id_forma_pago" id="id_forma_pago" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" required>
                                <option value="">Seleccione una forma de pago</option>
                                @foreach($formas_pagos as $forma)
                                <option value="{{ $forma->id }}">{{ $forma->nombre }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-4">
                            <label for="comrobante" class="block text-sm font-medium text-gray-700">Comprobante</label>
                            <input type="file" name="comprobante">
                        </div>
                        <div class="flex justify-start">
                            <button type="submit" class="bg-green-600 hover:bg-green-800 text-white font-bold py-2 px-4 rounded">
                                Pagar
                            </button>
                            <button type="button" onclick="location.href='{{ route('clientes.cuenta_corriente', $datos->slug) }}'" class="bg-red-500 hover:bg-red-800 text-white font-bold py-2 px-4 rounded ml-2">
                                Cancelar
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    </div>
</x-admin-layout>