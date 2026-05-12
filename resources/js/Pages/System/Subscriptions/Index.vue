<template>
    <SystemLayout>
        <div class="space-y-6">
            <!-- Page Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">الاشتراكات</h1>
                    <p class="text-gray-500 dark:text-gray-400">إدارة اشتراكات المستأجرين</p>
                </div>
                <button @click="openModal()" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-medium transition-colors">
                    + إنشاء اشتراك
                </button>
            </div>
            
            <!-- Stats Cards -->
            <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
                <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700">
                    <p class="text-sm text-gray-500 dark:text-gray-400">الإجمالي</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ stats.total }}</p>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700">
                    <p class="text-sm text-gray-500 dark:text-gray-400">نشط</p>
                    <p class="text-2xl font-bold text-emerald-600">{{ stats.active }}</p>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700">
                    <p class="text-sm text-gray-500 dark:text-gray-400">تجريبي</p>
                    <p class="text-2xl font-bold text-amber-600">{{ stats.trial }}</p>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700">
                    <p class="text-sm text-gray-500 dark:text-gray-400">ملغي</p>
                    <p class="text-2xl font-bold text-red-600">{{ stats.cancelled }}</p>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700">
                    <p class="text-sm text-gray-500 dark:text-gray-400">منتهي</p>
                    <p class="text-2xl font-bold text-gray-500">{{ stats.expired }}</p>
                </div>
            </div>
            
            <!-- Filters -->
            <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700">
                <div class="flex flex-wrap gap-4">
                    <div class="flex-1 min-w-[200px]">
                        <input 
                            v-model="search" 
                            type="text" 
                            placeholder="بحث بالاسم..."
                            class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm"
                            @keyup.enter="applyFilters"
                        />
                    </div>
                    <select 
                        v-model="statusFilter" 
                        @change="applyFilters"
                        class="px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm"
                    >
                        <option value="all">جميع الحالات</option>
                        <option value="active">نشط</option>
                        <option value="trial">تجريبي</option>
                        <option value="cancelled">ملغي</option>
                        <option value="expired">منتهي</option>
                    </select>
                    <select 
                        v-model="planFilter" 
                        @change="applyFilters"
                        class="px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm"
                    >
                        <option value="">جميع الباقات</option>
                        <option v-for="plan in plans" :key="plan.id" :value="plan.id">{{ plan.name_ar }}</option>
                    </select>
                </div>
            </div>
            
            <!-- Subscriptions Table -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50 dark:bg-gray-700/50">
                            <tr>
                                <th class="px-6 py-4 text-start text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">المستأجر</th>
                                <th class="px-6 py-4 text-start text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">الباقة</th>
                                <th class="px-6 py-4 text-start text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">الدورة</th>
                                <th class="px-6 py-4 text-start text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">الحالة</th>
                                <th class="px-6 py-4 text-start text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">ينتهي في</th>
                                <th class="px-6 py-4"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            <tr v-for="sub in subscriptions.data" :key="sub.id" class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                                <td class="px-6 py-4">
                                    <div>
                                        <p class="font-medium text-gray-900 dark:text-white">{{ sub.tenant?.trade_name || sub.tenant?.name }}</p>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">#{{ sub.tenant?.id }}</p>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="px-2 py-1 bg-indigo-100 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-400 rounded text-sm font-medium">
                                        {{ sub.plan?.name_ar }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-gray-600 dark:text-gray-400">
                                    {{ sub.billing_cycle === 'yearly' ? 'سنوي' : 'شهري' }}
                                </td>
                                <td class="px-6 py-4">
                                    <span :class="getStatusClass(sub.status)" class="px-2 py-1 rounded-full text-xs font-medium">
                                        {{ getStatusLabel(sub.status) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-gray-600 dark:text-gray-400">
                                    {{ formatDate(sub.ends_at) }}
                                </td>
                                <td class="px-6 py-4 text-end">
                                    <Link :href="`/system/subscriptions/${sub.id}`" class="text-indigo-600 hover:text-indigo-700 font-medium text-sm">
                                        عرض
                                    </Link>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <!-- Empty State -->
                <div v-if="!subscriptions.data?.length" class="px-6 py-12 text-center">
                    <svg class="w-12 h-12 mx-auto text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                    <p class="mt-4 text-gray-500 dark:text-gray-400">لا توجد اشتراكات</p>
                </div>
                
                <!-- Pagination -->
                <div v-if="subscriptions.links?.length > 3" class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 flex justify-center gap-2">
                    <Link 
                        v-for="link in subscriptions.links" 
                        :key="link.label"
                        :href="link.url || '#'"
                        :class="[
                            'px-3 py-1 rounded text-sm',
                            link.active ? 'bg-indigo-600 text-white' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700',
                            !link.url && 'opacity-50 cursor-not-allowed'
                        ]"
                        v-html="link.label"
                    />
                </div>
            </div>
            
            <!-- Create Modal -->
            <Transition name="modal">
                <div v-if="showModal" class="fixed inset-0 z-50 overflow-y-auto">
                    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
                        <!-- Backdrop -->
                        <div class="fixed inset-0 bg-black/50 transition-opacity" @click="closeModal"></div>
                        
                        <!-- Modal Panel -->
                        <div class="relative bg-white dark:bg-gray-800 rounded-2xl text-start overflow-hidden shadow-xl transform transition-all sm:my-8 sm:max-w-2xl sm:w-full">
                            <!-- Header -->
                            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">إنشاء اشتراك جديد</h3>
                                <button @click="closeModal" class="text-gray-400 hover:text-gray-500">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                </button>
                            </div>
                            
                            <!-- Form Body -->
                            <form @submit.prevent="submit" class="p-6 space-y-5 max-h-[70vh] overflow-y-auto">
                                <!-- Tenant Selection with Search -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">المستأجر</label>
                                    <div class="relative">
                                        <input 
                                            v-model="tenantSearch" 
                                            type="text" 
                                            placeholder="ابحث عن اسم المركز أو المالك..."
                                            class="w-full px-4 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                                            @focus="showTenantDropdown = true"
                                        />
                                        <div v-if="showTenantDropdown && filteredTenants.length > 0" class="absolute z-10 w-full mt-1 bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg shadow-lg max-h-60 overflow-y-auto">
                                            <div 
                                                v-for="tenant in filteredTenants" 
                                                :key="tenant.id"
                                                @click="selectTenant(tenant)"
                                                class="px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-600 cursor-pointer flex justify-between items-center"
                                            >
                                                <div>
                                                    <p class="font-medium text-gray-900 dark:text-white">{{ tenant.trade_name || tenant.name }}</p>
                                                    <p class="text-xs text-gray-500 dark:text-gray-400">#{{ tenant.id }}</p>
                                                </div>
                                                <svg v-if="form.tenant_id === tenant.id" class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                                </svg>
                                            </div>
                                        </div>
                                    </div>
                                    <p v-if="form.tenant_id" class="mt-1 text-sm text-emerald-600 flex items-center gap-1">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                        تم اختيار: {{ getSelectedTenantName() }}
                                    </p>
                                </div>
                                
                                <!-- Plan Selection -->
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">الباقة</label>
                                        <select v-model="form.plan_id" required class="w-full px-4 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                                            <option v-for="plan in plans" :key="plan.id" :value="plan.id">{{ plan.name_ar }}</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">دورة الفوترة</label>
                                        <select v-model="form.billing_cycle" required class="w-full px-4 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                                            <option value="monthly">شهري</option>
                                            <option value="yearly">سنوي</option>
                                        </select>
                                    </div>
                                </div>
                                
                                <!-- Price Preview -->
                                <div v-if="selectedPlan" class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4 border border-gray-200 dark:border-gray-600 flex justify-between items-center">
                                    <span class="text-gray-600 dark:text-gray-300 font-medium">سعر الاشتراك</span>
                                    <span class="text-xl font-bold text-gray-900 dark:text-white">
                                        {{ form.billing_cycle === 'yearly' ? selectedPlan.price_yearly : selectedPlan.price_monthly }} ر.س
                                    </span>
                                </div>
                                
                                <!-- Promo Code -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">رمز ترويجي (اختياري)</label>
                                    <input v-model="form.promo_code" type="text" dir="ltr" placeholder="CODE" class="w-full px-4 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white font-mono uppercase" />
                                </div>
                            </form>
                            
                            <!-- Footer -->
                            <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 flex justify-end gap-3">
                                <button @click="closeModal" type="button" class="px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg">
                                    إلغاء
                                </button>
                                <button @click="submit" :disabled="form.processing || !form.tenant_id" class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg font-medium disabled:opacity-50">
                                    {{ form.processing ? 'جاري الإنشاء...' : 'إنشاء الاشتراك' }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </Transition>
        </div>
    </SystemLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Link, router, useForm } from '@inertiajs/vue3';
import SystemLayout from '@/Layouts/SystemLayout.vue';

const props = defineProps({
    subscriptions: Object,
    plans: Array,
    filters: Object,
    stats: Object,
    eligibleTenants: Array,
});

const search = ref(props.filters?.search || '');
const statusFilter = ref(props.filters?.status || 'all');
const planFilter = ref(props.filters?.plan_id || '');

// Modal Logic
const showModal = ref(false);
const tenantSearch = ref('');
const showTenantDropdown = ref(false);
const form = useForm({
    tenant_id: '',
    plan_id: '',
    billing_cycle: 'monthly',
    promo_code: '',
});

const openModal = () => {
    form.reset();
    tenantSearch.value = '';
    showTenantDropdown.value = false;
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
};

// Filter Tenants
const filteredTenants = computed(() => {
    if (!tenantSearch.value) return props.eligibleTenants || [];
    const query = tenantSearch.value.toLowerCase();
    return (props.eligibleTenants || []).filter(tenant => 
        (tenant.name && tenant.name.toLowerCase().includes(query)) ||
        (tenant.trade_name && tenant.trade_name.toLowerCase().includes(query)) ||
        tenant.id.toString().includes(query)
    );
});

const selectTenant = (tenant) => {
    form.tenant_id = tenant.id;
    tenantSearch.value = tenant.trade_name || tenant.name;
    showTenantDropdown.value = false;
};

const getSelectedTenantName = () => {
    const tenant = (props.eligibleTenants || []).find(t => t.id === form.tenant_id);
    return tenant ? (tenant.trade_name || tenant.name) : '';
};

// Plan Logic
const selectedPlan = computed(() => {
    return props.plans.find(p => p.id === form.plan_id);
});

const submit = () => {
    form.post('/system/subscriptions', {
        onSuccess: () => closeModal(),
    });
};

const applyFilters = () => {
    router.get('/system/subscriptions', {
        search: search.value || undefined,
        status: statusFilter.value !== 'all' ? statusFilter.value : undefined,
        plan_id: planFilter.value || undefined,
    }, { preserveState: true });
};

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
        pending: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
    };
    return classes[status] || classes.expired;
};

const getStatusLabel = (status) => {
    const labels = {
        active: 'نشط',
        trial: 'تجريبي',
        cancelled: 'ملغي',
        expired: 'منتهي',
        pending: 'قيد الانتظار',
    };
    return labels[status] || status;
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
