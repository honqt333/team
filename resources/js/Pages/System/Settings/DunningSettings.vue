<template>
    <SystemLayout>
        <Head
            :title="
                isAr
                    ? $t('system.settings.dunning_automation_settings')
                    : 'Dunning Automation Settings'
            "
        />

        <div class="space-y-6">
            <!-- Header -->
            <PageHeader
                :title="
                    isAr
                        ? $t('system.settings.dunning_automation_settings')
                        : 'Dunning Automation Settings'
                "
                :subtitle="
                    isAr
                        ? $t('system.settings.automatically_remind_tenants_with_overdue_invoic')
                        : 'Automatically remind tenants with overdue invoices via email/in-app/WhatsApp. Every reminder is logged for audit.'
                "
            >
                <template #icon>
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                        />
                    </svg>
                </template>
            </PageHeader>

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
                            {{
                                isAr
                                    ? $t('system.settings.enable_automated_dunning')
                                    : 'Enable automated dunning'
                            }}
                        </div>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            {{
                                isAr
                                    ? $t(
                                          'system.settings.when_enabled_the_daily_cron_sends_reminders_to_t'
                                      )
                                    : 'When enabled, the daily cron sends reminders to tenants with overdue invoices based on the tiers below.'
                            }}
                        </p>
                    </div>
                </label>
            </div>

            <!-- Phase 7.3 + 7.7: Quiet hours + Auto-suspend -->
            <div
                class="grid grid-cols-1 gap-4 rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800 md:grid-cols-2"
            >
                <div>
                    <label class="block text-sm font-semibold text-gray-900 dark:text-white">
                        {{ isAr ? $t('system.settings.quiet_hours') : 'Quiet hours' }}
                    </label>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        {{
                            isAr
                                ? $t(
                                      'system.settings.no_reminders_will_be_sent_in_this_window._leave_'
                                  )
                                : 'No reminders will be sent in this window. Leave blank to disable.'
                        }}
                    </p>
                    <div class="mt-3 flex items-center gap-2">
                        <input
                            v-model="form.quiet_hours_start"
                            type="time"
                            class="rounded-md border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                        />
                        <span class="text-sm text-gray-500">→</span>
                        <input
                            v-model="form.quiet_hours_end"
                            type="time"
                            class="rounded-md border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                        />
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-900 dark:text-white">
                        {{ isAr ? $t('system.settings.auto-suspend_after') : 'Auto-suspend after' }}
                    </label>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        {{
                            isAr
                                ? $t(
                                      'system.settings.days_after_the_last_tier-4_final_reminder_before'
                                  )
                                : 'Days after the last tier-4 (final) reminder before auto-suspending the tenant. Leave blank to disable.'
                        }}
                    </p>
                    <div class="mt-3 flex items-center gap-2">
                        <input
                            v-model.number="form.auto_suspend_after_days"
                            type="number"
                            min="1"
                            max="90"
                            class="w-24 rounded-md border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                        />
                        <span class="text-sm text-gray-500">
                            {{ isAr ? $t('common.day') : 'days' }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Tier table -->
            <div
                class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800"
            >
                <div class="border-b border-gray-200 px-5 py-4 dark:border-gray-700">
                    <h2 class="text-sm font-bold uppercase tracking-wider text-gray-500">
                        {{ isAr ? $t('system.settings.reminder_tiers') : 'Reminder Tiers' }}
                    </h2>
                </div>
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-xs uppercase text-gray-500 dark:bg-gray-900/40">
                        <tr>
                            <th class="px-5 py-3 text-start">
                                {{ isAr ? $t('system.settings.tier_3') : 'Tier' }}
                            </th>
                            <th class="px-5 py-3 text-start">
                                {{ isAr ? $t('system.settings.after') : 'After' }}
                            </th>
                            <th class="px-5 py-3 text-start">
                                {{ isAr ? $t('system.settings.tone') : 'Tone' }}
                            </th>
                            <th class="px-5 py-3 text-start">
                                {{ isAr ? $t('system.settings.channels') : 'Channels' }}
                            </th>
                            <th class="px-5 py-3 text-start">
                                {{ isAr ? $t('common.enabled') : 'Active' }}
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        <tr v-for="(tier, key) in tierDefinitions" :key="key">
                            <td class="px-5 py-3 font-medium text-gray-900 dark:text-white">
                                {{ isAr ? $t('system.settings.tier_3') : 'Tier' }}
                                {{ key.replace('tier_', '') }}
                            </td>
                            <td class="px-5 py-3 text-gray-700 dark:text-gray-300">
                                {{ tier.days }} {{ isAr ? $t('common.day') : 'days' }}
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
                    {{ isAr ? $t('system.settings.view_log') : 'View log' }}
                </Link>
                <button
                    @click="save"
                    :disabled="processing"
                    class="rounded-lg bg-amber-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-amber-700 disabled:opacity-50"
                >
                    {{
                        processing
                            ? isAr
                                ? $t('system.settings.saving...')
                                : 'Saving...'
                            : isAr
                              ? $t('system.settings.save')
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
                        {{ isAr ? $t('system.settings.tier_3') : 'Tier' }}
                        {{ key.replace('tier_', '') }} —
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
import { useI18n } from 'vue-i18n';
const { t } = useI18n();

import { computed, onMounted, ref } from 'vue';
import { Head, router, Link } from '@inertiajs/vue3';
import SystemLayout from '@/Layouts/SystemLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';

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
    { key: 'in_app', label: isAr.value ? t('system.settings.in-app') : 'In-app' },
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
    quiet_hours_start: props.config.quiet_hours_start || '',
    quiet_hours_end: props.config.quiet_hours_end || '',
    auto_suspend_after_days: props.config.auto_suspend_after_days || '',
});

const processing = ref(false);

async function save() {
    processing.value = true;
    try {
        await router.put('/system/settings/dunning', {
            enabled: form.value.enabled,
            active_tiers: form.value.active_tiers_list.join(','),
            channel_matrix: form.value.channel_matrix,
            quiet_hours_start: form.value.quiet_hours_start || null,
            quiet_hours_end: form.value.quiet_hours_end || null,
            auto_suspend_after_days: form.value.auto_suspend_after_days || null,
        });
    } finally {
        processing.value = false;
    }
}
</script>
