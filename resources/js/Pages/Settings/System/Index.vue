<template>
    <AppLayout>
        <Head :title="`${$t('system_settings.title')} — ${currentSectionTitle}`" />

        <div class="space-y-6">
            <!-- Header Section -->
            <PageHeader
                :title="$t('system_settings.title')"
                :subtitle="currentSectionTitle"
                :total-count="toEnglish(currentTotalCount)"
            >
                <template #back>
                    <BackButton href="/app/settings" />
                </template>

                <template #icon>
                    <svg
                        class="w-8 h-8 text-white"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"
                        />
                    </svg>
                </template>

                <template #filters>
                    <AppTabs
                        :model-value="activeSection"
                        :tabs="sectionTabs"
                        variant="underline"
                        @update:model-value="navigateToSection"
                    >
                        <template #icon-makes>
                            <svg
                                class="w-4 h-4"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M19 9l-7 7-7-7"
                                />
                            </svg>
                        </template>
                        <template #icon-models>
                            <svg
                                class="w-4 h-4"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2"
                                />
                            </svg>
                        </template>
                        <template #icon-colors>
                            <svg
                                class="w-4 h-4"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01"
                                />
                            </svg>
                        </template>
                        <template #icon-condition-items>
                            <svg
                                class="w-4 h-4"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                                />
                            </svg>
                        </template>
                        <template #icon-income-categories>
                            <svg
                                class="w-4 h-4"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                                />
                            </svg>
                        </template>
                    </AppTabs>
                </template>

                <template #actions>
                    <button
                        type="button"
                        @click="openAddModal"
                        class="inline-flex items-center gap-2 px-5 py-2.5 bg-action-primary hover:bg-action-primary-hover text-white font-bold rounded-xl shadow-xs transition-colors cursor-pointer"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2.5"
                                d="M12 4v16m8-8H4"
                            />
                        </svg>
                        <span>{{ $t('common.add') }}</span>
                    </button>
                </template>
            </PageHeader>

            <!-- Unified Section Table -->
            <DataTable
                v-if="isManagedSection"
                :data="currentTableSource"
                :columns="currentColumns"
                :empty-title="$t('common.no_data')"
            >
                <template #header>
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                        <!-- Search -->
                        <div class="relative">
                            <div
                                class="absolute inset-y-0 start-0 ps-3 flex items-center pointer-events-none"
                            >
                                <svg
                                    class="w-4 h-4 text-content-muted"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
                                    />
                                </svg>
                            </div>
                            <input
                                type="text"
                                v-model="search"
                                :placeholder="$t('common.search')"
                                @input="handleSearch"
                                class="w-full sm:w-64 ps-10 pe-4 py-2 text-sm border border-border rounded-xl bg-surface-secondary text-content-primary placeholder-content-muted focus:ring-2 focus:ring-action-primary/30 focus:border-action-primary transition-colors"
                            />
                        </div>

                        <!-- Make Filter (for models only) -->
                        <div v-if="activeSection === 'models'" class="w-full sm:w-64">
                            <SearchableSelect
                                v-model="selectedMake"
                                :options="makeOptions"
                                option-label="label"
                                option-value="value"
                                :placeholder="$t('system_settings.models.all_makes')"
                                :label="''"
                                @change="handleMakeFilter"
                                :compact="true"
                            />
                        </div>

                        <!-- Transaction Type Filter (for income categories only) -->
                        <div v-if="activeSection === 'income-categories'" class="w-full sm:w-64">
                            <SearchableSelect
                                v-model="selectedType"
                                :options="typeOptions"
                                option-label="label"
                                option-value="value"
                                :placeholder="$t('system_settings.form.transaction_type')"
                                :label="''"
                                @change="handleTypeFilter"
                                :compact="true"
                            />
                        </div>
                    </div>
                </template>

                <template #cell-index="{ index }">
                    <span class="font-mono text-xs text-content-muted">
                        {{ toEnglish(getRowNumber(index)) }}
                    </span>
                </template>

                <template #cell-logo="{ item }">
                    <div
                        v-if="item.logo_url || item.logo_path"
                        class="w-10 h-10 rounded-xl bg-surface flex items-center justify-center p-1.5 border border-border-subtle shadow-xs"
                    >
                        <img
                            :src="
                                item.logo_url ||
                                (item.logo_path ? `/storage/${item.logo_path}` : '')
                            "
                            class="w-full h-full object-contain dark:invert"
                            :alt="item.name_ar"
                        />
                    </div>
                    <div
                        v-else
                        class="w-10 h-10 rounded-xl bg-surface-secondary flex items-center justify-center text-content-muted font-bold border border-border-subtle"
                    >
                        {{ item.name_ar?.charAt(0) || item.name_en?.charAt(0) || '?' }}
                    </div>
                </template>

                <template #cell-color="{ item }">
                    <div class="flex items-center gap-3">
                        <div
                            class="w-8 h-8 rounded-lg border border-border shadow-inner shrink-0"
                            :style="{ backgroundColor: item.hex_code || '#CCCCCC' }"
                        ></div>
                        <span class="text-xs text-content-muted font-mono">
                            {{ item.hex_code || '—' }}
                        </span>
                    </div>
                </template>

                <template #cell-name_ar="{ item }">
                    <span class="font-medium text-content-primary">
                        {{ item.name_ar }}
                    </span>
                </template>

                <template #cell-name_en="{ item }">
                    <span class="text-content-secondary">
                        {{ item.name_en || '—' }}
                    </span>
                </template>

                <template #cell-make="{ item }">
                    <span class="text-content-secondary">
                        {{ item.make ? getName(item.make) : '—' }}
                    </span>
                </template>

                <template #cell-transaction_type="{ item }">
                    <span
                        :class="[
                            'px-2.5 py-1 text-xs font-bold rounded-lg',
                            item.transaction_type === 'revenue'
                                ? 'bg-success-soft text-success'
                                : 'bg-danger-soft text-danger',
                        ]"
                    >
                        {{
                            item.transaction_type === 'revenue'
                                ? $t('system_settings.form.transaction_type_revenue')
                                : $t('system_settings.form.transaction_type_expense')
                        }}
                    </span>
                </template>

                <template #cell-is_active="{ item }">
                    <div class="flex items-center justify-center">
                        <AppToggle
                            :model-value="Boolean(item.is_active)"
                            :disabled="item.source === 'system'"
                            color="success"
                            size="sm"
                            :aria-label="
                                item.is_active ? $t('common.active') : $t('common.inactive')
                            "
                            @update:model-value="toggleActive(activeSection, item)"
                        />
                    </div>
                </template>

                <template #cell-updated_by="{ item }">
                    <span
                        v-if="item.source === 'system'"
                        class="inline-flex items-center px-2.5 py-1 text-xs font-semibold rounded-full bg-info-soft text-info"
                    >
                        {{ $t('common.system') }}
                    </span>
                    <div v-else class="flex flex-col">
                        <span class="text-content-primary">
                            {{ item.updated_by ? item.updated_by.name : '—' }}
                        </span>
                        <span v-if="item.updated_at" class="text-xs text-content-muted">
                            {{ toEnglish(formatDate(item.updated_at)) }}
                        </span>
                    </div>
                </template>

                <template #cell-actions="{ item }">
                    <div
                        v-if="item.source !== 'system'"
                        class="flex items-center justify-center gap-1.5"
                    >
                        <button
                            type="button"
                            @click="openEditModal(currentEditType, item)"
                            :title="$t('common.edit')"
                            class="p-2 text-content-secondary hover:text-action-primary hover:bg-action-primary/10 rounded-lg transition-colors cursor-pointer"
                        >
                            <svg
                                class="w-4 h-4"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"
                                />
                            </svg>
                        </button>
                        <button
                            type="button"
                            @click="handleDelete(activeSection, item)"
                            :title="$t('common.delete')"
                            class="p-2 text-content-secondary hover:text-danger hover:bg-danger-soft rounded-lg transition-colors cursor-pointer"
                        >
                            <svg
                                class="w-4 h-4"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                                />
                            </svg>
                        </button>
                    </div>
                    <span v-else class="text-xs text-content-muted">—</span>
                </template>
            </DataTable>
        </div>

        <!-- Modals -->
        <MakeFormModal
            v-if="showMakeModal"
            :make="editingMake"
            @close="closeMakeModal"
            @saved="handleSaved"
        />

        <ModelFormModal
            v-if="showModelModal"
            :model="editingModel"
            :makes="makesForFilter"
            @close="closeModelModal"
            @saved="handleSaved"
        />

        <ColorFormModal
            v-if="showColorModal"
            :color="editingColor"
            @close="closeColorModal"
            @saved="handleSaved"
        />

        <ConditionItemFormModal
            v-if="showConditionItemModal"
            :condition-item="editingConditionItem"
            @close="closeConditionItemModal"
            @saved="handleSaved"
        />

        <IncomeCategoryFormModal
            v-if="showIncomeCategoryModal"
            :income-category="editingIncomeCategory"
            @close="closeIncomeCategoryModal"
            @saved="handleSaved"
        />
    </AppLayout>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import BackButton from '@/Components/BackButton.vue';
