@extends('emails.layout')

@section('subject', 'Nuevo mensaje - ' . $brand['display_name'])
@section('title', 'Tienes un nuevo mensaje')

@section('content')
<p style="margin:0 0 14px;">Has recibido un nuevo mensaje de <strong>{{ $remitente->name }}</strong>.</p>

@if(!empty($mensaje->mensaje ?? null))
<div style="margin:0 0 18px; padding:14px 16px; border-radius:16px; background-color:{{ $brand['soft_color'] }}; border-left:4px solid {{ $brand['primary_color'] }};">
    {{ $mensaje->mensaje }}
</div>
@endif

@include('emails.partials.button', ['url' => url('/mensajes'), 'label' => 'Revisar mensaje', 'brand' => $brand])
@endsection