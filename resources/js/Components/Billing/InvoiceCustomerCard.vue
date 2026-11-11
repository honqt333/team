<template>
    <div
        class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800"
    >
        <div class="flex items-center gap-3">
            <!-- Tenant avatar -->
            <div
                class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-action-primary hover:bg-action-primary-hover text-lg font-bold text-white"
            >
                {{ initials }}
            </div>
            <div class="min-w-0 flex-1">
                <h3 class="truncate text-sm font-bold text-gray-900 dark:text-white">
                    {{ tenant?.trade_name || tenant?.name || '—' }}
                </h3>
                <p
                    v-if="tenant?.legal_name"
                    class="truncate text-xs text-gray-500 dark:text-gray-400"
                >
                    {{ tenant.legal_name }}
                </p>
            </div>
        </div>

        <dl class="mt-5 space-y-3 border-t border-gray-200 pt-4 text-sm dark:border-gray-700">
            <div v-if="tenant?.cr_number" class="flex items-start gap-2">
                <DocumentIcon class="mt-0.5 h-4 w-4 shrink-0 text-gray-400" />
                <div class="min-w-0 flex-1">
                    <dt class="text-xs text-gray-500 dark:text-gray-400">
                        {{ $t('company_profile.profile.cr_number') }}
                    </dt>
                    <dd class="truncate font-mono text-gray-900 dark:text-white">
                        {{ tenant.cr_number }}
                    </dd>
                </div>
            </div>

            <div v-if="tenant?.vat_number" class="flex items-start gap-2">
                <DocumentIcon class="mt-0.5 h-4 w-4 shrink-0 text-gray-400" />
                <div class="min-w-0 flex-1">
                    <dt class="text-xs text-gray-500 dark:text-gray-400">
                        {{ $t('center_settings.profile.vat_number') }}
                    </dt>
                    <dd class="truncate font-mono text-gray-900 dark:text-white">
                        {{ tenant.vat_number }}
                    </dd>
                </div>
            </div>

            <div v-if="tenant?.email" class="flex items-start gap-2">
                <MailIcon class="mt-0.5 h-4 w-4 shrink-0 text-gray-400" />
                <div class="min-w-0 flex-1">
                    <dt class="text-xs text-gray-500 dark:text-gray-400">
                        {{ $t('center_settings.contact.email') }}
                    </dt>
                    <dd class="truncate text-gray-900 dark:text-white">
                        {{ tenant.email }}
                    </dd>
                </div>
            </div>

            <div v-if="tenant?.phone" class="flex items-start gap-2">
                <PhoneIcon class="mt-0.5 h-4 w-4 shrink-0 text-gray-400" />
                <div class="min-w-0 flex-1">
                    <dt class="text-xs text-gray-500 dark:text-gray-400">
                        {{ $t('common.phone') }}
                    </dt>
                    <dd class="truncate font-mono text-gray-900 dark:text-white">
                        {{ tenant.phone }}
                    </dd>
                </div>
            </div>

            <div
                v-if="!tenant?.cr_number && !tenant?.vat_number && !tenant?.email && !tenant?.phone"
                class="text-center text-xs text-gray-400 italic"
            >
                {{ $t('system.invoice_show.no_additional_data') }}
            </div>
        </dl>

        <Link
            v-if="tenant?.id"
            :href="`/system/tenants/${tenant.id}`"
            class="mt-5 flex w-full items-center justify-center gap-1.5 rounded-lg border border-gray-200 px-3 py-2 text-xs font-semibold text-gray-700 transition hover:bg-gray-50 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-700/40"
        >
            {{ $t('system.invoice_show.view_tenant') }}
            <ArrowRightIcon class="h-3.5 w-3.5" />
        </Link>
    </div>
</template>

<script setup>
import { useI18n } from 'vue-i18n';
const { t } = useI18n();

import { computed, h } from 'vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    tenant: { type: Object, default: null },
});

const isAr = computed(() => (document?.documentElement?.lang || 'ar') === 'ar');

const initials = computed(() => {
    const source = props.tenant?.trade_name || props.tenant?.name || '?';
    const words = source.trim().split(/\s+/);
    return ((words[0]?.[0] || '') + (words[1]?.[0] || '')).toUpperCase() || '?';
});

// Inline SVG icons
const makeIcon = (path) =>
    h(
        'svg',
        {
            xmlns: 'http://www.w3.org/2000/svg',
            fill: 'none',
            viewBox: '0 0 24 24',
            strokeWidth: 1.8,
            stroke: 'currentColor',
        },
        [
            h('path', {
                strokeLinecap: 'round',
                strokeLinejoin: 'round',
                d: path,
            }),
        ]
    );

const DocumentIcon = () =>
    makeIcon(
        'M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z'
    );
const MailIcon = () =>
    makeIcon(
        'M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75'
    );
const PhoneIcon = () =>
    makeIcon(
        'M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 002.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 01-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 00-1.091-.852H4.5A2.25 2.25 0 002.25 4.5v2.25z'
    );
const ArrowRightIcon = () => makeIcon('M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3');
</script>
