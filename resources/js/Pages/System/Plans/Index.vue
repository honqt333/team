<template>
    <SystemLayout>
        <div class="space-y-6">
            <!-- Page Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">الباقات</h1>
                    <p class="text-gray-500 dark:text-gray-400">إدارة باقات الاشتراك</p>
                </div>
                <button @click="openModal()" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-medium transition-colors">
                    + إضافة باقة
                </button>
            </div>
            
            <!-- Plans Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <div 
                    v-for="plan in plans" 
                    :key="plan.id"
                    :class="[
                        'bg-white dark:bg-gray-800 rounded-2xl shadow-sm border overflow-hidden relative',
                        plan.is_featured ? 'border-indigo-500 ring-2 ring-indigo-500' : 'border-gray-200 dark:border-gray-700'
                    ]"
                >
                    <!-- Featured Badge -->
                    <div v-if="plan.is_featured" class="absolute top-4 left-4 px-2 py-1 bg-indigo-600 text-white text-xs font-medium rounded-full">
                        مميزة
                    </div>
                    
                    <!-- Status Badge -->
                    <div class="absolute top-4 right-4">
                        <span :class="plan.is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-100 text-gray-500'" class="px-2 py-1 rounded-full text-xs">
                            {{ plan.is_active ? 'نشطة' : 'معطلة' }}
                        </span>
                    </div>
                    
                    <div class="p-6 pt-12">
                        <!-- Plan Name -->
                        <h3 class="text-xl font-bold text-gray-900 dark:text-white">{{ plan.name_ar }}</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ plan.slug }}</p>
                        
                        <!-- Pricing -->
                        <div class="mt-6">
                            <div class="flex items-baseline gap-2">
                                <span class="text-3xl font-bold text-gray-900 dark:text-white">{{ plan.price_monthly }}</span>
                                <span class="text-gray-500">ر.س/شهر</span>
                            </div>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                                أو {{ plan.price_yearly }} ر.س/سنة
                                <span v-if="plan.yearly_discount > 0" class="text-emerald-600">({{ plan.yearly_discount }}% خصم)</span>
                            </p>
                        </div>
                        
                        <!-- Limits -->
                        <div class="mt-6 space-y-2 text-sm text-gray-600 dark:text-gray-400">
                            <div v-if="plan.limits?.max_users" class="flex items-center gap-2">
                                <svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                {{ plan.limits.max_users }} مستخدمين
                            </div>
                            <div v-if="plan.limits?.max_centers" class="flex items-center gap-2">
                                <svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                {{ plan.limits.max_centers }} مراكز
                            </div>
                            <div v-if="plan.limits?.storage_gb" class="flex items-center gap-2">
                                <svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                {{ plan.limits.storage_gb }} GB مساحة
                            </div>
                        </div>
                        
                        <!-- Trial -->
                        <p class="mt-4 text-sm text-gray-500 dark:text-gray-400">
                            فترة تجريبية: {{ plan.trial_days }} يوم
                        </p>
                        
                        <!-- Actions -->
                        <div class="mt-6 flex gap-2">
                            <button @click="openModal(plan)" class="flex-1 text-center px-4 py-2 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-lg text-sm font-medium">
                                تعديل
                            </button>
                            <button @click="deletePlan(plan)" class="px-4 py-2 text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg text-sm">
                                حذف
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Empty State -->
                <div v-if="!plans?.length" class="col-span-full text-center py-12 bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700">
                    <svg class="w-12 h-12 mx-auto text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                    </svg>
                    <p class="mt-4 text-gray-500 dark:text-gray-400">لا توجد باقات</p>
                    <button @click="openModal()" class="mt-4 inline-block text-indigo-600 hover:text-indigo-700">
                        إنشاء أول باقة
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Plan Form Modal -->
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
                                {{ editingPlan ? 'تعديل الباقة' : 'إضافة باقة جديدة' }}
                            </h3>
                            <button @click="closeModal" class="text-gray-400 hover:text-gray-500">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>
                        
                        <!-- Form Body -->
                        <form @submit.prevent="submit" class="p-6 space-y-5 max-h-[70vh] overflow-y-auto">
                            <!-- Basic Info -->
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">اسم الباقة (عربي)</label>
                                    <input v-model="form.name_ar" type="text" required class="w-full px-4 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white" />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">اسم الباقة (إنجليزي)</label>
                                    <input v-model="form.name_en" type="text" required dir="ltr" class="w-full px-4 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white" />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">المعرف (Slug)</label>
                                    <input v-model="form.slug" type="text" required dir="ltr" placeholder="basic, pro" class="w-full px-4 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white" />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">الترتيب</label>
                                    <input v-model.number="form.sort_order" type="number" min="0" class="w-full px-4 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white" />
                                </div>
                            </div>
                            
                            <!-- Pricing -->
                            <div>
                                <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-3">التسعير</h4>
                                <div class="grid grid-cols-3 gap-4">
                                    <div>
                                        <label class="block text-sm text-gray-600 dark:text-gray-400 mb-1">شهري (ر.س)</label>
                                        <input v-model.number="form.price_monthly" type="number" min="0" step="0.01" required class="w-full px-4 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white" />
                                    </div>
                                    <div>
                                        <label class="block text-sm text-gray-600 dark:text-gray-400 mb-1">سنوي (ر.س)</label>
                                        <input v-model.number="form.price_yearly" type="number" min="0" step="0.01" required class="w-full px-4 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white" />
                                    </div>
                                    <div>
                                        <label class="block text-sm text-gray-600 dark:text-gray-400 mb-1">أيام التجربة</label>
                                        <input v-model.number="form.trial_days" type="number" min="0" required class="w-full px-4 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white" />
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Limits -->
                            <div>
                                <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-3">الحدود</h4>
                                <div class="grid grid-cols-3 gap-4">
                                    <div>
                                        <label class="block text-sm text-gray-600 dark:text-gray-400 mb-1">المستخدمين</label>
                                        <input v-model.number="form.limits.max_users" type="number" min="1" class="w-full px-4 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white" />
                                    </div>
                                    <div>
                                        <label class="block text-sm text-gray-600 dark:text-gray-400 mb-1">المراكز</label>
                                        <input v-model.number="form.limits.max_centers" type="number" min="1" class="w-full px-4 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white" />
                                    </div>
                                    <div>
                                        <label class="block text-sm text-gray-600 dark:text-gray-400 mb-1">التخزين (GB)</label>
                                        <input v-model.number="form.limits.storage_gb" type="number" min="1" class="w-full px-4 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white" />
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Status -->
                            <div class="flex gap-6">
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input v-model="form.is_active" type="checkbox" class="w-5 h-5 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" />
                                    <span class="text-gray-700 dark:text-gray-300">نشطة</span>
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input v-model="form.is_featured" type="checkbox" class="w-5 h-5 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" />
                                    <span class="text-gray-700 dark:text-gray-300">مميزة</span>
                                </label>
                            </div>
                        </form>
                        
                        <!-- Footer -->
                        <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 flex justify-end gap-3">
                            <button @click="closeModal" type="button" class="px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg">
                                إلغاء
                            </button>
                            <button @click="submit" :disabled="form.processing" class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg font-medium disabled:opacity-50">
                                {{ editingPlan ? 'حفظ التغييرات' : 'إنشاء الباقة' }}
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
import { router, useForm } from '@inertiajs/vue3';
import SystemLayout from '@/Layouts/SystemLayout.vue';

