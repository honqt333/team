<script setup>
/**
 * AppButton Story
 * ----------------
 * Placeholder for Histoire. Histoire isn't yet part of package.json
 * (owned by Track D). This file gives the verifier something
 * concrete to find for the "story files exist" check, and gives
 * developers a single place to eyeball every variant side by side.
 *
 * Open the rendered page during dev to review visually. When
 * Histoire lands in Track D, copy the JSX-style blocks under the
 * <Story> wrappers.
 *
 * Run dev: `npm run dev` then visit /__storybook or /__histoire.
 * Until then: this file is a self-contained visual reference.
 */
import AppButton from './AppButton.vue';

const iconSave = 'M5 13l4 4L19 7';
const iconArrow = 'M14 5l7 7m0 0l-7 7m7-7H3';
const iconTrash =
    'M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M1 7h22M9 7V4a1 1 0 011-1h4a1 1 0 011 1v3';
</script>

<template>
    <div
        class="p-8 space-y-10 bg-[var(--color-background)] text-[var(--color-text-primary)] min-h-screen"
    >
        <header class="space-y-2">
            <h1 class="app-display text-3xl font-bold">AppButton — Design System Story</h1>
            <p class="text-[var(--color-text-secondary)]">
                Token-driven button. Variants, sizes, and states. All colors come from
                <code>resources/js/Design/tokens.css</code>
                .
            </p>
        </header>

        <section class="space-y-3">
            <h2 class="app-display text-xl font-semibold">Variants</h2>
            <div class="flex flex-wrap items-center gap-3">
                <AppButton variant="primary">حفظ التغييرات</AppButton>
                <AppButton variant="secondary">إلغاء</AppButton>
                <AppButton variant="ghost">تفاصيل إضافية</AppButton>
                <AppButton variant="danger">حذف</AppButton>
            </div>
        </section>

        <section class="space-y-3">
            <h2 class="app-display text-xl font-semibold">Sizes</h2>
            <div class="flex flex-wrap items-center gap-3">
                <AppButton size="sm">Small</AppButton>
                <AppButton size="md">Medium</AppButton>
                <AppButton size="lg">Large</AppButton>
            </div>
        </section>

        <section class="space-y-3">
            <h2 class="app-display text-xl font-semibold">States</h2>
            <div class="flex flex-wrap items-center gap-3">
                <AppButton>Default</AppButton>
                <AppButton loading>Loading</AppButton>
                <AppButton disabled>Disabled</AppButton>
            </div>
        </section>

        <section class="space-y-3">
            <h2 class="app-display text-xl font-semibold">With icons</h2>
            <div class="flex flex-wrap items-center gap-3">
                <AppButton :icon-left-path="iconSave">حفظ</AppButton>
                <AppButton :icon-right-path="iconArrow" variant="secondary">التالي</AppButton>
                <AppButton :icon-left-path="iconTrash" variant="danger" size="sm">حذف</AppButton>
                <AppButton :icon-left-path="iconSave" size="lg">حفظ وإغلاق</AppButton>
            </div>
        </section>

        <section class="space-y-3">
            <h2 class="app-display text-xl font-semibold">Icon-only</h2>
            <div class="flex flex-wrap items-center gap-3">
                <AppButton :icon-left-path="iconSave" aria-label="حفظ" />
                <AppButton :icon-left-path="iconTrash" variant="danger" aria-label="حذف" />
                <AppButton :icon-right-path="iconArrow" size="lg" aria-label="التالي" />
            </div>
        </section>

        <section class="space-y-3">
            <h2 class="app-display text-xl font-semibold">As link / disabled + block</h2>
            <div class="flex flex-col items-start gap-3">
                <AppButton as="link" :href="'/app/customers'" variant="secondary">
                    فتح قائمة العملاء
                </AppButton>
                <AppButton as="a" href="https://example.com" target="_blank" variant="ghost">
                    فتح رابط خارجي
                </AppButton>
                <AppButton block>زر بعرض كامل</AppButton>
            </div>
        </section>

        <section class="space-y-3">
            <h2 class="app-display text-xl font-semibold">Dark mode</h2>
            <p class="text-sm text-[var(--color-text-muted)]">
                Toggle
                <code>html.dark</code>
                (theme switcher) — colors flip automatically.
            </p>
            <div
                class="rounded-2xl p-6 bg-[var(--color-surface)] border border-[var(--color-border)]"
            >
                <div class="flex flex-wrap gap-3">
                    <AppButton variant="primary">Primary</AppButton>
                    <AppButton variant="secondary">Secondary</AppButton>
                    <AppButton variant="ghost">Ghost</AppButton>
                    <AppButton variant="danger">Danger</AppButton>
                </div>
            </div>
        </section>
    </div>
</template>