import AppTabs from '@/Components/Common/AppTabs.vue';
import AppToggle from '@/Components/Common/AppToggle.vue';
import DataTable from '@/Components/Common/DataTable.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import MakeFormModal from './Modals/MakeFormModal.vue';
import ModelFormModal from './Modals/ModelFormModal.vue';
import ColorFormModal from './Modals/ColorFormModal.vue';
import ConditionItemFormModal from './Modals/ConditionItemFormModal.vue';
import IncomeCategoryFormModal from './Modals/IncomeCategoryFormModal.vue';
import { useToast } from '@/Composables/useToast';
import { useConfirm } from '@/Composables/useConfirm';
import { useLocalized } from '@/Composables/useLocalized';
import { useNumberFormat } from '@/Composables/useNumberFormat';

const { t } = useI18n();
const { success } = useToast();
const { confirm } = useConfirm();
const { getName } = useLocalized();
const { toEnglish } = useNumberFormat();

const MANAGED_SECTIONS = ['makes', 'models', 'colors', 'condition-items', 'income-categories'];

const props = defineProps({
    makes: [Object, Array],
    models: Object,
    colors: Object,
    condition_items: Object,
    income_categories: Object,
    settings: Object,
    global_sms_enabled: Boolean,
    activeSection: {
        type: String,
        default: 'makes',
    },
    filters: Object,
});

