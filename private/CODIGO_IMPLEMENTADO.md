# 📝 Código Implementado: Resumen

## 1️⃣ Método del Controlador (AgendasController.php)

```php
public function agendaClientePorMes($slug, Request $request)
{
    // 1. Obtener cliente
    $cliente = \App\Models\Clientes::where('slug', $slug)->firstOrFail();

    // 2. Obtener año del request o usar año actual
    $ano = $request->input('ano', Carbon::now()->year);

    // 3. Obtener años disponibles
    $anosDisponibles = \App\Models\Agendas::where('id_cliente', $cliente->id)
        ->selectRaw('YEAR(fecha_inicio) as ano')
        ->distinct()
        ->orderBy('ano', 'desc')
        ->pluck('ano')
        ->toArray();

    // 4. Definir estados
    $estados = [
        1 => 'Agendado',
        2 => 'Cancelado sin recuperación',
        3 => 'Cancelado con recuperación',
        4 => 'Realizado',
        5 => 'Reagendado',
    ];

    // 5. Recorrer cada mes y contar agendas por estado
    $tablaAgendas = [];
    for ($mes = 1; $mes <= 12; $mes++) {
        $inicioMes = Carbon::createFromDate($ano, $mes, 1);
        $finMes = $inicioMes->clone()->endOfMonth();

        $fila = ['mes' => $mesesEnEspanol[$mes] . ' ' . $ano];

        // Contar por cada estado
        foreach ($estados as $id_estado => $nombre_estado) {
            $cantidad = \App\Models\Agendas::where('id_cliente', $cliente->id)
                ->where('estado', $id_estado)
                ->whereDate('fecha_inicio', '>=', $inicioMes)
                ->whereDate('fecha_inicio', '<=', $finMes)
                ->count();

            $fila[$nombre_estado] = $cantidad;
        }

        $fila['total'] = array_sum(array_filter($fila, /* ... */));
        $tablaAgendas[] = $fila;
    }

    // 6. Retornar vista con datos
    return view('agendas.agenda_cliente_por_mes', compact(
        'cliente',
        'tablaAgendas',
        'estados',
        'ano',
        'anosDisponibles'
    ));
}
```

## 2️⃣ Ruta (web.php)

```php
Route::get('/clientes/{slug}/agenda-por-mes',
    [\App\Http\Controllers\AgendasController::class, 'agendaClientePorMes']
)->name('agendas.cliente_por_mes');
```

## 3️⃣ Vista Principal (Tabla)

```blade
<table class="w-full border-collapse">
    <thead>
        <tr>
            <th>Mes</th>
            @foreach($estados as $id => $nombre)
                <th>{{ $nombre }}</th>
            @endforeach
            <th>Total</th>
        </tr>
    </thead>
    <tbody>
        @forelse($tablaAgendas as $fila)
            <tr>
                <td>{{ $fila['mes'] }}</td>
                @foreach($estados as $id => $nombre)
                    <td>
                        <span class="badge">{{ $fila[$nombre] }}</span>
                    </td>
                @endforeach
                <td><strong>{{ $fila['total'] }}</strong></td>
            </tr>
        @empty
            <tr>
                <td colspan="7">No hay agendas para este año</td>
            </tr>
        @endforelse
    </tbody>
    <tfoot>
        <tr>
            <td>Total Año {{ $ano }}</td>
            @foreach($estados as $id => $nombre)
                <td>{{ collect($tablaAgendas)->sum($nombre) }}</td>
            @endforeach
            <td><strong>{{ collect($tablaAgendas)->sum('total') }}</strong></td>
        </tr>
    </tfoot>
</table>
```

## 4️⃣ Selector de Año

```blade
<form method="GET" class="flex items-center gap-4">
    <label for="ano">Seleccionar Año:</label>
    <select name="ano" id="ano">
        @foreach($anosDisponibles as $anoDisponible)
            <option value="{{ $anoDisponible }}"
                {{ $anoDisponible == $ano ? 'selected' : '' }}>
                {{ $anoDisponible }}
            </option>
        @endforeach
    </select>
    <button type="submit">Filtrar</button>
</form>
```

## 5️⃣ Cards de Resumen

```blade
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
    @foreach($estados as $id => $nombre)
        @php
            $total = collect($tablaAgendas)->sum($nombre);
            $color = match($nombre) {
                'Agendado' => 'bg-green-100',
                'Cancelado sin recuperación' => 'bg-red-100',
                // ... más colores
            };
        @endphp
        <div class="p-4 rounded-lg {{ $color }}">
            <p>{{ $nombre }}</p>
            <p class="text-3xl font-bold">{{ $total }}</p>
        </div>
    @endforeach
</div>
```

## 📊 Estructura de Datos

### $tablaAgendas

```php
[
    [
        'mes' => 'Enero 2026',
        'mes_num' => 1,
        'Agendado' => 13,
        'Cancelado sin recuperación' => 0,
        'Cancelado con recuperación' => 1,
        'Realizado' => 24,
        'Reagendado' => 0,
        'total' => 38
    ],
    // ... más meses
]
```

### $estados

```php
[
    1 => 'Agendado',
    2 => 'Cancelado sin recuperación',
    3 => 'Cancelado con recuperación',
    4 => 'Realizado',
    5 => 'Reagendado',
]
```

## 🔄 Flujo de Datos

```
Usuario visita: /clientes/{slug}/agenda-por-mes?ano=2026
        ↓
    AgendasController::agendaClientePorMes()
        ↓
    Obtener cliente por slug
        ↓
    Obtener años disponibles de agendas
        ↓
    Para cada mes del año:
        - Contar agendas por estado
        - Calcular total del mes
        ↓
    Pasar datos a vista
        ↓
    Vista muestra tabla + cards + selector
```

## ✨ Características Clave

✅ **Dinámico**: Los años se generan automáticamente según los datos  
✅ **Flexible**: Se adapta a cualquier cliente  
✅ **Completo**: Muestra todas las métricas necesarias  
✅ **Responsive**: Funciona en todos los dispositivos  
✅ **Integrado**: Se conecta automáticamente con la interfaz existente  
✅ **Colorido**: Badges y cards con colores para cada estado
