<?php
use App\Models\Setting;

// Hero
Setting::set('landing.hero.title.ar', 'مرحباً بك في عصر الإدارة الذكية للورش', 'landing');
Setting::set('landing.hero.title.en', 'Welcome to the Era of Smart Workshop Management', 'landing');
Setting::set('landing.hero.subtitle.ar', 'النظام الأول المعتمد لإدارة مراكز صيانة السيارات. فحص رقمي، فواتير إلكترونية، إدارة المخزون، ونقاط البيع في منصة واحدة متكاملة.', 'landing');
Setting::set('landing.hero.subtitle.en', 'The certified #1 system for car workshop management. Digital inspections, e-invoices, inventory, and POS in one integrated platform.', 'landing');
Setting::set('landing.hero.cta_text.ar', 'اشترك الآن مجاناً', 'landing');
Setting::set('landing.hero.cta_text.en', 'Start Your Free Trial', 'landing');

// Features
Setting::set('landing.features.title.ar', 'لماذا نظام Carag هو خيارك الأفضل؟', 'landing');
Setting::set('landing.features.title.en', 'Why Carag is Your Best Choice?', 'landing');
Setting::set('landing.features.subtitle.ar', 'بنينا النظام خصيصاً ليناسب احتياجات مراكز الصيانة، لنوفر لك أدوات تقنية متطورة تزيد من كفاءة العمل.', 'landing');
Setting::set('landing.features.subtitle.en', 'We built the system specifically for workshop needs, providing advanced tech tools to boost efficiency.', 'landing');

$features = [
    [
        'title_ar' => 'فحص فني رقمي', 'title_en' => 'Digital Inspection',
        'desc_ar' => 'نماذج فحص رقمية احترافية مدعومة بالصور وإرسالها للعميل للموافقة.', 'desc_en' => 'Professional digital inspection templates with photos, sent to clients for approval.'
    ],
    [
        'title_ar' => 'إدارة شاملة للمخزون', 'title_en' => 'Comprehensive Inventory',
        'desc_ar' => 'تتبع قطع الغيار، فواتير المشتريات، وتنبيهات النواقص لحظة بلحظة.', 'desc_en' => 'Track spare parts, purchase invoices, and shortage alerts instantly.'
    ],
    [
        'title_ar' => 'فواتير إلكترونية معتمدة', 'title_en' => 'Certified E-Invoices',
        'desc_ar' => 'متوافق تماماً مع متطلبات هيئة الزكاة والدخل، مع دعم ضريبة القيمة المضافة.', 'desc_en' => 'Fully compliant with ZATCA requirements, with VAT support.'
    ],
    [
        'title_ar' => 'نقاط البيع السريعة', 'title_en' => 'Fast POS',
        'desc_ar' => 'شاشة نقاط بيع سريعة لإصدار الفواتير الفورية بخطوات بسيطة.', 'desc_en' => 'Fast POS screen to issue instant invoices with simple steps.'
    ]
];
Setting::set('landing.features.list', json_encode($features, JSON_UNESCAPED_UNICODE), 'landing');

// Stats
$stats = [
    ['value' => '+500', 'label_ar' => 'ورشة وصناعية', 'label_en' => 'Workshops'],
    ['value' => '+1M', 'label_ar' => 'فاتورة مصدرة', 'label_en' => 'Invoices Issued'],
    ['value' => '+100K', 'label_ar' => 'عميل مسجل', 'label_en' => 'Registered Clients'],
    ['value' => '%99.9', 'label_ar' => 'استقرار النظام', 'label_en' => 'Uptime'],
];
Setting::set('landing.stats.list', json_encode($stats, JSON_UNESCAPED_UNICODE), 'landing');

// Testimonials / Clients
$clients = [
    ['name' => 'AutoCare', 'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/4/44/BMW.svg'],
    ['name' => 'SpeedFix', 'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/a/a1/Volkswagen_Logo_till_1995.svg'],
    ['name' => 'ProGarage', 'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/9/90/Mercedes-Logo.svg'],
    ['name' => 'StarMotors', 'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/1/12/Suzuki_logo_2.svg'],
];
Setting::set('landing.testimonials.list', json_encode($clients, JSON_UNESCAPED_UNICODE), 'landing');

// FAQ
$faq = [
    ['q_ar' => 'هل النظام معتمد من هيئة الزكاة والدخل؟', 'q_en' => 'Is the system ZATCA certified?', 'a_ar' => 'نعم، النظام معتمد بالكامل ويدعم الفوترة الإلكترونية المرحلة الأولى والثانية.', 'a_en' => 'Yes, the system is fully certified and supports e-invoicing phases 1 & 2.'],
    ['q_ar' => 'هل يمكنني إدارة أكثر من فرع؟', 'q_en' => 'Can I manage multiple branches?', 'a_ar' => 'نعم، يدعم النظام إدارة فروع متعددة بصلاحيات منفصلة لكل فرع ومخزون مستقل.', 'a_en' => 'Yes, the system supports managing multiple branches with separate roles and inventory.'],
    ['q_ar' => 'ماذا يحدث إذا انقطع الإنترنت؟', 'q_en' => 'What if the internet disconnects?', 'a_ar' => 'النظام سحابي ويتطلب اتصال بالإنترنت، ولكننا نستخدم خوادم مستقرة تضمن استمرار العمل.', 'a_en' => 'The system is cloud-based and requires internet, but we use stable servers to ensure uptime.'],
];
Setting::set('landing.faq.list', json_encode($faq, JSON_UNESCAPED_UNICODE), 'landing');

// Toggles (Enable all)
Setting::set('landing.features.enabled', '1', 'landing');
Setting::set('landing.pricing.enabled', '1', 'landing');
Setting::set('landing.stats.enabled', '1', 'landing');
Setting::set('landing.faq.enabled', '1', 'landing');
Setting::set('landing.testimonials.enabled', '1', 'landing');
Setting::set('landing.contact.enabled', '1', 'landing');

echo "Landing settings seeded successfully!\n";
