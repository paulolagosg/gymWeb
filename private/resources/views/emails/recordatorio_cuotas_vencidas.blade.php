@component('mail::message')
# ¡Hola {{ $cliente->nombres }}!

Te recordamos que tienes cuotas vencidas pendientes de pago.

**Detalles de pagos pendientes:**

@foreach($cuotasVencidas as $cuota)
**Fecha de vencimiento:** {{ \Carbon\Carbon::parse($cuota->fecha_vencimiento)->format('d/m/Y') }}
**Monto a pagar:** ${{ number_format($cuota->monto_pagar, 0, ',', '.') }}

@endforeach

**Formas de pago aceptadas:**
Efectivo, Transferencia electrónica, Tarjeta de crédito/débito.

@component('mail::panel')
Si ya has realizado el pago, por favor ignora este mensaje. Si tienes alguna duda o necesitas asistencia, no dudes en contactarnos.
@endcomponent

Saludos cordiales,<br>
**Equipo Max Fitness & Health**
@endcomponent