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
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    {{ $t('system_settings.form.logo') }}
                </label>
                <div class="flex items-center gap-4">
                    <div
                        v-if="form.logo_path || previewUrl"
                        class="w-16 h-16 rounded-lg border border-gray-200 dark:border-gray-700 flex items-center justify-center p-2 bg-white dark:bg-gray-800"
                    >
                        <img
                            :src="previewUrl || `/storage/${form.logo_path}`"
                            class="w-full h-full object-contain"
                        />
                    </div>
                    <input
                        type="file"
                        @change="handleFileChange"
                        accept="image/*"
                        class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-selection-bg file:text-selection-text hover:file:bg-selection-bg"
                    />
                </div>
                <p v-if="form.errors.logo" class="mt-1 text-sm text-red-500">
                    {{ form.errors.logo }}
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
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import AppToggle from '@/Components/Common/AppToggle.vue';

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

const previewUrl = ref(null);

function handleFileChange(e) {
    const file = e.target.files[0];
    if (file) {
        form.logo = file;
        previewUrl.value = URL.createObjectURL(file);
    }
}

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
