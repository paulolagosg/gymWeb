<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceso bloqueado — Ampaya</title>
    <style>
        body {
            margin: 0;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: #f8fafc;
            color: #1f2937;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 24px;
        }
        .card {
            max-width: 420px;
            background: #fff;
            border-radius: 16px;
            padding: 40px 32px;
            text-align: center;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.06);
        }
        h1 { font-size: 1.25rem; margin: 0 0 12px; }
        p { color: #4b5563; line-height: 1.5; margin: 0 0 24px; }
        .contacto { font-size: 0.9rem; color: #6b7280; }
        .contacto a { color: #489ddf; text-decoration: none; font-weight: 600; }
    </style>
</head>
<body>
    <div class="card">
        <h1>Acceso bloqueado</h1>
        <p>{{ $mensaje }}</p>
        <div class="contacto">
            <a href="mailto:contacto@ampaya.cl">contacto@ampaya.cl</a> ·
            <a href="tel:+56992803469">+56 9 9280 3469</a>
        </div>
    </div>
</body>
</html>
