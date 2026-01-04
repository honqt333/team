<script setup>
import AuthLayout from '@/Layouts/AuthLayout.vue';
import InputError from '@/Components/InputError.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { ref, computed } from 'vue';

const { locale } = useI18n();
const isRTL = computed(() => locale.value === 'ar');

const props = defineProps({
    email: { type: String, required: true },
    token: { type: String, required: true },
});

const form = useForm({
    token: props.token,
    email: props.email,
    password: '',
    password_confirmation: '',
});

const showPassword = ref(false);
const showConfirmPassword = ref(false);

const submit = () => {
    form.post(route('password.store'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <AuthLayout>
        <Head :title="isRTL ? 'إعادة تعيين كلمة المرور' : 'Reset Password'" />
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white text-center mb-6">
            {{ isRTL ? 'إعادة تعيين كلمة المرور' : 'Reset Password' }}
        </h2>

        <form @submit.prevent="submit" class="space-y-5">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    {{ isRTL ? 'البريد الإلكتروني' : 'Email Address' }}
                </label>
                <input type="email" v-model="form.email" readonly class="w-full px-4 py-3 border rounded-xl bg-gray-50 dark:bg-gray-700 cursor-not-allowed" />
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    {{ isRTL ? 'كلمة المرور الجديدة' : 'New Password' }}
                </label>
                <div class="relative">
                    <input :type="showPassword ? 'text' : 'password'" v-model="form.password" required class="w-full px-4 py-3 pe-12 border rounded-xl focus:ring-2 focus:ring-violet-500 bg-white dark:bg-gray-800" />
                    <button type="button" @click="showPassword = !showPassword" class="absolute inset-y-0 end-0 pe-4 text-gray-400">👁</button>
                </div>
                <InputError class="mt-2" :message="form.errors.password" />
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    {{ isRTL ? 'تأكيد كلمة المرور' : 'Confirm Password' }}
                </label>
                <div class="relative">
                    <input :type="showConfirmPassword ? 'text' : 'password'" v-model="form.password_confirmation" required class="w-full px-4 py-3 pe-12 border rounded-xl focus:ring-2 focus:ring-violet-500 bg-white dark:bg-gray-800" />
                    <button type="button" @click="showConfirmPassword = !showConfirmPassword" class="absolute inset-y-0 end-0 pe-4 text-gray-400">👁</button>
                </div>
                <InputError class="mt-2" :message="form.errors.password_confirmation" />
            </div>

            <button type="submit" :disabled="form.processing" class="w-full py-3 bg-gradient-to-r from-violet-600 to-indigo-600 text-white font-semibold rounded-xl shadow-lg disabled:opacity-50">
                {{ form.processing ? (isRTL ? 'جاري الحفظ...' : 'Saving...') : (isRTL ? 'إعادة تعيين' : 'Reset Password') }}
            </button>
        </form>
    </AuthLayout>
</template>
