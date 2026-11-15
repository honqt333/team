<script setup>
import { computed, ref } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import BackButton from '@/Components/BackButton.vue';
import AppButton from '@/Components/App/AppButton.vue';
import AppInput from '@/Components/App/AppInput.vue';
import AppTextarea from '@/Components/App/AppTextarea.vue';
import AppTabs from '@/Components/Common/AppTabs.vue';
import AppToggle from '@/Components/Common/AppToggle.vue';
import StatusBadge from '@/Components/Common/StatusBadge.vue';
import DataTable from '@/Components/Common/DataTable.vue';
import { useToast } from '@/Composables/useToast';

const { t } = useI18n();
const { success: toastSuccess } = useToast();

const props = defineProps({
    settings: {
        type: Object,
        required: true,
    },
    templates: {
        type: Object,
        required: true,
    },
    initialTab: {
        type: String,
        default: 'channels',
    },
    isWhatsappAddon: {
        type: Boolean,
        default: false,
    },
    backHref: {
        type: String,
        default: '',
    },
    whatsappSummary: {
        type: Object,
        default: () => ({
            provider_active: false,
            provider_name: null,
            balance: 0,
            total_purchased: 0,
            total_used: 0,
        }),
    },
    whatsappLogs: {
        type: Array,
        default: () => [],
    },
});

const activeTab = ref(props.initialTab || 'channels');
const testing = ref(false);
const testingWhatsapp = ref(false);
const activeTemplateKey = ref('work_order_created');

const tabs = computed(() => {
    if (props.isWhatsappAddon) {
        return [
            { id: 'channels', label: t('settings.notifications.tabs.channels') },
            { id: 'events', label: t('settings.notifications.tabs.events') },
            { id: 'templates', label: t('settings.notifications.tabs.templates') },
            { id: 'logs', label: t('settings.notifications.tabs.logs') },
        ];
    }

    return [
        { id: 'channels', label: t('settings.notifications.tabs.channels') },
        { id: 'events', label: t('settings.notifications.tabs.events') },
    ];
});

const eventKeys = [
    'appointment_booked',
    'appointment_reminder',
    'appointment_cancelled',
    'work_order_created',
    'work_order_status_changed',
    'work_order_completed',
    'quote_shared',
    'quote_approved',
    'invoice_issued',
    'payment_received',
    'low_stock_alert',
];

const templateConfigs = [
    {
        key: 'work_order_created',
        labelKey: 'settings.notifications.templates.work_order_created',
        vars: ['{customer_name}', '{work_order_number}', '{vehicle}', '{plate_number}', '{company_name}'],
    },
    {
        key: 'work_order_completed',
        labelKey: 'settings.notifications.templates.work_order_completed',
        vars: ['{customer_name}', '{work_order_number}', '{vehicle}', '{plate_number}', '{company_name}'],
    },
    {
        key: 'quote_shared',
        labelKey: 'settings.notifications.templates.quote_shared',
        vars: ['{customer_name}', '{quote_number}', '{total}', '{url}', '{company_name}'],
    },
    {
        key: 'invoice_issued',
        labelKey: 'settings.notifications.templates.invoice_issued',
        vars: ['{customer_name}', '{invoice_number}', '{total}', '{url}', '{company_name}'],
    },
    {
        key: 'appointment_booked',
        labelKey: 'settings.notifications.templates.appointment_booked',
        vars: ['{customer_name}', '{reference_number}', '{date}', '{center_name}', '{company_name}'],
    },
    {
        key: 'appointment_reminder',
        labelKey: 'settings.notifications.templates.appointment_reminder',
        vars: ['{customer_name}', '{reference_number}', '{date}', '{center_name}', '{company_name}'],
    },
    {
        key: 'low_stock_alert',
        labelKey: 'settings.notifications.templates.low_stock_alert',
        vars: ['{item_name}', '{quantity}'],
    },
];

const quickVariables = [
    '{customer_name}',
    '{plate_number}',
    '{code}',
    '{center_name}',
    '{total}',
    '{url}',
];

