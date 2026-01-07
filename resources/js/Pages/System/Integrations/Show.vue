<template>
    <SystemLayout>
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <a href="/system/integrations" class="text-gray-500 hover:text-gray-700">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                        </svg>
                    </a>
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ integration.name_ar }}</h1>
                        <p class="text-gray-500 dark:text-gray-400">{{ integration.name }} • {{ integration.type_label }}</p>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <span :class="integration.is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-100 text-gray-500'" class="px-3 py-1 rounded-full text-sm">
                        {{ integration.is_active ? 'مفعّل' : 'معطّل' }}
                    </span>
                    <button @click="deleteIntegration" class="text-red-600 hover:text-red-700 text-sm">حذف</button>
                </div>
            </div>
            
            <!-- Config Form -->
            <div class="bg-white dark:bg-gray-800 rounded-xl p-6 border border-gray-200 dark:border-gray-700">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">إعدادات الاتصال</h2>
                <form @submit.prevent="saveConfig" class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div v-for="field in configFields" :key="field.key">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                {{ field.label }}
                                <span v-if="field.required" class="text-red-500">*</span>
                            </label>
                            <input 
                                v-if="field.type !== 'select'"
                                :type="field.type"
                                v-model="form.config[field.key]"
                                :required="field.required"
                                :placeholder="field.type === 'password' ? '••••••••' : ''"
                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                dir="ltr"
                            />
                            <select 
                                v-else
                                v-model="form.config[field.key]"
                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                            >
                                <option v-for="opt in field.options" :key="opt" :value="opt">{{ opt }}</option>
                            </select>
                            <p v-if="form.errors[`config.${field.key}`]" class="mt-1 text-sm text-red-600 dark:text-red-400">
                                {{ form.errors[`config.${field.key}`] }}
                            </p>
                        </div>
                    </div>
                    
                    <div class="flex items-center gap-6 pt-4 border-t border-gray-200 dark:border-gray-700">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" v-model="form.is_active" class="rounded border-gray-300 text-indigo-600" />
                            <span class="text-sm text-gray-700 dark:text-gray-300">تفعيل التكامل</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" v-model="form.is_default" class="rounded border-gray-300 text-amber-600" />
                            <span class="text-sm text-gray-700 dark:text-gray-300">جعله الافتراضي</span>
                        </label>
                    </div>
                    
                    <!-- Purpose selector for SMS/WhatsApp -->
                    <div v-if="['sms', 'whatsapp'].includes(integration.type)" class="pt-4 border-t border-gray-200 dark:border-gray-700">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            الغرض من الاستخدام
                        </label>
                        <div class="grid grid-cols-3 gap-3">
                            <label 
                                v-for="(purpose, key) in purposes" 
                                :key="key"
                                class="relative flex items-start p-4 rounded-lg border-2 cursor-pointer transition-all"
                                :class="form.purpose === key ? 'border-indigo-500 bg-indigo-50 dark:bg-indigo-900/20' : 'border-gray-200 dark:border-gray-700 hover:border-gray-300'"
                            >
                                <input 
                                    type="radio" 
                                    v-model="form.purpose" 
                                    :value="key"
                                    class="sr-only"
                                />
                                <div>
                                    <p class="font-medium text-gray-900 dark:text-white text-sm">{{ purpose.label }}</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ purpose.description }}</p>
                                </div>
                                <div v-if="form.purpose === key" class="absolute top-2 left-2">
                                    <svg class="w-5 h-5 text-indigo-600" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                    </svg>
                                </div>
                            </label>
                        </div>
                    </div>
                    
                    <div class="flex gap-3 pt-4">
                        <button type="submit" :disabled="form.processing" class="px-6 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg font-medium disabled:opacity-50">
                            {{ form.processing ? 'جاري الحفظ...' : 'حفظ الإعدادات' }}
                        </button>
                    </div>
                </form>
            </div>
            
            <!-- Test Section -->
            <div class="bg-white dark:bg-gray-800 rounded-xl p-6 border border-gray-200 dark:border-gray-700">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">اختبار التكامل</h2>
                <form @submit.prevent="testIntegration" class="flex gap-3">
                    <input 
                        type="text" 
                        v-model="testForm.test_recipient"
                        :placeholder="testPlaceholder"
                        class="flex-1 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                        dir="ltr"
                    />
                    <button type="submit" :disabled="testForm.processing" class="px-6 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg font-medium disabled:opacity-50">
                        {{ testForm.processing ? 'جاري الإرسال...' : 'إرسال اختبار' }}
                    </button>
                </form>
            </div>
            
            <!-- Balance Section (Authentica only) -->
            <div v-if="integration.provider === 'authentica'" class="bg-white dark:bg-gray-800 rounded-xl p-6 border border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">رصيد SMS المتبقي</h2>
                    <button 
                        @click="fetchBalance" 
                        :disabled="balanceLoading"
                        class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-medium disabled:opacity-50 flex items-center gap-2"
                    >
                        <svg v-if="balanceLoading" class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        {{ balanceLoading ? 'جاري الجلب...' : 'تحديث الرصيد' }}
                    </button>
                </div>
                
                <div class="mt-4">
                    <div v-if="balanceData" class="flex items-center gap-4">
                        <div class="bg-gradient-to-br from-emerald-500 to-teal-600 text-white rounded-xl p-6 flex-1">
                            <p class="text-sm opacity-80">الرصيد الحالي</p>
                            <p class="text-4xl font-bold mt-1">{{ balanceData.balance ?? 'غير متاح' }}</p>
                            <p class="text-xs opacity-70 mt-2">رسالة SMS</p>
                        </div>
                        <div v-if="balanceData.data" class="text-sm text-gray-500 dark:text-gray-400">
                            <p v-if="balanceData.data.credit">الرصيد الإجمالي: {{ balanceData.data.credit }}</p>
                            <p v-if="balanceData.data.used">المستخدم: {{ balanceData.data.used }}</p>
                        </div>
                    </div>
                    <div v-else-if="balanceError" class="bg-red-50 dark:bg-red-900/20 text-red-600 dark:text-red-400 rounded-lg p-4">
                        <p class="text-sm">{{ balanceError }}</p>
                    </div>
                    <p v-else class="text-gray-500 dark:text-gray-400 text-sm">اضغط على زر التحديث لجلب الرصيد من مزود الخدمة</p>
                </div>
            </div>
            
            <!-- Recent Logs -->
            <div class="bg-white dark:bg-gray-800 rounded-xl p-6 border border-gray-200 dark:border-gray-700">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">سجل الاستخدام الأخير</h2>
                <div v-if="logs.length" class="space-y-2">
                    <div v-for="log in logs" :key="log.id" class="flex items-center justify-between py-2 border-b border-gray-100 dark:border-gray-700 last:border-0">
                        <div class="flex items-center gap-3">
                            <span :class="log.status === 'success' ? 'text-emerald-500' : 'text-red-500'">
                                {{ log.status === 'success' ? '✓' : '✗' }}
                            </span>
                            <span class="text-sm text-gray-700 dark:text-gray-300">{{ log.action }}</span>
                        </div>
                        <div class="flex items-center gap-4 text-xs text-gray-500">
                            <span v-if="log.response_time_ms">{{ log.response_time_ms }}ms</span>
                            <span>{{ formatDate(log.created_at) }}</span>
                        </div>
                    </div>
                </div>
                <p v-else class="text-gray-500 text-sm">لا يوجد سجلات</p>
            </div>
        </div>
    </SystemLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import SystemLayout from '@/Layouts/SystemLayout.vue';

