<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Nueva solicitud de contacto</title>
</head>
<body style="font-family: sans-serif; color: #1f2937; padding: 24px;">
    <h2 style="margin: 0 0 16px;">Nueva solicitud desde la landing</h2>

    <table style="border-collapse: collapse; width: 100%; max-width: 480px;">
        <tr>
            <td style="padding: 6px 0; color: #6b7280;">Gimnasio</td>
            <td style="padding: 6px 0; font-weight: bold;">{{ $solicitud->nombre_gimnasio }}</td>
        </tr>
        <tr>
            <td style="padding: 6px 0; color: #6b7280;">Contacto</td>
            <td style="padding: 6px 0;">{{ $solicitud->nombre_contacto }}</td>
        </tr>
        <tr>
            <td style="padding: 6px 0; color: #6b7280;">Correo</td>
            <td style="padding: 6px 0;">{{ $solicitud->email }}</td>
        </tr>
        @if($solicitud->telefono)
        <tr>
            <td style="padding: 6px 0; color: #6b7280;">Teléfono</td>
            <td style="padding: 6px 0;">{{ $solicitud->telefono }}</td>
        </tr>
        @endif
        <tr>
            <td style="padding: 6px 0; color: #6b7280;">Plan de interés</td>
            <td style="padding: 6px 0;">{{ ucfirst($solicitud->plan) }}</td>
        </tr>
    </table>

    @if($solicitud->mensaje)
        <p style="margin-top: 16px; color: #6b7280;">Mensaje:</p>
        <p style="white-space: pre-line;">{{ $solicitud->mensaje }}</p>
    @endif

    <p style="margin-top: 24px; font-size: 12px; color: #9ca3af;">
        Solicitud #{{ $solicitud->id }} — {{ $solicitud->created_at->format('d-m-Y H:i') }}
    </p>
</body>
</html>
