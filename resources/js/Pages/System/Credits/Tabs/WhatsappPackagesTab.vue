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
                        ? 'border-emerald-500 shadow-md shadow-emerald-500/10'
                        : 'border-gray-200 dark:border-gray-700',
                ]"
            >
                <div
                    v-if="pkg.is_popular"
                    class="absolute -top-3 right-4 bg-emerald-500 text-white text-xs px-2.5 py-0.5 rounded-full font-bold shadow-sm"
                >
                    {{ $t('system.credits.best_seller_2') }}
                </div>

                <div class="flex items-center gap-3 mb-4">
                    <div
                        class="w-10 h-10 rounded-xl bg-emerald-100 dark:bg-emerald-950/40 flex items-center justify-center text-emerald-600 dark:text-emerald-400"
                    >
                        <svg class="w-6 h-6" viewBox="0 0 24 24" fill="currentColor">
                            <path
                                d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"
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
                    <span class="text-3xl font-extrabold text-emerald-600 dark:text-emerald-400">
                        {{ pkg.credits.toLocaleString() }}
                    </span>
                    <span class="text-gray-500 mr-1 text-sm">
                        {{ $t('system.credits.message_2') }}
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
                            $t('system.credits.sar_message_2')
                        }}
                    </p>
                </div>

                <div class="flex gap-2">
                    <button
                        @click="editPackage(pkg)"
                        class="flex-1 px-3 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg text-sm hover:bg-gray-200 transition-colors font-medium"
                    >
                        {{ $t('common.edit') }}
                    </button>
                    <button
                        @click="deletePackage(pkg)"
                        class="px-3 py-2 text-red-600 hover:bg-red-50 dark:hover:bg-red-950/30 rounded-lg text-sm transition-colors font-medium"
                    >
                        {{ $t('common.delete') }}
                    </button>
                </div>
            </div>

            <div
                v-if="!packages || !packages.length"
                class="col-span-full text-center py-12 text-gray-500 bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700"
            >
                {{ $t('system.credits.no_packages_yet._click_add_package_to_create_the_2') }}
            </div>
        </div>

        <!-- Add / Edit Modal -->
        <BaseModal
            :show="showAddModal"
            :title="
                editingPackage
                    ? $t('system.credits.edit_package_2')
                    : $t('system.credits.add_new_package_2')
            "
            size="sm"
            @close="showAddModal = false"
        >
            <form @submit.prevent="submitPackage" class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label
                            class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"
                        >
                            {{ $t('system.credits.name_arabic_2') }}
                        </label>
                        <input
                            type="text"
                            v-model="form.name_ar"
                            required
                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                        />
                    </div>
                    <div>
                        <label
                            class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"
                        >
                            {{ $t('system.credits.name_english_2') }}
                        </label>
                        <input
                            type="text"
                            v-model="form.name"
                            required
                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                            dir="ltr"
                        />
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label
                            class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"
                        >
                            {{ $t('system.credits.message_count_4') }}
                        </label>
                        <input
                            type="number"
                            v-model="form.credits"
                            required
                            min="1"
                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                        />
                    </div>
                    <div>
                        <label
                            class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"
                        >
                            {{ $t('system.credits.price_sar_2') }}
                        </label>
                        <input
                            type="number"
                            v-model="form.price"
                            required
                            min="0"
                            step="0.01"
                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                        />
                    </div>
                </div>
                <div class="flex gap-4">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <AppToggle v-model="form.is_active" />
                        <span class="text-sm text-gray-700 dark:text-gray-300">
                            {{ $t('common.enabled') }}
                        </span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <AppToggle v-model="form.is_popular" />
                        <span class="text-sm text-gray-700 dark:text-gray-300">
                            {{ $t('system.credits.best_seller_2') }}
                        </span>
                    </label>
                </div>
                <div class="flex gap-3 pt-4">
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="flex-1 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg font-medium disabled:opacity-50"
                    >
                        {{ editingPackage ? $t('common.save') : $t('common.add') }}
                    </button>
                    <button
                        type="button"
                        @click="showAddModal = false"
                        class="px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg"
                    >
                        {{ $t('common.cancel') }}
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
import { useForm, router } from '@inertiajs/vue3';
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
    price: 75,
    is_active: true,
    is_popular: false,
    sort_order: 0,
});

const openAddModal = () => {
    editingPackage.value = null;
    form.reset();
    form.is_active = true;
    showAddModal.value = true;
};

const editPackage = (pkg) => {
    editingPackage.value = pkg;
    Object.assign(form, {
        name: pkg.name,
        name_ar: pkg.name_ar,
        credits: pkg.credits,
        price: pkg.price,
        is_active: pkg.is_active,
        is_popular: pkg.is_popular,
    });
    showAddModal.value = true;
};

const submitPackage = () => {
    if (editingPackage.value) {
        form.put(`/system/whatsapp/packages/${editingPackage.value.id}`, {
            onSuccess: () => {
                showAddModal.value = false;
                editingPackage.value = null;
            },
        });
    } else {
        form.post('/system/whatsapp/packages', {
            onSuccess: () => {
                showAddModal.value = false;
                form.reset();
            },
        });
    }
};

const deletePackage = (pkg) => {
    if (confirm(t('system.credits.delete_package', { name: pkg.name_ar }))) {
        router.delete(`/system/whatsapp/packages/${pkg.id}`);
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
