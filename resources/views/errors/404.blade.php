@php
    // Get locale from browser Accept-Language or default to Arabic
    $acceptLang = request()->header('Accept-Language', 'ar');
    $locale = str_starts_with($acceptLang, 'en') ? 'en' : 'ar';
    $isRtl = $locale === 'ar';

    $texts = [
        'ar' => [
            'title' => 'الصفحة غير موجودة',
            'description' =>
                'عذراً، الصفحة التي تبحث عنها غير موجودة أو تم نقلها. يرجى التحقق من الرابط أو العودة للرئيسية.',
            'back' => 'رجوع',
            'home' => 'الصفحة الرئيسية',
            'contact' => 'تحتاج مساعدة؟ تواصل مع الدعم الفني',
        ],
        'en' => [
            'title' => 'Page Not Found',
            'description' =>
                "Sorry, the page you are looking for does not exist or has been moved. Please verify the URL or go back to home.",
            'back' => 'Go Back',
            'home' => 'Go Home',
            'contact' => 'Need help? Contact technical support',
        ],
    ];
    $t = $texts[$locale];
@endphp
<!DOCTYPE html>
<html lang="{{ $locale }}" dir="{{ $isRtl ? 'rtl' : 'ltr' }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>404 - {{ $t['title'] }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Cairo', sans-serif;
            min-height: 100vh;
            background: linear-gradient(135deg, #0f0c29 0%, #302b63 50%, #24243e 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }

        .container {
            text-align: center;
            max-width: 500px;
        }

        .icon {
            width: 120px;
            height: 120px;
            margin: 0 auto 32px;
            background: linear-gradient(135deg, rgba(59, 130, 246, 0.2), rgba(6, 182, 212, 0.2));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .icon svg {
            width: 56px;
            height: 56px;
            stroke: #60a5fa;
            stroke-width: 1.5;
            fill: none;
        }

        .code {
            font-size: 120px;
            font-weight: 800;
            line-height: 1;
            margin-bottom: 20px;
            background: linear-gradient(135deg, #60a5fa, #38bdf8);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .title {
            font-size: 24px;
            font-weight: 700;
            color: #fff;
            margin-bottom: 12px;
        }

        .description {
            font-size: 16px;
            color: #a1a1aa;
            margin-bottom: 32px;
            line-height: 1.7;
        }

        .buttons {
            display: flex;
            gap: 12px;
            justify-content: center;
            flex-wrap: wrap;
        }

        .btn {
            padding: 12px 24px;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            border: none;
            font-family: inherit;
            transition: transform 0.2s;
        }

        .btn:hover {
            transform: translateY(-2px);
        }

        .btn svg {
            width: 20px;
            height: 20px;
        }

        .btn-secondary {
            background: rgba(255, 255, 255, 0.1);
            color: #fff;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .btn-primary {
            background: linear-gradient(135deg, #3b82f6, #06b6d4);
            color: #fff;
        }

        .contact {
            margin-top: 40px;
            color: #6b7280;
            font-size: 14px;
        }

        [dir="rtl"] .arrow {
            transform: rotate(180deg);
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <circle cx="11" cy="11" r="8" stroke-width="2" />
                <path d="M21 21l-4.3-4.3" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </div>

        <div class="code">404</div>
        <h1 class="title">{{ $t['title'] }}</h1>
        <p class="description">{{ $t['description'] }}</p>

        <div class="buttons">
            <button onclick="history.back()" class="btn btn-secondary">
                <svg class="arrow" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                {{ $t['back'] }}
            </button>
            <a href="{{ url('/app') }}" class="btn btn-primary">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                </svg>
                {{ $t['home'] }}
            </a>
        </div>
        <p class="contact">{{ $t['contact'] }}</p>
    </div>
</body>

</html>