// Search & Filters
const search = ref(props.filters?.search || '');
const selectedMake = ref(props.filters?.make_id ? Number(props.filters.make_id) : '');
const selectedType = ref(props.filters?.transaction_type || '');

watch(
    () => props.filters?.make_id,
    (newVal) => {
        selectedMake.value = newVal ? Number(newVal) : '';
    }
);

// Modal state
const showMakeModal = ref(false);
const showModelModal = ref(false);
const showColorModal = ref(false);
const showConditionItemModal = ref(false);
const showIncomeCategoryModal = ref(false);
const editingMake = ref(null);
const editingModel = ref(null);
const editingColor = ref(null);
const editingConditionItem = ref(null);
const editingIncomeCategory = ref(null);

// Navigation Tabs
const sectionTabs = computed(() =>
    MANAGED_SECTIONS.map((id) => ({
        id,
        label: t(`system_settings.sections.${id.replace('-', '_')}`),
    }))
);

const isManagedSection = computed(() => MANAGED_SECTIONS.includes(props.activeSection));

// Computed Data Sources
const makesForFilter = computed(() => props.makes?.data || props.makes || []);

const makeOptions = computed(() => {
    const allOption = { value: '', label: t('system_settings.models.all_makes') };
    const mappedMakes = makesForFilter.value.map((make) => ({
        value: make.id,
        label: getName(make),
    }));
    return [allOption, ...mappedMakes];
});

const typeOptions = computed(() => [
    { value: '', label: t('settings.all_transaction_types') },
    { value: 'revenue', label: t('system_settings.form.transaction_type_revenue') },
    { value: 'expense', label: t('system_settings.form.transaction_type_expense') },
]);

const currentSectionTitle = computed(() => {
    const section = props.activeSection || 'makes';
    if (section === 'general') return t('permissions_modules.settings_general');
    if (section === 'condition-items') return t('system_settings.sections.condition_items');
    if (section === 'income-categories') return t('system_settings.income_categories.subtitle');
    return t(`system_settings.${section}.subtitle`) || t(`system_settings.sections.${section}`);
});

const currentTableSource = computed(() => {
    switch (props.activeSection) {
        case 'models':
            return props.models || [];
        case 'colors':
            return props.colors || [];
        case 'condition-items':
            return props.condition_items || [];
        case 'income-categories':
            return props.income_categories || [];
        case 'makes':
        default:
            return props.makes || [];
    }
});

const currentPagination = computed(() => {
    const src = currentTableSource.value;
    return Array.isArray(src) ? null : src;
});

const currentTotalCount = computed(() => {
    const src = currentTableSource.value;
    if (Array.isArray(src)) return src.length;
    return src?.total ?? src?.data?.length ?? 0;
});

const currentEditType = computed(() => {
    const map = {
        makes: 'make',
        models: 'model',
        colors: 'color',
        'condition-items': 'condition_item',
        'income-categories': 'income_category',
    };
    return map[props.activeSection] || 'make';
});

