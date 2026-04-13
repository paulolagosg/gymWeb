<x-admin-layout>
	<div class="py-4">
		<div class="">
			<div class="flex items-center justify-between mb-4 bg-white p-6 rounded-lg text-center">
				<a href="{{ route('clientes.opciones.portada', $cliente->slug) }}" class="text-gray-700 hover:text-gray-500">
					<i class="fas fa-circle-left fa-2x">&nbsp;{{ $cliente->nombres }} {{ $cliente->paterno }} {{ $cliente->materno }}</i>
				</a>
			</div>
			<div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
				<div class="p-6">
					<h2 class="text-lg font-semibold">Perimetros del Cliente</h2>
					@if($perimetroReciente)
					<div class="mt-4">
						<p><strong>Fecha de Registro:</strong> {{ $perimetroReciente->created_at ? $perimetroReciente->created_at->format('d/m/Y') : '-' }}</p>
						<p><strong>Cabeza (cm):</strong> {{ $perimetroReciente->cabeza ?? '-' }}</p>
					</div>
					@endif
					<div class="grid grid-cols-1 md:grid-cols-1 gap-4 mt-6">
						<!-- Gráfico de evolución del peso -->
						<!-- 
<div class="bg-white rounded-lg shadow p-4 flex items-center justify-center h-72 w-full">
                            <canvas id="graficoPeso"></canvas>
                        </div>
 -->
						<div id="tablaPesos" class="bg-white rounded-lg shadow p-4 w-full table-responsive">
							<h3 class="text-md font-semibold mt-4">Historial de Perímetros</h3>
							@if($perimetros->isEmpty())
							<p>No hay registros de perímetros.</p>
							@else
							<div class="overflow-x-auto w-full block whitespace-nowrap">

								<table id="tablaDatos" class="min-w-full divide-y divide-gray-200">
									<thead>
										<tr>
											<th>Fecha</th>
											<th>Cabeza</th>
											<th>Brazo Relajado</th>
											<th>Brazo Flexionado Tensión</th>
											<th>Antebrazo</th>
											<th>Torax Mesoexternal</th>
											<th>Cintura (mínima)</th>
											<th>Caderas (máxima)</th>
											<th>Muslo (superior)</th>
											<th>Muslo (medial)</th>
											<th>Pantorrila (máxima)</th>
										</tr>
									</thead>
									<tbody>
										@foreach($perimetros as $p)
										<tr>
											<td>{{ $p->created_at ? $p->created_at->format('d/m/Y') : '-' }}</td>
											<td>{{ $p->cabeza ?? '-' }}</td>
											<td>{{ $p->brazo_relajado ?? '-' }}</td>
											<td>{{ $p->brazo_flexionado_tension ?? '-' }}</td>
											<td>{{ $p->antebrazo ?? '-' }}</td>
											<td>{{ $p->torax_mesoexternal ?? '-' }}</td>
											<td>{{ $p->cintura_minima ?? '-' }}</td>
											<td>{{ $p->caderas_maxima ?? '-' }}</td>
											<td>{{ $p->muslo_superior ?? '-' }}</td>
											<td>{{ $p->muslo_medial ?? '-' }}</td>
											<td>{{ $p->pantorrilla_maxima ?? '-' }}</td>
										</tr>
										@endforeach
									</tbody>
								</table>
							</div>
							@endif
						</div>
					</div>
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

					<form action="{{ route('clientes.perimetros.store', $cliente->slug) }}" method="POST" class="mb-6 mt-6 bg-white p-4 rounded shadow">
						@csrf
						<h3 class="text-lg font-semibold mb-3">Registrar Perímetros</h3>
						<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
							<div><label class="block text-sm">Cabeza</label><input type="number" step="0.1" name="cabeza" class="mt-1 block w-full border rounded px-2 py-1" required></div>
							<div><label class="block text-sm">Brazo relajado</label><input type="number" step="0.1" name="brazo_relajado" class="mt-1 block w-full border rounded px-2 py-1" required></div>
							<div><label class="block text-sm">Brazo flexionado (tensión)</label><input type="number" step="0.1" name="brazo_flexionado_tension" class="mt-1 block w-full border rounded px-2 py-1" required></div>
							<div><label class="block text-sm">Antebrazo</label><input type="number" step="0.1" name="antebrazo" class="mt-1 block w-full border rounded px-2 py-1" required></div>
							<div><label class="block text-sm">Tórax (mesoexternal)</label><input type="number" step="0.1" name="torax_mesoexternal" class="mt-1 block w-full border rounded px-2 py-1" required></div>
							<div><label class="block text-sm">Cintura (mínima)</label><input type="number" step="0.1" name="cintura_minima" class="mt-1 block w-full border rounded px-2 py-1" required></div>
							<div><label class="block text-sm">Caderas (máxima)</label><input type="number" step="0.1" name="caderas_maxima" class="mt-1 block w-full border rounded px-2 py-1" required></div>
							<div><label class="block text-sm">Muslo (superior)</label><input type="number" step="0.1" name="muslo_superior" class="mt-1 block w-full border rounded px-2 py-1" required></div>
							<div><label class="block text-sm">Muslo (medial)</label><input type="number" step="0.1" name="muslo_medial" class="mt-1 block w-full border rounded px-2 py-1" required></div>
							<div><label class="block text-sm">Pantorrilla (máxima)</label><input type="number" step="0.1" name="pantorrilla_maxima" class="mt-1 block w-full border rounded px-2 py-1" required> </div>
						</div>
						<div class="mt-4">
							<button class="bg-green-600 text-white py-2 px-4 rounded">Registrar perímetros</button>
						</div>
					</form>
				</div>
			</div>
		</div>

		<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

</x-admin-layout>