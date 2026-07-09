@php
    // Get locale from browser Accept-Language or default to Arabic
    $acceptLang = request()->header('Accept-Language', 'ar');
    $locale = str_starts_with($acceptLang, 'en') ? 'en' : 'ar';
    $isRtl = $locale === 'ar';

    $texts = [
        'ar' => [
            'title' => 'النظام قيد الصيانة المؤقتة',
            'description' =>
                'نعمل حالياً على إجراء بعض التحديثات والتحسينات لتقديم تجربة أفضل. سنعود للخدمة خلال دقائق معدودة. شكراً لتفهمكم وصبركم.',
            'back' => 'تحديث الصفحة',
            'home' => 'الرئيسية',
            'contact' => 'تحتاج مساعدة عاجلة؟ تواصل مع الدعم الفني',
        ],
        'en' => [
            'title' => 'Under Maintenance',
            'description' =>
                "We are currently performing scheduled maintenance and updates to improve our service. We will be back online shortly. Thank you for your patience.",
            'back' => 'Refresh Page',
            'home' => 'Home',
            'contact' => 'Need urgent help? Contact technical support',
        ],
    ];
    $t = $texts[$locale];
@endphp
<!DOCTYPE html>
<html lang="{{ $locale }}" dir="{{ $isRtl ? 'rtl' : 'ltr' }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>503 - {{ $t['title'] }}</title>
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
            background: linear-gradient(135deg, rgba(139, 92, 246, 0.2), rgba(99, 102, 241, 0.2));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .icon svg {
            width: 56px;
            height: 56px;
            stroke: #a78bfa;
            stroke-width: 1.5;
            fill: none;
        }

        .code {
            font-size: 120px;
            font-weight: 800;
            line-height: 1;
            margin-bottom: 20px;
            background: linear-gradient(135deg, #a78bfa, #818cf8);
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
            background: linear-gradient(135deg, #8b5cf6, #6366f1);
            color: #fff;
        }

        .contact {
            margin-top: 40px;
            color: #6b7280;
            font-size: 14px;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <path d="M14.7 6.3a1 1 0 000 1.4l1.6 1.6a1 1 0 001.4 0l3.77-3.77a6 6 0 01-7.94 7.94l-6.91 6.91a2.12 2.12 0 01-3-3l6.91-6.91a6 6 0 017.94-7.94l-3.76 3.76z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </div>

        <div class="code">503</div>
        <h1 class="title">{{ $t['title'] }}</h1>
        <p class="description">{{ $t['description'] }}</p>

        <div class="buttons">
            <button onclick="window.location.reload()" class="btn btn-primary">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M4 4v5h.582m15.356 2A8.001 8.001 0 1121.21 8H18.2" />
                </svg>
                {{ $t['back'] }}
            </button>
            <a href="{{ url('/') }}" class="btn btn-secondary">
                {{ $t['home'] }}
            </a>
        </div>
        <p class="contact">{{ $t['contact'] }}</p>
    </div>
</body>

</html>
