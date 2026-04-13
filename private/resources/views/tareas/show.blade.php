<x-admin-layout>
    <div class="py-4">
        <div class="">
            <div class="flex items-center justify-between mb-4">
                <a href="{{ route('tareas.index') }}" class="hover:text-gray-500">
                    <i class="fas fa-circle-left fa-2x">&nbsp;</i>
                </a>
            </div>
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <h2 class="text-2xl font-bold p-4">Tarea</h2>
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

                    <div class="mt-4">
                        <strong>Entrenador:</strong> {{ $tarea->usuario->name }}
                    </div>
                    <div class="mt-4">
                        <strong>Nombre de la Tarea:</strong> {{ $tarea->nombre }}
                    </div>
                    <div class="mt-4">
                        <strong>Descripción:</strong> {{ $tarea->descripcion }}
                    </div>
                    <div class="mt-4">
                        <strong>Fecha Límite:</strong> {{ $tarea->fecha_limite }}
                    </div>
                    <div class="mt-4">
                        <strong>Estado:</strong> {{ $tarea->completada ? 'Completada' : 'Pendiente' }}
                    </div>
                    <div class="mt-6">
                        @if(auth()->user()->is_clasificacion == 3)
                        <button class="bg-gray-700 hover:bg-gray-500 text-white font-bold py-2 px-4 rounded" onclick="window.location.href='{{ route('tareas.edit', $tarea->slug) }}'">
                            Modificar
                        </button>
                        <form action="{{ route('tareas.destroy', $tarea->slug) }}" method="POST" style="display:inline;">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="bg-gray-700 hover:bg-gray-500 text-white font-bold py-2 px-4 rounded">
                                Eliminar
                            </button>
                        </form>
                        @else
                        <button class="bg-gray-700 hover:bg-gray-500 text-white font-bold py-2 px-4 rounded" onclick="window.location.href='{{ route('tareas.index') }}'">
                            Volver
                        </button>
                        @if(!$tarea->completada)
                        <form action="{{ route('tareas.completar', $tarea->slug) }}" method="POST" style="display:inline;">
                            @csrf
                            <button type="submit" class="bg-gray-700 hover:bg-gray-500 text-white font-bold py-2 px-4 rounded mr-2">
                                Marcar como Completada
                            </button>
                        </form>
                        @endif

                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-admin-layout>