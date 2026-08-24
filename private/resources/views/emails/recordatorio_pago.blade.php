@extends('emails.layout')

@section('subject', 'Recordatorio de pago - ' . $brand['display_name'])
@section('title', 'Hola ' . $cliente->nombres . '!')

@section('content')
<p style="margin:0 0 14px;">Te recordamos que tu próxima cuota vence en <strong>3 días ({{ \Carbon\Carbon::parse($datosPago['fecha_vencimiento'])->format('d/m/Y') }})</strong>.</p>

<div style="margin:0 0 18px; padding:14px 16px; border-radius:16px; background-color:#f9fafb; border:1px solid #e5e7eb;">
    <strong>Fecha de vencimiento:</strong> {{ \Carbon\Carbon::parse($datosPago['fecha_vencimiento'])->format('d/m/Y') }}<br>
    <strong>Monto a pagar:</strong> ${{ number_format($datosPago['monto_pagar'], 0, ',', '.') }}<br>
    <strong>Formas de pago:</strong> Efectivo, transferencia electrónica, tarjeta de crédito y débito.
</div>

<div style="padding:14px 16px; border-radius:16px; background-color:{{ $brand['soft_color'] }}; border-left:4px solid {{ $brand['primary_color'] }};">
    Si ya realizaste el pago, puedes ignorar este mensaje. Si tienes dudas, contáctanos y te ayudaremos.
</div>
@endsection