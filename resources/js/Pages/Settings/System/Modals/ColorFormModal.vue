<template>
    <BaseModal
        :show="true"
        size="sm"
        :title="color ? $t('system_settings.colors.edit') : $t('system_settings.colors.add')"
        @close="$emit('close')"
    >
        <!-- Form -->
        <form id="color-form" @submit.prevent="submit" class="space-y-4">
            <!-- Name AR -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    {{ $t('system_settings.form.name_ar') }}
                    <span class="text-red-500">*</span>
                </label>
                <input
                    v-model="form.name_ar"
                    type="text"
                    required
                    class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-focus-ring focus:border-transparent"
                    :placeholder="$t('system_settings.form.name_ar_placeholder')"
                />
                <p v-if="form.errors.name_ar" class="mt-1 text-sm text-red-500">
                    {{ form.errors.name_ar }}
                </p>
            </div>

            <!-- Name EN -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    {{ $t('system_settings.form.name_en') }}
                    <span class="text-red-500">*</span>
                </label>
                <input
                    v-model="form.name_en"
                    type="text"
                    required
                    class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-focus-ring focus:border-transparent"
                    :placeholder="$t('system_settings.form.name_en_placeholder')"
                />
                <p v-if="form.errors.name_en" class="mt-1 text-sm text-red-500">
                    {{ form.errors.name_en }}
                </p>
            </div>

            <!-- Color Picker -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    {{ $t('system_settings.form.hex_code') }}
                </label>
                <div class="flex items-center gap-3">
                    <input
                        v-model="form.hex_code"
                        type="color"
                        class="w-12 h-10 p-1 border border-gray-300 dark:border-gray-600 rounded-lg cursor-pointer"
                    />
                    <input
                        v-model="form.hex_code"
                        type="text"
                        maxlength="7"
                        class="flex-1 px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-focus-ring focus:border-transparent"
                        placeholder="#RRGGBB"
                    />
                </div>
                <p v-if="form.errors.hex_code" class="mt-1 text-sm text-red-500">
                    {{ form.errors.hex_code }}
                </p>
            </div>

            <!-- Is Active -->
            <div
                class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg"
            >
                <label class="text-sm font-medium text-gray-700 dark:text-gray-300">
                    {{ $t('system_settings.form.is_active') }}
                </label>
                <AppToggle v-model="form.is_active" />
            </div>
        </form>

        <template #footer>
            <div class="flex items-center justify-end gap-3 w-full">
                <button
                    type="button"
                    @click="$emit('close')"
                    class="px-5 py-2.5 text-xs font-bold text-text-secondary hover:bg-surface-secondary border border-border rounded-xl transition-colors cursor-pointer"
                >
                    {{ $t('common.cancel') }}
                </button>
                <button
                    type="submit"
                    form="color-form"
                    :disabled="form.processing"
                    class="inline-flex items-center justify-center gap-2 px-6 py-2.5 text-xs font-bold text-white bg-action-primary hover:bg-action-primary-hover rounded-xl shadow-xs transition-all disabled:opacity-50 cursor-pointer"
                >
                    {{ $t('common.save') }}
                </button>
            </div>
        </template>
    </BaseModal>
</template>

<script setup>
import BaseModal from '@/Components/BaseModal.vue';
import { useForm } from '@inertiajs/vue3';
import AppToggle from '@/Components/Common/AppToggle.vue';

const props = defineProps({
    color: Object,
});

const emit = defineEmits(['close', 'saved']);

const form = useForm({
    name_ar: props.color?.name_ar || '',
    name_en: props.color?.name_en || '',
    hex_code: props.color?.hex_code || '#000000',
    is_active: props.color?.is_active ?? true,
});

function submit() {
    const url = props.color ? `/app/settings/colors/${props.color.id}` : '/app/settings/colors';

    const method = props.color ? 'put' : 'post';

    form[method](url, {
        onSuccess: () => {
            emit('saved');
        },
    });
}
</script>
