<style>
    .dt-search,
    .dt-info,
    .dt-paging {
        display: none
    }
</style>
<x-admin-layout>
    <div class="py-4">
        <div class="max-w-7xl mx-auto">
            <div class="flex items-center justify-between mb-4 bg-white p-6 rounded-lg">
                @if(Auth::user()->id_tipo_usuario <= 2)
                    <a href="{{ route('clientes.index') }}" class="text-gray-700 hover:text-gray-500">
                    <i class="fas fa-circle-left fa-2x">&nbsp;{{ $cliente->nombres }} {{ $cliente->paterno }} {{ $cliente->materno }}</i>
                    <br><small>{{$cliente->plan->nombre}}</small>
                    </a>
                    @else
                    <i class="fas fa-user fa-2x">&nbsp;{{ $cliente->nombres }} {{ $cliente->paterno }} {{ $cliente->materno }}</i>
                    <br><small>{{$cliente->plan->nombre}}</small>
                    @endif
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 md:gap-6 mb-8">
                <a href="{{ route('clientes.edit',$cliente->slug) }}" class="hover:text-gray-500">
                    <div class="bg-white p-6 rounded-lg shadow text-center border-t-4 border-black hover:bg-gray-500 hover:border-gray-500 transition duration-300">
                        <!-- i class="fa-solid fa-user-pen fa-10x"></i-->
                        <div class="flex justify-center">
                            <img src="/iconos/editar.png">
                        </div>
                        <div class="text-gray-700 font-semibold ">&nbsp;</div>
                        <div class="text-gray-700 font-semibold  text-4xl">Modificar</div>
                    </div>
                </a>
                <a href="{{ route('clientes.evolucion_ejercicios', $cliente->slug) }}" class="hover:text-gray-500">
                    <div class="bg-white p-6 rounded-lg shadow text-center border-t-4 border-black hover:bg-gray-500 hover:border-gray-500 transition duration-300">
                        <div class="flex justify-center">
                            <img src="/iconos/evolucion_ejercicios.png">
                        </div>
                        <div class="text-gray-700 font-semibold ">&nbsp;</div>
                        <div class="text-gray-700 font-semibold text-4xl">Evolución Entrenamiento</div>
                    </div>
                </a>

                <a href="{{ route('clientes.agenda', $cliente->slug) }}" class="hover:text-gray-500">
                    <div class="bg-white p-6 rounded-lg shadow text-center border-t-4 border-black hover:bg-gray-500 hover:border-gray-500 transition duration-300">
                        <div class="flex justify-center">
                            <img src="/iconos/agenda.png">
                        </div>
                        <div class="text-gray-700 font-semibold ">&nbsp;</div>
                        <div class="text-gray-700 font-semibold text-4xl">Agenda</div>
                    </div>
                </a>
                @if(Auth::user()->id_tipo_usuario>2)
                <a href="{{ route('encuestas.create',$slug) }}" class="hover:text-gray-500"">
    				<div class=" bg-white p-6 rounded-lg shadow text-center border-t-4 border-black hover:bg-gray-500 hover:border-gray-500 transition duration-300">
                    <!-- i class=" fas fa-users fa-6x text-black mb-4"></i -->
                    <div class="flex justify-center">
                        <img src="/iconos/evaluacion.png">
                    </div>
                    <div class="text-gray-700 font-semibold">&nbsp;</div>
                    <div class="text-gray-700 font-semibold text-4xl">Evalúa a tu Entrenador</div>
            </div>
            </a>
            <a href="{{ route('survey.show',$cliente->slug) }}" class="hover:text-gray-500"">
    				<div class=" bg-white p-6 rounded-lg shadow text-center border-t-4 border-black hover:bg-gray-500 hover:border-gray-500 transition duration-300">
                <!-- i class=" fas fa-users fa-6x text-black mb-4"></i -->
                <div class="flex justify-center">
                    <img src="/iconos/satisfaccion2.png">
                </div>
                <div class="text-gray-700 font-semibold">&nbsp;</div>
                <div class="text-gray-700 font-semibold text-4xl">Evalúa al Gimnasio</div>
        </div>
        </a>
        <a href="{{ route('mensajes.index') }}" class="hover:text-gray-500"">
    				<div class=" bg-white p-6 rounded-lg shadow text-center border-t-4 border-black hover:bg-gray-500 hover:border-gray-500 transition duration-300">
            <!-- i class=" fas fa-users fa-6x text-black mb-4"></i -->
            <div class="flex justify-center">
                <img src="/iconos/mensajes.png">
            </div>
            <div class="text-gray-700 font-semibold">&nbsp;</div>
            <div class="text-gray-700 font-semibold text-4xl">Mensajería</div>
    </div>
    </a>
    @endif
    <a href="{{ route('agendas.cliente_por_mes', $cliente->slug) }}" class="hover:text-gray-500">
        <div class="bg-white p-6 rounded-lg shadow text-center border-t-4 border-black hover:bg-gray-500 hover:border-gray-500 transition duration-300">
            <div class="flex justify-center">
                <table class="w-full border tabla_datos">
                    <thead>
                        <tr style="height:50px;">
                            <th class="text-center ">Estado</th>
                            <th class="text-center ">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if($entrenamientos_estado)
                        @foreach($entrenamientos_estado as $e)
                        @if($e->estado !== '')
                        <tr style="border:1px solid #d2d2d2;">
                            <td class="" style="text-align:left;padding-left:5px;background-color:{{$e->color}};color:{{$e->texto}}">{{$e->estado}}</td>
                            <td class="text-left">{{$e->total}}</td>
                        </tr>
                        @endif
                        @endforeach
                        @else
                        <tr style="border:1px solid #d2d2d2;">
                            <td class="" colspan="2">No hay información</td>
                        </tr>
                        @endif
                    </tbody>
                </table>
            </div>
            <div class="text-gray-700 font-semibold mt-2">&nbsp;</div>
            <div class="text-gray-700 font-semibold mt-4 text-4xl">Entrenamientos por estado</div>
        </div>

        <!-- 
