<template>
    <SystemLayout>
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex items-center gap-4">
                <Link href="/system/subscriptions" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                    <svg class="w-5 h-5 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                </Link>
                <div class="flex-1">
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">تفاصيل الاشتراك</h1>
                    <p class="text-gray-500 dark:text-gray-400">{{ subscription.tenant?.trade_name || subscription.tenant?.name }}</p>
                </div>
                <span :class="getStatusClass(subscription.status)" class="px-3 py-1 rounded-full text-sm font-medium">
                    {{ getStatusLabel(subscription.status) }}
                </span>
            </div>
            
            <!-- Main Content -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Subscription Info -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Plan & Billing -->
                    <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 border border-gray-200 dark:border-gray-700">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">معلومات الاشتراك</h2>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <p class="text-sm text-gray-500 dark:text-gray-400">الباقة</p>
                                <p class="font-medium text-gray-900 dark:text-white">{{ subscription.plan?.name_ar }}</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500 dark:text-gray-400">دورة الفوترة</p>
                                <p class="font-medium text-gray-900 dark:text-white">{{ subscription.billing_cycle === 'yearly' ? 'سنوي' : 'شهري' }}</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500 dark:text-gray-400">السعر</p>
                                <p class="font-medium text-gray-900 dark:text-white">{{ subscription.price }} ر.س</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500 dark:text-gray-400">التجديد التلقائي</p>
                                <p class="font-medium" :class="subscription.auto_renew ? 'text-emerald-600' : 'text-red-600'">
                                    {{ subscription.auto_renew ? 'مفعّل' : 'معطّل' }}
                                </p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Dates -->
                    <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 border border-gray-200 dark:border-gray-700">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">التواريخ</h2>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <p class="text-sm text-gray-500 dark:text-gray-400">بداية الفترة</p>
                                <p class="font-medium text-gray-900 dark:text-white">{{ formatDate(subscription.starts_at) }}</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500 dark:text-gray-400">نهاية الفترة</p>
                                <p class="font-medium text-gray-900 dark:text-white">{{ formatDate(subscription.ends_at) }}</p>
                            </div>
                            <div v-if="subscription.trial_ends_at">
                                <p class="text-sm text-gray-500 dark:text-gray-400">نهاية التجربة</p>
                                <p class="font-medium text-amber-600">{{ formatDate(subscription.trial_ends_at) }}</p>
                            </div>
                            <div v-if="subscription.cancelled_at">
                                <p class="text-sm text-gray-500 dark:text-gray-400">تاريخ الإلغاء</p>
                                <p class="font-medium text-red-600">{{ formatDate(subscription.cancelled_at) }}</p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Invoices -->
                    <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 border border-gray-200 dark:border-gray-700">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">الفواتير</h2>
                        <div v-if="subscription.invoices?.length" class="space-y-3">
                            <div v-for="invoice in subscription.invoices" :key="invoice.id" class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                                <div>
                                    <p class="font-medium text-gray-900 dark:text-white">{{ invoice.invoice_number }}</p>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ formatDate(invoice.created_at) }}</p>
                                </div>
                                <div class="text-end">
                                    <p class="font-bold text-gray-900 dark:text-white">{{ invoice.total }} ر.س</p>
                                    <span :class="invoice.status === 'paid' ? 'text-emerald-600' : 'text-amber-600'" class="text-sm">
                                        {{ invoice.status === 'paid' ? 'مدفوع' : 'قيد الانتظار' }}
                                    </span>
                                </div>
                            </div>
                        </div>
                        <p v-else class="text-gray-500 dark:text-gray-400 text-center py-4">لا توجد فواتير</p>
                    </div>
                </div>
                
                <!-- Sidebar Actions -->
                <div class="space-y-6">
                    <!-- Quick Actions -->
                    <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 border border-gray-200 dark:border-gray-700">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">الإجراءات</h2>
                        <div class="space-y-3">
                            <button 
                                v-if="subscription.status === 'active' || subscription.status === 'trial'"
                                @click="cancelSubscription"
                                class="w-full px-4 py-2 bg-red-50 dark:bg-red-900/20 text-red-600 hover:bg-red-100 dark:hover:bg-red-900/30 rounded-lg text-sm font-medium"
                            >
                                إلغاء الاشتراك
                            </button>
                            <button 
                                v-if="subscription.status === 'cancelled'"
                                @click="activateSubscription"
                                class="w-full px-4 py-2 bg-emerald-50 dark:bg-emerald-900/20 text-emerald-600 hover:bg-emerald-100 dark:hover:bg-emerald-900/30 rounded-lg text-sm font-medium"
                            >
                                تفعيل الاشتراك
                            </button>
                            <button 
                                @click="showExtendModal = true"
                                class="w-full px-4 py-2 bg-indigo-50 dark:bg-indigo-900/20 text-indigo-600 hover:bg-indigo-100 dark:hover:bg-indigo-900/30 rounded-lg text-sm font-medium"
                            >
                                تمديد الاشتراك
                            </button>
                        </div>
                    </div>
                    
                    <!-- Tenant Info -->
                    <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 border border-gray-200 dark:border-gray-700">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">المستأجر</h2>
                        <div class="space-y-2">
                            <p class="text-gray-900 dark:text-white font-medium">{{ subscription.tenant?.trade_name || subscription.tenant?.name }}</p>
                            <p class="text-sm text-gray-500 dark:text-gray-400">#{{ subscription.tenant?.id }}</p>
                            <Link :href="`/system/tenants/${subscription.tenant?.id}`" class="text-indigo-600 hover:text-indigo-700 text-sm">
                                عرض تفاصيل المستأجر →
                            </Link>
                        </div>
                    </div>
                    
                    <!-- Promo Code -->
                    <div v-if="subscription.promo_code" class="bg-white dark:bg-gray-800 rounded-2xl p-6 border border-gray-200 dark:border-gray-700">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">رمز الخصم</h2>
                        <span class="font-mono font-bold text-indigo-600 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-900/30 px-3 py-1 rounded">
                            {{ subscription.promo_code }}
                        </span>
                        <p v-if="subscription.discount_percentage" class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                            خصم {{ subscription.discount_percentage }}%
                        </p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Extend Modal -->
        <Transition name="modal">
            <div v-if="showExtendModal" class="fixed inset-0 z-50 overflow-y-auto">
                <div class="flex items-center justify-center min-h-screen px-4">
                    <div class="fixed inset-0 bg-black/50" @click="showExtendModal = false"></div>
                    <div class="relative bg-white dark:bg-gray-800 rounded-2xl p-6 w-full max-w-md">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">تمديد الاشتراك</h3>
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">عدد الأيام</label>
                            <input v-model.number="extendDays" type="number" min="1" max="365" class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white" />
                        </div>
                        <div class="flex gap-3 justify-end">
                            <button @click="showExtendModal = false" class="px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg">
                                إلغاء
                            </button>
                            <button @click="extendSubscription" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg">
                                تمديد
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </Transition>
    </SystemLayout>
