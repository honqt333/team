<script setup>
import { useI18n } from 'vue-i18n';
const { t } = useI18n();

/**
 * AppSelect Story — visual reference for AppSelect.
 */
import { ref } from 'vue';
import AppSelect from './AppSelect.vue';

const status = ref('');
const city = ref('');
const errorStatus = ref(t('validation.required'));
</script>

<template>
    <div
        class="p-8 space-y-10 bg-[var(--color-background)] text-[var(--color-text-primary)] min-h-screen"
    >
        <header class="space-y-2">
            <h1 class="app-display text-3xl font-bold">AppSelect — Design System Story</h1>
            <p class="text-[var(--color-text-secondary)]">
                Token-driven select. Wraps
                <code>SelectInput.vue</code>
                .
            </p>
        </header>

        <section class="space-y-3 max-w-md">
            <h2 class="app-display text-xl font-semibold">Basics</h2>
            <AppSelect v-model="status" label="حالة أمر العمل" placeholder="اختر الحالة">
                <option value="open">{{ $t('center_settings.working_hours.open') }}</option>
                <option value="in_progress">
                    {{ $t('work_orders.work_order_statuses.in_progress') }}
                </option>
                <option value="done">{{ $t('hr.contracts.status.expired') }}</option>
                <option value="cancelled">{{ $t('common.status_cancelled') }}</option>
            </AppSelect>

            <AppSelect v-model="city" label="المدينة" hint="المدن المتاحة للتوصيل">
                <option value="riyadh">{{ $t('common.city_riyadh') }}</option>
                <option value="jeddah">{{ $t('company_profile.address.city_placeholder') }}</option>
                <option value="dammam">{{ $t('common.city_dammam') }}</option>
            </AppSelect>
        </section>

        <section class="space-y-3 max-w-md">
            <h2 class="app-display text-xl font-semibold">States</h2>
            <AppSelect
                v-model="status"
                label="حالة أمر العمل"
                required
                :error="errorStatus"
                placeholder="اختر الحالة"
            >
                <option value="open">{{ $t('center_settings.working_hours.open') }}</option>
                <option value="in_progress">
                    {{ $t('work_orders.work_order_statuses.in_progress') }}
                </option>
            </AppSelect>
            <AppSelect label="معطّل" disabled>
                <option>—</option>
            </AppSelect>
        </section>
    </div>
</template>