<div class="bg-white p-6 rounded-lg shadow text-center border-t-4 border-black hover:bg-gray-500 hover:border-gray-500 transition duration-300">
                     	 	<div class="flex justify-center">
                    			<img src="/iconos/agenda.png" >
                    		</div>
                            <div class="text-gray-700 font-semibold ">&nbsp;</div>
                            <div class="text-gray-700 font-semibold text-4xl">Agendas por Mes</div>
                    	</div>
 -->
    </a>
    <a @if($tieneParq> 0) href="{{ route('parq.show', $cliente->slug) }}" @else href="{{ route('parq.create', $cliente->slug) }}" @endif class="hover:text-gray-500">
        <div class="bg-white p-6 rounded-lg shadow text-center border-t-4 border-black hover:bg-gray-500 hover:border-gray-500 transition duration-300">
            <div class="flex justify-center">
                <img src="/iconos/par-q.png">
            </div>
            <!--i class="fa-solid fa-clipboard-question fa-10x"></i-->
            <div class="text-gray-700 font-semibold mt-2 ">Par-Q</div>
            <div class="text-gray-700 font-semibold text-4xl">{!!$mensajeParQ!!}</div>
        </div>
    </a>
    <a @if($tieneFitPlan> 0) href="{{ route('fitplan.edit', $cliente->slug) }}" @else href="{{ route('fitplan.create', $cliente->slug) }}" @endif class="hover:text-gray-500">
        <div class="bg-white p-6 rounded-lg shadow text-center border-t-4 border-black hover:bg-gray-500 hover:border-gray-500 transition duration-300">
            <div class="flex justify-center">
                <img src="/iconos/evolucion.png">
            </div>
            <!--i class="fa-solid fa-clipboard-question fa-10x"></i-->
            <div class="text-gray-700 font-semibold mt-2 ">Fit Plan Evolution</div>
            <div class="text-gray-700 font-semibold text-4xl">{!!$mensajeFitPlan!!}</div>
        </div>
    </a>
    @if(Auth::user()->id_tipo_usuario <= 2)
        <a href="{{ route('clientes.evaluacion_inicial.show', $cliente->slug) }}" class="hover:text-gray-500">
        <div class="bg-white p-6 rounded-lg shadow text-center border-t-4 border-black hover:bg-gray-500 hover:border-gray-500 transition duration-300">
            <div class="flex justify-center">
                <img src="/iconos/evaluacion.png">
            </div>
            <div class="text-gray-700 font-semibold mt-2 ">Evaluación Inicial</div>
            <div class="text-gray-700 font-semibold text-4xl">{!! $mensajeEvaluacionInicial !!}</div>
        </div>
        </a>
        @endif
        <a href="{{ route('clientes.cuenta_corriente', $cliente->slug) }}" class="hover:text-gray-500">
            <div class="bg-white p-6 rounded-lg shadow text-center border-t-4 border-black hover:bg-gray-500 hover:border-gray-500 transition duration-300">
                <div class="flex justify-center">
                    <img src="/iconos/billete.png">
                </div>
                <!-- i class="fa-solid fa-money-bill-wave fa-10x"></i-->
                <div class="text-gray-700 font-semibold mt-2 ">Cuenta Corriente</div>
                <div class="text-gray-700 font-semibold text-4xl">{!!$estado!!}</div>
            </div>
        </a>
        <a href="{{ route('clientes.pesos', $cliente->slug) }}" class="hover:text-gray-500">
            <div class="bg-white p-6 rounded-lg shadow text-center border-t-4 border-black hover:bg-gray-500 hover:border-gray-500 transition duration-300">
                <div class="flex justify-center">
                    <img src="/iconos/balanza.png">
                </div>
                <!-- i class="fa-solid fa-weight-scale fa-10x"></i -->
                <div class="text-gray-700 font-semibold mt-2 ">Peso</div>
                <div class="text-gray-700 font-semibold mt-4 text-4xl">{{ $pesoReciente->peso}} Kg</div>

            </div>
        </a>
        <a href="{{ route('clientes.imcs', $cliente->slug) }}" class="hover:text-gray-500">
            <div class="bg-white p-6 rounded-lg shadow text-center border-t-4 border-black hover:bg-gray-500 hover:border-gray-500 transition duration-300">
                <div class="flex justify-center">
                    <img src="/iconos/imc.png">
                </div>
                <!--i class="fa-solid fa-gauge-simple-high fa-10x"></i -->
                <div class="text-gray-700 font-semibold mt-2 ">IMC</div>
                <div class="text-gray-700 font-semibold mt-4 text-4xl">{{ $imcReciente->imc}}</div>
            </div>
        </a>
        <a href="{{ route('clientes.perimetros', $cliente->slug) }}" class="hover:text-gray-500">
            <div class="bg-white p-6 rounded-lg shadow text-center border-t-4 border-black hover:bg-gray-500 hover:border-gray-500 transition duration-300">
                <div class="flex justify-center">
                    <img src="/iconos/perimetros.png">
                </div>
                <!--i class="fa-solid fa-droplet fa-10x"></i-->
                <div class="text-gray-700 font-semibold ">Perímetros</div>
            </div>
        </a>
        <!-- 
