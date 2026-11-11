<script setup>
import { computed, ref } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import SystemLayout from '@/Layouts/SystemLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';

const { t, locale } = useI18n();
const isRTL = computed(() => locale.value === 'ar');

const showRecovery = ref(false);

const form = useForm({
    code: '',
    recovery_code: '',
});

const verify = () => form.post('/2fa/verify');

const toggleRecovery = () => {
    showRecovery.value = !showRecovery.value;
    form.code = '';
    form.recovery_code = '';
};
</script>

<template>
    <SystemLayout>
        <Head :title="$t('system.security.system_2fa_challenge')" />

        <div class="max-w-md mx-auto space-y-6">
            <PageHeader
                :title="$t('system.security.two-factor_authentication')"
                :subtitle="$t('system.security.add_extra_layer')"
            >
                <template #icon>
                    <div
                        class="w-12 h-12 rounded-full bg-selection-bg flex items-center justify-center"
                    >
                        <svg
                            class="w-6 h-6 text-action-primary"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"
                            />
                        </svg>
                    </div>
                </template>
            </PageHeader>

            <div class="text-center mb-6">
                <div
                    class="w-16 h-16 rounded-full bg-selection-bg flex items-center justify-center mx-auto mb-4"
                >
                    <svg
                        class="w-8 h-8 text-action-primary"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"
                        />
                    </svg>
                </div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                    {{ $t('system.security.two-factor_authentication') }}
                </h1>
                <p class="text-gray-500 dark:text-gray-400 mt-1 text-sm">
                    {{
                        showRecovery
                            ? $t('security.enter_recovery_code')
                            : $t('security.enter_authenticator_code')
                    }}
                </p>
            </div>

            <form @submit.prevent="verify" class="space-y-6">
                <!-- TOTP Code -->
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
                        class="w-full text-center text-3xl tracking-[0.5em] rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white font-mono py-3.5 focus:ring-2 focus:ring-focus-ring focus:border-action-primary"
                        dir="ltr"
                        inputmode="numeric"
                    />
                    <p
                        v-if="$page.props.errors?.code"
                        class="text-red-500 text-sm mt-2 text-center"
                    >
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
                        class="w-full text-center text-xl rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white font-mono py-3 uppercase focus:ring-2 focus:ring-focus-ring focus:border-action-primary"
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
                    class="w-full py-3 px-4 bg-action-primary hover:bg-action-primary-hover text-white font-semibold rounded-xl shadow-lg shadow-focus-ring-soft transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed"
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

            <div class="mt-6 text-center">
                <button
                    type="button"
                    @click="toggleRecovery"
                    class="text-sm text-action-primary hover:text-selection-text font-medium transition-colors"
                >
                    {{
                        showRecovery
                            ? $t('security.use_verification_code')
                            : $t('security.enter_recovery_code')
                    }}
                </button>
            </div>
        </div>
    </SystemLayout>
</template>
