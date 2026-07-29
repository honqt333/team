<template>
    <Head>
        <title>{{ seoTitle }}</title>
        <meta name="description" :content="seoDescription" />
        <meta property="og:title" :content="seoTitle" />
        <meta property="og:description" :content="seoDescription" />
        <meta property="og:type" content="website" />
        <meta property="og:locale" :content="isRtl ? 'ar_SA' : 'en_US'" />
        <meta v-if="site.logo_url" property="og:image" :content="site.logo_url" />
        <link v-if="site.logo_url" rel="icon" :href="site.logo_url" type="image/x-icon" />
    </Head>

    <div
        :dir="isRtl ? 'rtl' : 'ltr'"
        :data-theme="isDark ? 'dark' : 'light'"
        :style="cssVars"
        class="tenant-site min-h-screen bg-slate-50 text-slate-900 dark:bg-slate-950 dark:text-slate-100"
    >
        <!-- Top bar -->
        <header
            class="sticky top-0 z-40 backdrop-blur bg-white/80 dark:bg-slate-900/80 border-b border-slate-200 dark:border-slate-800"
        >
            <div
                class="max-w-6xl mx-auto px-4 sm:px-6 py-3 flex items-center justify-between gap-4"
            >
                <div class="flex items-center gap-3 min-w-0">
                    <img
                        v-if="site.logo_url"
                        :src="site.logo_url"
                        :alt="site.site_name"
                        class="h-10 w-auto max-w-[180px] object-contain"
                    />
                    <div
                        v-else
                        class="h-10 w-10 rounded-xl flex items-center justify-center text-white font-bold"
                        :style="{ backgroundColor: 'var(--tenant-primary, #4f46e5)' }"
                    >
                        {{ initial }}
                    </div>
                    <div class="min-w-0">
                        <h1 class="font-bold text-base sm:text-lg truncate">
                            {{ site.site_name }}
                        </h1>
                        <p
                            v-if="site.tagline"
                            class="text-xs text-slate-500 dark:text-slate-400 truncate"
                        >
                            {{ site.tagline }}
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <a
                        v-if="futurePortalEnabled"
                        href="#customer-portal"
                        class="hidden sm:inline-flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium border border-slate-200 dark:border-slate-700 hover:bg-slate-100 dark:hover:bg-slate-800"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"
                            />
                        </svg>
                        {{ t('tenant_site.nav.customer_portal') }}
                    </a>

                    <button
                        type="button"
                        @click="toggleTheme"
                        class="inline-flex items-center justify-center w-9 h-9 rounded-lg border border-slate-200 dark:border-slate-700 hover:bg-slate-100 dark:hover:bg-slate-800"
                        :aria-label="isDark ? lightModeLabel : darkModeLabel"
                    >
                        <svg
                            v-if="isDark"
                            class="w-4 h-4"
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
                            class="w-4 h-4"
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
        </header>

        <!-- Hero -->
        <section class="relative overflow-hidden">
            <div v-if="site.cover_url" class="absolute inset-0 -z-10">
                <img
                    :src="site.cover_url"
                    alt=""
                    class="w-full h-full object-cover opacity-30 dark:opacity-20"
                />
                <div
                    class="absolute inset-0 bg-gradient-to-b from-transparent via-white/60 to-white dark:via-slate-950/60 dark:to-slate-950"
                ></div>
            </div>
            <div
                v-else
                class="absolute inset-0 -z-10"
                :style="{
                    backgroundImage: heroGradient,
                }"
            ></div>

            <div class="max-w-6xl mx-auto px-4 sm:px-6 py-16 sm:py-24 text-center">
                <span
                    class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-medium"
                    :style="{
                        backgroundColor:
                            'color-mix(in srgb, var(--tenant-primary) 12%, transparent)',
                        color: 'var(--tenant-primary)',
                    }"
                >
                    <span
                        class="w-1.5 h-1.5 rounded-full"
                        :style="{ backgroundColor: 'var(--tenant-primary)' }"
                    ></span>
                    {{ t('tenant_site.hero.badge') }}
                </span>

                <h2 class="mt-6 text-3xl sm:text-5xl font-extrabold tracking-tight">
                    {{ site.site_name }}
                </h2>

                <p
                    v-if="site.tagline"
                    class="mt-4 text-base sm:text-lg text-slate-600 dark:text-slate-300 max-w-2xl mx-auto"
                >
                    {{ site.tagline }}
                </p>

                <div class="mt-8 flex flex-wrap items-center justify-center gap-3">
                    <a
                        v-if="site.phone_link"
                        :href="site.phone_link"
                        class="inline-flex items-center gap-2 px-5 py-3 rounded-xl text-white font-semibold shadow-lg shadow-indigo-500/20 hover:opacity-95 transition"
                        :style="{ backgroundColor: 'var(--tenant-primary)' }"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M3 5a2 2 0 012-2h2.28a2 2 0 011.94 1.515l.7 2.81a2 2 0 01-.45 1.95L8.09 10.91a16 16 0 006 6l1.64-1.39a2 2 0 011.95-.45l2.81.7A2 2 0 0122 17.71V20a2 2 0 01-2 2h-1C9.61 22 2 14.39 2 5V4"
                            />
                        </svg>
                        {{ site.contact_phone }}
                    </a>

                    <a
                        v-if="site.whatsapp_link"
                        :href="site.whatsapp_link"
                        target="_blank"
                        rel="noopener"
                        class="inline-flex items-center gap-2 px-5 py-3 rounded-xl font-semibold border border-emerald-500 text-emerald-600 dark:text-emerald-400 hover:bg-emerald-500/10 transition"
                    >
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                            <path
                                d="M.057 24l1.687-6.163a11.867 11.867 0 01-1.587-5.946C.16 5.335 5.495 0 12.05 0a11.817 11.817 0 018.413 3.488 11.824 11.824 0 013.48 8.414c-.003 6.557-5.338 11.892-11.893 11.892a11.9 11.9 0 01-5.688-1.448L.057 24zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884a9.86 9.86 0 001.683 5.51l-.999 3.648 3.805-.857z"
                            />
                        </svg>
                        {{ t('tenant_site.hero.whatsapp_cta') }}
                    </a>

                    <a
                        v-if="futurePortalEnabled"
                        href="#customer-portal"
                        class="inline-flex items-center gap-2 px-5 py-3 rounded-xl font-semibold border border-slate-200 dark:border-slate-700 hover:bg-slate-100 dark:hover:bg-slate-800"
                    >
                        {{ t('tenant_site.hero.book_cta') }}
                    </a>
                </div>
            </div>
        </section>

        <!-- Services -->
        <section v-if="services.length" class="py-14 sm:py-20">
            <div class="max-w-6xl mx-auto px-4 sm:px-6">
                <div class="text-center mb-10">
                    <h3 class="text-2xl sm:text-3xl font-bold">
                        {{ t('tenant_site.services.title') }}
                    </h3>
                    <p class="mt-2 text-slate-600 dark:text-slate-400">
                        {{ t('tenant_site.services.subtitle') }}
                    </p>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    <article
                        v-for="service in services"
                        :key="service.id"
                        class="group p-5 rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 hover:shadow-lg hover:-translate-y-0.5 transition"
                    >
                        <div
                            class="w-10 h-10 rounded-xl flex items-center justify-center mb-3"
                            :style="{
                                backgroundColor:
                                    'color-mix(in srgb, var(--tenant-primary) 12%, transparent)',
                                color: 'var(--tenant-primary)',
                            }"
                        >
                            <svg
                                class="w-5 h-5"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
                                />
                            </svg>
                        </div>
                        <h4 class="font-bold text-base">{{ service.name }}</h4>
                        <p
                            v-if="service.description"
                            class="mt-1.5 text-sm text-slate-600 dark:text-slate-400 line-clamp-3"
                        >
                            {{ service.description }}
                        </p>
                    </article>
                </div>
            </div>
        </section>

        <!-- Centers -->
        <section v-if="centers.length" class="py-14 sm:py-20 bg-slate-100/60 dark:bg-slate-900/40">
            <div class="max-w-6xl mx-auto px-4 sm:px-6">
                <div class="text-center mb-10">
                    <h3 class="text-2xl sm:text-3xl font-bold">
                        {{ t('tenant_site.centers.title') }}
                    </h3>
                    <p class="mt-2 text-slate-600 dark:text-slate-400">
                        {{ t('tenant_site.centers.subtitle') }}
                    </p>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    <article
                        v-for="center in centers"
                        :key="center.id"
                        class="p-5 rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900"
                    >
                        <div class="flex items-start justify-between gap-2">
                            <h4 class="font-bold text-base">{{ center.display_name }}</h4>
                            <span
                                v-if="center.is_main"
                                class="text-[10px] uppercase tracking-wide font-bold px-2 py-0.5 rounded-full"
                                :style="{
                                    backgroundColor:
                                        'color-mix(in srgb, var(--tenant-primary) 12%, transparent)',
                                    color: 'var(--tenant-primary)',
                                }"
                            >
                                {{ t('tenant_site.centers.main_badge') }}
                            </span>
                        </div>
                        <ul
                            v-if="
                                center.phone ||
                                center.email ||
                                (center.address &&
                                    (center.address.address_line || center.address.city))
                            "
                            class="mt-3 space-y-1.5 text-sm"
                        >
                            <li
                                v-if="center.phone"
                                class="flex items-center gap-2 text-slate-600 dark:text-slate-400"
                            >
                                <svg
                                    class="w-4 h-4 flex-shrink-0"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M3 5a2 2 0 012-2h2.28a2 2 0 011.94 1.515l.7 2.81a2 2 0 01-.45 1.95L8.09 10.91a16 16 0 006 6l1.64-1.39a2 2 0 011.95-.45l2.81.7A2 2 0 0122 17.71V20a2 2 0 01-2 2h-1C9.61 22 2 14.39 2 5V4"
                                    />
                                </svg>
                                <a :href="`tel:${center.phone}`" class="hover:underline" dir="ltr">
                                    {{ center.phone }}
                                </a>
                            </li>
                            <li
                                v-if="center.email"
                                class="flex items-center gap-2 text-slate-600 dark:text-slate-400"
                            >
                                <svg
                                    class="w-4 h-4 flex-shrink-0"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M3 8l9 6 9-6m-18 0v8a2 2 0 002 2h14a2 2 0 002-2V8m-18 0V6a2 2 0 012-2h14a2 2 0 012 2v2"
                                    />
                                </svg>
                                <a
                                    :href="`mailto:${center.email}`"
                                    class="hover:underline truncate"
                                >
                                    {{ center.email }}
                                </a>
                            </li>
                            <li
                                v-if="
                                    center.address &&
                                    (center.address.address_line || center.address.city)
                                "
                                class="flex items-start gap-2 text-slate-600 dark:text-slate-400"
                            >
                                <svg
                                    class="w-4 h-4 flex-shrink-0 mt-0.5"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"
                                    />
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"
                                    />
                                </svg>
                                <span>
                                    {{
                                        [
                                            center.address.address_line,
                                            center.address.district,
                                            center.address.city,
                                        ]
                                            .filter(Boolean)
                                            .join('، ')
                                    }}
                                </span>
                            </li>
                        </ul>
                    </article>
                </div>
            </div>
        </section>

        <!-- Why us -->
        <section class="py-14 sm:py-20">
            <div class="max-w-6xl mx-auto px-4 sm:px-6">
                <div class="text-center mb-10">
                    <h3 class="text-2xl sm:text-3xl font-bold">
                        {{ t('tenant_site.why_us.title') }}
                    </h3>
                    <p class="mt-2 text-slate-600 dark:text-slate-400">
                        {{ t('tenant_site.why_us.subtitle') }}
                    </p>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div
                        v-for="(card, i) in whyUsCards"
                        :key="i"
                        class="p-6 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800"
                    >
                        <div
                            class="w-12 h-12 rounded-xl flex items-center justify-center mb-3"
                            :style="{
                                backgroundColor:
                                    'color-mix(in srgb, var(--tenant-primary) 12%, transparent)',
                                color: 'var(--tenant-primary)',
                            }"
                        >
                            <svg
                                v-if="i === 0"
                                class="w-6 h-6"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"
                                />
                            </svg>
                            <svg
                                v-else-if="i === 1"
                                class="w-6 h-6"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M13 10V3L4 14h7v7l9-11h-7z"
                                />
                            </svg>
                            <svg
                                v-else
                                class="w-6 h-6"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"
                                />
                            </svg>
                        </div>
                        <h4 class="font-bold text-base">{{ card.title }}</h4>
                        <p class="mt-1.5 text-sm text-slate-600 dark:text-slate-400">
                            {{ card.body }}
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- CTA + future customer portal placeholder -->
        <section id="customer-portal" class="py-14 sm:py-20">
            <div class="max-w-4xl mx-auto px-4 sm:px-6">
                <div
                    class="rounded-2xl p-8 sm:p-10 text-center text-white shadow-2xl"
                    :style="{ background: heroGradient }"
                >
                    <h3 class="text-2xl sm:text-3xl font-bold">
                        {{ t('tenant_site.cta.title') }}
                    </h3>
                    <p class="mt-3 text-white/90 max-w-2xl mx-auto">
                        {{ t('tenant_site.cta.body') }}
                    </p>

                    <div class="mt-6 flex flex-wrap items-center justify-center gap-3">
                        <a
                            v-if="site.phone_link"
                            :href="site.phone_link"
                            class="inline-flex items-center gap-2 px-5 py-3 rounded-xl bg-white font-semibold text-slate-900 hover:bg-slate-100"
                        >
                            {{ t('tenant_site.cta.call') }}
                        </a>
                        <a
                            v-if="site.whatsapp_link"
                            :href="site.whatsapp_link"
                            target="_blank"
                            rel="noopener"
                            class="inline-flex items-center gap-2 px-5 py-3 rounded-xl bg-white/10 border border-white/30 font-semibold text-white hover:bg-white/20"
                        >
                            {{ t('tenant_site.cta.whatsapp') }}
                        </a>
                    </div>

                    <div
                        v-if="futurePortalEnabled"
                        class="mt-8 inline-flex items-center gap-2 px-4 py-2 rounded-full bg-white/15 border border-white/30 text-sm"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"
                            />
                        </svg>
                        {{ t('tenant_site.cta.portal_coming_soon') }}
                    </div>
                </div>
            </div>
        </section>

        <!-- Footer -->
        <footer class="border-t border-slate-200 dark:border-slate-800 py-8">
            <div
                class="max-w-6xl mx-auto px-4 sm:px-6 flex flex-col sm:flex-row items-center justify-between gap-4 text-sm text-slate-500 dark:text-slate-400"
            >
                <div class="flex items-center gap-2">
                    <span>© {{ year }}</span>
                    <span class="font-semibold text-slate-700 dark:text-slate-300">
                        {{ site.site_name }}
                    </span>
                </div>
                <div v-if="socialLinks.length" class="flex items-center gap-3">
                    <a
                        v-for="link in socialLinks"
                        :key="link.url"
                        :href="link.url"
                        target="_blank"
                        rel="noopener"
                        class="hover:opacity-80 transition"
                        :aria-label="link.platform"
                    >
                        <component :is="link.icon" class="w-5 h-5" />
                    </a>
                </div>
            </div>
        </footer>
    </div>
