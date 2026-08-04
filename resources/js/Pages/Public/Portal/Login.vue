<template>
    <Head>
        <title>{{ t('customer_portal.login.title') }} — {{ tenant.site_name }}</title>
    </Head>

    <div
        :dir="isRtl ? 'rtl' : 'ltr'"
        :data-theme="isDark ? 'dark' : 'light'"
        :style="cssVars"
        class="min-h-screen flex items-center justify-center px-4 py-10 bg-slate-50 text-slate-900 dark:bg-slate-950 dark:text-slate-100"
    >
        <div class="w-full max-w-md">
            <!-- Language toggle. Top-right, never blocks
                 the form. The form action is a regular
                 POST so the cookie sticks across visits. -->
            <div class="flex justify-end mb-3">
                <div
                    class="inline-flex rounded-full border border-slate-200 dark:border-slate-700 bg-white/70 dark:bg-slate-900/70 backdrop-blur p-0.5 text-xs font-semibold"
                >
                    <a
                        v-for="opt in languageOptions"
                        :key="opt.value"
                        :href="opt.href"
                        class="px-3 py-1 rounded-full transition"
                        :class="
                            locale.current === opt.value
                                ? 'text-white'
                                : 'text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white'
                        "
                        :style="
                            locale.current === opt.value
                                ? { backgroundColor: 'var(--tenant-primary, #4f46e5)' }
                                : {}
                        "
                    >
                        {{ opt.label }}
                    </a>
                </div>
            </div>

            <div class="text-center mb-8">
                <!-- Real brand mark when the tenant has a
                     logo; otherwise a small monogram tile
                     that still uses the tenant's color. The
                     customer can tell at a glance whose
                     portal this is. -->
                <div
                    class="mx-auto mb-4 flex items-center justify-center overflow-hidden"
                    :class="
                        tenant.has_logo
                            ? 'h-16 w-16 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700'
                            : 'h-14 w-14 rounded-2xl'
                    "
                    :style="
                        tenant.has_logo
                            ? {}
                            : {
                                  backgroundColor:
                                      'color-mix(in srgb, var(--tenant-primary, #4f46e5) 12%, transparent)',
                                  color: 'var(--tenant-primary, #4f46e5)',
                              }
                    "
                >
                    <img
                        v-if="tenant.has_logo"
                        :src="tenant.logo_url"
                        :alt="tenant.site_name"
                        class="h-12 w-12 object-contain"
                    />
                    <svg
                        v-else
                        class="w-7 h-7"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"
                        />
                    </svg>
                </div>
                <h1 class="text-2xl font-bold">{{ tenant.site_name }}</h1>
                <p v-if="tenant.tagline" class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                    {{ tenant.tagline }}
                </p>
                <p v-else class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                    {{ t('customer_portal.login.subtitle') }}
                </p>
            </div>

            <div
                class="bg-white dark:bg-slate-900 rounded-2xl p-6 sm:p-8 shadow-sm border border-slate-200 dark:border-slate-800"
            >
                <form @submit.prevent="submit" class="space-y-5">
                    <div>
                        <label class="block text-sm font-medium mb-1.5">
                            {{ t('customer_portal.login.phone_label') }}
                        </label>
                        <input
                            v-model="form.phone"
                            type="tel"
                            inputmode="tel"
                            autocomplete="tel"
                            :placeholder="t('customer_portal.login.phone_placeholder')"
                            dir="ltr"
                            class="w-full rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-950 text-base"
                            :class="{ 'border-rose-500': errors.phone }"
                            @input="onPhoneInput"
                        />
                        <!-- Hint: tells the customer what to
                             expect. Calm, not alarming. -->
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1.5">
                            {{ t('customer_portal.login.phone_hint') }}
                        </p>
                        <p v-if="errors.phone" class="text-rose-500 text-sm mt-1">
                            {{ errors.phone }}
                        </p>
                    </div>

                    <!-- Primary action: tenant brand color,
                         not a fixed purple. Falls back to a
                         neutral indigo only if the tenant
                         has not picked a color yet. -->
                    <button
                        type="submit"
                        :disabled="form.processing || !form.phone"
                        class="w-full inline-flex items-center justify-center gap-2 px-5 py-3 rounded-lg text-white font-semibold disabled:opacity-60 transition"
                        :style="{ backgroundColor: 'var(--tenant-primary, #4f46e5)' }"
                    >
                        <span v-if="form.processing">…</span>
                        {{ t('customer_portal.login.submit') }}
                    </button>
                </form>

                <p
                    v-if="statusBanner"
                    class="mt-4 text-sm text-emerald-600 dark:text-emerald-400 text-center"
                    data-testid="portal-login-status"
                >
                    {{ statusBanner }}
                </p>
            </div>

            <div class="mt-6 text-center">
                <a
                    :href="`/${tenant.slug}`"
                    class="text-sm text-slate-500 dark:text-slate-400 hover:underline"
                >
                    {{ t('customer_portal.login.back_to_site') }}
                </a>
            </div>
        </div>
    </div>
