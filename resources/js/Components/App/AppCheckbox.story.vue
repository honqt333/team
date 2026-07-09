<script setup>
/**
 * AppCheckbox Story — visual reference.
 */
import { ref } from 'vue';
import AppCheckbox from './AppCheckbox.vue';

const agreed = ref(false);
const notify = ref(true);
const acceptTerms = ref(false);
const errorTerms = ref('يجب الموافقة للمتابعة');
</script>

<template>
    <div
        class="p-8 space-y-10 bg-[var(--color-background)] text-[var(--color-text-primary)] min-h-screen"
    >
        <header class="space-y-2">
            <h1 class="app-display text-3xl font-bold">AppCheckbox — Design System Story</h1>
            <p class="text-[var(--color-text-secondary)]">Token-driven checkbox.</p>
        </header>

        <section class="space-y-3 max-w-md">
            <h2 class="app-display text-xl font-semibold">Basics</h2>
            <AppCheckbox v-model="agreed" label="أوافق على الشروط" required />
            <AppCheckbox v-model="notify" label="أرسل لي إشعارات الفاتورة" />
            <AppCheckbox label="خيار معطّل" disabled model-value />
        </section>

        <section class="space-y-3 max-w-md">
            <h2 class="app-display text-xl font-semibold">With hint / error</h2>
            <AppCheckbox
                v-model="acceptTerms"
                label="أوافق على شروط الاستخدام"
                hint="يمكنك التراجع في أي وقت من الإعدادات"
            />
            <AppCheckbox
                v-model="acceptTerms"
                label="أوافق على شروط الاستخدام"
                :error="errorTerms"
            />
        </section>

        <section class="space-y-3 max-w-md">
            <h2 class="app-display text-xl font-semibold">With default slot</h2>
            <AppCheckbox v-model="agreed">
                <span class="font-semibold">أتعهد بصحة البيانات</span>
                <span class="block text-xs text-[var(--color-text-muted)]">
                    هذا الإقرار يُستخدم لأغراض قانونية فقط
                </span>
            </AppCheckbox>
        </section>
    </div>
</template>
