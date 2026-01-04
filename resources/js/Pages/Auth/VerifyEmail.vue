<script setup>
import AuthLayout from '@/Layouts/AuthLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { computed } from 'vue';

const { locale } = useI18n();
const isRTL = computed(() => locale.value === 'ar');

const props = defineProps({ status: { type: String } });
const form = useForm({});
const submit = () => form.post(route('verification.send'));
const verificationLinkSent = computed(() => props.status === 'verification-link-sent');
</script>

<template>
    <AuthLayout>
        <Head :title="isRTL ? 'تفعيل البريد' : 'Email Verification'" />
        
        <div class="flex justify-center mb-6">
            <div class="w-20 h-20 rounded-full bg-violet-100 dark:bg-violet-900/30 flex items-center justify-center">
                <svg class="w-10 h-10 text-violet-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                </svg>
            </div>
        </div>

        <h2 class="text-2xl font-bold text-gray-900 dark:text-white text-center mb-4">
            {{ isRTL ? 'تفعيل البريد الإلكتروني' : 'Verify Your Email' }}
        </h2>

        <p class="text-sm text-gray-600 dark:text-gray-400 text-center mb-6">
            {{ isRTL ? 'شكراً لتسجيلك! يرجى تأكيد بريدك الإلكتروني بالنقر على الرابط المرسل.' : 'Thanks for signing up! Please verify your email by clicking the link we sent.' }}
        </p>

        <div v-if="verificationLinkSent" class="mb-6 p-4 rounded-xl bg-green-50 dark:bg-green-900/30 text-sm text-green-700 text-center">
            ✓ {{ isRTL ? 'تم إرسال رابط تفعيل جديد!' : 'A new verification link has been sent!' }}
        </div>

        <form @submit.prevent="submit" class="space-y-4">
            <button type="submit" :disabled="form.processing" class="w-full py-3 bg-gradient-to-r from-violet-600 to-indigo-600 text-white font-semibold rounded-xl shadow-lg disabled:opacity-50">
                {{ form.processing ? (isRTL ? 'جاري الإرسال...' : 'Sending...') : (isRTL ? 'إعادة إرسال' : 'Resend Email') }}
            </button>
            <div class="text-center">
                <Link :href="route('logout')" method="post" as="button" class="text-sm text-gray-600 hover:text-gray-500">
                    {{ isRTL ? 'تسجيل الخروج' : 'Log Out' }}
                </Link>
            </div>
        </form>
    </AuthLayout>
</template>
