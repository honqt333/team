<template>
    <div class="space-y-4">
        <!-- Toolbar -->
        <div class="flex items-center justify-between">
            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 flex items-center gap-2">
                <span class="text-2xl">👷</span>
                {{ $t('work_orders.tabs.technicians') }}
            </h3>
        </div>

        <!-- Technicians Table -->
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden shadow-sm">
            <table class="min-w-full">
                <thead>
                    <tr class="bg-gray-50 dark:bg-gray-900/50 border-b border-gray-200 dark:border-gray-700">
                        <th class="px-6 py-3 text-start text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            {{ $t('common.name') }}
                        </th>
                        <th class="px-6 py-3 text-start text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            {{ $t('work_orders.services') }}
                        </th>
                        <th class="px-6 py-3 text-start text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            {{ $t('common.actions') }}
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    <tr v-for="tech in assignedTechnicians" :key="tech.id" class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                        <!-- Name -->
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-full bg-indigo-100 dark:bg-indigo-900/30 flex items-center justify-center text-indigo-600 dark:text-indigo-400 font-bold text-sm">
                                    {{ tech.name.charAt(0).toUpperCase() }}
                                </div>
                                <span class="font-medium text-gray-900 dark:text-gray-100">{{ tech.name }}</span>
                            </div>
                        </td>

                        <!-- Assigned Services -->
                        <td class="px-6 py-4">
                            <div class="flex flex-wrap gap-2">
                                <span 
                                    v-for="item in tech.items" 
                                    :key="item.id"
                                    class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-medium bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300"
                                >
                                    <span class="w-1.5 h-1.5 rounded-full bg-indigo-500"></span>
                                    {{ getItemName(item) }}
                                </span>
                            </div>
                        </td>

                        <!-- Actions -->
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <!-- Placeholder for future actions like unassign -->
                            <span class="text-xs text-gray-400 italic">{{ $t('common.na') }}</span>
                        </td>
                    </tr>

                    <!-- Empty State -->
                    <tr v-if="assignedTechnicians.length === 0">
                        <td colspan="3" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                            <div class="flex flex-col items-center gap-2">
                                <svg class="w-8 h-8 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                                </svg>
                                <p>{{ $t('work_orders.no_technicians_assigned') }}</p>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';

const props = defineProps({
    workOrder: Object,
    itemsByDepartment: Object,
});

const { t, locale } = useI18n();

// Helpers
const getItemName = (item) => {
    if (!item) return '';
    if (item.service) {
        return locale.value === 'ar' ? (item.service.name_ar || item.service.name_en) : (item.service.name_en || item.service.name_ar);
    }
    return item.description || t('common.na');
};

// Computed: Aggregate technicians from all items
const assignedTechnicians = computed(() => {
    const techs = new Map();

    // Iterate through all items (flattened from departments)
    const allItems = Object.values(props.itemsByDepartment).flat();

    allItems.forEach(item => {
        if (item.technicians && item.technicians.length > 0) {
            item.technicians.forEach(tech => {
                if (!techs.has(tech.id)) {
                    techs.set(tech.id, {
                        id: tech.id,
                        name: tech.name,
                        items: []
                    });
                }
                techs.get(tech.id).items.push(item);
            });
        }
    });

    return Array.from(techs.values());
});
</script>
