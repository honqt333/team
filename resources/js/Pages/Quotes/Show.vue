<template>
    <AppLayout>
        <div class="space-y-6">
            <!-- Header Actions Bar -->
            <div class="flex items-center justify-between">
                <Link :href="route('app.quotes.index')"
                    class="inline-flex items-center gap-2 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 transition-colors">
                    <svg class="w-5 h-5 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    {{ $t('common.back') }}
                </Link>

                <!-- Action Buttons -->
                <div class="flex items-center gap-2">
                    <!-- Edit Quote -->
                    <button @click="openEditModal"
                        class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                        </svg>
                        <span class="hidden sm:inline">{{ $t('common.edit') }}</span>
                    </button>

                    <!-- Share -->
                    <button @click="shareQuote"
                        class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z" />
                        </svg>
                        <span class="hidden sm:inline">{{ $t('common.share') }}</span>
                    </button>

                    <!-- Print -->
                    <button @click="printQuote"
                        class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                        </svg>
                        <span class="hidden sm:inline">{{ $t('common.print') }}</span>
                    </button>

                    <!-- Delete -->
                    <button @click="deleteQuote"
                        class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-red-600 dark:text-red-400 bg-white dark:bg-gray-800 border border-red-200 dark:border-red-800 rounded-xl hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                        <span class="hidden sm:inline">{{ $t('common.delete') }}</span>
                    </button>
                </div>
            </div>

            <!-- Top Section: Financial Summary & Customer Info -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

                <!-- 1. Customer & Vehicle Info (Swapped: Now First/Right in RTL) -->
                <div
                    class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 relative overflow-hidden">
                    <!-- Status Badge (Top Left/Right) -->
                    <div class="absolute top-6 end-6">
                        <div class="flex items-center gap-2">
                            <span class="text-sm font-medium text-gray-500">{{ quote.code }}</span>
                            <span :class="statusBadgeClass">{{ $t(`quotes.status.${quote.status}`) }}</span>
                        </div>
                    </div>

                    <div class="flex flex-col items-center text-center mt-2">
                        <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-1" dir="ltr">
                            {{ quote.vehicle?.plate_number }}
                        </h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">
                            {{ getName(quote.vehicle?.make) }} {{ getName(quote.vehicle?.model) }} {{
                                quote.vehicle?.year }}
                        </p>

                        <div
                            class="w-16 h-16 rounded-2xl bg-gray-50 dark:bg-gray-700 flex items-center justify-center mb-6">
                            <img v-if="quote.vehicle?.make?.logo_path" :src="`/storage/${quote.vehicle.make.logo_path}`"
                                :alt="getName(quote.vehicle.make)" class="w-10 h-10 object-contain" />
                            <svg v-else class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0" />
                            </svg>
                        </div>

                        <div class="flex flex-col gap-2 w-full">
                            <div
                                class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700/50 rounded-xl">
                                <div class="flex items-center gap-3">
                                    <div
                                        class="w-8 h-8 rounded-full bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center text-blue-600">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                        </svg>
                                    </div>
                                    <Link :href="route('customers.show', quote.customer.id)"
                                        class="font-medium text-gray-900 dark:text-white hover:text-blue-500 hover:underline transition-colors">
                                        {{ quote.customer?.name }}
                                    </Link>
                                </div>

                                <!-- Contact Buttons -->
                                <div class="flex items-center gap-2">
                                    <span class="text-sm text-gray-500" dir="ltr">{{ quote.customer?.phone }}</span>
                                    <a :href="`tel:${quote.customer?.phone}`"
                                        class="w-8 h-8 rounded-lg bg-green-100 dark:bg-green-900/30 text-green-600 flex items-center justify-center hover:bg-green-200 dark:hover:bg-green-900/50 transition-colors"
                                        title="Call">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z">
                                            </path>
                                        </svg>
                                    </a>
                                    <a :href="`https://wa.me/${quote.customer?.phone?.replace(/\+/g, '').replace(/\s/g, '')}`"
                                        target="_blank"
                                        class="w-8 h-8 rounded-lg bg-green-500 text-white flex items-center justify-center hover:bg-green-600 transition-colors"
                                        title="WhatsApp">
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                            <path
                                                d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z" />
                                        </svg>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 2. Cost & Payment (Swapped: Now Second/Left in RTL) -->
                <div
                    class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">
                        {{ $t('work_orders.cost_and_payment') || 'Cost & Payment' }}
                    </h3>

                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr
                                    class="text-gray-500 dark:text-gray-400 border-b border-gray-100 dark:border-gray-700">
                                    <th class="pb-3 text-start">{{ $t('common.type') }}</th>
                                    <th class="pb-3 text-end">{{ $t('work_orders.price') }}</th>
                                    <th class="pb-3 text-end">{{ $t('work_orders.discount') }}</th>
                                    <th v-if="hasTax" class="pb-3 text-end">{{ $t('invoices.tax') }} (15%)</th>
                                    <th class="pb-3 text-end">{{ $t('common.total') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50 dark:divide-gray-700/50">
                                <!-- Services Row -->
                                <tr>
                                    <td class="py-3 font-medium text-gray-700 dark:text-gray-300">
                                        {{ $t('quotes.show.tabs.services') }}
                                    </td>
                                    <td class="py-3 text-end text-gray-600 dark:text-gray-400 font-mono">
                                        {{ formatCurrency(totals.services.price) }}
                                    </td>
                                    <td class="py-3 text-end text-red-500 font-mono">
                                        {{ totals.services.discount > 0 ? '-' +
                                            formatCurrency(totals.services.discount) :
                                            '0.00' }}
                                    </td>
                                    <td v-if="hasTax" class="py-3 text-end text-gray-600 dark:text-gray-400 font-mono">
                                        {{ formatCurrency(totals.services.tax) }}
                                    </td>
                                    <td class="py-3 text-end font-bold text-gray-900 dark:text-white font-mono">
                                        {{ formatCurrency(totals.services.total) }}
                                    </td>
                                </tr>

                                <!-- Parts Row -->
                                <tr>
                                    <td class="py-3 font-medium text-gray-700 dark:text-gray-300">
                                        {{ $t('quotes.show.tabs.spare_parts') }}
                                    </td>
                                    <td class="py-3 text-end text-gray-600 dark:text-gray-400 font-mono">
                                        {{ formatCurrency(totals.parts.price) }}
                                    </td>
                                    <td class="py-3 text-end text-red-500 font-mono">
                                        {{ totals.parts.discount > 0 ? '-' + formatCurrency(totals.parts.discount) :
                                            '0.00'
                                        }}
                                    </td>
                                    <td v-if="hasTax" class="py-3 text-end text-gray-600 dark:text-gray-400 font-mono">
                                        {{ formatCurrency(totals.parts.tax) }}
                                    </td>
                                    <td class="py-3 text-end font-bold text-gray-900 dark:text-white font-mono">
                                        {{ formatCurrency(totals.parts.total) }}
                                    </td>
                                </tr>

                                <!-- Grand Total Row -->
                                <tr
                                    class="bg-gray-50 dark:bg-gray-900/50 font-bold border-t-2 border-gray-100 dark:border-gray-700">
                                    <td class="py-4 text-gray-900 dark:text-white">
                                        {{ $t('common.total') }}
                                    </td>
                                    <td class="py-4 text-end font-mono">
                                        {{ formatCurrency(totals.grand.price) }}
                                    </td>
                                    <td class="py-4 text-end text-red-600 font-mono">
                                        {{ totals.grand.discount > 0 ? '-' + formatCurrency(totals.grand.discount) :
                                            '0.00'
                                        }}
                                    </td>
                                    <td v-if="hasTax" class="py-4 text-end font-mono">
                                        {{ formatCurrency(totals.grand.tax) }}
                                    </td>
                                    <td class="py-4 text-end text-lg text-blue-600 dark:text-blue-400 font-mono">
                                        {{ formatCurrency(totals.grand.total) }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>

            <!-- Customer Complaint & Initial Assessment -->
            <div v-if="quote.customer_complaint || quote.initial_assessment"
                class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Customer Complaint -->
                <div v-if="quote.customer_complaint"
                    class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                    <div class="flex items-center gap-3 mb-4">
                        <div
                            class="w-10 h-10 rounded-xl bg-red-100 dark:bg-red-900/30 flex items-center justify-center text-red-600">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white">
                            {{ $t('quotes.form_tabs.customer_complaint') }}
                        </h3>
                    </div>
                    <p class="text-gray-700 dark:text-gray-300 leading-relaxed whitespace-pre-wrap">
                        {{ quote.customer_complaint }}
                    </p>
                </div>

                <!-- Initial Assessment -->
                <div v-if="quote.initial_assessment"
                    class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                    <div class="flex items-center gap-3 mb-4">
                        <div
                            class="w-10 h-10 rounded-xl bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center text-blue-600">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white">
                            {{ $t('quotes.form_tabs.initial_assessment') }}
                        </h3>
                    </div>
                    <p class="text-gray-700 dark:text-gray-300 leading-relaxed whitespace-pre-wrap">
                        {{ quote.initial_assessment }}
                    </p>
                </div>
            </div>

            <!-- Tabs: Services / Parts -->
            <div
                class="flex items-center justify-between bg-white dark:bg-gray-800 p-4 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm">
                <!-- Tabs -->
                <div class="flex gap-1 bg-gray-100 dark:bg-gray-900 rounded-lg p-1">
                    <button @click="activeTab = 'services'" :class="[
                        'px-4 py-2 text-sm font-medium rounded-lg transition-all',
                        activeTab === 'services'
                            ? 'bg-white dark:bg-gray-700 text-gray-900 dark:text-white shadow-sm'
                            : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200'
                    ]">
                        {{ $t('quotes.tabs.services') }}
                        <span class="ms-1.5 px-2 py-0.5 text-xs rounded-full bg-gray-200 dark:bg-gray-600">{{
                            displayedLinesCount }}</span>
                    </button>
                    <button @click="activeTab = 'parts'" :class="[
                        'px-4 py-2 text-sm font-medium rounded-lg transition-all',
                        activeTab === 'parts'
                            ? 'bg-white dark:bg-gray-700 text-gray-900 dark:text-white shadow-sm'
                            : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200'
                    ]">
                        {{ $t('quotes.tabs.parts') }}
                        <span class="ms-1.5 px-2 py-0.5 text-xs rounded-full bg-gray-200 dark:bg-gray-600">{{
                            quote.parts?.length || 0 }}</span>
                    </button>
                </div>

                <!-- Add Actions -->
                <button v-if="activeTab === 'services'" @click="openAddDepartmentModal"
                    class="flex items-center gap-2 px-4 py-2 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 rounded-lg transition-colors text-sm font-medium">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                    </svg>
                    {{ $t('quotes.show.add_department') }}
                </button>
                <button v-if="activeTab === 'parts'" @click="openPartModal()"
                    class="flex items-center gap-2 px-4 py-2 bg-green-500 hover:bg-green-600 text-white rounded-lg transition-colors text-sm font-medium">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                    </svg>
                    {{ $t('quotes.show.add_part') }}
                </button>
            </div>


            <!-- Services Tab Content -->
            <div v-show="activeTab === 'services'" id="departments-section" class="space-y-6">
                <template v-for="dept in visibleDepartments" :key="dept.id">
                    <div
                        class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
                        <!-- Department Header -->
                        <div
                            class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-900/50 flex items-center justify-between">
                            <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
                                {{ getName(dept) }}
                            </h3>
                            <div class="flex items-center gap-2">
                                <!-- Delete Dept Button (Conditional) -->
                                <button v-if="getLinesForDept(dept.id).length === 0" @click="removeDepartment(dept.id)"
                                    class="p-1.5 text-gray-400 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors"
                                    :title="$t('quotes.confirm_remove_department')">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>

                                <button @click="openServiceModal(dept.id)"
                                    class="flex items-center gap-1.5 px-3 py-1.5 text-sm font-medium text-blue-600 hover:text-blue-700 hover:bg-blue-50 dark:text-blue-400 dark:hover:bg-blue-900/20 rounded-lg transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 4v16m8-8H4" />
                                    </svg>
                                    {{ $t('quotes.show.add_service') }}
                                </button>
                            </div>
                        </div>

                        <!-- Services List -->
                        <div class="p-6">
                            <div v-if="getLinesForDept(dept.id).length > 0" class="space-y-4">
                                <div v-for="line in getLinesForDept(dept.id)" :key="line.id"
                                    class="group flex items-center justify-between p-4 rounded-xl border border-gray-100 dark:border-gray-700 hover:border-blue-200 dark:hover:border-blue-800 hover:bg-blue-50/30 dark:hover:bg-blue-900/10 transition-all">

                                    <!-- Service Info -->
                                    <div class="flex items-start gap-4">
                                        <div class="pt-1">
                                            <div
                                                class="w-8 h-8 rounded-lg bg-blue-100 dark:bg-blue-900/30 text-blue-600 flex items-center justify-center">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                </svg>
                                            </div>
                                        </div>
                                        <div>
                                            <h4 @click="editLine(line)"
                                                class="font-medium text-gray-900 dark:text-white cursor-pointer hover:text-blue-600 dark:hover:text-blue-400 transition-colors">
                                                {{ line.description || getName(line.service) }}
                                            </h4>
                                            <!-- Parts Summary Tag -->
                                            <!-- <div v-if="line.parts_count > 0" class="flex items-center gap-1 mt-1 text-xs text-gray-500">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                                                {{ line.parts_count }} Parts Linked
                                            </div> -->
                                        </div>
                                    </div>

                                    <!-- Price & Actions -->
                                    <div class="flex items-center gap-6">
                                        <!-- Prices Group (like WorkOrder) -->
                                        <div class="flex items-center gap-3 bg-gray-50 dark:bg-gray-700/50 px-3 py-1.5 rounded-lg">
                                            <!-- Labor/Service -->
                                            <div class="flex items-center gap-1.5 text-gray-700 dark:text-gray-300 font-medium" :title="$t('work_orders.item.service_cost')">
                                                <span class="text-indigo-500">🔧</span>
                                                <span class="font-mono">{{ formatCurrency(line.line_total) }}</span>
                                            </div>
                                            <!-- Parts (if any) -->
                                            <div v-if="line.parts_total > 0" class="flex items-center gap-1.5 text-gray-700 dark:text-gray-300 font-medium border-s border-gray-200 dark:border-gray-600 ps-3" :title="$t('work_orders.item.parts_cost')">
                                                <span class="text-amber-500">🔩</span>
                                                <span class="font-mono">{{ formatCurrency(line.parts_total) }}</span>
                                            </div>
                                        </div>

                                        <!-- Discount indicator -->
                                        <p v-if="line.discount_amount > 0"
                                            class="text-xs text-red-500 line-through font-mono">
                                            {{ formatCurrency(line.unit_price * line.qty) }}
                                        </p>

                                        <!-- Actions -->
                                        <div
                                            class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                            <button @click="editLine(line)"
                                                class="p-2 text-gray-400 hover:text-blue-500 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded-lg transition-colors">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z">
                                                    </path>
                                                </svg>
                                            </button>
                                            <button @click="deleteLine(line)"
                                                class="p-2 text-gray-400 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16">
                                                    </path>
                                                </svg>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div v-else class="text-center py-8">
                                <div
                                    class="w-12 h-12 bg-gray-100 dark:bg-gray-700/50 rounded-full flex items-center justify-center mx-auto mb-3 text-gray-400">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4">
                                        </path>
                                    </svg>
                                </div>
                                <p class="text-gray-500 dark:text-gray-400 text-sm mb-3">
                                    {{ $t('quotes.show.no_services') }}
                                </p>
                                <button @click="openServiceModal(dept.id)"
                                    class="text-blue-600 hover:underline text-sm font-medium">
                                    {{ $t('quotes.show.add_service') }}
                                </button>
                            </div>
                        </div>
                    </div>
                </template>
            </div>

            <!-- Parts Tab Content -->
            <div v-show="activeTab === 'parts'" id="parts-section" class="space-y-4">
                <PartsDisplay :parts="quote.parts || []" :show-vat="hasTax" storage-key="quotes_parts_view_mode"
                    @edit="openPartModal" @delete="deletePart" @add="openPartModal()" />
            </div>

        </div>

        <!-- Modals -->
        <QuoteServiceModal :show="showServiceModal" :quote="quote" :line="editingLine"
            :department-id="activeDepartmentId" :services="activeDepartmentServices" :inventory-units="inventoryUnits"
            @close="closeServiceModal" @saved="onServiceSaved" />

        <QuoteDepartmentModal :show="showDepartmentModal" :quote="quote" :available-departments="departments"
            :lines-by-department="linesByDepartment" @close="closeDepartmentModal" @saved="onDepartmentSaved" />

        <QuoteFormModal :show="showEditModal" :quote="quote" :customers="customers" :departments="departments"
            :makes="makes" :colors="colors" :modelsByMake="modelsByMake" @close="closeEditModal"
            @saved="onQuoteSaved" />

        <QuotePartModal :show="showPartModal" :quote="quote" :part="editingPart" :quote-lines="quote.lines"
            :units="inventoryUnits" :show-service-select="true" :show-toggles="false" @close="closePartModal"
            @saved="onPartSaved" />

        <ConfirmModal />

    </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { useLocalized } from '@/Composables/useLocalized';
import { useNumberFormat } from '@/Composables/useNumberFormat';
import { useConfirm } from '@/Composables/useConfirm';
import { usePermission } from '@/Composables/usePermission';
import AppLayout from '@/Layouts/AppLayout.vue';
import QuoteServiceModal from '@/Components/Quotes/QuoteServiceModal.vue';
import QuoteDepartmentModal from '@/Components/Quotes/QuoteDepartmentModal.vue';
import QuoteFormModal from '@/Components/Quotes/QuoteFormModal.vue';
import QuotePartModal from '@/Components/Quotes/QuotePartModal.vue';
import ConfirmModal from '@/Components/ConfirmModal.vue';
import PartsDisplay from '@/Components/Common/PartsDisplay.vue';

const props = defineProps({
    quote: Object,
    linesByDepartment: [Array, Object],
    departments: Array,
    services: Array,
    quoteDepartments: Array,
    customers: Array,
    makes: Array,
    colors: Array,
    modelsByMake: Object,
    inventoryUnits: Array,
});

const { t } = useI18n();
const { getName } = useLocalized();
const { formatCurrency } = useNumberFormat();
const { can } = usePermission();
const { confirm } = useConfirm();

// State
const showServiceModal = ref(false);
const showDepartmentModal = ref(false);
const showEditModal = ref(false);
const showPartModal = ref(false);
const editingLine = ref(null);
const editingPart = ref(null);
const activeDepartmentId = ref(null);
const activeTab = ref('services'); // 'services' or 'parts'

// Computed: Sort departments by sort_order
const visibleDepartments = computed(() => {
    // We want to show departments that are associated with the quote
    // Since quote.departments relation contains the linked departments.
    // We should map them to include the sort order from full departments list if needed,
    // but usually relation data is enough.
    return [...(props.quoteDepartments || [])].sort((a, b) => a.sort_order - b.sort_order);
});

// Computed: Get active department's services for the modal dropdown
const activeDepartmentServices = computed(() => {
    if (!activeDepartmentId.value) return [];
    return (props.services || []).filter(s => s.department_id === activeDepartmentId.value);
});

// Helper: Get lines for a specific department
const getLinesForDept = (deptId) => {
    // Ensure linesByDepartment is an object and deptId entry is an array
    const linesMap = props.linesByDepartment ?? {};
    const lines = linesMap[deptId];
    return Array.isArray(lines) ? lines : [];
};

// Computed: Financial Totals Breakdown
const totals = computed(() => {
    const t = {
        services: { price: 0, discount: 0, tax: 0, total: 0 },
        parts: { price: 0, discount: 0, tax: 0, total: 0 },
        grand: { price: 0, discount: 0, tax: 0, total: 0 },
    };

    if (!props.quote?.lines) return t;

    props.quote.lines.forEach(line => {
        // Price (Qty * Unit Price)
        const linePrice = parseFloat(line.unit_price || 0) * parseFloat(line.qty || 0);
        t.services.price += linePrice;

        // Discount
        t.services.discount += parseFloat(line.discount_amount || 0);

        // Tax (Assuming 15% inclusive or exclusive - Quote model has tax logic)
        // Let's rely on line->tax_amount if available, or calc simplistic
        t.services.tax += parseFloat(line.tax_amount || 0);

        // Total
        t.services.total += parseFloat(line.line_total || 0);
    });

    // Parts totals
    if (props.quote?.parts) {
        props.quote.parts.forEach(part => {
            if (part.include_in_package) {
                const partPrice = parseFloat(part.unit_price || 0) * parseFloat(part.qty || 0);
                t.parts.price += partPrice;
                t.parts.discount += parseFloat(part.discount || 0);
                t.parts.total += parseFloat(part.total || 0);
            }
        });
    }

    // Grand Total
    t.grand.price = t.services.price + t.parts.price;
    t.grand.discount = t.services.discount + t.parts.discount;
    t.grand.tax = t.services.tax + t.parts.tax;
    t.grand.total = t.services.total + t.parts.total;

    return t;
});

const displayedLinesCount = computed(() => props.quote.lines?.length || 0);

// Check if quote has tax (based on quote's snapshot setting OR if quote actually has tax data)
const hasTax = computed(() => props.quote.tax_enabled_snapshot || totals.value.grand.tax > 0);

// Status Badge
const statusBadgeClass = computed(() => {
    switch (props.quote.status) {
        case 'draft': return 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300 px-3 py-1 rounded-full text-sm font-medium';
        case 'sent': return 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300 px-3 py-1 rounded-full text-sm font-medium';
        case 'approved': return 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300 px-3 py-1 rounded-full text-sm font-medium';
        case 'converted': return 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-300 px-3 py-1 rounded-full text-sm font-medium';
        default: return 'bg-gray-100 text-gray-800 px-3 py-1 rounded-full text-sm font-medium';
    }
});

// Actions
function openAddDepartmentModal() {
    showDepartmentModal.value = true;
}

function closeDepartmentModal() {
    showDepartmentModal.value = false;
}

function onDepartmentSaved() {
    closeDepartmentModal();
    // Refresh page
    router.reload();
}

// Open edit modal for quote
function openEditModal() {
    showEditModal.value = true;
}

function closeEditModal() {
    showEditModal.value = false;
}

function onQuoteSaved() {
    closeEditModal();
    router.reload();
}

async function removeDepartment(deptId) {
    const confirmed = await confirm({
        title: t('quotes.messages.confirm_remove_department_title'),
        message: t('quotes.messages.confirm_remove_department'),
        confirmText: t('common.delete'),
        cancelText: t('common.cancel'),
        type: 'danger'
    });

    if (confirmed) {
        router.delete(route('app.quotes.departments.destroy', { quote: props.quote.id, department: deptId }), {
            preserveScroll: true,
            onSuccess: () => router.reload()
        });
    }
}

function openServiceModal(deptId) {
    activeDepartmentId.value = deptId;
    editingLine.value = null;
    showServiceModal.value = true;
}

function editLine(line) {
    // Find dept for this line
    const service = props.services.find(s => s.id === line.service_id);
    activeDepartmentId.value = service?.department_id;

    editingLine.value = line;
    showServiceModal.value = true;
}

function closeServiceModal() {
    showServiceModal.value = false;
    editingLine.value = null;
    activeDepartmentId.value = null;
}

function onServiceSaved() {
    closeServiceModal();
    router.reload();
}

async function deleteLine(line) {
    const confirmed = await confirm({
        title: t('quotes.messages.confirm_delete_service_title'),
        message: t('quotes.messages.confirm_delete_service'),
        confirmText: t('common.delete'),
        cancelText: t('common.cancel'),
        type: 'danger'
    });

    if (confirmed) {
        router.delete(route('app.quotes.services.destroy', { quote: props.quote.id, line: line.id }), {
            onSuccess: () => router.reload()
        });
    }
}

// Share quote via WhatsApp or copy link
function shareQuote() {
    const url = window.location.href;
    if (navigator.share) {
        navigator.share({
            title: t('quotes.share_title', { code: props.quote.code }),
            url: url
        });
    } else {
        navigator.clipboard.writeText(url);
        // TODO: Show toast notification
        alert(t('common.link_copied'));
    }
}

// Print quote
function printQuote() {
    window.print();
}

// Delete quote
async function deleteQuote() {
    const confirmed = await confirm({
        title: t('quotes.messages.confirm_delete_title'),
        message: t('quotes.messages.confirm_delete'),
        confirmText: t('common.delete'),
        cancelText: t('common.cancel'),
        type: 'danger'
    });

    if (confirmed) {
        router.delete(route('app.quotes.destroy', props.quote.id), {
            onSuccess: () => router.visit(route('app.quotes.index'))
        });
    }
}

// ─────────────────────────────────────────────────────────────
// Parts Management
// ─────────────────────────────────────────────────────────────

function openPartModal(part = null) {
    editingPart.value = part;
    showPartModal.value = true;
}

function closePartModal() {
    showPartModal.value = false;
    editingPart.value = null;
}

function onPartSaved() {
    closePartModal();
    router.reload();
}

async function deletePart(part) {
    const confirmed = await confirm({
        title: t('quotes.messages.confirm_delete_part_title'),
        message: t('quotes.messages.confirm_delete_part'),
        confirmText: t('common.delete'),
        cancelText: t('common.cancel'),
        type: 'danger'
    });

    if (confirmed) {
        router.delete(route('app.quotes.parts.destroy', { quote: props.quote.id, quotePart: part.id }), {
            preserveScroll: true,
            onSuccess: () => router.reload()
        });
    }
}

function getSourceBadgeClass(source) {
    const classes = {
        warehouse: 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
        external: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
        customer: 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400',
    };
    return classes[source] || classes.external;
}
</script>
