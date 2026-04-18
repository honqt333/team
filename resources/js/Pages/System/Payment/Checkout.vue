<template>
    <SystemLayout>
        <div class="max-w-2xl mx-auto py-8">
            <!-- Header -->
            <div class="text-center mb-8">
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">إتمام الدفع</h1>
                <p class="text-gray-500 dark:text-gray-400">اختر طريقة الدفع لإتمام الاشتراك</p>
            </div>
            
            <!-- Order Summary -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-sm border border-gray-200 dark:border-gray-700 mb-6">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">ملخص الطلب</h2>
                <div class="space-y-3">
                    <div class="flex justify-between">
                        <span class="text-gray-600 dark:text-gray-400">المستأجر</span>
                        <span class="font-medium text-gray-900 dark:text-white">{{ subscription.tenant?.trade_name }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600 dark:text-gray-400">الباقة</span>
                        <span class="font-medium text-gray-900 dark:text-white">{{ subscription.plan?.name_ar }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600 dark:text-gray-400">الدورة</span>
                        <span class="font-medium text-gray-900 dark:text-white">{{ subscription.billing_cycle === 'yearly' ? 'سنوي' : 'شهري' }}</span>
                    </div>
                    <hr class="border-gray-200 dark:border-gray-700" />
                    <div class="flex justify-between text-lg">
                        <span class="font-semibold text-gray-900 dark:text-white">المجموع</span>
                        <span class="font-bold text-indigo-600 dark:text-indigo-400">{{ subscription.price }} ر.س</span>
                    </div>
                </div>
            </div>
            
            <!-- Payment Form Placeholder -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-sm border border-gray-200 dark:border-gray-700 mb-6">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">بيانات الدفع</h2>
                
                <!-- Gateway not configured message -->
                <div v-if="!isGatewayConfigured" class="text-center py-8">
                    <svg class="w-16 h-16 mx-auto text-amber-500 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">بوابة الدفع غير مُعدّة</h3>
                    <p class="text-gray-500 dark:text-gray-400 mb-4">
                        يجب إعداد مفاتيح API لبوابة الدفع في ملف .env
                    </p>
                    <div class="bg-gray-100 dark:bg-gray-700 rounded-lg p-4 text-start" dir="ltr">
                        <code class="text-sm text-gray-800 dark:text-gray-200">
                            MOYASAR_PUBLISHABLE_KEY=pk_xxx<br>
                            MOYASAR_SECRET_KEY=sk_xxx
                        </code>
                    </div>
                </div>
                
                <!-- Moyasar Payment Form -->
                <div v-else>
                    <div id="moyasar-form" class="min-h-[200px]"></div>
                </div>
            </div>
            
            <!-- Security Notice -->
            <div class="flex items-center justify-center gap-2 text-sm text-gray-500 dark:text-gray-400">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                </svg>
                <span>الدفع آمن ومشفر بمعيار PCI DSS</span>
            </div>
        </div>
    </SystemLayout>
</template>

<script setup>
import { computed, onMounted } from 'vue';
import SystemLayout from '@/Layouts/SystemLayout.vue';

const props = defineProps({
    subscription: Object,
    payment: Object,
    formData: Object,
    gateways: Object,
});

const isGatewayConfigured = computed(() => {
    return props.formData?.publishable_key && props.formData?.publishable_key !== '';
});

onMounted(() => {
    if (isGatewayConfigured.value && props.formData?.gateway === 'moyasar') {
        // Load Moyasar script dynamically
        const script = document.createElement('script');
        script.src = 'https://cdn.moyasar.com/mpf/1.14.0/moyasar.js';
        script.onload = initMoyasar;
        document.head.appendChild(script);
        
        // Load Moyasar CSS
        const link = document.createElement('link');
        link.rel = 'stylesheet';
        link.href = 'https://cdn.moyasar.com/mpf/1.14.0/moyasar.css';
        document.head.appendChild(link);
    }
});

const initMoyasar = () => {
    if (typeof Moyasar === 'undefined') return;
    
    Moyasar.init({
        element: '#moyasar-form',
        amount: props.formData.amount,
        currency: props.formData.currency,
        description: props.formData.description,
        publishable_api_key: props.formData.publishable_key,
        callback_url: props.formData.callback_url,
        metadata: props.formData.metadata,
        methods: ['creditcard', 'applepay'],
        apple_pay: {
            country: 'SA',
            label: 'Khidmh Pro',
            validate_merchant_url: 'https://api.moyasar.com/v1/applepay/initiate',
        },
        on_completed: function(payment) {
            console.log('Payment completed:', payment);
        },
    });
};
</script>
