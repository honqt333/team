<script setup>
import { ref, computed } from 'vue';
import { useForm, usePage, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { useToast } from '@/Composables/useToast';

const props = defineProps({
    mustVerifyEmail: Boolean,
    status: String,
    isEnabled: Boolean,
    isEnforced: Boolean,
    recoveryCodes: Array,
    smsEnabled: Boolean,
    currentMethod: String,
});

const { success, error } = useToast();
const page = usePage();
const user = computed(() => page.props.auth.user);

// Active Tab
const activeTab = ref('profile');

// Profile Form
const profileForm = useForm({
    name: user.value.name,
    email: user.value.email,
});

const updateProfile = () => {
    profileForm.patch(route('app.profile.update'), {
        preserveScroll: true,
        onSuccess: () => success('تم تحديث الملف الشخصي بنجاح'),
    });
};

// Password Form  
const passwordForm = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

const updatePassword = () => {
    passwordForm.put(route('password.update'), {
        preserveScroll: true,
        onSuccess: () => {
            passwordForm.reset();
            success('تم تغيير كلمة المرور بنجاح');
        },
    });
};

// 2FA
const showDisableModal = ref(false);
const showRegenModal = ref(false);
const codeSent = ref(false);
const selectedMethod = ref('email');

const sendCodeForm = useForm({ method: 'email' });
const codeForm = useForm({ code: '' });
const disableForm = useForm({ password: '' });
const regenForm = useForm({ password: '' });

const sendCode = () => {
    sendCodeForm.method = selectedMethod.value;
    sendCodeForm.post(route('app.2fa.send-code'), {
        onSuccess: () => codeSent.value = true
    });
};

const enable2FA = () => codeForm.post(route('app.security.2fa.enable'), {
    preserveScroll: true,
    onSuccess: () => {
        codeSent.value = false;
        codeForm.reset();
        success('تم تفعيل المصادقة الثنائية بنجاح');
    },
    onError: () => codeForm.reset()
});

const disable2FA = () => disableForm.post(route('app.security.2fa.disable'), { 
    onSuccess: () => {
        showDisableModal.value = false;
        success('تم إلغاء المصادقة الثنائية');
    }
});

const regenerateCodes = () => regenForm.post(route('app.security.2fa.regenerate'), { 
    onSuccess: () => {
        showRegenModal.value = false;
        success('تم إنشاء رموز استرداد جديدة');
    }
});
</script>

<template>
    <AppLayout>
        <div class="max-w-4xl mx-auto space-y-6">
            <!-- Header -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-sm border border-gray-200 dark:border-gray-700">
                <div class="flex items-center gap-4">
                    <div class="w-16 h-16 rounded-full bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center text-white text-2xl font-bold shadow-lg">
                        {{ user.name?.charAt(0).toUpperCase() }}
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ user.name }}</h1>
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ user.email }}</p>
                        <p v-if="user.phone" class="text-sm text-gray-500 dark:text-gray-400 mt-1" dir="ltr">{{ user.phone }}</p>
                    </div>
                </div>
            </div>

            <!-- Tabs -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl p-2 shadow-sm border border-gray-200 dark:border-gray-700">
                <div class="flex items-center gap-1 flex-wrap">
                    <button
                        @click="activeTab = 'profile'"
                        :class="[
                            'flex items-center gap-2 px-5 py-3 rounded-xl text-sm font-medium transition-all',
                            activeTab === 'profile'
                                ? 'bg-gradient-to-r from-indigo-500 to-purple-600 text-white shadow-lg shadow-indigo-500/30'
                                : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700'
                        ]"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                        الملف الشخصي
                    </button>
                    <button
                        @click="activeTab = 'security'"
                        :class="[
                            'flex items-center gap-2 px-5 py-3 rounded-xl text-sm font-medium transition-all',
                            activeTab === 'security'
                                ? 'bg-gradient-to-r from-indigo-500 to-purple-600 text-white shadow-lg shadow-indigo-500/30'
                                : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700'
                        ]"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                        </svg>
                        الأمان
                    </button>
                    <button
                        @click="activeTab = 'password'"
                        :class="[
                            'flex items-center gap-2 px-5 py-3 rounded-xl text-sm font-medium transition-all',
                            activeTab === 'password'
                                ? 'bg-gradient-to-r from-indigo-500 to-purple-600 text-white shadow-lg shadow-indigo-500/30'
                                : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700'
                        ]"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                        </svg>
                        كلمة المرور
                    </button>
                </div>
            </div>

            <!-- Profile Tab -->
            <div v-if="activeTab === 'profile'" class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-sm border border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-6">معلومات الملف الشخصي</h3>
                
                <form @submit.prevent="updateProfile" class="space-y-6 max-w-xl">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">الاسم</label>
                        <input 
                            type="text" 
                            v-model="profileForm.name"
                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-indigo-500 focus:border-indigo-500"
                            required
                        />
                        <p v-if="profileForm.errors.name" class="mt-1 text-sm text-red-500">{{ profileForm.errors.name }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">البريد الإلكتروني</label>
                        <input 
                            type="email" 
                            v-model="profileForm.email"
                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-indigo-500 focus:border-indigo-500"
                            required
                        />
                        <p v-if="profileForm.errors.email" class="mt-1 text-sm text-red-500">{{ profileForm.errors.email }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">رقم الهاتف</label>
                        <input 
                            type="tel" 
                            :value="user.phone"
                            disabled
                            dir="ltr"
                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 bg-gray-100 dark:bg-gray-600 text-gray-500 dark:text-gray-400 cursor-not-allowed"
                        />
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">رقم الهاتف المسجل عند إنشاء الحساب (للقراءة فقط)</p>
                    </div>

                    <div class="flex items-center gap-4">
                        <button 
                            type="submit" 
                            :disabled="profileForm.processing"
                            class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg font-medium disabled:opacity-50 transition-colors"
                        >
                            حفظ التغييرات
                        </button>
                    </div>
                </form>
            </div>

            <!-- Security Tab (2FA) -->
            <div v-if="activeTab === 'security'" class="space-y-6">
                <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-sm border border-gray-200 dark:border-gray-700">
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">المصادقة الثنائية</h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400">أضف طبقة حماية إضافية لحسابك</p>
                        </div>
                        <div v-if="isEnforced" class="px-3 py-1 bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400 rounded-full text-sm font-medium">
                            إلزامي
                        </div>
                    </div>

                    <!-- Not Enabled -->
                    <div v-if="!isEnabled" class="space-y-6">
                        <div class="flex items-center gap-4 p-4 bg-amber-50 dark:bg-amber-900/20 rounded-xl border border-amber-200 dark:border-amber-800">
                            <div class="w-12 h-12 rounded-full bg-amber-100 dark:bg-amber-900/30 flex items-center justify-center flex-shrink-0">
                                <svg class="w-6 h-6 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                </svg>
                            </div>
                            <div>
                                <h4 class="font-semibold text-gray-900 dark:text-white">المصادقة الثنائية غير مفعّلة</h4>
                                <p class="text-sm text-gray-600 dark:text-gray-400">قم بتفعيلها لحماية حسابك</p>
                            </div>
                        </div>

                        <!-- Step 1: Select Method & Send Code -->
                        <div v-if="!codeSent" class="space-y-4">
                            <div class="space-y-3">
                                <label class="flex items-center p-4 border border-gray-200 dark:border-gray-700 rounded-xl cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                                    <input type="radio" v-model="selectedMethod" value="email" class="w-4 h-4 text-indigo-600 border-gray-300 focus:ring-indigo-500">
                                    <span class="ms-3 flex-1">
                                        <span class="block text-sm font-medium text-gray-900 dark:text-white">البريد الإلكتروني</span>
                                        <span class="block text-sm text-gray-500">{{ user.email }}</span>
                                    </span>
                                </label>

                                <label v-if="smsEnabled && user.phone" class="flex items-center p-4 border border-gray-200 dark:border-gray-700 rounded-xl cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                                    <input type="radio" v-model="selectedMethod" value="sms" class="w-4 h-4 text-indigo-600 border-gray-300 focus:ring-indigo-500">
                                    <span class="ms-3 flex-1">
                                        <span class="block text-sm font-medium text-gray-900 dark:text-white">الرسائل النصية (SMS)</span>
                                        <span class="block text-sm text-gray-500" dir="ltr">{{ user.phone }}</span>
                                    </span>
                                </label>
                            </div>

                            <button 
                                @click="sendCode" 
                                :disabled="sendCodeForm.processing"
                                class="flex items-center gap-2 px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg font-medium disabled:opacity-50 transition-colors"
                            >
                                <svg v-if="!sendCodeForm.processing" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                </svg>
                                <span v-else class="w-5 h-5 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
                                إرسال رمز التحقق
                            </button>

                            <div v-if="$page.props.errors?.method" class="p-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg text-red-600 dark:text-red-400 text-sm">
                                {{ $page.props.errors.method }}
                            </div>
                        </div>

                        <!-- Step 2: Enter Code -->
                        <div v-else class="space-y-4">
                            <p class="text-gray-600 dark:text-gray-300">
                                أدخل رمز التحقق المرسل إلى {{ selectedMethod === 'sms' ? 'هاتفك' : 'بريدك' }}
                            </p>
                            <form @submit.prevent="enable2FA" class="flex items-start gap-3">
                                <div>
                                    <input 
                                        type="text" 
                                        v-model="codeForm.code"
                                        maxlength="6"
                                        placeholder="000000"
                                        class="w-40 text-center text-2xl tracking-wider rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white font-mono focus:ring-indigo-500 focus:border-indigo-500"
                                        dir="ltr"
                                        inputmode="numeric"
                                        autofocus
                                    />
                                    <p v-if="codeForm.errors.code" class="text-red-500 text-sm mt-1">{{ codeForm.errors.code }}</p>
                                </div>
                                <button type="submit" :disabled="codeForm.processing || codeForm.code.length !== 6" class="px-6 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg font-medium disabled:opacity-50 h-[46px]">
                                    تفعيل
                                </button>
                            </form>
                            <button @click="sendCode" :disabled="sendCodeForm.processing" class="text-indigo-600 hover:text-indigo-800 text-sm font-medium">
                                إعادة إرسال الرمز
                            </button>
                        </div>
                    </div>

                    <!-- Enabled State -->
                    <div v-else class="space-y-6">
                        <div class="flex items-center gap-4 p-4 bg-emerald-50 dark:bg-emerald-900/20 rounded-xl border border-emerald-200 dark:border-emerald-800">
                            <div class="w-12 h-12 rounded-full bg-emerald-100 dark:bg-emerald-900/30 flex items-center justify-center flex-shrink-0">
                                <svg class="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                                </svg>
                            </div>
                            <div>
                                <h4 class="font-semibold text-gray-900 dark:text-white">المصادقة الثنائية مفعّلة ✓</h4>
                                <p class="text-sm text-gray-600 dark:text-gray-400">
                                    يتم إرسال الرمز إلى {{ currentMethod === 'sms' ? 'هاتفك' : 'بريدك' }} عند تسجيل الدخول
                                </p>
                            </div>
                        </div>

                        <!-- Recovery Codes -->
                        <div v-if="recoveryCodes" class="p-4 bg-gray-50 dark:bg-gray-700/30 rounded-xl border border-gray-200 dark:border-gray-700">
                            <div class="flex items-center justify-between mb-3">
                                <h4 class="font-medium text-gray-900 dark:text-white">رموز الاسترداد</h4>
                                <button @click="showRegenModal = true" class="text-sm text-indigo-600 hover:text-indigo-800">إنشاء رموز جديدة</button>
                            </div>
                            <p class="text-sm text-gray-500 mb-3">احتفظ بهذه الرموز في مكان آمن. يمكن استخدام كل رمز مرة واحدة.</p>
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-2">
                                <code v-for="code in recoveryCodes" :key="code" class="text-sm bg-white dark:bg-gray-700 px-3 py-2 rounded text-center font-mono border border-gray-200 dark:border-gray-600">{{ code }}</code>
                            </div>
                        </div>

                        <!-- Disable Button -->
                        <div v-if="!isEnforced" class="pt-4 border-t border-gray-200 dark:border-gray-700">
                            <button 
                                @click="showDisableModal = true"
                                class="px-4 py-2 bg-red-100 hover:bg-red-200 text-red-700 rounded-lg text-sm font-medium transition-colors"
                            >
                                إلغاء المصادقة الثنائية
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Password Tab -->
            <div v-if="activeTab === 'password'" class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-sm border border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-6">تغيير كلمة المرور</h3>
                
                <form @submit.prevent="updatePassword" class="space-y-6 max-w-xl">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">كلمة المرور الحالية</label>
                        <input 
                            type="password" 
                            v-model="passwordForm.current_password"
                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-indigo-500 focus:border-indigo-500"
                            required
                        />
                        <p v-if="passwordForm.errors.current_password" class="mt-1 text-sm text-red-500">{{ passwordForm.errors.current_password }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">كلمة المرور الجديدة</label>
                        <input 
                            type="password" 
                            v-model="passwordForm.password"
                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-indigo-500 focus:border-indigo-500"
                            required
                        />
                        <p v-if="passwordForm.errors.password" class="mt-1 text-sm text-red-500">{{ passwordForm.errors.password }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">تأكيد كلمة المرور</label>
                        <input 
                            type="password" 
                            v-model="passwordForm.password_confirmation"
                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-indigo-500 focus:border-indigo-500"
                            required
                        />
                    </div>

                    <button 
                        type="submit" 
                        :disabled="passwordForm.processing"
                        class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg font-medium disabled:opacity-50 transition-colors"
                    >
                        تحديث كلمة المرور
                    </button>
                </form>
            </div>

            <!-- Disable 2FA Modal -->
            <Transition name="modal">
                <div v-if="showDisableModal" class="fixed inset-0 z-50 overflow-y-auto">
                    <div class="flex min-h-full items-center justify-center p-4">
                        <div class="fixed inset-0 bg-black/50" @click="showDisableModal = false"></div>
                        <div class="relative bg-white dark:bg-gray-800 rounded-2xl p-6 w-full max-w-md shadow-xl">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">تأكيد إلغاء المصادقة الثنائية</h3>
                            <form @submit.prevent="disable2FA">
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

            <!-- Regenerate Codes Modal -->
            <Transition name="modal">
                <div v-if="showRegenModal" class="fixed inset-0 z-50 overflow-y-auto">
                    <div class="flex min-h-full items-center justify-center p-4">
                        <div class="fixed inset-0 bg-black/50" @click="showRegenModal = false"></div>
                        <div class="relative bg-white dark:bg-gray-800 rounded-2xl p-6 w-full max-w-md shadow-xl">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">إنشاء رموز استرداد جديدة</h3>
                            <p class="text-sm text-gray-500 mb-4">سيتم إلغاء الرموز القديمة واستبدالها برموز جديدة.</p>
                            <form @submit.prevent="regenerateCodes">
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
