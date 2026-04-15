<x-admin-layout>
    @php($clienteActual = auth()->user()?->cliente)
    <div class="py-4">
        <div class="">
            <div class="flex items-center justify-between mb-4 bg-white p-6 rounded-lg">
                @if($clienteActual)
                <div class="text-gray-700">
                    <i class="fas fa-user fa-2x">&nbsp;{{ $clienteActual->nombres }} {{ $clienteActual->paterno }} {{ $clienteActual->materno }}</i>
                    <br><small>{{ $clienteActual->plan->nombre ?? 'Sin plan' }}</small>
                </div>
                @else
                <a href="{{ route('mensajes.index') }}" class="text-gray-700 hover:text-gray-500">
                    <i class="fas fa-circle-left fa-2x">&nbsp;</i>
                </a>
                @endif
            </div>
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-4">
                <h2 class="text-2xl font-bold mb-4">Nuevo mensaje</h2>
                <form method="POST" action="{{ route('mensajes.store') }}" enctype="multipart/form-data">
                    @csrf
                    <select name="para_id" required class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                        @foreach($usuarios as $usuario)
                        <option value="{{ $usuario->id }}">{{ $usuario->name }}</option>
                        @endforeach
                    </select>
                    <textarea name="contenido" required class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"></textarea>
                    <input type="file" name="archivos[]" multiple class="mt-1 block w-full  rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                    <button type="submit" class="bg-green-600 hover:bg-green-800 text-white font-bold py-2 px-4 rounded">Enviar</button>
                    <button type="button" class="bg-red-500 hover:bg-red-800 text-white font-bold py-2 px-4 rounded ml-2" onclick="window.location.href='{{route('mensajes.index')}}'">Cancelar</button>
                </form>
                <!-- div class="mt-4">
                    <a href="{{ route('mensajes.index') }}" class="bg-gray-700 hover:bg-gray-500 text-white font-bold py-2 px-4 rounded">Volver a Bandeja de Entrada</a>
                </div -->
            </div>
        </div>
    </div>
</x-admin-layout>