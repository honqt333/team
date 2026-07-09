<script setup>
/**
 * AppInput Story — visual reference for AppInput variants.
 * Companion to AppInput.vue. Not yet a real Histoire story —
 * see AppButton.story.vue for context.
 */
import { ref } from 'vue';
import AppInput from './AppInput.vue';

const name = ref('');
const email = ref('');
const amount = ref(0);
const password = ref('');
const plate = ref('');
const errorName = ref(null);
const hintEmail = ref('نستخدم هذا البريد لإرسال إشعارات الفاتورة فقط.');
</script>

<template>
    <div
        class="p-8 space-y-10 bg-[var(--color-background)] text-[var(--color-text-primary)] min-h-screen"
    >
        <header class="space-y-2">
            <h1 class="app-display text-3xl font-bold">AppInput — Design System Story</h1>
            <p class="text-[var(--color-text-secondary)]">
                Token-driven input. Wraps
                <code>TextInput.vue</code>
                for backwards compat.
            </p>
        </header>

        <section class="space-y-3 max-w-xl">
            <h2 class="app-display text-xl font-semibold">Basics</h2>
            <AppInput v-model="name" label="الاسم الكامل" placeholder="مثال: أحمد العتيبي" />
            <AppInput v-model="email" label="البريد الإلكتروني" type="email" :hint="hintEmail" />
            <AppInput v-model="amount" label="المبلغ" type="number" suffix="ر.س" />
            <AppInput v-model="password" label="كلمة المرور" type="password" required />
        </section>

        <section class="space-y-3 max-w-xl">
            <h2 class="app-display text-xl font-semibold">States</h2>
            <AppInput
                v-model="plate"
                label="رقم اللوحة"
                placeholder="ABC-1234"
                :error="errorName"
                hint="اكتب اللوحة بهذا الشكل"
                @update:model-value="
                    (v) => (errorName = v.length < 3 ? 'الرجاء إدخال لوحة صحيحة' : null)
                "
            />
            <AppInput label="محتوى معطّل" model-value="—" disabled />
            <AppInput label="حقل مطلوب" placeholder="املأ هذا الحقل" required />
        </section>

        <section class="space-y-3 max-w-xl">
            <h2 class="app-display text-xl font-semibold">With prefix</h2>
            <AppInput v-model="email" label="معرّف" prefix="@" placeholder="username" />
        </section>
    </div>
</template>