const props = defineProps({
    integration: Object,
    configFields: Array,
    logs: Array,
    purposes: Object, // Passed from controller
});

const form = useForm({
    config: { ...props.integration.config },
    is_active: props.integration.is_active,
    is_default: props.integration.is_default,
    purpose: props.integration.purpose || 'all',
});

const testForm = useForm({
    test_recipient: '',
});

// Purposes data (fallback if not passed from controller)
const purposes = props.purposes || {
    all: { label: 'الكل', description: 'لجميع الرسائل' },
    otp: { label: 'رموز التحقق', description: 'للتحقق الثنائي فقط' },
    notifications: { label: 'الإشعارات', description: 'للرسائل الإشعارية فقط' },
};

// Balance state
const balanceLoading = ref(false);
const balanceData = ref(null);
const balanceError = ref(null);

const testPlaceholder = computed(() => {
    if (props.integration.type === 'email') return 'example@email.com';
    return '+966xxxxxxxxx';
});

const saveConfig = () => {
    form.put(`/system/integrations/${props.integration.id}`);
};

const testIntegration = () => {
    testForm.post(`/system/integrations/${props.integration.id}/test`);
};

const deleteIntegration = () => {
    if (confirm('هل تريد حذف هذا التكامل؟')) {
        router.delete(`/system/integrations/${props.integration.id}`);
    }
};

const fetchBalance = async () => {
    balanceLoading.value = true;
    balanceError.value = null;
    
    try {
        const response = await fetch(`/system/integrations/${props.integration.id}/balance`);
        const data = await response.json();
        
        if (data.success) {
            balanceData.value = data;
        } else {
            balanceError.value = data.message || 'فشل في جلب الرصيد';
        }
    } catch (error) {
        balanceError.value = 'حدث خطأ في الاتصال بالخادم';
    } finally {
        balanceLoading.value = false;
    }
};

const formatDate = (date) => date ? new Date(date).toLocaleString('ar-SA') : '-';
</script>
