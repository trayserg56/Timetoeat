<!DOCTYPE html>
<html lang="ru">
    <head>
        <meta charset="utf-8">
        <title>Код для сброса пароля</title>
    </head>
    <body style="margin:0;padding:24px;background:#f5f5f4;font-family:Arial,sans-serif;color:#1c1917;">
        <div style="max-width:560px;margin:0 auto;background:#ffffff;border-radius:24px;padding:32px;box-shadow:0 20px 60px rgba(28,25,23,0.08);">
            <p style="margin:0 0 12px;font-size:12px;font-weight:700;letter-spacing:0.22em;text-transform:uppercase;color:#c2410c;">
                Food Delivery
            </p>
            <h1 style="margin:0 0 16px;font-size:30px;line-height:1.1;font-weight:900;">
                Код для сброса пароля
            </h1>
            <p style="margin:0 0 24px;font-size:16px;line-height:1.6;color:#57534e;">
                Введите этот код на сайте, чтобы задать новый пароль. Код действует {{ $expiresInMinutes }} мин.
            </p>
            <div style="margin:0 0 24px;padding:18px 24px;border-radius:18px;background:#fff7ed;border:1px solid #fdba74;font-size:32px;font-weight:900;letter-spacing:0.32em;text-align:center;color:#9a3412;">
                {{ $code }}
            </div>
            <p style="margin:0;font-size:14px;line-height:1.6;color:#78716c;">
                Если вы не запрашивали смену пароля, просто проигнорируйте это письмо.
            </p>
        </div>
    </body>
</html>