defineProps({
    plans: Array,
});

const showModal = ref(false);
const editingPlan = ref(null);

const form = useForm({
    name_ar: '',
    name_en: '',
    slug: '',
    description_ar: '',
    description_en: '',
    price_monthly: 0,
    price_yearly: 0,
    trial_days: 14,
    limits: {
        max_users: 5,
        max_centers: 1,
        storage_gb: 10,
    },
    features: [],
    is_active: true,
    is_featured: false,
    sort_order: 0,
});

const openModal = (plan = null) => {
    editingPlan.value = plan;
    if (plan) {
        form.name_ar = plan.name_ar || '';
        form.name_en = plan.name_en || '';
        form.slug = plan.slug || '';
        form.description_ar = plan.description_ar || '';
        form.description_en = plan.description_en || '';
        form.price_monthly = plan.price_monthly || 0;
        form.price_yearly = plan.price_yearly || 0;
        form.trial_days = plan.trial_days || 14;
        form.limits = {
            max_users: plan.limits?.max_users || 5,
            max_centers: plan.limits?.max_centers || 1,
            storage_gb: plan.limits?.storage_gb || 10,
        };
        form.features = plan.features || [];
        form.is_active = plan.is_active ?? true;
        form.is_featured = plan.is_featured ?? false;
        form.sort_order = plan.sort_order || 0;
    } else {
        form.reset();
        form.limits = { max_users: 5, max_centers: 1, storage_gb: 10 };
        form.is_active = true;
        form.trial_days = 14;
    }
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
    editingPlan.value = null;
};

const submit = () => {
    if (editingPlan.value) {
        form.put(`/system/plans/${editingPlan.value.id}`, {
            onSuccess: () => closeModal(),
        });
    } else {
        form.post('/system/plans', {
            onSuccess: () => closeModal(),
        });
    }
};

const deletePlan = (plan) => {
    if (confirm(`هل تريد حذف باقة "${plan.name_ar}"؟`)) {
        router.delete(`/system/plans/${plan.id}`);
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
