<template>
    <div
        :dir="isRtl ? 'rtl' : 'ltr'"
        :style="cssVars"
        class="min-h-screen overflow-hidden bg-[#f6f8fb] text-slate-950"
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
                class="grid w-full overflow-hidden rounded-[2rem] border border-white/80 bg-white shadow-[0_30px_90px_-35px_rgba(15,23,42,0.28)] lg:grid-cols-[0.9fr_1.1fr]"
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
                            <p class="truncate text-lg font-extrabold">{{ tenant.site_name }}</p>
                            <p v-if="tenant.tagline" class="mt-0.5 truncate text-sm text-white/70">
                                {{ tenant.tagline }}
                            </p>
                        </div>
                    </div>

                    <div class="relative my-auto max-w-md">
                        <span
                            class="inline-flex rounded-full border border-white/20 bg-white/10 px-3 py-1 text-xs font-bold text-white/90"
                        >
                            {{ eyebrow }}
                        </span>
                        <h1 class="mt-5 text-4xl font-black leading-[1.2] tracking-tight">
                            {{ heroTitle }}
                        </h1>
                        <p class="mt-4 max-w-sm text-base leading-8 text-white/75">
                            {{ heroText }}
                        </p>

                        <div class="mt-9 grid gap-3">
                            <div
                                v-for="item in benefits"
                                :key="item"
                                class="flex items-center gap-3 text-sm font-semibold text-white/90"
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
                        class="relative inline-flex w-fit items-center gap-2 text-sm font-semibold text-white/75 hover:text-white"
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
                    class="flex min-h-[620px] flex-col px-5 py-5 sm:px-10 sm:py-8 lg:min-h-[680px] lg:px-16"
                >
                    <div class="flex items-center justify-between gap-3">
                        <a
                            :href="`/${tenant.slug}`"
                            class="inline-flex items-center gap-2 lg:hidden"
                        >
                            <BrandMark :tenant="tenant" />
                            <span class="max-w-[170px] truncate text-sm font-extrabold">
                                {{ tenant.site_name }}
                            </span>
                        </a>
                        <span class="hidden lg:block" />
                        <LanguageToggle
                            :tenant="tenant"
                            :locale="locale"
                            :base-path="languageBasePath"
                        />
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
                                class="text-3xl font-black tracking-tight text-slate-950 sm:text-[2rem]"
                            >
                                {{ title }}
                            </h2>
                            <p class="mt-2 text-sm leading-7 text-slate-500">
                                {{ subtitle }}
                            </p>
                        </div>

                        <slot />
                    </div>

                    <p class="text-center text-xs leading-6 text-slate-400">
                        {{ footerText }}
                    </p>
                </div>
            </section>
        </main>
    </div>
</template>

<script setup>
import { computed, onMounted } from 'vue';
import BrandMark from '@/Components/CustomerPortal/BrandMark.vue';
import LanguageToggle from '@/Components/CustomerPortal/LanguageToggle.vue';

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

const isRtl = computed(() => props.locale?.is_rtl ?? props.locale?.current === 'ar');
const cssVars = computed(() => ({
    '--portal-primary': props.tenant?.primary_color || '#4f46e5',
    '--portal-secondary': props.tenant?.secondary_color || '#0f766e',
    '--portal-primary-soft': `color-mix(in srgb, ${props.tenant?.primary_color || '#4f46e5'} 10%, white)`,
}));
const brandGradient = computed(
    () =>
        `linear-gradient(145deg, ${props.tenant?.primary_color || '#4f46e5'} 0%, color-mix(in srgb, ${props.tenant?.primary_color || '#4f46e5'} 68%, ${props.tenant?.secondary_color || '#0f766e'}) 100%)`
);

onMounted(() => document.documentElement.classList.remove('dark'));
</script>
