<script setup>
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import axios from 'axios';
import { useI18n } from 'vue-i18n';
import AppButton from '@/Components/App/AppButton.vue';
import DataTable from '@/Components/Common/DataTable.vue';
import ServiceBayFormModal from '@/Components/Services/ServiceBayFormModal.vue';
import { useToast } from '@/Composables/useToast';
import { useConfirm } from '@/Composables/useConfirm';

const props = defineProps({
    bays: {
        type: Array,
        default: () => [],
    },
    searchQuery: {
        type: String,
        default: '',
    },
});

const { t } = useI18n();
const toast = useToast();
const { confirm } = useConfirm();

const showModal = ref(false);
const selectedBay = ref(null);

const filteredBays = computed(() => {
    const query = (props.searchQuery || '').trim().toLowerCase();
    if (!query) {
        return props.bays || [];
    }
    return (props.bays || []).filter((bay) => {
        const name = (bay.name || '').toLowerCase();
        const code = (bay.code || '').toLowerCase();
        const typeLabel = bay.type ? t(`service_bays.types.${bay.type}`).toLowerCase() : '';
        return name.includes(query) || code.includes(query) || typeLabel.includes(query);
    });
});

const columns = computed(() => [
    { key: 'name', label: t('service_bays.name') },
    { key: 'code', label: t('service_bays.code') },
    { key: 'type', label: t('service_bays.type') },
    { key: 'status', label: t('service_bays.status'), align: 'center' },
    { key: 'actions', label: t('common.actions'), align: 'end' },
]);

const openCreateModal = () => {
    selectedBay.value = null;
    showModal.value = true;
};

const openEditModal = (bay) => {
    selectedBay.value = bay;
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
    selectedBay.value = null;
};

const deleteBay = async (bay) => {
    if (bay.has_active_appointments) {
        toast.warning(t('service_bays.has_active_appointments'));
        return;
    }

    const confirmed = await confirm({
        title: t('common.confirm'),
        message: t('service_bays.confirm_delete'),
        type: 'danger',
    });

    if (!confirmed) {
        return;
    }

    try {
        await axios.delete(route('service-bays.destroy', { serviceBay: bay.id }), {
            headers: { Accept: 'application/json' },
        });
        toast.success(t('common.deleted_successfully'));
        router.reload({ preserveScroll: true });
    } catch (error) {
        toast.error(error.response?.data?.message || t('common.error_occurred'));
    }
};

const getTypeLabel = (type) => {
    if (!type) return '-';
    const key = `service_bays.types.${type}`;
    const translated = t(key);
    return translated === key ? type : translated;
};

defineExpose({
    openCreateModal,
});
</script>

<template>
    <div class="space-y-6">
        <div
            class="bg-surface-primary border border-border-subtle rounded-2xl overflow-hidden shadow-sm"
        >
            <DataTable
                :columns="columns"
                :data="filteredBays"
                :empty-title="$t('service_bays.empty')"
                :empty-action-text="$t('service_bays.add_bay')"
                @empty-action="openCreateModal"
            >
                <template #cell-name="{ item }">
                    <div>
                        <span class="font-semibold text-text-primary">{{ item.name }}</span>
                        <p v-if="item.notes" class="text-xs text-text-muted mt-0.5 line-clamp-1">
                            {{ item.notes }}
                        </p>
                    </div>
                </template>

                <template #cell-code="{ item }">
                    <span
                        v-if="item.code"
                        class="font-mono text-xs px-2 py-0.5 rounded bg-surface-secondary text-text-secondary"
                    >
                        {{ item.code }}
                    </span>
                    <span v-else class="text-text-muted">-</span>
                </template>

                <template #cell-type="{ item }">
                    <span class="text-sm text-text-secondary">{{ getTypeLabel(item.type) }}</span>
                </template>

                <template #cell-status="{ item }">
                    <span
                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold"
                        :class="
                            item.is_active
                                ? 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400'
                                : 'bg-surface-secondary text-text-muted'
                        "
                    >
                        {{ item.is_active ? $t('common.active') : $t('common.inactive') }}
                    </span>
                </template>

                <template #cell-actions="{ item }">
                    <div class="flex items-center justify-end gap-2">
                        <AppButton
                            type="button"
                            variant="ghost"
                            size="xs"
                            @click="openEditModal(item)"
                        >
                            {{ $t('common.edit') }}
                        </AppButton>
                        <AppButton
                            type="button"
                            variant="ghost"
                            size="xs"
                            class="text-rose-600 hover:text-rose-700 hover:bg-rose-500/10"
                            @click="deleteBay(item)"
                        >
                            {{ $t('common.delete') }}
                        </AppButton>
                    </div>
                </template>
            </DataTable>
        </div>

        <ServiceBayFormModal
            :show="showModal"
            :bay="selectedBay"
            @close="closeModal"
        />
    </div>
</template>
