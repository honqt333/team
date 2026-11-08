/**
 * The missing translation keys, with the English that goes with them.
 *
 * WHY THIS FILE EXISTS
 *
 * A key that is missing from BOTH ar.json and en.json does not fall
 * back to anything: vue-i18n renders the key itself, so the user
 * sees `system.credits.active_workshops` on the screen. The
 * `I18nCoverageTest` counts them, but the real symptom is on the
 * page.
 *
 * Every entry was written against the line the key is used on, so
 * the English says what the screen says and not what the key name
 * suggests.
 *
 * A PLACEHOLDER MUST MATCH
 *
 * `system.credits.tenant_label` takes `{id}`. The placeholder has to
 * be the same string in ar.json, in en.json and at the call site, or
 * vue-i18n renders `{id}` literally in one of the two languages.
 */

export const MISSING_KEYS = {
    // ── common ─────────────────────────────────────────────────────────────
    'common.count': { ar: 'العدد', en: 'Count' },
    'common.deselect_all': { ar: 'إلغاء تحديد الكل', en: 'Deselect all' },
    'common.paid': { ar: 'مدفوع', en: 'Paid' },
    'common.prepared_by': { ar: 'تمت الطباعة بواسطة', en: 'Prepared by' },
    'common.previous': { ar: 'السابق', en: 'Previous' },
    'common.time': { ar: 'الوقت', en: 'Time' },
    'common.view_mode': { ar: 'طريقة العرض', en: 'View mode' },

    // ── customers (print view) ─────────────────────────────────────────────
    'customers.print.balance': { ar: 'الرصيد', en: 'Balance' },
    'customers.print.customer_code': { ar: 'كود العميل', en: 'Customer code' },
    'customers.print.invoices_count': { ar: 'عدد الفواتير', en: 'Invoices count' },
    'customers.print.name': { ar: 'الاسم', en: 'Name' },
    'customers.print.no_customers': { ar: 'لا يوجد عملاء', en: 'No customers' },
    'customers.print.phone': { ar: 'الهاتف', en: 'Phone' },
    'customers.print.type': { ar: 'النوع', en: 'Type' },
    'customers.print.vehicles_count': { ar: 'عدد المركبات', en: 'Vehicles count' },

    // ── departments ────────────────────────────────────────────────────────
    'departments.subtitle': { ar: 'إدارة الأقسام وفئات العمل', en: 'Manage departments and work categories' },

    // ── employee portal ────────────────────────────────────────────────────
    'employee_portal.checklist': { ar: 'قائمة المهام', en: 'Checklist' },
    'employee_portal.no_tasks_filtered': { ar: 'لا توجد مهام مطابقة للفلاتر', en: 'No tasks match the filters' },
    'employee_portal.notes': { ar: 'الملاحظات', en: 'Notes' },
    'employee_portal.photos': { ar: 'الصور', en: 'Photos' },
    'employee_portal.search_tasks_placeholder': { ar: 'ابحث في المهام...', en: 'Search tasks...' },

    // ── financial ──────────────────────────────────────────────────────────
    'financial.bank_transfer': { ar: 'تحويل بنكي', en: 'Bank transfer' },

    // ── print settings ─────────────────────────────────────────────────────
    'print_settings.custom_template_active': { ar: 'القالب المخصص المستخدم حالياً', en: 'Custom template currently active' },
    'print_settings.default_global_template': { ar: 'القالب العام الافتراضي', en: 'Default global template' },
    'print_settings.document_template_select': { ar: 'اختر قالب المستند', en: 'Select the document template' },
    'print_settings.select_document_hint': {
        ar: 'القالب المختار يُطبَّق على كل المستندات المطبوعة من هذا القسم',
        en: 'The selected template applies to every document printed from this section',
    },
    'print_settings.using_global_template': { ar: 'يستخدم القالب العام', en: 'Using the global template' },

    // ── quotes ─────────────────────────────────────────────────────────────
    'quotes.service_modal.add_and_another': { ar: 'إضافة والاستمرار', en: 'Add and continue' },

    // ── settings / integrations ────────────────────────────────────────────
    'settings.integrations.manage_print': { ar: 'إدارة إعدادات الطباعة', en: 'Manage the print settings' },
    'settings.integrations.print_desc': {
        ar: 'اختر القوالب المستخدمة في طباعة المستندات والفواتير والتقارير',
        en: 'Choose the templates used to print documents, invoices and reports',
    },
    'settings.integrations.print_title': { ar: 'الطباعة', en: 'Printing' },

    // ── system / credits ───────────────────────────────────────────────────
    'system.credits.active_workshops': { ar: 'ورش نشطة', en: 'Active workshops' },
    'system.credits.add': { ar: 'إضافة', en: 'Add' },
    'system.credits.add_manual_sms_credit': { ar: 'إضافة رصيد SMS يدوي', en: 'Add manual SMS credit' },
    'system.credits.adding': { ar: 'جارٍ الإضافة...', en: 'Adding...' },
    'system.credits.critically_low': { ar: 'حرج منخفض', en: 'Critically low' },
    'system.credits.custom_package': { ar: 'باقة مخصصة', en: 'Custom package' },
    'system.credits.good': { ar: 'جيد', en: 'Good' },
    'system.credits.low': { ar: 'منخفض', en: 'Low' },
    'system.credits.low_credit': { ar: 'رصيد منخفض', en: 'Low credit' },
    'system.credits.message_type': { ar: 'نوع الرسالة', en: 'Message type' },
    'system.credits.messages_count': { ar: 'عدد الرسائل', en: 'Message count' },
    'system.credits.messages_sent': { ar: 'الرسائل المرسلة', en: 'Messages sent' },
    'system.credits.no_data_available': { ar: 'لا توجد بيانات متاحة', en: 'No data available' },
    'system.credits.no_messages_recorded': { ar: 'لا توجد رسائل مسجلة', en: 'No messages recorded' },
    'system.credits.no_operations_found': { ar: 'لا توجد عمليات', en: 'No operations found' },
    'system.credits.popular': { ar: 'الأكثر مبيعاً', en: 'Best seller' },
    'system.credits.reason_placeholder': { ar: 'مثال: هدية ترحيبية', en: 'e.g. Welcome bonus' },
    'system.credits.recipient': { ar: 'المستلم', en: 'Recipient' },
    'system.credits.search_workshop': { ar: 'ابحث باسم الورشة...', en: 'Search by workshop name...' },
    'system.credits.status': { ar: 'الحالة', en: 'Status' },
    'system.credits.tenant_id': { ar: 'رقم المستأجر', en: 'Tenant ID' },
    'system.credits.tenant_label': { ar: 'المستأجر #{id}', en: 'Tenant #{id}' },
    'system.credits.total_credits_balance': { ar: 'إجمالي الأرصدة', en: 'Total credit balance' },
    'system.credits.total_purchased': { ar: 'إجمالي المشتريات', en: 'Total purchased' },
    'system.credits.transaction_number': { ar: 'رقم العملية', en: 'Transaction number' },
    'system.credits.workshop': { ar: 'الورشة', en: 'Workshop' },

    // ── system / settings (dunning) ───────────────────────────────────────
    'system.settings.auto_suspension': { ar: 'الإيقاف التلقائي', en: 'Auto-suspension' },
    'system.settings.automatically_suspend_the_workshop_after_invo': {
        ar: 'إيقاف الورشة تلقائياً بعد تجاوز الفاتورة مدة السماح',
        en: 'Automatically suspend the workshop after an invoice exceeds the grace period',
    },
    'system.settings.channel_matrix': { ar: 'مصفوفة القنوات', en: 'Channel matrix' },
    'system.settings.days_overdue': { ar: 'متأخرة {days} يوم', en: '{days} days overdue' },
    'system.settings.days_overdue_2': { ar: 'يوم تأخير', en: 'days overdue' },
    'system.settings.quiet_hours_ksa': { ar: 'ساعات الصمت (توقيت السعودية)', en: 'Quiet hours (KSA)' },
    'system.settings.select_which_channels_are_used_for_each_tier': {
        ar: 'اختر القنوات المستخدمة في كل مستوى تذكير',
        en: 'Select the channels used for each reminder tier',
    },
    'system.settings.skip_outbound_messages_during_these_hours_': {
        ar: 'تجاهل الرسائل الصادرة خلال هذه الساعات',
        en: 'Skip outbound messages during these hours',
    },
    'system.settings.to': { ar: 'إلى', en: 'to' },
    'system.settings.uncheck_a_tier_to_skip_it_globally_skipped_tier': {
        ar: 'ألغِ تحديد مستوى التذكير لتخطيه عالمياً',
        en: 'Uncheck a tier to skip it globally (skipped tier)',
    },

    // ── The second batch ───────────────────────────────────────────────────
    // The system panel and the credit hubs brought in new screens, and
    // every one of them referenced a key that was never added. A key
    // missing from BOTH files is not a fallback: vue-i18n renders the
    // key itself, so the user reads `nav.groups.crm` on the screen.
    //
    // The English here comes from the `|| 'English'` fallback that the
    // code already carries, so the translation matches the copy the
    // developer wrote.
    'common.try_adjusting_search': { ar: 'جرّب تعديل البحث', en: 'Try adjusting the search' },

    'company_profile.contact.save_hint': {
        ar: 'احفظ معلومات الاتصال والعنوان الوطني لتحديث الفواتير والتقارير',
        en: 'Save the contact details and the national address to update the invoices and reports',
    },

    'invoices.sales.empty_hint': {
        ar: 'لا توجد فواتير مبيعات مطابقة لمعايير البحث الحالية.',
        en: 'No sales invoices match the current search criteria.',
    },

    'messages.status_updated': { ar: 'تم تحديث حالة كرت العمل بنجاح', en: 'The work order status was updated' },

    'nav.groups.general': { ar: 'الرئيسية', en: 'Home' },
    'nav.groups.operations': { ar: 'العمليات', en: 'Operations' },
    'nav.groups.crm': { ar: 'العملاء', en: 'Customers' },
    'nav.groups.inventory': { ar: 'المخزون', en: 'Inventory' },
    'nav.groups.hr': { ar: 'الموارد البشرية', en: 'Human resources' },
    'nav.groups.finance': { ar: 'المالية والفواتير', en: 'Finance and invoices' },
    'nav.groups.reports': { ar: 'التقارير', en: 'Reports' },
    'nav.inventory': { ar: 'المستودع وقطع الغيار', en: 'Warehouse and parts' },

    'tenant_site.admin.auto_apply_hint': {
        ar: 'جميع التعديلات يتم حفظها وتطبيقها فوراً على الموقع العام',
        en: 'Every change is saved and applied to the public site immediately',
    },
};

export default MISSING_KEYS;
