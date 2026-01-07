<template>
    <SystemLayout title="تعديل قالب">
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                    تعديل قالب: {{ template.name }}
                </h2>
                <Link :href="route('system.communication.templates.index')" class="px-4 py-2 bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300 rounded-md text-sm font-medium hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors">
                    عودة للقائمة
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <form @submit.prevent="submit" class="p-6 space-y-6">
                        <!-- Info Card -->
                        <div class="bg-blue-50 dark:bg-blue-900/20 p-4 rounded-lg flex gap-4 items-start">
                            <div class="text-blue-600 dark:text-blue-400">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            </div>
                            <div>
                                <h4 class="font-semibold text-blue-900 dark:text-blue-100">معلومات القالب</h4>
                                <p class="text-sm text-blue-700 dark:text-blue-300 mt-1">كود القالب: <span class="font-mono bg-blue-100 dark:bg-blue-900 px-1 rounded">{{ template.code }}</span></p>
                                <p class="text-sm text-blue-700 dark:text-blue-300">النوع: {{ template.type }}</p>
                            </div>
                        </div>

                        <!-- Variables -->
                        <div v-if="template.variables" class="bg-gray-50 dark:bg-gray-700/30 p-4 rounded-lg">
                            <h4 class="font-medium text-gray-900 dark:text-white mb-2">المتغيرات المتاحة</h4>
                            <div class="flex flex-wrap gap-2">
                                <code 
                                    v-for="variable in template.variables" 
                                    :key="variable" 
                                    class="bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded text-sm text-gray-600 dark:text-gray-300 font-mono border border-gray-200 dark:border-gray-600 cursor-pointer hover:bg-gray-200 dark:hover:bg-gray-600"
                                    @click="copyVariable(variable)"
                                    title="انقر للنسخ"
                                >
                                    {{ variable }}
                                </code>
                            </div>
                            <p class="text-xs text-gray-500 mt-2">انقر على المتغير لنسخه واستخدامه في المحتوى.</p>
                        </div>

                        <!-- Subject -->
                        <div v-if="template.type === 'email'">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">عنوان الرسالة (Subject)</label>
                            <input 
                                type="text" 
                                v-model="form.subject"
                                class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
                            />
                            <p v-if="form.errors.subject" class="text-red-500 text-sm mt-1">{{ form.errors.subject }}</p>
                        </div>

                        <!-- Content -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">المحتوى</label>
                            <textarea 
                                v-model="form.content"
                                rows="15"
                                class="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 focus:ring-indigo-500 font-mono"
                                dir="ltr"
                            ></textarea>
                            <p class="text-xs text-gray-500 mt-1">يمكنك استخدام HTML لتنسيق محتوى البريد الإلكتروني.</p>
                            <p v-if="form.errors.content" class="text-red-500 text-sm mt-1">{{ form.errors.content }}</p>
                        </div>

                        <!-- Active Toggle -->
                        <div class="flex items-center">
                            <input 
                                type="checkbox" 
                                id="is_active" 
                                v-model="form.is_active"
                                class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                            >
                            <label for="is_active" class="mr-2 block text-sm text-gray-900 dark:text-gray-300">
                                تفعيل القالب
                            </label>
                        </div>

                        <!-- Actions -->
                        <div class="flex items-center justify-end border-t border-gray-200 dark:border-gray-700 pt-6">
                            <button 
                                type="button" 
                                @click="resetToDefault"
                                class="text-sm text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-200 ml-4 hidden"
                            >
                                استعادة الافتراضي
                            </button>
                            <button 
                                type="submit" 
                                :disabled="form.processing"
                                class="px-6 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:border-indigo-900 focus:ring ring-indigo-300 disabled:opacity-25 transition ease-in-out duration-150"
                            >
                                حفظ التغييرات
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </SystemLayout>
</template>

<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import SystemLayout from '@/Layouts/SystemLayout.vue';

const props = defineProps({
    template: Object,
});

const form = useForm({
    subject: props.template.subject,
    content: props.template.content,
    is_active: Boolean(props.template.is_active),
});

const submit = () => {
    form.put(route('system.communication.templates.update', props.template.id));
};

const copyVariable = (variable) => {
    navigator.clipboard.writeText(variable);
    // Optional: Show toast
};

const resetToDefault = () => {
    // Implement reset logic if needed
};
</script>
