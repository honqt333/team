<template>
    <div>
        <!-- Header -->
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                    {{ $t('system_settings.sections.makes') }}
                </h1>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                    {{ $t('system_settings.makes.subtitle') }}
                </p>
            </div>
            <button
                @click="openModal()"
                class="flex items-center gap-2 px-4 py-2 bg-gradient-to-r from-indigo-600 to-purple-600 text-white rounded-lg hover:from-indigo-700 hover:to-purple-700 transition-all"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                {{ $t('common.add') }}
            </button>
        </div>

        <!-- Search -->
        <div class="mb-4">
            <input
                v-model="search"
                type="text"
                :placeholder="$t('common.search')"
                class="w-full max-w-md px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                @input="handleSearch"
            />
        </div>

        <!-- Table -->
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
            <table class="w-full">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-4 py-3 text-start text-sm font-medium text-gray-500 dark:text-gray-400">
                            {{ $t('system_settings.columns.name_ar') }}
                        </th>
                        <th class="px-4 py-3 text-start text-sm font-medium text-gray-500 dark:text-gray-400">
                            {{ $t('system_settings.columns.name_en') }}
                        </th>
                        <th class="px-4 py-3 text-center text-sm font-medium text-gray-500 dark:text-gray-400">
                            {{ $t('system_settings.columns.is_active') }}
                        </th>
                        <th class="px-4 py-3 text-start text-sm font-medium text-gray-500 dark:text-gray-400">
                            {{ $t('system_settings.columns.updated_by') }}
                        </th>
                        <th class="px-4 py-3 text-center text-sm font-medium text-gray-500 dark:text-gray-400">
                            {{ $t('common.actions') }}
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    <tr v-for="make in makesData" :key="make.id" class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                        <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">
                            <span class="flex items-center gap-2">
                                {{ make.name_ar }}
                                <span v-if="make.source === 'system'" class="inline-flex items-center px-1.5 py-0.5 text-[10px] font-medium rounded bg-purple-100 text-purple-700 dark:bg-purple-900/50 dark:text-purple-300">
                                    {{ $t('common.system') }}
                                </span>
                            </span>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">
                            {{ make.name_en || '—' }}
                        </td>
                        <td class="px-4 py-3 text-center">
                            <button
                                v-if="make.source !== 'system'"
                                @click="toggleActive(make)"
                                :class="[
                                    'px-2 py-1 text-xs font-medium rounded-full',
                                    make.is_active
                                        ? 'bg-green-100 text-green-700 dark:bg-green-900/50 dark:text-green-300'
                                        : 'bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-300'
                                ]"
                            >
                                {{ make.is_active ? $t('common.active') : $t('common.inactive') }}
                            </button>
                            <span v-else :class="[
                                'px-2 py-1 text-xs font-medium rounded-full',
                                make.is_active
                                    ? 'bg-green-100 text-green-700 dark:bg-green-900/50 dark:text-green-300'
                                    : 'bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-300'
                            ]">
                                {{ make.is_active ? $t('common.active') : $t('common.inactive') }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">
                            {{ make.updated_by ? make.updated_by.name : '—' }}
                        </td>
                        <td class="px-4 py-3 text-center">
                            <div v-if="make.source !== 'system'" class="flex items-center justify-center gap-2">
                                <button
                                    @click="openModal(make)"
                                    class="p-1.5 text-gray-500 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors"
                                    :title="$t('common.edit')"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                    </svg>
                                </button>
                                <button
                                    @click="handleDelete(make)"
                                    class="p-1.5 text-gray-500 hover:text-red-600 dark:hover:text-red-400 transition-colors"
                                    :title="$t('common.delete')"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>
                                </button>
                            </div>
                            <span v-else class="text-xs text-gray-400 dark:text-gray-500">—</span>
                        </td>
                    </tr>
                    <tr v-if="!makesData?.length">
                        <td colspan="5" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                            {{ $t('common.no_data') }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div v-if="makes?.links" class="mt-4 flex justify-center gap-1">
            <template v-for="link in makes.links" :key="link.label">
                <a
                    v-if="link.url"
                    :href="link.url"
                    :class="[
                        'px-3 py-1 text-sm rounded',
                        link.active
                            ? 'bg-indigo-600 text-white'
                            : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600'
                    ]"
                    v-html="link.label"
                />
            </template>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, inject } from 'vue';
import { useI18n } from 'vue-i18n';
import { router } from '@inertiajs/vue3';
import { useConfirm } from '@/Composables/useConfirm';
import { useLocalized } from '@/Composables/useLocalized';

const { t } = useI18n();
const { confirm } = useConfirm();
const { getName } = useLocalized();

const props = defineProps({
    makes: [Object, Array],
    filters: Object
});

const emit = defineEmits(['saved']);
const openMakeModal = inject('openMakeModal');

const search = ref(props.filters?.search || '');

const makesData = computed(() => {
    return props.makes?.data || props.makes || [];
});

function openModal(make = null) {
    openMakeModal(make);
}

function handleSearch() {
    router.get('/app/settings/makes', { search: search.value }, { preserveState: true });
}

function toggleActive(make) {
    router.patch(`/app/settings/makes/${make.id}/toggle-active`);
}

async function handleDelete(make) {
    const confirmed = await confirm({
        title: t('common.confirm_delete_title'),
        message: `${getName(make)}: ${t('common.confirm_delete_message')}`,
        confirmText: t('common.delete'),
        cancelText: t('common.cancel'),
        type: 'danger',
    });

    if (confirmed) {
        router.delete(`/app/settings/makes/${make.id}`);
    }
}

function formatDate(date) {
    if (!date) return '—';
    return new Date(date).toLocaleDateString();
}
</script>
