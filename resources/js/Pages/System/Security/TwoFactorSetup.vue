<template>
    <SystemLayout>
        <div class="max-w-2xl mx-auto space-y-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">المصادقة الثنائية</h1>
                <p class="text-gray-500 dark:text-gray-400">أضف طبقة حماية إضافية لحسابك</p>
            </div>
            
            <!-- Not Enabled State -->
            <div v-if="!isEnabled" class="bg-white dark:bg-gray-800 rounded-xl p-6 border border-gray-200 dark:border-gray-700">
                <div class="flex items-center gap-4 mb-6">
                    <div class="w-12 h-12 rounded-full bg-amber-100 dark:bg-amber-900/30 flex items-center justify-center">
                        <svg class="w-6 h-6 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="font-semibold text-gray-900 dark:text-white">المصادقة الثنائية غير مفعّلة</h3>
                        <p class="text-sm text-gray-500">قم بتفعيلها لحماية حسابك</p>
                    </div>
                </div>

                <div class="space-y-6">
                    <div>
                        <h4 class="font-medium text-gray-900 dark:text-white mb-3">1. امسح رمز QR</h4>
                        <p class="text-sm text-gray-500 mb-3">استخدم تطبيق Google Authenticator أو أي تطبيق TOTP</p>
                        <div class="flex items-center gap-6">
                            <div v-html="qrCode" class="bg-white p-2 rounded-lg border"></div>
                            <div>
                                <p class="text-xs text-gray-500 mb-1">أو أدخل الرمز يدوياً:</p>
                                <code class="block text-sm bg-gray-100 dark:bg-gray-700 px-3 py-2 rounded font-mono select-all">{{ secret }}</code>
                            </div>
                        </div>
                    </div>
                    
                    <div>
                        <h4 class="font-medium text-gray-900 dark:text-white mb-3">2. أدخل رمز التحقق</h4>
                        <form @submit.prevent="enable" class="flex gap-3">
                            <input 
                                type="text" 
                                v-model="form.code"
                                maxlength="6"
                                placeholder="000000"
                                class="w-40 text-center text-2xl tracking-wider rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white font-mono"
                                dir="ltr"
                                inputmode="numeric"
                            />
                            <button type="submit" :disabled="form.processing || form.code.length !== 6" class="px-6 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg font-medium disabled:opacity-50">
                                تفعيل
                            </button>
                        </form>
                        <p v-if="$page.props.errors?.code" class="text-red-500 text-sm mt-1">{{ $page.props.errors.code }}</p>
                    </div>
                </div>
            </div>
            
            <!-- Enabled State -->
            <div v-else class="space-y-6">
                <div class="bg-white dark:bg-gray-800 rounded-xl p-6 border border-gray-200 dark:border-gray-700">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 rounded-full bg-emerald-100 dark:bg-emerald-900/30 flex items-center justify-center">
                            <svg class="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                            </svg>
                        </div>
                        <div class="flex-1">
                            <h3 class="font-semibold text-gray-900 dark:text-white">المصادقة الثنائية مفعّلة ✓</h3>
                            <p class="text-sm text-gray-500">حسابك محمي بطبقة أمان إضافية</p>
                        </div>
                    </div>
                </div>
                
                <!-- Recovery Codes -->
                <div class="bg-white dark:bg-gray-800 rounded-xl p-6 border border-gray-200 dark:border-gray-700">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="font-semibold text-gray-900 dark:text-white">رموز الاسترداد</h3>
                        <button @click="showRegenModal = true" class="text-sm text-indigo-600 hover:text-indigo-800">إنشاء رموز جديدة</button>
                    </div>
                    <p class="text-sm text-gray-500 mb-4">احتفظ بهذه الرموز في مكان آمن. يمكن استخدام كل رمز مرة واحدة فقط.</p>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-2">
                        <code v-for="code in recoveryCodes" :key="code" class="text-sm bg-gray-100 dark:bg-gray-700 px-3 py-2 rounded text-center font-mono">{{ code }}</code>
                    </div>
                </div>
                
                <!-- Disable 2FA -->
                <div class="bg-white dark:bg-gray-800 rounded-xl p-6 border border-red-200 dark:border-red-900/30">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="font-semibold text-gray-900 dark:text-white">إلغاء المصادقة الثنائية</h3>
                            <p class="text-sm text-gray-500">هذا سيقلل من أمان حسابك</p>
                        </div>
                        <button @click="showDisableModal = true" class="px-4 py-2 bg-red-100 hover:bg-red-200 text-red-700 rounded-lg text-sm font-medium">
                            إلغاء التفعيل
                        </button>
                    </div>
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
    </SystemLayout>
</template>

<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import SystemLayout from '@/Layouts/SystemLayout.vue';

const props = defineProps({
    secret: String,
    qrCode: String,
    isEnabled: Boolean,
    recoveryCodes: Array,
});

const showDisableModal = ref(false);
const showRegenModal = ref(false);

const form = useForm({ code: '' });
const disableForm = useForm({ password: '' });
const regenForm = useForm({ password: '' });

const enable = () => form.post('/system/security/2fa/enable');
const disable = () => disableForm.post('/system/security/2fa/disable', { onSuccess: () => showDisableModal.value = false });
const regenerate = () => regenForm.post('/system/security/2fa/regenerate', { onSuccess: () => showRegenModal.value = false });
</script>

<style scoped>
.modal-enter-active, .modal-leave-active { transition: opacity 0.2s ease; }
.modal-enter-from, .modal-leave-to { opacity: 0; }
</style>
