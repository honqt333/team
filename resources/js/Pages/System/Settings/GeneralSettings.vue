<script setup>
import { useForm, router } from '@inertiajs/vue3';
import SystemLayout from '@/Layouts/SystemLayout.vue';
import { useToast } from '@/Composables/useToast';

const props = defineProps({
    settings: Object,
});

const { success } = useToast();

const form = useForm({
    security_2fa_sms_enabled: props.settings.security_2fa_sms_enabled,
});

const updateSetting = (key, value) => {
    form.processing = true;
    router.put(route('system.settings.general.update'), {
        key: key,
        value: value
    }, {
        preserveScroll: true,
        onSuccess: () => {
             success('تم حفظ التغييرات بنجاح');
             form.processing = false;
        },
        onError: () => {
            form.processing = false;
        }
    });
};
</script>

<template>
    <SystemLayout title="الإعدادات العامة">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                الإعدادات العامة
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900 dark:text-gray-100">
                        <h3 class="text-lg font-medium mb-4">إعدادات الأمان</h3>
                        
                        <div class="flex items-center justify-between py-4 border-b border-gray-200 dark:border-gray-700">
                            <div>
                                <h4 class="font-medium">تفعيل رسائل SMS (2FA)</h4>
                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                    تفعيل خيار إرسال رموز التحقق عبر الرسائل النصية لكافة النظام. 
                                    <br>
                                    <span class="text-xs text-amber-500">تحذير: هذا الخيار قد يترتب عليه تكاليف إضافية (رصيد SMS).</span>
                                </p>
                            </div>
                            
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" :checked="form.security_2fa_sms_enabled" @change="updateSetting('security.2fa_sms_enabled', $event.target.checked)" class="sr-only peer">
                                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-indigo-300 dark:peer-focus:ring-indigo-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-indigo-600 rtl:peer-checked:after:-translate-x-full"></div>
                            </label>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </SystemLayout>
</template>
