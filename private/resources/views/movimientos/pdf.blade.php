<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Cartola</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            border: 1px solid #ccc;
            padding: 4px;
        }

        th {
            background: #eee;
        }
    </style>
</head>

<body>
    <h2>Cartola</h2>
    <div class="mt-4 font-bold text-lg">
        Saldo actual: <span style="color:{{ $saldo > 0 ? 'green' : 'red' }}">${{ number_format($saldo, 0, ',', '.') }}</span>
    </div>
    <table>
        <thead>
            <tr>
                <th>Fecha</th>
                <th>Descripción</th>
                <th>Tipo</th>
                <th>Monto</th>
            </tr>
        </thead>
        <tbody>
            @foreach($movimientos as $m)
            <tr>
                <td>{{ $m->fecha }}</td>
                <td>{{ $m->descripcion }}</td>
                <td>{{ ucfirst($m->tipo) }}</td>
                <td style="color:{{ $m->tipo == 'ingreso' ? 'green' : 'red' }}">
                    {{ $m->tipo == 'ingreso' ? '+' : '-' }}${{ number_format($m->monto, 0, ',', '.') }}
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</body>

</html>