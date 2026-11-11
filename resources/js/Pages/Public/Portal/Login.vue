<template>
    <Head>
        <title>{{ t('customer_portal.login.title') }} — {{ tenant.site_name }}</title>
    </Head>

    <AuthShell
        :tenant="tenant"
        :locale="locale"
        :title="t('customer_portal.login.title')"
        :subtitle="t('customer_portal.login.subtitle')"
        :eyebrow="t('customer_portal.login.portal_eyebrow')"
        :hero-title="t('customer_portal.login.hero_title')"
        :hero-text="t('customer_portal.login.reassure')"
        :benefits="benefits"
        :back-label="t('customer_portal.login.back_to_site')"
        :footer-text="t('customer_portal.login.secure_note')"
        :language-base-path="`/${tenant.slug}/portal/login`"
    >
        <template #icon>
            <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M12 11c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4Zm7 10a7 7 0 0 0-14 0"
                />
            </svg>
        </template>

        <form @submit.prevent="submit" class="space-y-5">
            <div>
                <label for="phone" class="mb-2 block text-sm font-bold text-slate-800">
                    {{ t('customer_portal.login.phone_label') }}
                </label>
                <div
                    class="flex h-14 overflow-hidden rounded-2xl border bg-white transition focus-within:border-transparent focus-within:ring-4"
                    :class="form.errors.phone ? 'border-rose-400' : 'border-slate-200'"
                    :style="{ '--tw-ring-color': 'var(--portal-primary-soft)' }"
                >
                    <select
                        v-model="selectedCountryCode"
                        aria-label="Country code"
                        class="border-0 border-e border-slate-200 bg-slate-50 px-3 text-sm font-extrabold text-slate-700 outline-none focus:ring-0"
                        dir="ltr"
                    >
                        <option
                            v-for="country in countryDialOptions"
                            :key="country.code"
                            :value="country.code"
                        >
                            {{ country.flag }} {{ country.code }} {{ country.dial_code }}
                        </option>
                    </select>
                    <input
                        id="phone"
                        v-model="form.phone"
                        type="tel"
                        inputmode="numeric"
                        autocomplete="tel"
                        dir="ltr"
                        :placeholder="
                            activeCountryMeta.local_placeholder ||
                            t('customer_portal.login.local_phone_placeholder')
                        "
                        class="min-w-0 flex-1 border-0 bg-white px-4 text-lg font-semibold tracking-wide text-slate-950 outline-none ring-0 placeholder:text-slate-300 focus:ring-0"
                        @input="onPhoneInput"
                    />
                </div>
                <p v-if="form.errors.phone" class="mt-2 text-sm font-medium text-rose-600">
                    {{ form.errors.phone }}
                </p>
                <p v-else class="mt-2 text-xs leading-6 text-slate-500">
                    {{ t('customer_portal.login.phone_hint') }}
                </p>
            </div>

            <button
                type="submit"
                :disabled="form.processing || !form.phone"
                class="flex h-14 w-full items-center justify-center gap-2 rounded-2xl px-5 text-sm font-extrabold text-white shadow-lg transition hover:-translate-y-0.5 hover:shadow-xl disabled:translate-y-0 disabled:cursor-not-allowed disabled:opacity-50"
                :style="{
                    backgroundColor: 'var(--portal-primary)',
                    boxShadow: '0 16px 35px -18px var(--portal-primary)',
                }"
            >
                <svg
                    v-if="form.processing"
                    class="h-4 w-4 animate-spin"
                    viewBox="0 0 24 24"
                    fill="none"
                >
                    <circle
                        cx="12"
                        cy="12"
                        r="9"
                        stroke="currentColor"
                        stroke-width="3"
                        stroke-dasharray="42 18"
                    />
                </svg>
                <span>{{ t('customer_portal.login.submit') }}</span>
                <svg
                    v-if="!form.processing"
                    class="h-4 w-4 rtl:rotate-180"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2.5"
                        d="m9 18 6-6-6-6"
                    />
                </svg>
            </button>
        </form>

        <p
            v-if="status"
            class="mt-4 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-center text-sm font-medium text-emerald-800"
            data-testid="portal-login-status"
        >
            {{ status }}
        </p>
    </AuthShell>
</template>

<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import AuthShell from '@/Components/CustomerPortal/AuthShell.vue';
import { COUNTRIES, getCountryMeta } from '@/Utils/currency';

const props = defineProps({
    tenant: { type: Object, required: true },
    locale: { type: Object, default: () => ({ current: 'ar', is_rtl: true }) },
    phone: { type: String, default: '' },
    status: { type: String, default: '' },
});

const { t } = useI18n();

const countryDialOptions = Object.values(COUNTRIES);
const selectedCountryCode = ref(
    props.tenant?.country && COUNTRIES[String(props.tenant.country).toUpperCase()]
        ? String(props.tenant.country).toUpperCase()
        : 'SA'
);
const activeCountryMeta = computed(() => getCountryMeta(selectedCountryCode.value));

function localPhoneValue(phone) {
    const digits = String(phone || '').replace(/\D/g, '');
    const dialDigits = activeCountryMeta.value.dial_digits || '966';
    if (digits.startsWith(dialDigits)) return digits.slice(dialDigits.length);
    return digits;
}

const form = useForm({ phone: localPhoneValue(props.phone) });

const benefits = computed(() => [
    t('customer_portal.login.benefit_appointments'),
    t('customer_portal.login.benefit_tracking'),
    t('customer_portal.login.benefit_security'),
]);

function onPhoneInput(event) {
    const cleaned = event.target.value.replace(/\D/g, '').slice(0, 12);
    form.phone = cleaned;
}

function normalizedPhone() {
    const raw = String(form.phone || '').trim();
    if (!raw) return '';
    if (raw.startsWith('+')) return raw;
    if (raw.startsWith('00')) return `+${raw.slice(2)}`;
    const dialCode = activeCountryMeta.value.dial_code || '+966';
    const stripped = raw.startsWith('0') ? raw.slice(1) : raw;
    return `${dialCode}${stripped}`;
}

function submit() {
    form.transform((data) => ({ ...data, phone: normalizedPhone() })).post(
        route('public.portal.login.otp', { tenant: props.tenant.slug })
    );
}
</script>
