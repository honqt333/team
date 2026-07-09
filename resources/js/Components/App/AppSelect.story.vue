<script setup>
/**
 * AppSelect Story — visual reference for AppSelect.
 */
import { ref } from 'vue';
import AppSelect from './AppSelect.vue';

const status = ref('');
const city = ref('');
const errorStatus = ref('هذا الحقل مطلوب');
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
                <option value="open">مفتوح</option>
                <option value="in_progress">قيد التنفيذ</option>
                <option value="done">منتهي</option>
                <option value="cancelled">ملغي</option>
            </AppSelect>

            <AppSelect v-model="city" label="المدينة" hint="المدن المتاحة للتوصيل">
                <option value="riyadh">الرياض</option>
                <option value="jeddah">جدة</option>
                <option value="dammam">الدمام</option>
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
                <option value="open">مفتوح</option>
                <option value="in_progress">قيد التنفيذ</option>
            </AppSelect>
            <AppSelect label="معطّل" disabled>
                <option>—</option>
            </AppSelect>
        </section>
    </div>
</template>
