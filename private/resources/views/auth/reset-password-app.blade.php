<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Recuperar contraseña - Ampaya Gym</title>
    @php
        $deepLinkUrl = $scheme . '://reset-password?token=' . urlencode($token) . '&email=' . urlencode($email);
    @endphp
    @if($token && $email)
        <meta http-equiv="refresh" content="0; url={{ $deepLinkUrl }}">
    @endif
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            background: #f4f4f5;
            margin: 0;
            padding: 32px 16px;
            text-align: center;
            color: #1f2937;
        }
        .card {
            max-width: 420px;
            margin: 0 auto;
            background: #fff;
            border-radius: 16px;
            padding: 32px 24px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.08);
        }
        h1 { font-size: 20px; margin: 0 0 12px; }
        p { font-size: 14px; line-height: 1.6; color: #4b5563; }
        .btn {
            display: inline-block;
            margin-top: 16px;
            padding: 14px 28px;
            background: #2d3748;
            color: #fff;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
        }
        .error { color: #dc2626; }
    </style>
</head>
<body>
    <div class="card">
        @if($token && $email)
            <h1>Abriendo Ampaya Gym...</h1>
            <p>Si la app no se abre automáticamente, toca el botón.</p>
            <a class="btn" href="{{ $deepLinkUrl }}">Abrir en la app</a>
        @else
            <h1 class="error">Enlace inválido</h1>
            <p>Este enlace de recuperación no es válido o está incompleto. Solicita uno nuevo desde la app.</p>
        @endif

        <p>¿Aún no tienes la app instalada? Contacta a tu gimnasio para más información.</p>
    </div>

    @if($token && $email)
    <script>
        window.location.href = @json($deepLinkUrl);
    </script>
    @endif
</body>
</html>
