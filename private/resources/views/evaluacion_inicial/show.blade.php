<x-admin-layout>
    <div class="py-4">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white rounded-lg shadow-sm p-6 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <a href="{{ route('clientes.opciones.portada', $cliente->slug) }}" class="text-gray-700 hover:text-gray-500 inline-flex items-center gap-2 mb-3">
                        <i class="fas fa-circle-left"></i>
                        Volver a opciones del cliente
                    </a>
                    <h1 class="text-2xl font-bold text-gray-900">Evaluación inicial de {{ $cliente->nombres }} {{ $cliente->paterno }}</h1>
                    <p class="text-sm text-gray-500 mt-1">Vista de solo lectura para seguimiento del proceso inicial.</p>
                </div>
                <div class="text-sm">
                    @if($evaluacion?->completada_en)
                    <div class="inline-flex items-center rounded-full bg-green-100 px-4 py-2 font-semibold text-green-700">
                        Completada el {{ $evaluacion->completada_en->format('d-m-Y H:i') }}
                    </div>
                    @else
                    <div class="inline-flex items-center rounded-full bg-red-100 px-4 py-2 font-semibold text-red-700">
                        Pendiente de completar
                    </div>
                    @endif
                </div>
            </div>

            @foreach($secciones as $seccion)
            <section class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                    <h2 class="text-xl font-semibold text-gray-900">{{ $seccion->titulo }}</h2>
                    @if($seccion->descripcion)
                    <p class="text-sm text-gray-500 mt-1">{{ $seccion->descripcion }}</p>
                    @endif
                </div>
                <div class="p-6 space-y-5">
                    @foreach($seccion->preguntas as $pregunta)
                    @php
                    $respuesta = $respuestasAgrupadas[$pregunta->id] ?? ['selected_option_ids' => [], 'other_text' => null, 'text_value' => null];
                    $opcionesSeleccionadas = $pregunta->opciones->whereIn('id', $respuesta['selected_option_ids']);
                    @endphp
                    <div class="rounded-lg border border-gray-200 p-5">
                        <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-3">
                            <div>
                                <h3 class="text-base font-semibold text-gray-900">{{ $pregunta->pregunta }}</h3>
                                @if($pregunta->descripcion)
                                <p class="text-sm text-gray-500 mt-1">{{ $pregunta->descripcion }}</p>
                                @endif
                            </div>
                            <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">
                                {{ $pregunta->tipo === 'multiple' ? 'Selección múltiple' : ($pregunta->tipo === 'single' ? 'Selección única' : 'Texto libre') }}
                            </div>
                        </div>

                        <div class="mt-4">
                            @if($pregunta->tipo === 'text')
                            @if($respuesta['text_value'])
                            <p class="text-gray-800 whitespace-pre-line">{{ $respuesta['text_value'] }}</p>
                            @else
                            <p class="text-sm text-gray-400">Sin respuesta registrada.</p>
                            @endif
                            @else
                            @if($opcionesSeleccionadas->isNotEmpty())
                            <div class="flex flex-wrap gap-2">
                                @foreach($opcionesSeleccionadas as $opcion)
                                <span class="inline-flex items-center rounded-full bg-slate-100 px-3 py-1 text-sm font-medium text-slate-700">
                                    {{ $opcion->etiqueta }}@if($opcion->es_otro && $respuesta['other_text']) : {{ $respuesta['other_text'] }} @endif
                                </span>
                                @endforeach
                            </div>
                            @else
                            <p class="text-sm text-gray-400">Sin respuesta registrada.</p>
                            @endif
                            @endif
                        </div>
                    </div>
                    @endforeach
                </div>
            </section>
            @endforeach
        </div>
    </div>
</x-admin-layout>