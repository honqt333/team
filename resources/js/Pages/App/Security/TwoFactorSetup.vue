<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    isEnabled: Boolean,
    isEnforced: Boolean,
    recoveryCodes: Array,
    smsEnabled: Boolean,
    currentMethod: String,
});

const showDisableModal = ref(false);
const showRegenModal = ref(false);
const codeSent = ref(false);
const selectedMethod = ref('email');

const sendCodeForm = useForm({
    method: 'email'
});
const form = useForm({ code: '' });
const disableForm = useForm({ password: '' });
const regenForm = useForm({ password: '' });

const sendCode = () => {
    sendCodeForm.method = selectedMethod.value;
    sendCodeForm.post(route('app.2fa.send-code'), {
        onSuccess: () => codeSent.value = true
    });
};

const enable = () => form.post(route('app.security.2fa.enable'), {
    preserveScroll: true,
    onError: () => form.reset()
});

const disable = () => disableForm.post(route('app.security.2fa.disable'), { 
    onSuccess: () => showDisableModal.value = false 
});

const regenerate = () => regenForm.post(route('app.security.2fa.regenerate'), { 
    onSuccess: () => showRegenModal.value = false 
});
</script>

<template>
    <AppLayout>
        <div class="max-w-2xl mx-auto space-y-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">المصادقة الثنائية</h1>
                    <p class="text-gray-500 dark:text-gray-400">أضف طبقة حماية إضافية لحسابك عن طريق البريد الإلكتروني</p>
                </div>
                <div v-if="isEnforced" class="px-3 py-1 bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400 rounded-full text-sm font-medium">
                    إلزامي من قبل مدير النظام
                </div>
            </div>
            
            <!-- Not Enabled State -->
            <div v-if="!isEnabled" class="bg-white dark:bg-gray-800 rounded-xl p-6 border border-gray-200 dark:border-gray-700 shadow-sm">
                <div v-if="$page.props.flash?.warning" class="mb-6 p-4 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg text-amber-700 dark:text-amber-400">
                    {{ $page.props.flash.warning }}
                </div>
                
                <div v-if="$page.props.flash?.success" class="mb-6 p-4 bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800 rounded-lg text-emerald-700 dark:text-emerald-400">
                    {{ $page.props.flash.success }}
                </div>

                <div class="flex items-center gap-4 mb-6">
                    <div class="w-12 h-12 rounded-full bg-amber-100 dark:bg-amber-900/30 flex items-center justify-center">
                        <svg class="w-6 h-6 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="font-semibold text-gray-900 dark:text-white">المصادقة الثنائية غير مفعّلة</h3>
                        <p class="text-sm text-gray-500">قم بتفعيلها لحماية حسابك باستخدام رمز يرسل لبريدك الإلكتروني</p>
                    </div>
                </div>

                <div class="space-y-6">
                    <!-- Step 1: Send Code -->
                    <div v-if="!codeSent">
                        <p class="text-gray-600 dark:text-gray-300 mb-4">
                            سنقوم بإرسال رمز تحقق صالح لمدة 10 دقائق لتفعيل المصادقة الثنائية.
                        </p>

                        <!-- Method Selection -->
                        <div class="mb-6 space-y-3">
                            <label class="flex items-center p-3 border border-gray-200 dark:border-gray-700 rounded-lg cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                                <input type="radio" v-model="selectedMethod" value="email" class="w-4 h-4 text-indigo-600 border-gray-300 focus:ring-indigo-500">
                                <span class="ms-3 flex-1">
                                    <span class="block text-sm font-medium text-gray-900 dark:text-white">البريد الإلكتروني</span>
                                    <span class="block text-sm text-gray-500">إرسال الرمز إلى {{ $page.props.auth.user.email }}</span>
                                </span>
                            </label>

                            <label v-if="smsEnabled" class="flex items-center p-3 border border-gray-200 dark:border-gray-700 rounded-lg cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                                <input type="radio" v-model="selectedMethod" value="sms" class="w-4 h-4 text-indigo-600 border-gray-300 focus:ring-indigo-500">
                                <span class="ms-3 flex-1">
                                    <span class="block text-sm font-medium text-gray-900 dark:text-white">الرسائل النصية (SMS)</span>
                                    <span class="block text-sm text-gray-500">إرسال الرمز إلى {{ $page.props.auth.user.phone || 'رقم الهاتف المسجل' }}</span>
                                </span>
                            </label>
                        </div>

                        <button 
                            @click="sendCode" 
                            :disabled="sendCodeForm.processing"
                            class="flex items-center gap-2 px-6 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg font-medium disabled:opacity-50"
                        >
                            <svg v-if="!sendCodeForm.processing" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                            <svg v-else class="animate-spin w-5 h-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            إرسال رمز التحقق
                        </button>
                        
                        <!-- Error Messages -->
                        <div v-if="$page.props.errors?.method" class="mt-3 p-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg text-red-600 dark:text-red-400 text-sm">
                            {{ $page.props.errors.method }}
                        </div>
                    </div>

                    <!-- Step 2: Verify Code -->
                    <div v-else>
                        <h4 class="font-medium text-gray-900 dark:text-white mb-3">
                            أدخل رمز التحقق المرسل لـ {{ selectedMethod === 'sms' ? 'هاتفك' : 'بريدك' }}
                        </h4>
                        <form @submit.prevent="enable" class="space-y-4">
                            <div class="flex items-start gap-3">
                                <div class="relative">
                                    <input 
                                        type="text" 
                                        v-model="form.code"
                                        maxlength="6"
                                        placeholder="000000"
                                        class="w-40 text-center text-2xl tracking-wider rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white font-mono focus:ring-emerald-500 focus:border-emerald-500"
                                        dir="ltr"
                                        inputmode="numeric"
                                        autofocus
                                    />
                                    <p v-if="form.errors.code" class="text-red-500 text-sm mt-1 absolute right-0 w-60">{{ form.errors.code }}</p>
                                </div>
                                <button type="submit" :disabled="form.processing || form.code.length !== 6" class="px-6 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg font-medium disabled:opacity-50 h-[46px]">
                                    تفعيل
                                </button>
                            </div>
                            
                            <div class="text-sm">
                                <span class="text-gray-500">لم يصلك الرمز؟ </span>
                                <button 
                                    type="button" 
                                    @click="sendCode"
                                    :disabled="sendCodeForm.processing"
                                    class="text-indigo-600 hover:text-indigo-800 font-medium"
                                >
                                    إعادة الإرسال
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- Enabled State -->
            <div v-else class="space-y-6">
                <div class="bg-white dark:bg-gray-800 rounded-xl p-6 border border-gray-200 dark:border-gray-700 shadow-sm">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 rounded-full bg-emerald-100 dark:bg-emerald-900/30 flex items-center justify-center">
                            <svg class="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                            </svg>
                        </div>
                        <div class="flex-1">
                            <h3 class="font-semibold text-gray-900 dark:text-white">المصادقة الثنائية مفعّلة ✓</h3>
                            <p class="text-sm text-gray-500">
                                حسابك محمي - سيطلب منك إدخال رمز يرسل لـ {{ currentMethod === 'sms' ? 'هاتفك' : 'بريدك' }} عند تسجيل الدخول
                            </p>
                        </div>
                    </div>
                </div>
                
                <!-- Recovery Codes -->
                <div class="bg-white dark:bg-gray-800 rounded-xl p-6 border border-gray-200 dark:border-gray-700 shadow-sm">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="font-semibold text-gray-900 dark:text-white">رموز الاسترداد</h3>
                        <button @click="showRegenModal = true" class="text-sm text-indigo-600 hover:text-indigo-800">إنشاء رموز جديدة</button>
                    </div>
                    <p class="text-sm text-gray-500 mb-4">احتفظ بهذه الرموز في مكان آمن. يمكن استخدام كل رمز مرة واحدة فقط في حال فقدان الوصول لبريدك.</p>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-2">
                        <code v-for="code in recoveryCodes" :key="code" class="text-sm bg-gray-100 dark:bg-gray-700 px-3 py-2 rounded text-center font-mono">{{ code }}</code>
                    </div>
                </div>
                
                <!-- Disable 2FA -->
                <div class="bg-white dark:bg-gray-800 rounded-xl p-6 border border-red-200 dark:border-red-900/30 shadow-sm">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="font-semibold text-gray-900 dark:text-white">إلغاء المصادقة الثنائية</h3>
                            <p class="text-sm text-gray-500">
                                {{ isEnforced ? 'المصادقة إلزامية ولا يمكن تعطيلها' : 'هذا سيقلل من أمان حسابك' }}
                            </p>
                        </div>
                        <button 
                            @click="!isEnforced && (showDisableModal = true)" 
                            :disabled="isEnforced"
                            :class="isEnforced ? 'opacity-50 cursor-not-allowed' : 'hover:bg-red-200'"
                            class="px-4 py-2 bg-red-100 text-red-700 rounded-lg text-sm font-medium transition-colors"
                        >
                            إلغاء التفعيل
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Disable Modal -->
            <Transition name="modal">
                <div v-if="showDisableModal" class="fixed inset-0 z-50 overflow-y-auto">
                    <div class="flex min-h-full items-center justify-center p-4">
                        <div class="fixed inset-0 bg-black/50" @click="showDisableModal = false"></div>
                        <div class="relative bg-white dark:bg-gray-800 rounded-2xl p-6 w-full max-w-md shadow-xl">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">تأكيد إلغاء المصادقة الثنائية</h3>
                            <form @submit.prevent="disable">
                                <div class="mb-4">
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">أدخل كلمة المرور للتأكيد</label>
                                    <input type="password" v-model="disableForm.password" required class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white" />
                                </div>
                                <div class="flex gap-3">
                                    <button type="submit" :disabled="disableForm.processing" class="flex-1 px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg font-medium disabled:opacity-50">
                                        إلغاء التفعيل
                                    </button>
                                    <button type="button" @click="showDisableModal = false" class="px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg">
                                        تراجع
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </Transition>
            
            <!-- Regenerate Modal -->
            <Transition name="modal">
                <div v-if="showRegenModal" class="fixed inset-0 z-50 overflow-y-auto">
                    <div class="flex min-h-full items-center justify-center p-4">
                        <div class="fixed inset-0 bg-black/50" @click="showRegenModal = false"></div>
                        <div class="relative bg-white dark:bg-gray-800 rounded-2xl p-6 w-full max-w-md shadow-xl">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">إنشاء رموز استرداد جديدة</h3>
                            <p class="text-sm text-gray-500 mb-4">سيتم إلغاء الرموز القديمة واستبدالها برموز جديدة.</p>
                            <form @submit.prevent="regenerate">
                                <div class="mb-4">
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">أدخل كلمة المرور</label>
                                    <input type="password" v-model="regenForm.password" required class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white" />
                                </div>
                                <div class="flex gap-3">
                                    <button type="submit" :disabled="regenForm.processing" class="flex-1 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg font-medium disabled:opacity-50">
                                        إنشاء رموز جديدة
                                    </button>
                                    <button type="button" @click="showRegenModal = false" class="px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg">
                                        إلغاء
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </Transition>
        </div>
    </AppLayout>
</template>

<style scoped>
.modal-enter-active, .modal-leave-active { transition: opacity 0.2s ease; }
.modal-enter-from, .modal-leave-to { opacity: 0; }
</style>
