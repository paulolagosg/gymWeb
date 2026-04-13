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

                    <div class="container py-5">
                        <h2 class="text-2xl font-bold mb-4">Encuesta de Satisfacción - Max Fitness And Health</h2>

                        <form method="POST" action="{{ route('survey.store') }}">
                            @csrf

                            <!-- Sección I: Tiempo de Entrenamiento -->
                            <div class="card mb-4">
                                <div class="font-bold mb-4">
                                    <h2>1. ¿Cuánto tiempo llevas entrenando en Max Fitness And Health?</h2>
                                </div>
                                <div class="card-body">
                                    <div class="form-group">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="training_time" id="less_1_month" value="less_1_month" required>
                                            <label class="form-check-label" for="less_1_month">Menos de 1 mes</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="training_time" id="1_to_3_months" value="1_to_3_months">
                                            <label class="form-check-label" for="1_to_3_months">1 a 3 meses</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="training_time" id="3_to_6_months" value="3_to_6_months">
                                            <label class="form-check-label" for="3_to_6_months">3 a 6 meses</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="training_time" id="more_6_months" value="more_6_months">
                                            <label class="form-check-label" for="more_6_months">Más de 6 meses</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="training_time" id="more_1_year" value="more_1_year">
                                            <label class="form-check-label" for="more_1_year">Más de 1 año</label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Sección II: NPS -->
                            <div class="card mb-4">
                                <div class="font-bold mb-4">
                                    <h2>2. En una escala del 0 al 10, ¿qué tan probable es que recomiendes Max Fitness And Health?</h2>
                                </div>
                                <div class="card-body">
                                    <div class="form-group">
                                        <div class="d-flex justify-content-between">
                                            @for($i=1;$i<=10;$i++)
                                                <input type="radio" id="nps_score" name="nps_score" value="{{ $i }}" required> {{ $i }}
                                                @endfor
                                        </div>
                                    </div>
                                    <div class="font-bold mb-4 mt-2">
                                        <label for="nps_reason">¿Cuál es la razón principal de tu puntuación?</label>
                                        <textarea class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" id="nps_reason" name="nps_reason" rows="3" required></textarea>
                                    </div>
                                </div>
                            </div>

                            <!-- Sección III: SERVQUAL -->
                            <div class="card mb-4">
                                <div class="font-bold mb-4">
                                    <h2>3. Por favor, califica los siguientes aspectos:</h2>
                                </div>
                                <div class="card-body">
                                    @php
                                    $servqualQuestions = [
                                    'tangibles' => 'Instalaciones y Equipamiento (Diseño del espacio, limpieza, orden, calidad y mantenimiento de los equipos)',
                                    'reliability' => 'Confiabilidad del servicio (Cumplimiento de horarios, consistencia del entrenamiento, profesionalismo del equipo)',
                                    'responsiveness' => 'Capacidad de respuesta y gestión personalizada (Rapidez y eficiencia para resolver cambios, dudas o requerimientos personales)',
                                    'security' => 'Seguridad, profesionalismo y confianza (Dominio técnico del staff, seguridad durante el entrenamiento, confidencialidad)',
                                    'empathy' => 'Calidez humana y atención individualizada (Trato personalizado, ambiente humano, interés genuino por tu bienestar)'
                                    ];

                                    $ratingOptions = [
                                    'excellent' => 'Excelente',
                                    'very_good' => 'Muy bueno',
                                    'good' => 'Bueno',
                                    'needs_improvement' => 'A mejorar',
                                    'poor' => 'Deficiente'
                                    ];
                                    @endphp

                                    @foreach($servqualQuestions as $key => $question)
                                    <div class="mb-4">
                                        <h5 class="font-bold mb-4">{{ $question }}</h5>
                                        @foreach($ratingOptions as $value => $label)
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="servqual_ratings[{{ $key }}]"
                                                id="{{ $key }}_{{ $value }}" value="{{ $value }}" required>
                                            <label class="form-check-label" for="{{ $key }}_{{ $value }}">{{ $label }}</label>
                                        </div>
                                        @endforeach
                                    </div>
                                    @endforeach
                                </div>
                            </div>

                            <!-- Sección IV: Experiencia Boutique -->
                            <div class="card mb-4">
                                <div class="font-bold mb-4">
                                    <h2>4. Experiencia Boutique y Emocional</h2>
                                </div>
                                <div class="card-body">
                                    <div class="font-bold mb-4">
                                        <label for="essential_aspect">¿Qué aspecto de nuestra experiencia consideras absolutamente imprescindible y deseas que jamás cambie?</label>
                                        <textarea class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" id="essential_aspect" name="open_answers[essential_aspect]" rows="3" required></textarea>
                                    </div>

                                    <div class="font-bold mb-4">
                                        <label for="valued_moment">¿Qué detalle, gesto o momento te ha hecho sentir realmente valorado/a como cliente?</label>
                                        <textarea class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" id="valued_moment" name="open_answers[valued_moment]" rows="3" required></textarea>
                                    </div>

                                    <div class="font-bold mb-4">
                                        <label for="improvement_suggestion">¿En qué aspecto crees que podríamos elevar aún más tu experiencia en Max Fitness And Health?</label>
                                        <textarea class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" id="improvement_suggestion" name="open_answers[improvement_suggestion]" rows="3" required></textarea>
                                    </div>

                                    <div class="font-bold mb-4">
                                        <label for="disappointing_moment">¿Ha habido algún momento o situación donde sentiste que no estuvimos a la altura de lo que esperabas?</label>
                                        <textarea class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" id="disappointing_moment" name="open_answers[disappointing_moment]" rows="3"></textarea>
                                    </div>

                                    <div class="font-bold mb-4">
                                        <label for="describing_word">¿Qué palabra usarías para describir tu experiencia global con Max Fitness And Health?</label>
                                        <input type="text" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" id="describing_word" name="open_answers[describing_word]" required>
                                    </div>
                                </div>
                            </div>

                            <!-- Sección V: Sugerencias -->
                            <div class="card mb-4">
                                <div class="font-bold mb-4">
                                    <h2>5. Sugerencias y Cierre</h2>
                                </div>
                                <div class="card-body">
                                    <div class="font-bold mb-4">
                                        <label for="additional_comments">¿Hay algo más que te gustaría compartir con nosotros para ayudarnos a crecer y mejorar cada día?</label>
                                        <textarea class="fmt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" id="additional_comments" name="open_answers[additional_comments]" rows="3"></textarea>
                                    </div>
                                </div>
                            </div>

                            <div class="text-start mt-4">
                                <button type="submit" class="bg-green-600 hover:bg-green-800 text-white font-bold py-2 px-4 rounded">Enviar Encuesta</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-admin-layout>