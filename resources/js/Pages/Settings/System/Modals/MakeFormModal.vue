<template>
    <div class="fixed inset-0 z-50 flex items-center justify-center">
        <!-- Backdrop -->
        <div class="absolute inset-0 bg-black/50" @click="$emit('close')"></div>

        <!-- Modal -->
        <div class="relative bg-white dark:bg-gray-800 rounded-xl shadow-xl w-full max-w-md mx-4">
            <!-- Header -->
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                    {{ make ? $t('system_settings.makes.edit') : $t('system_settings.makes.add') }}
                </h3>
                <button
                    @click="$emit('close')"
                    class="p-1 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <!-- Form -->
            <form @submit.prevent="submit" class="p-6 space-y-4">
                <!-- Name AR -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        {{ $t('system_settings.form.name_ar') }} <span class="text-red-500">*</span>
                    </label>
                    <input
                        v-model="form.name_ar"
                        type="text"
                        required
                        class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                        :placeholder="$t('system_settings.form.name_ar_placeholder')"
                    />
                    <p v-if="form.errors.name_ar" class="mt-1 text-sm text-red-500">{{ form.errors.name_ar }}</p>
                </div>

                <!-- Name EN -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        {{ $t('system_settings.form.name_en') }}
                    </label>
                    <input
                        v-model="form.name_en"
                        type="text"
                        class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                        :placeholder="$t('system_settings.form.name_en_placeholder')"
                    />
                    <p v-if="form.errors.name_en" class="mt-1 text-sm text-red-500">{{ form.errors.name_en }}</p>
                </div>
                
                <!-- Logo -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        {{ $t('system_settings.form.logo') }}
                    </label>
                    <div class="flex items-center gap-4">
                        <div v-if="form.logo_path || previewUrl" class="w-16 h-16 rounded-lg border border-gray-200 dark:border-gray-700 flex items-center justify-center p-2 bg-white dark:bg-gray-800">
                            <img :src="previewUrl || `/storage/${form.logo_path}`" class="w-full h-full object-contain" />
                        </div>
                        <input
                            type="file"
                            @change="handleFileChange"
                            accept="image/*"
                            class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100"
                        />
                    </div>
                    <p v-if="form.errors.logo" class="mt-1 text-sm text-red-500">{{ form.errors.logo }}</p>
                </div>

                <!-- Is Active -->
                <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                    <label class="text-sm font-medium text-gray-700 dark:text-gray-300">
                        {{ $t('system_settings.form.is_active') }}
                    </label>
                    <button
                        type="button"
                        @click="form.is_active = !form.is_active"
                        :class="[
                            'relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2',
                            form.is_active ? 'bg-indigo-600' : 'bg-gray-300 dark:bg-gray-600'
                        ]"
                        role="switch"
                        :aria-checked="form.is_active"
                    >
                        <span
                            :class="[
                                'pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out',
                                form.is_active ? 'ltr:translate-x-5 rtl:-translate-x-5' : 'translate-x-0'
                            ]"
                        />
                    </button>
                </div>

                <!-- Actions -->
                <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-200 dark:border-gray-700">
                    <button
                        type="button"
                        @click="$emit('close')"
                        class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors"
                    >
                        {{ $t('common.cancel') }}
                    </button>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="px-4 py-2 text-sm font-medium text-white bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 rounded-lg transition-all disabled:opacity-50"
                    >
                        {{ $t('common.save') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';

const props = defineProps({
    make: Object
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
    const url = props.make 
        ? `/app/settings/makes/${props.make.id}` 
        : '/app/settings/makes';
    
    // Always use post for file uploads (Laravel handles _method: put)
    form.post(url, {
        onSuccess: () => {
            emit('saved');
        },
    });
}
</script>
