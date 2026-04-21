<template>
    <AppLayout>
        <div class="space-y-6">
            <!-- Header Actions Bar -->
            <div class="flex items-center justify-between">
                <!-- Action Buttons (Left) -->
                <div class="flex items-center gap-2">
                    <Link :href="route('app.quotes.index')"
                        class="p-2 rounded-xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-gray-500 hover:text-gray-700 transition-all shadow-sm">
                        <svg class="w-5 h-5 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                    </Link>

                    <!-- Share -->
                    <button v-if="quote.status !== 'rejected'" @click="shareQuote"
                        class="p-2 rounded-xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-50 transition-all shadow-sm">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z" />
                        </svg>
                    </button>

                    <!-- Print -->
                    <button @click="printQuote"
                        class="p-2 rounded-xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-50 transition-all shadow-sm">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                        </svg>
                    </button>

                    <!-- Edit Quote -->
                    <button v-if="quote.status === 'draft' || quote.status === 'sent'" @click="openEditModal"
                        class="p-2 rounded-xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-50 transition-all shadow-sm">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                        </svg>
                    </button>

                    <!-- Approve -->
                    <button v-if="(quote.status === 'draft' || quote.status === 'sent') && (quote.lines?.length > 0 || quote.parts?.length > 0)"
                        @click="approveQuote"
                        class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-green-600 rounded-xl hover:bg-green-700 shadow-sm transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        <span>{{ $t('quotes.actions.approve') }}</span>
                    </button>
                    
                    <!-- Delete (Conditional) -->
                    <button v-if="quote.status === 'draft' || quote.status === 'sent'" @click="deleteQuote"
                        class="p-2 rounded-xl bg-white dark:bg-gray-800 border border-red-200 dark:border-red-800 text-red-600 dark:text-red-400 hover:bg-red-50 transition-all shadow-sm">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                    </button>
                </div>

                <!-- Quote Info (Right) -->
                <div class="flex items-center gap-3">
                    <div class="flex flex-col items-end">
                        <div class="flex items-center gap-3">
                            <span :class="statusBadgeClass">{{ $t(`quotes.status.${quote.status}`) }}</span>
                            <div class="flex items-center gap-2">
                                <h1 class="text-2xl font-black text-gray-900 dark:text-white tracking-tight">{{ quote.code }}</h1>
                            </div>
                            <div class="w-10 h-10 rounded-xl bg-gray-100 dark:bg-gray-800 flex items-center justify-center text-gray-500 shadow-inner">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                            </div>
                        </div>
                        <div v-if="quote.status === 'converted' && quote.converted_work_order" class="mt-1">
                             <Link :href="route('work-orders.show', quote.converted_work_order.id)" 
                                class="text-xs font-bold text-purple-600 dark:text-purple-400 bg-purple-50 dark:bg-purple-900/30 px-3 py-1 rounded-xl flex items-center gap-2 hover:bg-purple-100 transition-all border border-purple-100 dark:border-purple-800">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" /></svg>
                                {{ quote.converted_work_order.code }}
                            </Link>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Top Section: Financial Summary & Customer Info -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- 1. Right Card: Vehicle & Customer Info -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden flex flex-col h-full">

                    <!-- Gray Header: Plate + Model + Color -->
                    <Link :href="route('vehicles.show', quote.vehicle?.id)"
                        class="flex items-center justify-between bg-gray-50 dark:bg-gray-900/60 border-b border-gray-100 dark:border-gray-700 px-4 py-3 hover:bg-gray-100 dark:hover:bg-gray-800/80 transition-colors group">
                        <!-- Spacer -->
                        <div class="w-8"></div>
                        <!-- Center: Plate + Model -->
                        <div class="text-center">
                            <h3 class="text-xl font-black text-gray-900 dark:text-white tracking-widest uppercase group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors" dir="ltr">
                                {{ quote.vehicle?.plate_number }}
                            </h3>
                            <p class="text-xs font-semibold text-blue-500 dark:text-blue-400 mt-0.5">
                                {{ getName(quote.vehicle?.make) }} {{ getName(quote.vehicle?.model) }} {{ quote.vehicle?.year }}
                            </p>
                        </div>
                        <!-- Right: Color -->
                        <div class="w-8 flex items-center justify-end">
                            <div v-if="quote.vehicle?.color" class="relative group/color">
                                <div
                                    class="w-6 h-6 rounded-full border-2 border-white dark:border-gray-700 shadow-md ring-1 ring-gray-300 dark:ring-gray-600"
                                    :style="{ backgroundColor: getColorHex(quote.vehicle.color) }">
                                </div>
                                <!-- Custom Tooltip -->
                                <div class="absolute bottom-full right-0 mb-2 hidden group-hover/color:block z-50 pointer-events-none">
                                    <div class="bg-gray-900 dark:bg-gray-700 text-white text-xs font-medium px-2 py-1 rounded-lg whitespace-nowrap shadow-lg">
                                        {{ quote.vehicle.color }}
                                    </div>
                                    <div class="absolute top-full right-2 w-2 h-2 bg-gray-900 dark:bg-gray-700 rotate-45 -mt-1"></div>
                                </div>
                            </div>
                        </div>
                    </Link>

                    <!-- Middle: Logo (centered) + Customer Info -->
                    <div class="flex items-center px-5 py-4 gap-4 flex-1">
                        <!-- Logo -->
                        <div class="flex-shrink-0 w-16 h-16 flex items-center justify-center">
                            <img v-if="quote.vehicle?.make?.logo_path"
                                :src="`/storage/${quote.vehicle.make.logo_path}`"
                                :alt="getName(quote.vehicle?.make)"
                                class="w-full h-full object-contain" />
                            <div v-else class="w-14 h-14 rounded-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center">
                                <svg class="w-7 h-7 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10l3 1h6z"/>
                                </svg>
                            </div>
                        </div>

                        <!-- Customer info (right side) -->
                        <div class="flex-1 flex flex-col gap-2.5 items-end">
                            <!-- Customer Name -->
                            <Link :href="route('customers.show', quote.customer.id)"
                                class="flex items-center gap-2 text-base font-bold text-gray-800 dark:text-gray-100 hover:text-blue-600 dark:hover:text-blue-400 transition-colors">
                                <span>{{ quote.customer?.name }}</span>
                                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                </svg>
                            </Link>
                            <!-- WhatsApp Only -->
                            <a :href="`https://wa.me/${quote.customer?.phone?.replace(/\+/g, '').replace(/\s/g, '')}`"
                                target="_blank"
                                class="flex items-center gap-2 text-green-600 dark:text-green-400 hover:text-green-700 dark:hover:text-green-300 transition-colors">
                                <span class="text-sm font-mono font-semibold" dir="ltr">{{ quote.customer?.phone }}</span>
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/>
                                </svg>
                            </a>
                        </div>
                    </div>

                    <!-- Bottom: VIN + Odometer -->
                    <div class="flex items-center justify-between px-5 py-3 border-t border-gray-100 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-900/30">
                        <!-- VIN -->
                        <div class="relative group/vin">
                            <span class="text-xs font-mono text-gray-500 dark:text-gray-400 cursor-default">
                                {{ quote.vehicle?.vin || '—' }}
                            </span>
                            <div v-if="quote.vehicle?.vin" class="absolute bottom-full left-0 mb-2 hidden group-hover/vin:block z-50 pointer-events-none">
                                <div class="bg-gray-900 dark:bg-gray-700 text-white text-xs px-2 py-1 rounded-lg whitespace-nowrap shadow-lg">
                                    {{ $t('vehicles.form.vin') }}: {{ quote.vehicle.vin }}
                                </div>
                                <div class="absolute top-full left-2 w-2 h-2 bg-gray-900 dark:bg-gray-700 rotate-45 -mt-1"></div>
                            </div>
                        </div>
                        <!-- Odometer -->
                        <div class="relative group/odo flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5 text-teal-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                            </svg>
                            <span class="text-sm font-bold text-teal-600 dark:text-teal-400 font-mono cursor-default">
                                {{ Number(quote.odometer).toLocaleString() }} كم
                            </span>
                            <div class="absolute bottom-full right-0 mb-2 hidden group-hover/odo:block z-50 pointer-events-none">
                                <div class="bg-gray-900 dark:bg-gray-700 text-white text-xs px-2 py-1 rounded-lg whitespace-nowrap shadow-lg">
                                    {{ $t('work_orders.form.odometer') }}
                                </div>
                                <div class="absolute top-full right-2 w-2 h-2 bg-gray-900 dark:bg-gray-700 rotate-45 -mt-1"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 2. Left Card: Financial Summary -->
                <div
                    class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 p-4 flex flex-col h-full">
                    <h3 class="text-base font-bold text-gray-900 dark:text-white mb-3 flex items-center gap-2">
                        <div class="w-2 h-5 bg-amber-500 rounded-full"></div>
                        {{ $t('work_orders.cost_and_payment') || 'التكلفة و الدفع' }}
                    </h3>

                    <div class="overflow-x-auto flex-1">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="text-gray-400 dark:text-gray-500 border-b border-gray-100 dark:border-gray-700/50">
                                    <th class="pb-2 text-start font-bold uppercase tracking-wider">{{ $t('common.type') }}</th>
                                    <th class="pb-2 text-end font-bold uppercase tracking-wider">{{ $t('work_orders.price') }}</th>
                                    <th class="pb-2 text-end font-bold uppercase tracking-wider text-red-500 italic">{{ $t('work_orders.discount') }}</th>
                                    <th class="pb-2 text-end font-bold uppercase tracking-wider">{{ $t('common.amount') }}</th>
                                    <th v-if="hasTax" class="pb-2 text-end font-bold uppercase tracking-wider italic">VAT (15%)</th>
                                    <th class="pb-2 text-end font-bold uppercase tracking-wider tracking-widest text-gray-900 dark:text-white">{{ $t('common.total') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50 dark:divide-gray-700/30">
                                <!-- Services Row -->
                                <tr class="group">
                                    <td class="py-1.5 font-bold text-gray-900 dark:text-white">{{ $t('quotes.show.tabs.services') }}</td>
                                    <td class="py-1.5 text-end font-mono text-gray-500">{{ formatCurrency(totals.services.price) }}</td>
                                    <td class="py-1.5 text-end font-mono text-red-500 italic">{{ totals.services.discount > 0 ? '-' + formatCurrency(totals.services.discount) : '0.00' }}</td>
                                    <td class="py-1.5 text-end font-mono text-gray-500">{{ formatCurrency(totals.services.amount) }}</td>
                                    <td v-if="hasTax" class="py-1.5 text-end font-mono text-gray-500">{{ formatCurrency(totals.services.tax) }}</td>
                                    <td class="py-1.5 text-end font-black text-gray-900 dark:text-white font-mono text-base">{{ formatCurrency(totals.services.total) }}</td>
                                </tr>

                                <!-- Parts Row -->
                                <tr class="group">
                                    <td class="py-1.5 font-bold text-gray-900 dark:text-white">{{ $t('quotes.show.tabs.spare_parts') }}</td>
                                    <td class="py-1.5 text-end font-mono text-gray-500">{{ formatCurrency(totals.parts.price) }}</td>
                                    <td class="py-1.5 text-end font-mono text-red-500 italic">{{ totals.parts.discount > 0 ? '-' + formatCurrency(totals.parts.discount) : '0.00' }}</td>
                                    <td class="py-1.5 text-end font-mono text-gray-500">{{ formatCurrency(totals.parts.amount) }}</td>
                                    <td v-if="hasTax" class="py-1.5 text-end font-mono text-gray-500">{{ formatCurrency(totals.parts.tax) }}</td>
                                    <td class="py-1.5 text-end font-black text-gray-900 dark:text-white font-mono text-base">{{ formatCurrency(totals.parts.total) }}</td>
                                </tr>

                                <!-- Grand Total Row -->
                                <tr class="bg-amber-50 dark:bg-amber-900/10 font-black border-t-2 border-amber-200 dark:border-amber-800">
                                    <td class="py-1.5 text-amber-900 dark:text-amber-400 font-black uppercase">{{ $t('common.total') }}</td>
                                    <td class="py-1.5 text-end font-mono text-amber-700 dark:text-amber-300">{{ formatCurrency(totals.grand.price) }}</td>
                                    <td class="py-1.5 text-end font-mono text-red-600 italic">{{ totals.grand.discount > 0 ? '-' + formatCurrency(totals.grand.discount) : '0.00' }}</td>
                                    <td class="py-1.5 text-end font-mono text-amber-700 dark:text-amber-300">{{ formatCurrency(totals.grand.amount) }}</td>
                                    <td v-if="hasTax" class="py-1.5 text-end font-mono text-amber-700 dark:text-amber-300">{{ formatCurrency(totals.grand.tax) }}</td>
                                    <td class="py-1.5 text-end font-black text-amber-600 dark:text-amber-400 font-mono text-xl">{{ formatCurrency(totals.grand.total) }}</td>
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
                <button v-if="activeTab === 'services' && (quote.status === 'draft' || quote.status === 'sent')" @click="openAddDepartmentModal"
                    class="flex items-center gap-2 px-4 py-2 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 rounded-lg transition-colors text-sm font-medium">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                    </svg>
                    {{ $t('quotes.show.add_department') }}
                </button>
                <button v-if="activeTab === 'parts' && (quote.status === 'draft' || quote.status === 'sent')" @click="openPartModal()"
                    class="flex items-center gap-2 px-4 py-2 bg-green-500 hover:bg-green-600 text-white rounded-lg transition-colors text-sm font-medium">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                    </svg>
                    {{ $t('quotes.show.add_part') }}
                </button>
            </div>

            <!-- Services Tab Content -->
            <div v-show="activeTab === 'services'" id="departments-section" class="space-y-3">
                <template v-for="dept in visibleDepartments" :key="dept.id">
                    <div class="border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden">
                        <!-- Department Header - matching WorkOrder style -->
                        <div class="w-full flex items-center justify-between px-4 py-3 bg-gradient-to-r from-gray-50 to-transparent dark:from-gray-900/50 dark:to-transparent">
                            <div class="flex items-center gap-3 flex-1">
                                <div class="w-8 h-8 rounded-lg bg-indigo-100 dark:bg-indigo-900/30 flex items-center justify-center">
                                    <span class="text-indigo-600 dark:text-indigo-400">🔧</span>
                                </div>
                                <span class="font-semibold text-gray-900 dark:text-white">{{ getName(dept) }}</span>
                                <span class="text-xs text-gray-400 bg-gray-100 dark:bg-gray-700 px-2 py-0.5 rounded-full">
                                    {{ getLinesForDept(dept.id).length }} {{ $t('quotes.show.services_count') }}
                                </span>
                            </div>

                            <div class="flex items-center gap-2">
                                <!-- Delete Dept Button (Conditional) -->
                                <button
                                    v-if="(quote.status === 'draft' || quote.status === 'sent') && getLinesForDept(dept.id).length === 0"
                                    @click.stop="removeDepartment(dept.id)"
                                    class="w-7 h-7 rounded-lg hover:bg-red-50 hover:text-red-500 dark:hover:bg-red-900/20 dark:hover:text-red-400 text-gray-400 flex items-center justify-center transition-colors"
                                    :title="$t('quotes.confirm_remove_department')">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <!-- Department Content -->
                        <div class="p-4 space-y-2 bg-gray-50/50 dark:bg-gray-900/30">
                            <!-- Services List -->
                            <div class="flex flex-col gap-3">
                                <div v-for="(line, index) in getLinesForDept(dept.id)" :key="line.id"
                                    class="bg-white dark:bg-gray-800 rounded-lg border border-gray-100 dark:border-gray-700 p-4 transition-all hover:shadow-md hover:border-gray-200 dark:hover:border-gray-600 group relative">
                                    <div class="flex items-center justify-between">
                                        <!-- Right Side: Icon + Title + Price -->
                                        <div class="flex items-start gap-4 flex-1 min-w-0">
                                            <!-- Status Icon -->
                                            <div class="mt-1 flex-shrink-0 w-10 h-10 rounded-full bg-indigo-50 dark:bg-indigo-900/20 text-indigo-500 flex items-center justify-center">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                </svg>
                                            </div>

                                            <div class="flex-1 min-w-0">
                                                <!-- Title Row -->
                                                <div class="flex items-baseline gap-2 mb-1">
                                                    <span class="text-gray-400 font-medium font-mono text-sm leading-none">{{ index + 1 }}.</span>
                                                    <button @click="editLine(line)" type="button"
                                                        class="font-bold text-gray-900 dark:text-white text-base hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors text-start leading-tight">
                                                        {{ line.description || getName(line.service) }}
                                                    </button>
                                                </div>

                                                <!-- Meta Row: Price badge -->
                                                <div class="flex items-center gap-3 text-sm text-gray-500 dark:text-gray-400">
                                                    <!-- Prices Group -->
                                                    <div class="flex items-center gap-3 bg-gray-50 dark:bg-gray-700/50 px-2 py-1 rounded-md">
                                                        <!-- Labor/Service cost -->
                                                        <div class="flex items-center gap-1.5 text-gray-700 dark:text-gray-300 font-medium" :title="$t('work_orders.item.service_cost')">
                                                            <span class="text-indigo-500">🔧</span>
                                                            <span class="font-mono">{{ formatCurrency((line.unit_price * (line.qty || 1)) - (line.discount_amount || 0)) }}</span>
                                                        </div>
                                                        <!-- Parts (if any) -->
                                                        <div v-if="line.parts_total > 0" class="flex items-center gap-1.5 text-gray-700 dark:text-gray-300 font-medium border-s border-gray-200 dark:border-gray-600 ps-3" :title="$t('work_orders.item.parts_cost')">
                                                            <span class="text-amber-500">🔩</span>
                                                            <span class="font-mono">{{ formatCurrency(line.parts_total) }}</span>
                                                        </div>
                                                    </div>

                                                    <!-- Discount indicator -->
                                                    <span v-if="line.discount_amount > 0" class="text-xs text-red-500 line-through font-mono">
                                                        {{ formatCurrency(line.unit_price * line.qty) }}
                                                    </span>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Left Side: Actions -->
                                        <div v-if="quote.status === 'draft' || quote.status === 'sent'" class="flex items-center gap-1 border-s border-gray-100 dark:border-gray-700 ps-3">
                                            <button @click="editLine(line)"
                                                class="p-2 text-gray-400 hover:text-indigo-600 hover:bg-indigo-50 dark:hover:bg-indigo-900/30 rounded-lg transition-colors"
                                                :title="$t('common.edit')">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                                                </svg>
                                            </button>
                                            <button @click="deleteLine(line)"
                                                class="p-2 text-gray-400 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors"
                                                :title="$t('common.delete')">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                </svg>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Empty State -->
                            <p v-if="getLinesForDept(dept.id).length === 0"
                                class="text-center text-gray-400 dark:text-gray-500 py-4 text-sm">
                                {{ $t('quotes.show.no_services') }}
                            </p>

                            <!-- Add Service Button (dashed, matching WorkOrder style) -->
                            <button v-if="quote.status === 'draft' || quote.status === 'sent'" @click="openServiceModal(dept.id)"
                                class="w-full flex items-center justify-center gap-2 py-2 text-sm text-indigo-600 dark:text-indigo-400 border border-dashed border-indigo-300 dark:border-indigo-700 rounded-lg hover:bg-indigo-50 dark:hover:bg-indigo-900/20 transition-colors mt-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                </svg>
                                {{ $t('quotes.show.add_service') }}
                            </button>
                        </div>
                    </div>
                </template>
            </div>

            <!-- Parts Tab Content -->
            <div v-show="activeTab === 'parts'" id="parts-section" class="space-y-4">
                <PartsDisplay :parts="quote.parts || []" :show-vat="hasTax" storage-key="quotes_parts_view_mode"
                    :read-only="quote.status !== 'draft' && quote.status !== 'sent'"
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

        <!-- Share Modal -->
        <QuoteShareModal 
            :show="showShareModal" 
            :quote="quote" 
            @close="showShareModal = false"
            @print="printQuote"
        />
    </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { useLocalized } from '@/Composables/useLocalized';
import { useNumberFormat } from '@/Composables/useNumberFormat';
import { useConfirm } from '@/Composables/useConfirm';
import { useToast } from '@/Composables/useToast';
import { usePermission } from '@/Composables/usePermission';
import AppLayout from '@/Layouts/AppLayout.vue';
import QuoteServiceModal from '@/Components/Quotes/QuoteServiceModal.vue';
import QuoteDepartmentModal from '@/Components/Quotes/QuoteDepartmentModal.vue';
import QuoteFormModal from '@/Components/Quotes/QuoteFormModal.vue';
import QuotePartModal from '@/Components/Quotes/QuotePartModal.vue';
import QuoteShareModal from '@/Components/Quotes/QuoteShareModal.vue';
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

function getColorHex(colorName) {
    if (!colorName) return '#9ca3af';
    const trimmedColor = colorName.trim();
    if (props.colors && props.colors.length > 0) {
        const found = props.colors.find(c =>
            c.name_ar === trimmedColor ||
            (c.name_en && c.name_en.toLowerCase() === trimmedColor.toLowerCase()) ||
            c.name === trimmedColor
        );
        if (found && found.hex_code) {
            return found.hex_code.startsWith('#') ? found.hex_code : `#${found.hex_code}`;
        }
    }
    const colorMap = {
        'أبيض': '#ffffff', 'أسود': '#111827', 'فضي': '#9ca3af', 'رمادي': '#4b5563', 'أحمر': '#dc2626',
        'أزرق': '#2563eb', 'أخضر': '#16a34a', 'ذهبي': '#ca8a04', 'بني': '#78350f', 'برتقالي': '#ea580c',
        'white': '#ffffff', 'black': '#111827', 'silver': '#9ca3af', 'gray': '#4b5563', 'red': '#dc2626',
        'blue': '#2563eb', 'green': '#16a34a', 'gold': '#ca8a04', 'brown': '#78350f', 'orange': '#ea580c',
    };
    return colorMap[trimmedColor.toLowerCase()] || colorMap[trimmedColor] || '#9ca3af';
}

// State
const showServiceModal = ref(false);
const showDepartmentModal = ref(false);
const showEditModal = ref(false);
const showPartModal = ref(false);
const showShareModal = ref(false);
const editingLine = ref(null);
const editingPart = ref(null);
const activeDepartmentId = ref(null);
const activeTab = ref('services'); // 'services' or 'parts'

const { success: successToast, error: errorToast } = useToast();

const approveQuote = async () => {
    const confirmed = await confirm({
        title: t('quotes.messages.confirm_approve_title'),
        message: t('quotes.messages.confirm_approve'),
        confirmText: t('quotes.actions.approve'),
        type: 'success'
    });

    if (confirmed) {
        router.post(route('app.quotes.approve', props.quote.id), {}, {
            onSuccess: () => successToast(t('quotes.messages.approved_success')),
            onError: (err) => errorToast(err.message || t('common.error'))
        });
    }
};

// Computed: Sort departments by sort_order
const visibleDepartments = computed(() =>
    [...(props.quoteDepartments || [])].sort((a, b) => a.sort_order - b.sort_order)
);

// Computed: Get active department's services for the modal dropdown
const activeDepartmentServices = computed(() => {
    if (!activeDepartmentId.value) return [];
    return (props.services || []).filter(s => s.department_id === activeDepartmentId.value);
});

// Helper: Get lines for a specific department
const getLinesForDept = (deptId) => {
    const lines = (props.linesByDepartment ?? {})[deptId];
    return Array.isArray(lines) ? lines : [];
};

// Computed: Financial Totals Breakdown
const totals = computed(() => {
    const t = {
        services: { price: 0, discount: 0, amount: 0, tax: 0, total: 0 },
        parts: { price: 0, discount: 0, amount: 0, tax: 0, total: 0 },
        grand: { price: 0, discount: 0, amount: 0, tax: 0, total: 0 },
    };

    const isInclusive = props.quote?.pricing_mode_snapshot === 'inclusive';
    const taxRate = Number(props.quote?.tax_rate_snapshot || 15);
    const taxFactor = 1 + (taxRate / 100);
    const taxEnabled = !!props.quote?.tax_enabled_snapshot;

    // Services calculation
    if (props.quote?.lines && props.quote.lines.length > 0) {
        props.quote.lines.forEach(line => {
            const price = Number(line.unit_price || 0) * Number(line.qty || 1);
            const discount = Number(line.discount_amount || 0);
            
            let amount = price - discount;
            let calculatedTax = Number(line.tax_amount || 0);
            let total = Number(line.line_total || price);

            if (taxEnabled) {
                if (isInclusive) {
                    amount = (price - discount) / taxFactor;
                    calculatedTax = (price - discount) - amount;
                } else if (calculatedTax === 0) {
                    calculatedTax = amount * (taxRate / 100);
                    total = amount + calculatedTax;
                }
            }

            if (!line.line_total) {
                total = isInclusive ? (price - discount) : (amount + calculatedTax);
            }

            t.services.price += price;
            t.services.discount += discount;
            t.services.tax += calculatedTax;
            t.services.total += total;
            t.services.amount += amount;
        });
    }

    // Parts calculation
    if (props.quote?.parts && props.quote.parts.length > 0) {
        props.quote.parts.forEach(part => {
            const qty = Number(part.qty || 0);
            const unitPrice = Number(part.unit_price || 0);
            const discount = Number(part.discount || 0);
            
            const partPrice = qty * unitPrice;
            const partNet = partPrice - discount;

            t.parts.price += partPrice;
            t.parts.discount += discount;

            if (taxEnabled) {
                if (isInclusive) {
                    const amount = Number(part.total_excl_tax || (partNet / taxFactor));
                    const tax = Number(part.tax_amount || (partNet - amount));
                    t.parts.amount += amount;
                    t.parts.tax += tax;
                    t.parts.total += partNet;
                } else {
                    const tax = Number(part.tax_amount || (partNet * (taxRate / 100)));
                    t.parts.amount += partNet;
                    t.parts.tax += tax;
                    t.parts.total += (partNet + tax);
                }
            } else {
                t.parts.amount += partNet;
                t.parts.total += Number(part.total_incl_tax || part.total || partNet);
            }
        });
    }

    // Grand Totals sync
    t.grand.price = t.services.price + t.parts.price;
    t.grand.discount = t.services.discount + t.parts.discount;
    t.grand.amount = t.services.amount + t.parts.amount;
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

// Share quote via custom modal
function shareQuote() {
    showShareModal.value = true;
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

</script>
