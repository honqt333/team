<template>
    <!-- Security Settings Modal -->
    <div v-if="modals.security" class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-black/50" @click="$emit('close', 'security')"></div>
            <div
                class="relative bg-white dark:bg-gray-800 rounded-2xl p-6 max-w-md w-full shadow-xl"
            >
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                    إعدادات الأمان
                </h3>
                <form @submit.prevent="$emit('save-security')">
                    <div class="mb-4">
                        <label class="flex items-center gap-2 cursor-pointer mb-4">
                            <input
                                type="checkbox"
                                :checked="securityForm.two_factor_enabled"
                                @change="securityForm.two_factor_enabled = $event.target.checked"
                                class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                            />
                            <span class="text-sm font-medium text-gray-900 dark:text-white">
                                تفعيل المصادقة الثنائية (2FA) للمستأجر
                            </span>
                        </label>

                        <div v-if="securityForm.two_factor_enabled">
                            <SearchableSelect
                                :model-value="securityForm.two_factor_enforcement"
                                @update:model-value="securityForm.two_factor_enforcement = $event"
                                :options="twoFactorEnforcementOptions"
                                option-label="label"
                                option-value="value"
                                label="مستوى الإلزام"
                            />
                            <p class="text-xs text-gray-500 mt-2">
                                يحدد هذا الإعداد ما إذا كان يجب على مستخدمي المستأجر تفعيل المصادقة
                                الثنائية.
                            </p>
                        </div>
                    </div>
                    <div class="flex gap-3 justify-end">
                        <button
                            type="button"
                            @click="$emit('close', 'security')"
                            class="px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg"
                        >
                            إلغاء
                        </button>
                        <button
                            type="submit"
                            :disabled="securityForm.processing"
                            class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg"
                        >
                            حفظ التغييرات
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Suspend Modal -->
    <div v-if="modals.suspend" class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-black/50" @click="$emit('close', 'suspend')"></div>
            <div
                class="relative bg-white dark:bg-gray-800 rounded-2xl p-6 max-w-md w-full shadow-xl"
            >
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                    تعليق الحساب
                </h3>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        سبب التعليق
                    </label>
                    <textarea
                        v-model="local.suspendReason"
                        rows="3"
                        class="w-full px-4 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                    ></textarea>
                </div>
                <div class="flex gap-3 justify-end">
                    <button
                        @click="$emit('close', 'suspend')"
                        class="px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg"
                    >
                        إلغاء
                    </button>
                    <button
                        @click="emitSuspend"
                        :disabled="!local.suspendReason"
                        class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg disabled:opacity-50"
                    >
                        تعليق
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Extend Trial Modal -->
    <div v-if="modals.extend" class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-black/50" @click="$emit('close', 'extend')"></div>
            <div
                class="relative bg-white dark:bg-gray-800 rounded-2xl p-6 max-w-md w-full shadow-xl"
            >
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                    تمديد الفترة التجريبية
                </h3>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        عدد الأيام
                    </label>
                    <input
                        v-model.number="local.extendDays"
                        type="number"
                        min="1"
                        max="365"
                        class="w-full px-4 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                        dir="ltr"
                    />
                    <p class="text-xs text-gray-500 mt-2">
                        يمكن أن يكون الرقم سالباً (لتقليص المدة) حتى -90 يوم.
                    </p>
                </div>
                <div class="flex gap-3 justify-end">
                    <button
                        @click="$emit('close', 'extend')"
                        class="px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg"
                    >
                        إلغاء
                    </button>
                    <button
                        @click="emitExtend"
                        :disabled="!local.extendDays"
                        class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white rounded-lg disabled:opacity-50"
                    >
                        تمديد
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div v-if="modals.delete" class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-black/50" @click="$emit('close', 'delete')"></div>
            <div
                class="relative bg-white dark:bg-gray-800 rounded-2xl p-6 max-w-md w-full shadow-xl"
            >
                <h3 class="text-lg font-semibold text-red-600 dark:text-red-400 mb-4">
                    ⚠️ حذف نهائي
                </h3>
                <div
                    class="mb-4 p-4 bg-red-50 dark:bg-red-900/20 rounded-lg border border-red-200 dark:border-red-800"
                >
                    <p class="text-sm text-red-700 dark:text-red-300">
                        سيتم حذف المستأجر
                        <strong>{{ tenant.name }}</strong>
                        وجميع بياناته نهائياً:
                    </p>
                    <ul class="mt-2 text-sm text-red-600 dark:text-red-400 list-disc list-inside">
                        <li>{{ analytics.users }} مستخدم</li>
                        <li>{{ analytics.centers }} مركز</li>
                        <li>{{ analytics.customers }} عميل</li>
                        <li>{{ analytics.work_orders }} كرت</li>
                    </ul>
                    <p class="mt-2 text-sm text-red-700 dark:text-red-300 font-bold">
                        هذا الإجراء لا يمكن التراجع عنه!
                    </p>
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        اكتب "
                        <span class="font-bold text-red-600">{{ tenant.name }}</span>
                        " للتأكيد
                    </label>
                    <input
                        v-model="local.deleteConfirmation"
                        type="text"
                        class="w-full px-4 py-2.5 rounded-lg border border-red-300 dark:border-red-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                    />
                </div>
                <div class="flex gap-3 justify-end">
                    <button
                        @click="$emit('close', 'delete')"
                        class="px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg"
                    >
                        إلغاء
                    </button>
                    <button
                        @click="emitDelete"
                        :disabled="local.deleteConfirmation !== tenant.name"
                        :class="
                            local.deleteConfirmation === tenant.name
                                ? 'bg-red-600 hover:bg-red-700'
                                : 'bg-gray-300 dark:bg-gray-600 cursor-not-allowed'
                        "
                        class="px-4 py-2 text-white rounded-lg"
                    >
                        حذف نهائي
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { reactive, watch } from 'vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';

const props = defineProps({
    modals: { type: Object, required: true },
    tenant: { type: Object, required: true },
    analytics: { type: Object, required: true },
    securityForm: { type: Object, required: true },
});

const emit = defineEmits([
    'close',
    'save-security',
    'confirm-suspend',
    'confirm-extend',
    'confirm-delete',
]);

// Local state for modal inputs. We use reactive() so we can v-model
// directly. Parent owns the form/values that matter for submission;
// these are scratch inputs cleared when the modal opens/closes.
const local = reactive({
    suspendReason: '',
    extendDays: 14,
    deleteConfirmation: '',
});

// Reset scratch inputs when a modal closes so reopening starts fresh.
watch(
    () => props.modals.suspend,
    (open) => {
        if (!open) local.suspendReason = '';
    }
);
watch(
    () => props.modals.extend,
    (open) => {
        if (!open) local.extendDays = 14;
    }
);
watch(
    () => props.modals.delete,
    (open) => {
        if (!open) local.deleteConfirmation = '';
    }
);

const emitSuspend = () => emit('confirm-suspend', local.suspendReason);
const emitExtend = () => emit('confirm-extend', local.extendDays);
const emitDelete = () => emit('confirm-delete', local.deleteConfirmation);

const twoFactorEnforcementOptions = [
    { value: 'disabled', label: 'غير ملزم (اختياري)' },
    { value: 'optional', label: 'اختياري (ينصح به)' },
    { value: 'required', label: 'إلزامي (للمستخدمين الجدد)' },
];
</script>
