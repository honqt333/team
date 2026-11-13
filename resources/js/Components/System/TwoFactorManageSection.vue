<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import BaseModal from '@/Components/BaseModal.vue';
import AppInput from '@/Components/App/AppInput.vue';
import AppButton from '@/Components/App/AppButton.vue';
import { useToast } from '@/Composables/useToast';

const props = defineProps({
    recoveryCodes: {
        type: Array,
        default: () => [],
    },
    disableUrl: {
        type: String,
        required: true,
    },
    regenerateUrl: {
        type: String,
        required: true,
    },
    disableSuccessMessage: {
        type: String,
        default: '',
    },
    regenerateSuccessMessage: {
        type: String,
        default: '',
    },
});

const { success: toastSuccess } = useToast();

const showDisableModal = ref(false);
const showRegenModal = ref(false);

const disableForm = useForm({ password: '' });
const regenForm = useForm({ password: '' });

const closeDisableModal = () => {
    showDisableModal.value = false;
    disableForm.reset();
    disableForm.clearErrors();
};

const closeRegenModal = () => {
    showRegenModal.value = false;
    regenForm.reset();
    regenForm.clearErrors();
};

const submitDisable = () => {
    disableForm.post(props.disableUrl, {
        preserveScroll: true,
        onSuccess: () => {
            closeDisableModal();
            if (props.disableSuccessMessage) {
                toastSuccess(props.disableSuccessMessage);
            }
        },
    });
};

const submitRegenerate = () => {
    regenForm.post(props.regenerateUrl, {
        preserveScroll: true,
        onSuccess: () => {
            closeRegenModal();
            if (props.regenerateSuccessMessage) {
                toastSuccess(props.regenerateSuccessMessage);
            }
        },
    });
};
</script>

<template>
    <div class="space-y-6">
        <!-- Enabled Banner -->
        <div
            class="bg-white dark:bg-gray-800 rounded-2xl p-6 border border-gray-200 dark:border-gray-700 shadow-sm"
        >
            <div class="flex items-center gap-4">
                <div
                    class="w-12 h-12 rounded-full bg-emerald-100 dark:bg-emerald-900/30 flex items-center justify-center shrink-0"
                >
                    <svg
                        class="w-6 h-6 text-emerald-600"
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
                <div class="flex-1">
                    <h3 class="font-semibold text-gray-900 dark:text-white text-lg">
                        {{ $t('security.two_factor_enabled') }}
                    </h3>
                    <p class="text-sm text-gray-500">
                        {{ $t('security.account_protected') }}
                    </p>
                </div>
            </div>
        </div>

        <!-- Recovery Codes -->
        <div
            class="bg-white dark:bg-gray-800 rounded-2xl p-6 border border-gray-200 dark:border-gray-700 shadow-sm"
        >
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-semibold text-gray-900 dark:text-white">
                    {{ $t('security.recovery_codes') }}
                </h3>
                <AppButton variant="ghost" size="sm" @click="showRegenModal = true">
                    {{ $t('security.new_codes') }}
                </AppButton>
            </div>
            <p class="text-sm text-gray-500 mb-4">
                {{ $t('security.keep_codes_safe') }}
            </p>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-2">
                <code
                    v-for="code in recoveryCodes"
                    :key="code"
                    class="text-sm bg-gray-100 dark:bg-gray-700 px-3 py-2 rounded-lg text-center font-mono"
                >
                    {{ code }}
                </code>
            </div>
        </div>

        <!-- Disable 2FA -->
        <div
            class="bg-white dark:bg-gray-800 rounded-2xl p-6 border border-red-200 dark:border-red-900/30 shadow-sm"
        >
            <div class="flex items-center justify-between gap-4">
                <div>
                    <h3 class="font-semibold text-gray-900 dark:text-white">
                        {{ $t('security.disable_two_factor') }}
                    </h3>
                    <p class="text-sm text-gray-500">
                        {{ $t('security.this_reduces_security') }}
                    </p>
                </div>
                <AppButton variant="danger" size="sm" @click="showDisableModal = true">
                    {{ $t('security.deactivate') }}
                </AppButton>
            </div>
        </div>

        <!-- Disable Modal -->
        <BaseModal
            :show="showDisableModal"
            :title="$t('security.confirm_disable_two_factor')"
            size="sm"
            @close="closeDisableModal"
        >
            <form @submit.prevent="submitDisable" class="space-y-4">
                <AppInput
                    v-model="disableForm.password"
                    type="password"
                    :label="$t('security.enter_password_to_confirm')"
                    :error="disableForm.errors.password"
                    required
                    autofocus
                />
                <div class="flex gap-3">
                    <AppButton
                        type="submit"
                        variant="danger"
                        :loading="disableForm.processing"
                        class="flex-1"
                    >
                        {{ $t('security.deactivate') }}
                    </AppButton>
                    <AppButton variant="secondary" @click="closeDisableModal">
                        {{ $t('common.cancel') }}
                    </AppButton>
                </div>
            </form>
        </BaseModal>

        <!-- Regenerate Modal -->
        <BaseModal
            :show="showRegenModal"
            :title="$t('security.new_recovery_codes')"
            size="sm"
            @close="closeRegenModal"
        >
            <p class="text-sm text-gray-500 mb-4">
                {{ $t('security.old_codes_cancelled') }}
            </p>
            <form @submit.prevent="submitRegenerate" class="space-y-4">
                <AppInput
                    v-model="regenForm.password"
                    type="password"
                    :label="$t('security.enter_password')"
                    :error="regenForm.errors.password"
                    required
                    autofocus
                />
                <div class="flex gap-3">
                    <AppButton
                        type="submit"
                        variant="primary"
                        :loading="regenForm.processing"
                        class="flex-1"
                    >
                        {{ $t('security.new_codes') }}
                    </AppButton>
                    <AppButton variant="secondary" @click="closeRegenModal">
                        {{ $t('common.cancel') }}
                    </AppButton>
                </div>
            </form>
        </BaseModal>
    </div>
</template>