</template>

<script setup>
import { Head, usePage } from '@inertiajs/vue3';
import { computed, h, ref, onMounted } from 'vue';
import { useI18n } from 'vue-i18n';

const props = defineProps({
    site: { type: Object, required: true },
    services: { type: Array, default: () => [] },
    centers: { type: Array, default: () => [] },
});

const { t: tRaw, locale } = useI18n();
const page = usePage();
const isRtl = computed(() => (page.props.locale || locale.value) === 'ar');
const t = tRaw;

// Localized helper that handles missing keys gracefully.
const tr = (key) => {
    const v = tRaw(key);
    return v === key ? '' : v;
};

// Theme toggle labels are inline (not in i18n) to keep the
// public site self-contained even if a tenant's translation
// pack is incomplete. They follow the page locale.
const darkModeLabel = computed(() => (isRtl.value ? 'الوضع الداكن' : 'Dark mode'));
const lightModeLabel = computed(() => (isRtl.value ? 'الوضع الفاتح' : 'Light mode'));

const isDark = ref(true);

const year = new Date().getFullYear();

const initial = computed(() => {
    const name = props.site.site_name || '';
    return name.trim().charAt(0).toUpperCase() || '?';
});

const seoTitle = computed(() => {
    return props.site.seo_title || props.site.site_name || 'Tenant Site';
});

