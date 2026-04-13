<x-admin-layout>
    <div class="py-4">
        <div class="max-w-7xl mx-auto">
            <div class="flex items-center justify-between mb-4 bg-white p-6 rounded-lg text-center">
            @if(Auth::user()->id_tipo_usuario <= 2)
                <a href="{{ route('entrenadores.index') }}" class="text-gray-700 hover:text-gray-500">
                    <i class="fas fa-circle-left fa-2x">&nbsp;{{ $entrenador->name }}</i>
                </a>
                @else
                    <i class="fas fa-user fa-2x">&nbsp;{{ $entrenador->name }}</i>
            @endif
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 md:gap-6 mb-8">
             		<div class="bg-white p-6 rounded-lg shadow text-center border-t-4 border-black ">
                        <!-- i class="fa-solid fa-user-pen fa-10x"></i-->
                        <div class="justify-center">
                			<table class="w-full">
                        		<tr class="bg-gray-100 p-4"><td class="text-left">Título Profesional u Otro</td><td>&nbsp;</td><th class="text-left font-bold"">{{ $titulo }}</th></tr>
                        		<tr><td class="text-left">Categoría</td><td>&nbsp;</td><th class="text-left font-bold"">{{ $categoria }}</th></tr>
                        		<tr class="bg-gray-100 p-4"><td class="text-left">Evaluación General</td><td>&nbsp;</td><th class="text-left font-bold"">@if($evaluacion) {{ $evaluacion->total }}% @else Sin información @endif</th></tr>
                        		<tr><td class="text-left">Clientes Activos</td><td>&nbsp;</td><th class="text-left font-bold"">{{ $clientes_activos }}</th></tr>
                        		<tr class="bg-gray-100 p-4"><td class="text-left">Tasa Retención</td><td>&nbsp;</td><th class="text-left font-bold"">{{ $retencion['tasa_retencion'] }}%</th></tr>
                    		</table>
                    	</div>
                    	@if($resumen)
                    	<div class=" justify-left">
                    	<br>
                    	<h2>Evaluación clientes</h2>
                    		<table class="tabla w-full">
                                <!--thead>
                                    <tr class="bg-gray-100 p-2">
                                        <th>Item</th>
                                        <th colspan="2" class="text-center">Calificación</th>
                                    </tr>
                                </thead-->
                                <tbody>
                                    @php
                                    $fila = 0;
                                    @endphp
                                    @foreach(['profesionalismo','claridad','motivacion','disponibilidad','puntualidad'] as $campo)
                                    @php
                                    $fila++;
                                    if($fila % 2 == 0) {
                                    echo '<tr class="bg-gray-100 p-4">';
                                        } else {
                                        echo '
                                    <tr>';
                                        }
                                        @endphp
                                        <td class="p-2 text-left text-xs">
                                            <span class="capitalize">{{ ucfirst(str_replace('motivacion','motivación',str_replace('_',' ',$campo))) }}</span>
                                        </td>
                                        <!-- td class="p-2 text-xs" style="text-align: center;">
                                            <span>{{ $resumen[$campo] }}/5</span>
                                        </td-->
                                        <td class="p-2 text-xs" style="text-align: center;">
                                            {{-- Estrellas --}}
                                            <span>
                                                @for($i = 1; $i <= 5; $i++)
                                                    @if($resumen[$campo]>= $i)
                                                    <i class="fa fa-star fa-xs" style="color:#EFB810"></i>
                                                    @elseif($resumen[$campo] > $i-1)
                                                    <i class="fa fa-star-half-alt fa-xs" style="color:#EFB810"></i>
                                                    @else
                                                    <i class="fa fa-star text-gray-300"></i>
                                                    @endif
                                                    @endfor
                                            </span>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                                @php
                                $valoracion = $resumen['valoracion_global'] ?? null;
                                $estrellas = $valoracion ? round($valoracion / 2, 1) : 0; // Escala de 0 a 5
                                @endphp
                                <tfoot>
                                    <tr class="bg-gray-100">
                                        @if($valoracion)
                                        <td class="p-2 text-xs text-left"><span>Cuánto recomendarías a tu entrenador/a:</td>
                                        <!-- td class="p-2 text-xs" style="text-align: center;"> {{ $valoracion }}/10</span></td -->
                                        <td class="p-2 text-xs" style="text-align: center;"><span>
                                                @for($i = 1; $i <= 5; $i++)
                                                    @if($estrellas>= $i)
                                                    <i class="fa fa-star fa-xs" style="color:#EFB810"></i>
                                                    @elseif($estrellas > $i-1)
                                                    <i class="fa fa-star-half-alt fa-xs" style="color:#EFB810"></i> @else
                                                    <i class="fa fa-star text-gray-300"></i>
                                                    @endif
                                                    @endfor
                                            </span>
                                        </td>
                                        @else
                                        <td colspan="3">
                                            <span class="text-gray-400">Sin respuestas</span>
                                        </td>
                                        @endif
                                    </tr>
                                </tfoot>
                            </table>
                		</div>
                		@endif
                        <div class="text-gray-700 font-semibold ">&nbsp;</div>
                        <div class="text-gray-700 font-semibold  text-4xl">Resumen</div>
                	</div>
            	<a href="{{ route('evaluaciones.index',$entrenador->slug) }}" class="hover:text-gray-500">
    				<div class="bg-white p-6 rounded-lg shadow text-center border-t-4 border-black hover:bg-gray-500 hover:border-gray-500 transition duration-300">
                        <!-- i class="fa-solid fa-user-pen fa-10x"></i-->
                        <div class="flex justify-center">
                			<img src="/iconos/ranking.png" >
                		</div>
                        <div class="text-gray-700 font-semibold ">&nbsp;</div>
                        <div class="text-gray-700 font-semibold  text-4xl">Categorizar</div>
                	</div>
                </a>
                <a href="{{ route('entrenadores.sesiones_semana',$entrenador->slug) }}" class="hover:text-gray-500">
    				<div class="bg-white p-6 rounded-lg shadow text-center border-t-4 border-black hover:bg-gray-500 hover:border-gray-500 transition duration-300">
                        <!-- i class="fa-solid fa-user-pen fa-10x"></i-->
                        <div class="flex justify-center">
                			<img src="/iconos/agenda.png" >
                		</div>
                        <div class="text-gray-700 font-semibold ">&nbsp;</div>
                        <div class="text-gray-700 font-semibold  text-4xl">Sesiones por Semana</div>
                	</div>
                </a>
                <a href="{{ route('entrenadores.clientes', $entrenador->slug) }}" class="hover:text-gray-500">
    				<div class="bg-white p-6 rounded-lg shadow text-center border-t-4 border-black hover:bg-gray-500 hover:border-gray-500 transition duration-300">
                 	 	<div class="flex justify-center">
                			<img src="/iconos/clientes.png" >
                		</div>
                        <div class="text-gray-700 font-semibold ">&nbsp;</div>
                        <div class="text-gray-700 font-semibold text-4xl">Cantidad de Clientes</div>
                	</div>
                </a>                
                <a href="{{ route('entrenadores.retencion',$entrenador->slug) }}" class="hover:text-gray-500"">
    				<div class="bg-white p-6 rounded-lg shadow text-center border-t-4 border-black hover:bg-gray-500 hover:border-gray-500 transition duration-300">
                        <div class="flex justify-center">
                			<img src="/iconos/retencion.png" >
                		</div>
                        <div class="text-gray-700 font-semibold">&nbsp;</div>
                        <div class="text-gray-700 font-semibold text-4xl">Retención Mensual</div>
                	</div>
                </a>
                <a href="{{ route('encuestas.index',[$entrenador->slug,'pe']) }}" class="hover:text-gray-500"">
    				<div class="bg-white p-6 rounded-lg shadow text-center border-t-4 border-black hover:bg-gray-500 hover:border-gray-500 transition duration-300">
    					<div class="flex justify-center">
                			<img src="/iconos/satisfaccion.png" >
                 	 	</div>
                        <div class="text-gray-700 font-semibold mt-2">&nbsp;</div>
                        <div class="text-gray-700 font-semibold mt-4 text-4xl">Satisfacción Cliente</div>
                	</div>
                </a>
                <a href="{{ route('entrenadores.cursos',$entrenador->slug) }}" class="hover:text-gray-500"">
    				<div class="bg-white p-6 rounded-lg shadow text-center border-t-4 border-black hover:bg-gray-500 hover:border-gray-500 transition duration-300">
                 	 	<div class="flex justify-center">
                			<img src="/iconos/educacion.png" >
                		</div>
                        <div class="text-gray-700 font-semibold mt-2 ">&nbsp;</div>
                        <div class="text-gray-700 font-semibold mt-4 text-4xl">Formación Continua</div>
                	</div>
                </a>
                @if(Auth::user()->id_clasificacion == 3)
                <a href="{{ route('tareas.index') }}" class="hover:text-gray-500"">
    				<div class="bg-white p-6 rounded-lg shadow text-center border-t-4 border-black hover:bg-gray-500 hover:border-gray-500 transition duration-300">
                 	 	<div class="flex justify-center">
                			<img src="/iconos/tareas.png" >
                		</div>
                        <!--i class="fa-solid fa-clipboard-question fa-10x"></i-->
                        <div class="text-gray-700 font-semibold mt-2 ">&nbsp;</div>
                        <div class="text-gray-700 font-semibold mt-4 text-4xl">Tareas Semanales</div>
                	</div>
                </a>
				@else
				<a href="{{ route('tareas.index') }}" class="hover:text-gray-500"">
    				<div class="bg-white p-6 rounded-lg shadow text-center border-t-4 border-black hover:bg-gray-500 hover:border-gray-500 transition duration-300">
                 	 	<div class="flex justify-center">
                			<img src="/iconos/tareas.png" >
                		</div>
                        <!--i class="fa-solid fa-clipboard-question fa-10x"></i-->
                        <div class="text-gray-700 font-semibold mt-2 ">&nbsp;</div>
                        <div class="text-gray-700 font-semibold mt-4 text-4xl">Tareas Semanales</div>
                	</div>
                </a>
				
				@endif
            </div>
        </div>
    </div>
</x-admin-layout>