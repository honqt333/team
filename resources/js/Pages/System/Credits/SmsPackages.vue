<template>
    <SystemLayout>
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">باقات رصيد SMS</h1>
                    <p class="text-gray-500 dark:text-gray-400">إدارة باقات الرسائل النصية المتاحة للشراء</p>
                </div>
                <button @click="showAddModal = true" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-medium">
                    + إضافة باقة
                </button>
            </div>
            
            <!-- Navigation Tabs -->
            <div class="flex gap-2 border-b border-gray-200 dark:border-gray-700">
                <a href="/system/sms/packages" class="px-4 py-2 border-b-2 border-indigo-600 text-indigo-600 font-medium">الباقات</a>
                <a href="/system/sms/balances" class="px-4 py-2 border-b-2 border-transparent text-gray-500 hover:text-gray-700">الأرصدة</a>
                <a href="/system/sms/purchases" class="px-4 py-2 border-b-2 border-transparent text-gray-500 hover:text-gray-700">المشتريات</a>
                <a href="/system/sms/usage" class="px-4 py-2 border-b-2 border-transparent text-gray-500 hover:text-gray-700">سجل الاستخدام</a>
            </div>
            
            <!-- Packages Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <div 
                    v-for="pkg in packages" 
                    :key="pkg.id"
                    :class="[
                        'bg-white dark:bg-gray-800 rounded-xl p-6 border-2 transition-all',
                        pkg.is_popular ? 'border-indigo-500 ring-2 ring-indigo-200 dark:ring-indigo-800' : 'border-gray-200 dark:border-gray-700'
                    ]"
                >
                    <div v-if="pkg.is_popular" class="absolute -top-3 right-4 bg-indigo-500 text-white text-xs px-2 py-1 rounded-full">
                        الأكثر مبيعاً
                    </div>
                    
                    <div class="flex items-start justify-between mb-4">
                        <div>
                            <h3 class="text-lg font-bold text-gray-900 dark:text-white">{{ pkg.name_ar }}</h3>
                            <p class="text-sm text-gray-500">{{ pkg.name }}</p>
                        </div>
                        <span :class="pkg.is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-100 text-gray-500'" class="px-2 py-1 rounded-full text-xs">
                            {{ pkg.is_active ? 'مفعّل' : 'معطّل' }}
                        </span>
                    </div>
                    
                    <div class="text-center mb-4">
                        <span class="text-4xl font-bold text-indigo-600">{{ pkg.credits.toLocaleString() }}</span>
                        <span class="text-gray-500 mr-1">رسالة</span>
                    </div>
                    
                    <div class="text-center mb-4">
                        <span class="text-2xl font-bold text-gray-900 dark:text-white">{{ pkg.price }} ر.س</span>
                        <p class="text-xs text-gray-500">{{ (pkg.price / pkg.credits).toFixed(3) }} ر.س / رسالة</p>
                    </div>
                    
                    <div class="flex gap-2">
                        <button @click="editPackage(pkg)" class="flex-1 px-3 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg text-sm hover:bg-gray-200">
                            تعديل
                        </button>
                        <button @click="deletePackage(pkg)" class="px-3 py-2 text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg text-sm">
                            حذف
                        </button>
                    </div>
                </div>
                
                <!-- Empty State -->
                <div v-if="!packages.length" class="col-span-full text-center py-12 text-gray-500">
                    لا توجد باقات. اضغط على "إضافة باقة" لإنشاء أول باقة.
                </div>
            </div>
        </div>
        
        <!-- Add/Edit Modal -->
        <Transition name="modal">
            <div v-if="showAddModal" class="fixed inset-0 z-50 overflow-y-auto">
                <div class="flex min-h-full items-center justify-center p-4">
                    <div class="fixed inset-0 bg-black/50" @click="showAddModal = false"></div>
                    <div class="relative bg-white dark:bg-gray-800 rounded-2xl p-6 w-full max-w-md shadow-xl">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                            {{ editingPackage ? 'تعديل الباقة' : 'إضافة باقة جديدة' }}
                        </h3>
                        
                        <form @submit.prevent="submitPackage" class="space-y-4">
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">الاسم (عربي)</label>
                                    <input type="text" v-model="form.name_ar" required class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white" />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">الاسم (إنجليزي)</label>
                                    <input type="text" v-model="form.name" required class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white" dir="ltr" />
                                </div>
                            </div>
                            
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">عدد الرسائل</label>
                                    <input type="number" v-model="form.credits" required min="1" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white" />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">السعر (ر.س)</label>
                                    <input type="number" v-model="form.price" required min="0" step="0.01" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white" />
                                </div>
                            </div>
                            
                            <div class="flex gap-4">
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox" v-model="form.is_active" class="rounded border-gray-300 text-indigo-600" />
                                    <span class="text-sm text-gray-700 dark:text-gray-300">مفعّل</span>
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox" v-model="form.is_popular" class="rounded border-gray-300 text-indigo-600" />
                                    <span class="text-sm text-gray-700 dark:text-gray-300">الأكثر مبيعاً</span>
                                </label>
                            </div>
                            
                            <div class="flex gap-3 pt-4">
                                <button type="submit" :disabled="form.processing" class="flex-1 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg font-medium disabled:opacity-50">
                                    {{ form.processing ? 'جاري الحفظ...' : 'حفظ' }}
                                </button>
                                <button type="button" @click="showAddModal = false" class="px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg">
                                    إلغاء
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </Transition>
    </SystemLayout>
</template>

<script setup>
import { ref } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import SystemLayout from '@/Layouts/SystemLayout.vue';

const props = defineProps({
    packages: Array,
});

const showAddModal = ref(false);
const editingPackage = ref(null);

const form = useForm({
    name: '',
    name_ar: '',
    credits: 100,
    price: 50,
    is_active: true,
    is_popular: false,
    sort_order: 0,
});

const editPackage = (pkg) => {
    editingPackage.value = pkg;
    form.name = pkg.name;
    form.name_ar = pkg.name_ar;
    form.credits = pkg.credits;
    form.price = pkg.price;
    form.is_active = pkg.is_active;
    form.is_popular = pkg.is_popular;
    showAddModal.value = true;
};

const submitPackage = () => {
    if (editingPackage.value) {
        form.put(`/system/sms/packages/${editingPackage.value.id}`, {
            onSuccess: () => {
                showAddModal.value = false;
                editingPackage.value = null;
                form.reset();
            },
        });
    } else {
        form.post('/system/sms/packages', {
            onSuccess: () => {
                showAddModal.value = false;
                form.reset();
            },
        });
    }
};

const deletePackage = (pkg) => {
    if (confirm(`هل تريد حذف باقة "${pkg.name_ar}"؟`)) {
        router.delete(`/system/sms/packages/${pkg.id}`);
    }
};
</script>

<style scoped>
.modal-enter-active, .modal-leave-active { transition: opacity 0.2s ease; }
.modal-enter-from, .modal-leave-to { opacity: 0; }
</style>
