<template>
    <Head>
        <title>{{ t('customer_portal.verify.title') }} — {{ tenant.site_name }}</title>
    </Head>

    <AuthShell
        :tenant="tenant"
        :locale="locale"
        :title="t('customer_portal.verify.title')"
        :subtitle="t('customer_portal.verify.sent_prefix') + ' ' + maskedPhone"
        :eyebrow="t('customer_portal.login.portal_eyebrow')"
        :hero-title="t('customer_portal.login.hero_title')"
        :hero-text="t('customer_portal.login.reassure')"
        :benefits="benefits"
        :back-label="t('customer_portal.login.back_to_site')"
        :footer-text="t('customer_portal.verify.expiry_notice')"
        :language-base-path="`/${tenant.slug}/portal/verify`"
    >
        <template #icon>
            <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M12 15v2m-6 4h12a2 2 0 0 0 2-2v-8a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2Zm10-12V7a4 4 0 0 0-8 0v2"
                />
            </svg>
        </template>

        <p
            v-if="status"
            class="mb-5 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-center text-sm font-medium text-emerald-800"
            data-testid="portal-verify-status"
        >
            {{ status }}
        </p>

        <form @submit.prevent="submit" class="space-y-5">
            <div>
                <label class="mb-3 block text-center text-sm font-bold text-slate-800">
                    {{ t('customer_portal.verify.code_label') }}
                </label>
                <div class="flex justify-center gap-2 sm:gap-3" dir="ltr" @paste="onPaste">
                    <input
                        v-for="(_, index) in otpDigits"
                        :key="index"
                        :ref="(el) => (boxRefs[index] = el)"
                        :value="otpDigits[index]"
                        type="text"
                        inputmode="numeric"
                        :autocomplete="index === 0 ? 'one-time-code' : 'off'"
                        maxlength="1"
                        class="h-14 w-11 rounded-2xl border bg-white text-center text-2xl font-black text-slate-950 outline-none transition focus:border-transparent focus:ring-4 sm:h-16 sm:w-12"
                        :class="form.errors.code ? 'border-rose-400' : 'border-slate-200'"
                        :style="{ '--tw-ring-color': 'var(--portal-primary-soft)' }"
                        :aria-label="`${t('customer_portal.verify.code_label')} ${index + 1}`"
                        @input="onDigitInput(index, $event)"
                        @keydown="onDigitKeydown(index, $event)"
                    />
                </div>
                <p
                    v-if="form.errors.code"
                    class="mt-2 text-center text-sm font-medium text-rose-600"
                >
                    {{ form.errors.code }}
                </p>
            </div>

            <button
                type="submit"
                :disabled="form.processing || code.length !== 6"
                class="flex h-14 w-full items-center justify-center gap-2 rounded-2xl px-5 text-sm font-extrabold text-white shadow-lg transition hover:-translate-y-0.5 disabled:translate-y-0 disabled:cursor-not-allowed disabled:opacity-50"
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
                {{ t('customer_portal.verify.submit') }}
            </button>
        </form>

        <div class="mt-6 flex flex-col items-center gap-3 text-sm">
            <button
                v-if="resendCooldown > 0"
                type="button"
                disabled
                class="font-semibold text-slate-400"
            >
                {{ t('customer_portal.verify.resend_in', { seconds: resendCooldown }) }}
            </button>
            <button
                v-else
                type="button"
                :disabled="resending"
                class="font-bold hover:underline disabled:opacity-50"
                :style="{ color: 'var(--portal-primary)' }"
                @click="resend"
            >
                {{ t('customer_portal.verify.resend_now') }}
            </button>
            <a
                :href="`/${tenant.slug}/portal/login`"
                class="font-medium text-slate-500 hover:text-slate-800"
            >
                {{ t('customer_portal.verify.change_link') }}
            </a>
        </div>
    </AuthShell>
</template>

<script setup>
import { Head, router, useForm } from '@inertiajs/vue3';
import { computed, nextTick, onMounted, onUnmounted, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import AuthShell from '@/Components/CustomerPortal/AuthShell.vue';

const props = defineProps({
    tenant: { type: Object, required: true },
    locale: { type: Object, default: () => ({ current: 'ar', is_rtl: true }) },
    phone: { type: String, default: '' },
    status: { type: String, default: '' },
    initialResendSeconds: { type: Number, default: 30 },
});

const { t } = useI18n();
const form = useForm({ phone: props.phone || '', code: '' });
const otpDigits = ref(Array(6).fill(''));
const boxRefs = ref([]);
const code = computed(() => otpDigits.value.join(''));
const benefits = computed(() => [
    t('customer_portal.login.benefit_appointments'),
    t('customer_portal.login.benefit_tracking'),
    t('customer_portal.login.benefit_security'),
]);
const maskedPhone = computed(() => {
    const digits = String(props.phone || '').replace(/\D/g, '');
    return digits ? `•••• ${digits.slice(-4)}` : '';
});

function fillDigits(value, start = 0) {
    const incoming = String(value)
        .replace(/\D/g, '')
        .slice(0, 6 - start)
        .split('');
    incoming.forEach((digit, offset) => {
        otpDigits.value[start + offset] = digit;
    });
    nextTick(() => boxRefs.value[Math.min(start + incoming.length, 5)]?.focus());
}

function onDigitInput(index, event) {
    const incoming = event.target.value.replace(/\D/g, '');
    if (incoming.length > 1) {
        fillDigits(incoming, index);
        return;
    }
    otpDigits.value[index] = incoming.slice(-1);
    if (otpDigits.value[index] && index < 5) boxRefs.value[index + 1]?.focus();
}

function onDigitKeydown(index, event) {
    if (event.key !== 'Backspace') return;
    event.preventDefault();
    if (otpDigits.value[index]) {
        otpDigits.value[index] = '';
        return;
    }
    if (index > 0) {
        otpDigits.value[index - 1] = '';
        boxRefs.value[index - 1]?.focus();
    }
}

function onPaste(event) {
    event.preventDefault();
    fillDigits(event.clipboardData?.getData('text') || '', 0);
}

const resendCooldown = ref(Math.max(0, props.initialResendSeconds || 30));
const resending = ref(false);
let timerId = null;

function startCooldown(seconds) {
    resendCooldown.value = seconds;
    if (timerId) clearInterval(timerId);
    timerId = setInterval(() => {
        resendCooldown.value = Math.max(0, resendCooldown.value - 1);
        if (resendCooldown.value === 0) {
            clearInterval(timerId);
            timerId = null;
        }
    }, 1000);
}

function resend() {
    if (resendCooldown.value || resending.value) return;
    resending.value = true;
    router.post(
        route('public.portal.login.otp', { tenant: props.tenant.slug }),
        { phone: props.phone },
        {
            preserveScroll: true,
            onSuccess: (page) => startCooldown(page?.props?.initialResendSeconds ?? 30),
            onError: () => startCooldown(60),
            onFinish: () => (resending.value = false),
        }
    );
}

function submit() {
    form.code = code.value;
    form.post(route('public.portal.verify.otp', { tenant: props.tenant.slug }));
}

onMounted(() => {
    boxRefs.value[0]?.focus();
    if (resendCooldown.value) startCooldown(resendCooldown.value);
});
onUnmounted(() => timerId && clearInterval(timerId));
</script>
