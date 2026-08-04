<template>
    <Head>
        <title>{{ t('customer_portal.verify.title') }} — {{ tenant.site_name }}</title>
    </Head>

    <div
        :dir="isRtl ? 'rtl' : 'ltr'"
        :data-theme="isDark ? 'dark' : 'light'"
        :style="cssVars"
        class="min-h-screen flex items-center justify-center px-4 py-10 bg-slate-50 text-slate-900 dark:bg-slate-950 dark:text-slate-100"
    >
        <div class="w-full max-w-md">
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
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                    {{ t('customer_portal.verify.subtitle') }}
                </p>
                <p v-if="phone" class="mt-2 text-xs text-slate-400 dark:text-slate-500" dir="ltr">
                    {{ t('customer_portal.verify.sent_to') }}
                    <span class="font-mono">{{ maskedPhone }}</span>
                </p>
            </div>

            <div
                class="bg-white dark:bg-slate-900 rounded-2xl p-6 sm:p-8 shadow-sm border border-slate-200 dark:border-slate-800"
            >
                <!-- Generic "we sent you a code" flash
                     from the login → verify handoff. Same
                     banner shape as Login.vue so the
                     customer gets a consistent cue. The
                     status prop is consumed server-side
                     on this GET, so a back-button
                     navigation does not resurrect a stale
                     "we sent you a code" message after
                     the user has already verified. -->
                <p
                    v-if="statusBanner"
                    class="mb-4 text-sm text-emerald-600 dark:text-emerald-400 text-center"
                    data-testid="portal-verify-status"
                >
                    {{ statusBanner }}
                </p>

                <form @submit.prevent="submit" class="space-y-5">
                    <div>
                        <label class="block text-sm font-medium mb-1.5">
                            {{ t('customer_portal.verify.code_label') }}
                        </label>
                        <input
                            v-model="form.code"
                            type="text"
                            inputmode="numeric"
                            autocomplete="one-time-code"
                            maxlength="6"
                            pattern="\d{6}"
                            :placeholder="t('customer_portal.verify.code_placeholder')"
                            dir="ltr"
                            class="w-full rounded-lg border-slate-300 dark:border-slate-700 dark:bg-slate-950 text-center text-2xl tracking-[0.5em] font-mono"
                            :class="{ 'border-rose-500': errors.code }"
                            @input="onCodeInput"
                        />
                        <p v-if="errors.code" class="text-rose-500 text-sm mt-1 text-center">
                            {{ errors.code }}
                        </p>
                    </div>

                    <button
                        type="submit"
                        :disabled="form.processing || form.code.length !== 6"
                        class="w-full inline-flex items-center justify-center gap-2 px-5 py-3 rounded-lg text-white font-semibold disabled:opacity-60 transition"
                        :style="{ backgroundColor: 'var(--tenant-primary, #4f46e5)' }"
                    >
                        <span v-if="form.processing">…</span>
                        {{ t('customer_portal.verify.submit') }}
                    </button>
                </form>

                <p class="mt-5 text-xs text-slate-500 dark:text-slate-400 text-center">
                    {{ t('customer_portal.verify.expiry_notice') }}
                </p>
            </div>

            <div class="mt-6 text-center">
                <a
                    :href="`/${tenant.slug}/portal/login`"
                    class="text-sm text-slate-500 dark:text-slate-400 hover:underline"
                >
                    {{ t('customer_portal.verify.change_number') }}
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
    // Server-supplied flash status. The login → verify
    // handoff sets `session('status')` to the generic
    // "if your number is registered, we sent you a
    // code" message; the controller reads it back out
    // of the session and exposes it as a top-level
    // prop. Inertia does not auto-merge Laravel's
    // session flash into props, so the page reads
    // `status`, not `page.props.flash.status`. The
    // server-side flash is consumed on this GET, so a
    // back-button navigation does not re-show a stale
    // banner after the user has already verified.
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

// The banner value is the prop directly; we re-read
// on every render so the banner appears / disappears
// correctly across the handoff.
const statusBanner = computed(() => props.status || '');

const form = useForm({
    phone: props.phone || '',
    code: '',
});

const errors = form.errors;

const cssVars = computed(() => ({
    '--tenant-primary': props.tenant?.primary_color || '#4f46e5',
    '--tenant-secondary': props.tenant?.secondary_color || '#0ea5e9',
}));

const languageOptions = computed(() => {
    const base = `/${props.tenant.slug}/portal/verify`;
    return [
        { value: 'ar', label: 'العربية', href: `${base}?lang=ar` },
        { value: 'en', label: 'English', href: `${base}?lang=en` },
    ];
});

// Show only the last 4 digits of the phone to the user.
// We never show the full phone anywhere in the portal —
// even the user themselves only sees the masked form.
const maskedPhone = computed(() => {
    if (!props.phone) {
        return '';
    }
    const digits = props.phone.replace(/\D/g, '');
    if (digits.length <= 4) {
        return props.phone;
    }
    return '•••• ••'.replace('••', digits.slice(-4));
});

function onCodeInput(e) {
    // Force digits only, max 6.
    const cleaned = e.target.value.replace(/\D/g, '').slice(0, 6);
    if (cleaned !== e.target.value) {
        form.code = cleaned;
    }
}

function submit() {
    form.post(route('public.portal.verify.otp', { tenant: props.tenant.slug }));
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
