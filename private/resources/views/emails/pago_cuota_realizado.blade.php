@extends('emails.layout')

@section('subject', 'Pago registrado - ' . $brand['display_name'])
@section('title', 'Hola ' . $cliente->nombres . '!')

@section('content')
<p style="margin:0 0 14px;">Te informamos que registramos el pago de tu cuota correspondiente al mes de <strong>{{ \Carbon\Carbon::parse($datosPago['fecha_vencimiento'])->translatedFormat('F Y') }}</strong>.</p>

<div style="margin:0 0 18px; padding:14px 16px; border-radius:16px; background-color:#f9fafb; border:1px solid #e5e7eb;">
    <strong>Item pagado:</strong> {{ $datosPago['tipo_cuota'] }}<br>
    <strong>Fecha de pago:</strong> {{ \Carbon\Carbon::parse($datosPago['fecha_pago'])->format('d/m/Y') }}<br>
    <strong>Monto pagado:</strong> ${{ number_format($datosPago['monto_pagado'], 0, ',', '.') }}<br>
    <strong>Forma de pago:</strong> {{ $datosPago['forma_pago'] }}
    @if(!empty($datosPago['observaciones']))
    <br><strong>Observaciones:</strong> {{ $datosPago['observaciones'] }}
    @endif
</div>

<div style="padding:14px 16px; border-radius:16px; background-color:{{ $brand['soft_color'] }}; border-left:4px solid {{ $brand['primary_color'] }};">
    Gracias por mantenerte al día con tus pagos.
</div>
@endsection