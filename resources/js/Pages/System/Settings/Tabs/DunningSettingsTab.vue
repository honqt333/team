<template>
    <div class="space-y-6">
        <!-- Sub-navigation tabs (Rules vs. Audit Log) -->
        <div class="flex items-center gap-2 border-b border-gray-200 dark:border-gray-700 pb-3">
            <button
                type="button"
                @click="activeSub = 'rules'"
                :class="[
                    'px-4 py-2 text-sm font-semibold rounded-xl transition-all',
                    activeSub === 'rules'
                        ? 'bg-amber-500 text-white shadow-sm'
                        : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800',
                ]"
            >
                {{ $t('system.settings.dunning_settings') }}
            </button>
            <button
                type="button"
                @click="switchToLog"
                :class="[
                    'px-4 py-2 text-sm font-semibold rounded-xl transition-all flex items-center gap-2',
                    activeSub === 'log'
                        ? 'bg-amber-500 text-white shadow-sm'
                        : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800',
                ]"
            >
                <span>{{ $t('system.settings.dunning_log') }}</span>
                <span
                    v-if="logs?.total"
                    class="px-2 py-0.5 text-xs rounded-full bg-white/20 dark:bg-black/20"
                >
                    {{ logs.total }}
                </span>
            </button>
        </div>

        <!-- 1. Rules View -->
        <div v-if="activeSub === 'rules'" class="space-y-6">
            <!-- Global toggle -->
            <div
                class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800"
            >
                <label class="flex cursor-pointer items-start gap-3">
                    <AppToggle v-model="form.enabled" />
                    <div class="flex-1">
                        <div class="text-base font-semibold text-gray-900 dark:text-white">
                            {{ $t('system.settings.enable_automated_dunning') }}
                        </div>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            {{
                                $t(
                                    'system.settings.when_enabled_the_daily_cron_sends_reminders_to_t'
                                )
                            }}
                        </p>
                    </div>
                </label>
            </div>

            <!-- Active tiers -->
            <div
                class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800"
            >
                <h3 class="text-base font-semibold text-gray-900 dark:text-white">
                    {{ $t('system.settings.active_tiers') }}
                </h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    {{ $t('system.settings.uncheck_a_tier_to_skip_it_globally_skipped_tier') }}
                </p>

                <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    <label
                        v-for="(def, key) in tier_definitions"
                        :key="key"
                        class="flex cursor-pointer items-start gap-3 rounded-lg border border-gray-200 p-3 hover:bg-gray-50 dark:border-gray-700 dark:hover:bg-gray-700/60"
                    >
                        <AppToggle :value="key" v-model="form.active_tiers_list" />
                        <div class="flex-1 text-sm">
                            <div class="font-medium text-gray-900 dark:text-white">
                                {{ $t('system.settings.tier_3') }}
                                {{ key.replace('tier_', '') }} —
                                {{ def.tone[isAr ? 'ar' : 'en'] }}
                            </div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">
                                {{
                                    isAr
                                        ? $t('system.settings.days_overdue', { days: def.days })
                                        : `${def.days} days overdue`
                                }}
                            </div>
                        </div>
                    </label>
                </div>
            </div>

            <!-- Quiet hours & Auto suspend -->
            <div
                class="grid grid-cols-1 gap-4 md:grid-cols-2 rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800"
            >
                <div>
                    <h3 class="text-base font-semibold text-gray-900 dark:text-white">
                        {{ $t('system.settings.quiet_hours_ksa') }}
                    </h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        {{ $t('system.settings.skip_outbound_messages_during_these_hours_') }}
                    </p>
                    <div class="mt-3 flex items-center gap-2">
                        <input
                            v-model="form.quiet_hours_start"
                            type="time"
                            class="rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                        />
                        <span class="text-gray-500 dark:text-gray-400">
                            {{ $t('system.settings.to') }}
                        </span>
                        <input
                            v-model="form.quiet_hours_end"
                            type="time"
                            class="rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                        />
                    </div>
                </div>

                <div>
                    <h3 class="text-base font-semibold text-gray-900 dark:text-white">
                        {{ $t('system.settings.auto_suspension') }}
                    </h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        {{ $t('system.settings.automatically_suspend_the_workshop_after_invo') }}
                    </p>
                    <div class="mt-3 flex items-center gap-2">
                        <div class="w-28">
                            <AppInput
                                v-model.number="form.auto_suspend_after_days"
                                type="number"
                                min="1"
                                max="90"
                                placeholder="e.g. 21"
                            />
                        </div>
                        <span class="text-sm text-gray-500 dark:text-gray-400">
                            {{ $t('system.settings.days_overdue_2') }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Channel Matrix -->
            <div
                class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800"
            >
                <div class="border-b border-gray-200 p-5 dark:border-gray-700">
                    <h3 class="text-base font-semibold text-gray-900 dark:text-white">
                        {{ $t('system.settings.channel_matrix') }}
                    </h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        {{ $t('system.settings.select_which_channels_are_used_for_each_tier') }}
                    </p>
                </div>

                <DataTable structured class="w-full text-sm">
                    <thead class="bg-gray-50 text-xs uppercase text-gray-500 dark:bg-gray-900/40">
                        <tr>
                            <th class="px-4 py-3 text-start">
                                {{ $t('system.settings.tier_2') }}
                            </th>
                            <th
                                v-for="channel in availableChannels"
                                :key="channel.key"
                                class="px-4 py-3 text-center"
                            >
                                {{ channel.label }}
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        <tr v-for="(def, tierKey) in tier_definitions" :key="tierKey">
                            <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">
                                {{ $t('system.settings.tier_3') }}
                                {{ tierKey.replace('tier_', '') }}
                                ({{ def.days }}d)
                            </td>
                            <td
                                v-for="channel in availableChannels"
                                :key="channel.key"
                                class="px-4 py-3 text-center"
                            >
                                <AppToggle
                                    :checked="isChannelEnabled(tierKey, channel.key)"
                                    @change="toggleChannel(tierKey, channel.key, $event)"
                                />
                            </td>
                        </tr>
                    </tbody>
                </DataTable>
            </div>

            <!-- Save button -->
            <div class="flex justify-end gap-3">
                <button
                    @click="save"
                    :disabled="processing"
                    class="rounded-xl bg-amber-600 px-6 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-amber-700 disabled:opacity-50 transition-all"
                >
                    {{ processing ? $t('system.settings.saving') : $t('system.settings.save') }}
                </button>
            </div>
        </div>

        <!-- 2. Audit Log View -->
        <div v-else-if="activeSub === 'log'" class="space-y-6">
            <!-- Filters -->
            <div
                class="flex flex-wrap items-end gap-3 rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800"
            >
                <div class="flex-1 min-w-[150px]">
                    <label class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">
                        {{ $t('common.status') }}
                    </label>
                    <SearchableSelect
                        v-model="filtersState.status"
                        :options="statusOptions"
                        option-label="label"
                        option-value="value"
                        compact
                    />
                </div>
                <div class="flex-1 min-w-[150px]">
                    <label class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">
                        {{ $t('system.settings.tier') }}
                    </label>
                    <SearchableSelect
                        v-model="filtersState.tier"
                        :options="tierOptions"
                        option-label="label"
                        option-value="value"
                        compact
                    />
                </div>
                <button
                    @click="applyFilters"
                    class="rounded-xl bg-amber-600 px-4 py-2 text-sm font-semibold text-white hover:bg-amber-700"
                >
                    {{ $t('common.apply') }}
                </button>
            </div>

            <!-- Log table -->
            <div
                class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800"
            >
                <DataTable structured class="w-full text-sm">
                    <thead class="bg-gray-50 text-xs uppercase text-gray-500 dark:bg-gray-900/40">
                        <tr>
                            <th class="px-4 py-3 text-start">
                                {{ $t('common.date') }}
                            </th>
                            <th class="px-4 py-3 text-start">
                                {{ $t('system.settings.tenant') }}
                            </th>
                            <th class="px-4 py-3 text-start">
                                {{ $t('system.settings.invoice') }}
                            </th>
                            <th class="px-4 py-3 text-start">
                                {{ $t('system.settings.tier') }}
                            </th>
                            <th class="px-4 py-3 text-start">
                                {{ $t('system.settings.channel') }}
                            </th>
                            <th class="px-4 py-3 text-start">
                                {{ $t('common.status') }}
                            </th>
                            <th class="px-4 py-3 text-start">
                                {{ $t('common.error') }}
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        <tr v-for="log in logs?.data || []" :key="log.id">
                            <td class="px-4 py-3 text-gray-700 dark:text-gray-300">
                                {{ formatDate(log.sent_at) }}
                            </td>
                            <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">
                                {{ log.tenant?.name || '—' }}
                            </td>
                            <td class="px-4 py-3 text-gray-700 dark:text-gray-300">
                                {{ log.invoice?.invoice_number || '—' }}
                            </td>
                            <td class="px-4 py-3">
                                <span
                                    class="rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-700 dark:bg-gray-700 dark:text-gray-300"
                                >
                                    {{ log.tier_key?.replace('tier_', '') }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-gray-700 dark:text-gray-300">
                                {{ log.channel }}
                            </td>
                            <td class="px-4 py-3">
                                <span
                                    :class="statusClass(log.status)"
                                    class="inline-block rounded-full px-2 py-0.5 text-xs font-semibold"
                                >
                                    {{ statusLabel(log.status) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-xs text-rose-600 dark:text-rose-400">
                                {{ log.error_message || '—' }}
                            </td>
                        </tr>
                        <tr v-if="!logs?.data?.length">
                            <td
                                colspan="7"
                                class="px-4 py-8 text-center text-sm text-gray-500 dark:text-gray-400"
                            >
                                {{ $t('system.settings.no_log_entries_yet') }}
                            </td>
                        </tr>
                    </tbody>
                </DataTable>
            </div>
        </div>
    </div>
</template>

<script setup>
import DataTable from '@/Components/Common/DataTable.vue';
import AppToggle from '@/Components/Common/AppToggle.vue';
import { computed, ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import AppInput from '@/Components/App/AppInput.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import { useToast } from '@/Composables/useToast';

const { t } = useI18n();
const { success } = useToast();

const props = defineProps({
    config: { type: Object, default: () => ({}) },
    tier_definitions: { type: Object, default: () => ({}) },
    default_channel_matrix: { type: Object, default: () => ({}) },
    logs: { type: Object, default: () => ({}) },
    filters: { type: Object, default: () => ({}) },
    sub: { type: String, default: 'rules' },
});

const isAr = computed(() => (document?.documentElement?.lang || 'ar') === 'ar');
const activeSub = ref(props.sub === 'log' ? 'log' : 'rules');

const availableChannels = [
    { key: 'email', label: 'Email' },
    { key: 'in_app', label: 'In-app' },
    { key: 'sms', label: 'SMS' },
    { key: 'whatsapp', label: 'WhatsApp' },
];

const form = ref({
    enabled: props.config?.enabled || false,
    active_tiers_list: (props.config?.active_tiers || '')
        .split(',')
        .map((s) => s.trim())
        .filter(Boolean),
    channel_matrix: { ...(props.config?.channel_matrix || props.default_channel_matrix || {}) },
    quiet_hours_start: props.config?.quiet_hours_start || '',
    quiet_hours_end: props.config?.quiet_hours_end || '',
    auto_suspend_after_days: props.config?.auto_suspend_after_days || '',
});

const processing = ref(false);

const isChannelEnabled = (tierKey, channelKey) => {
    return !!form.value.channel_matrix?.[tierKey]?.[channelKey];
};

const toggleChannel = (tierKey, channelKey, checked) => {
    if (!form.value.channel_matrix[tierKey]) {
        form.value.channel_matrix[tierKey] = {};
    }
    form.value.channel_matrix[tierKey][channelKey] = checked;
};

const save = () => {
    processing.value = true;
    router.put(
        '/system/settings/dunning',
        {
            enabled: form.value.enabled,
            active_tiers: form.value.active_tiers_list.join(','),
            channel_matrix: form.value.channel_matrix,
            quiet_hours_start: form.value.quiet_hours_start || null,
            quiet_hours_end: form.value.quiet_hours_end || null,
            auto_suspend_after_days: form.value.auto_suspend_after_days || null,
        },
        {
            preserveScroll: true,
            onSuccess: () => {
                success(t('system.settings.saved'));
            },
            onFinish: () => {
                processing.value = false;
            },
        }
    );
};

// Log view methods
const filtersState = ref({
    status: props.filters?.status || '',
    tier: props.filters?.tier || '',
});

const statusOptions = computed(() => [
    { value: '', label: t('common.all') },
    { value: 'sent', label: t('system.credits.sent') },
    { value: 'failed', label: t('system.credits.failed') },
    { value: 'skipped', label: t('system.settings.skipped') },
]);

const tierOptions = computed(() => [
    { value: '', label: t('common.all') },
    { value: 'tier_1', label: 'Tier 1' },
    { value: 'tier_2', label: 'Tier 2' },
    { value: 'tier_3', label: 'Tier 3' },
    { value: 'tier_4', label: 'Tier 4' },
    { value: 'tier_5', label: 'Tier 5' },
]);

const switchToLog = () => {
    activeSub.value = 'log';
    router.get(
        '/system/settings',
        { tab: 'dunning', sub: 'log' },
        { preserveState: true, preserveScroll: true }
    );
};

const applyFilters = () => {
    router.get(
        '/system/settings',
        {
            tab: 'dunning',
            sub: 'log',
            status: filtersState.value.status || undefined,
            tier: filtersState.value.tier || undefined,
        },
        { preserveState: true }
    );
};

const formatDate = (date) => (date ? new Date(date).toLocaleString('ar-SA-u-nu-latn') : '—');

const statusClass = (status) =>
    ({
        sent: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-300',
        failed: 'bg-rose-100 text-rose-800 dark:bg-rose-950/40 dark:text-rose-300',
        skipped: 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-300',
    })[status] || 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-300';

const statusLabel = (status) =>
    ({
        sent: t('system.credits.sent'),
        failed: t('system.credits.failed'),
        skipped: t('system.settings.skipped'),
    })[status] || status;
</script>
