<template>
    <SystemLayout>
        <div class="max-w-2xl mx-auto space-y-6">
            <div class="flex items-center gap-4">
                <a href="/system/announcements" class="text-gray-500 hover:text-gray-700">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                </a>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">إنشاء إعلان جديد</h1>
            </div>
            
            <div class="bg-white dark:bg-gray-800 rounded-xl p-6 border border-gray-200 dark:border-gray-700">
                <form @submit.prevent="submit" class="space-y-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">العنوان *</label>
                        <input type="text" v-model="form.title" required class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white" />
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">المحتوى *</label>
                        <textarea v-model="form.content" rows="4" required class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"></textarea>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">النوع</label>
                            <SearchableSelect
                                v-model="form.type"
                                :options="typeOptions"
                                option-label="label"
                                option-value="value"
                                placeholder="اختر النوع"
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">الفئة المستهدفة</label>
                            <SearchableSelect
                                v-model="form.target"
                                :options="targetOptions"
                                option-label="label"
                                option-value="value"
                                placeholder="اختر الفئة" 
                            />
                        </div>
                    </div>
                    
                    <!-- Specific Tenants -->
                    <div v-if="form.target === 'specific'">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">اختر المستأجرين</label>
                        <div class="border border-gray-200 dark:border-gray-700 rounded-lg max-h-48 overflow-y-auto p-2 space-y-1">
                            <label v-for="tenant in tenants" :key="tenant.id" class="flex items-center gap-2 p-2 hover:bg-gray-50 dark:hover:bg-gray-700 rounded cursor-pointer">
                                <input type="checkbox" :value="tenant.id" v-model="form.target_tenant_ids" class="rounded border-gray-300 text-indigo-600" />
                                <span class="text-sm text-gray-700 dark:text-gray-300">{{ tenant.trade_name || tenant.name }}</span>
                            </label>
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">قنوات الإرسال</label>
                        <div class="flex gap-4">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" value="in_app" v-model="form.channels" class="rounded border-gray-300 text-indigo-600" />
                                <span class="text-sm text-gray-700 dark:text-gray-300">داخل التطبيق</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" value="email" v-model="form.channels" class="rounded border-gray-300 text-indigo-600" />
                                <span class="text-sm text-gray-700 dark:text-gray-300">البريد الإلكتروني</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" value="sms" v-model="form.channels" class="rounded border-gray-300 text-indigo-600" />
                                <span class="text-sm text-gray-700 dark:text-gray-300">SMS</span>
                            </label>
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">تاريخ الانتهاء (اختياري)</label>
                        <input type="date" v-model="form.expires_at" class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white" dir="ltr" />
                    </div>
                    
                    <div class="flex gap-3 pt-4 border-t border-gray-200 dark:border-gray-700">
                        <button type="submit" :disabled="form.processing" class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg font-medium disabled:opacity-50">
                            إنشاء كمسودة
                        </button>
                        <a href="/system/announcements" class="px-6 py-2.5 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg">إلغاء</a>
                    </div>
                </form>
            </div>
        </div>
    </SystemLayout>
</template>

<script setup>
import { useForm } from '@inertiajs/vue3';
import SystemLayout from '@/Layouts/SystemLayout.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';

const props = defineProps({
    tenants: Array,
});

const form = useForm({
    title: '',
    content: '',
    type: 'info',
    target: 'all',
    target_tenant_ids: [],
    channels: ['in_app'],
    expires_at: '',
});

const submit = () => form.post('/system/announcements');
</script>
