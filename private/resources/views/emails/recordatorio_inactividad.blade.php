@extends('emails.layout')

@section('subject', 'Te extrañamos - ' . $brand['display_name'])
@section('title', 'Hola ' . $cliente->nombres . '!')

@section('content')
<p style="margin:0 0 14px;">Tu entrenador se acordó de ti. Llevas <strong>{{ $diasSinEntrenar }} {{ $diasSinEntrenar === 1 ? 'día' : 'días' }} sin entrenar</strong> y queremos motivarte a retomar tu rutina.</p>

<p style="margin:0 0 16px;">Cada sesión cuenta y tu progreso sigue esperándote.</p>

<div style="padding:14px 16px; border-radius:16px; background-color:{{ $brand['soft_color'] }}; border-left:4px solid {{ $brand['primary_color'] }};">
    Si tienes algún inconveniente para asistir o necesitas coordinar un horario especial, escríbenos y lo revisamos contigo.
</div>
@endsection