</template>

<script setup>
import { ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import SystemLayout from '@/Layouts/SystemLayout.vue';

const props = defineProps({
    subscription: Object,
});

const showExtendModal = ref(false);
const extendDays = ref(30);

const formatDate = (date) => {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('ar-SA');
};

const getStatusClass = (status) => {
    const classes = {
        active: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400',
        trial: 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
        cancelled: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
        expired: 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400',
    };
    return classes[status] || classes.expired;
};

const getStatusLabel = (status) => {
    const labels = {
        active: 'نشط',
        trial: 'تجريبي',
        cancelled: 'ملغي',
        expired: 'منتهي',
    };
    return labels[status] || status;
};

const cancelSubscription = () => {
    if (confirm('هل تريد إلغاء هذا الاشتراك؟')) {
        router.post(`/system/subscriptions/${props.subscription.id}/cancel`);
    }
};

const activateSubscription = () => {
    router.post(`/system/subscriptions/${props.subscription.id}/activate`);
};

const extendSubscription = () => {
    router.post(`/system/subscriptions/${props.subscription.id}/extend`, {
        days: extendDays.value,
    });
    showExtendModal.value = false;
};
</script>

<style scoped>
.modal-enter-active,
.modal-leave-active {
    transition: opacity 0.2s ease;
}
.modal-enter-from,
.modal-leave-to {
    opacity: 0;
}
</style>
