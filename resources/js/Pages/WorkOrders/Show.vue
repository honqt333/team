<template>
    <AppLayout>
        <div class="space-y-6">
            <!-- Header Actions Bar -->
            <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6">
                <!-- Left side: Back and Main Actions -->
                <div class="flex flex-wrap items-center gap-3">
                    <Link :href="backUrl"
                        :title="$t('common.back')"
                        class="p-2.5 bg-white/80 dark:bg-gray-800/80 backdrop-blur-md rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm hover:shadow-md hover:border-indigo-200 transition-all duration-300 text-indigo-600">
                        <svg class="w-5 h-5 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                    </Link>

                    <div class="flex items-center gap-1.5 p-1.5 bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm">
                        <!-- Print Button -->
                        <button @click="showPrintModal = true"
                            class="p-2.5 text-gray-500 hover:text-indigo-600 hover:bg-indigo-50 dark:hover:bg-indigo-900/20 rounded-xl transition-all"
                            :title="$t('common.print')">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                            </svg>
                        </button>

                        <!-- Payments Button -->
                        <button v-if="!isReadOnly" @click="showPaymentsListModal = true"
                            class="p-2.5 text-gray-500 hover:text-indigo-600 hover:bg-indigo-50 dark:hover:bg-indigo-900/20 rounded-xl transition-all"
                            :title="$t('payments.title')">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                            </svg>
                        </button>

                        <div class="w-px h-6 bg-gray-200 dark:bg-gray-700 mx-1"></div>

                        <!-- Edit Button -->
                        <button v-if="!isReadOnly" @click="showEditModal = true"
                            class="p-2.5 text-gray-500 hover:text-indigo-600 hover:bg-indigo-50 dark:hover:bg-indigo-900/20 rounded-xl transition-all"
                            :title="$t('common.edit')">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                            </svg>
                        </button>
                    </div>

                    <!-- Status Change Buttons -->
                    <div v-if="!isReadOnly" class="flex items-center gap-2">
                        <template v-if="workOrder.status === 'open'">
                            <button @click="changeStatus('in_progress')"
                                class="flex items-center gap-2 px-6 py-2.5 bg-amber-500 text-white rounded-2xl font-bold shadow-lg shadow-amber-500/25 hover:shadow-amber-500/40 hover:-translate-y-0.5 transition-all">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                        d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                                </svg>
                                <span>{{ $t('work_orders.actions.start_work') }}</span>
                            </button>
                        </template>
                        <template v-if="workOrder.status === 'in_progress'">
                            <button @click="changeStatus('done')"
                                class="flex items-center gap-2 px-6 py-2.5 bg-emerald-600 text-white rounded-2xl font-bold shadow-lg shadow-emerald-500/25 hover:shadow-emerald-500/40 hover:-translate-y-0.5 transition-all">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                        d="M5 13l4 4L19 7" />
                                </svg>
                                <span>{{ $t('work_orders.actions.complete') }}</span>
                            </button>
                        </template>
                        <button v-if="workOrder.status !== 'done' && workOrder.status !== 'cancelled'" @click="changeStatus('cancelled')"
                            class="p-2.5 rounded-2xl bg-white dark:bg-gray-800 border border-red-200 dark:border-red-800 text-red-600 dark:text-red-400 hover:bg-red-50 transition-all shadow-sm"
                            :title="$t('work_orders.actions.cancel')">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Right side: Code and Status -->
                <div class="flex items-center gap-4">
                    <div class="text-end">
                        <div class="flex items-center gap-3">
                            <span :class="statusBadgeClass" class="px-3 py-1 rounded-full text-xs font-black uppercase tracking-widest">{{ $t(`work_orders.status.${workOrder.status}`) }}</span>
                            <h1 class="text-3xl font-black text-gray-900 dark:text-white tracking-tight">{{ workOrder.code }}</h1>
                            <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-indigo-600 to-purple-600 shadow-xl shadow-indigo-500/30 flex items-center justify-center text-white shrink-0">
                                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Top Section: Financial Summary & Customer Info -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- 1. Right Card: Vehicle & Customer Info -->
                <div
                    class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 flex flex-col h-full">
                    <!-- Gray Header: Plate + Model + Color -->
                    <Link :href="route('vehicles.show', workOrder.vehicle?.id || 1)"
                        class="flex items-center justify-between bg-gray-50 dark:bg-gray-900/60 border-b border-gray-100 dark:border-gray-700 px-4 py-3 hover:bg-gray-100 dark:hover:bg-gray-800/80 transition-colors group rounded-t-2xl">
                        <!-- Spacer -->
                        <div class="w-8"></div>
                        <!-- Center: Plate + Model -->
                        <div class="text-center">
                            <h3 class="text-xl font-black text-gray-900 dark:text-white tracking-widest uppercase group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors"
                                dir="ltr">
                                {{ workOrder.vehicle?.plate_number }}
                            </h3>
                            <p class="text-xs font-semibold text-blue-500 dark:text-blue-400 mt-0.5">
                                {{ getName(workOrder.vehicle?.make) }} {{ getName(workOrder.vehicle?.model) }} {{
                                    workOrder.vehicle?.year }}
                            </p>
                        </div>
                        <!-- Right: Color -->
                        <div class="w-8 flex items-center justify-end">
                            <div v-if="workOrder.vehicle?.color" class="relative group/color">
                                <div class="w-6 h-6 rounded-full border-2 border-white dark:border-gray-700 shadow-md ring-1 ring-gray-300 dark:ring-gray-600"
                                    :style="{ backgroundColor: getColorHex(workOrder.vehicle.color) }">
                                </div>
                                <!-- Custom Tooltip -->
                                <div
                                    class="absolute bottom-full right-0 mb-2 hidden group-hover/color:block z-50 pointer-events-none">
                                    <div
                                        class="bg-gray-900 dark:bg-gray-700 text-white text-xs font-medium px-2 py-1 rounded-lg whitespace-nowrap shadow-lg">
                                        {{ workOrder.vehicle.color }}
                                    </div>
                                    <div
                                        class="absolute top-full right-2 w-2 h-2 bg-gray-900 dark:bg-gray-700 rotate-45 -mt-1">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </Link>

                    <!-- Middle: Logo (centered) + Customer Info -->
                    <div class="flex items-center px-5 py-4 gap-4 flex-1">
                        <!-- Logo -->
                        <div class="flex-shrink-0 w-16 h-16 flex items-center justify-center">
                            <img v-if="workOrder.vehicle?.make?.logo_path"
                                :src="`/storage/${workOrder.vehicle.make.logo_path}`"
                                :alt="getName(workOrder.vehicle?.make)" class="w-full h-full object-contain" />
                            <div v-else
                                class="w-14 h-14 rounded-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center">
                                <svg class="w-7 h-7 text-gray-400" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                        d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                        d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10l3 1h6z" />
                                </svg>
                            </div>
                        </div>

                        <!-- Customer info (right side) -->
                        <div class="flex-1 flex flex-col gap-2.5 items-end">
                            <!-- Customer Name -->
                            <div class="relative group/customer">
                                <Link :href="route('customers.show', workOrder.customer?.id || 1)"
                                    class="flex items-center justify-end gap-2 text-base font-bold text-gray-800 dark:text-gray-100 hover:text-blue-600 dark:hover:text-blue-400 transition-colors">
                                    <span>{{ workOrder.customer?.name }}</span>
                                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                    </svg>
                                </Link>
                                <div
                                    class="absolute bottom-full right-0 mb-2 hidden group-hover/customer:block z-50 pointer-events-none">
                                    <div
                                        class="bg-gray-900 dark:bg-gray-700 text-white text-xs font-medium px-2 py-1 rounded-lg whitespace-nowrap shadow-lg">
                                        {{ $t('customers.view_profile') || 'عرض ملف العميل' }}
                                    </div>
                                    <div
                                        class="absolute top-full right-4 w-2 h-2 bg-gray-900 dark:bg-gray-700 rotate-45 -mt-1">
                                    </div>
                                </div>
                            </div>

                            <!-- Contact Name (if exists) -->
                            <div v-if="workOrder.contact_name"
                                class="relative group/contact flex items-center justify-end gap-1.5 text-xs text-gray-500 font-medium">
                                <span>{{ workOrder.contact_name }}</span>
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                </svg>
                                <div
                                    class="absolute bottom-full right-0 mb-2 hidden group-hover/contact:block z-50 pointer-events-none">
                                    <div
                                        class="bg-gray-900 dark:bg-gray-700 text-white text-xs font-medium px-2 py-1 rounded-lg whitespace-nowrap shadow-lg">
                                        {{ $t('work_orders.form.contact_name') || 'اسم جهة الاتصال' }}
                                    </div>
                                    <div
                                        class="absolute top-full right-4 w-2 h-2 bg-gray-900 dark:bg-gray-700 rotate-45 -mt-1">
                                    </div>
                                </div>
                            </div>

                            <!-- WhatsApp Only -->
                            <div class="relative group/whatsapp">
                                <a :href="`https://wa.me/${(workOrder.contact_phone || workOrder.customer?.phone)?.replace(/\+/g, '').replace(/\s/g, '')}`"
                                    target="_blank"
                                    class="flex items-center justify-end gap-2 text-green-600 dark:text-green-400 hover:text-green-700 dark:hover:text-green-300 transition-colors">
                                    <span class="text-sm font-mono font-semibold" dir="ltr">{{ workOrder.contact_phone
                                        ||
                                        workOrder.customer?.phone }}</span>
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                        <path
                                            d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z" />
                                    </svg>
                                </a>
                                <div
                                    class="absolute bottom-full right-0 mb-2 hidden group-hover/whatsapp:block z-50 pointer-events-none">
                                    <div
                                        class="bg-gray-900 dark:bg-gray-700 text-white text-xs font-medium px-2 py-1 rounded-lg whitespace-nowrap shadow-lg">
                                        {{ $t('common.open_in_whatsapp') || 'فتح باستخدام الواتساب' }}
                                    </div>
                                    <div
                                        class="absolute top-full right-4 w-2 h-2 bg-gray-900 dark:bg-gray-700 rotate-45 -mt-1">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Bottom: VIN + Odometer -->
                    <div
                        class="flex items-center justify-between px-5 py-3 border-t border-gray-100 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-900/30 rounded-b-2xl">
                        <!-- VIN -->
                        <div class="relative group/vin">
                            <span class="text-xs font-mono text-gray-500 dark:text-gray-400 cursor-default">
                                {{ workOrder.vehicle?.vin || '—' }}
                            </span>
                            <div v-if="workOrder.vehicle?.vin"
                                class="absolute bottom-full left-0 mb-2 hidden group-hover/vin:block z-50 pointer-events-none">
                                <div
                                    class="bg-gray-900 dark:bg-gray-700 text-white text-xs px-2 py-1 rounded-lg whitespace-nowrap shadow-lg">
                                    {{ $t('vehicles.form.vin') }}: {{ workOrder.vehicle.vin }}
                                </div>
                                <div
                                    class="absolute top-full left-2 w-2 h-2 bg-gray-900 dark:bg-gray-700 rotate-45 -mt-1">
                                </div>
                            </div>
                        </div>
                        <!-- Odometer -->
                        <div class="relative group/odo flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5 text-teal-500" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M13 10V3L4 14h7v7l9-11h-7z" />
                            </svg>
                            <span class="text-sm font-bold text-teal-600 dark:text-teal-400 font-mono cursor-default"
                                dir="ltr">
                                {{ workOrder.odometer ? Number(workOrder.odometer).toLocaleString() + ' km' : '—' }}
                            </span>
                            <div
                                class="absolute bottom-full right-0 mb-2 hidden group-hover/odo:block z-50 pointer-events-none">
                                <div
                                    class="bg-gray-900 dark:bg-gray-700 text-white text-xs px-2 py-1 rounded-lg whitespace-nowrap shadow-lg">
                                    {{ $t('work_orders.form.mileage') }}
                                </div>
                                <div
                                    class="absolute top-full right-2 w-2 h-2 bg-gray-900 dark:bg-gray-700 rotate-45 -mt-1">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 2. Left Card: Financial Summary (Match mockup) -->
                <div
                    class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 p-4 flex flex-col h-full">
                    <h3 class="text-base font-bold text-gray-900 dark:text-white mb-3 flex items-center gap-2">
                        <div class="w-2 h-5 bg-amber-500 rounded-full"></div>
                        {{ $t('work_orders.cost_and_payment') || 'التكلفة و الدفع' }}
                    </h3>

                    <div class="overflow-x-auto flex-1">
                        <table class="w-full text-sm">
                            <thead>
                                <tr
                                    class="text-gray-400 dark:text-gray-500 border-b border-gray-100 dark:border-gray-700/50">
                                    <th class="pb-2 text-start font-bold uppercase tracking-wider">{{ $t('common.type')
                                        }}</th>
                                    <th class="pb-2 text-end font-bold uppercase tracking-wider">{{
                                        $t('work_orders.price') }}</th>
                                    <th class="pb-2 text-end font-bold uppercase tracking-wider text-red-500 italic">{{
                                        $t('work_orders.discount') }}</th>
                                    <th class="pb-2 text-end font-bold uppercase tracking-wider">{{ $t('common.amount')
                                        }}</th>
                                    <th v-if="hasTax" class="pb-2 text-end font-bold uppercase tracking-wider italic">
                                        VAT (15%)</th>
                                    <th
                                        class="pb-2 text-end font-bold uppercase tracking-wider tracking-widest text-gray-900 dark:text-white">
                                        {{ $t('common.total') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50 dark:divide-gray-700/30">
                                <!-- Services Row -->
                                <tr class="group">
                                    <td class="py-1.5 font-bold text-gray-900 dark:text-white">{{
                                        $t('work_orders.services_total')
                                        }}</td>
                                    <td class="py-1.5 text-end font-mono text-gray-500">{{
                                        formatCurrency(totals.services.price) }}
                                    </td>
                                    <td class="py-1.5 text-end font-mono text-red-500 italic">{{
                                        totals.services.discount > 0 ? '-'
                                            + formatCurrency(totals.services.discount) : '0.00' }}</td>
                                    <td class="py-1.5 text-end font-mono text-gray-500">{{
                                        formatCurrency(totals.services.amount) }}
                                    </td>
                                    <td v-if="hasTax" class="py-1.5 text-end font-mono text-gray-500">{{
                                        formatCurrency(totals.services.tax) }}</td>
                                    <td
                                        class="py-1.5 text-end font-black text-gray-900 dark:text-white font-mono text-base">
                                        {{
                                            formatCurrency(totals.services.total) }}</td>
                                </tr>

                                <!-- Parts Row -->
                                <tr class="group" v-if="totals.parts.total > 0">
                                    <td class="py-1.5 font-bold text-gray-900 dark:text-white">{{
                                        $t('work_orders.parts_total') }}
                                    </td>
                                    <td class="py-1.5 text-end font-mono text-gray-500">{{
                                        formatCurrency(totals.parts.price) }}
                                    </td>
                                    <td class="py-1.5 text-end font-mono text-red-500 italic">{{ totals.parts.discount >
                                        0 ? '-' +
                                    formatCurrency(totals.parts.discount) : '0.00' }}</td>
                                    <td class="py-1.5 text-end font-mono text-gray-500">{{
                                        formatCurrency(totals.parts.amount) }}
                                    </td>
                                    <td v-if="hasTax" class="py-1.5 text-end font-mono text-gray-500">{{
                                        formatCurrency(totals.parts.tax) }}</td>
                                    <td
                                        class="py-1.5 text-end font-black text-gray-900 dark:text-white font-mono text-base">
                                        {{
                                            formatCurrency(totals.parts.total) }}</td>
                                </tr>

                                <!-- Financial Totals -->
                                <tr
                                    class="bg-gray-50/50 dark:bg-gray-900/30 font-black border-t-2 border-gray-100 dark:border-gray-700">
                                    <td class="py-1.5 text-gray-900 dark:text-white ps-2 uppercase">{{
                                        $t('work_orders.total') }}
                                    </td>
                                    <td class="py-1.5 text-end font-mono text-gray-500">{{
                                        formatCurrency(totals.grand.price) }}
                                    </td>
                                    <td class="py-1.5 text-end font-mono text-red-600 italic">{{ totals.grand.discount >
                                        0 ? '-' +
                                    formatCurrency(totals.grand.discount) : '0.00' }}</td>
                                    <td class="py-1.5 text-end font-mono text-gray-500">{{
                                        formatCurrency(totals.grand.amount) }}
                                    </td>
                                    <td v-if="hasTax" class="py-1.5 text-end font-mono text-gray-500">{{
                                        formatCurrency(totals.grand.tax) }}</td>
                                    <td
                                        class="py-1.5 text-end font-black text-indigo-600 dark:text-indigo-400 font-mono text-lg">
                                        {{
                                            formatCurrency(totals.grand.total) }}</td>
                                </tr>

                                <!-- Paid Row -->
                                <tr class="bg-gray-50/50 dark:bg-gray-900/30">
                                    <td class="py-1.5 font-black text-green-600 dark:text-green-500 ps-2"
                                        :colspan="hasTax ? 5 : 4">
                                        {{ $t('work_orders.paid') }}</td>
                                    <td
                                        class="py-1.5 text-end font-black text-green-600 dark:text-green-500 font-mono text-base">
                                        {{
                                            formatCurrency(workOrderTotalPaid) }}</td>
                                </tr>

                                <!-- Balance Row -->
                                <tr class="bg-gray-50/50 dark:bg-gray-900/30 border-b-0">
                                    <td class="py-1.5 font-black ps-2"
                                        :class="workOrderBalance > 0 ? 'text-orange-600 dark:text-orange-500' : 'text-gray-500'"
                                        :colspan="hasTax ? 5 : 4">{{ $t('work_orders.balance') }}</td>
                                    <td class="py-1.5 text-end font-black font-mono text-base"
                                        :class="workOrderBalance > 0 ? 'text-orange-600 dark:text-orange-500' : 'text-gray-500'">
                                        {{
                                            formatCurrency(workOrderBalance) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Main Content Container with Info Cards -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700">
                <!-- Info Cards -->
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                        <!-- Entry Date -->
                        <div class="bg-gray-50 dark:bg-gray-900/50 rounded-xl p-3">
                            <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">{{
                                $t('work_orders.form.entry_date') }}</p>
                            <p class="font-medium text-gray-900 dark:text-white flex items-center gap-2">
                                <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                                {{ formatDate(workOrder.entry_date) }}
                            </p>
                        </div>
                        <!-- Expected End -->
                        <div class="bg-gray-50 dark:bg-gray-900/50 rounded-xl p-3">
                            <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">{{
                                $t('work_orders.form.expected_end_date') }}
                            </p>
                            <p class="font-medium text-gray-900 dark:text-white flex items-center gap-2">
                                <svg class="w-4 h-4 text-amber-500" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                {{ formatDate(workOrder.expected_end_date) }}
                            </p>
                        </div>
                        <!-- Duration / Status -->
                        <div class="bg-gray-50 dark:bg-gray-900/50 rounded-xl p-3">
                            <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">{{ $t('work_orders.duration') }}
                            </p>
                            <p class="font-medium flex items-center gap-2"
                                :class="durationInfo.isOverdue ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400'">
                                <svg v-if="durationInfo.isOverdue" class="w-4 h-4" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                {{ durationInfo.text }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Customer Complaint & Initial Assessment -->
                <div v-if="workOrder.customer_complaint || workOrder.initial_assessment"
                    class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Customer Complaint -->
                        <div v-if="workOrder.customer_complaint"
                            class="bg-orange-50 dark:bg-orange-900/20 rounded-xl p-4 border border-orange-200 dark:border-orange-700/50">
                            <div class="flex items-center gap-2 mb-2">
                                <span class="text-lg">💬</span>
                                <h4 class="font-semibold text-orange-700 dark:text-orange-300">{{
                                    $t('quotes.show.customer_complaint') }}</h4>
                            </div>
                            <p class="text-sm text-gray-700 dark:text-gray-300 whitespace-pre-wrap">{{
                                workOrder.customer_complaint
                                }}</p>
                        </div>

                        <!-- Initial Assessment -->
                        <div v-if="workOrder.initial_assessment"
                            class="bg-teal-50 dark:bg-teal-900/20 rounded-xl p-4 border border-teal-200 dark:border-teal-700/50">
                            <div class="flex items-center gap-2 mb-2">
                                <span class="text-lg">📋</span>
                                <h4 class="font-semibold text-teal-700 dark:text-teal-300">{{
                                    $t('quotes.show.initial_assessment')
                                    }}</h4>
                            </div>
                            <p class="text-sm text-gray-700 dark:text-gray-300 whitespace-pre-wrap">{{
                                workOrder.initial_assessment
                                }}</p>
                        </div>
                    </div>
                </div>

                <!-- Tabs -->
                <div class="px-6 pt-4">
                    <div class="flex gap-1 border-b border-gray-200 dark:border-gray-700 overflow-x-auto pb-px">
                        <button v-for="tab in tabs" :key="tab.key" @click="activeTab = tab.key" :class="[
                            'px-4 py-2 text-sm font-medium rounded-t-lg transition-colors whitespace-nowrap -mb-px',
                            activeTab === tab.key
                                ? 'bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 border-b-white dark:border-b-gray-800 text-indigo-600 dark:text-indigo-400'
                                : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300'
                        ]">
                            {{ tab.icon }} {{ tab.label }}
                        </button>
                    </div>
                </div>

                <!-- Tab Content -->
                <div class="p-6">
                    <!-- Services Tab -->
                    <div v-if="activeTab === 'services'" key="tab-services" class="space-y-4">
                        <!-- Add Department Dropdown -->
                        <div v-if="!isReadOnly" class="flex justify-end">
                            <div class="relative">
                                <button @click="showDeptMenu = !showDeptMenu"
                                    class="inline-flex items-center gap-2 px-3 py-2 text-sm font-medium text-indigo-600 dark:text-indigo-400 hover:bg-indigo-50 dark:hover:bg-indigo-900/20 rounded-lg transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 4v16m8-8H4" />
                                    </svg>
                                    {{ $t('quotes.show.add_department') }}
                                </button>
                                <!-- Department Menu -->
                                <div v-if="showDeptMenu"
                                    class="absolute z-50 start-0 sm:start-auto sm:end-0 mt-2 w-56 bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 py-1 max-h-60 overflow-y-auto">
                                    <button v-for="dept in availableDepartments" :key="dept.id"
                                        @click="addDepartment(dept.id)"
                                        class="w-full px-4 py-2 text-start text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                        {{ getName(dept) }}
                                    </button>
                                    <p v-if="availableDepartments.length === 0" class="px-4 py-2 text-sm text-gray-400">
                                        {{ $t('quotes.show.all_departments_added') }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Departments Accordion -->
                        <div v-if="displayDepartments.length > 0" class="space-y-3">
                            <div v-for="dept in displayDepartments" :key="dept.id"
                                class="border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden">
                                <!-- Department Header -->
                                <div
                                    class="w-full flex items-center justify-between px-4 py-3 bg-gradient-to-r from-gray-50 to-transparent dark:from-gray-900/50 dark:to-transparent">
                                    <div class="flex items-center gap-3 flex-1">
                                        <div
                                            class="w-8 h-8 rounded-lg bg-indigo-100 dark:bg-indigo-900/30 flex items-center justify-center">
                                            <span class="text-indigo-600 dark:text-indigo-400">🔧</span>
                                        </div>
                                        <span class="font-semibold text-gray-900 dark:text-white">{{ getName(dept)
                                            }}</span>
                                        <span
                                            class="text-xs text-gray-400 bg-gray-100 dark:bg-gray-700 px-2 py-0.5 rounded-full">
                                            {{ getDepartmentItems(dept.id).length }} {{ $t('quotes.show.services_count')
                                            }}
                                        </span>
                                    </div>

                                    <div class="flex items-center gap-2">
                                        <!-- Remove Department Button -->
                                        <button v-if="!isReadOnly && getDepartmentItems(dept.id).length === 0"
                                            @click.stop="removeDepartment(dept.id)"
                                            class="w-7 h-7 rounded-lg hover:bg-red-50 hover:text-red-500 dark:hover:bg-red-900/20 dark:hover:text-red-400 text-gray-400 flex items-center justify-center transition-colors"
                                            :title="$t('common.delete')">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>

                                <!-- Department Content (Always Visible) -->
                                <div class="p-4 space-y-2 bg-gray-50/50 dark:bg-gray-900/30">
                                    <!-- Services List -->
                                    <div class="flex flex-col gap-3">
                                        <div v-for="(item, index) in getDepartmentItems(dept.id)" :key="item.id"
                                            class="bg-white dark:bg-gray-800 rounded-lg border border-gray-100 dark:border-gray-700 p-4 transition-all hover:shadow-md hover:border-gray-200 dark:hover:border-gray-600 group relative">
                                            <div class="flex items-center justify-between">
                                                <!-- Right Side: Status + Title + Meta -->
                                                <div class="flex items-start gap-4 flex-1">
                                                    <!-- Status Icon (Large) -->
                                                    <div class="mt-1 flex-shrink-0 w-10 h-10 rounded-full flex items-center justify-center transition-colors"
                                                        :class="{
                                                            'bg-gray-50 text-gray-400 dark:bg-gray-700 dark:text-gray-500': item.status === 'pending',
                                                            'bg-blue-50 text-blue-500 dark:bg-blue-900/20 dark:text-blue-400': item.status === 'in_progress',
                                                            'bg-teal-50 text-teal-500 dark:bg-teal-900/20 dark:text-teal-400': item.status === 'ready_for_qc',
                                                            'bg-green-50 text-green-500 dark:bg-green-900/20 dark:text-green-400': item.status === 'completed',
                                                            'bg-yellow-50 text-yellow-500 dark:bg-yellow-900/20 dark:text-yellow-400': item.status === 'on_hold',
                                                            'bg-red-50 text-red-500 dark:bg-red-900/20 dark:text-red-400': item.status === 'cancelled'
                                                        }">
                                                        <svg v-if="item.status === 'completed'" class="w-6 h-6"
                                                            fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2" d="M5 13l4 4L19 7" />
                                                        </svg>
                                                        <svg v-else-if="item.status === 'in_progress'" class="w-6 h-6"
                                                            fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2"
                                                                d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                        </svg>
                                                        <svg v-else-if="item.status === 'cancelled'" class="w-6 h-6"
                                                            fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                        </svg>
                                                        <svg v-else class="w-6 h-6" fill="none" stroke="currentColor"
                                                            viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2"
                                                                d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                        </svg>
                                                    </div>

                                                    <div class="flex-1 min-w-0">
                                                        <!-- Title Row - CLICKABLE -->
                                                        <div class="flex items-baseline gap-2 mb-1">
                                                            <span
                                                                class="text-gray-400 font-medium font-mono text-sm leading-none">{{
                                                                    index + 1 }}.</span>
                                                            <button v-if="!isReadOnly"
                                                                @click.stop="openEditServiceModal(item)" type="button"
                                                                class="font-bold text-gray-900 dark:text-white text-lg hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors text-start leading-tight">
                                                                {{ item.title || getName(item.service) }}
                                                            </button>
                                                            <span v-else
                                                                class="font-bold text-gray-900 dark:text-white text-lg text-start leading-tight">
                                                                {{ item.title || getName(item.service) }}
                                                            </span>
                                                        </div>

                                                        <!-- Meta Row: Price | Technician -->
                                                        <div
                                                            class="flex items-center gap-4 text-sm text-gray-500 dark:text-gray-400">
                                                            <!-- Prices Group -->
                                                            <div
                                                                class="flex items-center gap-3 bg-gray-50 dark:bg-gray-700/50 px-2 py-1 rounded-md">
                                                                <!-- Labor -->
                                                                <div class="flex items-center gap-1.5 text-gray-700 dark:text-gray-300 font-medium"
                                                                    :title="$t('work_orders.item.service_cost')">
                                                                    <span class="text-indigo-500">🔧</span>
                                                                    <span>{{ formatPrice(item.line_total || item.total)
                                                                        }}</span>
                                                                </div>
                                                                <!-- Parts (if any) -->
                                                                <div v-if="item.parts_total > 0"
                                                                    class="flex items-center gap-1.5 text-gray-700 dark:text-gray-300 font-medium border-s border-gray-200 dark:border-gray-600 ps-3"
                                                                    :title="$t('work_orders.item.parts_cost')">
                                                                    <span class="text-amber-500">🔩</span>
                                                                    <span>{{ formatPrice(item.parts_total) }}</span>
                                                                </div>
                                                            </div>

                                                            <div v-if="item.technicians && item.technicians.length"
                                                                class="flex items-center gap-1.5">
                                                                <span class="w-1 h-1 rounded-full bg-gray-300"></span>
                                                                <div class="flex items-center gap-1">
                                                                    <svg class="w-3.5 h-3.5 text-gray-400" fill="none"
                                                                        stroke="currentColor" viewBox="0 0 24 24">
                                                                        <path stroke-linecap="round"
                                                                            stroke-linejoin="round" stroke-width="2"
                                                                            d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                                                    </svg>
                                                                    <span>{{ item.technicians[0].name }}</span>
                                                                </div>
                                                            </div>
                                                            <div v-else
                                                                class="flex items-center gap-1.5 text-amber-500">
                                                                <span class="w-1 h-1 rounded-full bg-gray-300"></span>
                                                                <span>{{ $t('work_orders.item.assign_technician')
                                                                    }}</span>
                                                            </div>

                                                            <!-- Warranty Info -->
                                                            <div v-if="item.warranty_expires_at"
                                                                class="flex items-center gap-1.5 text-green-600 dark:text-green-400">
                                                                <span class="w-1 h-1 rounded-full bg-gray-300"></span>
                                                                <svg class="w-3.5 h-3.5" fill="none"
                                                                    stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                                        stroke-width="2"
                                                                        d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                                                </svg>
                                                                <span class="text-xs font-bold">{{
                                                                    $t('services_management.warranty') }}: {{
                                                                        formatDate(item.warranty_expires_at) }}</span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Left Side: Actions + Date -->
                                                <div class="flex items-center gap-4 pl-2">
                                                    <!-- Date Badge (Red Style) -->
                                                    <span
                                                        class="hidden sm:inline-flex bg-red-500 text-white text-xs px-2.5 py-1 rounded-md font-bold shadow-sm">
                                                        {{ formatDate(item.created_at) }}
                                                    </span>

                                                    <div v-if="!isReadOnly"
                                                        class="flex items-center gap-1 border-s border-gray-100 dark:border-gray-700 ps-3">
                                                        <button @click.stop="openEditServiceModal(item)"
                                                            class="p-2 text-gray-400 hover:text-indigo-600 hover:bg-indigo-50 dark:hover:bg-indigo-900/30 rounded-lg transition-colors"
                                                            :title="$t('common.edit')">
                                                            <svg class="w-5 h-5" fill="none" stroke="currentColor"
                                                                viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                                    stroke-width="2"
                                                                    d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                                            </svg>
                                                        </button>
                                                        <button @click.stop="deleteServiceItem(item)"
                                                            class="p-2 text-gray-400 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-900/30 rounded-lg transition-colors"
                                                            :title="$t('common.delete')">
                                                            <svg class="w-5 h-5" fill="none" stroke="currentColor"
                                                                viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                                    stroke-width="2"
                                                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                            </svg>
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Empty State -->
                                    <p v-if="getDepartmentItems(dept.id).length === 0"
                                        class="text-center text-gray-400 dark:text-gray-500 py-4 text-sm">
                                        {{ $t('quotes.show.no_services') }}
                                    </p>

                                    <!-- Add Service Button -->
                                    <button v-if="!isReadOnly" @click="openAddServiceModal(dept.id)"
                                        class="w-full flex items-center justify-center gap-2 py-2 text-sm text-indigo-600 dark:text-indigo-400 border border-dashed border-indigo-300 dark:border-indigo-700 rounded-lg hover:bg-indigo-50 dark:hover:bg-indigo-900/20 transition-colors">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M12 4v16m8-8H4" />
                                        </svg>
                                        {{ $t('quotes.show.add_service') }}
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Empty State -->
                        <div v-else class="text-center py-12">
                            <div
                                class="w-16 h-16 mx-auto bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center mb-4">
                                <span class="text-2xl">🔧</span>
                            </div>
                            <div v-if="departments.length === 0" class="space-y-1">
                                <p class="text-gray-500 dark:text-gray-400">
                                    {{ $t('work_orders.show.no_departments_in_center') }}
                                </p>
                                <Link :href="route('services.index')"
                                    class="text-indigo-600 dark:text-indigo-400 font-bold hover:underline">
                                    {{ $t('work_orders.show.click_here_to_add') }}
                                </Link>
                            </div>
                            <p v-else class="text-gray-500 dark:text-gray-400">{{ $t('work_orders.show.no_services') }}
                            </p>
                        </div>
                    </div>

                    <!-- Spare Parts Tab -->
                    <div v-if="activeTab === 'parts'" key="tab-parts" class="space-y-4">
                        <!-- Add Part Button -->
                        <div v-if="!isReadOnly" class="flex justify-end">
                            <button @click="showAddPartModal = true"
                                class="inline-flex items-center gap-2 px-3 py-2 text-sm font-medium text-indigo-600 dark:text-indigo-400 hover:bg-indigo-50 dark:hover:bg-indigo-900/20 rounded-lg transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 4v16m8-8H4" />
                                </svg>
                                {{ $t('inventory.parts.add_to_wo') }}
                            </button>
                        </div>

                        <!-- Parts Display Component -->
                        <PartsDisplay :parts="normalizedPartsForDisplay" :read-only="isReadOnly"
                            storage-key="work_orders_parts_view_mode" :empty-message="$t('work_orders.show.no_parts')"
                            :add-button-text="$t('inventory.parts.add_to_wo')" @delete="deleteWorkOrderPart"
                            @edit="editWorkOrderPart" @add="openAddPartModal" />
                    </div>

                    <!-- Technicians Tab -->
                    <div v-if="activeTab === 'technicians'" key="tab-technicians">
                        <TechniciansSection :work-order="workOrder" :items-by-department="itemsByDepartment" />
                    </div>

                    <!-- Payments Tab -->
                    <div v-if="activeTab === 'payments'" key="tab-payments">
                        <PaymentsSection :work-order-id="workOrder.id" :payments="workOrder.payments || []"
                            :grand-total="workOrderTotal" :total-paid="workOrderTotalPaid" :balance="workOrderBalance"
                            :read-only="isReadOnly" @refresh="refreshWorkOrder" />
                    </div>

                    <!-- Condition Report Tab -->
                    <div v-if="activeTab === 'condition'" key="tab-condition" class="max-w-4xl mx-auto">
                        <VehicleConditionReport v-model:damageMarks="workOrder.damage_marks"
                            v-model:fuelLevel="workOrder.fuel_level" :class="{ 'pointer-events-none': isReadOnly }" />
                    </div>

                    <!-- Photos Tab -->
                    <div v-if="activeTab === 'photos'" key="tab-photos" class="space-y-4">
                        <div v-if="!isReadOnly" class="flex justify-end">
                            <button @click="showPhotoModal = true"
                                class="inline-flex items-center gap-2 px-3 py-2 text-sm font-medium text-indigo-600 dark:text-indigo-400 hover:bg-indigo-50 dark:hover:bg-indigo-900/20 rounded-lg transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 4v16m8-8H4" />
                                </svg>
                                {{ $t('common.add') }}
                            </button>
                        </div>

                        <div v-if="workOrder.photos?.length" class="grid grid-cols-2 md:grid-cols-4 gap-4">
                            <div v-for="photo in workOrder.photos" :key="photo.id"
                                class="relative group aspect-square rounded-xl overflow-hidden bg-gray-100 dark:bg-gray-900 border border-gray-200 dark:border-gray-700">
                                <img :src="`/storage/${photo.path}`" class="w-full h-full object-cover" />

                                <!-- Hover Overlay with Actions -->
                                <div
                                    class="absolute inset-0 bg-black/60 opacity-0 group-hover:opacity-100 transition-all flex items-center justify-center gap-3">
                                    <!-- View Button -->
                                    <a :href="`/storage/${photo.path}`" target="_blank"
                                        class="w-10 h-10 rounded-full bg-white/20 hover:bg-white/40 flex items-center justify-center text-white transition-all"
                                        :title="$t('common.view')">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                    </a>
                                    <!-- Download Button -->
                                    <a :href="`/storage/${photo.path}`" :download="photo.path.split('/').pop()"
                                        class="w-10 h-10 rounded-full bg-white/20 hover:bg-white/40 flex items-center justify-center text-white transition-all"
                                        :title="$t('common.download')">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                        </svg>
                                    </a>
                                    <!-- Delete Button -->
                                    <button v-if="!isReadOnly" type="button" @click="deletePhoto(photo)"
                                        class="w-10 h-10 rounded-full bg-red-500/80 hover:bg-red-600 flex items-center justify-center text-white transition-all"
                                        :title="$t('common.delete')">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </div>

                                <!-- Photo Info Overlay -->
                                <div
                                    class="absolute inset-x-0 bottom-0 bg-black/60 p-2 text-white text-xs backdrop-blur-sm">
                                    <p class="font-bold uppercase">{{ $t(`work_orders.photos.types.${photo.type}`) }}
                                    </p>
                                    <p v-if="photo.caption" class="truncate">{{ photo.caption }}</p>
                                </div>
                            </div>
                        </div>
                        <div v-else class="text-center py-12">
                            <div
                                class="w-16 h-16 mx-auto bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center mb-4">
                                <span class="text-2xl">📸</span>
                            </div>
                            <p class="text-gray-500 dark:text-gray-400">{{ $t('work_orders.photos.no_photos') }}</p>
                        </div>
                    </div>

                    <!-- Activities Tab -->
                    <div v-if="activeTab === 'activities'" key="tab-activities" class="space-y-6">
                        <div v-if="workOrder.activities?.length" class="relative">
                            <!-- Timeline Line -->
                            <div class="absolute start-8 top-0 bottom-0 w-0.5 bg-gray-200 dark:bg-gray-700"></div>

                            <div class="space-y-8 relative">
                                <div v-for="activity in workOrder.activities" :key="activity.id" class="flex gap-4">
                                    <!-- Icon/Marker -->
                                    <div class="w-16 flex justify-center flex-shrink-0 relative">
                                        <div
                                            class="w-10 h-10 rounded-full bg-white dark:bg-gray-800 border-2 border-indigo-500 flex items-center justify-center z-10 shadow-sm">
                                            <span v-if="activity.action === 'created'">🆕</span>
                                            <span v-else-if="activity.action === 'status_changed'">🔄</span>
                                            <span v-else-if="activity.action === 'item_added'">🔧</span>
                                            <span v-else-if="activity.action === 'item_updated'">📝</span>
                                            <span v-else-if="activity.action === 'item_deleted'">🗑️</span>
                                            <span v-else-if="activity.action === 'payment_added'">💰</span>
                                            <span v-else-if="activity.action === 'condition_updated'">🚗</span>
                                            <span v-else-if="activity.action === 'photos_uploaded'">📸</span>
                                            <span v-else-if="activity.action === 'attachments_uploaded'">📎</span>
                                            <span v-else-if="activity.action.includes('part')">🔩</span>
                                            <span v-else-if="activity.action.includes('technician')">👷</span>
                                            <span v-else>📝</span>
                                        </div>
                                    </div>

                                    <!-- Content -->
                                    <div
                                        class="flex-1 bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4 shadow-sm">
                                        <div class="flex flex-wrap items-center justify-between gap-2 mb-2">
                                            <h4 class="text-sm font-bold text-gray-900 dark:text-white">
                                                {{ te(`work_orders.activities.actions.${activity.action}`)
                                                    ? $t(`work_orders.activities.actions.${activity.action}`)
                                                    : activity.description
                                                }}
                                            </h4>
                                            <span class="text-xs text-gray-400 font-medium">{{
                                                formatDateTime(activity.created_at)
                                                }}</span>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <div
                                                class="w-6 h-6 rounded-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center text-[10px] font-bold">
                                                {{ activity.user?.name?.charAt(0) }}
                                            </div>
                                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                                {{ activity.user?.name || $t('common.system') }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div v-else class="text-center py-12">
                            <div
                                class="w-16 h-16 mx-auto bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center mb-4">
                                <span class="text-2xl">📋</span>
                            </div>
                            <p class="text-gray-500 dark:text-gray-400">{{ $t('work_orders.activities.no_activities') }}
                            </p>
                        </div>
                    </div>

                    <!-- Attachments Tab -->
                    <div v-if="activeTab === 'attachments'" key="tab-attachments" class="space-y-4">
                        <div v-if="!isReadOnly" class="flex justify-end">
                            <button @click="showAttachmentModal = true"
                                class="inline-flex items-center gap-2 px-3 py-2 text-sm font-medium text-indigo-600 dark:text-indigo-400 hover:bg-indigo-50 dark:hover:bg-indigo-900/20 rounded-lg transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 4v16m8-8H4" />
                                </svg>
                                {{ $t('common.add') }}
                            </button>
                        </div>

                        <div v-if="workOrder.attachments?.length"
                            class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                            <div v-for="attachment in workOrder.attachments" :key="attachment.id"
                                class="flex items-center justify-between p-3 bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm hover:shadow-md transition-shadow">
                                <div class="flex items-center gap-3 min-w-0">
                                    <div
                                        class="w-12 h-12 rounded-lg bg-gray-50 dark:bg-gray-900 flex items-center justify-center flex-shrink-0">
                                        <svg v-if="attachment.file_type === 'pdf'" class="w-6 h-6 text-red-500"
                                            fill="currentColor" viewBox="0 0 24 24">
                                            <path
                                                d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 14H9v-2h2v2zm0-4H9V7h2v5z" />
                                        </svg>
                                        <svg v-else class="w-6 h-6 text-blue-500" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                        </svg>
                                    </div>
                                    <div class="min-w-0">
                                        <p class="text-sm font-bold text-gray-900 dark:text-white truncate"
                                            :title="attachment.file_name">{{ attachment.file_name }}</p>
                                        <p class="text-[10px] text-gray-400 font-bold uppercase tracking-wider">
                                            {{ formatFileSize(attachment.file_size) }} • {{ attachment.user?.name }}
                                        </p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-1">
                                    <a :href="`/storage/${attachment.path}`" target="_blank"
                                        class="p-2 text-gray-400 hover:text-indigo-500 transition-colors"
                                        :title="$t('common.view')">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                    </a>
                                    <button v-if="!isReadOnly" type="button" @click="deleteAttachment(attachment)"
                                        class="p-2 text-gray-400 hover:text-red-500 transition-colors"
                                        :title="$t('common.delete')">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div v-else class="text-center py-12">
                            <div
                                class="w-16 h-16 mx-auto bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center mb-4">
                                <span class="text-2xl">📎</span>
                            </div>
                            <p class="text-gray-500 dark:text-gray-400">{{ $t('common.no_data') }}</p>
                        </div>
                    </div>

                    <div v-if="activeTab === 'inspections'" key="tab-inspections">
                        <InspectionChecklist :work-order="workOrder" :read-only="isReadOnly" />
                    </div>

                    <!-- Signatures Tab -->
                    <div v-if="activeTab === 'signatures'" key="tab-signatures">
                        <WorkOrderSignatures :work-order="workOrder" :is-read-only="isReadOnly" />
                    </div>

                </div>
            </div>
        </div>





        <!-- Edit Modal -->
        <WorkOrderFormModal :show="showEditModal" :work-order="workOrder" :customers="customers"
            :departments="departments" :makes="makes" :colors="colors" :modelsByMake="modelsByMake"
            @close="showEditModal = false" @saved="handleSaved" />

        <!-- Item Edit/Add Modal (unified) -->
        <WorkOrderServiceModal v-if="showItemModal || showServiceModal" :show="showItemModal || showServiceModal"
            :work-order="workOrder" :item="selectedItem" :department-id="selectedDepartmentId" :services="services"
            :technicians="technicians" :inventory-units="inventoryUnits" :warehouses="warehouses"
            :read-only="isReadOnly" @close="showItemModal ? closeItemModal() : closeServiceModal()"
            @saved="showItemModal ? handleItemSaved() : handleServiceSaved()" />

        <!-- Add Part Modal -->
        <WorkOrderPartModal :show="showAddPartModal" :workOrder="workOrder" :part="selectedPartToEdit"
            :units="inventoryUnits" :warehouses="warehouses" :show-service-select="true"
            :workOrderItems="workOrder.items" @close="closeAddPartModal" @saved="handlePartSaved" />

        <!-- Print Options Modal -->
        <PrintOptionsModal :show="showPrintModal" :work-order="workOrder" @close="showPrintModal = false"
            @print="handlePrint" />

        <!-- Payments List Modal (New) -->
        <PaymentsListModal :show="showPaymentsListModal" :work-order-id="workOrder.id"
            :payments="workOrder.payments || []" :grand-total="workOrderTotal" :total-paid="workOrderTotalPaid"
            :balance="workOrderBalance" @close="showPaymentsListModal = false" @refresh="refreshWorkOrder" />
        <WorkOrderPhotoModal v-if="showPhotoModal" :show="showPhotoModal" :work-order="workOrder"
            @close="showPhotoModal = false" @saved="refreshWorkOrder" />

        <WorkOrderAttachmentModal v-if="showAttachmentModal" :show="showAttachmentModal" :work-order="workOrder"
            @close="showAttachmentModal = false" @saved="refreshWorkOrder" />
    </AppLayout>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import AppLayout from '@/Layouts/AppLayout.vue';
import { useLocalized } from '@/Composables/useLocalized';
import { useNumberFormat } from '@/Composables/useNumberFormat';
import { useToast } from '@/Composables/useToast';
import { useConfirm } from '@/Composables/useConfirm';
import VehicleConditionReport from '@/Components/WorkOrders/VehicleConditionReport.vue';
import WorkOrderFormModal from '@/Components/WorkOrders/WorkOrderFormModal.vue';
import WorkOrderServiceModal from '@/Components/WorkOrders/WorkOrderServiceModal.vue';
import WorkOrderPartModal from '@/Components/WorkOrders/WorkOrderPartModal.vue';
import PrintOptionsModal from '@/Components/WorkOrders/PrintOptionsModal.vue';
import PaymentsSection from '@/Components/WorkOrders/PaymentsSection.vue';
import TechniciansSection from '@/Components/WorkOrders/TechniciansSection.vue';
import PaymentsListModal from '@/Components/WorkOrders/PaymentsListModal.vue';
import WorkOrderPhotoModal from '@/Components/WorkOrders/WorkOrderPhotoModal.vue';
import WorkOrderAttachmentModal from '@/Components/WorkOrders/WorkOrderAttachmentModal.vue';
import InspectionChecklist from '@/Components/WorkOrders/InspectionChecklist.vue';
import WorkOrderSignatures from '@/Components/WorkOrders/WorkOrderSignatures.vue';
import PartsDisplay from '@/Components/Common/PartsDisplay.vue';
import { usePermission } from '@/Composables/usePermission';

const { can } = usePermission();

const props = defineProps({
    workOrder: Object,
    itemsByDepartment: { type: Object, default: () => ({}) },
    customers: { type: Array, default: () => [] },
    makes: { type: Array, default: () => [] },
    colors: { type: Array, default: () => [] },
    modelsByMake: { type: Object, default: () => ({}) },
    departments: { type: Array, default: () => [] },
    services: { type: Array, default: () => [] },
    technicians: { type: Array, default: () => [] },
    warehouses: { type: Array, default: () => [] },
    inventoryParts: { type: Array, default: () => [] },
    inventoryUnits: { type: Array, default: () => [] },
    enableSystematicInspections: { type: Boolean, default: true },
});

const { t, te } = useI18n();
const { getName } = useLocalized();
const { formatNumber, formatCurrency } = useNumberFormat();
const { success, error: errorToast } = useToast();
const { confirm } = useConfirm();

function getColorHex(colorName) {
    if (!colorName) return '#9ca3af';
    const trimmedColor = String(colorName).trim();
    if (props.colors && props.colors.length > 0) {
        const searchColor = trimmedColor.toLowerCase();
        const found = props.colors.find(c =>
            (c.name_ar && c.name_ar.toLowerCase() === searchColor) ||
            (c.name_en && c.name_en.toLowerCase() === searchColor) ||
            (c.hex_code && c.hex_code.toLowerCase() === searchColor)
        );
        if (found && found.hex_code) return found.hex_code;
    }

    const colorMap = {
        'أبيض': '#ffffff', 'أسود': '#111827', 'فضي': '#9ca3af', 'رمادي': '#4b5563',
        'أحمر': '#dc2626', 'أزرق': '#2563eb', 'أخضر': '#16a34a', 'ذهبي': '#ca8a04',
        'بني': '#78350f', 'برتقالي': '#ea580c',
        'white': '#ffffff', 'black': '#111827', 'silver': '#9ca3af', 'gray': '#4b5563',
        'red': '#dc2626', 'blue': '#2563eb', 'green': '#16a34a', 'gold': '#ca8a04',
        'brown': '#78350f', 'orange': '#ea580c',
    };
    return colorMap[trimmedColor.toLowerCase()] || colorMap[trimmedColor] || '#9ca3af';
}

const showEditModal = ref(false);
const activeTab = ref('services');
const showDeptMenu = ref(false);
const expandedDepartments = ref([]);
const showAddPartModal = ref(false);
const showPrintModal = ref(false);
const showPaymentsListModal = ref(false);
const showPhotoModal = ref(false);
const showAttachmentModal = ref(false);
const selectedPartToEdit = ref(null);

function openAddPartModal() {
    selectedPartToEdit.value = null;
    showAddPartModal.value = true;
}

function editWorkOrderPart(part) {
    selectedPartToEdit.value = part;
    showAddPartModal.value = true;
}

function closeAddPartModal() {
    showAddPartModal.value = false;
    selectedPartToEdit.value = null;
}

// Read-only mode for closed work orders
const isReadOnly = computed(() => {
    const closedStatuses = ['done', 'cancelled', 'closed'];
    return closedStatuses.includes(props.workOrder.status);
});

// Duration info computed property
const durationInfo = computed(() => {
    const entryDate = props.workOrder.entry_date ? new Date(props.workOrder.entry_date) : null;
    const expectedEndDate = props.workOrder.expected_end_date ? new Date(props.workOrder.expected_end_date) : null;
    const today = new Date();
    today.setHours(0, 0, 0, 0);

    if (!entryDate || !expectedEndDate) {
        return { text: '-', isOverdue: false };
    }

    // Calculate total duration (entry to expected end)
    const totalDays = Math.ceil((expectedEndDate - entryDate) / (1000 * 60 * 60 * 24));

    // Check if closed work order
    if (isReadOnly.value) {
        const exitDate = props.workOrder.exit_date ? new Date(props.workOrder.exit_date) : expectedEndDate;
        const daysUsed = Math.ceil((exitDate - entryDate) / (1000 * 60 * 60 * 24));
        const isOverdue = exitDate > expectedEndDate;
        const overdueDays = isOverdue ? Math.ceil((exitDate - expectedEndDate) / (1000 * 60 * 60 * 24)) : 0;

        if (isOverdue) {
            return { text: t('work_orders.overdue_days', { days: overdueDays }), isOverdue: true };
        }
        return { text: t('work_orders.completed_in_days', { days: daysUsed }), isOverdue: false };
    }

    // For open work orders - check current status
    const daysRemaining = Math.ceil((expectedEndDate - today) / (1000 * 60 * 60 * 24));

    if (daysRemaining < 0) {
        return { text: t('work_orders.overdue_days', { days: Math.abs(daysRemaining) }), isOverdue: true };
    } else if (daysRemaining === 0) {
        return { text: t('work_orders.due_today'), isOverdue: false };
    } else {
        return { text: t('work_orders.days_remaining', { days: daysRemaining }), isOverdue: false };
    }
});

// All parts from the work order (using the loaded relationship)
const allWorkOrderParts = computed(() => {
    return props.workOrder.parts || [];
});

const normalizedPartsForDisplay = computed(() => {
    const isInclusive = props.workOrder?.pricing_mode_snapshot === 'inclusive';
    const taxRate = Number(props.workOrder?.tax_rate_snapshot || 15);
    const taxFactor = 1 + (taxRate / 100);
    const taxEnabled = !!props.workOrder?.tax_enabled_snapshot;

    return allWorkOrderParts.value.map(part => {
        const qty = Number(part.qty || 0);
        const unitPrice = Number(part.unit_price || 0);
        const discount = Number(part.discount || 0);
        const net = (qty * unitPrice) - discount;

        let taxAmount = 0;
        let amount = net;

        if (taxEnabled) {
            if (isInclusive) {
                amount = net / taxFactor;
                taxAmount = net - amount;
            } else {
                taxAmount = net * (taxRate / 100);
            }
        }

        return {
            ...part,
            name: part.part?.name_ar || part.part?.name_en || part.name || '',
            part_number: part.part?.sku || '',
            source: part.source || 'warehouse',
            total: net,
            discount: discount,
            tax_amount: taxAmount,
            total_incl_tax: isInclusive ? net : (net + taxAmount)
        };
    });
});

// Payment computed properties
const hasTax = computed(() => {
    return !!props.workOrder?.tax_enabled_snapshot;
});

const totals = computed(() => {
    const t = {
        services: { price: 0, discount: 0, amount: 0, tax: 0, total: 0 },
        parts: { price: 0, discount: 0, amount: 0, tax: 0, total: 0 },
        grand: { price: 0, discount: 0, amount: 0, tax: 0, total: 0 },
    };

    const isInclusive = props.workOrder?.pricing_mode_snapshot === 'inclusive';
    const taxRate = Number(props.workOrder?.tax_rate_snapshot || 15);
    const taxFactor = 1 + (taxRate / 100);
    const taxEnabled = !!props.workOrder?.tax_enabled_snapshot;

    // Services calculation
    if (props.workOrder?.items && props.workOrder.items.length > 0) {
        props.workOrder.items.forEach(line => {
            const price = Number(line.unit_price || 0) * Number(line.qty || 1);
            const discount = Number(line.discount_amount || 0);

            let amount = price - discount;
            let calculatedTax = Number(line.tax_amount || 0);
            let total = Number(line.line_total || price);

            // Compute frontend tax correctly if missing or for inclusive view separation
            if (taxEnabled) {
                if (isInclusive) {
                    amount = (price - discount) / taxFactor;
                    calculatedTax = (price - discount) - amount;
                } else if (calculatedTax === 0) {
                    calculatedTax = amount * (taxRate / 100);
                    total = amount + calculatedTax;
                }
            }

            // Always trust line_total if provided directly, otherwise compute
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

    // Parts calculation (using unified parts relationship)
    if (props.workOrder?.parts && props.workOrder.parts.length > 0) {
        props.workOrder.parts.forEach(part => {
            const partQty = Number(part.qty || 0);
            const partUnitPrice = Number(part.unit_price || 0);
            const partDiscount = Number(part.discount || 0);

            const partPrice = partQty * partUnitPrice;
            const partNet = partPrice - partDiscount;

            t.parts.price += partPrice;
            t.parts.discount += partDiscount;

            let partAmount = partNet;
            let partTax = 0;

            if (taxEnabled) {
                if (isInclusive) {
                    partAmount = partNet / taxFactor;
                    partTax = partNet - partAmount;
                } else {
                    partTax = partNet * (taxRate / 100);
                }
            }

            t.parts.amount += partAmount;
            t.parts.tax += partTax;
            t.parts.total += partTax + partAmount;
        });
    }

    t.grand.price = t.services.price + t.parts.price;
    t.grand.discount = t.services.discount + t.parts.discount;
    t.grand.amount = t.services.amount + t.parts.amount;
    t.grand.tax = t.services.tax + t.parts.tax;
    t.grand.total = t.services.total + t.parts.total;

    return t;
});

const servicesTotal = computed(() => totals.value.services.total);
const partsTotal = computed(() => totals.value.parts.total);
const workOrderTotal = computed(() => totals.value.grand.total);

const workOrderTotalPaid = computed(() => {
    return props.workOrder.payments?.reduce((sum, p) => {
        const amount = parseFloat(p.amount || 0);
        return p.type === 'refund' ? sum - amount : sum + amount;
    }, 0) || 0;
});

const workOrderBalance = computed(() => {
    return workOrderTotal.value - workOrderTotalPaid.value;
});

// Refresh work order data
function refreshWorkOrder() {
    router.reload({ only: ['workOrder'] });
}

// Debounce helper
let saveConditionReportTimer = null;
let isSavingCondition = false;

function saveConditionReportDebounced() {
    if (isSavingCondition) return; // Skip if we're already saving
    if (saveConditionReportTimer) clearTimeout(saveConditionReportTimer);
    saveConditionReportTimer = setTimeout(() => {
        saveConditionReport();
    }, 1000);
}

// Save condition report (fuel_level and damage_marks)
function saveConditionReport() {
    if (isReadOnly.value) return;

    isSavingCondition = true;
    router.put(route('app.work-orders.update-condition', props.workOrder.id), {
        fuel_level: props.workOrder.fuel_level,
        damage_marks: props.workOrder.damage_marks,
    }, {
        preserveScroll: true,
        preserveState: true,
        onFinish: () => {
            // Reset flag after a delay to avoid re-triggering from prop update
            setTimeout(() => {
                isSavingCondition = false;
            }, 500);
        },
        onError: () => {
            isSavingCondition = false;
        }
    });
}

// Watch fuel level and damage marks changes - skip initial values
watch(() => props.workOrder.fuel_level, (newVal, oldVal) => {
    if (oldVal !== undefined && !isSavingCondition) {
        saveConditionReportDebounced();
    }
});
watch(() => props.workOrder.damage_marks, (newVal, oldVal) => {
    if (oldVal !== undefined && !isSavingCondition) {
        saveConditionReportDebounced();
    }
}, { deep: true });

// State for payment modal from header
const showPaymentModalFromHeader = ref(false);

// Handle part saved
function handlePartSaved(data, options = {}) {
    if (options.close !== false) {
        showAddPartModal.value = false;
    }
    success(t('common.saved_success'));
    router.reload({ only: ['workOrder', 'itemsByDepartment'] });
}

// Delete photo
async function deletePhoto(photo) {
    const confirmed = await confirm(t('common.confirm_delete'), t('work_orders.photos.delete_confirm'));
    if (!confirmed) return;

    router.delete(route('work-orders.photos.destroy', [props.workOrder.id, photo.id]), {
        onSuccess: () => {
            success(t('common.deleted_success'));
        }
    });
}

// Delete attachment
async function deleteAttachment(attachment) {
    const confirmed = await confirm(t('common.confirm_delete'), t('common.confirm_delete_message'));
    if (!confirmed) return;

    router.delete(route('work-orders.attachments.destroy', [props.workOrder.id, attachment.id]), {
        onSuccess: () => {
            success(t('common.deleted_success'));
        }
    });
}

function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

// Handle print option selection
function handlePrint(type) {
    const woId = props.workOrder.id;
    let url = '';

    switch (type) {
        case 'condition':
            url = route('work-orders.print.condition', woId);
            break;
        case 'work_order':
            url = route('work-orders.print.services', woId);
            break;
        case 'proforma':
            url = route('work-orders.print.proforma', woId);
            break;
        case 'payments':
            url = route('work-orders.print.payments', woId);
            break;
    }

    if (url) {
        window.open(url, '_blank');
    }
}

// Delete work order part
async function deleteWorkOrderPart(part) {
    const confirmed = await confirm({
        title: t('common.confirm_delete_title'),
        message: t('common.confirm_delete_message'),
        confirmText: t('common.delete'),
        cancelText: t('common.cancel'),
        type: 'danger',
    });

    if (confirmed) {
        router.delete(route('work-orders.parts.destroy', part.id), {
            onSuccess: () => success(t('common.deleted_success')),
        });
    }
}

// Get departments that have items or are linked to work order
const displayDepartments = computed(() => {
    const deptIds = new Set();

    // Add departments with items
    Object.keys(props.itemsByDepartment).forEach(id => {
        if (id !== '0') deptIds.add(parseInt(id));
    });

    // Add work order's linked departments
    props.workOrder.departments?.forEach(dept => deptIds.add(dept.id));

    return props.departments.filter(d => deptIds.has(d.id));
});

// Departments that can still be added
const availableDepartments = computed(() => {
    const usedIds = displayDepartments.value.map(d => d.id);
    return props.departments.filter(d => !usedIds.includes(d.id));
});

// Get items for a specific department
function getDepartmentItems(deptId) {
    return props.itemsByDepartment[deptId] || [];
}

// Toggle department expansion
function toggleDepartment(deptId) {
    const idx = expandedDepartments.value.indexOf(deptId);
    if (idx > -1) {
        expandedDepartments.value.splice(idx, 1);
    } else {
        expandedDepartments.value.push(deptId);
    }
}

// Add department to work order
function addDepartment(deptId) {
    showDeptMenu.value = false;
    router.post(route('work-orders.departments.store', props.workOrder.id), {
        department_id: deptId,
    }, {
        onSuccess: () => {
            success(t('common.saved_success'));
            if (!expandedDepartments.value.includes(deptId)) {
                expandedDepartments.value.push(deptId);
            }
        },
    });
}

// Service modal state
const showServiceModal = ref(false);
const selectedItem = ref(null);
const selectedDepartmentId = ref(null);

// Services filtered by department
const departmentServices = computed(() => {
    if (!selectedDepartmentId.value) return [];
    return props.services.filter(s => s.department_id === selectedDepartmentId.value);
});

// Open add service modal
function openAddServiceModal(deptId) {
    selectedDepartmentId.value = deptId;
    selectedItem.value = null;
    showServiceModal.value = true;
}

// Open edit service modal (advanced modal with tabs)
function openEditServiceModal(item) {
    selectedItem.value = item;
    showItemModal.value = true;
}

// Close service modal
function closeServiceModal() {
    showServiceModal.value = false;
    selectedItem.value = null;
    selectedDepartmentId.value = null;
}

// Handle service saved
function handleServiceSaved() {
    closeServiceModal();
    success(t('common.saved_success'));
    router.reload({ only: ['workOrder', 'itemsByDepartment'] });
}

// Item Modal state
const showItemModal = ref(false);

// Close item modal
function closeItemModal() {
    showItemModal.value = false;
    selectedItem.value = null;
}

// Handle item saved
function handleItemSaved() {
    router.reload({ only: ['workOrder', 'itemsByDepartment'] });
}

// Delete service item
async function deleteServiceItem(item) {
    const confirmed = await confirm({
        title: t('common.confirm_delete_title'),
        message: t('common.confirm_delete_message'),
        confirmText: t('common.delete'),
        cancelText: t('common.cancel'),
        type: 'danger',
    });

    if (confirmed) {
        router.delete(route('work-orders.items.destroy', { work_order: props.workOrder.id, item: item.id }), {
            preserveScroll: true
        });
    }
}

const tabs = computed(() => {
    const allTabs = [
        { key: 'services', label: t('work_orders.show.tabs.services'), icon: '🔧' },
        { key: 'parts', label: t('work_orders.show.tabs.parts'), icon: '🔩' },
        { key: 'technicians', label: t('work_orders.show.tabs.technicians'), icon: '👷' },
        { key: 'payments', label: t('work_orders.show.tabs.payments'), icon: '💰' },
        { key: 'condition', label: t('work_orders.show.tabs.condition'), icon: '🚗' },
        { key: 'photos', label: t('work_orders.show.tabs.photos'), icon: '📸' },
        { key: 'attachments', label: t('work_orders.show.tabs.attachments'), icon: '📎' },
        { key: 'inspections', label: t('work_orders.show.tabs.inspections'), icon: '🔍' },
        { key: 'signatures', label: t('work_orders.show.tabs.signatures'), icon: '✍️' },
        { key: 'activities', label: t('work_orders.show.tabs.activities'), icon: '📋' },
    ];

    if (props.enableSystematicInspections) {
        return allTabs;
    }

    return allTabs.filter(tab => tab.key !== 'inspections');
});

// Compute back URL based on work order status
const backUrl = computed(() => {
    const status = props.workOrder.status;
    // Open statuses go back to open list, closed statuses go to closed list
    if (['done', 'cancelled'].includes(status)) {
        return route('work-orders.index', { status: 'closed' });
    }
    return route('work-orders.index', { status: 'open' });
});

const statusBadgeClass = computed(() => {
    const baseClass = 'inline-block px-2 py-1 text-xs font-medium rounded-full mt-1';
    const classes = {
        draft: 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300',
        open: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300',
        in_progress: 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300',
        ready_for_qc: 'bg-teal-100 text-teal-700 dark:bg-teal-900/30 dark:text-teal-300',
        done: 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300',
        cancelled: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300',
    };
    return `${baseClass} ${classes[props.workOrder.status] || classes.draft}`;
});

function formatPrice(value) {
    return formatCurrency(value) + ' ' + t('common.currency');
}

function formatDate(date) {
    if (!date) return '-';

    // If date is in YYYY-MM-DD format, parse manually to avoid timezone issues
    if (typeof date === 'string' && /^\d{4}-\d{2}-\d{2}/.test(date)) {
        const parts = date.split('T')[0].split('-');
        const day = parts[2];
        const month = parts[1];
        const year = parts[0];
        return `${day}/${month}/${year}`;
    }

    const d = new Date(date);
    const day = String(d.getDate()).padStart(2, '0');
    const month = String(d.getMonth() + 1).padStart(2, '0');
    const year = d.getFullYear();
    return `${day}/${month}/${year}`;
}

function formatDateTime(date) {
    if (!date) return '-';
    const d = new Date(date);
    const day = String(d.getDate()).padStart(2, '0');
    const month = String(d.getMonth() + 1).padStart(2, '0');
    const year = d.getFullYear();
    const hours = String(d.getHours()).padStart(2, '0');
    const minutes = String(d.getMinutes()).padStart(2, '0');
    return `${day}/${month}/${year} ${hours}:${minutes}`;
}

function handleSaved() {
    showEditModal.value = false;
    success(t('common.saved_success'));
    router.reload();
}

// Remove department
async function removeDepartment(deptId) {
    const confirmed = await confirm({
        title: t('common.confirm_delete_title'),
        message: t('common.confirm_delete_message'),
        confirmText: t('common.delete'),
        cancelText: t('common.cancel'),
        type: 'danger',
    });

    if (confirmed) {
        router.delete(route('work-orders.departments.destroy', { work_order: props.workOrder.id, department: deptId }), {
            onSuccess: () => {
                success(t('common.deleted_success'));
            },
        });
    }
}

async function changeStatus(newStatus) {
    const statusLabels = {
        in_progress: t('work_orders.actions.start_work'),
        done: t('work_orders.actions.complete'),
        cancelled: t('work_orders.actions.cancel'),
    };

    const confirmed = await confirm({
        title: statusLabels[newStatus],
        message: t('work_orders.messages.confirm_status_change'),
        confirmText: t('common.confirm'),
        cancelText: t('common.cancel'),
        type: newStatus === 'cancelled' ? 'danger' : 'success',
    });

    if (confirmed) {
        router.put(route('work-orders.update', props.workOrder.id), {
            status: newStatus,
        }, {
            onSuccess: () => {
                success(t('common.saved_success'));
            },
            onError: (err) => {
                const msg = err.status || Object.values(err)[0] || t('common.error');
                errorToast(msg);
            }
        });
    }
}
</script>
