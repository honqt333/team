<template>
    <SystemLayout>
        <div class="space-y-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">أرصدة WhatsApp</h1>
                    <p class="text-gray-500 dark:text-gray-400">رصيد رسائل واتساب لكل مستأجر</p>
                </div>
                <button @click="showAddModal = true" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-sm font-medium">+ إضافة رصيد</button>
            </div>
            
            <div class="flex gap-2 border-b border-gray-200 dark:border-gray-700">
                <a href="/system/whatsapp/packages" class="px-4 py-2 border-b-2 border-transparent text-gray-500 hover:text-gray-700">الباقات</a>
                <a href="/system/whatsapp/balances" class="px-4 py-2 border-b-2 border-emerald-600 text-emerald-600 font-medium">الأرصدة</a>
                <a href="/system/whatsapp/usage" class="px-4 py-2 border-b-2 border-transparent text-gray-500 hover:text-gray-700">سجل الاستخدام</a>
            </div>
            
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700">
                    <p class="text-2xl font-bold text-emerald-600">{{ stats.total_purchased?.toLocaleString() }}</p>
                    <p class="text-sm text-gray-500">إجمالي المشتريات</p>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700">
                    <p class="text-2xl font-bold text-amber-600">{{ stats.total_used?.toLocaleString() }}</p>
                    <p class="text-sm text-gray-500">المستخدم</p>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700">
                    <p class="text-2xl font-bold text-blue-600">{{ stats.total_balance?.toLocaleString() }}</p>
                    <p class="text-sm text-gray-500">الرصيد الحالي</p>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700">
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ formatCurrency(stats.total_revenue) }}</p>
                    <p class="text-sm text-gray-500">الإيرادات</p>
                </div>
            </div>
            
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                <table class="w-full">
                    <thead class="bg-gray-50 dark:bg-gray-700/50">
                        <tr>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">المستأجر</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">الرصيد الحالي</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">إجمالي المشتريات</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">المستخدم</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500">الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        <tr v-for="item in balances.data" :key="item.id" class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                            <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">{{ item.tenant?.trade_name }}</td>
                            <td class="px-4 py-3"><span :class="item.balance > 0 ? 'text-emerald-600' : 'text-red-500'" class="font-semibold">{{ item.balance?.toLocaleString() }}</span></td>
                            <td class="px-4 py-3 text-gray-600 dark:text-gray-400">{{ item.total_purchased?.toLocaleString() }}</td>
                            <td class="px-4 py-3 text-gray-600 dark:text-gray-400">{{ item.total_used?.toLocaleString() }}</td>
                            <td class="px-4 py-3"><button @click="openAddModal(item)" class="text-emerald-600 hover:text-emerald-800 text-sm">+ إضافة</button></td>
                        </tr>
                        <tr v-if="!balances.data?.length"><td colspan="5" class="px-4 py-8 text-center text-gray-500">لا توجد بيانات</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
        
        <Transition name="modal">
            <div v-if="showAddModal" class="fixed inset-0 z-50 overflow-y-auto">
                <div class="flex min-h-full items-center justify-center p-4">
                    <div class="fixed inset-0 bg-black/50" @click="showAddModal = false"></div>
                    <div class="relative bg-white dark:bg-gray-800 rounded-2xl p-6 w-full max-w-md shadow-xl">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">إضافة رصيد WhatsApp</h3>
                        <form @submit.prevent="addCredits" class="space-y-4">
                            <div v-if="selectedTenant" class="bg-emerald-50 dark:bg-emerald-900/20 rounded-lg p-3">
                                <p class="text-sm text-gray-600 dark:text-gray-400">المستأجر</p>
                                <p class="font-semibold text-gray-900 dark:text-white">{{ selectedTenant.tenant?.trade_name }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">عدد الرسائل</label>
                                <input type="number" v-model="form.credits" required min="1" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">السبب</label>
                                <input type="text" v-model="form.reason" placeholder="اختياري" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white" />
                            </div>
                            <div class="flex gap-3 pt-4">
                                <button type="submit" :disabled="form.processing" class="flex-1 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg font-medium disabled:opacity-50">إضافة</button>
                                <button type="button" @click="showAddModal = false" class="px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg">إلغاء</button>
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
import { useForm } from '@inertiajs/vue3';
import SystemLayout from '@/Layouts/SystemLayout.vue';

const props = defineProps({ balances: Object, stats: Object, filters: Object });
const showAddModal = ref(false);
const selectedTenant = ref(null);
const form = useForm({ tenant_id: '', credits: 100, reason: '' });

const formatCurrency = (v) => new Intl.NumberFormat('ar-SA', { style: 'currency', currency: 'SAR' }).format(v || 0);

const openAddModal = (item) => {
    selectedTenant.value = item;
    form.tenant_id = item.tenant_id;
    showAddModal.value = true;
};

const addCredits = () => {
    form.post('/system/whatsapp/add-credits', { onSuccess: () => { showAddModal.value = false; selectedTenant.value = null; form.reset(); } });
};
</script>

<style scoped>
.modal-enter-active, .modal-leave-active { transition: opacity 0.2s ease; }
.modal-enter-from, .modal-leave-to { opacity: 0; }
</style>
