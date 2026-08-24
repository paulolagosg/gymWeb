@extends('emails.layout')

@section('subject', 'Cuotas vencidas - ' . $brand['display_name'])
@section('title', 'Hola ' . $cliente->nombres . '!')

@section('content')
<p style="margin:0 0 14px;">Te recordamos que tienes cuotas vencidas pendientes de pago.</p>

<div style="margin:0 0 18px; padding:14px 16px; border-radius:16px; background-color:{{ $brand['soft_color'] }}; border-left:4px solid {{ $brand['primary_color'] }};">
    <strong>Debes ${{ number_format($montoTotal, 0, ',', '.') }}</strong> desde hace {{ $diasDesde }} {{ $diasDesde == 1 ? 'día' : 'días' }}.
</div>

<div style="margin:0 0 18px; padding:14px 16px; border-radius:16px; background-color:#f9fafb; border:1px solid #e5e7eb;">
    @foreach($cuotasVencidas as $cuota)
    <div style="{{ !$loop->first ? 'margin-top:12px; padding-top:12px; border-top:1px solid #e5e7eb;' : '' }}">
        <strong>Fecha de vencimiento:</strong> {{ \Carbon\Carbon::parse($cuota->fecha_vencimiento)->format('d/m/Y') }}<br>
        <strong>Monto pendiente:</strong> ${{ number_format($cuota->saldo ?? $cuota->monto_pagar, 0, ',', '.') }}
    </div>
    @endforeach
</div>

<p style="margin:0 0 14px;"><strong>Formas de pago aceptadas:</strong> Efectivo, transferencia electrónica, tarjeta de crédito y débito.</p>

<div style="padding:14px 16px; border-radius:16px; background-color:{{ $brand['soft_color'] }}; border-left:4px solid {{ $brand['primary_color'] }};">
    Si ya realizaste el pago, puedes ignorar este mensaje. Si necesitas ayuda, contáctanos y te apoyaremos.
</div>
@endsection