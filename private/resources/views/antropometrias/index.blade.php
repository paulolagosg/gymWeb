<x-admin-layout>
    <div class="py-4">
        <div class="">
            <div class="flex items-center justify-between mb-4 bg-white p-6 rounded-lg text-center">

            </div>
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 overflow-x-auto w-full">
                    <h2 class="text-xl font-bold mb-4">Resultados</h2>
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
                    <h3>Ingreso de Datos Antropométricos</h3>

                    <form action="{{ url('/antropometrias/calcular') }}" method="POST">
                        @csrf

                        <div>
                            <label for="peso">Peso (kg):</label>
                            <input type="number" name="peso" step="0.01" value="{{ old('peso') }}" required>
                            @error('peso')
                            <div class="error">{{ $message }}</div>
                            @enderror
                        </div>

                        <div>
                            <label for="talla">Talla (m):</label>
                            <input type="number" name="talla" step="0.01" value="{{ old('talla') }}" required>
                            @error('talla')
                            <div class="error">{{ $message }}</div>
                            @enderror
                        </div>

                        <div>
                            <label for="talla_sentado">Talla Sentado (cm):</label>
                            <input type="number" name="talla_sentado" step="0.01" value="{{ old('talla_sentado') }}">
                            @error('talla_sentado')
                            <div class="error">{{ $message }}</div>
                            @enderror
                        </div>

                        <div>
                            <label for="biacromial">Biacromial (cm):</label>
                            <input type="number" name="biacromial" step="0.01" value="{{ old('biacromial') }}" required>
                            @error('biacromial')
                            <div class="error">{{ $message }}</div>
                            @enderror
                        </div>

                        <div>
                            <label for="torax_transverso">Tórax Transverso (cm):</label>
                            <input type="number" name="torax_transverso" step="0.01" value="{{ old('torax_transverso') }}">
                            @error('torax_transverso')
                            <div class="error">{{ $message }}</div>
                            @enderror
                        </div>

                        <div>
                            <label for="torax_anteriorposterior">Tórax Anterior-Posterior (cm):</label>
                            <input type="number" name="torax_anteriorposterior" step="0.01" value="{{ old('torax_anteriorposterior') }}">
                            @error('torax_anteriorposterior')
                            <div class="error">{{ $message }}</div>
                            @enderror
                        </div>

                        <div>
                            <label for="bi_iliocrestido">Bi Iliocrestido (cm):</label>
                            <input type="number" name="bi_iliocrestido" step="0.01" value="{{ old('bi_iliocrestido') }}">
                            @error('bi_iliocrestido')
                            <div class="error">{{ $message }}</div>
                            @enderror
                        </div>

                        <div>
                            <label for="humeral">Humeral (cm):</label>
                            <input type="number" name="humeral" step="0.01" value="{{ old('humeral') }}">
                            @error('humeral')
                            <div class="error">{{ $message }}</div>
                            @enderror
                        </div>

                        <div>
                            <label for="femoral">Femoral (cm):</label>
                            <input type="number" name="femoral" step="0.01" value="{{ old('femoral') }}">
                            @error('femoral')
                            <div class="error">{{ $message }}</div>
                            @enderror
                        </div>

                        <div>
                            <label for="cabeza">Cabeza (cm):</label>
                            <input type="number" name="cabeza" step="0.01" value="{{ old('cabeza') }}">
                            @error('cabeza')
                            <div class="error">{{ $message }}</div>
                            @enderror
                        </div>

                        <div>
                            <label for="brazo_relajado">Brazo Relajado (cm):</label>
                            <input type="number" name="brazo_relajado" step="0.01" value="{{ old('brazo_relajado') }}">
                            @error('brazo_relajado')
                            <div class="error">{{ $message }}</div>
                            @enderror
                        </div>

                        <div>
                            <label for="brazo_flexionado">Brazo Flexionado (cm):</label>
                            <input type="number" name="brazo_flexionado" step="0.01" value="{{ old('brazo_flexionado') }}">
                            @error('brazo_flexionado')
                            <div class="error">{{ $message }}</div>
                            @enderror
                        </div>

                        <div>
                            <label for="antebrazo">Antebrazo (cm):</label>
                            <input type="number" name="antebrazo" step="0.01" value="{{ old('antebrazo') }}">
                            @error('antebrazo')
                            <div class="error">{{ $message }}</div>
                            @enderror
                        </div>

                        <div>
                            <label for="torax_mesoesternal">Tórax Mesoesternal (cm):</label>
                            <input type="number" name="torax_mesoesternal" step="0.01" value="{{ old('torax_mesoesternal') }}">
                            @error('torax_mesoesternal')
                            <div class="error">{{ $message }}</div>
                            @enderror
                        </div>

                        <div>
                            <label for="cintura_minima">Cintura Mínima (cm):</label>
                            <input type="number" name="cintura_minima" step="0.01" value="{{ old('cintura_minima') }}">
                            @error('cintura_minima')
                            <div class="error">{{ $message }}</div>
                            @enderror
                        </div>

                        <div>
                            <label for="caderas_maxima">Caderas Máxima (cm):</label>
                            <input type="number" name="caderas_maxima" step="0.01" value="{{ old('caderas_maxima') }}">
                            @error('caderas_maxima')
                            <div class="error">{{ $message }}</div>
                            @enderror
                        </div>

                        <div>
                            <label for="muslo_superior">Muslo Superior (cm):</label>
                            <input type="number" name="muslo_superior" step="0.01" value="{{ old('muslo_superior') }}">
                            @error('muslo_superior')
                            <div class="error">{{ $message }}</div>
                            @enderror
                        </div>

                        <div>
                            <label for="muslo_medial">Muslo Medial (cm):</label>
                            <input type="number" name="muslo_medial" step="0.01" value="{{ old('muslo_medial') }}">
                            @error('muslo_medial')
                            <div class="error">{{ $message }}</div>
                            @enderror
                        </div>

                        <div>
                            <label for="pantorrilla_maxima">Pantorrilla Máxima (cm):</label>
                            <input type="number" name="pantorrilla_maxima" step="0.01" value="{{ old('pantorrilla_maxima') }}">
                            @error('pantorrilla_maxima')
                            <div class="error">{{ $message }}</div>
                            @enderror
                        </div>

                        <div>
                            <label for="triceps">Pliegue Tríceps (mm):</label>
                            <input type="number" name="triceps" step="0.1" value="{{ old('triceps') }}" required>
                            @error('triceps')
                            <div class="error">{{ $message }}</div>
                            @enderror
                        </div>

                        <div>
                            <label for="subescapular">Pliegue Subescapular (mm):</label>
                            <input type="number" name="subescapular" step="0.1" value="{{ old('subescapular') }}" required>
                            @error('subescapular')
                            <div class="error">{{ $message }}</div>
                            @enderror
                        </div>

                        <div>
                            <label for="supraespinal">Pliegue Supraespinal (mm):</label>
                            <input type="number" name="supraespinal" step="0.1" value="{{ old('supraespinal') }}" required>
                            @error('supraespinal')
                            <div class="error">{{ $message }}</div>
                            @enderror
                        </div>

                        <div>
                            <label for="abdominal">Pliegue Abdominal (mm):</label>
                            <input type="number" name="abdominal" step="0.1" value="{{ old('abdominal') }}" required>
                            @error('abdominal')
                            <div class="error">{{ $message }}</div>
                            @enderror
                        </div>

                        <div>
                            <label for="pantorrilla">Pantorrilla (cm):</label>
                            <input type="number" name="pantorrilla" step="0.01" value="{{ old('pantorrilla') }}" required>
                            @error('pantorrilla')
                            <div class="error">{{ $message }}</div>
                            @enderror
                        </div>

                        <button type="submit">Calcular</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-admin-layout>