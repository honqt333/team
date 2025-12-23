<template>
    <BaseModal :show="show" @close="$emit('close')" size="md">
        <template #title>
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-orange-600 to-amber-600 flex items-center justify-center">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                    </svg>
                </div>
                {{ department ? $t('departments.edit') : $t('departments.add') }}
            </div>
        </template>

        <form @submit.prevent="submitForm" class="space-y-5">
            <!-- Name Arabic -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                    {{ $t('departments.form.name_ar') }} <span class="text-red-500">*</span>
                </label>
                <input 
                    type="text" 
                    v-model="form.name_ar"
                    dir="rtl"
                    :placeholder="$t('departments.form.name_ar_placeholder')"
                    class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-orange-500 focus:border-transparent transition-all"
                    :class="{ 'border-red-500': form.errors.name_ar }"
                />
                <p v-if="form.errors.name_ar" class="mt-1.5 text-sm text-red-600 dark:text-red-400">{{ form.errors.name_ar }}</p>
            </div>

            <!-- Name English -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                    {{ $t('departments.form.name_en') }}
                </label>
                <input 
                    type="text" 
                    v-model="form.name_en"
                    dir="ltr"
                    :placeholder="$t('departments.form.name_en_placeholder')"
                    class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-orange-500 focus:border-transparent transition-all"
                />
            </div>

            <!-- Description -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                    {{ $t('departments.form.description') }}
                </label>
                <textarea 
                    v-model="form.description"
                    rows="3"
                    class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-orange-500 focus:border-transparent resize-none transition-all"
                ></textarea>
            </div>

            <!-- Sort Order -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                    {{ $t('departments.form.sort_order') }}
                </label>
                <input 
                    type="number" 
                    v-model.number="form.sort_order"
                    min="0"
                    class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-orange-500 focus:border-transparent transition-all"
                />
            </div>

            <!-- Active Toggle -->
            <div class="flex items-center gap-3">
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" v-model="form.is_active" class="sr-only peer">
                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-orange-300 dark:peer-focus:ring-orange-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-orange-600"></div>
                </label>
                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ $t('departments.form.is_active') }}</span>
            </div>
        </form>

        <template #footer>
            <button
                type="button"
                @click="$emit('close')"
                class="px-4 py-2 text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white font-medium transition-colors"
            >
                {{ $t('common.cancel') }}
            </button>
            <button
                @click="submitForm"
                :disabled="form.processing"
                class="px-5 py-2.5 bg-gradient-to-r from-orange-600 to-amber-600 text-white rounded-xl font-medium shadow-lg shadow-orange-500/30 hover:shadow-xl disabled:opacity-50 disabled:cursor-not-allowed transition-all"
            >
                {{ form.processing ? $t('common.loading') : $t('common.save') }}
            </button>
        </template>
    </BaseModal>
</template>

<script setup>
import { watch } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import BaseModal from '@/Components/BaseModal.vue';
const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    department: {
        type: Object,
        default: null,
    },
});

const emit = defineEmits(['close', 'saved']);

const form = useForm({
    name_ar: '',
    name_en: '',
    description: '',
    sort_order: 0,
    is_active: true,
});

// Initialize form when department prop changes (for edit mode)
watch(() => props.department, (newDept) => {
    if (newDept) {
        form.name_ar = newDept.name_ar || '';
        form.name_en = newDept.name_en || '';
        form.description = newDept.description || '';
        form.sort_order = newDept.sort_order || 0;
        form.is_active = newDept.is_active ?? true;
    }
}, { immediate: true });

// Reset form when modal opens for create
watch(() => props.show, (open) => {
    if (open && !props.department) {
        form.reset();
        form.is_active = true;
    }
});

function submitForm() {
    const url = props.department 
        ? `/app/departments/${props.department.id}` 
        : '/app/departments';
    
    const method = props.department ? 'put' : 'post';
    const isEdit = !!props.department;

    form[method](url, {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            emit('saved');
            emit('close');
        },
    });
}
</script>
