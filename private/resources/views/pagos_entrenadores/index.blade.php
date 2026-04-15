<x-admin-layout>
    <div class="py-4">
        <div class="">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-4 text-gray-900 flex justify-between text-end">
                    <h2 class="text-2xl font-bold mb-4">Pagos mensuales por entrenador</h2>
                </div>
                <div class="p-6 text-gray-900 overflow-x-auto w-full">
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
                    <form method="GET" class="mb-4 flex gap-2 items-end">
                        <label>Mes:
                            <select name="month" onchange="this.form.submit()" class="border px-2 py-1">
                                @for($m=1;$m<=12;$m++)
                                    <option value="{{ $m }}" {{ $m == $month ? 'selected':'' }}>{{ \Carbon\Carbon::create()->locale('es')->month($m)->translatedFormat('F') }}</option>
                                    @endfor
                            </select>
                        </label>
                        <label>Año:
                            <select name="year" onchange="this.form.submit()" class="border px-2 py-1">
                                @for($y = date('Y')-2; $y<=date('Y')+2; $y++)
                                    <option value="{{ $y }}" {{ $y == $year ? 'selected':'' }}>{{ $y }}</option>
                                    @endfor
                            </select>
                        </label>
                    </form>

                    <form method="POST" action="{{ route('pagos_entrenadores.store') }}" id="form-pagos">
                        @csrf
                        <input type="hidden" name="month" value="{{ $month }}">
                        <input type="hidden" name="year" value="{{ $year }}">

                        <table class="w-full table-auto border-collapse">
                            <thead>
                                <tr class="bg-gray-100">
                                    <th class="p-2 border">Entrenador</th>
                                    <th class="p-2 border">Valor individual</th>
                                    <th class="p-2 border">Sesiones individuales</th>
                                    <th class="p-2 border">Valor duo</th>
                                    <th class="p-2 border">Sesiones duo</th>
                                    <th class="p-2 border">Bono mensual</th>
                                    <th class="p-2 border">Descuento</th>
                                    <th class="p-2 border">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($entrenadores as $ent)
                                @php
                                $reg = $registros->get($ent->id);
                                $tarifa = $tarifas->get($ent->id);

                                $ses_ind = $reg->sesiones_individual ?? 0;
                                $ses_duo = $reg->sesiones_duo ?? 0;
                                $bono = $reg->bono ?? 0;
                                $descuento = $reg->descuento ?? 0;
                                $valor_ind = $tarifa->individual ?? $ent->individual ?? 0;
                                $valor_duo = $tarifa->duo ?? $ent->duo ?? 0;
                                $row_total = $reg->total ?? ($ses_ind * $valor_ind + $ses_duo * $valor_duo + $bono - $descuento);
                                @endphp
                                <tr class="border-b">
                                    <td class="p-2 border">{{ $ent->name }} <br><small class="text-gray-600">{{ $ent->email }}</small></td>
                                    <td class="p-2 border text-right">${{ number_format($valor_ind,0,',','.') }}</td>
                                    <td class="p-2 border">
                                        <input type="number" min="0" name="entrenadores[{{ $loop->index }}][sesiones_individual]" value="{{ $ses_ind }}" class="mt-1 block w-full border ... sesiones-ind" data-valor="{{ $valor_ind }}">
                                        <input type="hidden" name="entrenadores[{{ $loop->index }}][id]" value="{{ $ent->id }}">
                                    </td>
                                    <td class="p-2 border text-right">${{ number_format($valor_duo,0,',','.') }}</td>
                                    <td class="p-2 border">
                                        <input type="number" min="0" name="entrenadores[{{ $loop->index }}][sesiones_duo]" value="{{ $ses_duo }}" class="mt-1 block w-full border ... sesiones-duo" data-valor="{{ $valor_duo }}">
                                    </td>
                                    <td class="p-2 border">
                                        <input type="number" step="0.01" min="0" name="entrenadores[{{ $loop->index }}][bono]" value="{{ $bono }}" class="mt-1 block w-full border ... bono">
                                    </td>
                                    <td class="p-2 border">
                                        <input type="number" step="0.01" min="0" name="entrenadores[{{ $loop->index }}][descuento]" value="{{ $descuento }}" class="mt-1 block w-full border ... descuento">
                                    </td>
                                    <td class="p-2 border text-right">
                                        <span class="row-total">${{ number_format($row_total,0,',','.') }}</span>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="7" class="p-2 text-right font-bold">Total mensual:</td>
                                    <td class="p-2 text-right font-bold" id="grand-total">0.00</td>
                                </tr>
                            </tfoot>
                        </table>

                        <div class="mt-4">
                            <button type="submit" class="bg-gray-700 hover:bg-gray-500 text-white font-bold py-2 px-4 rounded">Guardar</button>
                        </div>
                    </form>

                </div>
            </div>
        </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function() {

            const formatter = new Intl.NumberFormat('es-CL', {
                minimumFractionDigits: 0,
                maximumFractionDigits: 0
            });

            function recalcRow(input) {
                const tr = input.closest('tr');
                const ind = parseFloat(tr.querySelector('.sesiones-ind').value || 0);
                const duo = parseFloat(tr.querySelector('.sesiones-duo').value || 0);
                const valInd = parseFloat(tr.querySelector('.sesiones-ind').dataset.valor || 0);
                const valDuo = parseFloat(tr.querySelector('.sesiones-duo').dataset.valor || 0);
                const bono = parseFloat((tr.querySelector('.bono') && tr.querySelector('.bono').value) || 0);
                const descuento = parseFloat((tr.querySelector('.descuento') && tr.querySelector('.descuento').value) || 0);

                const total = (ind * valInd) + (duo * valDuo) + bono - descuento;

                tr.querySelector('.row-total').textContent = formatter.format(total);
                return total;
            }

            function recalcAll() {
                let sum = 0;
                document.querySelectorAll('tbody tr').forEach(function(row) {
                    const txt = row.querySelector('.row-total').textContent.replace(/\./g, '') || "0";
                    sum += parseFloat(txt);
                });
                document.getElementById('grand-total').textContent = formatter.format(sum);
            }

            document.querySelectorAll('.sesiones-ind, .sesiones-duo, .bono, .descuento').forEach(function(input) {
                input.addEventListener('input', function() {
                    recalcRow(this);
                    recalcAll();
                });
            });

            // inicializar totales al cargar
            document.querySelectorAll('tbody tr').forEach(function(row) {
                const firstInput = row.querySelector('.sesiones-ind') || row.querySelector('.sesiones-duo') || row.querySelector('.bono') || row.querySelector('.descuento');
                if (firstInput) recalcRow(firstInput);
            });
            recalcAll();
        });
    </script>
</x-admin-layout>