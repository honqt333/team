<template>
    <div
        class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800"
    >
        <h3 class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">
            {{ $t('system.invoice_show.timeline') }}
        </h3>

        <ol class="mt-5 space-y-5">
            <li v-for="(event, idx) in events" :key="idx" class="relative flex gap-x-3">
                <!-- Connector line (between dots) -->
                <span
                    v-if="idx !== events.length - 1"
                    class="absolute top-8 start-3.5 h-full w-px bg-gray-200 dark:bg-gray-700"
                    aria-hidden="true"
                />

                <!-- Event dot -->
                <span
                    :class="[
                        'relative z-10 flex h-7 w-7 shrink-0 items-center justify-center rounded-full ring-4 ring-white dark:ring-gray-800',
                        event.completed
                            ? event.color
                            : 'bg-gray-100 text-gray-400 dark:bg-gray-700 dark:text-gray-500',
                    ]"
                >
                    <component :is="event.icon" class="h-3.5 w-3.5" />
                </span>

                <!-- Event content -->
                <div class="flex min-w-0 flex-1 flex-col gap-0.5 pb-2">
                    <p
                        :class="[
                            'text-sm font-semibold',
                            event.completed
                                ? 'text-gray-900 dark:text-white'
                                : 'text-gray-500 dark:text-gray-400',
                        ]"
                    >
                        {{ isAr ? event.labelAr : event.labelEn }}
                    </p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        {{ event.timestamp }}
                    </p>
                    <p v-if="event.detail" class="mt-1 text-xs text-gray-600 dark:text-gray-300">
                        {{ event.detail }}
                    </p>
                </div>
            </li>
        </ol>
    </div>
</template>

<script setup>
import { useI18n } from 'vue-i18n';
const { t } = useI18n();

import { computed, h } from 'vue';

const props = defineProps({
    invoice: { type: Object, required: true },
});

// Defensive locale read.
const isAr = computed(() => (document?.documentElement?.lang || 'ar') === 'ar');

// Inline SVG icons via h() to keep this component standalone
// (no extra Heroicons dependency). Each is a 24x24 stroke icon.
const Icon = (path) =>
    h(
        'svg',
        {
            xmlns: 'http://www.w3.org/2000/svg',
            fill: 'none',
            viewBox: '0 0 24 24',
            strokeWidth: 2.5,
            stroke: 'currentColor',
        },
        [h('path', { strokeLinecap: 'round', strokeLinejoin: 'round', d: path })]
    );

const icons = {
    plus: () => Icon('M12 9v6m3-3H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z'),
    calendar: () =>
        Icon(
            'M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5'
        ),
    envelope: () =>
        Icon(
            'M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75'
        ),
    check: () => Icon('M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z'),
    x: () => Icon('M9.75 9.75l4.5 4.5m0-4.5l-4.5 4.5M21 12a9 9 0 11-18 0 9 9 0 0118 0z'),
    ban: () =>
        Icon(
            'M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636'
        ),
};

function formatDate(d) {
    if (!d) return '—';
    return new Date(d).toLocaleString(isAr.value ? 'ar-SA' : 'en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}

const events = computed(() => {
    const out = [];

    // 1. Issued — always present
    out.push({
        labelAr: t('invoices.timeline.issued'),
        labelEn: 'Invoice issued',
        timestamp: formatDate(props.invoice.issued_at),
        detail: null,
        icon: icons.plus,
        color: 'bg-indigo-500 text-white',
        completed: true,
    });

    // 2. Due date — shows even if past
    const isPastDue =
        props.invoice.status === 'pending' &&
        props.invoice.due_at &&
        new Date(props.invoice.due_at) < new Date();
    out.push({
        labelAr: t('invoices.timeline.overdue'),
        labelEn: isPastDue ? 'Past due date' : 'Due date',
        timestamp: formatDate(props.invoice.due_at),
        detail: isPastDue ? t('invoices.timeline.overdue_action') : null,
        icon: icons.calendar,
        color: isPastDue ? 'bg-rose-500 text-white' : 'bg-amber-500 text-white',
        completed: !!props.invoice.due_at,
    });

    // 3. Status-specific event
    if (props.invoice.status === 'paid') {
        out.push({
            labelAr: t('invoices.timeline.payment_received'),
            labelEn: 'Payment received',
            timestamp: formatDate(props.invoice.paid_at),
            detail: null,
            icon: icons.check,
            color: 'bg-emerald-500 text-white',
            completed: true,
        });
    } else if (props.invoice.status === 'cancelled') {
        out.push({
            labelAr: t('invoices.timeline.cancelled'),
            labelEn: 'Invoice cancelled',
            timestamp: formatDate(props.invoice.updated_at),
            detail: null,
            icon: icons.ban,
            color: 'bg-gray-500 text-white',
            completed: true,
        });
    } else if (props.invoice.status === 'pending') {
        out.push({
            labelAr: t('system.company_subscription.status.pending'),
            labelEn: 'Awaiting payment',
            timestamp: isAr.value ? '—' : '—',
            detail: t('invoices.timeline.send_receipt_hint'),
            icon: icons.envelope,
            color: 'bg-gray-300 text-gray-600 dark:bg-gray-600 dark:text-gray-300',
            completed: false,
        });
    }

    return out;
});
</script>