const seoDescription = computed(() => {
    return props.site.seo_description || props.site.tagline || '';
});

const futurePortalEnabled = computed(() => true); // Phase 6 spec: always show

const cssVars = computed(() => ({
    '--tenant-primary': props.site.primary_color || '#4f46e5',
    '--tenant-secondary': props.site.secondary_color || '#0ea5e9',
}));

const heroGradient = computed(() => {
    return `linear-gradient(135deg, ${props.site.primary_color || '#4f46e5'} 0%, ${props.site.secondary_color || '#0ea5e9'} 100%)`;
});

const whyUsCards = computed(() => [
    {
        title:
            tr('tenant_site.why_us.cards.quality.title') ||
            (isRtl.value ? 'جودة معتمدة' : 'Certified quality'),
        body:
            tr('tenant_site.why_us.cards.quality.body') ||
            (isRtl.value
                ? 'نلتزم بأعلى معايير الجودة في صيانة وإصلاح مركبتك باستخدام قطع غيار أصلية وأجهزة فحص حديثة.'
                : 'We uphold the highest quality standards in servicing your vehicle, using original parts and modern diagnostics.'),
    },
    {
        title:
            tr('tenant_site.why_us.cards.speed.title') ||
            (isRtl.value ? 'سرعة الإنجاز' : 'Fast turnaround'),
        body:
            tr('tenant_site.why_us.cards.speed.body') ||
            (isRtl.value
                ? 'فريق متخصص يضمن إنجاز خدمتك في الوقت المحدد دون تأخير، مع متابعة دقيقة لكل خطوة.'
                : 'A specialized team ensures your service is completed on time with step-by-step tracking.'),
    },
    {
        title:
            tr('tenant_site.why_us.cards.warranty.title') ||
            (isRtl.value ? 'ضمان موثوق' : 'Trusted warranty'),
        body:
            tr('tenant_site.why_us.cards.warranty.body') ||
            (isRtl.value
                ? 'نقدم ضمانًا على الخدمات والقطع المستبدلة لراحة بالك وثقة كاملة في كل صيانة.'
                : 'We back our services and replaced parts with a warranty for your complete peace of mind.'),
    },
]);