</template>

<script setup>
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref, onMounted } from 'vue';
import { useI18n } from 'vue-i18n';

const props = defineProps({
    tenant: { type: Object, required: true },
    locale: { type: Object, default: () => ({ current: 'ar', is_rtl: true }) },
    phone: { type: String, default: '' },
    // Server-supplied flash status (e.g. "we sent you
    // a code" from a back-navigation, or a cooldowned
    // banner). The controller reads it out of
    // `session('status')` and exposes it as a top-level
    // prop because Inertia does not auto-merge
    // Laravel's session flash into props.
    status: { type: String, default: '' },
});

const { t: tRaw } = useI18n();
const page = usePage();
const isRtl = computed(() => props.locale?.is_rtl ?? (page.props.locale || 'ar') === 'ar');
const t = (k) => {
    const v = tRaw(k);
    return v === k ? k.split('.').pop() : v;
};

const isDark = ref(true);
// Re-read the prop on every render so the banner
// appears / disappears correctly. The session's
// flash is consumed server-side on this GET, so a
// back-button navigation will not re-show a stale
// message.
const statusBanner = computed(() => props.status || '');

const form = useForm({
    phone: props.phone || '',
});

const errors = form.errors;

// CSS variables. We inline the tenant's primary + secondary
// colors as CSS custom properties on the root container. The
// buttons throughout the page use `var(--tenant-primary, ...)`
// so a single source of truth is in scope.
const cssVars = computed(() => ({
    '--tenant-primary': props.tenant?.primary_color || '#4f46e5',
    '--tenant-secondary': props.tenant?.secondary_color || '#0ea5e9',
}));

// The toggle is a pair of <a> tags that GET the page with
// `?lang=` set. The server reads the query, sets the cookie,
// and redirects to the same URL with the new locale in
// effect. We use plain links (not buttons) so the form's
// CSRF token is irrelevant — a GET to the page is harmless.
const languageOptions = computed(() => {
    const base = `/${props.tenant.slug}/portal/login`;
    return [
        { value: 'ar', label: 'العربية', href: `${base}?lang=ar` },
        { value: 'en', label: 'English', href: `${base}?lang=en` },
    ];
});

function onPhoneInput(e) {
    const cleaned = e.target.value.replace(/[^\d+]/g, '');
    if (cleaned !== e.target.value) {
        form.phone = cleaned;
    }
}

function submit() {
    form.post(route('public.portal.login.otp', { tenant: props.tenant.slug }));
}

onMounted(() => {
    if (typeof window !== 'undefined') {
        const stored = localStorage.getItem('cp-dark');
        if (stored !== null) {
            isDark.value = stored === '1';
        } else if (window.matchMedia) {
            isDark.value = window.matchMedia('(prefers-color-scheme: dark)').matches;
        }
        document.documentElement.classList.toggle('dark', isDark.value);
    }
});
</script>
