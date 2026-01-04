<script setup>
import AuthLayout from '@/Layouts/AuthLayout.vue';
import InputError from '@/Components/InputError.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { computed } from 'vue';

const { locale } = useI18n();
const isRTL = computed(() => locale.value === 'ar');

defineProps({
    status: {
        type: String,
    },
});

const form = useForm({
    email: '',
});

const submit = () => {
    form.post(route('password.email'));
};
</script>

<template>
    <AuthLayout>
        <Head :title="isRTL ? 'نسيت كلمة المرور' : 'Forgot Password'" />

        <!-- Title -->
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white text-center mb-4 font-cairo">
            {{ isRTL ? 'نسيت كلمة المرور' : 'Forgot Password' }}
        </h2>

        <p class="text-sm text-gray-600 dark:text-gray-400 text-center mb-6">
            {{ isRTL 
                ? 'أدخل بريدك الإلكتروني المسجل وسنرسل لك رابط لإعادة تعيين كلمة المرور'
                : 'Enter your registered email and we will send you a password reset link'
            }}
        </p>

        <div v-if="status" class="mb-4 p-3 rounded-xl bg-green-50 dark:bg-green-900/30 text-sm font-medium text-green-600 dark:text-green-400 text-center">
            {{ status }}
        </div>

        <form @submit.prevent="submit" class="space-y-6">
            <!-- Email -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    {{ isRTL ? 'البريد الإلكتروني' : 'Email Address' }}
                </label>
                <input
                    type="email"
                    v-model="form.email"
                    required
                    autofocus
                    autocomplete="username"
                    :placeholder="isRTL ? 'البريد الإلكتروني' : 'Email'"
                    class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-violet-500 focus:border-violet-500 bg-white dark:bg-gray-800 text-gray-900 dark:text-white placeholder-gray-400"
                />
                <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <!-- Submit Button -->
            <button
                type="submit"
                :disabled="form.processing"
                class="w-full py-3 px-4 bg-gradient-to-r from-violet-600 to-indigo-600 hover:from-violet-700 hover:to-indigo-700 text-white font-semibold rounded-xl shadow-lg shadow-violet-500/30 transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed"
            >
                <span v-if="form.processing" class="flex items-center justify-center gap-2">
                    <svg class="animate-spin h-5 w-5" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    {{ isRTL ? 'جاري الإرسال...' : 'Sending...' }}
                </span>
                <span v-else>{{ isRTL ? 'إرسال' : 'Send Reset Link' }}</span>
            </button>

            <!-- Back to Login -->
            <div class="text-center">
                <Link
                    :href="route('login')"
                    class="text-sm font-medium text-violet-600 hover:text-violet-500 dark:text-violet-400"
                >
                    {{ isRTL ? 'العودة لتسجيل الدخول' : 'Back to Login' }}
                </Link>
            </div>
        </form>
    </AuthLayout>
</template>

<style scoped>
.font-cairo {
    font-family: 'Cairo', system-ui, sans-serif;
}
</style>
