<style>
    .tab-content {
        display: none;
    }

    .tab:target .tab-content,
    .tab:last-of-type .tab-content {
        display: block;
    }

    .tab:target~.tab:last-of-type .tab-content {
        display: none;
    }

    /* parámetros para configurar las pestañas */
    :root {
        --tabs-border-color: #374151;
        --tabs-border-size: 3px;
        --tabs-text-color: #fff;
        --tabs-text-color-active: #374151;
        --tabs-dark-color: #374151;
        --tabs-lite-color: #ffffff;
        --tabs-width: 25%;
        /*220px;*/
        --tabs-height: 70px;
    }

    /* aspecto básico */

    h2,
    p {
        margin: 0;
    }

    a {
        color: inherit;
        text-decoration: none;
    }

    .tabs * {
        box-sizing: border-box;
    }

    /* esto es para posicionar las pestañas correctamente */
    .tab-container {
        position: relative;
        padding-top: var(--tabs-height);
        /* en esta zona colocaremos las pestañas */
    }

    #tab1>a {
        --tabs-position: 0;
    }

    #tab2>a {
        --tabs-position: 1;
    }

    #tab3>a {
        --tabs-position: 2;
    }

    #tab4>a {
        --tabs-position: 3;
    }

    #tab5>a {
        --tabs-position: 4;
    }

    #tab6>a {
        --tabs-position: 5;
    }

    #tab7>a {
        --tabs-position: 6;
    }

    #tab8>a {
        --tabs-position: 7;
    }

    #tab9>a {
        --tabs-position: 8;
    }

    .tab>a {
        text-align: center;
        position: absolute;
        width: calc(var(--tabs-width));
        height: calc(var(--tabs-height) + var(--tabs-border-size));
        top: 0;
        left: calc(var(--tabs-width) * var(--tabs-position));
        /* posición de cada pestaña */
    }

    /* más aspecto */
    .tabs {
        padding: 5px;
        color: var(--tabs-text-color);
        font-weight: bold;
    }

    .tab-content {
        background-color: var(--tabs-lite-color);
        padding: 20px;
        border: var(--tabs-border-size) solid var(--tabs-border-color);
        border-radius: 0 0 10px 10px;
        position: relative;
        z-index: 100;
    }

    .tab>a {
        background-color: var(--tabs-dark-color);
        padding: 5px;
        border: var(--tabs-border-size) solid var(--tabs-border-color);
        border-radius: 10px 10px 0 0;
        border-bottom: 0;
    }

    .tab:target>a,
    .tab:last-of-type>a {
        background-color: var(--tabs-lite-color);
        z-index: 200;
        color: var(--tabs-text-color-active);

    }

    .tab:target~.tab:last-of-type>a {
        background-color: var(--tabs-dark-color);
        color: var(--tabs-text-color);
        z-index: 0;
    }