const socialPlatforms = {
    facebook: {
        name: 'Facebook',
        icon: () =>
            h('svg', { class: 'w-5 h-5', fill: 'currentColor', viewBox: '0 0 24 24' }, [
                h('path', {
                    d: 'M22 12a10 10 0 10-11.6 9.87v-6.99H7.9V12h2.5V9.8c0-2.47 1.47-3.84 3.72-3.84 1.08 0 2.21.19 2.21.19v2.42h-1.24c-1.22 0-1.6.76-1.6 1.54V12h2.72l-.43 2.88h-2.29v6.99A10 10 0 0022 12z',
                }),
            ]),
    },
    twitter: {
        name: 'X',
        icon: () =>
            h('svg', { class: 'w-5 h-5', fill: 'currentColor', viewBox: '0 0 24 24' }, [
                h('path', {
                    d: 'M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z',
                }),
            ]),
    },
    instagram: {
        name: 'Instagram',
        icon: () =>
            h('svg', { class: 'w-5 h-5', fill: 'currentColor', viewBox: '0 0 24 24' }, [
                h('path', {
                    d: 'M12 2.16c3.2 0 3.58 0 4.85.07 1.17.05 1.8.25 2.23.41.56.22.96.48 1.38.9.42.42.68.82.9 1.38.16.43.36 1.06.41 2.23.06 1.27.07 1.65.07 4.85s0 3.58-.07 4.85c-.05 1.17-.25 1.8-.41 2.23a3.7 3.7 0 01-.9 1.38 3.7 3.7 0 01-1.38.9c-.43.16-1.06.36-2.23.41-1.27.06-1.65.07-4.85.07s-3.58 0-4.85-.07c-1.17-.05-1.8-.25-2.23-.41a3.7 3.7 0 01-1.38-.9 3.7 3.7 0 01-.9-1.38c-.16-.43-.36-1.06-.41-2.23C2.16 15.58 2.16 15.2 2.16 12s0-3.58.07-4.85c.05-1.17.25-1.8.41-2.23.22-.56.48-.96.9-1.38.42-.42.82-.68 1.38-.9.43-.16 1.06-.36 2.23-.41C8.42 2.17 8.8 2.16 12 2.16zm0 5.4a4.44 4.44 0 100 8.88 4.44 4.44 0 000-8.88zm0 7.32a2.88 2.88 0 110-5.76 2.88 2.88 0 010 5.76zm4.62-7.5a1.04 1.04 0 11-2.08 0 1.04 1.04 0 012.08 0z',
                }),
            ]),
    },
    linkedin: {
        name: 'LinkedIn',
        icon: () =>
            h('svg', { class: 'w-5 h-5', fill: 'currentColor', viewBox: '0 0 24 24' }, [
                h('path', {
                    d: 'M4.98 3.5C4.98 4.88 3.87 6 2.5 6S0 4.88 0 3.5 1.13 1 2.5 1s2.48 1.12 2.48 2.5zM0 8h5v16H0V8zm7.5 0H12v2.2h.07c.66-1.2 2.27-2.46 4.67-2.46 5 0 5.93 3.3 5.93 7.58V24h-5v-7.4c0-1.77-.03-4.05-2.47-4.05-2.47 0-2.85 1.93-2.85 3.92V24h-5V8z',
                }),
            ]),
    },
    tiktok: {
        name: 'TikTok',
        icon: () =>
            h('svg', { class: 'w-5 h-5', fill: 'currentColor', viewBox: '0 0 24 24' }, [
                h('path', {
                    d: 'M19.59 6.69a4.83 4.83 0 01-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 01-5.2 1.74 2.89 2.89 0 012.31-4.64 2.93 2.93 0 01.88.13V9.4a6.84 6.84 0 00-1-.05A6.33 6.33 0 005.8 20.1a6.34 6.34 0 0010.86-4.43V8.92a8.16 8.16 0 004.77 1.52V7a4.85 4.85 0 01-1.84-.3z',
                }),
            ]),
    },
    youtube: {
        name: 'YouTube',
        icon: () =>
            h('svg', { class: 'w-5 h-5', fill: 'currentColor', viewBox: '0 0 24 24' }, [
                h('path', {
                    d: 'M23.5 6.2a3.02 3.02 0 00-2.12-2.14C19.5 3.5 12 3.5 12 3.5s-7.5 0-9.38.56A3.02 3.02 0 00.5 6.2 31.6 31.6 0 000 12a31.6 31.6 0 00.5 5.8 3.02 3.02 0 002.12 2.14C4.5 20.5 12 20.5 12 20.5s7.5 0 9.38-.56a3.02 3.02 0 002.12-2.14A31.6 31.6 0 0024 12a31.6 31.6 0 00-.5-5.8zM9.6 15.6V8.4l6.2 3.6-6.2 3.6z',
                }),
            ]),
    },
};