<a href="{{ route('clientes.aguas', $cliente->slug) }}" class="hover:text-gray-500">
    				<div class="bg-white p-6 rounded-lg shadow text-center border-t-4 border-black hover:bg-gray-500 hover:border-gray-500 transition duration-300">
                        <div class="flex justify-center">
                			<img src="/iconos/agua.png" >
                		</div>
                        <!~~i class="fa-solid fa-droplet fa-10x"></i~~>
                        <div class="text-gray-700 font-semibold ">% Agua</div>
                        <div class="text-gray-700 font-semibold mt-4 text-4xl">{{ $aguaReciente->valor}} %</div>
                	</div>
                </a>
 -->
        <a href="{{ route('clientes.grasas', $cliente->slug) }}" class="hover:text-gray-500">
            <div class="bg-white p-6 rounded-lg shadow text-center border-t-4 border-black hover:bg-gray-500 hover:border-gray-500 transition duration-300">
                <div class="flex justify-center">
                    <img src="/iconos/porcentaje_grasa.png">
                </div>
                <!--span class="fa-stack fa-2x" style="font-size: 5rem;">
                            <i class="fa-solid fa-user fa-stack-2x text-black"></i>
                            <i class="fa-solid fa-percent fa-stack-1x text-white" style="font-size:2.5rem;margin-top:40px;"></i>
                        </span-->
                <div class="text-gray-700 font-semibold mt-2 ">% Grasa Corporal</div>
                <div class="text-gray-700 font-semibold mt-4 text-4xl">{{ $grasaReciente->valor}} %</div>
            </div>
        </a>
        <a href="{{ route('clientes.poseas', $cliente->slug) }}" class="hover:text-gray-500">
            <div class="bg-white p-6 rounded-lg shadow text-center border-t-4 border-black hover:bg-gray-500 hover:border-gray-500 transition duration-300">
                <!-- i class="fa-solid fa-bone fa-10x"></i -->
                <div class="flex justify-center">
                    <img src="/iconos/porcentaje_osea.png">
                </div>
                <div class="text-gray-700 font-semibold mt-2 ">% Masa Ósea</div>
                <div class="text-gray-700 font-semibold mt-4 text-4xl">{{ $poseaReciente->valor}} %</div>
            </div>
        </a>
        <a href="{{ route('clientes.pmusculares', $cliente->slug) }}" class="hover:text-gray-500">
            <div class="bg-white p-6 rounded-lg shadow text-center border-t-4 border-black hover:bg-gray-500 hover:border-gray-500 transition duration-300">
                <div class="flex justify-center">
                    <img src="/iconos/porcentaje_musculos.png">
                </div>
                <!--span class="inline-block w-52 h-52">
                                 <i class="fa-solid fa-percent fa-stack-1x text-white" style="font-size:2.5rem;margin-top:-80px;margin-left: 430px;"></i>
                        </span-->
                <div class="text-gray-700 font-semibold mt-2 ">% Masa Muscular</div>
                <div class="text-gray-700 font-semibold mt-4 text-4xl">{{ $pmuscularReciente->valor}} %</div>
            </div>
        </a>

        <!--a href="{{ route('clientes.cuenta_corriente', $cliente->slug) }}" class="hover:text-gray-500">
    				<div class="bg-white p-6 rounded-lg shadow text-center border-t-4 border-black hover:bg-gray-500 hover:border-gray-500 transition duration-300">
                        <div class="flex justify-center">
                			<img src="/iconos/masa_osea.png" >
                		</div>
                        <div class="text-gray-700 font-semibold ">Masa Ósea</div>
                	</div>
                </a>
                <a href="{{ route('clientes.cuenta_corriente', $cliente->slug) }}" class="hover:text-gray-500">
    				<div class="bg-white p-6 rounded-lg shadow text-center border-t-4 border-black hover:bg-gray-500 hover:border-gray-500 transition duration-300">
                        <div class="flex justify-center">
                			<img src="/iconos/masa_muscular.png" >
                		</div>
                        
                                <div class="text-gray-700 font-semibold ">Masa Muscular</div>

                	</div>
                </a-->
        <a href="{{ route('clientes.reporte', $cliente->slug) }}" class="hover:text-gray-500">
            <div class="bg-white p-6 rounded-lg shadow text-center border-t-4 border-black hover:bg-gray-500 hover:border-gray-500 transition duration-300">
                <div class="flex justify-center">
                    <img src="/iconos/reporte.png">
                </div>
                <!--span class="fa-stack fa-2x" style="font-size: 5rem;">
                            i class="fa-solid fa-chart-line fa-10x"></i-->
                <div class="text-gray-700 font-semibold ">&nbsp;</div>
                <div class="text-gray-700 font-semibold mt-4 text-4xl">Reporte</div>
            </div>
        </a>
        </div>
        </div>
        </div>
</x-admin-layout>