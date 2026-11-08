/**
 * The English half for the NEW translation keys of the /system pages.
 *
 * The key is `<file relative to Pages/System> || <arabic value>` and
 * the value is the English copy.
 *
 * WHY A DICTIONARY AND NOT A MACHINE TRANSLATION
 *
 * These strings are read by a paying administrator looking at a
 * finance or credit screen. A wrong word here is a support ticket,
 * so every entry was written against the line it belongs to.
 *
 * The English for a bilingual pair or a ternary is taken FROM THE
 * FILE — the codebase already carries the English next to the Arabic.
 * This dictionary only covers what the file does not carry.
 *
 * PLACEHOLDERS
 *
 * `{name}` style placeholders must be IDENTICAL in ar.json and
 * en.json, or vue-i18n renders the placeholder literally in one of
 * the two languages.
 */

export const TRANSLATIONS = {
    // ── AdminUsers / ActivityLog ───────────────────────────────────────────
    'AdminUsers/ActivityLog.vue||كل المسؤولين': 'All administrators',
    'AdminUsers/ActivityLog.vue||كل الإجراءات': 'All actions',

    // ── Announcements / Show ───────────────────────────────────────────────
    'Announcements/Show.vue||🚀 نشر الآن': '🚀 Publish now',
    'Announcements/Show.vue||المستهدفين': 'Recipients',
    'Announcements/Show.vue||شاهدوا': 'Viewed',
    'Announcements/Show.vue||تم الإرسال': 'Delivered',
    'Announcements/Show.vue||فشل': 'Failed',
    'Announcements/Show.vue||القنوات:': 'Channels:',
    'Announcements/Show.vue||ينتهي:': 'Ends:',
    'Announcements/Show.vue||سجل الإرسال': 'Delivery log',
    'Announcements/Show.vue||لم يتم الإرسال بعد': 'Not sent yet',

    // ── Bank transfer receipts ─────────────────────────────────────────────
    'BankTransferReceipts/Index.vue||⚠️ ناقص ': '⚠️ Short by ',
    'BankTransferReceipts/Show.vue||سيُسجَّل نقص ': 'A shortfall of ',
    'BankTransferReceipts/Show.vue|| SAR كرصيد (الفاتورة تبقى مفتوحة)':
        ' SAR will be recorded as credit (the invoice stays open)',
    'BankTransferReceipts/Show.vue||المبلغ المُحوّل أقل من المطلوب بـ ':
        'The transferred amount is less than required by ',
    'BankTransferReceipts/Show.vue||المبلغ المُحوّل أكثر من المطلوب بـ ':
        'The transferred amount is more than required by ',
    'BankTransferReceipts/Show.vue|| SAR (سيُسجَّل كرصيد دائن)':
        ' SAR (it will be recorded as credit)',

    // ── Communication / Templates ──────────────────────────────────────────
    'Communication/Templates/Edit.vue||تم نسخ ': 'Copied ',

    // ── Credits / SMS ──────────────────────────────────────────────────────
    'Credits/SmsBalances.vue||رصيد الرسائل النصية لكل مستأجر': 'SMS credit balance per tenant',
    'Credits/SmsBalances.vue||+ إضافة رصيد يدوي': '+ Add manual credit',
    'Credits/SmsBalances.vue||إجمالي المشتريات': 'Total purchases',
    'Credits/SmsBalances.vue||إجمالي المستخدم': 'Total used',
    'Credits/SmsBalances.vue||الرصيد الحالي': 'Current balance',
    'Credits/SmsBalances.vue||بحث باسم المستأجر...': 'Search by tenant name...',
    'Credits/SmsBalances.vue||المستأجر': 'Tenant',
    'Credits/SmsBalances.vue||+ إضافة رصيد': '+ Add credit',
    'Credits/SmsBalances.vue||إضافة رصيد SMS': 'Add SMS credit',
    'Credits/SmsBalances.vue||اختر المستأجر': 'Select the tenant',
    'Credits/SmsBalances.vue||عدد الرسائل': 'Message count',
    'Credits/SmsBalances.vue||السبب (اختياري)': 'Reason (optional)',
    'Credits/SmsBalances.vue||مثال: هدية ترحيبية': 'e.g. Welcome bonus',

    'Credits/SmsPackages.vue||باقات رصيد SMS': 'SMS credit packages',
    'Credits/SmsPackages.vue||إدارة باقات الرسائل النصية المتاحة للشراء':
        'Manage the SMS packages available for purchase',
    'Credits/SmsPackages.vue||+ إضافة باقة': '+ Add package',
    'Credits/SmsPackages.vue||الأكثر مبيعاً': 'Best seller',
    'Credits/SmsPackages.vue||معطّل': 'Disabled',
    'Credits/SmsPackages.vue||رسالة': 'message',
    'Credits/SmsPackages.vue||ر.س / رسالة': 'SAR / message',
    'Credits/SmsPackages.vue||لا توجد باقات. اضغط على "إضافة باقة" لإنشاء أول باقة.':
        'No packages yet. Click "Add package" to create the first one.',
    'Credits/SmsPackages.vue||إضافة باقة جديدة': 'Add new package',
    'Credits/SmsPackages.vue||الاسم (عربي)': 'Name (Arabic)',
    'Credits/SmsPackages.vue||الاسم (إنجليزي)': 'Name (English)',
    'Credits/SmsPackages.vue||السعر (ر.س)': 'Price (SAR)',
    'Credits/SmsPackages.vue||هل تريد حذف باقة "': 'Delete package "',
    'Credits/SmsPackages.vue||"؟': '"?',

    'Credits/SmsPurchases.vue||كل الحالات': 'All statuses',
    'Credits/SmsPurchases.vue||الرصيد': 'Credit',
    'Credits/SmsPurchases.vue||طريقة الدفع': 'Payment method',
    'Credits/SmsPurchases.vue||فشل': 'Failed',
    'Credits/SmsPurchases.vue||مسترد': 'Refunded',

    'Credits/SmsUsage.vue||تتبع جميع الرسائل المرسلة': 'Track every message sent',
    'Credits/SmsUsage.vue||إجمالي الرسائل': 'Total messages',
    'Credits/SmsUsage.vue||تم التسليم': 'Delivered',
    'Credits/SmsUsage.vue||الرصيد المستخدم': 'Credit used',
    'Credits/SmsUsage.vue||رقم الجوال': 'Mobile number',
    'Credits/SmsUsage.vue||تذكير': 'Reminder',
    'Credits/SmsUsage.vue||إشعار': 'Notification',
    'Credits/SmsUsage.vue||رمز تحقق': 'Verification code',
    'Credits/SmsUsage.vue||تسويقي': 'Marketing',
    'Credits/SmsUsage.vue||موعد': 'Appointment',

    // ── Credits / WhatsApp ─────────────────────────────────────────────────
    'Credits/WhatsappBalances.vue||رصيد رسائل واتساب لكل مستأجر': 'WhatsApp credit balance per tenant',
    'Credits/WhatsappBalances.vue||+ إضافة رصيد': '+ Add credit',
    'Credits/WhatsappBalances.vue||+ إضافة': '+ Add',
    'Credits/WhatsappBalances.vue||إضافة رصيد WhatsApp': 'Add WhatsApp credit',
    'Credits/WhatsappBalances.vue||عدد الرسائل': 'Message count',
    'Credits/WhatsappBalances.vue||السبب': 'Reason',

    'Credits/WhatsappPackages.vue||باقات رصيد WhatsApp': 'WhatsApp credit packages',
    'Credits/WhatsappPackages.vue||إدارة باقات رسائل واتساب المتاحة للشراء':
        'Manage the WhatsApp packages available for purchase',
    'Credits/WhatsappPackages.vue||+ إضافة باقة': '+ Add package',
    'Credits/WhatsappPackages.vue||الأكثر مبيعاً': 'Best seller',
    'Credits/WhatsappPackages.vue||معطّل': 'Disabled',
    'Credits/WhatsappPackages.vue||رسالة': 'message',
    'Credits/WhatsappPackages.vue||ر.س / رسالة': 'SAR / message',
    'Credits/WhatsappPackages.vue||لا توجد باقات. اضغط على "إضافة باقة" وإنشاء أول باقة.':
        'No packages yet. Click "Add package" to create the first one.',
    'Credits/WhatsappPackages.vue||لا توجد باقات. اضغط على "إضافة باقة" لإنشاء أول باقة.':
        'No packages yet. Click "Add package" to create the first one.',
    'Credits/WhatsappPackages.vue||إضافة باقة جديدة': 'Add new package',
    'Credits/WhatsappPackages.vue||الاسم (عربي)': 'Name (Arabic)',
    'Credits/WhatsappPackages.vue||الاسم (إنجليزي)': 'Name (English)',
    'Credits/WhatsappPackages.vue||السعر (ر.س)': 'Price (SAR)',
    'Credits/WhatsappPackages.vue||هل تريد حذف باقة "': 'Delete package "',
    'Credits/WhatsappPackages.vue||"؟': '"?',

    'Credits/WhatsappUsage.vue||إجمالي الرسائل': 'Total messages',
    'Credits/WhatsappUsage.vue||مقروء': 'Read',
    'Credits/WhatsappUsage.vue||القالب': 'Template',
    'Credits/WhatsappUsage.vue||الرصيد': 'Credit',
    'Credits/WhatsappUsage.vue||مرسل': 'Sent',
    'Credits/WhatsappUsage.vue||قيد الإرسال': 'Pending',

    // ── Help ───────────────────────────────────────────────────────────────
    'Help/Index.vue||السبت - الخميس: 8:00 ص - 10:00 م': 'Saturday - Thursday: 8:00 AM - 10:00 PM',
    'Help/Index.vue||تعديل الموضوع': 'Edit topic',
    'Help/Index.vue||حذف الموضوع': 'Delete topic',
    'Help/Index.vue||اختر القسم...': 'Select the section...',
    'Help/Index.vue||مثال: دورة حياة كرت العمل من الدخول حتى الخروج':
        'e.g. The work order lifecycle from entry to exit',
    'Help/Index.vue||مثال: كروت العمل': 'e.g. Work orders',
    'Help/Index.vue||إعادة احتساب المدة تلقائياً من الرابط': 'Recalculate the duration automatically from the link',
    'Help/Index.vue||https://www.youtube.com/watch?v=... أو https://youtu.be/...':
        'https://www.youtube.com/watch?v=... or https://youtu.be/...',
    'Help/Index.vue||شرح مبسط لما يتضمنه الفيديو التدريبي...':
        'A simple explanation of what the training video covers...',
    'Help/Index.vue||مثال: كيف تنشئ كرت عمل جديد للمركبة؟':
        'e.g. How do you create a new work order for a vehicle?',
    'Help/Index.vue||وصف سريع لمحتوى هذا الدليل...': 'A short description of this guide...',
    'Help/Index.vue||اكتب الخطوة هنا...': 'Write the step here...',
    'Help/Index.vue||حذف هذه الخطوة': 'Delete this step',
    'Help/Index.vue||مثال: يمكنك إرفاق صور أضرار المركبة قبل البدء...':
        'e.g. You can attach photos of the vehicle damage before starting...',
    'Help/Index.vue||الانتقال إلى كروت العمل': 'Go to work orders',
    'Help/Index.vue||كروت العمل والخدمات': 'Work orders and services',
    'Help/Index.vue||اكتب السؤال هنا...': 'Write the question here...',
    'Help/Index.vue||اكتب الإجابة المفصلة...': 'Write the detailed answer here...',

    // ── Invoices ───────────────────────────────────────────────────────────
    'Invoices/Show.vue||فاتورة ': 'Invoice ',

    // ── Payment / Checkout ─────────────────────────────────────────────────
    'Payment/Checkout.vue||إتمام الدفع': 'Complete payment',
    'Payment/Checkout.vue||اختر طريقة الدفع لإتمام الاشتراك':
        'Choose a payment method to complete the subscription',
    'Payment/Checkout.vue||ملخص الطلب': 'Order summary',
    'Payment/Checkout.vue||المستأجر': 'Tenant',
    'Payment/Checkout.vue||الباقة': 'Package',
    'Payment/Checkout.vue||الدورة': 'Cycle',
    'Payment/Checkout.vue||المجموع': 'Total',
    'Payment/Checkout.vue||بيانات الدفع': 'Payment details',
    'Payment/Checkout.vue||بوابة الدفع غير مُعدّة': 'The payment gateway is not configured',
    'Payment/Checkout.vue||يجب إعداد مفاتيح API لبوابة الدفع في ملف .env':
        'The payment gateway API keys must be set in the .env file',
    'Payment/Checkout.vue||الدفع آمن ومشفر بمعيار PCI DSS':
        'Payments are secure and encrypted to the PCI DSS standard',

    // ── Profile ────────────────────────────────────────────────────────────
    'Profile/Index.vue||مدير النظام': 'System admin',
    'Profile/Index.vue||مسؤول': 'Admin',
    'Profile/Index.vue||دعم فني': 'Support',

    // ── Settings / Tenant dunning ──────────────────────────────────────────
    'Settings/TenantDunning.vue||إعدادات التذكير — ': 'Dunning settings — ',
    'Settings/TenantDunning.vue||إعدادات التذكير: ': 'Dunning settings: ',
    'Settings/TenantDunning.vue||الحقول الفارغة ترجع للإعدادات العامة. اضغط "حفظ" لتفعيل التجاوز.':
        'Empty fields fall back to the general settings. Press "Save" to apply the override.',

    // ── Subscriptions ──────────────────────────────────────────────────────
    'Subscriptions/Create.vue||المستأجر *': 'Tenant *',
    'Subscriptions/Create.vue||الباقة *': 'Package *',
    'Subscriptions/Create.vue||ر.س / شهرياً': 'SAR / monthly',
    'Subscriptions/Create.vue||ر.س / سنوياً': 'SAR / yearly',
    'Subscriptions/Create.vue||تجربة مجانية:': 'Free trial:',
    'Subscriptions/Create.vue||دورة الفوترة *': 'Billing cycle *',
    'Subscriptions/Create.vue||سنوي (12 شهر)': 'Yearly (12 months)',

    'Subscriptions/Show.vue||تفاصيل الاشتراك': 'Subscription details',
    'Subscriptions/Show.vue||معلومات الاشتراك': 'Subscription information',
    'Subscriptions/Show.vue||السعر': 'Price',
    'Subscriptions/Show.vue|| ر.س': ' SAR',
    'Subscriptions/Show.vue||معطّل': 'Disabled',
    'Subscriptions/Show.vue||التجديد التلقائي': 'Auto-renewal',
    'Subscriptions/Show.vue||التواريخ': 'Dates',
    'Subscriptions/Show.vue||بداية الفترة': 'Period start',
    'Subscriptions/Show.vue||نهاية الفترة': 'Period end',
    'Subscriptions/Show.vue||نهاية التجربة': 'Trial end',
    'Subscriptions/Show.vue||تاريخ الإلغاء': 'Cancellation date',
    'Subscriptions/Show.vue||الفواتير': 'Invoices',
    'Subscriptions/Show.vue||قيد الانتظار': 'Pending',
    'Subscriptions/Show.vue||لا توجد فواتير': 'No invoices',
    'Subscriptions/Show.vue||إلغاء الاشتراك': 'Cancel subscription',
    'Subscriptions/Show.vue||تفعيل الاشتراك': 'Activate subscription',
    'Subscriptions/Show.vue||تمديد الاشتراك': 'Extend subscription',
    'Subscriptions/Show.vue||عرض تفاصيل المستأجر →': 'View tenant details →',
    'Subscriptions/Show.vue||رمز الخصم': 'Discount code',
    'Subscriptions/Show.vue||خصم': 'Discount',
    'Subscriptions/Show.vue||عدد الأيام': 'Number of days',
    'Subscriptions/Show.vue||تمديد': 'Extend',
    'Subscriptions/Show.vue||هل تريد إلغاء هذا الاشتراك؟': 'Do you want to cancel this subscription?',

    // ── Tenants ────────────────────────────────────────────────────────────
    'Tenants/Index.vue||جميع الباقات': 'All packages',

    'Tenants/ShowComponents/TenantModals.vue||تعيين / ترقية باقة للمستأجر':
        'Assign / upgrade a package for the tenant',
    'Tenants/ShowComponents/TenantModals.vue||ربط المستأجر': 'Link the tenant',
    'Tenants/ShowComponents/TenantModals.vue||بباقة جديدة وتفعيل الاشتراك فوراً.':
        'to a new package and activate the subscription immediately.',
    'Tenants/ShowComponents/TenantModals.vue||اختر الباقة': 'Select the package',
    'Tenants/ShowComponents/TenantModals.vue||-- حدد الباقة --': '-- Select a package --',
    'Tenants/ShowComponents/TenantModals.vue||(الاشتراك السنوي:': ' (yearly subscription:',
    'Tenants/ShowComponents/TenantModals.vue||ر.س)': ' SAR)',
    'Tenants/ShowComponents/TenantModals.vue||دورة الفوترة': 'Billing cycle',
    'Tenants/ShowComponents/TenantModals.vue||اشتراك سنوي (12 شهر)': 'Yearly subscription (12 months)',
    'Tenants/ShowComponents/TenantModals.vue||نوع الباقة:': 'Package type:',
    'Tenants/ShowComponents/TenantModals.vue||مدة الاشتراك:': 'Subscription period:',
    'Tenants/ShowComponents/TenantModals.vue|| يوم (تجريبي)': ' days (trial)',
    'Tenants/ShowComponents/TenantModals.vue||سنة كاملة (12 شهر)': 'Full year (12 months)',
    'Tenants/ShowComponents/TenantModals.vue||المبلغ الإجمالي:': 'Total amount:',
    'Tenants/ShowComponents/TenantModals.vue||* سيتم تفعيل الاشتراك مباشرة للمستأجر وتحديث حالته إلى (نشط)، مع أرشفة أي\n                        اشتراك سابق نشط.':
        '* The subscription will be activated for the tenant right away and its status set to (active), archiving any previously active subscription.',    'Tenants/ShowComponents/TenantModals.vue||تأكيد وتفعيل الباقة': 'Confirm and activate the package',

    // ── Website tabs ───────────────────────────────────────────────────────
    'Website/Tabs/AdvancedTab.vue||مثال: خصم 20% لفترة محدودة!': 'e.g. 20% off for a limited time!',
    'Website/Tabs/LandingTab.vue||أدر': 'Manage',
    'Website/Tabs/LandingTab.vue||بذكاء حقيقي': 'With real intelligence',
    'Website/Tabs/LandingTab.vue||مركزك الآن|أرباحك اليوم|فريقك معك|مستقبلك هنا':
        'Your center now|Your revenue today|Your team with you|Your future here',
    'Website/Tabs/SeoSocialTab.vue||كلمة, كلمة اخرى, keywords': 'keyword, another keyword, keywords',

    // ── Remaining per-file entries ─────────────────────────────────────────
    'Announcements/Show.vue||إيقاف': 'Stop',
    'Credits/SmsPackages.vue||عدد الرسائل': 'Message count',
    'Credits/SmsPurchases.vue||المستأجر': 'Tenant',
    'Credits/SmsUsage.vue||فشل': 'Failed',
    'Credits/SmsUsage.vue||كل الحالات': 'All statuses',
    'Credits/SmsUsage.vue||المستأجر': 'Tenant',
    'Credits/SmsUsage.vue||الرصيد': 'Credit',
    'Credits/SmsUsage.vue||مرسل': 'Sent',
    'Credits/SmsUsage.vue||قيد الإرسال': 'Pending',
    'Credits/SmsUsage.vue||فاتورة': 'Invoice',
    'Credits/WhatsappBalances.vue||إجمالي المشتريات': 'Total purchases',
    'Credits/WhatsappBalances.vue||الرصيد الحالي': 'Current balance',
    'Credits/WhatsappBalances.vue||المستأجر': 'Tenant',
    'Credits/WhatsappPackages.vue||عدد الرسائل': 'Message count',
    'Credits/WhatsappUsage.vue||تم التسليم': 'Delivered',
    'Credits/WhatsappUsage.vue||فشل': 'Failed',
    'Credits/WhatsappUsage.vue||المستأجر': 'Tenant',
    'Credits/WhatsappUsage.vue||رقم الجوال': 'Mobile number',

    // ── Arabic-to-Arabic pairs ─────────────────────────────────────────────
    // These are ternaries where BOTH branches are Arabic, so the file
    // carries no English to copy. The pair shares ONE key: the
    // rewriter turns `cond ? 'أ' : 'ب'` into `cond ? t('k') : t('k')`.
    'Announcements/Show.vue||منشور': 'Published',
    'Announcements/Show.vue||مسودة': 'Draft',
    'Credits/SmsPackages.vue||تعديل الباقة': 'Edit package',
    'Credits/SmsPackages.vue||إضافة باقة جديدة': 'Add new package',
    'Credits/WhatsappPackages.vue||تعديل الباقة': 'Edit package',
    'Credits/WhatsappPackages.vue||إضافة باقة جديدة': 'Add new package',
    'Payment/Checkout.vue||سنوي': 'Yearly',
    'Payment/Checkout.vue||شهري': 'Monthly',
    'Subscriptions/Create.vue||سنوي (12 شهر)': 'Yearly (12 months)',
    'Subscriptions/Create.vue||شهري': 'Monthly',
    'Tenants/ShowComponents/TenantModals.vue||سنوي (12 شهر)': 'Yearly (12 months)',
    'Tenants/ShowComponents/TenantModals.vue||شهري': 'Monthly',
};

export default TRANSLATIONS;
