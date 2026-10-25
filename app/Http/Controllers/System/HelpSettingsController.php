<?php

declare(strict_types=1);

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class HelpSettingsController extends Controller
{
    /**
     * Default quick-start items.
     */
    public const DEFAULT_QUICK_START = [
        [
            'id' => 'qs1',
            'title' => 'فتح كرت عمل جديد',
            'desc' => 'خطوات استلام سيارة العميل، وإضافة الخدمات والقطع وتحديد تاريخ التسليم المتوقع.',
            'bg' => 'bg-blue-50 dark:bg-blue-900/30',
            'text' => 'text-blue-600 dark:text-blue-400',
            'targetCategory' => 'work_orders',
            'icon_name' => 'document-text',
        ],
        [
            'id' => 'qs2',
            'title' => 'لوحة الورشة ومؤقتات الفنيين',
            'desc' => 'تشغيل مؤقتات العمل للفنيين، تتبع مراحل الصيانة، ومراقبة الورشة على الشاشة الكبيرة.',
            'bg' => 'bg-emerald-50 dark:bg-emerald-900/30',
            'text' => 'text-emerald-600 dark:text-emerald-400',
            'targetCategory' => 'workshop',
            'icon_name' => 'bolt',
        ],
        [
            'id' => 'qs3',
            'title' => 'الربط مع هيئة الزكاة (ZATCA)',
            'desc' => 'إصدار الفاتورة الضريبية المبسطة المتوافقة 100% مع المرحلة الثانية ورمز QR.',
            'bg' => 'bg-amber-50 dark:bg-amber-900/30',
            'text' => 'text-amber-600 dark:text-amber-400',
            'targetCategory' => 'billing',
            'icon_name' => 'check-badge',
        ],
        [
            'id' => 'qs4',
            'title' => 'بوابة العميل الذكية',
            'desc' => 'كيفية دخول العميل برقم جواله، حجز المواعيد، وتتبع حالة الصيانة خطوة بخطوة.',
            'bg' => 'bg-purple-50 dark:bg-purple-900/30',
            'text' => 'text-purple-600 dark:text-purple-400',
            'targetCategory' => 'portal',
            'icon_name' => 'user-circle',
        ],
    ];

    /**
     * Default video tutorials.
     */
    public const DEFAULT_VIDEOS = [
        [
            'id' => 'v1',
            'title' => 'جولة سريعة في النظام وضبط المركز',
            'category' => 'الأساسيات',
            'duration' => '05:20',
            'video_url' => '',
            'description' => 'نظرة شاملة على لوحة التحكم الرئيسية، الإعدادات، وإضافة بيانات المركز ومعلومات التواصل.',
        ],
        [
            'id' => 'v2',
            'title' => 'دورة حياة كرت العمل من الدخول حتى الخروج',
            'category' => 'كروت العمل',
            'duration' => '07:45',
            'video_url' => '',
            'description' => 'شرح تفصيلي لإنشاء كرت عمل، إضافة أجور اليد وقطع الغيار، الفحص المبدئي، واعتماد الجودة.',
        ],
        [
            'id' => 'v3',
            'title' => 'إدارة الورشة الحية ومؤقتات الفنيين',
            'category' => 'لوحة الورشة',
            'duration' => '04:30',
            'video_url' => '',
            'description' => 'كيف يضغط الفني على بدء العمل وإيقافه من جهازه، وكيف تظهر الإنتاجية على لوحة الكانبان.',
        ],
        [
            'id' => 'v4',
            'title' => 'الفوترة الإلكترونية والربط مع هيئة الزكاة',
            'category' => 'الفوترة والضريبة',
            'duration' => '06:15',
            'video_url' => '',
            'description' => 'خطوات إصدار الفاتورة الضريبية، السداد عبر مدى والنقد والآجل، ومطابقة متطلبات هيئة الزكاة.',
        ],
        [
            'id' => 'v5',
            'title' => 'إدارة المخزون وقطع الغيار وأوامر الشراء',
            'category' => 'المخزون',
            'duration' => '08:10',
            'video_url' => '',
            'description' => 'إضافة المنتجات، تتبع الأرصدة بالمستودعات، ربط القطع بكروت العمل، وإصدار أوامر الشراء للموردين.',
        ],
        [
            'id' => 'v6',
            'title' => 'بوابة العميل الذكية والحجز الذاتي',
            'category' => 'بوابة العميل',
            'duration' => '03:50',
            'video_url' => '',
            'description' => 'تجربة العميل من الجوال: طلب حجز موعد صيانة، تلقي رمز التحقق OTP، ومتابعة جاهزية السيارة.',
        ],
    ];

    /**
     * Default knowledge base categories.
     */
    public const DEFAULT_CATEGORIES = [
        ['id' => 'work_orders', 'name' => 'كروت العمل والخدمات'],
        ['id' => 'workshop', 'name' => 'لوحة الورشة والفنيين'],
        ['id' => 'portal', 'name' => 'بوابة العميل الذكية'],
        ['id' => 'billing', 'name' => 'الفوترة وضريبة ZATCA'],
        ['id' => 'inventory', 'name' => 'المخزون وقطع الغيار'],
        ['id' => 'customers', 'name' => 'العملاء وسجل المركبات'],
        ['id' => 'appointments', 'name' => 'المواعيد والبايات'],
    ];

    /**
     * Default topics.
     */
    public const DEFAULT_TOPICS = [
        [
            'id' => 1,
            'category' => 'work_orders',
            'stepNumber' => '01',
            'title' => 'كيف تنشئ كرت عمل جديد للمركبة؟',
            'summary' => 'خطوات فتح أمر شغل رسمي واستلام السيارة في المركز.',
            'steps' => [
                'توجه إلى قائمة كروت العمل (/app/work-orders) واضغط على زر "كرت عمل جديد".',
                'اختر العميل بالاسم أو رقم الجوال، أو اضغط "إضافة عميل جديد" للتسجيل الفوري.',
                'اختر المركبة التابعة للعميل وحدد قراءة العداد الحالي (الكيلومترات).',
                'أضف شكوى العميل وملاحظات الفحص المبدئي وأرفق صور المركبة إن وجدت.',
                'حدد تاريخ ووقت التسليم المتوقع ثم اضغط "حفظ وإنشاء الكرت".',
            ],
            'tip' => 'يمكنك إرفاق صور أضرار المركبة وتوثيق الخدوش عبر ميزة فحص المركبة قبل بدء الصيانة.',
            'link' => ['label' => 'الانتقال إلى كروت العمل', 'url' => '/app/work-orders'],
        ],
        [
            'id' => 2,
            'category' => 'work_orders',
            'stepNumber' => '02',
            'title' => 'إضافة الخدمات وقطع الغيار إلى كرت العمل',
            'summary' => 'تسعير أجور اليد وربط القطع المصروفة من المستودع.',
            'steps' => [
                'من صفحة كرت العمل، انتقل إلى تبويب "الخدمات والقطع".',
                'اضغط على "إضافة خدمة"، واختر القسم المعني (ميكانيكا، كهرباء، صيانة سريعة).',
                'حدد السعر والكمية والخصم إن وُجد.',
                'في خانة قطع الغيار، اختر القطعة المصروفة من المستودع؛ سيتم خصمها من المخزون تلقائياً عند اعتماد الكرت.',
            ],
            'tip' => 'يمكنك توزيع بنود كرت العمل على باقات جاهزة بضغطة زر لتسريع العملية.',
            'link' => null,
        ],
        [
            'id' => 3,
            'category' => 'work_orders',
            'stepNumber' => '03',
            'title' => 'فحص الجودة (QC) وتسليم المركبة',
            'summary' => 'التحقق من اكتمال كافة البنود واعتماد خروج السيارة.',
            'steps' => [
                'بعد انتهاء الفنيين من كافة البنود، تتحول حالة الكرت إلى "جاهز لفحص الجودة".',
                'يقوم مشرف الورشة بمراجعة بنود الفحص واعتمادها أو إعادتها مع كتابة الملاحظة.',
                'عند الاعتماد، يُتاح إصدار الفاتورة النهائية وإغلاق الكرت لخروج السيارة.',
            ],
            'tip' => null,
            'link' => null,
        ],
        [
            'id' => 10,
            'category' => 'workshop',
            'stepNumber' => '01',
            'title' => 'استخدام لوحة كانبان للورشة ومتابعة الفنيين',
            'summary' => 'إدارة العمليات الميدانية على الشاشة الكبيرة داخل صالة الصيانة.',
            'steps' => [
                'ادخل إلى مسار لوحة الورشة (/app/workshop).',
                'تظهر الأعمدة الأربعة للعمليات: قيد الانتظار، جاري العمل، معلق، وجاهز لفحص الجودة.',
                'يعرض أعلى اللوحة ملخص المواعيد القادمة اليوم وزر التسجيل السريع (Check-in).',
                'تتحدث الكروت تلقائياً وتُظهر اسم الفني الفعال والوقت المنقضي.',
            ],
            'tip' => null,
            'link' => ['label' => 'فتح لوحة الورشة الآن', 'url' => '/app/workshop'],
        ],
        [
            'id' => 11,
            'category' => 'workshop',
            'stepNumber' => '02',
            'title' => 'تسجيل ساعات العمل بمؤقت الفني (Labor Timers)',
            'summary' => 'حساب الإنتاجية الحقيقية لكل فني عبر أزرار البدء والتوقف.',
            'steps' => [
                'يفتح الفني حسابه ويختار البند المكلف به.',
                'يضغط على زر "بدء العمل" لبدء عداد الثواني.',
                'في حال الراحة أو انتظار قطعة الغيار، يضغط على "إيقاف مؤقت".',
                'عند إنهاء الإصلاح يضغط "إنهاء العمل"، فيتم حفظ مجموع الساعات الفعالة آلياً في تقرير الفني.',
            ],
            'tip' => 'يمنع النظام الفني من تشغيل مؤقتين متزامنين في وقت واحد لمنع التضارب وحساب الإنتاجية بدقة.',
            'link' => null,
        ],
        [
            'id' => 20,
            'category' => 'portal',
            'stepNumber' => '01',
            'title' => 'كيف يدخل العميل إلى بوابته الخاصة؟',
            'summary' => 'الدخول الآمن برقم الجوال عبر رمز التحقق OTP دون الحاجة لكلمة مرور.',
            'steps' => [
                'يزور العميل الرابط المخصص للمركز: /{tenant_slug}/portal.',
                'يدخل رقم جواله المسجل في النظام، ويتلقى رمز تحقق OTP مكون من 4 أرقام عبر SMS.',
                'بعد التحقق، تفتح لوحة تحكم مخصصة تعرض سياراته ومواعيده السابقة والحالية.',
            ],
            'tip' => 'بوابة العميل معزولة أمنياً عن لوحة موظفي المركز وتعمل بسلاسة على شاشات الجوال.',
            'link' => null,
        ],
        [
            'id' => 21,
            'category' => 'portal',
            'stepNumber' => '02',
            'title' => 'حجز موعد صيانة جديد وإعادة الجدولة ذاتياً',
            'summary' => 'تمكين العميل من اختيار الفرع والخدمات والموعد المناسب له.',
            'steps' => [
                'يضغط العميل على زر "حجز موعد صيانة".',
                'يختار مركبته، والفرع الأقرب، والخدمات المطلوبة.',
                'يختار التاريخ والوقت المتاح وفق الطاقة الاستيعابية للبايات.',
                'يحصل العميل على إشعار فوري برقم الحجز مع إمكانية تعديل الموعد أو إلغائه من نفس الصفحة.',
            ],
            'tip' => null,
            'link' => null,
        ],
        [
            'id' => 30,
            'category' => 'billing',
            'stepNumber' => '01',
            'title' => 'إصدار الفاتورة الضريبية وتحصيل المبالغ',
            'summary' => 'إصدار الفاتورة الضريبية المبسطة المتوافقة مع متطلبات هيئة الزكاة والضريبة والجمارك.',
            'steps' => [
                'من صفحة كرت العمل المنجز، اضغط على زر "إصدار فاتورة".',
                'يقوم النظام بحساب ضريبة القيمة المضافة 15% تلقائياً وتوليد رمز الاستجابة السريعة (QR Code).',
                'اختر طريقة الدفع: نقدي، مدى، بطاقة ائتمانية، تحويل بنكي، أو ذمم مدينة (آجل).',
                'اطبع الفاتورة بالشكل الحراري السريع (Thermal Print) أو مقاس A4 المعتمد.',
            ],
            'tip' => 'يدعم النظام طباعة الباركود المشفر المتوافق مع تطبيق التحقق الرسمي لهيئة الزكاة (ZATCA).',
            'link' => null,
        ],
        [
            'id' => 40,
            'category' => 'inventory',
            'stepNumber' => '01',
            'title' => 'إدارة المستودعات وجرد قطع الغيار',
            'summary' => 'متابعة حركة المخزون، تنبيهات النواقص، والتسويات المخزنية.',
            'steps' => [
                'من قائمة المخزون (/app/inventory/stock)، استعرض كافة القطع وأرصدتها في كل مستودع.',
                'يمكنك إنشاء أمر شراء جديد للمورد عند وصول القطعة إلى حد الطلب الأدنى.',
                'عند استلام البضائع، سجل مذكرة استلام بضاعة (GRN) ليتم تحديث الأرصدة تلقائياً.',
            ],
            'tip' => null,
            'link' => null,
        ],
        [
            'id' => 50,
            'category' => 'appointments',
            'stepNumber' => '01',
            'title' => 'جدولة البايات (الرافعات) وإدارة السعة',
            'summary' => 'منع التعارضات وحساب السعة اليومية لرافعات المركز.',
            'steps' => [
                'عرف رافعات المركز وبقع الخدمة من صفحة البايات (/app/service-bays).',
                'عند حجز أي موعد، يتحقق النظام من خلو الباي والفني والمركبة في الوقت المحدد.',
                'عند حضور العميل، اضغط على "تسجيل وصول" (Check-in)، ثم "تحويل إلى كرت عمل" بضغطة زر واحدة.',
            ],
            'tip' => null,
            'link' => null,
        ],
    ];

    /**
     * Default FAQs.
     */
    public const DEFAULT_FAQS = [
        [
            'id' => 'faq1',
            'q' => 'هل يمكن تشغيل لوحة الورشة على شاشة تلفاز معلقة داخل المركز؟',
            'a' => 'نعم بالتأكيد! صُممت لوحة الورشة (/app/workshop) لتكون متجاوبة وتعمل بوضوح تام على الشاشات الكبيرة في صالة الصيانة وتتحدث فورياً.',
        ],
        [
            'id' => 'faq2',
            'q' => 'كيف أربط الفواتير بمتطلبات هيئة الزكاة والضريبة والجمارك (ZATCA)؟',
            'a' => 'النظام يدعم المرحلة الأولى والمرحلة الثانية، ويولد QR Code مشفر يحتوي على اسم المنشأة والرقم الضريبي والتوقيت والمجموع والضريبة تلقائياً.',
        ],
        [
            'id' => 'faq3',
            'q' => 'هل يستطيع الفني العمل على أكثر من سيارة في نفس اللحظة؟',
            'a' => 'لا، يمنع النظام تشغيل مؤقتين نشطين في نفس الوقت للفني الواحد؛ يجب عليه إيقاف العمل مؤقتاً على السيارة الأولى قبل بدء العمل على الثانية لضمان دقة احتساب الإنتاجية.',
        ],
        [
            'id' => 'faq4',
            'q' => 'كيف يتلقى العميل رمز الـ OTP لدخول بوابته الذكية؟',
            'a' => 'يدخل العميل رقم جواله في بوابة العميل، ويصله الرمز السداسي عبر رسالة SMS فورية على هاتفه المسجل للدخول الآمن دون الحاجة لتذكر أي كلمة مرور.',
        ],
    ];

    /**
     * Display the Help Center settings management page.
     */
    public function index(): Response
    {
        return Inertia::render('System/Help/Index', [
            'settings' => [
                'contact_whatsapp' => Setting::get('help.contact.whatsapp', '+966 555 785 658'),
                'contact_phone' => Setting::get('help.contact.phone', '920000000'),
                'contact_email' => Setting::get('help.contact.email', 'support@carag.sa'),
                'contact_hours' => Setting::get('help.contact.hours', 'السبت - الخميس: 8:00 ص - 10:00 م'),
                'contact_whatsapp_enabled' => Setting::get('help.contact.whatsapp_enabled', '1') === '1',
                'contact_ticket_url' => Setting::get('help.contact.ticket_url', ''),

                'quick_start_items' => json_decode((string) Setting::get('help.quick_start_items'), true) ?: self::DEFAULT_QUICK_START,
                'video_tutorials' => json_decode((string) Setting::get('help.video_tutorials'), true) ?: self::DEFAULT_VIDEOS,
                'categories' => json_decode((string) Setting::get('help.categories'), true) ?: self::DEFAULT_CATEGORIES,
                'topics' => json_decode((string) Setting::get('help.topics'), true) ?: self::DEFAULT_TOPICS,
                'faqs' => json_decode((string) Setting::get('help.faqs'), true) ?: self::DEFAULT_FAQS,
            ],
        ]);
    }

    /**
     * Update Help Center settings.
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'contact_whatsapp' => 'nullable|string|max:50',
            'contact_phone' => 'nullable|string|max:50',
            'contact_email' => 'nullable|email|max:100',
            'contact_hours' => 'nullable|string|max:150',
            'contact_whatsapp_enabled' => 'nullable|boolean',
            'contact_ticket_url' => 'nullable|string|max:255',

            'quick_start_items' => 'nullable|array',
            'video_tutorials' => 'nullable|array',
            'categories' => 'nullable|array',
            'topics' => 'nullable|array',
            'faqs' => 'nullable|array',
        ]);

        if (array_key_exists('contact_whatsapp', $validated)) {
            Setting::set('help.contact.whatsapp', (string) ($validated['contact_whatsapp'] ?? ''), 'help');
        }

        if (array_key_exists('contact_phone', $validated)) {
            Setting::set('help.contact.phone', (string) ($validated['contact_phone'] ?? ''), 'help');
        }

        if (array_key_exists('contact_email', $validated)) {
            Setting::set('help.contact.email', (string) ($validated['contact_email'] ?? ''), 'help');
        }

        if (array_key_exists('contact_hours', $validated)) {
            Setting::set('help.contact.hours', (string) ($validated['contact_hours'] ?? ''), 'help');
        }

        if (array_key_exists('contact_whatsapp_enabled', $validated)) {
            Setting::set('help.contact.whatsapp_enabled', ! empty($validated['contact_whatsapp_enabled']) ? '1' : '0', 'help');
        }

        if (array_key_exists('contact_ticket_url', $validated)) {
            Setting::set('help.contact.ticket_url', (string) ($validated['contact_ticket_url'] ?? ''), 'help');
        }

        if (array_key_exists('quick_start_items', $validated)) {
            Setting::set('help.quick_start_items', json_encode($validated['quick_start_items'], JSON_UNESCAPED_UNICODE), 'help');
        }

        if (array_key_exists('video_tutorials', $validated)) {
            Setting::set('help.video_tutorials', json_encode($validated['video_tutorials'], JSON_UNESCAPED_UNICODE), 'help');
        }

        if (array_key_exists('categories', $validated)) {
            Setting::set('help.categories', json_encode($validated['categories'], JSON_UNESCAPED_UNICODE), 'help');
        }

        if (array_key_exists('topics', $validated)) {
            Setting::set('help.topics', json_encode($validated['topics'], JSON_UNESCAPED_UNICODE), 'help');
        }

        if (array_key_exists('faqs', $validated)) {
            Setting::set('help.faqs', json_encode($validated['faqs'], JSON_UNESCAPED_UNICODE), 'help');
        }

        return redirect()->route('system.settings.help')->with('success', 'تم حفظ إعدادات ومحتوى صفحة المساعدة بنجاح.');
    }

    /**
     * Reset all Help Center settings to defaults.
     */
    public function resetDefaults(): RedirectResponse
    {
        Setting::set('help.contact.whatsapp', '+966 555 785 658', 'help');
        Setting::set('help.contact.phone', '920000000', 'help');
        Setting::set('help.contact.email', 'support@carag.sa', 'help');
        Setting::set('help.contact.hours', 'السبت - الخميس: 8:00 ص - 10:00 م', 'help');
        Setting::set('help.contact.whatsapp_enabled', '1', 'help');
        Setting::set('help.contact.ticket_url', '', 'help');

        Setting::set('help.quick_start_items', json_encode(self::DEFAULT_QUICK_START, JSON_UNESCAPED_UNICODE), 'help');
        Setting::set('help.video_tutorials', json_encode(self::DEFAULT_VIDEOS, JSON_UNESCAPED_UNICODE), 'help');
        Setting::set('help.categories', json_encode(self::DEFAULT_CATEGORIES, JSON_UNESCAPED_UNICODE), 'help');
        Setting::set('help.topics', json_encode(self::DEFAULT_TOPICS, JSON_UNESCAPED_UNICODE), 'help');
        Setting::set('help.faqs', json_encode(self::DEFAULT_FAQS, JSON_UNESCAPED_UNICODE), 'help');

        return redirect()->route('system.settings.help')->with('success', 'تمت استعادة الإعدادات الافتراضية لصفحة المساعدة بنجاح.');
    }

    /**
     * Fetch video metadata (duration, title) automatically from a video URL.
     */
    public function fetchVideoMeta(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'url' => 'required|url',
        ]);

        $url = trim($validated['url']);
        $durationSeconds = null;
        $title = null;

        try {
            // 1. YouTube
            if (preg_match('/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=|shorts\/|live\/)|youtu\.be\/)([a-zA-Z0-9_-]{11})/i', $url, $m)) {
                $videoId = $m[1];
                $ytUrl = "https://www.youtube.com/watch?v={$videoId}";
                $response = Http::timeout(6)
                    ->withHeaders([
                        'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                        'Accept-Language' => 'ar,en-US;q=0.9,en;q=0.8',
                    ])
                    ->get($ytUrl);

                if ($response->successful()) {
                    $body = $response->body();

                    // Match lengthSeconds
                    if (preg_match('/"lengthSeconds":"(\d+)"/', $body, $secMatch)) {
                        $durationSeconds = (int) $secMatch[1];
                    } elseif (preg_match('/"approxDurationMs":"(\d+)"/', $body, $msMatch)) {
                        $durationSeconds = (int) round(((int) $msMatch[1]) / 1000);
                    }

                    // Match title
                    if (preg_match('/<title>(.*?)<\/title>/', $body, $titleMatch)) {
                        $rawTitle = html_entity_decode($titleMatch[1], ENT_QUOTES | ENT_HTML5, 'UTF-8');
                        $title = trim(preg_replace('/ - YouTube$/i', '', $rawTitle));
                    }
                }
            }
            // 2. Vimeo
            elseif (preg_match('/vimeo\.com\/(?:channels\/(?:\w+\/)?|groups\/[^\/]*\/videos\/|album\/\d+\/video\/|video\/|)(\d+)/i', $url, $vm)) {
                $vimeoId = $vm[1];
                $vimeoResp = Http::timeout(6)->get("https://vimeo.com/api/v2/video/{$vimeoId}.json");

                if ($vimeoResp->successful()) {
                    $data = $vimeoResp->json();

                    if (! empty($data[0]['duration'])) {
                        $durationSeconds = (int) $data[0]['duration'];
                    }

                    if (! empty($data[0]['title'])) {
                        $title = (string) $data[0]['title'];
                    }
                }
            }
        } catch (Throwable $e) {
            // Gracefully ignore network errors
        }

        if ($durationSeconds !== null && $durationSeconds >= 0) {
            $hours = floor($durationSeconds / 3600);
            $minutes = floor(($durationSeconds % 3600) / 60);
            $seconds = $durationSeconds % 60;

            if ($hours > 0) {
                $formattedDuration = sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
            } else {
                $formattedDuration = sprintf('%02d:%02d', $minutes, $seconds);
            }

            return response()->json([
                'success' => true,
                'duration' => $formattedDuration,
                'duration_seconds' => $durationSeconds,
                'title' => $title,
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'تعذر استخراج مدة الفيديو تلقائياً من هذا الرابط، يمكنك إدخالها يدوياً.',
            'title' => $title,
        ], 200);
    }
}
