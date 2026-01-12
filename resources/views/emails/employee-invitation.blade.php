<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="utf-8">
    <title>مرحباً بك في الفريق - تفعيل الحساب</title>
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
            border-radius: 12px;
            padding: 40px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
        }

        .header-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #8b5cf6 0%, #6366f1 100%);
            border-radius: 50%;
            margin: 0 auto 20px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .header-icon svg {
            width: 40px;
            height: 40px;
            fill: white;
        }

        h1 {
            color: #111827;
            font-size: 24px;
            margin: 0 0 10px;
        }

        .subtitle {
            color: #6b7280;
            font-size: 16px;
            margin: 0;
        }

        .content {
            color: #374151;
            font-size: 16px;
            line-height: 1.8;
            margin: 20px 0;
        }

        .button-container {
            text-align: center;
            margin: 30px 0;
        }

        .button {
            display: inline-block;
            padding: 14px 32px;
            background: linear-gradient(135deg, #8b5cf6 0%, #6366f1 100%);
            color: #ffffff !important;
            text-decoration: none;
            border-radius: 10px;
            font-weight: bold;
            font-size: 16px;
            box-shadow: 0 4px 15px rgba(99, 102, 241, 0.3);
            transition: transform 0.2s;
        }

        .button:hover {
            transform: translateY(-2px);
        }

        .info-box {
            background-color: #f0fdf4;
            border: 1px solid #bbf7d0;
            border-radius: 8px;
            padding: 16px;
            margin: 20px 0;
            color: #166534;
            font-size: 14px;
        }

        .warning-box {
            background-color: #fffbeb;
            border: 1px solid #fde68a;
            border-radius: 8px;
            padding: 16px;
            margin: 20px 0;
            color: #92400e;
            font-size: 14px;
        }

        .expiry {
            text-align: center;
            color: #9ca3af;
            font-size: 13px;
            margin-top: 20px;
        }

        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
            font-size: 12px;
            color: #9ca3af;
            text-align: center;
        }

        .footer a {
            color: #6366f1;
            text-decoration: none;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <div class="header-icon"
                style="background: linear-gradient(135deg, #8b5cf6 0%, #6366f1 100%); width: 80px; height: 80px; border-radius: 50%; margin: 0 auto 20px; text-align: center; line-height: 80px;">
                <span style="font-size: 36px;">👋</span>
            </div>
            <h1>مرحباً بك في الفريق!</h1>
            <p class="subtitle">تم إنشاء حساب لك في النظام</p>
        </div>

        <div class="content">
            <p>أهلاً <strong>{{ $userName }}</strong>،</p>
            <p>
                تم إضافتك كموظف في نظام <strong>{{ $appName }}</strong>.
                يرجى الضغط على الزر أدناه لتفعيل حسابك وتعيين كلمة المرور الخاصة بك.
            </p>
        </div>

        <div class="button-container">
            <a href="{{ $activationUrl }}" class="button">تفعيل الحساب وتعيين كلمة المرور</a>
        </div>

        <div class="info-box">
            ✨ بعد التفعيل ستتمكن من:
            <ul style="margin: 10px 0 0; padding-right: 20px;">
                <li>الوصول لبوابة الموظف الذاتية</li>
                <li>مشاهدة سجل الحضور والإجازات</li>
                <li>متابعة كشوف الرواتب</li>
            </ul>
        </div>

        <div class="warning-box">
            ⚠️ هذا الرابط صالح لمدة <strong>3 أيام</strong> فقط. إذا انتهت صلاحيته، تواصل مع مسؤول الموارد البشرية.
        </div>

        <p class="expiry">
            🔒 إذا لم تتوقع هذه الدعوة، يرجى تجاهل هذا البريد.
        </p>

        <div class="footer">
            &copy; {{ date('Y') }} {{ $appName }}. جميع الحقوق محفوظة.
            <br>
            <span style="color: #d1d5db;">تم الإرسال بواسطة النظام تلقائياً</span>
        </div>
    </div>
</body>

</html>
