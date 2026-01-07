<template>
    <SystemLayout>
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">التكاملات</h1>
                    <p class="text-gray-500 dark:text-gray-400">إدارة مزودي الرسائل والبريد الإلكتروني</p>
                </div>
            </div>
            
            <!-- SMS Section -->
            <div class="bg-white dark:bg-gray-800 rounded-xl p-6 border border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                        <span class="text-2xl">📱</span>
                        رسائل SMS
                    </h2>
                    <button @click="openAddModal('sms')" class="text-sm text-indigo-600 hover:text-indigo-800">+ إضافة مزود</button>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div v-for="item in integrations.sms" :key="item.id" class="border border-gray-200 dark:border-gray-700 rounded-lg p-4 hover:border-indigo-300 transition-colors">
                        <div class="flex items-center justify-between mb-2">
                            <span class="font-medium text-gray-900 dark:text-white">{{ item.name_ar }}</span>
                            <span :class="item.is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-100 text-gray-500'" class="px-2 py-0.5 rounded-full text-xs">
                                {{ item.is_active ? 'مفعّل' : 'معطّل' }}
                            </span>
                        </div>
                        <p class="text-xs text-gray-500 mb-3">{{ item.name }}</p>
                        <div class="flex gap-2">
                            <a :href="`/system/integrations/${item.id}`" class="text-sm text-indigo-600 hover:underline">إعدادات</a>
                            <span v-if="item.is_default" class="text-xs text-amber-600">⭐ افتراضي</span>
                        </div>
                    </div>
                    <div v-if="!integrations.sms?.length" class="text-gray-500 text-sm">لم يتم إضافة مزودين</div>
                </div>
            </div>
            
            <!-- WhatsApp Section -->
            <div class="bg-white dark:bg-gray-800 rounded-xl p-6 border border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                        <span class="text-2xl">💬</span>
                        واتساب
                    </h2>
                    <button @click="openAddModal('whatsapp')" class="text-sm text-indigo-600 hover:text-indigo-800">+ إضافة مزود</button>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div v-for="item in integrations.whatsapp" :key="item.id" class="border border-gray-200 dark:border-gray-700 rounded-lg p-4 hover:border-emerald-300">
                        <div class="flex items-center justify-between mb-2">
                            <span class="font-medium text-gray-900 dark:text-white">{{ item.name_ar }}</span>
                            <span :class="item.is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-100 text-gray-500'" class="px-2 py-0.5 rounded-full text-xs">
                                {{ item.is_active ? 'مفعّل' : 'معطّل' }}
                            </span>
                        </div>
                        <p class="text-xs text-gray-500 mb-3">{{ item.name }}</p>
                        <a :href="`/system/integrations/${item.id}`" class="text-sm text-indigo-600 hover:underline">إعدادات</a>
                    </div>
                    <div v-if="!integrations.whatsapp?.length" class="text-gray-500 text-sm">لم يتم إضافة مزودين</div>
                </div>
            </div>
            
            <!-- Email Section -->
            <div class="bg-white dark:bg-gray-800 rounded-xl p-6 border border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                        <span class="text-2xl">📧</span>
                        البريد الإلكتروني
                    </h2>
                    <button @click="openAddModal('email')" class="text-sm text-indigo-600 hover:text-indigo-800">+ إضافة مزود</button>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div v-for="item in integrations.email" :key="item.id" class="border border-gray-200 dark:border-gray-700 rounded-lg p-4 hover:border-blue-300">
                        <div class="flex items-center justify-between mb-2">
                            <span class="font-medium text-gray-900 dark:text-white">{{ item.name_ar }}</span>
                            <span :class="item.is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-100 text-gray-500'" class="px-2 py-0.5 rounded-full text-xs">
                                {{ item.is_active ? 'مفعّل' : 'معطّل' }}
                            </span>
                        </div>
                        <p class="text-xs text-gray-500 mb-3">{{ item.name }}</p>
                        <a :href="`/system/integrations/${item.id}`" class="text-sm text-indigo-600 hover:underline">إعدادات</a>
                    </div>
                    <div v-if="!integrations.email?.length" class="text-gray-500 text-sm">لم يتم إضافة مزودين</div>
                </div>
            </div>
        </div>
        
        <!-- Add Provider Modal -->
        <Transition name="modal">
            <div v-if="showAddModal" class="fixed inset-0 z-50 overflow-y-auto">
                <div class="flex min-h-full items-center justify-center p-4">
                    <div class="fixed inset-0 bg-black/50" @click="showAddModal = false"></div>
                    <div class="relative bg-white dark:bg-gray-800 rounded-2xl p-6 w-full max-w-md shadow-xl">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">إضافة مزود {{ typeLabel }}</h3>
                        <form @submit.prevent="addProvider" class="space-y-4">
                            <div class="grid grid-cols-1 gap-3">
                                <label 
                                    v-for="(info, key) in availableProviders" 
                                    :key="key"
                                    :class="['border rounded-lg p-4 cursor-pointer transition-colors', form.provider === key ? 'border-indigo-500 bg-indigo-50 dark:bg-indigo-900/20' : 'border-gray-200 dark:border-gray-700 hover:border-gray-300']"
                                >
                                    <input type="radio" v-model="form.provider" :value="key" class="hidden" />
                                    <div class="flex items-center gap-3">
                                        <span class="text-2xl">{{ info.icon }}</span>
                                        <div>
                                            <p class="font-medium text-gray-900 dark:text-white">{{ info.name_ar }}</p>
                                            <p class="text-xs text-gray-500">{{ info.name }}</p>
                                        </div>
                                    </div>
                                </label>
                            </div>
                            <div class="flex gap-3 pt-4">
                                <button type="submit" :disabled="!form.provider || form.processing" class="flex-1 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg font-medium disabled:opacity-50">
                                    إضافة وتفعيل
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
import { ref, computed } from 'vue';
import { useForm } from '@inertiajs/vue3';
import SystemLayout from '@/Layouts/SystemLayout.vue';

const props = defineProps({
    integrations: Object,
    providers: Object,
});

const showAddModal = ref(false);
const selectedType = ref('sms');

const form = useForm({
    type: '',
    provider: '',
});

const typeLabel = computed(() => ({ sms: 'SMS', whatsapp: 'واتساب', email: 'البريد' }[selectedType.value]));
const availableProviders = computed(() => props.providers?.[selectedType.value] || {});

const openAddModal = (type) => {
    selectedType.value = type;
    form.type = type;
    form.provider = '';
    showAddModal.value = true;
};

const addProvider = () => {
    form.post('/system/integrations', {
        onSuccess: () => {
            showAddModal.value = false;
            form.reset();
        },
    });
};
</script>

<style scoped>
.modal-enter-active, .modal-leave-active { transition: opacity 0.2s ease; }
.modal-enter-from, .modal-leave-to { opacity: 0; }
</style>
