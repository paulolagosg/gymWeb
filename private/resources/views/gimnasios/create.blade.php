<x-admin-layout>
    <div class="py-4">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 text-gray-900">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight mb-4">
                    Crear gimnasio
                </h2>

                <form action="{{ route('gimnasios.store') }}" method="POST">
                    @csrf

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="nombre" class="block text-gray-700">Nombre</label>
                            <input type="text" id="nombre" name="nombre" value="{{ old('nombre') }}" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3" required>
                            @error('nombre') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="direccion" class="block text-gray-700">Dirección</label>
                            <input type="text" id="direccion" name="direccion" value="{{ old('direccion') }}" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3">
                            @error('direccion') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="telefono" class="block text-gray-700">Teléfono</label>
                            <input type="text" id="telefono" name="telefono" value="{{ old('telefono') }}" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3">
                            @error('telefono') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="correo_electronico" class="block text-gray-700">Correo electrónico</label>
                            <input type="email" id="correo_electronico" name="correo_electronico" value="{{ old('correo_electronico') }}" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3">
                            @error('correo_electronico') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="color_primario" class="block text-gray-700">Color primario</label>
                            <input type="color" id="color_primario" name="color_primario" value="{{ old('color_primario', '#489ddf') }}" class="mt-1 block h-11 w-full border border-gray-300 rounded-md shadow-sm py-2 px-3">
                            @error('color_primario') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="color_secundario" class="block text-gray-700">Color secundario</label>
                            <input type="color" id="color_secundario" name="color_secundario" value="{{ old('color_secundario', '#3f8ac4') }}" class="mt-1 block h-11 w-full border border-gray-300 rounded-md shadow-sm py-2 px-3">
                            @error('color_secundario') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="sitio_web" class="block text-gray-700">Sitio web</label>
                            <input type="url" id="sitio_web" name="sitio_web" value="{{ old('sitio_web') }}" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3">
                            @error('sitio_web') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="estado" class="block text-gray-700">Estado</label>
                            <select id="estado" name="estado" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3">
                                <option value="1" {{ old('estado', '1') == '1' ? 'selected' : '' }}>Activo</option>
                                <option value="0" {{ old('estado') == '0' ? 'selected' : '' }}>Inactivo</option>
                            </select>
                            @error('estado') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="instagram" class="block text-gray-700">Instagram</label>
                            <input type="text" id="instagram" name="instagram" value="{{ old('instagram') }}" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3">
                            @error('instagram') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="facebook" class="block text-gray-700">Facebook</label>
                            <input type="text" id="facebook" name="facebook" value="{{ old('facebook') }}" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3">
                            @error('facebook') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div class="md:col-span-2">
                            <label for="tiktok" class="block text-gray-700">TikTok</label>
                            <input type="text" id="tiktok" name="tiktok" value="{{ old('tiktok') }}" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3">
                            @error('tiktok') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div class="md:col-span-2">
                            <label for="descripcion" class="block text-gray-700">Descripción</label>
                            <textarea id="descripcion" name="descripcion" rows="4" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3">{{ old('descripcion') }}</textarea>
                            @error('descripcion') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="email_encabezado" class="block text-gray-700">Encabezado email</label>
                            <input type="text" id="email_encabezado" name="email_encabezado" value="{{ old('email_encabezado') }}" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3" placeholder="Ej: Bienvenido a Fit Norte">
                            @error('email_encabezado') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="email_firma" class="block text-gray-700">Firma email</label>
                            <input type="text" id="email_firma" name="email_firma" value="{{ old('email_firma') }}" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3" placeholder="Ej: Equipo Fit Norte">
                            @error('email_firma') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div class="md:col-span-2">
                            <label for="email_pie" class="block text-gray-700">Pie de página email</label>
                            <textarea id="email_pie" name="email_pie" rows="3" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3" placeholder="Teléfono, dirección, sitio web o mensaje institucional">{{ old('email_pie') }}</textarea>
                            @error('email_pie') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="mt-6">
                        <button type="submit" class="bg-green-600 hover:bg-green-800 text-white font-bold py-2 px-4 rounded">
                            Guardar cambios
                        </button>
                        <button type="button" onclick="location.href='{{ route('gimnasios.index') }}'" class="bg-red-500 hover:bg-red-800 text-white font-bold py-2 px-4 rounded ml-2">
                            Cancelar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-admin-layout>