@component('mail::message')
# ¡Hola {{ $cliente->nombres }}!

Te informamos que hemos registrado el pago de tu cuota correspondiente al mes de **{{ \Carbon\Carbon::parse($datosPago['fecha_vencimiento'])->translatedFormat('F Y') }}**.

**Detalles del pago:**

- **Item pagado:** {{ $datosPago['tipo_cuota'] }}
- **Fecha de pago:** {{ \Carbon\Carbon::parse($datosPago['fecha_pago'])->format('d/m/Y') }}
- **Monto pagado:** ${{ number_format($datosPago['monto_pagado'], 0, ',', '.') }}
- **Forma de pago:** {{ $datosPago['forma_pago'] }}

@if(!empty($datosPago['observaciones']))
- **Observaciones:** {{ $datosPago['observaciones'] }}
@endif

@component('mail::panel')
¡Gracias por mantenerte al día con tus pagos!
@endcomponent

Saludos cordiales,<br>
**Equipo Max Fitness & Health**
@endcomponent