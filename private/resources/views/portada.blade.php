<x-admin-layout>
    <div class="py-4">
        <div class="max-w-7xl mx-auto">
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 md:gap-6 mb-8">
                <a href="{{ route('dashboard') }}" class="hover:text-gray-500"">
    				<div class=" bg-white p-6 rounded-lg shadow text-center border-t-4 border-black hover:bg-gray-500 hover:border-gray-500 transition duration-300">
                    <div class="flex justify-center">
                        <img src="iconos/dashboard.png">
                    </div>
                    <div class="text-gray-700 font-semibold mt-2 ">&nbsp;</div>
                    <div class="text-gray-700 font-semibold text-4xl">Panel de Control</div>
            </div>
            </a>
            @if(Auth::user()->id_tipo_usuario == 2)
            <a href="{{ route('evaluaciones.index',$slug) }}" class="hover:text-gray-500">
                <div class="bg-white p-6 rounded-lg shadow text-center border-t-4 border-black hover:bg-gray-500 hover:border-gray-500 transition duration-300">
                    <!-- i class="fa-solid fa-user-pen fa-10x"></i-->
                    <div class="flex justify-center">
                        <img src="/iconos/ranking.png">
                    </div>
                    <div class="text-gray-700 font-semibold ">&nbsp;</div>
                    <div class="text-gray-700 font-semibold  text-4xl">Categorización</div>
                </div>
            </a>
            @endif
            <a href="{{ route('agendas.index') }}">
                <div class="bg-white p-6 rounded-lg shadow text-center border-t-4 border-black hover:bg-gray-500 hover:border-white transition duration-300">
                    <div class="flex justify-center">
                        <img src="iconos/agenda.png" class="hover:opacity-80">
                    </div>
                    <div class="text-gray-700 font-semibold mt-2 ">&nbsp;</div>
                    <div class="text-gray-700 font-semibold text-4xl">Agenda</div>
                </div>
            </a>
            <a href="{{ route('clientes.index') }}" class="hover:text-gray-500"">
    				<div class=" bg-white p-6 rounded-lg shadow text-center border-t-4 border-black hover:bg-gray-500 hover:border-gray-500 transition duration-300">
                <!-- i class=" fas fa-users fa-6x text-black mb-4"></i -->
                <div class="flex justify-center">
                    <img src="iconos/clientes.png">
                </div>
                <div class="text-gray-700 font-semibold mt-2 ">&nbsp;</div>
                <div class="text-gray-700 font-semibold text-4xl">Clientes</div>
        </div>
        </a>
        <a href="{{ route('mensajes.index') }}" class="hover:text-gray-500"">
    				<div class=" bg-white p-6 rounded-lg shadow text-center border-t-4 border-black hover:bg-gray-500 hover:border-gray-500 transition duration-300">
            <!-- i class=" fas fa-users fa-6x text-black mb-4"></i -->
            <div class="flex justify-center">
                <img src="iconos/mensajes.png">
            </div>
            <div class="text-gray-700 font-semibold mt-2 ">&nbsp;</div>
            <div class="text-gray-700 font-semibold text-4xl">Mensajería</div>
    </div>
    </a>
    @if(auth()->user()->id_tipo_usuario == 2)
    <a href="{{ route('encuestas.index',[$slug,'p']) }}" class="hover:text-gray-500"">
    				<div class=" bg-white p-6 rounded-lg shadow text-center border-t-4 border-black hover:bg-gray-500 hover:border-gray-500 transition duration-300">
        <div class="flex justify-center">
            <img src="/iconos/satisfaccion.png">
        </div>
        <div class="text-gray-700 font-semibold mt-2">&nbsp;</div>
        <div class="text-gray-700 font-semibold mt-4 text-4xl">Satisfacción Cliente</div>
        </div>
    </a>
    @endif
    @if(Auth::user()->id_clasificacion == 3)
    <a href="{{ route('cursos.index') }}" class="hover:text-gray-500"">
    				<div class=" bg-white p-6 rounded-lg shadow text-center border-t-4 border-black hover:bg-gray-500 hover:border-gray-500 transition duration-300">
        <div class="flex justify-center">
            <img src="/iconos/educacion.png">
        </div>
        <div class="text-gray-700 font-semibold mt-2 ">&nbsp;</div>
        <div class="text-gray-700 font-semibold text-4xl">Formación Continua</div>
        </div>
    </a>
    <a href="{{ route('entrenadores.index') }}" class="hover:text-gray-500"">
    				<div class=" bg-white p-6 rounded-lg shadow text-center border-t-4 border-black hover:bg-gray-500 hover:border-gray-500 transition duration-300">
        <!-- i class=" fas fa-users fa-6x text-black mb-4"></i -->
        <div class="flex justify-center">
            <img src="iconos/supervisor.png">
        </div>
        <div class="text-gray-700 font-semibold mt-2 ">&nbsp;</div>
        <div class="text-gray-700 font-semibold text-4xl">Supervisión</div>
        </div>
    </a>
    @else
    <a href="{{ route('cursos.index') }}" class="hover:text-gray-500"">
    				<div class=" bg-white p-6 rounded-lg shadow text-center border-t-4 border-black hover:bg-gray-500 hover:border-gray-500 transition duration-300">
        <div class="flex justify-center">
            <img src="/iconos/educacion.png">
        </div>
        <div class="text-gray-700 font-semibold mt-2 ">&nbsp;</div>
        <div class="text-gray-700 font-semibold text-4xl">Formación Continua</div>
        </div>
    </a>
    <a href="{{ route('tareas.index') }}" class="hover:text-gray-500"">
    				<div class=" bg-white p-6 rounded-lg shadow text-center border-t-4 border-black hover:bg-gray-500 hover:border-gray-500 transition duration-300">
        <!-- i class=" fas fa-users fa-6x text-black mb-4"></i -->
        <div class="flex justify-center">
            <img src="/iconos/tareas.png">
        </div>
        <div class="text-gray-700 font-semibold mt-2 ">&nbsp;</div>
        <div class="text-gray-700 font-semibold text-4xl">Tareas semanales</div>
        </div>
    </a>
    @endif
    <a href="{{ route('ejercicios.index') }}" class="hover:text-gray-500"">
    				<div class=" bg-white p-6 rounded-lg shadow text-center border-t-4 border-black hover:bg-gray-500 hover:border-gray-500 transition duration-300">
        <!--i class=" fas fa-dumbbell fa-6x text-black mb-4"></i-->
        <div class="flex justify-center">
            <img src="iconos/ejercicios.png">
        </div>
        <div class="text-gray-700 font-semibold mt-2 ">&nbsp;</div>
        <div class="text-gray-700 font-semibold text-4xl">Ejercicios</div>
        </div>
    </a>
    @if(auth()->user()->id_tipo_usuario == 1)
    <a href="{{ route('pagos_entrenadores.index') }}" class="hover:text-gray-500"">
    				<div class=" bg-white p-6 rounded-lg shadow text-center border-t-4 border-black hover:bg-gray-500 hover:border-gray-500 transition duration-300">
        <!--i class=" fas fa-money-bill-trend-up fa-6x text-black mb-4"></i-->
        <div class="flex justify-center">
            <img src="iconos/pagos.png">
        </div>
        <div class="text-gray-700 font-semibold mt-2 ">&nbsp;</div>
        <div class="text-gray-700 font-semibold text-4xl">Pagos Entrenadores</div>
        </div>
    </a>
    <a href="{{ route('caja.index') }}" class="hover:text-gray-500"">
    				<div class=" bg-white p-6 rounded-lg shadow text-center border-t-4 border-black hover:bg-gray-500 hover:border-gray-500 transition duration-300">
        <!--i class=" fas fa-money-bill-trend-up fa-6x text-black mb-4"></i-->
        <div class="flex justify-center">
            <img src="iconos/cuenta_corriente.png">
        </div>
        <div class="text-gray-700 font-semibold mt-2 ">&nbsp;</div>
        <div class="text-gray-700 font-semibold text-4xl">Cuenta Corriente</div>
        </div>
    </a>
    <a href="{{ route('planes.index') }}" class="hover:text-gray-500"">
    				<div class=" bg-white p-6 rounded-lg shadow text-center border-t-4 border-black hover:bg-gray-500 hover:border-gray-500 transition duration-300">
        <div class="flex justify-center">
            <img src="iconos/planes.png">
        </div>
        <div class="text-gray-700 font-semibold mt-2 ">&nbsp;</div>
        <div class="text-gray-700 font-semibold text-4xl">Planes</div>
        </div>
    </a>
    <a href="{{ route('evaluacion-inicial.catalogo') }}" class="hover:text-gray-500"">
					<div class=" bg-white p-6 rounded-lg shadow text-center border-t-4 border-black hover:bg-gray-500 hover:border-gray-500 transition duration-300">
        <div class="flex justify-center">
            <img src="iconos/evaluacion.png">
        </div>
        <div class="text-gray-700 font-semibold mt-2 ">&nbsp;</div>
        <div class="text-gray-700 font-semibold text-4xl">Evaluación Inicial</div>
        </div>
    </a>
    <a href="{{ route('usuarios.index') }}" class="hover:text-gray-500"">
    				<div class=" bg-white p-6 rounded-lg shadow text-center border-t-4 border-black hover:bg-gray-500 hover:border-gray-500 transition duration-300">
        <div class="flex justify-center">
            <img src="iconos/usuarios.png">
        </div>
        <div class="text-gray-700 font-semibold mt-2 ">&nbsp;</div>
        <div class="text-gray-700 font-semibold text-4xl">Usuarios</div>
        </div>
    </a>
    @endif
    </div>
    </div>
    </div>
</x-admin-layout>