const socialLinks = computed(() => {
    const raw = props.site.social_links || {};
    const out = [];
    for (const [platform, def] of Object.entries(socialPlatforms)) {
        const url = raw[platform];
        if (url) {
            out.push({ platform: def.name, url, icon: def.icon });
        }
    }
    return out;
});

function toggleTheme() {
    isDark.value = !isDark.value;
    if (typeof document !== 'undefined') {
        document.documentElement.classList.toggle('dark', isDark.value);
        try {
            localStorage.setItem('tenant-site-dark', isDark.value ? '1' : '0');
        } catch (e) {
            // ignore
        }
    }
}

onMounted(() => {
    if (typeof window !== 'undefined') {
        const stored = localStorage.getItem('tenant-site-dark');
        if (stored !== null) {
            isDark.value = stored === '1';
        } else if (window.matchMedia) {
            isDark.value = window.matchMedia('(prefers-color-scheme: dark)').matches;
        }
        document.documentElement.classList.toggle('dark', isDark.value);
    }
});
</script>

<style scoped>
.tenant-site {
    font-family:
        'Cairo',
        'Tajawal',
        system-ui,
        -apple-system,
        sans-serif;
}

:global([dir='rtl']) .tenant-site {
    font-family: 'Cairo', 'Tajawal', system-ui, sans-serif;
}
</style>
