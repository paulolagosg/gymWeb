@extends('emails.layout')

@section('subject', 'Bienvenido a ' . $brand['display_name'])
@section('title', 'Hola ' . $cliente->nombres . '!')

@section('content')
<p style="margin:0 0 16px;">Bienvenido/a a {{ $brand['display_name'] }}. Estas son tus credenciales de acceso:</p>

<p style="margin:0 0 18px;">
    <strong>Correo electrónico:</strong> {{ $cliente->email }}<br>
    <strong>Clave:</strong> {{ $pwd }}
</p>

@if(!empty($includeAppDownload) && !empty($appDownloadUrl))
<p style="margin:0 0 8px;">Descarga la app del gimnasio para ingresar con estos datos:</p>
<table role="presentation" cellspacing="0" cellpadding="0" style="margin:8px 0 18px; border-collapse:collapse;">
    <tr>
        <td>
            <a href="{{ $appDownloadUrl }}">
                <img src="https://play.google.com/intl/es/badges/static/images/badges/es_badge_web_generic.png" alt="Disponible en Google Play" style="height:56px; display:block; border:0;">
            </a>
        </td>
    </tr>
</table>
@else
<p style="margin:0 0 12px;">Puedes acceder al sistema con estos mismos datos.</p>
@include('emails.partials.button', ['url' => url('/login'), 'label' => 'Acceder al sistema', 'brand' => $brand])
@endif

<p style="margin:10px 0 0;">Si tienes alguna duda, no dudes en contactarnos.</p>
@endsection