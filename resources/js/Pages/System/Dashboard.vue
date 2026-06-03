<template>
    <SystemLayout>
        <div class="space-y-6">
<!-- Welcome Header -->
<div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-indigo-600 via-purple-600 to-pink-600 p-8 shadow-lg">
    <div class="relative">
        <!-- Decorative blobs -->
        <div class="absolute -top-10 -right-10 w-32 h-32 bg-white/5 rounded-full blur-2xl pointer-events-none" />
        <div class="absolute -bottom-8 -left-8 w-24 h-24 bg-pink-400/10 rounded-full blur-2xl pointer-events-none" />
        <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-48 h-16 bg-white/3 rounded-full blur-3xl pointer-events-none" />
        
        <!-- Grid pattern overlay -->
        <div class="absolute inset-0 opacity-5 pointer-events-none"
             style="background-image: linear-gradient(rgba(255,255,255,.3) 1px, transparent 1px), linear-gradient(90deg, rgba(255,255,255,.3) 1px, transparent 1px); background-size: 32px 32px;" />

        <div class="relative flex flex-col sm:flex-row sm:items-center justify-between gap-4 px-6 py-4">
            <!-- Left: Greeting -->
            <div class="flex items-center gap-4">
                <!-- Avatar ring -->
                <div class="hidden sm:flex w-14 h-14 rounded-2xl bg-white/15 ring-2 ring-white/30 items-center justify-center flex-shrink-0 shadow-lg">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M12 12c2.21 0 4-1.79 4-4h-2c0 1.1.9 2 2 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2c0-1.1.9-2 2-2h2c0 2.21 1.79 4 4 4z" />
                    </svg>
                </div>
                <div>
                    <p class="text-indigo-200 text-sm font-medium">مرحباً أيها المُشرف</p>
                    <h1 class="text-white text-2xl font-bold tracking-tight">
                        لوحة التحكم
                    </h1>
                    <p class="text-indigo-200/70 text-xs mt-0.5">إدارة شاملة لجميع المستأجرين والإعدادات</p>
                </div>
            </div>

            <!-- Right: Date -->
            <div class="flex items-center gap-3 flex-shrink-0">
                <!-- Date badge -->
                <div class="hidden sm:flex items-center gap-2 bg-white/10 backdrop-blur-sm text-white/80 text-xs font-medium px-3 py-2 rounded-xl border border-white/20">
                    <svg class="w-3.5 h-3.5 text-indigo-200 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    <span id="system-date"></span>
                </div>
            </div>
        </div>
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
<div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
    <template v-if="integrations?.length">
        <div class="flex items-center justify-between mb-5">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-blue-500 to-cyan-500 flex items-center justify-center">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/>
                    </svg>
                </div>
                <div>
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-white">أرصدة مزودي الخدمة</h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400">مستوى أرصدة SMS و WhatsApp</p>
                </div>
            </div>
            <button 
                @click="refreshAllBalances" 
                :disabled="isRefreshing"
                class="px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-sm font-medium disabled:opacity-50 flex items-center gap-3 transition-colors"
            >
                <svg :class="{'animate-spin': isRefreshing}" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
                تحديث الأرصدة
            </button>
        </div>
        
        <div class="divide-y divide-gray-200 dark:divide-gray-700">
            <div 
                v-for="integration in integrations" 
                :key="integration.id"
                class="flex items-center py-4"
                :class="getProviderCardClass(integration.provider) + ' bg-gray-50 dark:bg-gray-900/20'"
            >
                <div class="flex items-center gap-4 flex-shrink-0">
                    <div class="w-10 h-10 rounded-xl flex items-center justify-center">
                        <span class="text-2xl">{{ getProviderIcon(integration.type) }}</span>
                    </div>
                    <div>
                        <p class="font-medium text-gray-900 dark:text-white">{{ integration.name_ar }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ integration.type === 'sms' ? 'رسائل SMS' : 'واتساب' }}</p>
                    </div>
                </div>
                
                <div class="flex-1"></div>
                
                <div class="flex items-center gap-4 text-right">
                    <div class="flex items-center gap-3">
                        <p class="text-xs text-gray-500 dark:text-gray-400">الرصيد المتبقي</p>
                        <p class="text-2xl font-bold" :class="getBalanceTextClass(providerBalances[integration.id])">
                            {{ providerBalances[integration.id]?.balance ?? '—' }}
                        </p>
                    </div>
                    <div class="flex items-center gap-2">
                        <button 
                            @click="fetchProviderBalance(integration)"
                            :disabled="providerLoading[integration.id]"
                            class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors disabled:opacity-50"
                        >
                            <svg :class="{'animate-spin': providerLoading[integration.id]}" class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                            </svg>
                        </button>
                        <a 
                            :href="`/system/integrations/${integration.id}`"
                            class="text-sm text-blue-600 dark:text-blue-400 hover:underline"
                        >
                            الإعدادات
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </template>
    <template v-else>
        <div class="text-center py-8">
            <p class="text-gray-500 dark:text-gray-400">لا توجد مزودي خدمة مُفعَّلون لعرض الأرصدة</p>
        </div>
    </template>
