@extends('emails.layout')

@section('subject', 'Recupera tu contraseña - ' . $brand['display_name'])
@section('title', 'Hola ' . $user->name . '!')

@section('content')
<p style="margin:0 0 16px;">Recibimos una solicitud para restablecer la contraseña de tu cuenta en {{ $brand['display_name'] }}. Toca el siguiente botón para elegir una nueva contraseña directamente en la app:</p>

@include('emails.partials.button', ['url' => $resetUrl, 'label' => 'Restablecer contraseña', 'brand' => $brand])

<p style="margin:14px 0 0;">Este enlace vence en {{ config('auth.passwords.users.expire', 60) }} minutos. Si no solicitaste este cambio, puedes ignorar este correo.</p>
@endsection