const logColumns = computed(() => [
    { key: 'created_at', label: t('settings.notifications.logs.date'), sortable: true },
    { key: 'phone_number', label: t('settings.notifications.logs.phone'), sortable: false },
    { key: 'message_type_label', label: t('settings.notifications.logs.type'), sortable: false },
    { key: 'template_name', label: t('settings.notifications.logs.template'), sortable: false },
    { key: 'credits_used', label: t('settings.notifications.logs.credits'), sortable: false },
    { key: 'status', label: t('settings.notifications.logs.status'), sortable: false },
]);

const form = useForm({
    channels: {
        in_app: props.settings?.channels?.in_app ?? true,
        email: props.settings?.channels?.email ?? false,
        sms: props.settings?.channels?.sms ?? false,
        whatsapp: props.settings?.channels?.whatsapp ?? false,
    },
    channel_configs: {
        email_recipient: props.settings?.channel_configs?.email_recipient ?? '',
        whatsapp_phone: props.settings?.channel_configs?.whatsapp_phone ?? '',
    },
    events: JSON.parse(JSON.stringify(props.settings?.events ?? {})),
    templates: {
        work_order_created: props.templates?.work_order_created ?? '',
        work_order_completed: props.templates?.work_order_completed ?? '',
        quote_shared: props.templates?.quote_shared ?? '',
        invoice_issued: props.templates?.invoice_issued ?? '',
        appointment_booked: props.templates?.appointment_booked ?? '',
        appointment_reminder: props.templates?.appointment_reminder ?? '',
        low_stock_alert: props.templates?.low_stock_alert ?? '',
    },
});

const routePrefix = computed(() =>
    props.isWhatsappAddon ? 'app.addons.whatsapp' : 'settings.notifications'
);

const pageTitle = computed(() =>
    props.isWhatsappAddon
        ? t('settings.integrations.whatsapp_title')
        : t('settings.notifications.title')
);

const pageSubtitle = computed(() =>
    props.isWhatsappAddon
        ? t('settings.integrations.whatsapp_desc')
        : t('settings.notifications.subtitle')
);

const resolvedBackHref = computed(() => props.backHref || route('app.addons'));

const submit = () => {
    form.transform((data) => {
        const scopedEvents = {};
        for (const [eventKey, flags] of Object.entries(data.events || {})) {
            scopedEvents[eventKey] = props.isWhatsappAddon
                ? { sms: !!flags?.sms, whatsapp: !!flags?.whatsapp }
                : { in_app: !!flags?.in_app, email: !!flags?.email };
        }

        if (props.isWhatsappAddon) {
            return {
                channels: {
                    sms: !!data.channels.sms,
                    whatsapp: !!data.channels.whatsapp,
                },
                channel_configs: {
                    whatsapp_phone: data.channel_configs.whatsapp_phone,
                },
                events: scopedEvents,
                templates: data.templates,
            };
        }

        return {
            channels: {
                in_app: !!data.channels.in_app,
                email: !!data.channels.email,
            },
            channel_configs: {
                email_recipient: data.channel_configs.email_recipient,
            },
            events: scopedEvents,
        };
    }).put(route(`${routePrefix.value}.update`), {
        preserveScroll: true,
    });
};

const sendTestNotification = () => {
    testing.value = true;
    router.post(
        route(`${routePrefix.value}.test`),
        {},
        {
            preserveScroll: true,
            onFinish: () => {
                testing.value = false;
            },
        }
    );
};

const sendTestWhatsapp = () => {
    testingWhatsapp.value = true;
    router.post(
        route(`${routePrefix.value}.test-whatsapp`),
        {
            phone: form.channel_configs.whatsapp_phone,
        },
        {
            preserveScroll: true,
            onFinish: () => {
                testingWhatsapp.value = false;
            },
        }
    );
};

const insertVariable = (variable) => {
    const key = activeTemplateKey.value || 'work_order_created';
    const current = String(form.templates[key] || '');
    form.templates[key] = current ? `${current} ${variable}` : variable;
    if (typeof navigator !== 'undefined' && navigator.clipboard) {
        navigator.clipboard.writeText(variable).catch(() => {});
    }
    toastSuccess(t('settings.notifications.templates.variable_inserted', { var: variable }));
};

