<template>
    <AppLayout>
        <div class="space-y-6">
            <!-- Header -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-sm border border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between flex-wrap gap-4">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-slate-500 to-gray-600 flex items-center justify-center shadow-lg">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                        </div>
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $t('inventory.settings.title') }}</h1>
                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ $t('inventory.settings.subtitle') }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabs -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="border-b border-gray-200 dark:border-gray-700">
                    <nav class="flex -mb-px">
                        <button
                            v-for="tab in tabs"
                            :key="tab.key"
                            @click="activeTab = tab.key"
                            :class="[
                                'px-6 py-4 text-sm font-medium border-b-2 transition-colors',
                                activeTab === tab.key
                                    ? 'border-emerald-500 text-emerald-600 dark:text-emerald-400'
                                    : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300'
                            ]"
                        >
                            {{ tab.icon }} {{ tab.label }}
                        </button>
                    </nav>
                </div>

                <div class="p-6">
                    <!-- Units Tab -->
                    <div v-show="activeTab === 'units'" class="space-y-4">
                        <div class="flex justify-between items-center">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ $t('inventory.settings.units.title') }}</h3>
                            <button
                                @click="showUnitModal = true; editingUnit = null"
                                class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg font-medium transition-colors"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                </svg>
                                {{ $t('common.add') }}
                            </button>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                            <div
                                v-for="unit in units"
                                :key="unit.id"
                                class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl border border-gray-200 dark:border-gray-600"
                            >
                                <div class="flex items-center gap-3">
                                    <div>
                                        <p class="font-medium text-gray-900 dark:text-white">{{ unit.name_ar }}</p>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ unit.name_en }}</p>
                                    </div>
                                    <span :class="[
                                        'inline-flex px-2 py-0.5 rounded-full text-xs font-medium',
                                        unit.is_active
                                            ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400'
                                            : 'bg-gray-100 text-gray-600 dark:bg-gray-600 dark:text-gray-300'
                                    ]">
                                        {{ unit.is_active ? $t('common.active') : $t('common.inactive') }}
                                    </span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <button
                                        @click="editUnit(unit)"
                                        class="p-2 text-gray-400 hover:text-blue-600 dark:hover:text-blue-400 rounded-lg hover:bg-blue-50 dark:hover:bg-blue-900/30"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                    </button>
                                    <button
                                        @click="deleteUnit(unit)"
                                        class="p-2 text-gray-400 hover:text-red-600 dark:hover:text-red-400 rounded-lg hover:bg-red-50 dark:hover:bg-red-900/30"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <p v-if="!units.length" class="text-center py-8 text-gray-400">
                            {{ $t('common.no_data') }}
                        </p>
                    </div>

                    <!-- Categories Tab -->
                    <div v-show="activeTab === 'categories'" class="space-y-4">
                        <div class="flex justify-between items-center">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ $t('inventory.settings.categories.title') }}</h3>
                            <button
                                @click="showCategoryModal = true; editingCategory = null"
                                class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg font-medium transition-colors"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                </svg>
                                {{ $t('common.add') }}
                            </button>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                            <div
                                v-for="category in categories"
                                :key="category.id"
                                class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700/50 rounded-xl border border-gray-200 dark:border-gray-600"
                            >
                                <div class="flex items-center gap-3">
                                    <div>
                                        <p class="font-medium text-gray-900 dark:text-white">{{ category.name_ar }}</p>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ category.name_en }}</p>
                                    </div>
                                    <span :class="[
                                        'inline-flex px-2 py-0.5 rounded-full text-xs font-medium',
                                        category.is_active
                                            ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400'
                                            : 'bg-gray-100 text-gray-600 dark:bg-gray-600 dark:text-gray-300'
                                    ]">
                                        {{ category.is_active ? $t('common.active') : $t('common.inactive') }}
                                    </span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <button
                                        @click="editCategory(category)"
                                        class="p-2 text-gray-400 hover:text-blue-600 dark:hover:text-blue-400 rounded-lg hover:bg-blue-50 dark:hover:bg-blue-900/30"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                    </button>
                                    <button
                                        @click="deleteCategory(category)"
                                        class="p-2 text-gray-400 hover:text-red-600 dark:hover:text-red-400 rounded-lg hover:bg-red-50 dark:hover:bg-red-900/30"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <p v-if="!categories.length" class="text-center py-8 text-gray-400">
                            {{ $t('common.no_data') }}
                        </p>
                    </div>
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
import { useToast } from '@/Composables/useToast';
import { useConfirm } from '@/Composables/useConfirm';

const { t } = useI18n();
const { success } = useToast();
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
            onSuccess: () => success(t('common.deleted_success')),
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
            onSuccess: () => success(t('common.deleted_success')),
        });
    }
}
</script>
