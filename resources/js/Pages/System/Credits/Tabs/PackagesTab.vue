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
                        ? isWhatsapp
                            ? 'border-emerald-500 ring-2 ring-emerald-500/20'
                            : 'border-action-primary ring-2 ring-action-primary/20'
                        : 'border-gray-200 dark:border-gray-700',
                ]"
            >
                <div
                    v-if="pkg.is_popular"
                    :class="[
                        'absolute -top-3 right-4 text-white text-xs px-2.5 py-0.5 rounded-full font-bold shadow-sm',
                        isWhatsapp ? 'bg-emerald-500' : 'bg-action-primary',
                    ]"
                >
                    {{ $t('system.credits.popular') }}
                </div>

                <div class="flex items-center gap-3 mb-4">
                    <div
                        :class="[
                            'w-10 h-10 rounded-xl flex items-center justify-center',
                            isWhatsapp
                                ? 'bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600 dark:text-emerald-400'
                                : 'bg-selection-bg text-action-primary',
                        ]"
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
                        {{ pkg.is_active ? $t('common.enabled') : $t('system.credits.disabled') }}
                    </span>
                </div>

                <div class="text-center my-4 py-2 bg-gray-50 dark:bg-gray-900/40 rounded-xl">
                    <span
                        :class="[
                            'text-3xl font-extrabold',
                            isWhatsapp
                                ? 'text-emerald-600 dark:text-emerald-400'
                                : 'text-action-primary',
                        ]"
                    >
                        {{ pkg.credits.toLocaleString() }}
                    </span>
                    <span class="text-gray-500 mr-1 text-sm">
                        {{ $t('system.credits.message') }}
                    </span>
                </div>

                <div class="text-center mb-5">
                    <span class="text-2xl font-bold text-gray-900 dark:text-white">
                        {{ pkg.price }}
                    </span>
                    <span class="text-gray-500 mr-1 text-sm">{{ $t('common.currency_sar') }}</span>
                    <p class="text-xs text-gray-400 mt-1">
                        ({{ (pkg.price / pkg.credits).toFixed(2) }}
                        {{ $t('system.credits.sar_message') }})
                    </p>
                </div>

                <div class="flex gap-2">
                    <button
                        @click="editPackage(pkg)"
                        class="flex-1 px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300 transition-colors"
                    >
                        {{ $t('common.edit') }}
                    </button>
                    <button
                        @click="deletePackage(pkg)"
                        class="px-3 py-2 text-sm text-red-600 border border-red-200 dark:border-red-900 rounded-lg hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors"
                    >
                        {{ $t('common.delete') }}
                    </button>
                </div>
            </div>
        </div>

        <!-- Add/Edit Modal -->
        <BaseModal
            :show="showAddModal"
            :title="
                editingPackage
                    ? $t('system.credits.edit_package')
                    : $t('system.credits.add_new_package')
            "
            size="md"
            @close="closeModal"
        >
            <form @submit.prevent="submitPackage" class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <AppInput
                        v-model="form.name_ar"
                        type="text"
                        :label="$t('system.credits.name_arabic')"
                        :placeholder="$t('system.credits.basic_package')"
                        required
                    />
                    <AppInput
                        v-model="form.name"
                        type="text"
                        :label="$t('system.credits.name_english')"
                        placeholder="Basic Package"
                        required
                    />
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <AppInput
                        v-model="form.credits"
                        type="number"
                        min="1"
                        :label="$t('system.credits.messages_count')"
                        required
                    />
                    <AppInput
                        v-model="form.price"
                        type="number"
                        min="0"
                        step="0.01"
                        :label="$t('system.credits.price_sar')"
                        required
                    />
                </div>

                <div class="flex items-center gap-6 pt-1">
                    <AppToggle
                        v-model="form.is_active"
                        :label="$t('common.enabled')"
                        size="sm"
                        color="success"
                    />
                    <AppToggle
                        v-model="form.is_popular"
                        :label="$t('system.credits.popular')"
                        size="sm"
                        color="primary"
                    />
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
                        :class="[
                            'px-4 py-2 text-white rounded-lg disabled:opacity-50 text-sm font-medium',
                            isWhatsapp
                                ? 'bg-emerald-600 hover:bg-emerald-700'
                                : 'bg-action-primary hover:bg-action-primary-hover',
                        ]"
                    >
                        {{ form.processing ? $t('common.saving') : $t('common.save') }}
                    </button>
                </div>
            </form>
        </BaseModal>
    </div>
</template>

<script setup>
import { computed, ref } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import AppToggle from '@/Components/Common/AppToggle.vue';
import AppInput from '@/Components/App/AppInput.vue';
import BaseModal from '@/Components/BaseModal.vue';
import { useConfirm } from '@/Composables/useConfirm';

const { t } = useI18n();
const { confirm } = useConfirm();

const props = defineProps({
    channel: { type: String, required: true },
    packages: { type: Array, default: () => [] },
});

const isWhatsapp = computed(() => props.channel === 'whatsapp');
const baseUrl = computed(() => `/system/${props.channel}/packages`);

const showAddModal = ref(false);
const editingPackage = ref(null);

const form = useForm({
    name: '',
    name_ar: '',
    credits: 100,
    price: isWhatsapp.value ? 75 : 50,
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

const closeModal = () => {
    showAddModal.value = false;
    editingPackage.value = null;
    form.reset();
};

const submitPackage = () => {
    if (editingPackage.value) {
        form.put(`${baseUrl.value}/${editingPackage.value.id}`, {
            onSuccess: () => closeModal(),
        });
    } else {
        form.post(baseUrl.value, {
            onSuccess: () => closeModal(),
        });
    }
};

const deletePackage = async (pkg) => {
    const ok = await confirm({
        title: t('common.delete'),
        message: t('system.credits.delete_package', { name: pkg.name_ar }),
        confirmText: t('common.delete'),
        cancelText: t('common.cancel'),
        type: 'danger',
    });
    if (ok) {
        router.delete(`${baseUrl.value}/${pkg.id}`);
    }
};

defineExpose({
    openAddModal,
});
</script>
