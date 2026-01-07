<template>
    <SystemLayout>
        <div class="space-y-6">
            <!-- Welcome Header -->
            <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-indigo-600 via-purple-600 to-pink-600 p-6 shadow-lg">
                <div class="relative">
                    <h1 class="text-2xl font-bold text-white">مرحباً بك في لوحة التحكم 👋</h1>
                    <p class="mt-1 text-indigo-100">نظرة شاملة على أداء منصتك</p>
                </div>
            </div>
            
            <!-- Stats Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Total Tenants -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 p-5">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 rounded-xl bg-indigo-100 dark:bg-indigo-900/50 flex items-center justify-center">
                            <svg class="w-6 h-6 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">إجمالي المستأجرين</p>
                            <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ stats.total_tenants }}</p>
                        </div>
                    </div>
                </div>
                
                <!-- Active Tenants -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 p-5">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 rounded-xl bg-emerald-100 dark:bg-emerald-900/50 flex items-center justify-center">
                            <svg class="w-6 h-6 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">نشط</p>
                            <p class="text-2xl font-bold text-emerald-600 dark:text-emerald-400">{{ stats.active_tenants }}</p>
                        </div>
                    </div>
                </div>
                
                <!-- Trial Tenants -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 p-5">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 rounded-xl bg-amber-100 dark:bg-amber-900/50 flex items-center justify-center">
                            <svg class="w-6 h-6 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">تجريبي</p>
                            <p class="text-2xl font-bold text-amber-600 dark:text-amber-400">{{ stats.trial_tenants }}</p>
                        </div>
                    </div>
                </div>
                
                <!-- Suspended Tenants -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 p-5">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 rounded-xl bg-red-100 dark:bg-red-900/50 flex items-center justify-center">
                            <svg class="w-6 h-6 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">معلّق</p>
                            <p class="text-2xl font-bold text-red-600 dark:text-red-400">{{ stats.suspended_tenants }}</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Provider Balances -->
            <div v-if="integrations?.length" class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 p-5">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-blue-500 to-cyan-500 flex items-center justify-center">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/>
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">أرصدة مزودي الخدمة</h2>
                            <p class="text-sm text-gray-500 dark:text-gray-400">SMS & WhatsApp</p>
                        </div>
                    </div>
                    <button 
                        @click="refreshAllBalances" 
                        :disabled="isRefreshing"
                        class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-medium disabled:opacity-50 flex items-center gap-2 transition-colors"
                    >
                        <svg :class="{'animate-spin': isRefreshing}" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        تحديث الأرصدة
                    </button>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div 
                        v-for="integration in integrations" 
                        :key="integration.id"
                        class="relative overflow-hidden rounded-xl p-4 border border-gray-200 dark:border-gray-700"
                        :class="getProviderCardClass(integration.provider)"
                    >
                        <div class="flex items-center justify-between mb-3">
                            <div class="flex items-center gap-2">
                                <span class="text-2xl">{{ getProviderIcon(integration.type) }}</span>
                                <div>
                                    <p class="font-medium text-gray-900 dark:text-white text-sm">{{ integration.name_ar }}</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ integration.type === 'sms' ? 'رسائل SMS' : 'واتساب' }}</p>
                                </div>
                            </div>
                            <button 
                                @click="fetchProviderBalance(integration)"
                                :disabled="providerLoading[integration.id]"
                                class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors disabled:opacity-50"
                            >
                                <svg :class="{'animate-spin': providerLoading[integration.id]}" class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                </svg>
                            </button>
                        </div>
                        
                        <div class="flex items-end justify-between">
                            <div>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">الرصيد المتبقي</p>
                                <p class="text-2xl font-bold" :class="getBalanceTextClass(providerBalances[integration.id])">
                                    {{ providerBalances[integration.id]?.balance ?? '—' }}
                                </p>
                            </div>
                            <a 
                                :href="`/system/integrations/${integration.id}`"
                                class="text-xs text-blue-600 dark:text-blue-400 hover:underline"
                            >
                                الإعدادات ←
                            </a>
                        </div>
                        
                        <p v-if="providerErrors[integration.id]" class="mt-2 text-xs text-red-500">
                            {{ providerErrors[integration.id] }}
                        </p>
                    </div>
                </div>
            </div>
            
            <!-- Content Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Recent Registrations -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
                    <div class="px-5 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                        <div>
                            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">التسجيلات الأخيرة</h2>
                            <p class="text-sm text-gray-500 dark:text-gray-400">آخر 7 أيام</p>
                        </div>
                        <Link href="/system/tenants" class="text-sm text-indigo-600 dark:text-indigo-400 hover:underline">
                            عرض الكل →
                        </Link>
                    </div>
                    <div class="divide-y divide-gray-200 dark:divide-gray-700">
                        <div v-for="tenant in recentRegistrations" :key="tenant.id" class="px-5 py-4 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-lg bg-gray-100 dark:bg-gray-700 flex items-center justify-center text-gray-600 dark:text-gray-300 font-bold text-sm">
                                        {{ (tenant.trade_name || tenant.name)?.charAt(0)?.toUpperCase() }}
                                    </div>
                                    <div>
                                        <p class="font-medium text-gray-900 dark:text-white">{{ tenant.trade_name || tenant.name }}</p>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ formatDate(tenant.created_at) }}</p>
                                    </div>
                                </div>
                                <span :class="getStatusClass(tenant.status)" class="px-2.5 py-1 rounded-full text-xs font-medium">
                                    {{ getStatusLabel(tenant.status) }}
                                </span>
                            </div>
                        </div>
                        <div v-if="!recentRegistrations?.length" class="px-5 py-12 text-center text-gray-500 dark:text-gray-400">
                            لا توجد تسجيلات حديثة
                        </div>
                    </div>
                </div>
                
                <!-- Trial Ending Soon -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
                    <div class="px-5 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                        <div>
                            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">⏰ التجربة تنتهي قريباً</h2>
                            <p class="text-sm text-gray-500 dark:text-gray-400">خلال 7 أيام</p>
                        </div>
                    </div>
                    <div class="divide-y divide-gray-200 dark:divide-gray-700">
                        <div v-for="tenant in trialEndingSoon" :key="tenant.id" class="px-5 py-4 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-lg bg-amber-100 dark:bg-amber-900/50 flex items-center justify-center text-amber-600 dark:text-amber-400">
                                        ⚡
                                    </div>
                                    <div>
                                        <p class="font-medium text-gray-900 dark:text-white">{{ tenant.trade_name || tenant.name }}</p>
                                        <p class="text-sm text-amber-600 dark:text-amber-400">تنتهي: {{ formatDate(tenant.trial_ends_at) }}</p>
                                    </div>
                                </div>
                                <Link :href="`/system/tenants/${tenant.id}`" class="px-3 py-1.5 rounded-lg bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 text-xs transition-colors">
                                    عرض
                                </Link>
                            </div>
                        </div>
                        <div v-if="!trialEndingSoon?.length" class="px-5 py-12 text-center text-gray-500 dark:text-gray-400">
                            لا توجد فترات تجريبية منتهية قريباً
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </SystemLayout>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue';
import { Link } from '@inertiajs/vue3';
import SystemLayout from '@/Layouts/SystemLayout.vue';

