/**
 * The composed sentences for the template literals of the /system pages.
 *
 * A template literal with a hole in it is ONE sentence:
 *
 *     `سيُسجَّل نقص ${formatCurrency(partialShortfall)} SAR كرصيد (الفاتورة تبقى مفتوحة)`
 *
 * The Arabic value of the key is the whole sentence with the hole
 * replaced by a placeholder, and the English is the same sentence in
 * English. The rewriter turns the literal into
 * `t('key', { amount: formatCurrency(partialShortfall) })`.
 *
 * THE PLACEHOLDER NAMES MUST MATCH
 *
 * vue-i18n renders `{amount}` literally when the key is missing from
 * the active language. So the placeholder name in ar.json and in
 * en.json has to be the same string, and it has to be the same string
 * the rewriter passes.
 *
 * The key is `<file relative to Pages/System>` and the value is the
 * whole sentence with the hole marked as `{name}`.
 */

export const TEMPLATE_LITERALS = {
    'BankTransferReceipts/Index.vue||⚠️ ناقص {amount}': {
        ar: '⚠️ ناقص {amount}',
        en: '⚠️ Short by {amount}',
        key: 'system.bank_transfer_receipts.short_by',
    },

    'BankTransferReceipts/Show.vue||المبلغ المُحوّل أقل من المطلوب بـ {amount} SAR': {
        ar: 'المبلغ المُحوّل أقل من المطلوب بـ {amount} SAR',
        en: 'The transferred amount is less than required by {amount} SAR',
        key: 'system.bank_transfer_receipts.transferred_less_than_required',
    },

    'BankTransferReceipts/Show.vue||المبلغ المُحوّل أكثر من المطلوب بـ {amount} SAR (سيُسجَّل كرصيد دائن)': {
        ar: 'المبلغ المُحوّل أكثر من المطلوب بـ {amount} SAR (سيُسجَّل كرصيد دائن)',
        en: 'The transferred amount is more than required by {amount} SAR (it will be recorded as credit)',
        key: 'system.bank_transfer_receipts.transferred_more_than_required',
    },

    'BankTransferReceipts/Show.vue||سيُسجَّل نقص {amount} SAR كرصيد (الفاتورة تبقى مفتوحة)': {
        ar: 'سيُسجَّل نقص {amount} SAR كرصيد (الفاتورة تبقى مفتوحة)',
        en: 'A shortfall of {amount} SAR will be recorded as credit (the invoice stays open)',
        key: 'system.bank_transfer_receipts.shortfall_recorded_as_credit',
    },

    'Communication/Templates/Edit.vue||تم نسخ {variable}': {
        ar: 'تم نسخ {variable}',
        en: 'Copied {variable}',
        key: 'system.communication.copied',
    },

    'Credits/SmsPackages.vue||هل تريد حذف باقة "{name}"؟': {
        ar: 'هل تريد حذف باقة "{name}"؟',
        en: 'Delete package "{name}"?',
        key: 'system.credits.delete_package',
    },

    'Credits/WhatsappPackages.vue||هل تريد حذف باقة "{name}"؟': {
        ar: 'هل تريد حذف باقة "{name}"؟',
        en: 'Delete package "{name}"?',
        key: 'system.credits.delete_package',
    },

    'Invoices/Show.vue||فاتورة {number}': {
        ar: 'فاتورة {number}',
        en: 'Invoice {number}',
        key: 'system.invoices.invoice',
    },

    'Settings/TenantDunning.vue||إعدادات التذكير — {name}': {
        ar: 'إعدادات التذكير — {name}',
        en: 'Dunning settings — {name}',
        key: 'system.settings.dunning_settings',
    },

    'Settings/TenantDunning.vue||إعدادات التذكير: {name}': {
        ar: 'إعدادات التذكير: {name}',
        en: 'Dunning settings: {name}',
        key: 'system.settings.dunning_settings_colon',
    },

    'Tenants/ShowComponents/TenantModals.vue||{days} يوم (تجريبي)': {
        ar: '{days} يوم (تجريبي)',
        en: '{days} days (trial)',
        key: 'system.tenants.trial_days',
    },
};

export default TEMPLATE_LITERALS;
