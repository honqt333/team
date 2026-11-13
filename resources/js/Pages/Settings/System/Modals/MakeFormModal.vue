<template>
    <BaseModal
        :show="true"
        size="sm"
        :title="make ? $t('system_settings.makes.edit') : $t('system_settings.makes.add')"
        @close="$emit('close')"
    >
        <!-- Form -->
        <form id="make-form" @submit.prevent="submit" class="space-y-4">
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

            <!-- Logo -->
            <AppFileUpload
                v-model="form.logo"
                variant="image"
                :label="$t('system_settings.form.logo')"
                :existing-url="form.logo_path ? `/storage/${form.logo_path}` : ''"
                accept="image/*"
                :max-size-mb="2"
                :error="form.errors.logo"
            />

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
                    form="make-form"
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
import AppFileUpload from '@/Components/Common/AppFileUpload.vue';

const props = defineProps({
    make: Object,
});

const emit = defineEmits(['close', 'saved']);

const form = useForm({
    name_ar: props.make?.name_ar || '',
    name_en: props.make?.name_en || '',
    is_active: props.make?.is_active ?? true,
    logo: null,
    logo_path: props.make?.logo_path || '',
    _method: props.make ? 'put' : undefined,
});

function submit() {
    const url = props.make ? `/app/settings/makes/${props.make.id}` : '/app/settings/makes';

    // Always use post for file uploads (Laravel handles _method: put)
    form.post(url, {
        onSuccess: () => {
            emit('saved');
        },
    });
}
</script>
