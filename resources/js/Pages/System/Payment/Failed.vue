<template>
    <SystemLayout>
        <div class="max-w-xl mx-auto py-12 text-center">
            <!-- Failed Icon -->
            <div class="w-20 h-20 mx-auto bg-red-100 dark:bg-red-900/30 rounded-full flex items-center justify-center mb-6">
                <svg class="w-10 h-10 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </div>
            
            <!-- Error Message -->
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">فشل الدفع</h1>
            <p class="text-gray-500 dark:text-gray-400 mb-4">
                عذراً، لم نتمكن من إتمام عملية الدفع
            </p>
            
            <!-- Error Reason -->
            <div v-if="payment.failure_reason" class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-4 mb-8">
                <p class="text-red-700 dark:text-red-300 text-sm">
                    {{ payment.failure_reason }}
                </p>
            </div>
            
            <!-- Payment Details -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-sm border border-gray-200 dark:border-gray-700 text-start mb-8">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">تفاصيل المحاولة</h2>
                <div class="space-y-3">
                    <div class="flex justify-between">
                        <span class="text-gray-600 dark:text-gray-400">رقم العملية</span>
                        <span class="font-medium text-gray-900 dark:text-white font-mono">{{ payment.id }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600 dark:text-gray-400">المبلغ</span>
                        <span class="font-medium text-gray-900 dark:text-white">{{ payment.amount }} {{ payment.currency }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600 dark:text-gray-400">الحالة</span>
                        <span class="px-2 py-1 bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400 rounded-full text-xs font-medium">
                            فشل
                        </span>
                    </div>
                </div>
            </div>
            
            <!-- Actions -->
            <div class="flex justify-center gap-4">
                <button @click="retryPayment" class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg font-medium transition-colors">
                    إعادة المحاولة
                </button>
                <Link href="/system/subscriptions" class="px-6 py-2.5 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-lg font-medium transition-colors">
                    العودة للاشتراكات
                </Link>
            </div>
            
            <!-- Help Text -->
            <p class="mt-8 text-sm text-gray-500 dark:text-gray-400">
                إذا استمرت المشكلة، تواصل مع الدعم الفني
            </p>
        </div>
    </SystemLayout>
</template>

<script setup>
import { Link, router } from '@inertiajs/vue3';
import SystemLayout from '@/Layouts/SystemLayout.vue';

const props = defineProps({
    payment: Object,
});

const retryPayment = () => {
    router.post(`/system/payment/retry/${props.payment.id}`);
};
</script>
