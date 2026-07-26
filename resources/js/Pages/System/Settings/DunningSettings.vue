<template>
    <SystemLayout>
        <Head :title="isAr ? 'إعدادات التذكير التلقائي' : 'Dunning Automation Settings'" />

        <div class="space-y-6">
            <!-- Header -->
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                    {{ isAr ? 'إعدادات التذكير التلقائي' : 'Dunning Automation Settings' }}
                </h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    {{
                        isAr
                            ? 'تذكير المستأجرين المتأخرين في السداد تلقائياً عبر إيميل/إشعار/WhatsApp. كل تذكير يُسجَّل في سجل كامل.'
                            : 'Automatically remind tenants with overdue invoices via email/in-app/WhatsApp. Every reminder is logged for audit.'
                    }}
                </p>
            </div>

            <!-- Global toggle -->
            <div
                class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800"
            >
                <label class="flex cursor-pointer items-start gap-3">
                    <input
                        v-model="form.enabled"
                        type="checkbox"
                        class="mt-1 h-5 w-5 rounded border-gray-300 text-amber-600 focus:ring-amber-500"
                    />
                    <div class="flex-1">
                        <div class="text-base font-semibold text-gray-900 dark:text-white">
                            {{ isAr ? 'تفعيل التذكير التلقائي' : 'Enable automated dunning' }}
                        </div>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            {{
                                isAr
                                    ? 'عند التفعيل، الـ cron اليومي يرسل تذكيرات للمستأجرين اللي عليهم فواتير متأخرة حسب الـ tiers المحددة.'
                                    : 'When enabled, the daily cron sends reminders to tenants with overdue invoices based on the tiers below.'
                            }}
                        </p>
                    </div>
                </label>
            </div>

            <!-- Tier table -->
            <div
                class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800"
            >
                <div class="border-b border-gray-200 px-5 py-4 dark:border-gray-700">
                    <h2 class="text-sm font-bold uppercase tracking-wider text-gray-500">
                        {{ isAr ? 'مستويات التذكير' : 'Reminder Tiers' }}
                    </h2>
                </div>
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-xs uppercase text-gray-500 dark:bg-gray-900/40">
                        <tr>
                            <th class="px-5 py-3 text-start">
                                {{ isAr ? 'المستوى' : 'Tier' }}
                            </th>
                            <th class="px-5 py-3 text-start">
                                {{ isAr ? 'بعد' : 'After' }}
                            </th>
                            <th class="px-5 py-3 text-start">
                                {{ isAr ? 'اللهجة' : 'Tone' }}
                            </th>
                            <th class="px-5 py-3 text-start">
                                {{ isAr ? 'القنوات' : 'Channels' }}
                            </th>
                            <th class="px-5 py-3 text-start">
                                {{ isAr ? 'مفعّل' : 'Active' }}
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        <tr v-for="(tier, key) in tierDefinitions" :key="key">
                            <td class="px-5 py-3 font-medium text-gray-900 dark:text-white">
                                {{ isAr ? 'المستوى' : 'Tier' }} {{ key.replace('tier_', '') }}
                            </td>
                            <td class="px-5 py-3 text-gray-700 dark:text-gray-300">
                                {{ tier.days }} {{ isAr ? 'يوم' : 'days' }}
                            </td>
                            <td class="px-5 py-3 text-gray-700 dark:text-gray-300">
                                {{ tier.tone[isAr ? 'ar' : 'en'] }}
                            </td>
                            <td class="px-5 py-3">
                                <div class="flex flex-wrap gap-1">
                                    <label
                                        v-for="ch in availableChannels"
                                        :key="ch.key"
                                        class="inline-flex cursor-pointer items-center gap-1 rounded-md border border-gray-200 px-2 py-1 text-xs hover:bg-gray-50 dark:border-gray-700 dark:hover:bg-gray-700/40"
                                        :class="{
                                            'bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-900/30 dark:text-amber-300 dark:border-amber-800':
                                                form.channel_matrix[key]?.includes(ch.key),
                                        }"
                                    >
                                        <input
                                            v-model="form.channel_matrix[key]"
                                            :value="ch.key"
                                            type="checkbox"
                                            class="h-3 w-3 rounded text-amber-600"
                                        />
                                        {{ ch.label }}
                                    </label>
                                </div>
                            </td>
                            <td class="px-5 py-3">
                                <input
                                    v-model="form.active_tiers_list"
                                    :value="key"
                                    type="checkbox"
                                    class="h-4 w-4 rounded border-gray-300 text-amber-600"
                                />
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Save button -->
            <div class="flex justify-end gap-3">
                <Link
                    :href="'/system/billing/dunning-log'"
                    class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700"
                >
                    {{ isAr ? 'عرض السجل' : 'View log' }}
                </Link>
                <button
                    @click="save"
                    :disabled="processing"
                    class="rounded-lg bg-amber-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-amber-700 disabled:opacity-50"
                >
                    {{
                        processing
                            ? isAr
                                ? 'يحفظ...'
                                : 'Saving...'
                            : isAr
                              ? 'حفظ الإعدادات'
                              : 'Save settings'
                    }}
                </button>
            </div>

            <!-- Tier descriptions -->
            <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                <div
                    v-for="(tier, key) in tierDefinitions"
                    :key="key"
                    class="rounded-lg border border-gray-200 bg-gray-50 p-4 text-sm dark:border-gray-700 dark:bg-gray-900/40"
                >
                    <div class="font-semibold text-gray-900 dark:text-white">
                        {{ isAr ? 'المستوى' : 'Tier' }} {{ key.replace('tier_', '') }} —
                        {{ tier.tone[isAr ? 'ar' : 'en'] }}
                        ({{ tier.days }}d)
                    </div>
                    <div class="mt-1 text-gray-600 dark:text-gray-400">
                        {{ tier.description[isAr ? 'ar' : 'en'] }}
                    </div>
                </div>
            </div>
        </div>
    </SystemLayout>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { Head, router, Link } from '@inertiajs/vue3';
import SystemLayout from '@/Layouts/SystemLayout.vue';

const props = defineProps({
    config: { type: Object, required: true },
    tier_definitions: { type: Object, required: true },
    default_channel_matrix: { type: Object, required: true },
});

// Defensive locale read — same pattern as Reports.vue to avoid the
// "Cannot read properties of undefined" setup crash.
const isAr = computed(() => (document?.documentElement?.lang || 'ar') === 'ar');

const availableChannels = [
    { key: 'email', label: 'Email' },
    { key: 'in_app', label: isAr.value ? 'داخل التطبيق' : 'In-app' },
    { key: 'sms', label: 'SMS' },
    { key: 'whatsapp', label: 'WhatsApp' },
];

// We keep `active_tiers` as a comma-separated string for the API
// (simpler for the admin form) but expose it to the user as a list
// of checkboxes via `active_tiers_list`.
const form = ref({
    enabled: props.config.enabled,
    active_tiers_list: props.config.active_tiers.split(',').map((s) => s.trim()),
    channel_matrix: { ...props.config.channel_matrix },
});

const processing = ref(false);

async function save() {
    processing.value = true;
    try {
        await router.put('/system/settings/dunning', {
            enabled: form.value.enabled,
            active_tiers: form.value.active_tiers_list.join(','),
            channel_matrix: form.value.channel_matrix,
        });
    } finally {
        processing.value = false;
    }
}
</script>