const logStatusClasses = (status) => {
    if (status === 'delivered' || status === 'read' || status === 'sent') {
        return 'bg-emerald-50 text-emerald-700 ring-1 ring-inset ring-emerald-600/20 dark:bg-emerald-900/20 dark:text-emerald-300 dark:ring-emerald-500/30';
    }
    if (status === 'failed') {
        return 'bg-rose-50 text-rose-700 ring-1 ring-inset ring-rose-600/20 dark:bg-rose-900/20 dark:text-rose-300 dark:ring-rose-500/30';
    }
    return 'bg-amber-50 text-amber-700 ring-1 ring-inset ring-amber-600/20 dark:bg-amber-900/20 dark:text-amber-300 dark:ring-amber-500/30';
};
</script>

<template>
    <AppLayout>
        <Head :title="pageTitle" />

        <div class="space-y-6 pb-20 sm:pb-6">
            <!-- Header Section (Constitution-compliant PageHeader + AppButton) -->
            <PageHeader :title="pageTitle" :subtitle="pageSubtitle">
                <template #back>
                    <BackButton :href="resolvedBackHref" />
                </template>

                <template #icon>
                    <svg
                        class="w-7 h-7 text-brand-primary"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"
                        />
                    </svg>
                </template>

                <template #actions>
                    <div class="flex flex-wrap items-center gap-2.5 w-full sm:w-auto">
                        <AppButton
                            v-if="!isWhatsappAddon"
                            :loading="testing"
                            @click="sendTestNotification"
                            variant="secondary"
                            size="md"
                            class="flex-1 sm:flex-initial"
                        >
                            {{
                                testing
                                    ? $t('settings.notifications.actions.sending_test')
                                    : $t('settings.notifications.actions.send_test')
                            }}
                        </AppButton>

                        <AppButton
                            v-else
                            :loading="testingWhatsapp"
                            @click="sendTestWhatsapp"
                            variant="secondary"
                            size="md"
                            class="flex-1 sm:flex-initial"
                        >
                            {{ $t('settings.notifications.actions.send_test_whatsapp') }}
                        </AppButton>

                        <AppButton
                            :loading="form.processing"
                            @click="submit"
                            variant="primary"
                            size="md"
                            class="flex-1 sm:flex-initial"
                        >
                            {{
                                form.processing
                                    ? $t('settings.notifications.actions.saving')
                                    : $t('settings.notifications.actions.save')
                            }}
                        </AppButton>
                    </div>
                </template>
            </PageHeader>

            <!-- WhatsApp Credits & Provider Status Summary (WhatsApp Addon only) -->
            <div v-if="isWhatsappAddon" class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div
                    class="bg-surface rounded-xl p-4 border border-border-subtle flex items-center justify-between"
                >
                    <div>
                        <p class="text-xs text-text-secondary">
                            {{ $t('settings.notifications.whatsapp_card.provider_status') }}
                        </p>
                        <p class="text-sm font-bold text-text-primary mt-1">
                            {{
                                whatsappSummary.provider_active
                                    ? $t('settings.notifications.whatsapp_card.connected')
                                    : $t('settings.notifications.whatsapp_card.disconnected')
                            }}
                        </p>
                    </div>
                    <StatusBadge
                        :label="
                            whatsappSummary.provider_active
                                ? $t('common.active')
                                : $t('common.inactive')
                        "
                        class="gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium"
                        :class="
                            whatsappSummary.provider_active
                                ? 'bg-emerald-50 text-emerald-700 ring-1 ring-inset ring-emerald-600/20 dark:bg-emerald-900/20 dark:text-emerald-300 dark:ring-emerald-500/30'
                                : 'bg-amber-50 text-amber-700 ring-1 ring-inset ring-amber-600/20 dark:bg-amber-900/20 dark:text-amber-300 dark:ring-amber-500/30'
                        "
                        :dot-class="whatsappSummary.provider_active ? 'bg-emerald-500' : 'bg-amber-500'"
                    />
                </div>

                <div
                    class="bg-surface rounded-xl p-4 border border-border-subtle flex items-center justify-between"
                >
                    <div>
                        <p class="text-xs text-text-secondary">
                            {{ $t('settings.notifications.whatsapp_card.available_balance') }}
                        </p>
                        <p class="text-xl font-bold text-brand-primary tabular-nums mt-0.5">
                            {{ whatsappSummary.balance }}
                        </p>
                    </div>
                    <span class="text-xs text-text-muted">
                        {{ $t('settings.notifications.whatsapp_card.credits_unit') }}
                    </span>
                </div>

                <div
                    class="bg-surface rounded-xl p-4 border border-border-subtle flex items-center justify-between"
                >
                    <div>
                        <p class="text-xs text-text-secondary">
                            {{ $t('settings.notifications.whatsapp_card.total_sent') }}
                        </p>
                        <p class="text-xl font-bold text-text-primary tabular-nums mt-0.5">
                            {{ whatsappSummary.total_used }}
                        </p>
                    </div>
                    <span class="text-xs text-text-muted">
                        {{ $t('settings.notifications.whatsapp_card.messages_unit') }}
                    </span>
                </div>
            </div>

            <!-- Navigation Tabs -->
            <div class="border-b border-border-subtle pb-3">
                <AppTabs v-model="activeTab" :tabs="tabs" variant="pills" />
            </div>

            <!-- Tab 1: Channels -->
            <div v-if="activeTab === 'channels'" class="space-y-6">
                <div class="bg-surface rounded-xl p-5 sm:p-6 border border-border-subtle space-y-6">
                    <div>
                        <h3 class="text-base font-bold text-text-primary">
                            {{ $t('settings.notifications.channels.title') }}
                        </h3>
                        <p class="text-xs sm:text-sm text-text-secondary mt-1">
                            {{ $t('settings.notifications.channels.description') }}
                        </p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- In-App Channel (Internal Notifications) -->
                        <div
                            v-if="!isWhatsappAddon"
                            class="p-4 rounded-xl border border-border-subtle bg-surface-muted/40 flex items-start justify-between gap-4"
                        >
                            <div>
                                <p class="text-sm font-bold text-text-primary">
                                    {{ $t('settings.notifications.channels.in_app') }}
                                </p>
                                <p class="text-xs text-text-secondary mt-1">
                                    {{ $t('settings.notifications.channels.in_app_desc') }}
                                </p>
                            </div>
                            <AppToggle v-model="form.channels.in_app" />
                        </div>

                        <!-- Email Channel (Internal Notifications) -->
                        <div
                            v-if="!isWhatsappAddon"
                            class="p-4 rounded-xl border border-border-subtle bg-surface-muted/40 flex items-start justify-between gap-4"
                        >
                            <div>
                                <p class="text-sm font-bold text-text-primary">
                                    {{ $t('settings.notifications.channels.email') }}
                                </p>
                                <p class="text-xs text-text-secondary mt-1">
                                    {{ $t('settings.notifications.channels.email_desc') }}
                                </p>
                            </div>
                            <AppToggle v-model="form.channels.email" />
                        </div>

                        <!-- WhatsApp Channel (Customer Messaging Addon) -->
                        <div
                            v-if="isWhatsappAddon"
                            class="p-4 rounded-xl border border-border-subtle bg-surface-muted/40 flex items-start justify-between gap-4"
                        >
                            <div>
                                <p class="text-sm font-bold text-text-primary">
                                    {{ $t('settings.notifications.channels.whatsapp') }}
                                </p>
                                <p class="text-xs text-text-secondary mt-1">
                                    {{ $t('settings.notifications.channels.whatsapp_desc') }}
                                </p>
                            </div>
                            <AppToggle v-model="form.channels.whatsapp" />
                        </div>

                        <!-- SMS Channel (Customer Messaging Addon) -->
                        <div
                            v-if="isWhatsappAddon"
                            class="p-4 rounded-xl border border-border-subtle bg-surface-muted/40 flex items-start justify-between gap-4"
                        >
                            <div>
                                <p class="text-sm font-bold text-text-primary">
                                    {{ $t('settings.notifications.channels.sms') }}
                                </p>
                                <p class="text-xs text-text-secondary mt-1">
                                    {{ $t('settings.notifications.channels.sms_desc') }}
                                </p>
                            </div>
                            <AppToggle v-model="form.channels.sms" />
                        </div>
                    </div>

                    <!-- Channel Recipient Configs -->
                    <div class="pt-4 border-t border-border-subtle">
                        <div v-if="!isWhatsappAddon" class="space-y-1.5 max-w-xl">
                            <label class="block text-sm font-semibold text-text-primary">
                                {{ $t('settings.notifications.channels.email_recipient') }}
                            </label>
                            <AppInput
                                v-model="form.channel_configs.email_recipient"
                                type="email"
                                dir="ltr"
                                :placeholder="$t('settings.notifications.channels.email_recipient_hint')"
                            />
                            <p class="text-xs text-text-muted">
                                {{ $t('settings.notifications.channels.email_recipient_hint') }}
                            </p>
                        </div>

                        <div v-else class="space-y-1.5 max-w-xl">
                            <label class="block text-sm font-semibold text-text-primary">
                                {{ $t('settings.notifications.channels.whatsapp_phone') }}
                            </label>
                            <div class="flex gap-2 items-start">
                                <div class="flex-1">
                                    <AppInput
                                        v-model="form.channel_configs.whatsapp_phone"
                                        type="text"
                                        dir="ltr"
                                        placeholder="9665XXXXXXXX"
                                    />
                                </div>
                                <AppButton
                                    type="button"
                                    variant="secondary"
                                    size="md"
                                    :loading="testingWhatsapp"
                                    @click="sendTestWhatsapp"
                                >
                                    {{ $t('settings.notifications.actions.send_test_whatsapp') }}
                                </AppButton>
                            </div>
                            <p class="text-xs text-text-muted">
                                {{ $t('settings.notifications.channels.whatsapp_phone_hint') }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tab 2: Events Matrix -->
            <div v-if="activeTab === 'events'" class="space-y-6">
                <div class="bg-surface rounded-xl p-5 sm:p-6 border border-border-subtle space-y-4">
                    <div>
                        <h3 class="text-base font-bold text-text-primary">
                            {{ $t('settings.notifications.events.title') }}
                        </h3>
                        <p class="text-xs sm:text-sm text-text-secondary mt-1">
                            {{ $t('settings.notifications.events.description') }}
                        </p>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr
                                    class="border-b border-border-subtle text-text-secondary text-xs"
                                >
                                    <th class="py-3 px-3 text-start font-semibold">
                                        {{ $t('settings.notifications.events.table_event') }}
                                    </th>
                                    <th v-if="!isWhatsappAddon" class="py-3 px-3 text-center font-semibold">
                                        {{ $t('settings.notifications.events.table_in_app') }}
                                    </th>
                                    <th v-if="!isWhatsappAddon" class="py-3 px-3 text-center font-semibold">
                                        {{ $t('settings.notifications.events.table_email') }}
                                    </th>
                                    <th v-if="isWhatsappAddon" class="py-3 px-3 text-center font-semibold">
                                        {{ $t('settings.notifications.events.table_whatsapp') }}
                                    </th>
                                    <th v-if="isWhatsappAddon" class="py-3 px-3 text-center font-semibold">
                                        {{ $t('settings.notifications.events.table_sms') }}
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-border-subtle">
                                <tr
                                    v-for="eventKey in eventKeys"
                                    :key="eventKey"
                                    class="hover:bg-surface-muted/40 transition-colors"
                                >
                                    <td class="py-3 px-3 font-medium text-text-primary">
                                        {{ $t(`settings.notifications.events.${eventKey}`) }}
                                    </td>
                                    <td v-if="!isWhatsappAddon" class="py-3 px-3 text-center">
                                        <div class="inline-flex justify-center">
                                            <AppToggle
                                                v-if="form.events[eventKey]"
                                                v-model="form.events[eventKey].in_app"
                                                size="sm"
                                            />
                                        </div>
                                    </td>
                                    <td v-if="!isWhatsappAddon" class="py-3 px-3 text-center">
                                        <div class="inline-flex justify-center">
                                            <AppToggle
                                                v-if="form.events[eventKey]"
                                                v-model="form.events[eventKey].email"
                                                size="sm"
                                            />
                                        </div>
                                    </td>
                                    <td v-if="isWhatsappAddon" class="py-3 px-3 text-center">
                                        <div class="inline-flex justify-center">
                                            <AppToggle
                                                v-if="form.events[eventKey]"
                                                v-model="form.events[eventKey].whatsapp"
                                                size="sm"
                                            />
                                        </div>
                                    </td>
                                    <td v-if="isWhatsappAddon" class="py-3 px-3 text-center">
                                        <div class="inline-flex justify-center">
                                            <AppToggle
                                                v-if="form.events[eventKey]"
                                                v-model="form.events[eventKey].sms"
                                                size="sm"
                                            />
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Tab 3: Templates (including migrated Company Notifications templates) -->
            <div v-if="isWhatsappAddon && activeTab === 'templates'" class="space-y-6">
                <div class="bg-surface rounded-xl p-5 sm:p-6 border border-border-subtle space-y-6">
                    <div>
                        <h3 class="text-base font-bold text-text-primary">
                            {{ $t('settings.notifications.templates.title') }}
                        </h3>
                        <p class="text-xs sm:text-sm text-text-secondary mt-1">
                            {{ $t('settings.notifications.templates.description') }}
                        </p>
                    </div>

                    <!-- Clickable Variables Legend -->
                    <div
                        class="p-4 rounded-xl bg-brand-primary-soft border border-brand-primary/20 space-y-2"
                    >
                        <p class="text-xs font-bold text-text-primary">
                            {{ $t('company_profile.templates.available_variables') }}
                        </p>
                        <div class="flex flex-wrap gap-2">
                            <AppButton
                                v-for="variable in quickVariables"
                                :key="variable"
                                type="button"
                                variant="secondary"
                                size="sm"
                                class="font-mono text-xs"
                                @click="insertVariable(variable)"
                            >
                                {{ variable }}
                            </AppButton>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-5">
                        <div
                            v-for="tpl in templateConfigs"
                            :key="tpl.key"
                            class="p-4 rounded-xl border border-border-subtle bg-surface-muted/30 space-y-2"
                            @focusin="activeTemplateKey = tpl.key"
                        >
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <label class="text-sm font-bold text-text-primary">
                                    {{ $t(tpl.labelKey) }}
                                </label>
                                <span class="text-[11px] text-brand-primary font-mono">
                                    {{ tpl.vars.join(', ') }}
                                </span>
                            </div>
                            <AppTextarea v-model="form.templates[tpl.key]" :rows="3" />
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tab 4: WhatsApp Message Logs (DataTable) -->
            <div v-if="isWhatsappAddon && activeTab === 'logs'" class="space-y-6">
                <div class="bg-surface rounded-xl p-5 sm:p-6 border border-border-subtle space-y-4">
                    <div>
                        <h3 class="text-base font-bold text-text-primary">
                            {{ $t('settings.notifications.logs.title') }}
                        </h3>
                        <p class="text-xs sm:text-sm text-text-secondary mt-1">
                            {{ $t('settings.notifications.logs.description') }}
                        </p>
                    </div>

                    <DataTable
                        :columns="logColumns"
                        :data="whatsappLogs"
                        :empty-text="$t('settings.notifications.logs.empty')"
                    >
                        <template #cell-phone_number="{ row }">
                            <span class="font-mono text-xs" dir="ltr">
                                {{ row.phone_number }}
                            </span>
                        </template>
                        <template #cell-template_name="{ row }">
                            <span class="font-mono text-xs text-text-secondary">
                                {{ row.template_name || '-' }}
                            </span>
                        </template>
                        <template #cell-status="{ row }">
                            <StatusBadge
                                :label="row.status_label"
                                class="gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-medium"
                                :class="logStatusClasses(row.status)"
                            />
                        </template>
                    </DataTable>
                </div>
            </div>

            <!-- Sticky Save Footer for Mobile -->
            <div
                class="sm:hidden fixed bottom-0 inset-x-0 p-3 bg-surface/95 backdrop-blur border-t border-border-subtle flex gap-2 z-30"
            >
                <AppButton
                    type="button"
                    variant="primary"
                    size="md"
                    class="w-full"
                    :loading="form.processing"
                    @click="submit"
                >
                    {{
                        form.processing
                            ? $t('settings.notifications.actions.saving')
                            : $t('settings.notifications.actions.save')
                    }}
                </AppButton>
            </div>
        </div>
    </AppLayout>
</template>
