<template>
    <div
        :dir="isRtl ? 'rtl' : 'ltr'"
        :data-theme="isDark ? 'dark' : 'light'"
        :style="cssVars"
        class="min-h-screen overflow-hidden bg-[var(--color-background)] text-text-primary"
    >
        <div class="pointer-events-none fixed inset-0" aria-hidden="true">
            <div
                class="absolute -top-36 -end-28 h-96 w-96 rounded-full opacity-[0.12] blur-3xl"
                :style="{ backgroundColor: 'var(--portal-primary)' }"
            />
            <div
                class="absolute -bottom-40 -start-28 h-96 w-96 rounded-full opacity-[0.10] blur-3xl"
                :style="{ backgroundColor: 'var(--portal-secondary)' }"
            />
        </div>

        <main
            class="relative mx-auto flex min-h-screen max-w-6xl items-center px-4 py-6 sm:px-6 lg:px-8"
        >
            <section
                class="grid w-full overflow-hidden rounded-[2rem] border border-border bg-surface shadow-[0_30px_90px_-35px_rgba(15,23,42,0.28)] lg:grid-cols-[0.9fr_1.1fr]"
            >
                <aside
                    class="relative hidden min-h-[680px] overflow-hidden p-10 text-white lg:flex lg:flex-col"
                    :style="{ background: brandGradient }"
                >
                    <div
                        class="absolute -end-24 -top-20 h-72 w-72 rounded-full border border-white/15"
                    />
                    <div
                        class="absolute -end-8 -top-4 h-44 w-44 rounded-full border border-white/15"
                    />
                    <div
                        class="absolute bottom-16 start-10 h-24 w-24 rounded-full bg-white/10 blur-xl"
                    />

                    <div class="relative flex items-center gap-3">
                        <BrandMark :tenant="tenant" size="lg" />
                        <div class="min-w-0">
                            <p class="truncate text-lg font-extrabold text-white">
                                {{ tenant.site_name }}
                            </p>
                            <p v-if="tenant.tagline" class="mt-0.5 truncate text-sm text-white/95">
                                {{ tenant.tagline }}
                            </p>
                        </div>
                    </div>

                    <div class="relative my-auto max-w-md">
                        <span
                            class="inline-flex rounded-full border border-white/30 bg-white/15 px-3 py-1 text-xs font-bold text-white"
                        >
                            {{ eyebrow }}
                        </span>
                        <h1
                            class="mt-5 text-4xl font-black leading-[1.2] tracking-tight text-white"
                        >
                            {{ heroTitle }}
                        </h1>
                        <p class="mt-4 max-w-sm text-base leading-8 text-white/95">
                            {{ heroText }}
                        </p>

                        <div class="mt-9 grid gap-3">
                            <div
                                v-for="item in benefits"
                                :key="item"
                                class="flex items-center gap-3 text-sm font-semibold text-white"
                            >
                                <span
                                    class="flex h-7 w-7 items-center justify-center rounded-full bg-white/15"
                                >
                                    <svg
                                        class="h-4 w-4"
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="currentColor"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2.5"
                                            d="M5 13l4 4L19 7"
                                        />
                                    </svg>
                                </span>
                                <span>{{ item }}</span>
                            </div>
                        </div>
                    </div>

                    <a
                        :href="`/${tenant.slug}`"
                        class="relative inline-flex w-fit items-center gap-2 rounded-lg px-1 py-0.5 text-sm font-semibold text-white/95 transition hover:text-white focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white/90"
                    >
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M3 12l9-9 9 9M5 10v10h14V10"
                            />
                        </svg>
                        {{ backLabel }}
                    </a>
                </aside>

                <div
                    class="flex min-h-[620px] min-w-0 flex-col px-5 py-5 sm:px-10 sm:py-8 lg:min-h-[680px] lg:px-16"
                >
                    <div class="flex items-center justify-between gap-2">
                        <a
                            :href="`/${tenant.slug}`"
                            class="inline-flex min-w-0 items-center gap-2 lg:hidden"
                        >
                            <BrandMark :tenant="tenant" class="shrink-0" />
                            <span class="truncate text-sm font-extrabold">
                                {{ tenant.site_name }}
                            </span>
                        </a>
                        <span class="hidden lg:block" />
                        <div class="flex shrink-0 items-center gap-1.5 sm:gap-2">
                            <LanguageToggle
                                :tenant="tenant"
                                :locale="locale"
                                :base-path="languageBasePath"
                            />
                            <button
                                type="button"
                                data-testid="portal-theme-toggle"
                                @click="toggleThemeHandler"
                                class="inline-flex h-8 w-8 items-center justify-center rounded-full border border-border bg-surface text-text-secondary transition hover:bg-surface-muted hover:text-text-primary focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-focus-ring"
                                :aria-label="t('common.toggle_theme')"
                                :title="t('common.toggle_theme')"
                            >
                                <svg
                                    v-if="isDark"
                                    class="h-4 w-4 text-accent-gold"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"
                                    />
                                </svg>
                                <svg
                                    v-else
                                    class="h-4 w-4 text-text-secondary"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"
                                    />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <div class="my-auto mx-auto w-full max-w-md py-10">
                        <div class="mb-8">
                            <span
                                class="mb-4 inline-flex h-11 w-11 items-center justify-center rounded-2xl"
                                :style="{
                                    color: 'var(--portal-primary)',
                                    backgroundColor: 'var(--portal-primary-soft)',
                                }"
                            >
                                <slot name="icon" />
                            </span>
                            <h2
                                class="text-3xl font-black tracking-tight text-text-primary sm:text-[2rem]"
                            >
                                {{ title }}
                            </h2>
                            <p class="mt-2 text-sm leading-7 text-text-secondary">
                                {{ subtitle }}
                            </p>
                        </div>

                        <slot />
                    </div>

                    <p class="text-center text-xs leading-6 text-text-secondary">
                        {{ footerText }}
                    </p>
                </div>
            </section>
        </main>
    </div>
