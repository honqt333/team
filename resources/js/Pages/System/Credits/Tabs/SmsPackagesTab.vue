<template>
    <div>
        <!-- Packages Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <div
                v-for="pkg in packages"
                :key="pkg.id"
                :class="[
                    'bg-white dark:bg-gray-800 rounded-xl p-6 border-2 transition-all relative',
                    pkg.is_popular
                        ? 'border-action-primary ring-2 ring-action-primary/20'
                        : 'border-gray-200 dark:border-gray-700',
                ]"
            >
                <div
                    v-if="pkg.is_popular"
                    class="absolute -top-3 right-4 bg-action-primary text-white text-xs px-2.5 py-0.5 rounded-full font-bold shadow-sm"
                >
                    {{ $t('system.credits.popular') }}
                </div>

                <div class="flex items-center gap-3 mb-4">
                    <div
                        class="w-10 h-10 rounded-xl bg-selection-bg flex items-center justify-center text-action-primary"
                    >
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"
                            />
                        </svg>
                    </div>
                    <div>
                        <h3 class="font-bold text-gray-900 dark:text-white">
                            {{ pkg.name_ar }}
                        </h3>
                        <p class="text-xs text-gray-500">{{ pkg.name }}</p>
                    </div>
                    <span
                        :class="
                            pkg.is_active
                                ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300'
                                : 'bg-gray-100 text-gray-500 dark:bg-gray-800 dark:text-gray-400'
                        "
                        class="px-2 py-1 rounded-full text-xs mr-auto font-medium"
                    >
                        {{ pkg.is_active ? $t('common.enabled') : $t('system.credits.disabled_2') }}
                    </span>
                </div>

                <div class="text-center my-4 py-2 bg-gray-50 dark:bg-gray-900/40 rounded-xl">
                    <span class="text-3xl font-extrabold text-action-primary">
                        {{ pkg.credits.toLocaleString() }}
                    </span>
                    <span class="text-gray-500 mr-1 text-sm">
                        {{ $t('system.credits.message') }}
                    </span>
                </div>

                <div class="text-center mb-5">
                    <span class="text-2xl font-bold text-gray-900 dark:text-white">
                        {{ pkg.price + ' ' + $t('common.currency') }}
                    </span>
                    <p class="text-xs text-gray-500 mt-1">
                        {{
                            (pkg.price / pkg.credits).toFixed(3) +
                            ' ' +
                            $t('system.credits.sar_message')
                        }}
                    </p>
                </div>

                <div class="flex gap-2">
                    <button
                        @click="editPackage(pkg)"
                        class="flex-1 py-2 text-center text-sm font-medium text-action-primary hover:bg-selection-bg rounded-lg transition-colors"
                    >
                        {{ $t('common.edit') }}
                    </button>
                    <button
                        @click="deletePackage(pkg)"
                        class="px-3 py-2 text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-950/40 rounded-lg transition-colors"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                            />
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- Add/Edit Modal -->
        <!-- Add/Edit Package Modal -->
        <BaseModal
            :show="showAddModal"
            :title="
                editingPackage
                    ? $t('system.credits.edit_package')
                    : $t('system.credits.add_new_package')
            "
            size="sm"
            @close="closeModal"
        >
            <form @submit.prevent="submitPackage" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium mb-1 text-gray-700 dark:text-gray-300">
                        {{ $t('system.credits.name_arabic') }}
                    </label>
                    <input
                        v-model="form.name_ar"
                        type="text"
                        required
                        class="w-full px-3 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                    />
                </div>

                <div>
                    <label class="block text-sm font-medium mb-1 text-gray-700 dark:text-gray-300">
                        {{ $t('system.credits.name_english') }}
                    </label>
                    <input
                        v-model="form.name"
                        type="text"
                        required
                        class="w-full px-3 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                    />
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label
                            class="block text-sm font-medium mb-1 text-gray-700 dark:text-gray-300"
                        >
                            {{ $t('system.credits.messages_count') }}
                        </label>
                        <input
                            v-model="form.credits"
                            type="number"
                            min="1"
                            required
                            class="w-full px-3 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                        />
                    </div>
                    <div>
                        <label
                            class="block text-sm font-medium mb-1 text-gray-700 dark:text-gray-300"
                        >
                            {{ $t('system.credits.price_sar') }}
                        </label>
                        <input
                            v-model="form.price"
                            type="number"
                            step="0.01"
                            min="0"
                            required
                            class="w-full px-3 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                        />
                    </div>
                </div>

                <div class="flex items-center gap-4 pt-2">
                    <label
                        class="flex items-center gap-2 cursor-pointer text-gray-700 dark:text-gray-300 text-sm"
                    >
                        <AppToggle v-model="form.is_active" />
                        {{ $t('common.enabled') }}
                    </label>
                    <label
                        class="flex items-center gap-2 cursor-pointer text-gray-700 dark:text-gray-300 text-sm"
                    >
                        <AppToggle v-model="form.is_popular" />
                        {{ $t('system.credits.popular') }}
                    </label>
                </div>

                <div
                    class="flex justify-end gap-3 pt-4 border-t border-gray-100 dark:border-gray-700"
                >
                    <button
                        type="button"
                        @click="closeModal"
                        class="px-4 py-2 border rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300 text-sm"
                    >
                        {{ $t('common.cancel') }}
                    </button>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="px-4 py-2 bg-action-primary text-white rounded-lg hover:bg-action-primary-hover disabled:opacity-50 text-sm font-medium"
                    >
                        {{ form.processing ? $t('common.saving') : $t('common.save') }}
                    </button>
                </div>
            </form>
        </BaseModal>
    </div>
</template>

<script setup>
import AppToggle from '@/Components/Common/AppToggle.vue';
import BaseModal from '@/Components/BaseModal.vue';
import { ref } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

const props = defineProps({
    packages: { type: Array, default: () => [] },
});

const showAddModal = ref(false);
const editingPackage = ref(null);

const form = useForm({
    name: '',
    name_ar: '',
    credits: 100,
    price: 50,
    is_active: true,
    is_popular: false,
    sort_order: 0,
});

const openAddModal = () => {
    editingPackage.value = null;
    form.reset();
    showAddModal.value = true;
};

const editPackage = (pkg) => {
    editingPackage.value = pkg;
    form.name = pkg.name;
    form.name_ar = pkg.name_ar;
    form.credits = pkg.credits;
    form.price = pkg.price;
    form.is_active = pkg.is_active;
    form.is_popular = pkg.is_popular;
    showAddModal.value = true;
};

const closeModal = () => {
    showAddModal.value = false;
    editingPackage.value = null;
    form.reset();
};

const submitPackage = () => {
    if (editingPackage.value) {
        form.put(`/system/sms/packages/${editingPackage.value.id}`, {
            onSuccess: () => {
                closeModal();
            },
        });
    } else {
        form.post('/system/sms/packages', {
            onSuccess: () => {
                closeModal();
            },
        });
    }
};

const deletePackage = (pkg) => {
    if (confirm(t('system.credits.delete_package', { name: pkg.name_ar }))) {
        router.delete(`/system/sms/packages/${pkg.id}`);
    }
};

defineExpose({
    openAddModal,
});
</script>

<style scoped>
.modal-enter-active,
.modal-leave-active {
    transition: opacity 0.2s ease;
}
.modal-enter-from,
.modal-leave-to {
    opacity: 0;
}
</style>
