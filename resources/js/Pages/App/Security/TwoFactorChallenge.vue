<script setup>
import { computed, ref } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import AuthLayout from '@/Layouts/AuthLayout.vue';

const { t, locale } = useI18n();
const isRTL = computed(() => locale.value === 'ar');

const showRecovery = ref(false);

const form = useForm({
    code: '',
    recovery_code: '',
});

const resendForm = useForm({});

const verify = () => form.post(route('app.2fa.verify'));

const resend = () => {
    resendForm.post(route('app.2fa.challenge.resend'), {
        preserveScroll: true,
    });
};

const toggleRecovery = () => {
    showRecovery.value = !showRecovery.value;
    form.code = '';
    form.recovery_code = '';
};
</script>

<template>
    <AuthLayout>
        <Head :title="isRTL ? 'التحقق بخطوتين' : 'Two-Factor Challenge'" />

        <!-- Flash Message -->
        <div
            v-if="$page.props.flash?.success"
            class="mb-4 p-3 bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800 rounded-xl text-emerald-700 dark:text-emerald-400 text-sm text-center font-medium"
        >
            {{ $page.props.flash.success }}
        </div>

        <div class="text-center mb-6">
            <div
                class="w-16 h-16 rounded-full bg-indigo-100 dark:bg-indigo-900/30 flex items-center justify-center mx-auto mb-4"
            >
                <svg
                    class="w-8 h-8 text-indigo-600 dark:text-indigo-400"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"
                    />
                </svg>
            </div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white font-cairo">
                {{ isRTL ? 'التحقق بخطوتين' : 'Two-Factor Authentication' }}
            </h1>
            <p class="text-gray-500 dark:text-gray-400 mt-1 text-sm">
                {{
                    showRecovery
                        ? $t('security.enter_recovery_code')
                        : $t('security.enter_code_sent_to_address')
                }}
            </p>
        </div>

        <form @submit.prevent="verify" class="space-y-6">
            <!-- Email OTP Code -->
            <div v-if="!showRecovery">
                <label
                    class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2 text-center"
                >
                    {{ $t('customer_portal.verify.code_label') }}
                </label>
                <input
                    type="text"
                    v-model="form.code"
                    maxlength="6"
                    placeholder="000000"
                    autofocus
                    class="w-full text-center text-3xl tracking-[0.5em] rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white font-mono py-3.5 focus:ring-2 focus:ring-violet-500 focus:border-violet-500"
                    dir="ltr"
                    inputmode="numeric"
                />
                <p v-if="$page.props.errors?.code" class="text-red-500 text-sm mt-2 text-center">
                    {{ $page.props.errors.code }}
                </p>
            </div>

            <!-- Recovery Code -->
            <div v-else>
                <label
                    class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2 text-center"
                >
                    {{ $t('security.recovery_code') }}
                </label>
                <input
                    type="text"
                    v-model="form.recovery_code"
                    placeholder="XXXX-XXXX"
                    class="w-full text-center text-xl rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white font-mono py-3 uppercase focus:ring-2 focus:ring-violet-500 focus:border-violet-500"
                    dir="ltr"
                />
                <p
                    v-if="$page.props.errors?.recovery_code"
                    class="text-red-500 text-sm mt-2 text-center"
                >
                    {{ $page.props.errors.recovery_code }}
                </p>
            </div>

            <button
                type="submit"
                :disabled="form.processing || (!showRecovery && form.code.length !== 6)"
                class="w-full py-3 px-4 bg-gradient-to-r from-violet-600 to-indigo-600 hover:from-violet-700 hover:to-indigo-700 text-white font-semibold rounded-xl shadow-lg shadow-violet-500/30 transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed"
            >
                <span v-if="form.processing" class="flex items-center justify-center gap-2">
                    <svg class="animate-spin h-5 w-5" fill="none" viewBox="0 0 24 24">
                        <circle
                            class="opacity-25"
                            cx="12"
                            cy="12"
                            r="10"
                            stroke="currentColor"
                            stroke-width="4"
                        ></circle>
                        <path
                            class="opacity-75"
                            fill="currentColor"
                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                        ></path>
                    </svg>
                    <span>{{ $t('security.verifying') }}</span>
                </span>
                <span v-else>{{ $t('common.confirm') }}</span>
            </button>
        </form>

        <div class="mt-6 flex flex-col gap-3 text-center">
            <button
                v-if="!showRecovery"
                type="button"
                @click="resend"
                :disabled="resendForm.processing"
                class="text-sm text-gray-600 hover:text-violet-600 dark:text-gray-400 dark:hover:text-violet-400 transition-colors"
            >
                {{ resendForm.processing ? $t('common.loading') : $t('security.resend') }}
            </button>

            <button
                type="button"
                @click="toggleRecovery"
                class="text-sm text-violet-600 hover:text-violet-700 dark:text-violet-400 font-medium transition-colors"
            >
                {{
                    showRecovery
                        ? $t('security.use_email_code')
                        : $t('security.enter_recovery_code')
                }}
            </button>

            <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                <form method="POST" action="/logout">
                    <input type="hidden" name="_token" :value="$page.props.csrf_token" />
                    <button
                        type="submit"
                        class="text-sm text-gray-500 hover:text-rose-600 dark:text-gray-400 dark:hover:text-rose-400 transition-colors block w-full"
                    >
                        {{ $t('nav.logout') }}
                    </button>
                </form>
            </div>
        </div>
    </AuthLayout>
</template>
