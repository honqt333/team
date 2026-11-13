<template>
    <SystemLayout>
        <Head
            :title="
                isAr
                    ? $t('system.settings.dunning_settings', { name: tenant.name })
                    : `Dunning config — ${tenant.name}`
            "
        />

        <div class="space-y-6">
            <!-- Header -->
            <PageHeader
                :title="$t('system.settings.dunning_settings_colon', { name: tenant.name })"
                :subtitle="tenant.email"
            >
                <template #back>
                    <Link
                        :href="`/system/tenants/${tenant.id}`"
                        class="p-2 rounded-xl text-gray-400 hover:text-gray-600 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors"
                        :title="$t('common.back')"
                    >
                        <svg
                            class="w-5 h-5 rtl:rotate-180"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M10 19l-7-7m0 0l7-7m-7 7h18"
                            />
                        </svg>
                    </Link>
                </template>
                <template #icon>
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
                        />
                    </svg>
                </template>
            </PageHeader>

            <!-- Global baseline (read-only) -->
            <div
                class="rounded-xl border border-blue-200 bg-blue-50 p-5 dark:border-blue-800/40 dark:bg-blue-900/20"
            >
                <h3 class="text-sm font-bold text-blue-900 dark:text-blue-200">
                    {{ $t('system.settings.global_baseline_reference') }}
                </h3>
                <dl class="mt-3 grid grid-cols-2 gap-3 text-xs sm:grid-cols-4">
                    <div>
                        <dt class="text-blue-700/70 dark:text-blue-300/70">
                            {{ $t('common.enabled') }}
                        </dt>
                        <dd
                            class="font-mono text-sm font-semibold text-blue-900 dark:text-blue-100"
                        >
                            {{ global_config.enabled ? '✓' : '✗' }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-blue-700/70 dark:text-blue-300/70">
                            {{ $t('system.settings.active_tiers') }}
                        </dt>
                        <dd
                            class="font-mono text-sm font-semibold text-blue-900 dark:text-blue-100"
                        >
                            {{ global_config.active_tiers }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-blue-700/70 dark:text-blue-300/70">
                            {{ $t('system.settings.quiet_hours_2') }}
                        </dt>
                        <dd
                            class="font-mono text-sm font-semibold text-blue-900 dark:text-blue-100"
                        >
                            {{
                                global_config.quiet_hours_start && global_config.quiet_hours_end
                                    ? `${global_config.quiet_hours_start} – ${global_config.quiet_hours_end}`
                                    : $t('system.settings.disabled_label')
                            }}
                        </dd>
                    </div>
                </dl>
            </div>

            <!-- Override form -->
            <form
                @submit.prevent="submit"
                class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800"
            >
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                    {{ $t('system.settings.per-tenant_override') }}
                </h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    {{ $t('system.settings.empty_fields_fall_back_to_the_general_settings._') }}
                </p>

                <div class="mt-5 space-y-4">
                    <label class="flex cursor-pointer items-start gap-3">
                        <AppToggle v-model="form.enabled" />
                        <div>
                            <div class="text-sm font-medium text-gray-900 dark:text-white">
                                {{ $t('system.settings.enable_override') }}
                            </div>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                {{
                                    $t(
                                        'system.settings.when_on_this_tenant_uses_the_values_below_instea'
                                    )
                                }}
                            </p>
                        </div>
                    </label>

                    <div v-if="form.enabled">
                        <AppInput
                            v-model="form.active_tiers"
                            type="text"
                            :label="$t('system.settings.active_tiers_2')"
                            placeholder="tier_1,tier_2,tier_3,tier_4"
                            class="font-mono"
                        />
                    </div>

                    <div v-if="form.enabled">
                        <label
                            class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300"
                        >
                            {{ $t('system.settings.quiet_hours_optional') }}
                        </label>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="mb-1 block text-xs text-gray-500 dark:text-gray-400">
                                    {{ $t('common.from') }}
                                </label>
                                <input
                                    v-model="form.quiet_hours_start"
                                    type="time"
                                    class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                />
                            </div>
                            <div>
                                <label class="mb-1 block text-xs text-gray-500 dark:text-gray-400">
                                    {{ $t('common.to') }}
                                </label>
                                <input
                                    v-model="form.quiet_hours_end"
                                    type="time"
                                    class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                />
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-6 flex flex-wrap items-center justify-between gap-3">
                    <button
                        v-if="override"
                        type="button"
                        @click="clearOverride"
                        class="rounded-lg border border-rose-300 bg-white px-4 py-2 text-sm font-medium text-rose-600 hover:bg-rose-50 dark:border-rose-800 dark:bg-gray-800 dark:text-rose-400 dark:hover:bg-rose-900/20"
                    >
                        {{ $t('system.settings.remove_override') }}
                    </button>
                    <div class="ml-auto flex gap-2">
                        <Link
                            :href="`/system/tenants/${tenant.id}`"
                            class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600"
                        >
                            {{ $t('common.cancel') }}
                        </Link>
                        <button
                            type="submit"
                            :disabled="processing"
                            class="rounded-lg bg-amber-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-amber-700 disabled:opacity-50"
                        >
                            {{ processing ? '…' : $t('common.save') }}
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </SystemLayout>
</template>

<script setup>
import AppToggle from '@/Components/Common/AppToggle.vue';
import AppInput from '@/Components/App/AppInput.vue';
import { useI18n } from 'vue-i18n';
const { t } = useI18n();

import { computed, ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import SystemLayout from '@/Layouts/SystemLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import { useConfirm } from '@/Composables/useConfirm';

const { confirm } = useConfirm();

const props = defineProps({
    tenant: { type: Object, required: true },
    global_config: { type: Object, required: true },
    override: { type: Object, default: null },
});

const isAr = computed(() => (document?.documentElement?.lang || 'ar') === 'ar');

const form = ref({
    enabled: props.override?.enabled ?? false,
    active_tiers: props.override?.active_tiers ?? '',
    quiet_hours_start: props.override?.quiet_hours_start ?? '',
    quiet_hours_end: props.override?.quiet_hours_end ?? '',
});

const processing = ref(false);

async function submit() {
    processing.value = true;
    try {
        await router.put(`/system/tenants/${props.tenant.id}/dunning`, {
            enabled: form.value.enabled,
            active_tiers: form.value.active_tiers || props.global_config.active_tiers,
            channel_matrix: props.global_config.channel_matrix,
            quiet_hours_start: form.value.quiet_hours_start || null,
            quiet_hours_end: form.value.quiet_hours_end || null,
        });
    } finally {
        processing.value = false;
    }
}

async function clearOverride() {
    const ok = await confirm({
        title: t('common.delete'),
        message: t('system.settings.remove_the_override'),
        confirmText: t('common.delete'),
        cancelText: t('common.cancel'),
        type: 'danger',
    });
    if (!ok) {
        return;
    }
    await router.delete(`/system/tenants/${props.tenant.id}/dunning`);
}
</script>
