<template>
    <div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-700">
        <table class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400">
            <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                <tr>
                    <th scope="col" class="px-6 py-3">#</th>
                    <th v-if="columns.includes('name_ar')" scope="col" class="px-6 py-3">{{ $t('common.name') }} (AR)</th>
                    <th v-if="columns.includes('name_en')" scope="col" class="px-6 py-3">{{ $t('common.name') }} (EN)</th>
                    <th v-if="columns.includes('department')" scope="col" class="px-6 py-3">{{ $t('common.department') }}</th>
                    <th v-if="columns.includes('type')" scope="col" class="px-6 py-3">{{ $t('common.type') }}</th>
                    <th v-if="columns.includes('amount')" scope="col" class="px-6 py-3">{{ $t('common.amount') }}</th>
                    <th v-if="columns.includes('is_active')" scope="col" class="px-6 py-3">{{ $t('common.status') }}</th>
                    <th v-if="columns.includes('updated_by')" scope="col" class="px-6 py-3">{{ $t('common.updated_by') }}</th>
                    <th scope="col" class="px-6 py-3">{{ $t('common.actions') }}</th>
                </tr>
            </thead>
            <tbody>
                <tr 
                    v-for="(item, index) in items" 
                    :key="item.id" 
                    class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600"
                >
                    <td class="px-6 py-4">{{ index + 1 }}</td>
                    <td 
                        v-if="columns.includes('name_ar')"
                        class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap dark:text-white cursor-pointer hover:text-violet-600 hover:underline"
                        @click="$emit('edit', item)"
                    >
                        {{ item.name_ar }}
                    </td>
                    <td v-if="columns.includes('name_en')" class="px-6 py-4">{{ item.name_en || '-' }}</td>
                    <td v-if="columns.includes('department')" class="px-6 py-4">{{ item.department?.name || '-' }}</td>
                    <td v-if="columns.includes('type')" class="px-6 py-4">
                        <span :class="[
                            'inline-flex px-2 py-0.5 rounded-full text-xs font-medium',
                            item.type === 'fixed' 
                                ? 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400'
                                : 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400'
                        ]">
                            {{ $t(`hr.settings.types.${item.type}`) }}
                        </span>
                    </td>
                    <td v-if="columns.includes('amount')" class="px-6 py-4">
                        {{ item.amount }}{{ item.type === 'percentage' ? '%' : '' }}
                    </td>
                    <td v-if="columns.includes('is_active')" class="px-6 py-4">
                        <span :class="[
                            'inline-flex px-2 py-0.5 rounded-full text-xs font-medium',
                            item.is_active
                                ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400'
                                : 'bg-gray-100 text-gray-600 dark:bg-gray-600 dark:text-gray-300'
                        ]">
                            {{ item.is_active ? $t('common.active') : $t('common.inactive') }}
                        </span>
                    </td>
                    <td v-if="columns.includes('updated_by')" class="px-6 py-4">{{ item.updated_by?.name || '-' }}</td>
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-2">
                            <button
                                @click="$emit('edit', item)"
                                class="text-violet-600 dark:text-violet-400 hover:underline font-medium"
                            >
                                {{ $t('common.edit') }}
                            </button>
                            <button
                                @click="$emit('delete', item)"
                                class="text-red-600 dark:text-red-500 hover:underline font-medium"
                            >
                                {{ $t('common.delete') }}
                            </button>
                        </div>
                    </td>
                </tr>
                <tr v-if="!items?.length">
                    <td :colspan="columns.length + 2" class="px-6 py-8 text-center text-gray-500 dark:text-gray-400">
                        {{ $t('common.no_data') }}
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>

<script setup>
defineProps({
    items: {
        type: Array,
        default: () => [],
    },
    columns: {
        type: Array,
        default: () => ['name_ar', 'name_en', 'is_active'],
    },
});

defineEmits(['edit', 'delete']);
</script>
