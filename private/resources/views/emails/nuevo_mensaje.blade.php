@component('mail::message')
# ¡Tienes un nuevo mensaje!

Has recibido un nuevo mensaje de **{{ $remitente->name }}**.

@component('mail::button', ['url' => url('/mensajes')])
Revíselo aquí
@endcomponent

Gracias,<br>
{{ config('app.name') }}
@endcomponent