<x-admin-layout>
    <div class="py-4">
        <div class="">
            <div class="flex items-center justify-between mb-4 bg-white p-6 rounded-lg text-center">
                <a href="{{ route('clientes.opciones.portada', $cliente->slug) }}" class="text-gray-700 hover:text-gray-500">
                    <i class="fas fa-circle-left fa-2x">&nbsp;{{ $cliente->nombres }} {{ $cliente->paterno }} {{ $cliente->materno }}</i>
                </a>
            </div>
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 overflow-x-auto w-full">
                    <table class="tabla_datos">
                        <thead class="table-dark">
                            <tr>
                                <th style="text-align: center;">Mes</th>
                                <th>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</th>
                                <th style="text-align: center;">Ejercicio</th>
                                <th style="text-align: center;">Tendencias<br />Series</th>
                                <th style="text-align: center;">Tendencias<br />Repeticiones</th>
                                <th style="text-align: center;">Tendencias<br />RIR</th>
                                <th style="text-align: center;">Tendencias<br />RPE</th>
                                <th style="text-align: center;">Tendencias<br />% 1RM</th>
                                <th style="text-align: center;">Total Series</th>
                                <th style="text-align: center;">Series<br />Promedio</th>
                                <th style="text-align: center;">Peso<br />Promedio</th>
                                <th style="text-align: center;">Peso<br />Mínimo</th>
                                <th style="text-align: center;">Peso<br />Máximo</th>
                                <th style="text-align: center;">RIR<br />Promedio</th>
                                <th style="text-align: center;">RIR<br />Mínimo</th>
                                <th style="text-align: center;">RIR<br />Máximo</th>
                                <th style="text-align: center;">RPE<br />Promedio</th>
                                <th style="text-align: center;">RPE<br />Mínimo</th>
                                <th style="text-align: center;">RPE<br />Máximo</th>
                                <th style="text-align: center;">% 1RM<br />Promedio</th>
                                <th style="text-align: center;">% 1RM<br />Mínimo</th>
                                <th style="text-align: center;">% 1RM<br />Máximo</th>
                            </tr>
                        </thead>
                        <tbody id="tbody-datos">
                            @foreach ($evolucion as $item)
                            <tr>
                                <td nowrap>{{ $item->mes }}</td>
                                <td style="width:100px !important">{!! $item->imagen !!}</td>
                                <td nowrap>{{ $item->ejercicio }}</td>
                                <td>{{ $item->tendencia_series }}</td>
                                <td>{{ $item->tendencia_repeticiones }}</td>
                                <td>{{ $item->tendencia_rir }}</td>
                                <td>{{ $item->tendencia_rpe }}</td>
                                <td>{{ $item->tendencia_rm }}</td>
                                <td>{{ $item->total_series }}</td>
                                <td>{{ $item->serie_promedio }}</td>
                                <td>{{ $item->repeticiones_promedio }}</td>
                                <td>{{ $item->repeticiones_maximas }}</td>
                                <td>{{ $item->repeticiones_minimas }}</td>
                                <td>{{ $item->rir_promedio }}</td>
                                <td>{{ $item->rir_minimo }}</td>
                                <td>{{ $item->rir_maximo }}</td>
                                <td>{{ $item->rpe_promedio }}</td>
                                <td>{{ $item->rpe_minimo }}</td>
                                <td>{{ $item->rpe_maximo }}</td>
                                <td>{{ $item->rm_promedio }}</td>
                                <td>{{ $item->rm_minimo }}</td>
                                <td>{{ $item->rm_maximo }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-admin-layout>