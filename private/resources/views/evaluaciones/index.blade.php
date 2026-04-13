<style>
    .nivel-barra {
        background: #e5e7eb;
        border-radius: 8px;
        height: 24px;
        width: 100%;
        margin-bottom: 12px;
        overflow: hidden;
    }

    .nivel-barra-interno {
        height: 100%;
        border-radius: 8px;
        transition: width 0.5s;
    }

    .nivel-empatia {
        background: #3b82f6;
    }

    .nivel-escucha {
        background: #10b981;
    }

    .nivel-comunicacion {
        background: #eab308;
    }

    .nivel-anatomia {
        background: #eab308;
    }

    .nivel-label {
        font-weight: bold;
        margin-bottom: 2px;
    }

    .nivel-valor {
        float: right;
        font-size: 0.95em;
        margin-right: 8px;
    }
</style>
<x-admin-layout>
    <div class="py-4">
        <div class="">
            <div class="flex items-center justify-between mb-4 bg-white p-4 rounded-lg shadow">
                @if(Auth::user()->id_clasificacion == 3)
                <a href="{{ route('entrenadores.opciones.portada',$entrenador->slug) }}" class="hover:text-gray-500">
                    <i class="fas fa-circle-left fa-2x">&nbsp;{{ $entrenador->name }}</i>
                </a>
                @else
                <a href="{{ route('portada') }}" class="hover:text-gray-500">
                    <i class="fas fa-circle-left fa-2x"></i>
                </a>
                @endif
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="flex items-center justify-between p-4">
                    <h1 class="text-2xl font-bold">Categorización</h1>
                </div>
                <div class="p-6 text-gray-900 overflow-x-auto w-full">
                    <div class="mb-4">
                        @if(Auth::user()->id_clasificacion == 3)
                        <label class="nivel-label">Categoría</label>
                        @else
                        <label class="nivel-label">Tu categoría</label>
                        @endif
                        <br>
                        <label class="nivel-label text-2xl">{{$categorias->nombre}}</label>
                    </div>
                    <br>
                    <div class="mb-6 max-w-md mt-4">
                        @if($promedios)
                        <div class="nivel-label text-2xl">Evaluación General <span class="nivel-valor">{{ $promedios->total }}%</span></div>
                        <div class="nivel-barra">
                            <div class="nivel-barra-interno nivel-anatomia" style="width: {{ $promedios->total }}%"></div>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 md:gap-6">
                            <div class="mb-4 sm:mb-0">
                                <div class="nivel-label">Empatía <span class="nivel-valor">{{ $promedios->empatia }}/7</span></div>
                                <div class="nivel-barra">
                                    <div class="nivel-barra-interno nivel-empatia" style="width: {{ ($promedios->empatia/7)*100}}%"></div>
                                </div>
                            </div>
                            <div class="mb-4 sm:mb-0">
                                <div class="nivel-label">Escucha activa <span class="nivel-valor">{{ $promedios->escucha_activa }}/7</span></div>
                                <div class="nivel-barra">
                                    <div class="nivel-barra-interno nivel-empatia" style="width: {{ ($promedios->escucha_activa/7)*100 }}%"></div>
                                </div>
                            </div>
                            <div class="mb-4 sm:mb-0">
                                <div class="nivel-label">Comunicación <span class="nivel-valor">{{ $promedios->comunicacion }}/7</span></div>
                                <div class="nivel-barra">
                                    <div class="nivel-barra-interno nivel-empatia" style="width: {{ ($promedios->comunicacion/7)*100 }}%"></div>
                                </div>
                            </div>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 md:gap-6">
                            <div class="mb-4 sm:mb-0">
                                <div class="nivel-label">Anatomía Funcional y Biomecánica <span class="nivel-valor">{{ $promedios->anatomia }}/7</span></div>
                                <div class="nivel-barra">
                                    <div class="nivel-barra-interno nivel-empatia" style="width: {{ ($promedios->anatomia/7)*100 }}%"></div>
                                </div>
                            </div>
                            <div class="mb-4 sm:mb-0">
                                <div class="nivel-label">Fisiología del Ejercicio <span class="nivel-valor">{{ $promedios->fisiologia }}/7</span></div>
                                <div class="nivel-barra">
                                    <div class="nivel-barra-interno nivel-empatia" style="width: {{ ($promedios->fisiologia/7)*100 }}%"></div>
                                </div>
                            </div>
                            <div class="mb-4 sm:mb-0">
                                <div class="nivel-label">Programación del entrenamiento <span class="nivel-valor">{{ $promedios->programacion }}/7</span></div>
                                <div class="nivel-barra">
                                    <div class="nivel-barra-interno nivel-empatia" style="width: {{ ($promedios->programacion/7)*100 }}%"></div>
                                </div>
                            </div>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 md:gap-6">
                            <div class="mb-4 sm:mb-0">
                                <div class="nivel-label">Poblaciones especiales y Fisiología clínica <span class="nivel-valor">{{ $promedios->poblacion }}/7</span></div>
                                <div class="nivel-barra">
                                    <div class="nivel-barra-interno nivel-empatia" style="width: {{ ($promedios->poblacion/7)*100 }}%"></div>
                                </div>
                            </div>
                            <div class="mb-4 sm:mb-0">
                                <div class="nivel-label">Psicología del deporte <span class="nivel-valor">{{ $promedios->psicologia }}/7</span></div>
                                <div class="nivel-barra">
                                    <div class="nivel-barra-interno nivel-empatia" style="width: {{ ($promedios->psicologia/7)*100 }}%"></div>
                                </div>
                            </div>
                        </div>
                        @else
                        <div class="nivel-label">Sin evaluación</div>
                        @endif
                    </div>
                </div>
                <div class="flex items-center justify-between p-4">
                    @if(Auth::user()->id_clasificacion == 3)
                    <a href="{{ route('evaluaciones.create',$entrenador->slug) }}" class="bg-gray-700 hover:bg-gray-500 text-white font-bold py-2 px-4 rounded">
                        Categorizar
                    </a>
                    @endif
                    <a href="{{ route('evaluaciones.exportar_pdf', $entrenador->slug) }}" class="bg-green-600 hover:bg-green-400 text-white font-bold py-2 px-4 rounded" target="_blank">
                        Exportar PDF
                    </a>
                </div>
            </div>
        </div>
    </div>
</x-admin-layout>