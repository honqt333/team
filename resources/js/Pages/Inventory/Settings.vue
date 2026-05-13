<template>
    <AppLayout>
        <div class="space-y-6">
            <!-- Page Header -->
            <PageHeader
                :title="$t('inventory.settings.title')"
                :subtitle="$t('inventory.settings.subtitle')"
                gradientFrom="from-slate-600"
                gradientTo="to-gray-700"
                glowFrom="from-slate-500"
            >
                <template #icon>
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                </template>

                <template #filters>
                    <div class="inline-flex items-center p-1.5 bg-slate-900/40 backdrop-blur-md rounded-2xl border border-white/20 shadow-2xl">
                        <button
                            v-for="tab in tabs"
                            :key="tab.key"
                            @click="activeTab = tab.key"
                            :class="[
                                'flex items-center gap-3 px-10 py-3 text-sm font-black rounded-xl transition-all duration-500',
                                activeTab === tab.key
                                    ? 'bg-white text-slate-900 shadow-xl scale-[1.02] ring-1 ring-white/50'
                                    : 'text-slate-100 hover:text-white hover:bg-white/10'
                            ]"
                        >
                            <span class="text-xl transform transition-transform duration-500" :class="activeTab === tab.key ? 'scale-110' : ''">{{ tab.icon }}</span>
                            <span class="tracking-tight uppercase">{{ tab.label }}</span>
                        </button>
                    </div>
                </template>
            </PageHeader>
                    <!-- Units Tab -->
                    <div v-show="activeTab === 'units'" class="space-y-4">
                        <div class="flex justify-between items-center bg-gray-50 dark:bg-gray-900/50 p-4 rounded-2xl border border-gray-100 dark:border-gray-700">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl bg-emerald-100 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400 flex items-center justify-center">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                                    </svg>
                                </div>
                                <h3 class="text-lg font-bold text-gray-900 dark:text-white">{{ $t('inventory.settings.units.title') }}</h3>
                            </div>
                            <button
                                @click="showUnitModal = true; editingUnit = null"
                                class="flex items-center justify-center gap-2 px-4 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl font-bold shadow-lg shadow-emerald-500/20 hover:-translate-y-0.5 transition-all group/add"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 4v16m8-8H4"/>
                                </svg>
                                <span class="text-sm tracking-tight">{{ $t('common.add') }}</span>
                            </button>
                        </div>

                        <!-- Units Table -->
                        <div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-700">
                            <table class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400">
                                <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                                    <tr>
                                        <th scope="col" class="px-6 py-3">#</th>
                                        <th scope="col" class="px-6 py-3">{{ $t('common.name') }} (AR)</th>
                                        <th scope="col" class="px-6 py-3">{{ $t('common.name') }} (EN)</th>
                                        <th scope="col" class="px-6 py-3">{{ $t('common.active') }}</th>
                                        <th scope="col" class="px-6 py-3">{{ $t('common.updated_by') }}</th>
                                        <th scope="col" class="px-6 py-3">{{ $t('common.actions') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr 
                                        v-for="(unit, index) in units" 
                                        :key="unit.id" 
                                        class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600"
                                    >
                                        <td class="px-6 py-4">{{ index + 1 }}</td>
                                        <td 
                                            class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap dark:text-white cursor-pointer hover:text-emerald-600 hover:underline"
                                            @click="editUnit(unit)"
                                        >
                                            {{ unit.name_ar }}
                                        </td>
                                        <td class="px-6 py-4">{{ unit.name_en }}</td>
                                        <td class="px-6 py-4">
                                            <span :class="[
                                                'inline-flex px-2 py-0.5 rounded-full text-xs font-medium',
                                                unit.is_active
                                                    ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400'
                                                    : 'bg-gray-100 text-gray-600 dark:bg-gray-600 dark:text-gray-300'
                                            ]">
                                                {{ unit.is_active ? $t('common.active') : $t('common.inactive') }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4">{{ unit.updated_by?.name || '-' }}</td>
                                        <td class="px-6 py-4">
                                            <button
                                                @click="deleteUnit(unit)"
                                                class="font-medium text-red-600 dark:text-red-500 hover:underline"
                                            >
                                                {{ $t('common.delete') }}
                                            </button>
                                        </td>
                                    </tr>
                                    <tr v-if="!units.length">
                                        <td colspan="6" class="px-6 py-8 text-center text-gray-500">
                                            {{ $t('common.no_data') }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Categories Tab -->
                    <div v-show="activeTab === 'categories'" class="space-y-4">
                        <div class="flex justify-between items-center bg-gray-50 dark:bg-gray-900/50 p-4 rounded-2xl border border-gray-100 dark:border-gray-700">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 flex items-center justify-center">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2z"/>
                                    </svg>
                                </div>
                                <h3 class="text-lg font-bold text-gray-900 dark:text-white">{{ $t('inventory.settings.categories.title') }}</h3>
                            </div>
                            <button
                                @click="showCategoryModal = true; editingCategory = null"
                                class="flex items-center justify-center gap-2 px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl font-bold shadow-lg shadow-blue-500/20 hover:-translate-y-0.5 transition-all group/add"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 4v16m8-8H4"/>
                                </svg>
                                <span class="text-sm tracking-tight">{{ $t('common.add') }}</span>
                            </button>
                        </div>

                        <!-- Categories Table -->
                        <div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-700">
                            <table class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400">
                                <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                                    <tr>
                                        <th scope="col" class="px-6 py-3">#</th>
                                        <th scope="col" class="px-6 py-3">{{ $t('common.name') }} (AR)</th>
                                        <th scope="col" class="px-6 py-3">{{ $t('common.name') }} (EN)</th>
                                        <th scope="col" class="px-6 py-3">{{ $t('common.active') }}</th>
                                        <th scope="col" class="px-6 py-3">{{ $t('common.updated_by') }}</th>
                                        <th scope="col" class="px-6 py-3">{{ $t('common.actions') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr 
                                        v-for="(category, index) in categories" 
                                        :key="category.id" 
                                        class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600"
                                    >
                                        <td class="px-6 py-4">{{ index + 1 }}</td>
                                        <td 
                                            class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap dark:text-white cursor-pointer hover:text-emerald-600 hover:underline"
                                            @click="editCategory(category)"
                                        >
                                            {{ category.name_ar }}
                                        </td>
                                        <td class="px-6 py-4">{{ category.name_en }}</td>
                                        <td class="px-6 py-4">
                                            <span :class="[
                                                'inline-flex px-2 py-0.5 rounded-full text-xs font-medium',
                                                category.is_active
                                                    ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400'
                                                    : 'bg-gray-100 text-gray-600 dark:bg-gray-600 dark:text-gray-300'
                                            ]">
                                                {{ category.is_active ? $t('common.active') : $t('common.inactive') }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4">{{ category.updated_by?.name || '-' }}</td>
                                        <td class="px-6 py-4">
                                            <button
                                                @click="deleteCategory(category)"
                                                class="font-medium text-red-600 dark:text-red-500 hover:underline"
                                            >
                                                {{ $t('common.delete') }}
                                            </button>
                                        </td>
                                    </tr>
                                    <tr v-if="!categories.length">
                                        <td colspan="6" class="px-6 py-8 text-center text-gray-500">
                                            {{ $t('common.no_data') }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

        <!-- Unit Modal -->
        <Teleport to="body">
            <div v-if="showUnitModal" class="fixed inset-0 z-50 overflow-y-auto">
                <div class="flex min-h-full items-center justify-center p-4">
                    <div class="fixed inset-0 bg-black/50" @click="showUnitModal = false"></div>
                    <div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-xl w-full max-w-md p-6">
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">
                            {{ editingUnit ? $t('common.edit') : $t('common.add') }} {{ $t('inventory.settings.units.unit') }}
                        </h3>
                        <form @submit.prevent="saveUnit" class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ $t('common.name') }} (عربي)</label>
                                <input v-model="unitForm.name_ar" type="text" required class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white"/>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ $t('common.name') }} (English) <span class="text-red-500">*</span></label>
                                <input v-model="unitForm.name_en" type="text" required class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white"/>
                            </div>
                            <div class="flex items-center gap-3">
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input v-model="unitForm.is_active" type="checkbox" class="sr-only peer">
                                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-emerald-300 dark:peer-focus:ring-emerald-800 rounded-full peer dark:bg-gray-600 peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-500 peer-checked:bg-emerald-600"></div>
                                </label>
                                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ $t('common.active') }}</span>
                            </div>
                            <div class="flex justify-end gap-3 pt-4">
                                <button type="button" @click="showUnitModal = false" class="px-4 py-2 text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg">
                                    {{ $t('common.cancel') }}
                                </button>
                                <button type="submit" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg">
                                    {{ $t('common.save') }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- Category Modal -->
        <Teleport to="body">
            <div v-if="showCategoryModal" class="fixed inset-0 z-50 overflow-y-auto">
                <div class="flex min-h-full items-center justify-center p-4">
                    <div class="fixed inset-0 bg-black/50" @click="showCategoryModal = false"></div>
                    <div class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-xl w-full max-w-md p-6">
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">
                            {{ editingCategory ? $t('common.edit') : $t('common.add') }} {{ $t('inventory.settings.categories.category') }}
                        </h3>
                        <form @submit.prevent="saveCategory" class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ $t('common.name') }} (عربي)</label>
                                <input v-model="categoryForm.name_ar" type="text" required class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white"/>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ $t('common.name') }} (English) <span class="text-red-500">*</span></label>
                                <input v-model="categoryForm.name_en" type="text" required class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white"/>
                            </div>
                            <div class="flex items-center gap-3">
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input v-model="categoryForm.is_active" type="checkbox" class="sr-only peer">
                                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-emerald-300 dark:peer-focus:ring-emerald-800 rounded-full peer dark:bg-gray-600 peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-500 peer-checked:bg-emerald-600"></div>
                                </label>
                                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ $t('common.active') }}</span>
                            </div>
                            <div class="flex justify-end gap-3 pt-4">
                                <button type="button" @click="showCategoryModal = false" class="px-4 py-2 text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg">
                                    {{ $t('common.cancel') }}
                                </button>
                                <button type="submit" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg">
                                    {{ $t('common.save') }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </Teleport>
    </AppLayout>
</template>

<script setup>
import { ref, reactive } from 'vue';
import { router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import { useToast } from '@/Composables/useToast';
import { useConfirm } from '@/Composables/useConfirm';

const { t } = useI18n();
const { success, error } = useToast();
const { confirm } = useConfirm();

const props = defineProps({
    units: { type: Array, default: () => [] },
    categories: { type: Array, default: () => [] },
});

const activeTab = ref('units');
const tabs = [
    { key: 'units', label: t('inventory.settings.units.title'), icon: '📐' },
    { key: 'categories', label: t('inventory.settings.categories.title'), icon: '📁' },
];

// Units
const showUnitModal = ref(false);
const editingUnit = ref(null);
const unitForm = reactive({ name_ar: '', name_en: '', is_active: true });

function editUnit(unit) {
    editingUnit.value = unit;
    unitForm.name_ar = unit.name_ar;
    unitForm.name_en = unit.name_en;
    unitForm.is_active = unit.is_active ?? true;
    showUnitModal.value = true;
}

function saveUnit() {
    const url = editingUnit.value
        ? route('app.inventory.settings.units.update', editingUnit.value.id)
        : route('app.inventory.settings.units.store');
    const method = editingUnit.value ? 'put' : 'post';

    router[method](url, unitForm, {
        onSuccess: () => {
            showUnitModal.value = false;
            success(t('common.saved_success'));
            unitForm.name_ar = '';
            unitForm.name_en = '';
            unitForm.is_active = true;
            editingUnit.value = null;
        },
    });
}

async function deleteUnit(unit) {
    const confirmed = await confirm({
        title: t('common.confirm_delete_title'),
        message: t('common.confirm_delete_message'),
        confirmText: t('common.delete'),
        cancelText: t('common.cancel'),
        type: 'danger',
    });
    if (confirmed) {
        router.delete(route('app.inventory.settings.units.destroy', unit.id), {
            preserveScroll: true,
            onSuccess: (page) => {
                if (page.props.flash?.error) {
                    error(page.props.flash.error);
                } else {
                    success(t('common.deleted_success'));
                }
            },
        });
    }
}

// Categories
const showCategoryModal = ref(false);
const editingCategory = ref(null);
const categoryForm = reactive({ name_ar: '', name_en: '', is_active: true });

function editCategory(category) {
    editingCategory.value = category;
    categoryForm.name_ar = category.name_ar;
    categoryForm.name_en = category.name_en;
    categoryForm.is_active = category.is_active ?? true;
    showCategoryModal.value = true;
}

function saveCategory() {
    const url = editingCategory.value
        ? route('app.inventory.settings.categories.update', editingCategory.value.id)
        : route('app.inventory.settings.categories.store');
    const method = editingCategory.value ? 'put' : 'post';

    router[method](url, categoryForm, {
        onSuccess: () => {
            showCategoryModal.value = false;
            success(t('common.saved_success'));
            categoryForm.name_ar = '';
            categoryForm.name_en = '';
            categoryForm.is_active = true;
            editingCategory.value = null;
        },
    });
}

async function deleteCategory(category) {
    const confirmed = await confirm({
        title: t('common.confirm_delete_title'),
        message: t('common.confirm_delete_message'),
        confirmText: t('common.delete'),
        cancelText: t('common.cancel'),
        type: 'danger',
    });
    if (confirmed) {
        router.delete(route('app.inventory.settings.categories.destroy', category.id), {
            preserveScroll: true,
            onSuccess: (page) => {
                if (page.props.flash?.error) {
                    error(page.props.flash.error);
                } else {
                    success(t('common.deleted_success'));
                }
            },
        });
    }
}
</script>
