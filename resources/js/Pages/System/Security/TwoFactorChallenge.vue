<template>
    <div class="min-h-screen flex items-center justify-center bg-gray-100 dark:bg-gray-900 p-4">
        <div class="w-full max-w-md">
            <div class="bg-white dark:bg-gray-800 rounded-2xl p-8 shadow-xl">
                <div class="text-center mb-6">
                    <div class="w-16 h-16 rounded-full bg-indigo-100 dark:bg-indigo-900/30 flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                        </svg>
                    </div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">المصادقة الثنائية</h1>
                    <p class="text-gray-500 dark:text-gray-400 mt-1">أدخل رمز التحقق من تطبيق المصادقة</p>
                </div>
                
                <form @submit.prevent="verify" class="space-y-6">
                    <!-- TOTP Code -->
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
                
                <div class="mt-6 text-center">
                    <button 
                        @click="toggleRecovery" 
                        class="text-sm text-indigo-600 hover:text-indigo-800 dark:text-indigo-400"
                    >
                        {{ showRecovery ? 'استخدام رمز التحقق' : 'استخدام رمز الاسترداد' }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';

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