const props = defineProps({
    stats: Object,
    recentRegistrations: Array,
    trialEndingSoon: Array,
    integrations: Array,
});

// Balance state
const providerBalances = reactive({});
const providerLoading = reactive({});
const providerErrors = reactive({});
const isRefreshing = ref(false);

const formatDate = (date) => {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('ar-SA');
};

const getStatusClass = (status) => {
    const classes = {
        active: 'bg-emerald-100 dark:bg-emerald-900/50 text-emerald-700 dark:text-emerald-400',
        trial: 'bg-amber-100 dark:bg-amber-900/50 text-amber-700 dark:text-amber-400',
        suspended: 'bg-red-100 dark:bg-red-900/50 text-red-700 dark:text-red-400',
    };
    return classes[status] || 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-400';
};

const getStatusLabel = (status) => {
    const labels = {
        active: 'نشط',
        trial: 'تجريبي',
        suspended: 'معلّق',
    };
    return labels[status] || status;
};

const getProviderIcon = (type) => {
    return type === 'sms' ? '📱' : '💬';
};

const getProviderCardClass = (provider) => {
    const classes = {
        authentica: 'bg-gradient-to-br from-emerald-50 to-teal-50 dark:from-emerald-900/20 dark:to-teal-900/20',
        unifonic: 'bg-gradient-to-br from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20',
        twilio: 'bg-gradient-to-br from-red-50 to-pink-50 dark:from-red-900/20 dark:to-pink-900/20',
        whatsapp_cloud: 'bg-gradient-to-br from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20',
    };
    return classes[provider] || 'bg-gray-50 dark:bg-gray-900/20';
};

const getBalanceTextClass = (balanceData) => {
    if (!balanceData?.balance) return 'text-gray-400';
    const balance = parseFloat(balanceData.balance);
    if (balance <= 10) return 'text-red-600 dark:text-red-400';
    if (balance <= 50) return 'text-amber-600 dark:text-amber-400';
    return 'text-emerald-600 dark:text-emerald-400';
};

const fetchProviderBalance = async (integration) => {
    providerLoading[integration.id] = true;
    providerErrors[integration.id] = null;
    
    try {
        const response = await fetch(`/system/integrations/${integration.id}/balance`);
        const data = await response.json();
        
        if (data.success) {
            providerBalances[integration.id] = data;
        } else {
            providerErrors[integration.id] = data.message || 'فشل في جلب الرصيد';
        }
    } catch (error) {
        providerErrors[integration.id] = 'خطأ في الاتصال';
    } finally {
        providerLoading[integration.id] = false;
    }
};

const refreshAllBalances = async () => {
    isRefreshing.value = true;
    
    const promises = props.integrations?.map(integration => fetchProviderBalance(integration)) || [];
    await Promise.all(promises);
    
    isRefreshing.value = false;
};

// Auto-fetch balances on mount
onMounted(() => {
    if (props.integrations?.length) {
        refreshAllBalances();
    }
});
</script>
