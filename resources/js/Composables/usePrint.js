import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { useNumberFormat } from '@/Composables/useNumberFormat';
import { useFormatters } from '@/Composables/useFormatters';

/**
 * Trigger browser print safely with optional document title.
 */
export function printDocument(options = {}) {
    if (typeof window === 'undefined') return;
    const originalTitle = document.title;
    if (options?.title) {
        document.title = options.title;
    }
    window.print();
    if (options?.title) {
        setTimeout(() => {
            document.title = originalTitle;
        }, 1000);
    }
}

/**
 * Shared print composable - استدعاء الوظائف المشتركة للطباعة
 * used across all print templates.
 *
 * Usage:
 *   import { usePrint } from '@/Composables/usePrint';
 *   const { formatCurrency, formatDate, getDocTypeTitle, buildSignatures } = usePrint();
 */

export function usePrint() {
    const { locale } = useI18n();
    const isRtl = computed(() => locale.value === 'ar');

    const helpers = useNumberFormat();
    const formatters = useFormatters();

    // Re-export formatting helpers
    const {
        formatNumber: formatNumberBase,
        formatInteger: formatIntegerBase,
        formatQuantity: formatQuantityBase,
    } = helpers;

    // Re-export date formatters
    const { formatDate: formatDateBase, formatDateTime: formatDateTimeBase } = formatters;

    /**
     * Format a currency value with SAR suffix.
     * Unlike formatCurrency (no suffix), this is for labels.
     */
    function formatCurrency(value = 0, showSuffix = true) {
        const numValue = typeof value === 'string' ? parseFloat(value) : value;
        if (isNaN(numValue)) return '0.00';
        const suffix = isRtl.value ? ' ر.س' : ' SAR';
        return (
            numValue.toLocaleString('en-US', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2,
            }) + (showSuffix ? suffix : '')
        );
    }

    /**
     * Format raw price (no suffix) for table cells.
     */
    function formatRawPrice(value = 0) {
        const numValue = typeof value === 'string' ? parseFloat(value) : value;
        if (isNaN(numValue)) return '0.00';
        return numValue.toLocaleString('en-US', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        });
    }

    /**
     * Format date as YYYY-MM-DD (ISO format)
     */
    function formatDateISO(date = '') {
        if (!date) return '-';
        const d = new Date(date);
        return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
    }

    /**
     * Format date as DD/MM/YYYY (local format)
     */
    function formatDate(date = '', showTime = false) {
        return showTime ? formatDateTimeBase(date) : formatDateBase(date);
    }

    /**
     * Get payment method label (bilingual)
     */
    function getMethodLabel(method) {
        if (!method) return '-';
        const methodsAr = {
            cash: 'نقداً',
            card: 'بطاقة مدى / ائتمانية',
            bank_transfer: 'تحويل بنكي',
            check: 'شيك',
            online: 'دفع إلكتروني',
            other: 'آخر',
        };
        const methodsEn = {
            cash: 'Cash',
            card: 'Card',
            bank_transfer: 'Bank Transfer',
            check: 'Cheque',
            online: 'Online',
            other: 'Other',
        };
        const methodKey = method.toLowerCase();
        return isRtl.value ? methodsAr[methodKey] || method : methodsEn[methodKey] || method;
    }

    /**
     * Check if tax is enabled from data snapshot
     */
    function isTaxEnabled(data = {}) {
        const val = data.tax_enabled_snapshot;
        if (val === true || val === 1 || val === '1') return true;
        if (val === false || val === 0 || val === '0') return false;
        if (data.total_tax !== undefined && data.total_tax !== null) {
            return Number(data.total_tax) > 0;
        }
        return true;
    }

    /**
     * Get document type title (bilingual, tax-aware)
     */
    function getDocTypeTitle(type, taxActive = true) {
        const titlesAr = {
            invoice: taxActive ? 'فاتورة ضريبية' : 'فاتورة',
            proforma_invoice: 'فاتورة أولية',
            quotation: 'عرض سعر',
            parts_invoice: taxActive ? 'فاتورة قطع غيار ضريبية' : 'فاتورة قطع غيار',
            work_order: 'كرت صيانة',
            receipt: 'سند قبض مالي',
            checklist: 'تقرير فحص',
            delivery_note: 'سند تسليم',
            condition_report: 'تقرير حالة المركبة',
            payments: 'سندات الدفع والمدفوعات',
            purchase_invoice: taxActive ? 'فاتورة مشتريات ضريبية' : 'فاتورة مشتريات',
            purchase_return: taxActive ? 'فاتورة مرتجع مشتريات ضريبية' : 'فاتورة مرتجع مشتريات',
            credit_note: taxActive ? 'إشعار دائن (فاتورة إرجاع)' : 'إشعار دائن',
        };
        const titlesEn = {
            invoice: taxActive ? 'Tax Invoice' : 'Invoice',
            proforma_invoice: 'Proforma Invoice',
            quotation: 'Quotation',
            parts_invoice: taxActive ? 'Tax Parts Invoice' : 'Parts Invoice',
            work_order: 'Work Order',
            receipt: 'Receipt',
            checklist: 'Checklist',
            delivery_note: 'Delivery Note',
            condition_report: 'Vehicle Condition Report',
            payments: 'Payments Receipt',
            purchase_invoice: taxActive ? 'Tax Purchase Invoice' : 'Purchase Invoice',
            purchase_return: taxActive ? 'Tax Purchase Return Invoice' : 'Purchase Return Invoice',
            credit_note: taxActive ? 'Credit Note (Return Invoice)' : 'Credit Note',
        };
        if (isRtl.value) {
            return titlesAr[type] || 'وثيقة رسمية';
        }
        return titlesEn[type] || 'Official Document';
    }

    /**
     * Get localized color name (bilingual)
     */
    function getLocalizedColor(colorName) {
        if (!colorName) return '';
        const colorMap = {
            white: { ar: 'أبيض', en: 'White' },
            أبيض: { ar: 'أبيض', en: 'White' },
            black: { ar: 'أسود', en: 'Black' },
            أسود: { ar: 'أسود', en: 'Black' },
            silver: { ar: 'فضي', en: 'Silver' },
            فضي: { ar: 'فضي', en: 'Silver' },
            gray: { ar: 'رمادي', en: 'Gray' },
            grey: { ar: 'رمادي', en: 'Gray' },
            رمادي: { ar: 'رمادي', en: 'Gray' },
            red: { ar: 'أحمر', en: 'Red' },
            أحمر: { ar: 'أحمر', en: 'Red' },
            blue: { ar: 'أزرق', en: 'Blue' },
            أزرق: { ar: 'أزرق', en: 'Blue' },
            brown: { ar: 'بني', en: 'Brown' },
            بني: { ar: 'بني', en: 'Brown' },
            green: { ar: 'أخضر', en: 'Green' },
            أخضر: { ar: 'أخضر', en: 'Green' },
            beige: { ar: 'بيج', en: 'Beige' },
            بيج: { ar: 'بيج', en: 'Beige' },
            orange: { ar: 'برتقالي', en: 'Orange' },
            برتقالي: { ar: 'برتقالي', en: 'Orange' },
            gold: { ar: 'ذهبي', en: 'Gold' },
            ذهبي: { ar: 'ذهبي', en: 'Gold' },
            yellow: { ar: 'أصفر', en: 'Yellow' },
            أصفر: { ar: 'أصفر', en: 'Yellow' },
            purple: { ar: 'بنفسجي', en: 'Purple' },
            بنفسجي: { ar: 'بنفسجي', en: 'Purple' },
            bronze: { ar: 'برونزي', en: 'Bronze' },
            برونزي: { ar: 'برونزي', en: 'Bronze' },
            maroon: { ar: 'كستنائي', en: 'Maroon' },
            كستنائي: { ar: 'كستنائي', en: 'Maroon' },
            navy: { ar: 'كحلي', en: 'Navy' },
            كحلي: { ar: 'كحلي', en: 'Navy' },
        };
        const key = colorName.trim().toLowerCase();
        const match = colorMap[key];
        return match ? (isRtl.value ? match.ar : match.en) : colorName;
    }

    /**
     * Format signature URL
     */
    function formatSignatureUrl(url) {
        if (!url) return '';
        if (url.startsWith('data:')) return url;
        if (url.includes('/storage/')) {
            return '/storage/' + url.split('/storage/')[1];
        }
        if (url.startsWith('http://') || url.startsWith('https://')) {
            return url;
        }
        return '/storage/' + url.replace(/^\/+/, '');
    }

    /**
     * Check if signature name is client signature
     */
    function isClientSignature(name) {
        if (!name) return false;
        const n = name.toLowerCase();
        return (
            n.includes('عميل') ||
            n.includes('العميل') ||
            n.includes('زبون') ||
            n.includes('customer') ||
            n.includes('client')
        );
    }

    /**
     * Get status label for work order items
     */
    function getStatusLabel(status) {
        const labelsAr = {
            open: 'مفتوح',
            in_progress: 'قيد التنفيذ',
            pending: 'معلق',
            on_hold: 'متوقف',
            completed: 'مكتمل',
            closed: 'مغلق',
            cancelled: 'ملغي',
        };
        const labelsEn = {
            open: 'Open',
            in_progress: 'In Progress',
            pending: 'Pending',
            on_hold: 'On Hold',
            completed: 'Completed',
            closed: 'Closed',
            cancelled: 'Cancelled',
        };
        if (!status) return isRtl.value ? 'مفتوح' : 'Open';
        return isRtl.value ? labelsAr[status] || status : labelsEn[status] || status;
    }

    /**
     * Get damage mark color value
     */
    function getColorValue(color) {
        const colors = { red: '#ef4444', blue: '#3b82f6', gray: '#6b7280' };
        return colors[color || ''] || '#ef4444';
    }

    /**
     * Get signature grid class based on count
     */
    function getSignatureGridClass(count) {
        if (count === 1) return 'grid-cols-1 justify-items-center';
        if (count === 2) return 'grid-cols-2';
        if (count === 3) return 'grid-cols-3';
        return 'grid-cols-4';
    }

    /**
     * Clean document title from tax terms if tax disabled
     */
    function cleanDocumentTitle(title, taxActive) {
        if (taxActive) return title;
        if (isRtl.value) {
            return (
                title
                    .replace(/فاتورة ضريبية مبسطة/g, 'فاتورة')
                    .replace(/فاتورة ضريبية/g, 'فاتورة')
                    .replace(/ضريبية/g, '')
                    .trim() || 'فاتورة'
            );
        }
        return (
            title
                .replace(/Simplified Tax Invoice/gi, 'Invoice')
                .replace(/Tax Invoice/gi, 'Invoice')
                .replace(/Tax/gi, '')
                .trim() || 'Invoice'
        );
    }

    /**
     * Build signature list from docSettings
     */
    const buildPrintSignatures = (docSettings, customerSignatureUrl) => {
        const saved = docSettings?.signatures;
        const separator = (s1, s2) => `${s1} ${s2}`.trim();
        const isCustomerSig = (sig) => {
            const text = separator(sig?.name_ar, separator(sig?.name_en, sig?.name || ''));
            return (
                text.includes('عميل') ||
                text.includes('العميل') ||
                text.includes('زبون') ||
                text.includes('customer') ||
                text.includes('client')
            );
        };

        const list =
            saved && saved.length > 0
                ? saved.map((signature) => {
                      if (isCustomerSig(signature)) {
                          return { ...signature, url: customerSignatureUrl || signature.url };
                      }
                      return signature;
                  })
                : [
                      {
                          name_ar: 'توقيع المستشار الفني',
                          name_en: 'Service Advisor Signature',
                          show: true,
                      },
                      {
                          name_ar: 'توقيع العميل للاعتماد',
                          name_en: 'Customer Signature',
                          url: customerSignatureUrl,
                          show: true,
                      },
                  ];

        if (customerSignatureUrl) {
            const existing = list.some((s) => isCustomerSig(s) && s.url);
            if (!existing) {
                list.push({
                    name_ar: 'توقيع العميل للاعتماد',
                    name_en: 'Customer Signature',
                    url: customerSignatureUrl,
                    show: true,
                });
            }
        }

        return list;
    };

    /**
     * Parse all composable functions
     */
    function parseSignatures(docSettings, customerSignatureUrl = '') {
        import('@/Utils/printSignatures').then(({ buildPrintSignatures }) => {
            return buildPrintSignatures(docSettings, customerSignatureUrl);
        });
        return buildPrintSignatures(docSettings, customerSignatureUrl);
    }

    function print() {
        printDocument();
    }

    return {
        isRtl,
        print,
        printDocument,
        formatCurrency,
        formatRawPrice,
        formatDateISO,
        formatDate,
        formatDateTimeBase,
        getMethodLabel,
        isTaxEnabled,
        getDocTypeTitle,
        getLocalizedColor,
        formatSignatureUrl,
        isClientSignature,
        getStatusLabel,
        getColorValue,
        getSignatureGridClass,
        cleanDocumentTitle,
        formatNumber: formatNumberBase,
        formatInteger: formatIntegerBase,
        formatQuantity: formatQuantityBase,
        buildPrintSignatures,
        buildSignatures: buildPrintSignatures,
        parseSignatures,
    };
}
