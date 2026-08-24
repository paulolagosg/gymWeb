@extends('emails.layout')

@section('subject', 'Reporte de indicadores - ' . $brand['display_name'])
@section('title', 'Hola ' . trim($cliente->nombres . ' ' . $cliente->paterno . ' ' . $cliente->materno) . '!')

@section('content')
<p style="margin:0 0 14px;">Adjunto encontrarás tu reporte de evolución, junto con la información financiera asociada a tu cuenta en {{ $brand['display_name'] }}.</p>

<div style="padding:14px 16px; border-radius:16px; background-color:{{ $brand['soft_color'] }}; border-left:4px solid {{ $brand['primary_color'] }};">
    El archivo PDF viaja adjunto en este correo para que puedas revisarlo cuando quieras.
</div>
@endsection