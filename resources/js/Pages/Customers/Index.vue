<template>
    <AppLayout>
        <div class="space-y-6">
            <!-- Header Section -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-sm border border-gray-200 dark:border-gray-700">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 sm:gap-4">
                    <!-- Title + Count -->
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-indigo-600 to-purple-600 flex items-center justify-center shadow-lg shadow-indigo-500/30">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                            </svg>
                        </div>
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $t('customers.title') }}</h1>
                            <p v-if="customers" class="text-sm text-gray-500 dark:text-gray-400">
                                {{ customers.total }} {{ $t('customers.total_count') }}
                            </p>
                        </div>
                    </div>

                    <!-- Actions Row -->
                    <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
                        <!-- Search -->
                        <div class="relative">
                            <div class="absolute inset-y-0 start-0 ps-3 flex items-center pointer-events-none">
                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                </svg>
                            </div>
                            <input
                                type="text"
                                v-model="searchQuery"
                                :placeholder="$t('customers.search')"
                                class="w-full sm:w-64 ps-10 pe-4 py-2.5 text-sm border border-gray-300 dark:border-gray-600 rounded-xl bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-gray-100 placeholder-gray-400 focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all"
                            />
                        </div>

                        <!-- View Toggle -->
                        <div class="flex rounded-xl bg-gray-100 dark:bg-gray-900 p-1">
                            <button
                                @click="viewMode = 'grid'"
                                :class="[
                                    'flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium transition-all',
                                    viewMode === 'grid'
                                        ? 'bg-white dark:bg-gray-800 text-indigo-600 dark:text-indigo-400 shadow-sm'
                                        : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300'
                                ]"
                            >
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M3 3h8v8H3V3zm10 0h8v8h-8V3zM3 13h8v8H3v-8zm10 0h8v8h-8v-8z"/>
                                </svg>
                            </button>
                            <button
                                @click="viewMode = 'list'"
                                :class="[
                                    'flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium transition-all',
                                    viewMode === 'list'
                                        ? 'bg-white dark:bg-gray-800 text-indigo-600 dark:text-indigo-400 shadow-sm'
                                        : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300'
                                ]"
                            >
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M3 4h18v2H3V4zm0 7h18v2H3v-2zm0 7h18v2H3v-2z"/>
                                </svg>
                            </button>
                        </div>

                        <!-- Add Button -->
                        <button
                            @click="openCreateModal"
                            class="flex items-center justify-center gap-2 px-5 py-2.5 bg-gradient-to-r from-indigo-600 to-purple-600 text-white rounded-xl font-medium shadow-lg shadow-indigo-500/30 hover:shadow-xl hover:shadow-indigo-500/40 hover:-translate-y-0.5 transition-all"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                            <span>{{ $t('customers.add') }}</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Loading State -->
            <div v-if="!customers" class="flex flex-col items-center justify-center py-16">
                <div class="relative">
                    <div class="w-12 h-12 border-4 border-indigo-200 dark:border-indigo-900 rounded-full"></div>
                    <div class="absolute top-0 left-0 w-12 h-12 border-4 border-indigo-600 border-t-transparent rounded-full animate-spin"></div>
                </div>
                <p class="mt-4 text-gray-500 dark:text-gray-400">{{ $t('common.loading') }}</p>
            </div>

            <!-- Empty State -->
            <div v-else-if="customers.data.length === 0" class="bg-white dark:bg-gray-800 rounded-2xl p-12 text-center border border-gray-200 dark:border-gray-700">
                <div class="w-16 h-16 mx-auto rounded-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center mb-4">
                    <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                </div>
                <p class="text-lg font-medium text-gray-900 dark:text-white mb-1">{{ $t('customers.empty') }}</p>
                <p class="text-gray-500 dark:text-gray-400 mb-6">{{ $t('customers.empty_hint') }}</p>
                <button
                    @click="openCreateModal"
                    class="inline-flex items-center gap-2 px-5 py-2.5 bg-gradient-to-r from-indigo-600 to-purple-600 text-white rounded-xl font-medium shadow-lg shadow-indigo-500/30 hover:shadow-xl transition-all"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    {{ $t('customers.add') }}
                </button>
            </div>

            <!-- Grid View -->
            <div v-else-if="viewMode === 'grid'" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-3 sm:gap-4">
                <div
                    v-for="customer in customers.data"
                    :key="customer.id"
                    @click="openEditModal(customer)"
                    class="group bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 hover:border-indigo-300 dark:hover:border-indigo-700 hover:shadow-lg cursor-pointer transition-all overflow-hidden"
                >
                    <!-- Header -->
                    <div class="bg-gradient-to-r from-gray-50 to-gray-100 dark:from-gray-900/50 dark:to-gray-800/50 px-4 py-3 border-b border-gray-200 dark:border-gray-700">
                        <div class="flex items-center gap-3">
                            <!-- Type Icon -->
                            <div 
                                class="w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0 shadow-sm"
                                :class="customer.type === 'company' 
                                    ? 'bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400' 
                                    : 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400'"
                            >
                                <svg v-if="customer.type === 'individual'" class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                </svg>
                                <svg v-else class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                </svg>
                            </div>
                            <h3 class="font-bold text-gray-900 dark:text-white truncate group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors">
                                {{ customer.name }}
                            </h3>
                        </div>
                    </div>

                    <!-- Card Body -->
                    <div class="p-4 space-y-3">
                        <!-- Contact Name (for companies) -->
                        <p v-if="customer.contact_name" class="text-sm text-gray-500 dark:text-gray-400 truncate flex items-center gap-2 pb-3 border-b border-gray-100 dark:border-gray-700">
                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                            {{ customer.contact_name }}
                        </p>

                        <!-- Contact Details -->
                        <div class="space-y-2 text-sm text-gray-600 dark:text-gray-400">
                            <!-- Phone -->
                            <div class="flex items-center gap-2">
                                <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                                </svg>
                                <span class="truncate font-medium" dir="ltr">{{ customer.phone }}</span>
                            </div>
                            
                            <!-- WhatsApp -->
                            <div v-if="customer.whatsapp" class="flex items-center gap-2">
                                <svg class="w-4 h-4 flex-shrink-0 text-green-500" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.355-5.033c0-5.458 4.441-9.898 9.899-9.898 2.645 0 5.132 1.03 7.002 2.901l.001.001c1.868 1.87 2.898 4.357 2.898 7.002 0 5.459-4.441 9.899-9.897 9.899L12.05 21.785z"/>
                                </svg>
                                <span class="truncate font-medium" dir="ltr">{{ customer.whatsapp }}</span>
                            </div>
                            
                            <!-- Email -->
                            <div v-if="customer.email" class="flex items-center gap-2">
                                <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                </svg>
                                <span class="truncate">{{ customer.email }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- List View -->
            <div v-else class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full">
                        <thead>
                            <tr class="bg-gray-50 dark:bg-gray-900/50">
                                <th class="px-5 py-3 text-start text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    {{ $t('customers.columns.name') }}
                                </th>
                                <th class="px-5 py-3 text-start text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    {{ $t('customers.columns.type') }}
                                </th>
                                <th class="px-5 py-3 text-start text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    {{ $t('customers.columns.phone') }}
                                </th>
                                <th class="px-5 py-3 text-start text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    {{ $t('customers.columns.contact_name') }}
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            <tr
                                v-for="customer in customers.data"
                                :key="customer.id"
                                @click="openEditModal(customer)"
                                class="hover:bg-gray-50 dark:hover:bg-gray-900/50 cursor-pointer transition-colors"
                            >
                                <td class="px-5 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-9 h-9 rounded-lg flex items-center justify-center text-sm font-bold text-white flex-shrink-0"
                                            :class="customer.type === 'company' 
                                                ? 'bg-gradient-to-br from-blue-500 to-cyan-500' 
                                                : 'bg-gradient-to-br from-emerald-500 to-teal-500'">
                                            {{ customer.name.charAt(0).toUpperCase() }}
                                        </div>
                                        <div class="min-w-0">
                                            <p class="font-medium text-gray-900 dark:text-white truncate">{{ customer.name }}</p>
                                            <p v-if="customer.email" class="text-sm text-gray-500 dark:text-gray-400 truncate">{{ customer.email }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-5 py-4">
                                    <span
                                        class="px-2 py-1 text-xs font-medium rounded-full"
                                        :class="customer.type === 'company' 
                                            ? 'bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300' 
                                            : 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-300'"
                                    >
                                        {{ $t(`customers.type.${customer.type}`) }}
                                    </span>
                                </td>
                                <td class="px-5 py-4 text-gray-700 dark:text-gray-300">
                                    {{ customer.phone }}
                                </td>
                                <td class="px-5 py-4 text-gray-500 dark:text-gray-400">
                                    {{ customer.contact_name || $t('common.na') }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Pagination -->
            <div v-if="customers && customers.data.length > 0" class="text-sm text-gray-500 dark:text-gray-400">
                {{ $t('customers.showing') }} 
                <span class="font-medium text-gray-900 dark:text-white">{{ customers.from }}</span>-<span class="font-medium text-gray-900 dark:text-white">{{ customers.to }}</span>
                {{ $t('customers.of') }}
                <span class="font-medium text-gray-900 dark:text-white">{{ customers.total }}</span>
            </div>
        </div>

        <!-- Customer Form Modal -->
        <CustomerFormModal
            :show="showModal"
            :customer="selectedCustomer"
            @close="closeModal"
            @saved="handleSaved"
        />
    </AppLayout>
</template>

<script setup>
import { ref, watch } from 'vue';
import { router } from '@inertiajs/vue3'; // Import router
import { useI18n } from 'vue-i18n'; // Import useI18n
import { useToast } from '@/Composables/useToast'; // Import useToast
import AppLayout from '@/Layouts/AppLayout.vue';
import CustomerFormModal from '@/Components/Customers/CustomerFormModal.vue';

defineProps({
    customers: {
        type: Object,
        default: null,
    },
});

const { t } = useI18n(); // Init i18n
const { success } = useToast(); // Init toast
const showModal = ref(false);
const selectedCustomer = ref(null);
const searchQuery = ref('');
const viewMode = ref(localStorage.getItem('customersViewMode') || 'grid');

watch(viewMode, (newMode) => {
    localStorage.setItem('customersViewMode', newMode);
});

function openCreateModal() {
    selectedCustomer.value = null;
    showModal.value = true;
}

function openEditModal(customer) {
    selectedCustomer.value = customer;
    showModal.value = true;
}

function closeModal() {
    showModal.value = false;
    selectedCustomer.value = null;
}

function handleSaved() {
    closeModal();
    success(t('common.saved_success'));
    router.reload({ only: ['customers'] });
}
</script>
