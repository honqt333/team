/**
 * usePortalFormat — shared helpers for the customer portal pages.
 *
 * Every portal page used to redefine its own `t` fallback, date and
 * money formatters. Centralising them keeps the portal consistent and
 * gives future portal modules (satisfaction surveys, loyalty, …) a
 * single place to pick up formatting conventions.
 *
 *   const { t, isRtl, formatDate, formatDateTime, formatMoney } = usePortalFormat(props);
 */
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';

export function usePortalFormat(props = {}) {
    const { t: tRaw, locale: i18nLocale } = useI18n();
    const page = usePage();

    const isRtl = computed(
        () => props.locale?.is_rtl ?? (page.props.locale || i18nLocale.value || 'ar') === 'ar'
    );

    // Missing keys fall back to their last segment instead of the
    // full dotted path, so a forgotten key never shows raw JSON paths.
    const t = (path, params) => {
        const v = tRaw(path, params || {});
        return v === path ? path.split('.').pop() : v;
    };

    const intlLocale = computed(() => (isRtl.value ? 'ar-SA-u-nu-latn' : 'en-US'));

    function formatDate(value) {
        if (!value) return '';
        try {
            // Pure `YYYY-MM-DD` strings are parsed as local dates to
            // avoid the UTC shift that renders the previous day.
            const d =
                typeof value === 'string' && /^\d{4}-\d{2}-\d{2}$/.test(value)
                    ? new Date(`${value}T00:00:00`)
                    : new Date(value);
            return new Intl.DateTimeFormat(intlLocale.value, { dateStyle: 'medium' }).format(d);
        } catch (e) {
            return value;
        }
    }

    function formatDateTime(value) {
        if (!value) return '';
        try {
            return new Intl.DateTimeFormat(intlLocale.value, {
                dateStyle: 'medium',
                timeStyle: 'short',
            }).format(new Date(value));
        } catch (e) {
            return value;
        }
    }

    function formatMoney(value) {
        return Number(value || 0).toLocaleString('en-US', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        });
    }

    return { t, isRtl, formatDate, formatDateTime, formatMoney };
}

/** Badge palette for quote statuses (expired overrides the raw status). */
export function quoteStatusClass(q) {
    const approved = q.status === 'approved' || q.status === 'converted';
    if (q.is_expired && !approved && q.status !== 'rejected') {
        return 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300';
    }
    const map = {
        draft: 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-200',
        sent: 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-200',
        approved: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-200',
        converted: 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-200',
        rejected: 'bg-rose-100 text-rose-800 dark:bg-rose-900/40 dark:text-rose-200',
    };
    return map[q.status] || 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300';
}

/** Badge palette for invoice payment statuses. */
export function paymentStatusClass(status) {
    const map = {
        paid: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-200',
        partial: 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-200',
        unpaid: 'bg-rose-100 text-rose-800 dark:bg-rose-900/40 dark:text-rose-200',
    };
    return map[status] || 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300';
}
