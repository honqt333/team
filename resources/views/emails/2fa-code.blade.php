<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="utf-8">
    <title>رمز التحقق الثنائي</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f3f4f6;
            padding: 20px;
            direction: rtl;
            text-align: right;
        }

        .container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 8px;
            padding: 40px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .code {
            font-size: 32px;
            font-weight: bold;
            letter-spacing: 5px;
            color: #4f46e5;
            text-align: center;
            margin: 30px 0;
            padding: 20px;
            background-color: #eef2ff;
            border-radius: 8px;
            border: 1px dashed #c7d2fe;
        }

        .footer {
            margin-top: 30px;
            font-size: 12px;
            color: #6b7280;
            text-align: center;
        }
    </style>
</head>

<body>
    <div class="container">
        <h2 style="color: #111827; margin-bottom: 20px;">رمز التحقق الثنائي</h2>

        <p style="color: #374151; font-size: 16px; line-height: 1.5;">
            مرحباً،
            <br>
            يرجى استخدام الرمز التالي لإكمال عملية التحقق:
        </p>

        <div class="code">{{ $code }}</div>

        <p style="color: #374151; font-size: 14px;">
            هذا الرمز صالح لمدة 10 دقائق. لا تشارك هذا الرمز مع أي شخص.
        </p>

        <div class="footer">
            &copy; {{ date('Y') }} {{ config('app.name') }}. جميع الحقوق محفوظة.
        </div>
    </div>
</body>

</html>