</div>
            
<!-- Content Grid -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <!-- Recent Registrations -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="px-6 py-5 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
            <div>
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">التسجيلات الأخيرة</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400">آخر 7 أيام</p>
            </div>
            <Link href="/system/tenants" class="text-sm font-medium text-indigo-600 dark:text-indigo-400 hover:text-indigo-700 dark:hover:text-indigo-300 flex items-center gap-2 transition-colors">
                {{ $t('common.view_all') }}
                <svg class="w-4 h-4 rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </Link>
        </div>
        <div class="divide-y divide-gray-200 dark:divide-gray-700">
            <div v-for="tenant in recentRegistrations" :key="tenant.id" class="px-6 py-4 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <div class="w-10 h-10 rounded-lg bg-gray-100 dark:bg-gray-700 flex items-center justify-center text-gray-600 dark:text-gray-300 font-bold text-sm">
                            {{ (tenant.trade_name || tenant.name)?.charAt(0)?.toUpperCase() }}
                        </div>
                        <div>
                            <p class="font-medium text-gray-900 dark:text-white">{{ tenant.trade_name || tenant.name }}</p>
                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ formatDate(tenant.created_at) }}</p>
                        </div>
                    </div>
                    <span :class="getStatusClass(tenant.status)" class="px-3 py-1.5 rounded-full text-xs font-medium">
                        {{ getStatusLabel(tenant.status) }}
                    </span>
                </div>
            </div>
            <div v-if="!recentRegistrations?.length" class="px-6 py-8 text-center text-gray-500 dark:text-gray-400">
                لا توجد تسجيلات حديثة
            </div>
        </div>
    </div>
    
    <!-- Trial Ending Soon -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="px-6 py-5 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
            <div>
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">⏰ التجربة تنتهي قريباً</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400">خلال 7 أيام</p>
            </div>
        </div>
        <div class="divide-y divide-gray-200 dark:divide-gray-700">
            <div v-for="tenant in trialEndingSoon" :key="tenant.id" class="px-6 py-4 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <div class="w-10 h-10 rounded-lg bg-amber-100 dark:bg-amber-900/50 flex items-center justify-center text-amber-600 dark:text-amber-400">
                            ⚡
                        </div>
                        <div>
                            <p class="font-medium text-gray-900 dark:text-white">{{ tenant.trade_name || tenant.name }}</p>
                            <p class="text-sm text-amber-600 dark:text-amber-400">تنتهي: {{ formatDate(tenant.trial_ends_at) }}</p>
                        </div>
                    </div>
                    <Link :href="`/system/tenants/${tenant.id}`" class="px-4 py-2 rounded-lg bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 text-sm transition-colors">
                        عرض
                    </Link>
                </div>
            </div>
            <div v-if="!trialEndingSoon?.length" class="px-6 py-8 text-center text-gray-500 dark:text-gray-400">
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
    return new Date(date).toLocaleDateString('ar-SA-u-nu-latn');
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
    // Update date in header
    updateSystemDate();
    setInterval(updateSystemDate, 60000); // Update every minute
});

function updateSystemDate() {
    const dateElement = document.getElementById('system-date');
    if (dateElement) {
        const now = new Date();
        const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
        dateElement.textContent = now.toLocaleDateString('ar-SA', options);
    }
}
</script>