</style>
<x-admin-layout>
    <div class="py-4">
        <div class="">
            <div class="flex items-center justify-between mb-4 bg-white p-6 rounded-lg">
                <div class="text-gray-700">
                    <i class="fas fa-user fa-2x">&nbsp;{{ $cliente->nombres }} {{ $cliente->paterno }} {{ $cliente->materno }}</i>
                    <br><small>{{ $cliente->plan->nombre ?? 'Sin plan' }}</small>
                </div>
                @if(in_array((int) Auth::user()->id_tipo_usuario, [1, 2, 10], true))
                <a href="{{ route('clientes.opciones.portada', $cliente->slug) }}" class="inline-flex items-center gap-2 rounded-lg bg-gray-800 px-3 py-2 text-sm font-semibold text-white hover:bg-gray-600">
                    <i class="fas fa-arrow-left"></i>
                    Volver
                </a>
                @endif
            </div>
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 overflow-x-auto w-full">
                    <h2 class="font-semibold text-xl text-gray-800 leading-tight mb-4">
                        Cuestionario Acondicionamiento Físico
                    </h2>
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

                    <form method="POST" action="{{ route('fitplan.store', $cliente->slug) }}">
                        @csrf
                        <div class="tabs">
                            <div class="tab-container">
                                <div id="tab4" class="tab">
                                    <a href="#tab4">Otros Datos de Interés</a>
                                    <div class="tab-content">
                                        <div class="mb-4">
                                            <label class="font-semibold text-xl text-gray-800 leading-tight mb-4">Descríbeme lo que sueles comer actualmente un día cualquiera</label>
                                            <textarea name="dia_cualquiera" class="mt-1 block text-black w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" placeholder="Describir">{{ old('dia_cualquiera') }}</textarea>
                                        </div>
                                        <div class="mb-4">
                                            <label class="font-semibold text-xl text-gray-800 leading-tight mb-4">Datos que puedas creer que son de intereses relacionados con tu preparación</label>
                                            <textarea name="datos_interes" class="mt-1 block text-black w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" placeholder="Datos de interés">{{ old('datos_interes') }}</textarea>
                                        </div>
                                        <div class="mb-4">
                                            <label class="font-semibold text-xl text-gray-800 leading-tight mb-4">Objetivo del acondicionamiento físico</label>
                                            <textarea name="objetivo" class="mt-1 block text-black w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" placeholder="Objetivo">{{ old('objetivo') }}</textarea>
                                        </div>
                                    </div>
                                </div>
                                <div id="tab3" class="tab">
                                    <a href="#tab3">Horarios, Entrenos y Suplementación</a>
                                    <div class="tab-content">
                                        <div class="mb-4">
                                            <label class="font-semibold text-xl text-gray-800 leading-tight mb-4">Hora a la que te sueles levantar</label>
                                            <textarea name="hora_levantarse" class="mt-1 block text-black w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" placeholder="Horario">{{ old('hora_levantarse') }}</textarea>
                                        </div>
                                        <div class="mb-4">
                                            <label class="font-semibold text-xl text-gray-800 leading-tight mb-4">Hora a la que te sueles acostar</label>
                                            <textarea name="hora_acostarse" class="mt-1 block text-black w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" placeholder="Horario">{{ old('hora_acostarse') }}</textarea>
                                        </div>
                                        <div class="mb-4">
                                            <label class="font-semibold text-xl text-gray-800 leading-tight mb-4">Descripción de tu trabajo</label>
                                            <textarea name="trabajo" class="mt-1 block text-black w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" placeholder="Describe">{{ old('trabajo') }}</textarea>
                                        </div>
                                        <div class="mb-4">
                                            <label class="font-semibold text-xl text-gray-800 leading-tight mb-4">Horario en que vas al gimnasio</label>
                                            <textarea name="hora_gimnasio" class="mt-1 block text-black w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" placeholder="Horario">{{ old('hora_gimnasio') }}</textarea>
                                        </div>
                                        <div class="mb-4">
                                            <label class="font-semibold text-xl text-gray-800 leading-tight mb-4">Duración del entreno</label>
                                            <textarea name="duracion_entreno" class="mt-1 block text-black w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" placeholder="Horario">{{ old('duracion_entreno') }}</textarea>
                                        </div>
                                        <div class="mb-4">
                                            <label class="font-semibold text-xl text-gray-800 leading-tight mb-4">Suplementación Actual (sólo si aplica)</label>
                                            <textarea name="suplemento" class="mt-1 block text-black w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" placeholder="Suplementación">{{ old('suplemento') }}</textarea>
                                        </div>
                                    </div>
                                </div>
                                <div id="tab2" class="tab">
                                    <a href="#tab2">Hábitos Alimenticios</a>
                                    <div class="tab-content">
                                        <div class="mb-4">
                                            <label class="font-semibold text-xl text-gray-800 leading-tight mb-4">Intolerancias Alimentarias</label>
                                            <textarea name="intolerancias" class="mt-1 block text-black w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" placeholder="Intolertancias alimentarias">{{ old('intolerancias') }}</textarea>
                                        </div>
                                        <div class="mb-4">
                                            <label class="font-semibold text-xl text-gray-800 leading-tight mb-4">Alimentos que NO te gustan</label>
                                            <textarea name="no_gustan" class="mt-1 block text-black w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" placeholder="Alimentos que no te gustan">{{ old('no_gustan') }}</textarea>
                                        </div>
                                        <div class="mb-4">
                                            <label class="font-semibold text-xl text-gray-800 leading-tight mb-4">Alimentos que ENCANTAN</label>
                                            <textarea name="encantan" class="mt-1 block text-black w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" placeholder="Alimentos que te encantan">{{ old('encantan') }}</textarea>
                                        </div>
                                        <div class="mb-4">
                                            <label class="font-semibold text-xl text-gray-800 leading-tight mb-4">Horario de comidas</label>
                                            <textarea name="horario" class="mt-1 block text-black w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" placeholder="Horario de cmomidas">{{ old('horario') }}</textarea>
                                        </div>
                                    </div>

                                </div>
                                <div id="tab1" class="tab">
                                    <a href="#tab1">Patologías</a>
                                    <div class="tab-content">
                                        <h1 class="font-semibold text-xl text-gray-800 leading-tight mb-4">Alergias, enfermedades patológicas conocidas</h1>
                                        <textarea name="patologias" class="w-full text-black h-32 p-2 border border-gray-300 rounded" placeholder="Alergias u otros">{{ old('patologias') }}</textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <button type="submit" class="bg-green-600 hover:bg-green-800 text-white font-bold py-2 px-4 rounded">
                            Guardar Cambios
                        </button>

                    </form>
                </div>
            </div>
        </div>
    </div>
</x-admin-layout>