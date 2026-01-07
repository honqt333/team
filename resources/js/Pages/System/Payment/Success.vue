<template>
    <SystemLayout>
        <div class="max-w-xl mx-auto py-12 text-center">
            <!-- Success Icon -->
            <div class="w-20 h-20 mx-auto bg-emerald-100 dark:bg-emerald-900/30 rounded-full flex items-center justify-center mb-6">
                <svg class="w-10 h-10 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
            </div>
            
            <!-- Success Message -->
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">تم الدفع بنجاح! 🎉</h1>
            <p class="text-gray-500 dark:text-gray-400 mb-8">
                تم تفعيل الاشتراك وسيتم إرسال الفاتورة على البريد الإلكتروني
            </p>
            
            <!-- Payment Details -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-sm border border-gray-200 dark:border-gray-700 text-start mb-8">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">تفاصيل الدفع</h2>
                <div class="space-y-3">
                    <div class="flex justify-between">
                        <span class="text-gray-600 dark:text-gray-400">رقم العملية</span>
                        <span class="font-medium text-gray-900 dark:text-white font-mono">{{ payment.gateway_payment_id || payment.id }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600 dark:text-gray-400">المبلغ</span>
                        <span class="font-medium text-emerald-600 dark:text-emerald-400">{{ payment.amount }} {{ payment.currency }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600 dark:text-gray-400">طريقة الدفع</span>
                        <span class="font-medium text-gray-900 dark:text-white">{{ getPaymentMethodLabel(payment.payment_method) }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600 dark:text-gray-400">تاريخ الدفع</span>
                        <span class="font-medium text-gray-900 dark:text-white">{{ formatDate(payment.paid_at) }}</span>
                    </div>
                </div>
            </div>
            
            <!-- Actions -->
            <div class="flex justify-center gap-4">
                <Link href="/system/subscriptions" class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg font-medium transition-colors">
                    عرض الاشتراكات
                </Link>
                <Link href="/system" class="px-6 py-2.5 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-lg font-medium transition-colors">
                    الرئيسية
                </Link>
            </div>
        </div>
    </SystemLayout>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';
import SystemLayout from '@/Layouts/SystemLayout.vue';

defineProps({
    payment: Object,
});

const getPaymentMethodLabel = (method) => {
    const labels = {
        creditcard: 'بطاقة ائتمان',
        mada: 'مدى',
        visa: 'Visa',
        mastercard: 'Mastercard',
        applepay: 'Apple Pay',
    };
    return labels[method] || method || '-';
};

const formatDate = (date) => {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('ar-SA', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};
</script>
