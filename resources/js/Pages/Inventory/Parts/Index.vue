<template>
    <AppLayout>
        <div class="space-y-6">
            <!-- Header -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-sm border border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between flex-wrap gap-4">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center shadow-lg">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                            </svg>
                        </div>
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $t('inventory.parts.title') }}</h1>
                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ $t('inventory.parts.subtitle') }}</p>
                        </div>
                    </div>
                    <button
                        v-if="can('inventory.parts.create')"
                        @click="createPart"
                        class="inline-flex items-center gap-2 px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition-colors"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        <span class="hidden sm:inline">{{ $t('inventory.parts.add') }}</span>
                    </button>
                </div>
            </div>

            <!-- Filters -->
            <div class="bg-white dark:bg-gray-800 rounded-xl p-4 shadow-sm border border-gray-200 dark:border-gray-700">
                <div class="flex flex-wrap items-center gap-4">
                    <div class="flex-1 min-w-[200px]">
                        <input
                            v-model="localFilters.search"
                            type="text"
                            :placeholder="$t('inventory.parts.search_placeholder')"
                            class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500"
                            @input="debouncedSearch"
                        />
                    </div>
                    <div class="w-48">
                        <SearchableSelect
                            v-model="localFilters.status"
                            :options="[
                                {value: '', label: $t('common.all')},
                                {value: 'active', label: $t('common.active')},
                                {value: 'inactive', label: $t('common.inactive')}
                            ]"
                            option-label="label"
                            option-value="value"
                            :placeholder="$t('common.all')"
                            :label="''"
                            @change="applyFilters"
                        />
                    </div>
                    <div class="w-64" v-if="categories.length">
                        <SearchableSelect
                            v-model="localFilters.category"
                            :options="computedCategories"
                            option-label="name"
                            option-value="id"
                            :placeholder="$t('inventory.parts.all_categories')"
                            :label="''"
                            @change="applyFilters"
                        />
                    </div>
                </div>
            </div>

            <!-- Parts Table -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="px-4 py-3 text-start text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">{{ $t('inventory.parts.sku') }}</th>
                                <th class="px-4 py-3 text-start text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">{{ $t('inventory.parts.name') }}</th>
                                <th class="px-4 py-3 text-start text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">{{ $t('inventory.parts.unit') }}</th>
                                <th class="px-4 py-3 text-start text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">{{ $t('inventory.parts.category') }}</th>
                                <th class="px-4 py-3 text-start text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">{{ $t('inventory.stock.qty') }}</th>
                                <th class="px-4 py-3 text-start text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">{{ $t('common.status') }}</th>
                                <th class="px-4 py-3 text-end text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">{{ $t('common.actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            <tr v-for="part in parts.data" :key="part.id" class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                <td class="px-4 py-3 text-sm font-mono text-gray-900 dark:text-white">{{ part.sku }}</td>
                                <td class="px-4 py-3">
                                    <div class="text-sm font-medium text-gray-900 dark:text-white">{{ part.name_ar }}</div>
                                    <div v-if="part.name_en" class="text-xs text-gray-500 dark:text-gray-400">{{ part.name_en }}</div>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300">{{ part.unit?.name_ar || '-' }}</td>
                                <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300">{{ part.category?.name_ar || '-' }}</td>
                                <td class="px-4 py-3">
                                    <span :class="[
                                        'inline-flex px-2 py-1 rounded-full text-xs font-medium',
                                        part.inventory_balances_sum_qty_on_hand > part.min_qty 
                                            ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400'
                                            : part.inventory_balances_sum_qty_on_hand > 0
                                                ? 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400'
                                                : 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400'
                                    ]">
                                        {{ part.inventory_balances_sum_qty_on_hand ?? 0 }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <span :class="[
                                        'inline-flex px-2 py-1 rounded-full text-xs font-medium',
                                        part.is_active 
                                            ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400'
                                            : 'bg-gray-100 text-gray-700 dark:bg-gray-600 dark:text-gray-300'
                                    ]">
                                        {{ part.is_active ? $t('common.active') : $t('common.inactive') }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-end">
                                    <div class="flex items-center justify-end gap-2">
                                        <button
                                            v-if="can('inventory.parts.edit')"
                                            @click="editPart(part)"
                                            class="p-2 text-gray-500 hover:text-blue-600 dark:text-gray-400 dark:hover:text-blue-400"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                            </svg>
                                        </button>
                                        <button
                                            v-if="can('inventory.parts.deactivate')"
                                            @click="toggleActive(part)"
                                            :class="[
                                                'p-2',
                                                part.is_active 
                                                    ? 'text-gray-500 hover:text-red-600 dark:text-gray-400 dark:hover:text-red-400'
                                                    : 'text-gray-500 hover:text-green-600 dark:text-gray-400 dark:hover:text-green-400'
                                            ]"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path v-if="part.is_active" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                                                <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="!parts.data.length">
                                <td colspan="7" class="px-4 py-12 text-center text-gray-500 dark:text-gray-400">
                                    {{ $t('inventory.parts.empty') }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="parts.links?.length > 3" class="px-4 py-3 border-t border-gray-200 dark:border-gray-700">
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-500 dark:text-gray-400">
                            {{ $t('common.showing') }} {{ parts.from }} - {{ parts.to }} {{ $t('common.of') }} {{ parts.total }}
                        </span>
                        <div class="flex gap-1">
                            <Link
                                v-for="link in parts.links"
                                :key="link.label"
                                :href="link.url || '#'"
                                :class="[
                                    'px-3 py-1 rounded text-sm',
                                    link.active 
                                        ? 'bg-blue-600 text-white' 
                                        : link.url 
                                            ? 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600'
                                            : 'bg-gray-50 dark:bg-gray-800 text-gray-400 cursor-not-allowed'
                                ]"
                                v-html="link.label"
                            />
                        </div>
                    </div>
                </div>
            </div>

            <!-- Create/Edit Modal -->
            <CreateModal
                :show="showCreateModal"
                :part="editingPart"
                :units="units"
                :categories="categories"
                @close="closeModal"
            />
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { debounce } from 'lodash-es';
import CreateModal from './CreateModal.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';

const props = defineProps({
    parts: Object,
    categories: {
        type: Array,
        default: () => [],
    },
    units: {
        type: Array,
        default: () => [],
    },
    filters: Object,
});

const page = usePage();
const can = (permission) => page.props.auth?.permissions?.includes(permission) ?? false;

const computedCategories = computed(() => {
    const allOption = { id: '', name: page.props.auth.user.locale === 'ar' ? 'جميع التصنيفات' : 'All Categories' };
    const cats = props.categories.map(cat => ({
        ...cat,
        name: page.props.auth.user.locale === 'ar' ? cat.name_ar : (cat.name_en || cat.name_ar)
    }));
    return [allOption, ...cats];
});

const localFilters = ref({
    search: props.filters?.search || '',
    category: props.filters?.category || '',
    status: props.filters?.status || '',
});

const showCreateModal = ref(false);
const editingPart = ref(null);

const createPart = () => {
    editingPart.value = null;
    showCreateModal.value = true;
};

const editPart = (part) => {
    editingPart.value = part;
    showCreateModal.value = true;
};

const closeModal = () => {
    showCreateModal.value = false;
    editingPart.value = null;
};

const applyFilters = () => {
    router.get(route('app.inventory.parts.index'), {
        search: localFilters.value.search || undefined,
        category: localFilters.value.category || undefined,
        status: localFilters.value.status || undefined,
    }, { preserveState: true, preserveScroll: true });
};

const debouncedSearch = debounce(applyFilters, 300);

const toggleActive = (part) => {
    router.patch(route('app.inventory.parts.toggle', part.id), {}, {
        preserveScroll: true,
    });
};
</script>
