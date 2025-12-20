<template>
    <AppLayout>
        <div class="space-y-6">
            <!-- Page Header -->
            <div class="flex justify-between items-center">
                <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                    {{ $t('customers.title') }}
                </h1>
            </div>

            <!-- Loading State -->
            <div v-if="!customers" class="text-center py-12">
                <p class="text-gray-500 dark:text-gray-400">{{ $t('common.loading') }}</p>
            </div>

            <!-- Empty State -->
            <div v-else-if="customers.data.length === 0" class="text-center py-12">
                <p class="text-gray-500 dark:text-gray-400">{{ $t('customers.empty') }}</p>
            </div>

            <!-- Customers Table -->
            <div v-else class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-800">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                {{ $t('customers.columns.name') }}
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                {{ $t('customers.columns.phone') }}
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                {{ $t('customers.columns.type') }}
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-200 dark:divide-gray-700">
                        <tr v-for="customer in customers.data" :key="customer.id" class="hover:bg-gray-50 dark:hover:bg-gray-800">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                {{ customer.name }}
                                <span v-if="customer.contact_name" class="text-gray-500 dark:text-gray-400 block text-xs">
                                    {{ customer.contact_name }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                {{ customer.phone }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full"
                                    :class="customer.type === 'company' 
                                        ? 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200' 
                                        : 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200'">
                                    {{ $t(`customers.type.${customer.type}`) }}
                                </span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination (Simple display, no navigation yet) -->
            <div v-if="customers && customers.data.length > 0" class="flex justify-between items-center text-sm text-gray-500 dark:text-gray-400">
                <div>
                    {{ $t('customers.showing') }} {{ customers.from }} - {{ customers.to }} {{ $t('customers.of') }} {{ customers.total }}
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';

defineProps({
    customers: {
        type: Object,
        default: null,
    },
});
</script>