const currentColumns = computed(() => {
    const sharedEnd = [
        { key: 'is_active', label: t('system_settings.columns.is_active'), align: 'center' },
        { key: 'updated_by', label: t('system_settings.columns.updated_by') },
        { key: 'actions', label: t('common.actions'), align: 'center' },
    ];

    switch (props.activeSection) {
        case 'makes':
            return [
                { key: 'logo', label: t('system_settings.columns.logo') },
                { key: 'name_ar', label: t('system_settings.columns.name_ar') },
                { key: 'name_en', label: t('system_settings.columns.name_en') },
                ...sharedEnd,
            ];
        case 'models':
            return [
                { key: 'name_ar', label: t('system_settings.columns.name_ar') },
                { key: 'name_en', label: t('system_settings.columns.name_en') },
                { key: 'make', label: t('system_settings.columns.make') },
                ...sharedEnd,
            ];
        case 'colors':
            return [
                { key: 'color', label: t('system_settings.columns.color') },
                { key: 'name_ar', label: t('system_settings.columns.name_ar') },
                { key: 'name_en', label: t('system_settings.columns.name_en') },
                ...sharedEnd,
            ];
        case 'income-categories':
            return [
                { key: 'index', label: '#', align: 'center', width: 'w-16' },
                { key: 'name_ar', label: t('system_settings.columns.name_ar') },
                { key: 'name_en', label: t('system_settings.columns.name_en') },
                {
                    key: 'transaction_type',
                    label: t('system_settings.columns.transaction_type'),
                },
                ...sharedEnd,
            ];
        case 'condition-items':
        default:
            return [
                { key: 'name_ar', label: t('system_settings.columns.name_ar') },
                { key: 'name_en', label: t('system_settings.columns.name_en') },
                ...sharedEnd,
            ];
    }
});

// Helpers & Handlers
function getRowNumber(index) {
    const page = currentPagination.value?.current_page || 1;
    const perPage = currentPagination.value?.per_page || 15;
    return (page - 1) * perPage + index + 1;
}

function formatDate(dateString) {
    return new Date(dateString).toLocaleDateString('ar-SA-u-nu-latn');
}

function handleTypeFilter() {
    handleSearch();
}

function handleSearch() {
    const params = {
        search: search.value || undefined,
    };
    if (props.activeSection === 'models') {
        params.make_id = selectedMake.value || undefined;
    }
    if (props.activeSection === 'income-categories') {
        params.transaction_type = selectedType.value || undefined;
    }
    router.get(`/app/settings/${props.activeSection}`, params, { preserveState: true });
}

function handleMakeFilter() {
    handleSearch();
}

function navigateToSection(section) {
    router.get(`/app/settings/${section}`, {}, { preserveState: false });
}

function openAddModal() {
    if (props.activeSection === 'makes') {
        editingMake.value = null;
        showMakeModal.value = true;
    } else if (props.activeSection === 'models') {
        editingModel.value = null;
        showModelModal.value = true;
    } else if (props.activeSection === 'colors') {
        editingColor.value = null;
        showColorModal.value = true;
    } else if (props.activeSection === 'condition-items') {
        editingConditionItem.value = null;
        showConditionItemModal.value = true;
    } else if (props.activeSection === 'income-categories') {
        editingIncomeCategory.value = null;
        showIncomeCategoryModal.value = true;
    }
}

function openEditModal(type, item) {
    if (type === 'make') {
        editingMake.value = item;
        showMakeModal.value = true;
    } else if (type === 'model') {
        editingModel.value = item;
        showModelModal.value = true;
    } else if (type === 'color') {
        editingColor.value = item;
        showColorModal.value = true;
    } else if (type === 'condition_item') {
        editingConditionItem.value = item;
        showConditionItemModal.value = true;
    } else if (type === 'income_category') {
        editingIncomeCategory.value = item;
        showIncomeCategoryModal.value = true;
    }
}

function closeMakeModal() {
    showMakeModal.value = false;
    editingMake.value = null;
}

function closeModelModal() {
    showModelModal.value = false;
    editingModel.value = null;
}

function closeColorModal() {
    showColorModal.value = false;
    editingColor.value = null;
}

function closeConditionItemModal() {
    showConditionItemModal.value = false;
    editingConditionItem.value = null;
}

function closeIncomeCategoryModal() {
    showIncomeCategoryModal.value = false;
    editingIncomeCategory.value = null;
}

function handleSaved() {
    closeMakeModal();
    closeModelModal();
    closeColorModal();
    closeConditionItemModal();
    closeIncomeCategoryModal();
    success(t('common.saved_success'));
    router.reload();
}

function toggleActive(section, item) {
    if (item.source === 'system') return;
    router.patch(`/app/settings/${section}/${item.id}/toggle-active`);
}

async function handleDelete(section, item) {
    const confirmed = await confirm({
        title: t('common.confirm_delete_title'),
        message: `${getName(item)}: ${t('common.confirm_delete_message')}`,
        confirmText: t('common.delete'),
        cancelText: t('common.cancel'),
        type: 'danger',
    });

    if (confirmed) {
        router.delete(`/app/settings/${section}/${item.id}`, {
            onSuccess: () => {
                success(t('common.deleted_success'));
            },
        });
    }
}
</script>
