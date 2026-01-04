<template>
    <SystemLayout>
        <div class="space-y-6">
            <!-- Back & Header -->
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <Link href="/system/tenants" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                        <svg class="w-5 h-5 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                        </svg>
                    </Link>
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ tenant.trade_name || tenant.name }}</h1>
                        <p class="text-gray-500 dark:text-gray-400">{{ tenant.legal_name }}</p>
                    </div>
                </div>
                <span :class="getStatusClass(tenant.status)" class="px-3 py-1.5 rounded-full text-sm font-medium">
                    {{ getStatusLabel(tenant.status) }}
                </span>
            </div>
            
            <!-- Quick Actions -->
            <div class="flex flex-wrap gap-3">
                <button 
                    v-if="tenant.status !== 'suspended'"
                    @click="showSuspendModal = true"
                    class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg text-sm font-medium transition-colors"
                >
                    تعليق الحساب
                </button>
                <button 
                    v-if="tenant.status === 'suspended'"
                    @click="activateTenant"
                    class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-sm font-medium transition-colors"
                >
                    تفعيل الحساب
                </button>
                <button 
                    v-if="tenant.status === 'trial'"
                    @click="showExtendModal = true"
                    class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white rounded-lg text-sm font-medium transition-colors"
                >
                    تمديد التجربة
                </button>
                <button 
                    @click="showDeleteModal = true"
                    class="px-4 py-2 border border-red-300 dark:border-red-700 text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg text-sm font-medium transition-colors"
                >
                    🗑️ حذف نهائي
                </button>
            </div>
            
            <!-- Info Cards Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Main Info -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Company Info -->
                    <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-sm border border-gray-200 dark:border-gray-700">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">معلومات الشركة</h2>
                        <dl class="grid grid-cols-2 gap-4">
                            <div>
                                <dt class="text-sm text-gray-500 dark:text-gray-400">الاسم التجاري</dt>
                                <dd class="text-gray-900 dark:text-white font-medium">{{ tenant.trade_name || '-' }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm text-gray-500 dark:text-gray-400">الاسم القانوني</dt>
                                <dd class="text-gray-900 dark:text-white font-medium">{{ tenant.legal_name || '-' }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm text-gray-500 dark:text-gray-400">المالك</dt>
                                <dd class="text-gray-900 dark:text-white font-medium">{{ tenant.owner_name || '-' }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm text-gray-500 dark:text-gray-400">السجل التجاري</dt>
                                <dd class="text-gray-900 dark:text-white font-medium" dir="ltr">{{ tenant.cr_number || '-' }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm text-gray-500 dark:text-gray-400">الرقم الضريبي</dt>
                                <dd class="text-gray-900 dark:text-white font-medium" dir="ltr">{{ tenant.vat_number || '-' }}</dd>
                            </div>
                        </dl>
                    </div>
                    
                    <!-- Contact Info -->
                    <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-sm border border-gray-200 dark:border-gray-700">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">التواصل</h2>
                        <dl class="grid grid-cols-2 gap-4">
                            <div>
                                <dt class="text-sm text-gray-500 dark:text-gray-400">الهاتف</dt>
                                <dd class="text-gray-900 dark:text-white font-medium" dir="ltr">{{ tenant.phone || '-' }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm text-gray-500 dark:text-gray-400">البريد</dt>
                                <dd class="text-gray-900 dark:text-white font-medium">{{ tenant.email || '-' }}</dd>
                            </div>
                        </dl>
                    </div>
                    
                    <!-- Centers -->
                    <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-sm border border-gray-200 dark:border-gray-700">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">المراكز ({{ tenant.centers?.length || 0 }})</h2>
                        <div class="space-y-3">
                            <div v-for="center in tenant.centers" :key="center.id" class="p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="font-medium text-gray-900 dark:text-white">{{ center.name }}</p>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ center.phone || center.email }}</p>
                                    </div>
                                    <span v-if="center.is_main" class="px-2 py-1 bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400 rounded-full text-xs">
                                        رئيسي
                                    </span>
                                </div>
                            </div>
                            <div v-if="!tenant.centers?.length" class="text-center py-4 text-gray-500 dark:text-gray-400">
                                لا توجد مراكز
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Sidebar -->
                <div class="space-y-6">
                    <!-- Status Card -->
                    <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-sm border border-gray-200 dark:border-gray-700">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">الحالة</h2>
                        <dl class="space-y-4">
                            <div>
                                <dt class="text-sm text-gray-500 dark:text-gray-400">الحالة الحالية</dt>
                                <dd class="mt-1">
                                    <span :class="getStatusClass(tenant.status)" class="px-2.5 py-1 rounded-full text-xs font-medium">
                                        {{ getStatusLabel(tenant.status) }}
                                    </span>
                                </dd>
                            </div>
                            <div>
                                <dt class="text-sm text-gray-500 dark:text-gray-400">تاريخ التسجيل</dt>
                                <dd class="text-gray-900 dark:text-white font-medium">{{ formatDate(tenant.created_at) }}</dd>
                            </div>
                            <div v-if="tenant.trial_ends_at">
                                <dt class="text-sm text-gray-500 dark:text-gray-400">انتهاء التجربة</dt>
                                <dd class="text-gray-900 dark:text-white font-medium">{{ formatDate(tenant.trial_ends_at) }}</dd>
                            </div>
                            <div v-if="tenant.suspended_at">
                                <dt class="text-sm text-gray-500 dark:text-gray-400">تاريخ التعليق</dt>
                                <dd class="text-red-600 dark:text-red-400 font-medium">{{ formatDate(tenant.suspended_at) }}</dd>
                            </div>
                            <div v-if="tenant.suspension_reason">
                                <dt class="text-sm text-gray-500 dark:text-gray-400">سبب التعليق</dt>
                                <dd class="text-red-600 dark:text-red-400">{{ tenant.suspension_reason }}</dd>
                            </div>
                        </dl>
                    </div>
                    
                    <!-- Stats Card -->
                    <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-sm border border-gray-200 dark:border-gray-700">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">إحصائيات</h2>
                        <dl class="space-y-4">
                            <div class="flex items-center justify-between">
                                <dt class="text-sm text-gray-500 dark:text-gray-400">المراكز</dt>
                                <dd class="text-lg font-bold text-gray-900 dark:text-white">{{ tenant.centers?.length || 0 }}</dd>
                            </div>
                            <div class="flex items-center justify-between">
                                <dt class="text-sm text-gray-500 dark:text-gray-400">المستخدمين</dt>
                                <dd class="text-lg font-bold text-gray-900 dark:text-white">{{ tenant.users?.length || 0 }}</dd>
                            </div>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Suspend Modal -->
        <div v-if="showSuspendModal" class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen px-4">
                <div class="fixed inset-0 bg-black/50" @click="showSuspendModal = false"></div>
                <div class="relative bg-white dark:bg-gray-800 rounded-2xl p-6 max-w-md w-full shadow-xl">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">تعليق الحساب</h3>
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">سبب التعليق</label>
                        <textarea v-model="suspendReason" rows="3" class="w-full px-4 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white"></textarea>
                    </div>
                    <div class="flex gap-3 justify-end">
                        <button @click="showSuspendModal = false" class="px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg">إلغاء</button>
                        <button @click="suspendTenant" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg">تعليق</button>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Extend Trial Modal -->
        <div v-if="showExtendModal" class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen px-4">
                <div class="fixed inset-0 bg-black/50" @click="showExtendModal = false"></div>
                <div class="relative bg-white dark:bg-gray-800 rounded-2xl p-6 max-w-md w-full shadow-xl">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">تمديد الفترة التجريبية</h3>
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">عدد الأيام</label>
                        <input v-model.number="extendDays" type="number" min="1" max="90" class="w-full px-4 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white" />
                    </div>
                    <div class="flex gap-3 justify-end">
                        <button @click="showExtendModal = false" class="px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg">إلغاء</button>
                        <button @click="extendTrial" class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white rounded-lg">تمديد</button>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Delete Confirmation Modal -->
        <div v-if="showDeleteModal" class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen px-4">
                <div class="fixed inset-0 bg-black/50" @click="showDeleteModal = false"></div>
                <div class="relative bg-white dark:bg-gray-800 rounded-2xl p-6 max-w-md w-full shadow-xl">
                    <h3 class="text-lg font-semibold text-red-600 dark:text-red-400 mb-4">⚠️ حذف نهائي</h3>
                    <div class="mb-4 p-4 bg-red-50 dark:bg-red-900/20 rounded-lg border border-red-200 dark:border-red-800">
                        <p class="text-sm text-red-700 dark:text-red-300">
                            سيتم حذف المستأجر <strong>{{ tenant.name }}</strong> وجميع بياناته نهائياً:
                        </p>
                        <ul class="mt-2 text-sm text-red-600 dark:text-red-400 list-disc list-inside">
                            <li>{{ tenant.users?.length || 0 }} مستخدم</li>
                            <li>{{ tenant.centers?.length || 0 }} مركز</li>
                            <li>جميع البيانات والإعدادات</li>
                        </ul>
                        <p class="mt-2 text-sm text-red-700 dark:text-red-300 font-bold">
                            هذا الإجراء لا يمكن التراجع عنه!
                        </p>
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            اكتب "<span class="font-bold text-red-600">{{ tenant.name }}</span>" للتأكيد
                        </label>
                        <input v-model="deleteConfirmation" type="text" class="w-full px-4 py-2.5 rounded-lg border border-red-300 dark:border-red-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white" />
                    </div>
                    <div class="flex gap-3 justify-end">
                        <button @click="showDeleteModal = false; deleteConfirmation = ''" class="px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg">إلغاء</button>
                        <button 
                            @click="deleteTenant" 
                            :disabled="deleteConfirmation !== tenant.name"
                            :class="deleteConfirmation === tenant.name ? 'bg-red-600 hover:bg-red-700' : 'bg-gray-300 dark:bg-gray-600 cursor-not-allowed'"
                            class="px-4 py-2 text-white rounded-lg"
                        >
                            حذف نهائي
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </SystemLayout>
</template>

<script setup>
import { ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import SystemLayout from '@/Layouts/SystemLayout.vue';

const props = defineProps({
    tenant: Object,
});

const showSuspendModal = ref(false);
const showExtendModal = ref(false);
const showDeleteModal = ref(false);
const suspendReason = ref('');
const extendDays = ref(14);
const deleteConfirmation = ref('');

const formatDate = (date) => {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('ar-SA');
};

const getStatusClass = (status) => {
    const classes = {
        active: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400',
        trial: 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
        suspended: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
    };
    return classes[status] || 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-400';
};

const getStatusLabel = (status) => {
    const labels = {
        active: 'نشط',
        trial: 'تجريبي',
        suspended: 'معلّق',
    };
    return labels[status] || status || 'غير محدد';
};

const suspendTenant = () => {
    router.post(`/system/tenants/${props.tenant.id}/suspend`, {
        reason: suspendReason.value,
    }, {
        onSuccess: () => {
            showSuspendModal.value = false;
            suspendReason.value = '';
        },
    });
};

const activateTenant = () => {
    router.post(`/system/tenants/${props.tenant.id}/activate`);
};

const extendTrial = () => {
    router.post(`/system/tenants/${props.tenant.id}/extend-trial`, {
        days: extendDays.value,
    }, {
        onSuccess: () => {
            showExtendModal.value = false;
            extendDays.value = 14;
        },
    });
};

const deleteTenant = () => {
    if (deleteConfirmation.value !== props.tenant.name) return;
    
    router.delete(`/system/tenants/${props.tenant.id}`, {
        data: { confirmation: deleteConfirmation.value },
    });
};
</script>
