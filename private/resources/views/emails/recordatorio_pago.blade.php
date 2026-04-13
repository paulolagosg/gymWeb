@component('mail::message')
# ¡Hola {{ $cliente->nombres }}!

Te recordamos que tu próxima cuota vence en **3 días ({{ \Carbon\Carbon::parse($datosPago['fecha_vencimiento'])->format('d/m/Y') }})**.

**Detalles del pago pendiente:**

- **Fecha de vencimiento:** {{ \Carbon\Carbon::parse($datosPago['fecha_vencimiento'])->format('d/m/Y') }}
- **Monto a pagar:** ${{ number_format($datosPago['monto_pagar'], 0, ',', '.') }}
- **Formas de pago aceptadas:** Efectivo, Transferencia electrónica, Tarjeta de crédito/débito.


@component('mail::panel')
Si ya has realizado el pago, por favor ignora este mensaje. Si tienes alguna duda o necesitas asistencia, no dudes en contactarnos.
@endcomponent

Saludos cordiales,<br>
**Equipo Max Fitness & Health**
@endcomponent