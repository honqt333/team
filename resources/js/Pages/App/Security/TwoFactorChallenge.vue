<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';

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
    <div class="min-h-screen flex items-center justify-center bg-gray-100 dark:bg-gray-900 p-4">
        <div class="w-full max-w-md">
            <div class="bg-white dark:bg-gray-800 rounded-2xl p-8 shadow-xl">
                <!-- Flash Message -->
                <div v-if="$page.props.flash?.success" class="mb-4 p-4 bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800 rounded-lg text-emerald-700 dark:text-emerald-400 text-sm text-center">
                    {{ $page.props.flash.success }}
                </div>

                <div class="text-center mb-6">
                    <div class="w-16 h-16 rounded-full bg-indigo-100 dark:bg-indigo-900/30 flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                    </div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">المصادقة الثنائية</h1>
                    <p class="text-gray-500 dark:text-gray-400 mt-1">
                        {{ showRecovery ? 'أدخل رمز الاسترداد للدخول' : 'أدخل الرمز المرسل لبريدك الإلكتروني' }}
                    </p>
                </div>
                
                <form @submit.prevent="verify" class="space-y-6">
                    <!-- Email OTP Code -->
                    <div v-if="!showRecovery">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">رمز التحقق</label>
                        <input 
                            type="text" 
                            v-model="form.code"
                            maxlength="6"
                            placeholder="000000"
                            autofocus
                            class="w-full text-center text-3xl tracking-[0.5em] rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white font-mono py-4"
                            dir="ltr"
                            inputmode="numeric"
                        />
                        <p v-if="$page.props.errors?.code" class="text-red-500 text-sm mt-2 text-center">{{ $page.props.errors.code }}</p>
                    </div>
                    
                    <!-- Recovery Code -->
                    <div v-else>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">رمز الاسترداد</label>
                        <input 
                            type="text" 
                            v-model="form.recovery_code"
                            placeholder="XXXX-XXXX"
                            class="w-full text-center text-xl rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white font-mono py-3 uppercase"
                            dir="ltr"
                        />
                        <p v-if="$page.props.errors?.recovery_code" class="text-red-500 text-sm mt-2 text-center">{{ $page.props.errors.recovery_code }}</p>
                    </div>
                    
                    <button 
                        type="submit" 
                        :disabled="form.processing || (!showRecovery && form.code.length !== 6)"
                        class="w-full py-3 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg font-medium disabled:opacity-50 transition-colors"
                    >
                        {{ form.processing ? 'جاري التحقق...' : 'تأكيد' }}
                    </button>
                </form>
                
                <div class="mt-6 flex flex-col gap-3 text-center">
                    <button 
                        v-if="!showRecovery"
                        @click="resend" 
                        :disabled="resendForm.processing"
                        class="text-sm text-gray-600 hover:text-indigo-600 dark:text-gray-400 dark:hover:text-indigo-400"
                    >
                        {{ resendForm.processing ? 'جاري الإرسال...' : 'إعادة إرسال الرمز' }}
                    </button>

                    <button 
                        @click="toggleRecovery" 
                        class="text-sm text-indigo-600 hover:text-indigo-800 dark:text-indigo-400"
                    >
                        {{ showRecovery ? 'استخدام رمز البريد الإلكتروني' : 'استخدام رمز الاسترداد' }}
                    </button>
                    
                    <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                        <form method="POST" action="/logout">
                            <input type="hidden" name="_token" :value="$page.props.csrf_token">
                            <button type="submit" class="text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 block w-full">
                                تسجيل الخروج
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
