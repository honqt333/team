import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { usePrint } from '@/Composables/usePrint';
import { usePrintTotals } from '@/Composables/usePrintTotals';

/**
 * Shared presentation logic for financial print templates (A4 & Thermal 80mm).
 * Unifies item classification, document titles, field visibility rules,
 * regulatory mandatory-field enforcement, and missing-snapshot warnings
 * without mutating or recalculating stored invoice totals.
 *
 * @param {object} props Component props ({ documentType, data, documentSettings, visualSettings, previewMode })
 * @param {object} [options] Optional helpers ({ dummyItems, qrDocumentTypes })
 */
export function usePrintTemplateLogic(props, options = {}) {
    const { t, locale } = useI18n();
    const isRtl = computed(() => locale.value === 'ar');

    const printHelpers = usePrint();
    const printTotals = usePrintTotals();

    const dummyItems = options.dummyItems || [];
    const qrDocumentTypes = options.qrDocumentTypes || [
        'invoice',
        'parts_invoice',
        'credit_note',
        'receipt',
    ];

    const normalizedItems = computed(() => {
        const items = printTotals.normalizeItems(props.data || {});
        if (items.length === 0 && props.previewMode) {
            return dummyItems;
        }
        return items;
    });

    const filteredItems = computed(() => {
        let items = normalizedItems.value;
        if (props.data?.printed_department && props.data?.printed_department_id) {
            items = items.filter(
                (i) => !i.department_id || i.department_id === props.data.printed_department_id
            );
        }
        return items.filter((item) => item.status !== 'cancelled');
    });

    const isQuoteOrProforma = computed(() =>
        ['quotation', 'quote', 'proforma_invoice'].includes(props.documentType)
    );
    const isCreditNote = computed(() => props.documentType === 'credit_note');
    const isPartsDocument = computed(() =>
        ['parts_invoice', 'purchase_invoice', 'purchase_return'].includes(props.documentType)
    );

    const services = computed(() => {
        if (isPartsDocument.value) return [];
        return filteredItems.value.filter((i) => !i.is_part);
    });

    const parts = computed(() => {
        if (isPartsDocument.value) return filteredItems.value;
        return filteredItems.value.filter((i) => i.is_part);
    });

    const taxEnabled = computed(() => printHelpers.isTaxEnabled(props.data));

    // Unified visibility rules respecting regulatory mandatory overrides
    const showLogo = computed(
        () => (props.documentSettings?.show_logo ?? props.visualSettings?.show_logo ?? true) !== false
    );

    const showQrCode = computed(() => {
        if (!taxEnabled.value) {
            return false;
        }
        // Regulatory QR on issued taxable invoices/credit notes cannot be hidden by cosmetic template settings
        if (props.data?.qr_mandatory && props.data?.qr_code_url) {
            return true;
        }
        const qrPreference =
            (props.documentSettings?.show_qr_code ?? props.visualSettings?.show_qr_code ?? true) !==
            false;
        if (!qrPreference) {
            return false;
        }
        if (props.data?.qr_code_url) {
            return true;
        }
        return Boolean(props.previewMode && qrDocumentTypes.includes(props.documentType));
    });

    const showStamp = computed(
        () => (props.documentSettings?.show_stamp ?? props.visualSettings?.show_stamp ?? true) !== false
    );

    // Never show paid/balance collection rows on quotations or proforma invoices
    const showPayments = computed(
        () => !isQuoteOrProforma.value && (props.documentSettings?.show_payments ?? true) !== false
    );

    const showWarranty = computed(() => (props.documentSettings?.show_warranty ?? true) !== false);
    const showTerms = computed(() => (props.documentSettings?.show_terms ?? true) !== false);
    const showVehicle = computed(() => (props.documentSettings?.show_vehicle ?? true) !== false);

    // Cosmetic hide_on_print cannot suppress mandatory regulatory sections on issued tax documents
    const hideOnPrint = computed(() => {
        if (props.data?.qr_mandatory) {
            return false;
        }
        return Boolean(props.documentSettings?.hide_on_print);
    });

    const missingSnapshots = computed(() => {
        const list = props.data?.missing_snapshots;
        return Array.isArray(list) ? list : [];
    });

    const hasMissingSnapshots = computed(
        () => missingSnapshots.value.length > 0 && props.data?.status !== 'draft'
    );

    const missingSnapshotsMessage = computed(() => {
        if (!hasMissingSnapshots.value) {
            return '';
        }
        return `${t('print.missing_snapshots_warning')} (${missingSnapshots.value.join(', ')})`;
    });

    const totals = computed(() => printTotals.calculateTotals(filteredItems.value, props.data));

    function getDocumentTitle() {
        const taxActive = taxEnabled.value;
        let title = isRtl.value
            ? props.documentSettings?.title_ar ||
              printHelpers.getDocTypeTitle(props.documentType, taxActive)
            : props.documentSettings?.title_en ||
              printHelpers.getDocTypeTitle(props.documentType, taxActive);

        title = String(title || '');
        return printHelpers.cleanDocumentTitle(title, taxActive);
    }

    return {
        isRtl,
        printHelpers,
        printTotals,
        normalizedItems,
        filteredItems,
        services,
        parts,
        isQuoteOrProforma,
        isCreditNote,
        isPartsDocument,
        taxEnabled,
        showLogo,
        showQrCode,
        showStamp,
        showPayments,
        showWarranty,
        showTerms,
        showVehicle,
        hideOnPrint,
        missingSnapshots,
        hasMissingSnapshots,
        missingSnapshotsMessage,
        totals,
        getDocumentTitle,
    };
}
