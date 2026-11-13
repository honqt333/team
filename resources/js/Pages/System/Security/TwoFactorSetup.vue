<template>
    <SystemLayout>
        <div class="max-w-4xl mx-auto space-y-6">
            <PageHeader
                :title="$t('system.tenants.show.two_factor')"
                :subtitle="$t('security.add_extra_layer')"
            >
                <template #icon>
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"
                        />
                    </svg>
                </template>
            </PageHeader>

            <!-- Not Enabled State -->
            <div
                v-if="!isEnabled"
                class="bg-white dark:bg-gray-800 rounded-xl p-6 border border-gray-200 dark:border-gray-700"
            >
                <div class="flex items-center gap-4 mb-6">
                    <div
                        class="w-12 h-12 rounded-full bg-amber-100 dark:bg-amber-900/30 flex items-center justify-center"
                    >
                        <svg
                            class="w-6 h-6 text-amber-600"
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
                    <div>
                        <h3 class="font-semibold text-gray-900 dark:text-white">
                            {{ $t('security.two_factor_disabled') }}
                        </h3>
                        <p class="text-sm text-gray-500">{{ $t('security.enable_to_protect') }}</p>
                    </div>
                </div>

                <div class="space-y-6">
                    <div>
                        <h4 class="font-medium text-gray-900 dark:text-white mb-3">
                            {{ $t('security.step_1_scan_qr') }}
                        </h4>
                        <p class="text-sm text-gray-500 mb-3">
                            {{ $t('security.use_authenticator_app') }}
                        </p>
                        <div class="flex items-center gap-6">
                            <div v-safe-html="qrCode" class="bg-white p-2 rounded-lg border"></div>
                            <div>
                                <p class="text-xs text-gray-500 mb-1">
                                    {{ $t('security.or_enter_manually') }}
                                </p>
                                <code
                                    class="block text-sm bg-gray-100 dark:bg-gray-700 px-3 py-2 rounded font-mono select-all"
                                >
                                    {{ secret }}
                                </code>
                            </div>
                        </div>
                    </div>

                    <div>
                        <h4 class="font-medium text-gray-900 dark:text-white mb-3">
                            {{ $t('security.step_2_enter_code') }}
                        </h4>
                        <form @submit.prevent="enable" class="flex gap-3 items-start">
                            <div class="w-40">
                                <AppInput
                                    v-model="form.code"
                                    type="text"
                                    maxlength="6"
                                    placeholder="000000"
                                    class="text-center text-2xl tracking-wider font-mono"
                                    dir="ltr"
                                    inputmode="numeric"
                                />
                            </div>
                            <button
                                type="submit"
                                :disabled="form.processing || form.code.length !== 6"
                                class="px-6 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg font-medium disabled:opacity-50"
                            >
                                {{ $t('common.activate') }}
                            </button>
                        </form>
                        <p v-if="$page.props.errors?.code" class="text-red-500 text-sm mt-1">
                            {{ $page.props.errors.code }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Enabled State -->
            <TwoFactorManageSection
                v-else
                :recovery-codes="recoveryCodes || []"
                disable-url="/system/security/2fa/disable"
                regenerate-url="/system/security/2fa/regenerate"
            />
        </div>
    </SystemLayout>
</template>

<script setup>
import { useForm } from '@inertiajs/vue3';
import SystemLayout from '@/Layouts/SystemLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import AppInput from '@/Components/App/AppInput.vue';
import TwoFactorManageSection from '@/Components/System/TwoFactorManageSection.vue';

const props = defineProps({
    secret: String,
    qrCode: String,
    isEnabled: Boolean,
    recoveryCodes: Array,
});

const form = useForm({ code: '' });

const enable = () => form.post('/system/security/2fa/enable');
</script>
