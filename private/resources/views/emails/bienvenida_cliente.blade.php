@component('mail::message')
# ¡Hola {{ $cliente->nombres }}!

Bienvenido/a a Ampaya Gym. Aquí tienes tus credenciales de acceso:

**Correo electrónico:** {{ $cliente->email }}<br>
**Clave:** {{ $pwd }}

Puedes acceder a nuestro sistema utilizando estas credenciales.

@component('mail::button', ['url' => url('/login')])
Acceder al sistema
@endcomponent

Si tienes alguna duda, no dudes en contactarnos.

Saludos cordiales,<br>
**Equipo Ampaya Gym**
@endcomponent