</template>

<script setup>
import { computed, watchEffect } from 'vue';
import { useI18n } from 'vue-i18n';
import BrandMark from '@/Components/CustomerPortal/BrandMark.vue';
import LanguageToggle from '@/Components/CustomerPortal/LanguageToggle.vue';
import { useTheme } from '@/Composables/useTheme';

const props = defineProps({
    tenant: { type: Object, required: true },
    locale: { type: Object, default: () => ({ current: 'ar', is_rtl: true }) },
    title: { type: String, required: true },
    subtitle: { type: String, default: '' },
    eyebrow: { type: String, default: '' },
    heroTitle: { type: String, default: '' },
    heroText: { type: String, default: '' },
    benefits: { type: Array, default: () => [] },
    backLabel: { type: String, default: '' },
    footerText: { type: String, default: '' },
    languageBasePath: { type: String, default: '' },
});

const { t } = useI18n();
const { isDark, toggle: toggleThemeHandler } = useTheme();

const isRtl = computed(() => props.locale?.is_rtl ?? props.locale?.current === 'ar');

watchEffect(() => {
    if (typeof document === 'undefined') return;
    const lang = props.locale?.current || (isRtl.value ? 'ar' : 'en');
    document.documentElement.lang = lang;
    document.documentElement.dir = isRtl.value ? 'rtl' : 'ltr';
});
const cssVars = computed(() => {
    const primary = props.tenant?.primary_color || '#2563eb';
    const secondary = props.tenant?.secondary_color || '#0f766e';
    const softMix = isDark.value ? '18%' : '12%';
    return {
        '--portal-primary': primary,
        '--portal-secondary': secondary,
        '--portal-primary-soft': `color-mix(in srgb, ${primary} ${softMix}, transparent)`,
    };
});
const brandGradient = computed(
    () =>
        `linear-gradient(145deg, ${props.tenant?.primary_color || '#2563eb'} 0%, color-mix(in srgb, ${props.tenant?.primary_color || '#2563eb'} 68%, ${props.tenant?.secondary_color || '#0f766e'}) 100%)`
);
</script>
