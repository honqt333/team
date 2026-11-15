<script setup>
import { computed } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import AuthLayout from '@/Layouts/AuthLayout.vue';
import AppInput from '@/Components/App/AppInput.vue';

const { t, locale } = useI18n();
const isRTL = computed(() => locale.value === 'ar');

const props = defineProps({
    user: Object,
    signature_params: Object,
});

const form = useForm({
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(
        route('invitations.accept', {
            user: props.user.id,
            ...props.signature_params,
        }),
        {
            onFinish: () => form.reset('password', 'password_confirmation'),
        }
    );
};
</script>

<template>
    <AuthLayout>
        <Head :title="isRTL ? 'تعيين كلمة المرور' : 'Set Password'" />

        <!-- Title -->
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white text-center mb-2">
            {{ $t('auth.set_password') }}
        </h2>

        <p class="text-sm text-gray-600 dark:text-gray-400 text-center mb-6">
            {{ $t('auth.set_password_hint', { name: user.name }) }}
        </p>

        <form @submit.prevent="submit" class="space-y-5">
            <!-- Email (Disabled) -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    {{ $t('center_settings.contact.email') }}
                </label>
                <AppInput
                    type="email"
                    :model-value="user.email"
                    disabled
                    input-class="px-4 py-3 bg-gray-100 dark:bg-gray-800/60 text-gray-500 dark:text-gray-400 cursor-not-allowed"
                />
            </div>

            <!-- Password -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    {{ $t('auth.password') }}
                </label>
                <AppInput
                    id="password"
                    type="password"
                    v-model="form.password"
                    required
                    autofocus
                    autocomplete="new-password"
                    :placeholder="isRTL ? 'أدخل كلمة المرور الجديدة' : 'Enter new password'"
                    :error="form.errors.password"
                    input-class="px-4 py-3"
                />
            </div>

            <!-- Password Confirmation -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    {{ $t('auth.confirm_password') }}
                </label>
                <AppInput
                    id="password_confirmation"
                    type="password"
                    v-model="form.password_confirmation"
                    required
                    autocomplete="new-password"
                    :placeholder="isRTL ? 'أعد إدخال كلمة المرور' : 'Confirm new password'"
                    :error="form.errors.password_confirmation"
                    input-class="px-4 py-3"
                />
            </div>

            <!-- Submit Button -->
            <button
                type="submit"
                :disabled="form.processing"
                class="w-full py-3 px-4 bg-action-primary hover:bg-action-primary-hover text-white font-semibold rounded-xl shadow-lg shadow-focus-ring-soft transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed mt-2"
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
                <span v-else>{{ $t('auth.set_password_action') }}</span>
            </button>
        </form>
    </AuthLayout>
</template>
