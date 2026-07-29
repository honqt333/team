<?php

declare(strict_types=1);

return [
    'errors' => [
        'too_many_attempts' => 'تجاوزت الحد المسموح من المحاولات. حاول بعد بضع دقائق.',
        'unauthenticated' => 'الرجاء تسجيل الدخول للمتابعة.',
        'malformed' => 'رقم الجوال غير صالح.',
    ],
    'auth' => [
        'code_sent' => 'إذا كان رقمك مسجلاً لدينا، فقد أرسلنا لك رمز تحقق. الرجاء التحقق من هاتفك.',
        'code_invalid' => 'رمز التحقق غير صالح أو منتهي الصلاحية.',
    ],
    'login' => [
        'title' => 'بوابة العميل',
        'subtitle' => 'سجّل دخولك برقم الجوال للوصول إلى مركباتك ومواعيدك.',
        'phone_label' => 'رقم الجوال',
        'phone_placeholder' => '+966 5x xxx xxxx',
        'phone_hint' => 'سنرسل رمز تحقق من 6 أرقام إلى جوالك.',
        'submit' => 'إرسال الرمز',
        'back_to_site' => 'العودة إلى الموقع العام',
    ],
    'verify' => [
        'title' => 'أدخل رمز التحقق',
        'subtitle' => 'أرسلنا رمزاً من 6 أرقام إلى هاتفك.',
        'sent_to' => 'تم إرسال الرمز إلى',
        'code_label' => 'رمز التحقق',
        'code_placeholder' => '••••••',
        'submit' => 'تحقق',
        'change_number' => 'استخدام رقم آخر',
        'expiry_notice' => 'الرمز ينتهي خلال 10 دقائق.',
    ],
    'dashboard' => [
        'title' => 'حسابي',
        'greeting' => 'مرحباً، :name',
        'back_to_site' => 'الموقع العام',
        'logout' => 'تسجيل الخروج',
        'vehicles' => 'مركباتي',
        'no_vehicles' => 'لا توجد مركبات مرتبطة بحسابك حتى الآن.',
        'upcoming' => 'المواعيد القادمة',
        'no_upcoming' => 'لا توجد مواعيد قادمة.',
        'until' => 'حتى',
        'recent' => 'النشاط الأخير',
        'no_recent' => 'لا يوجد نشاط حديث على حسابك.',
        'opened' => 'تم الفتح',
        'expected_end' => 'الإنجاز المتوقع',
        'status_scheduled' => 'مجدول',
        'status_confirmed' => 'مؤكد',
        'status_checked_in' => 'تم الدخول',
    ],
];
