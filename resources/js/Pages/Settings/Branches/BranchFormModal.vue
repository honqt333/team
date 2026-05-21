<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import Modal from '@/Components/Modal.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import Checkbox from '@/Components/Checkbox.vue';
import { useToast } from '@/Composables/useToast';

const props = defineProps({
    show: Boolean,
});

const emit = defineEmits(['close', 'saved']);
const { t } = useI18n();
const { success, error } = useToast();

const form = useForm({
    name: '',
    center_type: '',
    manager_name: '',
    phone: '',
    email: '',
    is_active: true,
});

const submit = () => {
    form.post(route('settings.branches.store'), {
        onSuccess: () => {
            success(t('messages.saved_success') || t('company_profile.branches.created_successfully'));
            emit('close');
            emit('saved');
            form.reset();
        },
        onError: () => error(t('common.error_occurred')),
        preserveScroll: true
    });
};
</script>

<template>
    <Modal :show="show" @close="$emit('close')" maxWidth="2xl">
        <div class="p-4 sm:p-6">
            <h2 class="text-lg font-medium text-gray-900 dark:text-white mb-6">
                {{ t('company_profile.branches.add') || 'Add Branch' }}
            </h2>

            <form @submit.prevent="submit" class="space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <InputLabel for="name" :value="t('company_profile.branches.name') || 'Branch Name'" />
                        <TextInput
                            id="name"
                            v-model="form.name"
                            type="text"
                            class="mt-1 block w-full"
                            required
                        />
                        <InputError :message="form.errors.name" class="mt-2" />
                    </div>
                    <div>
                        <InputLabel for="center_type" :value="t('company_profile.branches.center_type') || 'Branch Type'" />
                        <select
                            id="center_type"
                            v-model="form.center_type"
                            class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-blue-500 dark:focus:border-blue-600 focus:ring-blue-500 dark:focus:ring-blue-600 rounded-md shadow-sm"
                            required
                        >
                            <option value="">{{ t('common.select') }}</option>
                            <option value="main">Main Branch</option>
                            <option value="branch">Sub Branch</option>
                            <option value="workshop">Workshop</option>
                            <option value="warehouse">Warehouse</option>
                        </select>
                        <InputError :message="form.errors.center_type" class="mt-2" />
                    </div>

                    <div>
                        <InputLabel for="manager_name" :value="t('company_profile.branches.manager_name') || 'Manager Name'" />
                        <TextInput
                            id="manager_name"
                            v-model="form.manager_name"
                            type="text"
                            class="mt-1 block w-full"
                        />
                        <InputError :message="form.errors.manager_name" class="mt-2" />
                    </div>

                    <div>
                        <InputLabel for="phone" :value="t('company_profile.branches.phone') || 'Phone'" />
                        <TextInput
                            id="phone"
                            v-model="form.phone"
                            type="text"
                            class="mt-1 block w-full"
                            dir="ltr"
                        />
                        <InputError :message="form.errors.phone" class="mt-2" />
                    </div>

                    <div class="md:col-span-2">
                        <InputLabel for="email" :value="t('company_profile.branches.email') || 'Email'" />
                        <TextInput
                            id="email"
                            v-model="form.email"
                            type="email"
                            class="mt-1 block w-full"
                            dir="ltr"
                        />
                        <InputError :message="form.errors.email" class="mt-2" />
                    </div>
                </div>

                <div class="mt-4">
                    <label class="flex items-center">
                        <Checkbox v-model:checked="form.is_active" />
                        <span class="ms-2 text-sm text-gray-600 dark:text-gray-400">{{ t('common.active') }}</span>
                    </label>
                </div>

                <div class="flex flex-col-reverse sm:flex-row justify-end gap-3 mt-6">
                    <button
                        type="button"
                        class="w-full sm:w-auto px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-700"
                        @click="$emit('close')"
                    >
                        {{ t('common.cancel') }}
                    </button>
                    <button
                        type="submit"
                        class="w-full sm:w-auto px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
                        :disabled="form.processing"
                    >
                        {{ form.processing ? t('common.saving') : t('common.save') }}
                    </button>
                </div>
            </form>
        </div>
    </Modal>
</template>
