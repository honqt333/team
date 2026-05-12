<template>
    <SystemLayout>
        <div class="space-y-6">
            <!-- Page Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">الرموز الترويجية</h1>
                    <p class="text-gray-500 dark:text-gray-400">إدارة أكواد الخصم والعروض</p>
                </div>
                <button @click="openModal()" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-medium transition-colors">
                    + إنشاء رمز
                </button>
            </div>
            
            <!-- Promo Codes Table -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50 dark:bg-gray-700/50">
                            <tr>
                                <th class="px-6 py-4 text-start text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">الرمز</th>
                                <th class="px-6 py-4 text-start text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">الاسم</th>
                                <th class="px-6 py-4 text-start text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">الخصم</th>
                                <th class="px-6 py-4 text-start text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">الاستخدام</th>
                                <th class="px-6 py-4 text-start text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">الحالة</th>
                                <th class="px-6 py-4"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            <tr v-for="code in promoCodes" :key="code.id" class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                                <td class="px-6 py-4">
                                    <span class="font-mono font-bold text-indigo-600 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-900/30 px-2 py-1 rounded">
                                        {{ code.code }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-gray-900 dark:text-white">{{ code.name }}</td>
                                <td class="px-6 py-4 text-gray-900 dark:text-white">{{ code.discount_description }}</td>
                                <td class="px-6 py-4 text-gray-600 dark:text-gray-400">
                                    {{ code.times_used }} / {{ code.max_uses || '∞' }}
                                </td>
                                <td class="px-6 py-4">
                                    <span v-if="code.is_valid" class="px-2 py-1 bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400 rounded-full text-xs">
                                        صالح
                                    </span>
                                    <span v-else class="px-2 py-1 bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-400 rounded-full text-xs">
                                        {{ !code.is_active ? 'غير نشط' : 'منتهي' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-end">
                                    <button @click="openModal(code)" class="text-indigo-600 hover:text-indigo-700 font-medium text-sm me-3">
                                        تعديل
                                    </button>
                                    <button @click="deleteCode(code)" class="text-red-600 hover:text-red-700 text-sm">
                                        حذف
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <!-- Empty State -->
                <div v-if="!promoCodes?.length" class="px-6 py-12 text-center">
                    <svg class="w-12 h-12 mx-auto text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                    </svg>
                    <p class="mt-4 text-gray-500 dark:text-gray-400">لا توجد رموز ترويجية</p>
                    <button @click="openModal()" class="mt-4 inline-block text-indigo-600 hover:text-indigo-700">
                        إنشاء أول رمز
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Promo Code Form Modal -->
        <Transition name="modal">
            <div v-if="showModal" class="fixed inset-0 z-50 overflow-y-auto">
                <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
                    <!-- Backdrop -->
                    <div class="fixed inset-0 bg-black/50 transition-opacity" @click="closeModal"></div>
                    
                    <!-- Modal Panel -->
                    <div class="relative bg-white dark:bg-gray-800 rounded-2xl text-start overflow-hidden shadow-xl transform transition-all sm:my-8 sm:max-w-2xl sm:w-full">
                        <!-- Header -->
                        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                                {{ editingCode ? 'تعديل الرمز' : 'إنشاء رمز ترويجي' }}
                            </h3>
                            <button @click="closeModal" class="text-gray-400 hover:text-gray-500">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>
                        
                        <!-- Form Body -->
                        <form @submit.prevent="submit" class="p-6 space-y-5 max-h-[70vh] overflow-y-auto">
                            <!-- Code & Name -->
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">الرمز</label>
                                    <div class="flex gap-2">
                                        <input v-model="form.code" type="text" required dir="ltr" class="flex-1 px-4 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white font-mono uppercase" />
                                        <button v-if="!editingCode" type="button" @click="generateCode" class="px-3 py-2 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-lg text-gray-700 dark:text-gray-300 text-sm">
                                            🎲
                                        </button>
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">اسم العرض</label>
                                    <input v-model="form.name" type="text" required class="w-full px-4 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white" />
                                </div>
                            </div>
                            
                            <!-- Discount -->
                            <div>
                                <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-3">الخصم</h4>
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm text-gray-600 dark:text-gray-400 mb-1">نوع الخصم</label>
                                        <select v-model="form.discount_type" class="w-full px-4 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                                            <option value="percentage">نسبة مئوية (%)</option>
                                            <option value="fixed">مبلغ ثابت (ر.س)</option>
                                            <option value="trial_days">أيام تجربة إضافية</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-sm text-gray-600 dark:text-gray-400 mb-1">{{ discountLabel }}</label>
                                        <input v-model.number="form.discount_value" type="number" min="0" :max="form.discount_type === 'percentage' ? 100 : 9999" step="0.01" required class="w-full px-4 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white" />
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Limits -->
                            <div>
                                <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-3">الحدود</h4>
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm text-gray-600 dark:text-gray-400 mb-1">الحد الأقصى للاستخدام</label>
                                        <input v-model.number="form.max_uses" type="number" min="1" placeholder="غير محدود" class="w-full px-4 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white" />
                                    </div>
                                    <div>
                                        <label class="block text-sm text-gray-600 dark:text-gray-400 mb-1">الحد لكل مستأجر</label>
                                        <input v-model.number="form.max_uses_per_tenant" type="number" min="1" required class="w-full px-4 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white" />
                                    </div>
                                    <div>
                                        <label class="block text-sm text-gray-600 dark:text-gray-400 mb-1">يبدأ من</label>
                                        <input v-model="form.starts_at" type="datetime-local" class="w-full px-4 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white" />
                                    </div>
                                    <div>
                                        <label class="block text-sm text-gray-600 dark:text-gray-400 mb-1">ينتهي في</label>
                                        <input v-model="form.expires_at" type="datetime-local" class="w-full px-4 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white" />
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Restrictions -->
                            <div>
                                <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-3">القيود</h4>
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm text-gray-600 dark:text-gray-400 mb-1">الباقة</label>
                                        <select v-model="form.plan_id" class="w-full px-4 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                                            <option :value="null">جميع الباقات</option>
                                            <option v-for="plan in plans" :key="plan.id" :value="plan.id">{{ plan.name_ar }}</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-sm text-gray-600 dark:text-gray-400 mb-1">دورة الفوترة</label>
                                        <select v-model="form.billing_cycle" class="w-full px-4 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                                            <option value="any">الكل</option>
                                            <option value="monthly">شهري فقط</option>
                                            <option value="yearly">سنوي فقط</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Status -->
                            <div class="flex gap-6">
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input v-model="form.first_subscription_only" type="checkbox" class="w-5 h-5 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" />
                                    <span class="text-gray-700 dark:text-gray-300">للاشتراك الأول فقط</span>
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input v-model="form.is_active" type="checkbox" class="w-5 h-5 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" />
                                    <span class="text-gray-700 dark:text-gray-300">نشط</span>
                                </label>
                            </div>
                        </form>
                        
                        <!-- Footer -->
                        <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 flex justify-end gap-3">
                            <button @click="closeModal" type="button" class="px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg">
                                إلغاء
                            </button>
                            <button @click="submit" :disabled="form.processing" class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg font-medium disabled:opacity-50">
                                {{ editingCode ? 'حفظ التغييرات' : 'إنشاء الرمز' }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </Transition>
    </SystemLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import SystemLayout from '@/Layouts/SystemLayout.vue';

const props = defineProps({
    promoCodes: Array,
    plans: Array,
});

const showModal = ref(false);
const editingCode = ref(null);

const form = useForm({
    code: '',
    name: '',
    description: '',
    discount_type: 'percentage',
    discount_value: 10,
    max_uses: null,
    max_uses_per_tenant: 1,
    starts_at: '',
    expires_at: '',
    plan_id: null,
    billing_cycle: 'any',
    first_subscription_only: true,
    is_active: true,
});

const discountLabel = computed(() => {
    return {
        percentage: 'نسبة الخصم (%)',
        fixed: 'مبلغ الخصم (ر.س)',
        trial_days: 'أيام التجربة الإضافية',
    }[form.discount_type];
});

const openModal = (code = null) => {
    editingCode.value = code;
    if (code) {
        form.code = code.code || '';
        form.name = code.name || '';
        form.description = code.description || '';
        form.discount_type = code.discount_type || 'percentage';
        form.discount_value = code.discount_value || 10;
        form.max_uses = code.max_uses || null;
        form.max_uses_per_tenant = code.max_uses_per_tenant || 1;
        form.starts_at = code.starts_at || '';
        form.expires_at = code.expires_at || '';
        form.plan_id = code.plan_id || null;
        form.billing_cycle = code.billing_cycle || 'any';
        form.first_subscription_only = code.first_subscription_only ?? true;
        form.is_active = code.is_active ?? true;
    } else {
        form.reset();
        form.discount_type = 'percentage';
        form.discount_value = 10;
        form.max_uses_per_tenant = 1;
        form.first_subscription_only = true;
        form.is_active = true;
        generateCode();
    }
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
    editingCode.value = null;
};

const generateCode = () => {
    const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    let code = '';
    for (let i = 0; i < 8; i++) {
        code += chars.charAt(Math.floor(Math.random() * chars.length));
    }
    form.code = code;
};

const submit = () => {
    if (editingCode.value) {
        form.put(`/system/promo-codes/${editingCode.value.id}`, {
            onSuccess: () => closeModal(),
        });
    } else {
        form.post('/system/promo-codes', {
            onSuccess: () => closeModal(),
        });
    }
};

const deleteCode = (code) => {
    if (confirm(`حذف الرمز "${code.code}"؟`)) {
        router.delete(`/system/promo-codes/${code.id}`);
    }
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
