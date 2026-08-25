@extends('emails.layout')

@section('subject', $esTrial ? 'Tu periodo de prueba terminó - ' . $brand['display_name'] : 'Pago pendiente con la plataforma - ' . $brand['display_name'])
@section('title', 'Hola ' . $brand['display_name'] . '!')

@section('content')
@if ($esTrial)
<p style="margin:0 0 14px;">Tu periodo de prueba de 7 días con Ampaya terminó y el acceso quedó bloqueado.</p>

<div style="margin:0 0 18px; padding:14px 16px; border-radius:16px; background-color:{{ $brand['soft_color'] }}; border-left:4px solid {{ $brand['primary_color'] }};">
    Contáctanos para contratar un plan y seguir usando la plataforma.
</div>
@else
<p style="margin:0 0 14px;">Tienes un pago pendiente con la plataforma Ampaya.</p>

<div style="margin:0 0 18px; padding:14px 16px; border-radius:16px; background-color:{{ $brand['soft_color'] }}; border-left:4px solid {{ $brand['primary_color'] }};">
    <strong>Debes ${{ number_format($facturacion->monto, 0, ',', '.') }}</strong> desde hace {{ $diasDesde }} {{ $diasDesde == 1 ? 'día' : 'días' }}.
</div>

<div style="margin:0 0 18px; padding:14px 16px; border-radius:16px; background-color:#f9fafb; border:1px solid #e5e7eb;">
    <strong>Plan:</strong> {{ ucfirst($facturacion->plan) }}<br>
    <strong>Fecha de vencimiento:</strong> {{ \Carbon\Carbon::parse($facturacion->fecha_vencimiento)->format('d/m/Y') }}
</div>
@endif

<div style="padding:14px 16px; border-radius:16px; background-color:{{ $brand['soft_color'] }}; border-left:4px solid {{ $brand['primary_color'] }};">
    Si ya regularizaste esto, puedes ignorar este mensaje. Escríbenos a contacto@ampaya.cl si necesitas ayuda.
</div>
@endsection
