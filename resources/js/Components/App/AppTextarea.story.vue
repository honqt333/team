<script setup>
/**
 * AppTextarea Story — visual reference.
 */
import { ref } from 'vue';
import AppTextarea from './AppTextarea.vue';

const notes = ref('');
const description = ref('Initial description text.');
</script>

<template>
    <div
        class="p-8 space-y-10 bg-[var(--color-background)] text-[var(--color-text-primary)] min-h-screen"
    >
        <header class="space-y-2">
            <h1 class="app-display text-3xl font-bold">AppTextarea — Design System Story</h1>
            <p class="text-[var(--color-text-secondary)]">
                Token-driven textarea with character counter.
            </p>
        </header>

        <section class="space-y-3 max-w-2xl">
            <h2 class="app-display text-xl font-semibold">Basics</h2>
            <AppTextarea
                v-model="notes"
                label="ملاحظات"
                placeholder="اكتب ملاحظاتك هنا..."
                :rows="4"
            />
            <AppTextarea
                v-model="description"
                label="الوصف"
                hint="حدّث الوصف بوضوح حتى يفهمه العميل"
                :rows="6"
            />
        </section>

        <section class="space-y-3 max-w-2xl">
            <h2 class="app-display text-xl font-semibold">States</h2>
            <AppTextarea v-model="description" label="وصف مختصر" :maxlength="120" :rows="3" />
            <AppTextarea
                v-model="description"
                label="حقل به خطأ"
                error="الرجاء إدخال وصف لا يقل عن 10 أحرف"
            />
            <AppTextarea label="معطّل" model-value="—" disabled />
        </section>
    </div>
</template>
