<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('subject', $brand['display_name'])</title>
</head>

<body style="margin:0; padding:24px 12px; background-color:#f3f4f6; font-family:Arial, Helvetica, sans-serif; color:{{ $brand['text_color'] }};">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="border-collapse:collapse;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:640px; border-collapse:separate; border-spacing:0; background-color:#ffffff; border-radius:24px; overflow:hidden; box-shadow:0 16px 40px rgba(17,24,39,0.10);">
                    <tr>
                        <td style="padding:28px 32px; background:linear-gradient(135deg, {{ $brand['primary_color'] }} 0%, {{ $brand['primary_dark'] }} 100%); color:#ffffff;">
                            <div style="font-size:12px; line-height:1.4; letter-spacing:0.08em; text-transform:uppercase; opacity:0.82;">{{ $brand['email_heading'] }}</div>
                            <div style="margin-top:8px; font-size:28px; line-height:1.15; font-weight:700;">{{ $brand['display_name'] }}</div>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:32px 32px 20px; font-size:15px; line-height:1.65; color:{{ $brand['text_color'] }};">
                            @hasSection('title')
                            <h1 style="margin:0 0 18px; font-size:28px; line-height:1.2; color:{{ $brand['text_color'] }};">@yield('title')</h1>
                            @endif

                            @yield('content')
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:0 32px 32px;">
                            <div style="height:1px; background-color:#e5e7eb; margin-bottom:16px;"></div>
                            <div style="font-size:14px; line-height:1.5; font-weight:700; color:{{ $brand['text_color'] }};">{{ $brand['email_signature'] }}</div>
                            <div style="margin-top:6px; font-size:13px; line-height:1.6; color:{{ $brand['muted_color'] }}; white-space:pre-line;">{{ $brand['email_footer'] }}</div>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>

</html>