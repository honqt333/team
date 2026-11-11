<template>
    <SystemLayout>
        <Head :title="$t('system.credits.sms_packages_title')" />
        <div class="space-y-6">
            <!-- Header -->
            <PageHeader
                :title="$t('system.credits.sms_credit_packages')"
                :subtitle="$t('system.credits.manage_the_sms_packages_available_for_purchase')"
                :totalCount="packages ? packages.length : null"
                :countLabel="$t('system.credits.package')"
            >
                <template #icon>
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"
                        />
                    </svg>
                </template>
                <template #actions>
                    <button
                        @click="showAddModal = true"
                        class="inline-flex items-center gap-2 px-4 py-2.5 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white rounded-xl text-sm font-semibold transition-all shadow-md hover:shadow-indigo-500/25 active:scale-95"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M12 4v16m8-8H4"
                            />
                        </svg>
                        {{ $t('system.credits.add_package') }}
                    </button>
                </template>
            </PageHeader>

            <!-- Navigation Tabs -->
            <div class="flex gap-2 border-b border-gray-200 dark:border-gray-700">
                <Link
                    :href="route('system.sms.packages')"
                    :class="
                        route().current('system.sms.packages')
                            ? 'border-indigo-600 text-indigo-600 font-medium'
                            : 'border-transparent text-gray-500 hover:text-gray-700'
                    "
                    class="px-4 py-2 border-b-2"
                >
                    {{ $t('system.credits.tabs.packages') }}
                </Link>
                <Link
                    :href="route('system.sms.balances')"
                    :class="
                        route().current('system.sms.balances')
                            ? 'border-indigo-600 text-indigo-600 font-medium'
                            : 'border-transparent text-gray-500 hover:text-gray-700'
                    "
                    class="px-4 py-2 border-b-2"
                >
                    {{ $t('system.credits.tabs.balances') }}
                </Link>
                <Link
                    :href="route('system.sms.purchases')"
                    :class="
                        route().current('system.sms.purchases')
                            ? 'border-indigo-600 text-indigo-600 font-medium'
                            : 'border-transparent text-gray-500 hover:text-gray-700'
                    "
                    class="px-4 py-2 border-b-2"
                >
                    {{ $t('system.credits.tabs.purchases') }}
                </Link>
                <Link
                    :href="route('system.sms.usage')"
                    :class="
                        route().current('system.sms.usage')
                            ? 'border-indigo-600 text-indigo-600 font-medium'
                            : 'border-transparent text-gray-500 hover:text-gray-700'
                    "
                    class="px-4 py-2 border-b-2"
                >
                    {{ $t('system.credits.tabs.usage') }}
                </Link>
            </div>

            <!-- Packages Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <div
                    v-for="pkg in packages"
                    :key="pkg.id"
                    :class="[
                        'bg-white dark:bg-gray-800 rounded-xl p-6 border-2 transition-all',
                        pkg.is_popular
                            ? 'border-indigo-500 ring-2 ring-indigo-200 dark:ring-indigo-800'
                            : 'border-gray-200 dark:border-gray-700',
                    ]"
                >
                    <div
                        v-if="pkg.is_popular"
                        class="absolute -top-3 right-4 bg-indigo-500 text-white text-xs px-2 py-1 rounded-full"
                    >
                        {{ $t('system.credits.best_seller') }}
                    </div>

                    <div class="flex items-start justify-between mb-4">
                        <div>
                            <h3 class="text-lg font-bold text-gray-900 dark:text-white">
                                {{ pkg.name_ar }}
                            </h3>
                            <p class="text-sm text-gray-500">{{ pkg.name }}</p>
                        </div>
                        <span
                            :class="
                                pkg.is_active
                                    ? 'bg-emerald-100 text-emerald-700'
                                    : 'bg-gray-100 text-gray-500'
                            "
                            class="px-2 py-1 rounded-full text-xs"
                        >
                            {{
                                pkg.is_active ? $t('common.enabled') : $t('system.credits.disabled')
                            }}
                        </span>
                    </div>

                    <div class="text-center mb-4">
                        <span class="text-4xl font-bold text-indigo-600">
                            {{ pkg.credits.toLocaleString() }}
                        </span>
                        <span class="text-gray-500 mr-1">{{ $t('system.credits.message') }}</span>
                    </div>

                    <div class="text-center mb-4">
                        <span class="text-2xl font-bold text-gray-900 dark:text-white">
                            {{ pkg.price + $t('common.currency') }}
                        </span>
                        <p class="text-xs text-gray-500">
                            {{
                                (pkg.price / pkg.credits).toFixed(3) +
                                $t('system.credits.sar_message')
                            }}
                        </p>
                    </div>

                    <div class="flex gap-2">
                        <button
                            @click="editPackage(pkg)"
                            class="flex-1 px-3 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg text-sm hover:bg-gray-200"
                        >
                            {{ $t('common.edit') }}
                        </button>
                        <button
                            @click="deletePackage(pkg)"
                            class="px-3 py-2 text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg text-sm"
                        >
                            {{ $t('common.delete') }}
                        </button>
                    </div>
                </div>

                <!-- Empty State -->
                <div v-if="!packages.length" class="col-span-full text-center py-12 text-gray-500">
                    {{ $t('system.credits.no_packages_yet._click_add_package_to_create_the') }}
                </div>
            </div>
        </div>

        <!-- Add/Edit Modal -->
        <Transition name="modal">
            <div v-if="showAddModal" class="fixed inset-0 z-50 overflow-y-auto">
                <div class="flex min-h-full items-center justify-center p-4">
                    <div class="fixed inset-0 bg-black/50" @click="showAddModal = false"></div>
                    <div
                        class="relative bg-white dark:bg-gray-800 rounded-2xl p-6 w-full max-w-md shadow-xl"
                    >
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                            {{
                                editingPackage
                                    ? $t('system.credits.edit_package')
                                    : $t('system.credits.add_new_package')
                            }}
                        </h3>

                        <form @submit.prevent="submitPackage" class="space-y-4">
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label
                                        class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"
                                    >
                                        {{ $t('system.credits.name_arabic') }}
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
                                        {{ $t('system.credits.name_english') }}
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
                                        {{ $t('system.credits.message_count_2') }}
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
                                        {{ $t('system.credits.price_sar') }}
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
                                        {{ $t('system.credits.best_seller') }}
                                    </span>
                                </label>
                            </div>

                            <div class="flex gap-3 pt-4">
                                <button
                                    type="submit"
                                    :disabled="form.processing"
                                    class="flex-1 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg font-medium disabled:opacity-50"
                                >
                                    {{ form.processing ? $t('common.saving') : $t('common.save') }}
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
                    </div>
                </div>
            </div>
        </Transition>
    </SystemLayout>
</template>

<script setup>
import AppToggle from '@/Components/Common/AppToggle.vue';
import { useI18n } from 'vue-i18n';
const { t } = useI18n();

import { ref } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import SystemLayout from '@/Layouts/SystemLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';

const props = defineProps({
    packages: Array,
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

const submitPackage = () => {
    if (editingPackage.value) {
        form.put(`/system/sms/packages/${editingPackage.value.id}`, {
            onSuccess: () => {
                showAddModal.value = false;
                editingPackage.value = null;
                form.reset();
            },
        });
    } else {
        form.post('/system/sms/packages', {
            onSuccess: () => {
                showAddModal.value = false;
                form.reset();
            },
        });
    }
};

const deletePackage = (pkg) => {
    if (confirm(t('system.credits.delete_package', { name: pkg.name_ar }))) {
        router.delete(`/system/sms/packages/${pkg.id}`);
    }
};
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
