<template>
    <AppLayout>
        <div class="space-y-8 pb-12 print:space-y-4">
            <!-- Header Section -->
            <PageHeader
                :title="$t('reports.title')"
                :subtitle="$t('reports.subtitle')"
                gradientFrom="from-violet-600"
                gradientTo="to-purple-700"
                glowFrom="from-violet-500"
                badgeBg="bg-violet-50/50 dark:bg-violet-900/30"
                badgeText="text-violet-600 dark:text-violet-400"
                badgeBorder="border-violet-100/50 dark:border-violet-800/30"
                badgeDot="bg-violet-500"
                class="print:hidden"
            >
                <template #icon>
                    <svg
                        class="w-8 h-8 text-white"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"
                        />
                    </svg>
                </template>
                <template #actions>
                    <div class="flex items-center gap-2">
                        <button
                            @click="printReport"
                            class="inline-flex items-center gap-2 px-4 py-2 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-200 rounded-xl font-medium shadow-sm transition-colors text-sm"
                        >
                            <svg
                                class="w-4 h-4 text-gray-500"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"
                                />
                            </svg>
                            <span>{{ $t('common.print') }}</span>
                        </button>
                    </div>
                </template>
            </PageHeader>

            <!-- Print Header (Visible only when printing) -->
            <div class="hidden print:block text-center border-b pb-4 mb-4">
                <h1 class="text-2xl font-bold">{{ $t('reports.title') }}</h1>
                <p class="text-sm text-gray-600 mt-1">
                    الفترة: من {{ filters.date_from }} إلى {{ filters.date_to }}
                </p>
            </div>

            <!-- Date Range Filter Bar -->
            <div
                class="bg-white dark:bg-gray-800 rounded-2xl p-4 border border-gray-100 dark:border-gray-700/60 shadow-sm flex flex-wrap items-center justify-between gap-4 print:hidden"
            >
                <div class="flex items-center gap-1.5 overflow-x-auto pb-1 scrollbar-none">
                    <button
                        v-for="r in rangePresets"
                        :key="r.id"
                        @click="setRange(r.id)"
                        :class="[
                            'px-3.5 py-1.5 rounded-xl text-xs font-bold transition-all whitespace-nowrap',
                            filters.range === r.id
                                ? 'bg-violet-600 text-white shadow-sm'
                                : 'bg-gray-100 dark:bg-gray-700/60 text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700',
                        ]"
                    >
                        {{ r.label }}
                    </button>
                </div>

                <div class="flex items-center gap-3 text-xs text-gray-500 dark:text-gray-400">
                    <span class="flex items-center gap-1">
                        <svg
                            class="w-4 h-4 text-violet-500"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"
                            />
                        </svg>
                        <span>{{ filters.date_from }}</span>
                    </span>
                    <span>→</span>
                    <span class="flex items-center gap-1">
                        <span>{{ filters.date_to }}</span>
                    </span>
                </div>
            </div>

            <!-- Executive KPI Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Total Gross Revenue -->
                <div
                    class="bg-gradient-to-br from-emerald-50 to-teal-50 dark:from-emerald-950/20 dark:to-teal-950/20 p-5 rounded-2xl border border-emerald-200 dark:border-emerald-800/60 shadow-sm"
                >
                    <div class="flex items-center justify-between">
                        <span
                            class="text-xs font-bold uppercase tracking-wider text-emerald-700 dark:text-emerald-400"
                        >
                            إجمالي الإيرادات
                        </span>
                        <div
                            class="w-9 h-9 rounded-xl bg-emerald-500 text-white flex items-center justify-center shadow-sm"
                        >
                            <svg
                                class="w-5 h-5"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                                />
                            </svg>
                        </div>
                    </div>
                    <div class="mt-3">
                        <div class="text-2xl font-black text-gray-900 dark:text-white font-mono">
                            {{ formatCurrency(kpis.gross_revenue) }}
                        </div>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                            {{ kpis.invoices_count }} فاتورة صادرة
                        </p>
                    </div>
                </div>

                <!-- Total Paid -->
                <div
                    class="bg-gradient-to-br from-blue-50 to-indigo-50 dark:from-blue-950/20 dark:to-indigo-950/20 p-5 rounded-2xl border border-blue-200 dark:border-blue-800/60 shadow-sm"
                >
                    <div class="flex items-center justify-between">
                        <span
                            class="text-xs font-bold uppercase tracking-wider text-blue-700 dark:text-blue-400"
                        >
                            التحصيل الفعلي
                        </span>
                        <div
                            class="w-9 h-9 rounded-xl bg-blue-500 text-white flex items-center justify-center shadow-sm"
                        >
                            <svg
                                class="w-5 h-5"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"
                                />
                            </svg>
                        </div>
                    </div>
                    <div class="mt-3">
                        <div class="text-2xl font-black text-gray-900 dark:text-white font-mono">
                            {{ formatCurrency(kpis.net_paid) }}
                        </div>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                            صافي المدفوعات المستلمة
                        </p>
                    </div>
                </div>

                <!-- VAT Collected (15%) -->
                <div
                    class="bg-gradient-to-br from-amber-50 to-orange-50 dark:from-amber-950/20 dark:to-orange-950/20 p-5 rounded-2xl border border-amber-200 dark:border-amber-800/60 shadow-sm"
                >
                    <div class="flex items-center justify-between">
                        <span
                            class="text-xs font-bold uppercase tracking-wider text-amber-700 dark:text-amber-400"
                        >
                            ضريبة القيمة المضافة ZATCA (15%)
                        </span>
                        <div
                            class="w-9 h-9 rounded-xl bg-amber-500 text-white flex items-center justify-center shadow-sm"
                        >
                            <svg
                                class="w-5 h-5"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
                                />
                            </svg>
                        </div>
                    </div>
                    <div class="mt-3">
                        <div class="text-2xl font-black text-gray-900 dark:text-white font-mono">
                            {{ formatCurrency(kpis.total_tax) }}
                        </div>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                            المبلغ الضريبي المستحق
                        </p>
                    </div>
                </div>

                <!-- Work Orders Count -->
                <div
                    class="bg-gradient-to-br from-purple-50 to-fuchsia-50 dark:from-purple-950/20 dark:to-fuchsia-950/20 p-5 rounded-2xl border border-purple-200 dark:border-purple-800/60 shadow-sm"
                >
                    <div class="flex items-center justify-between">
                        <span
                            class="text-xs font-bold uppercase tracking-wider text-purple-700 dark:text-purple-400"
                        >
                            كروت العمل
                        </span>
                        <div
                            class="w-9 h-9 rounded-xl bg-purple-500 text-white flex items-center justify-center shadow-sm"
                        >
                            <svg
                                class="w-5 h-5"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"
                                />
                            </svg>
                        </div>
                    </div>
                    <div class="mt-3">
                        <div class="text-2xl font-black text-gray-900 dark:text-white font-mono">
                            {{ kpis.total_work_orders }}
                        </div>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                            {{ kpis.completed_work_orders }} مكتمل •
                            {{ kpis.in_progress_work_orders }} جاري
                        </p>
                    </div>
                </div>
            </div>

            <!-- Report Detail Tabs -->
            <div class="space-y-4">
                <!-- Navigation Tabs -->
                <div
                    class="flex items-center gap-2 overflow-x-auto pb-1 scrollbar-none border-b border-gray-200 dark:border-gray-700 print:hidden"
                >
                    <button
                        v-for="tab in reportTabs"
                        :key="tab.id"
                        @click="activeTab = tab.id"
                        :class="[
                            'px-4 py-2.5 text-xs font-bold transition-all relative whitespace-nowrap flex items-center gap-2',
                            activeTab === tab.id
                                ? 'text-violet-600 dark:text-violet-400 font-extrabold'
                                : 'text-gray-500 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-200',
                        ]"
                    >
                        <span>{{ tab.label }}</span>
                        <div
                            v-if="activeTab === tab.id"
                            class="absolute bottom-0 left-0 w-full h-0.5 bg-violet-600 dark:bg-violet-400 rounded-t-full"
                        ></div>
                    </button>
                </div>

                <!-- 1. Payments Report Tab -->
                <div v-if="activeTab === 'payments'" class="space-y-6">
                    <!-- Method breakdown chips -->
                    <div
                        class="bg-white dark:bg-gray-800 rounded-2xl p-5 border border-gray-100 dark:border-gray-700/60 shadow-sm space-y-3"
                    >
                        <h4 class="text-xs font-bold uppercase tracking-wider text-gray-500">
                            توزيع التحصيل حسب طريقة الدفع
                        </h4>
                        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-6 gap-3">
                            <div
                                v-for="method in paymentsByMethod"
                                :key="method.method"
                                class="p-3 bg-gray-50 dark:bg-gray-900/40 rounded-xl border border-gray-100 dark:border-gray-800 text-center"
                            >
                                <span
                                    class="text-xs font-semibold text-gray-600 dark:text-gray-300"
                                >
                                    {{ getMethodLabel(method.method) }}
                                </span>
                                <div
                                    class="text-sm font-black text-gray-900 dark:text-white mt-1 font-mono"
                                >
                                    {{ formatCurrency(method.total) }}
                                </div>
                                <span class="text-[10px] text-gray-400">
                                    {{ method.count }} عملية
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Payments Table -->
                    <div
                        class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700/60 shadow-sm overflow-hidden"
                    >
                        <div
                            class="p-5 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between"
                        >
                            <h4 class="text-sm font-bold text-gray-900 dark:text-white">
                                حركات الدفع والتحصيل (أحدث العمليات)
                            </h4>
                            <span class="text-xs text-gray-500">
                                {{ recentPayments.length }} عملية مسجلة
                            </span>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-xs text-start">
                                <thead
                                    class="bg-gray-50 dark:bg-gray-900/40 text-gray-500 font-bold uppercase border-b border-gray-100 dark:border-gray-700"
                                >
                                    <tr>
                                        <th class="px-5 py-3 text-start">التاريخ والوقت</th>
                                        <th class="px-5 py-3 text-start">كرت العمل</th>
                                        <th class="px-5 py-3 text-start">العميل</th>
                                        <th class="px-5 py-3 text-start">طريقة الدفع</th>
                                        <th class="px-5 py-3 text-start">الفاتورة</th>
                                        <th class="px-5 py-3 text-end">المبلغ</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                    <tr
                                        v-for="p in recentPayments"
                                        :key="p.id"
                                        class="hover:bg-gray-50/50 dark:hover:bg-gray-700/20"
                                    >
                                        <td
                                            class="px-5 py-3 text-gray-600 dark:text-gray-300 font-mono"
                                        >
                                            {{ p.date }}
                                        </td>
                                        <td
                                            class="px-5 py-3 font-bold text-violet-600 dark:text-violet-400"
                                        >
                                            {{ p.work_order_code || '-' }}
                                        </td>
                                        <td class="px-5 py-3 text-gray-800 dark:text-gray-200">
                                            {{ p.customer_name || '-' }}
                                        </td>
                                        <td class="px-5 py-3">
                                            <span
                                                class="px-2 py-0.5 rounded-md bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 font-medium"
                                            >
                                                {{ getMethodLabel(p.method) }}
                                            </span>
                                        </td>
                                        <td class="px-5 py-3 text-gray-500 font-mono">
                                            {{ p.invoice_number || '-' }}
                                        </td>
                                        <td
                                            class="px-5 py-3 text-end font-bold font-mono text-emerald-600 dark:text-emerald-400"
                                        >
                                            {{ formatCurrency(p.amount) }}
                                        </td>
                                    </tr>
                                    <tr v-if="recentPayments.length === 0">
                                        <td colspan="6" class="px-5 py-8 text-center text-gray-400">
                                            لا توجد مدفوعات مسجلة خلال الفترة المحددة
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- 2. Invoices & ZATCA Report Tab -->
                <div v-if="activeTab === 'invoices'" class="space-y-6">
                    <div
                        class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700/60 shadow-sm overflow-hidden"
                    >
                        <div
                            class="p-5 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between"
                        >
                            <h4 class="text-sm font-bold text-gray-900 dark:text-white">
                                سجل الفواتير الضريبية وضريبة القيمة المضافة 15%
                            </h4>
                            <span class="text-xs text-gray-500">
                                {{ recentInvoices.length }} فاتورة
                            </span>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-xs text-start">
                                <thead
                                    class="bg-gray-50 dark:bg-gray-900/40 text-gray-500 font-bold uppercase border-b border-gray-100 dark:border-gray-700"
                                >
                                    <tr>
                                        <th class="px-5 py-3 text-start">رقم الفاتورة</th>
                                        <th class="px-5 py-3 text-start">تاريخ الإصدار</th>
                                        <th class="px-5 py-3 text-start">العميل</th>
                                        <th class="px-5 py-3 text-start">كرت العمل</th>
                                        <th class="px-5 py-3 text-end">قبل الضريبة</th>
                                        <th class="px-5 py-3 text-end">الضريبة (15%)</th>
                                        <th class="px-5 py-3 text-end">المجموع الشامل</th>
                                        <th class="px-5 py-3 text-center">حالة السداد</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                    <tr
                                        v-for="inv in recentInvoices"
                                        :key="inv.id"
                                        class="hover:bg-gray-50/50 dark:hover:bg-gray-700/20"
                                    >
                                        <td
                                            class="px-5 py-3 font-bold font-mono text-gray-900 dark:text-white"
                                        >
                                            {{ inv.invoice_number }}
                                        </td>
                                        <td
                                            class="px-5 py-3 text-gray-600 dark:text-gray-400 font-mono"
                                        >
                                            {{ inv.date }}
                                        </td>
                                        <td class="px-5 py-3 text-gray-800 dark:text-gray-200">
                                            {{ inv.customer_name }}
                                        </td>
                                        <td
                                            class="px-5 py-3 font-mono text-violet-600 dark:text-violet-400"
                                        >
                                            {{ inv.work_order_code || '-' }}
                                        </td>
                                        <td
                                            class="px-5 py-3 text-end font-mono text-gray-600 dark:text-gray-400"
                                        >
                                            {{ formatCurrency(inv.total_excl_tax) }}
                                        </td>
                                        <td
                                            class="px-5 py-3 text-end font-mono text-amber-600 dark:text-amber-400 font-semibold"
                                        >
                                            {{ formatCurrency(inv.total_tax) }}
                                        </td>
                                        <td
                                            class="px-5 py-3 text-end font-mono font-bold text-gray-900 dark:text-white"
                                        >
                                            {{ formatCurrency(inv.total_incl_tax) }}
                                        </td>
                                        <td class="px-5 py-3 text-center">
                                            <span
                                                :class="[
                                                    'px-2 py-0.5 rounded-full text-[10px] font-bold',
                                                    inv.payment_status === 'paid'
                                                        ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400'
                                                        : 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
                                                ]"
                                            >
                                                {{
                                                    inv.payment_status === 'paid'
                                                        ? 'مسددة'
                                                        : 'معلقة / جزئي'
                                                }}
                                            </span>
                                        </td>
                                    </tr>
                                    <tr v-if="recentInvoices.length === 0">
                                        <td colspan="8" class="px-5 py-8 text-center text-gray-400">
                                            لا توجد فواتير صادرة خلال الفترة المحددة
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- 3. Work Orders Report Tab -->
                <div v-if="activeTab === 'work_orders'" class="space-y-6">
                    <div
                        class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700/60 shadow-sm overflow-hidden"
                    >
                        <div
                            class="p-5 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between"
                        >
                            <h4 class="text-sm font-bold text-gray-900 dark:text-white">
                                كروت العمل والعمليات المنجزة
                            </h4>
                            <span class="text-xs text-gray-500">
                                {{ recentWorkOrders.length }} كرت مسجل
                            </span>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-xs text-start">
                                <thead
                                    class="bg-gray-50 dark:bg-gray-900/40 text-gray-500 font-bold uppercase border-b border-gray-100 dark:border-gray-700"
                                >
                                    <tr>
                                        <th class="px-5 py-3 text-start">رقم الكرت</th>
                                        <th class="px-5 py-3 text-start">تاريخ الفتح</th>
                                        <th class="px-5 py-3 text-start">العميل</th>
                                        <th class="px-5 py-3 text-start">رقم اللوحة</th>
                                        <th class="px-5 py-3 text-center">الحالة</th>
                                        <th class="px-5 py-3 text-end">القيمة الإجمالية</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                    <tr
                                        v-for="wo in recentWorkOrders"
                                        :key="wo.id"
                                        class="hover:bg-gray-50/50 dark:hover:bg-gray-700/20"
                                    >
                                        <td
                                            class="px-5 py-3 font-bold font-mono text-violet-600 dark:text-violet-400"
                                        >
                                            {{ wo.code }}
                                        </td>
                                        <td
                                            class="px-5 py-3 text-gray-600 dark:text-gray-400 font-mono"
                                        >
                                            {{ wo.created_at }}
                                        </td>
                                        <td class="px-5 py-3 text-gray-800 dark:text-gray-200">
                                            {{ wo.customer_name }}
                                        </td>
                                        <td class="px-5 py-3 font-mono font-medium">
                                            {{ wo.vehicle_plate }}
                                        </td>
                                        <td class="px-5 py-3 text-center">
                                            <span
                                                class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300"
                                            >
                                                {{ wo.status }}
                                            </span>
                                        </td>
                                        <td
                                            class="px-5 py-3 text-end font-bold font-mono text-gray-900 dark:text-white"
                                        >
                                            {{ formatCurrency(wo.total_incl_tax) }}
                                        </td>
                                    </tr>
                                    <tr v-if="recentWorkOrders.length === 0">
                                        <td colspan="6" class="px-5 py-8 text-center text-gray-400">
                                            لا توجد كروت عمل مسجلة خلال الفترة المحددة
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- 4. Top Services Tab -->
                <div v-if="activeTab === 'services'" class="space-y-6">
                    <div
                        class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700/60 shadow-sm overflow-hidden"
                    >
                        <div
                            class="p-5 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between"
                        >
                            <h4 class="text-sm font-bold text-gray-900 dark:text-white">
                                الخدمات الأكثر طلباً وإيراداً
                            </h4>
                        </div>
                        <div class="p-5">
                            <div class="space-y-3">
                                <div
                                    v-for="(srv, idx) in topServices"
                                    :key="srv.service_id"
                                    class="p-4 bg-gray-50 dark:bg-gray-900/40 rounded-xl border border-gray-100 dark:border-gray-800 flex items-center justify-between gap-4"
                                >
                                    <div class="flex items-center gap-3">
                                        <span
                                            class="w-7 h-7 rounded-lg bg-violet-100 dark:bg-violet-900/30 text-violet-600 dark:text-violet-400 font-extrabold text-xs flex items-center justify-center"
                                        >
                                            #{{ idx + 1 }}
                                        </span>
                                        <div>
                                            <h5
                                                class="text-sm font-bold text-gray-900 dark:text-white"
                                            >
                                                {{ srv.name }}
                                            </h5>
                                            <span class="text-xs text-gray-500">
                                                تم تنفيذها {{ srv.count }} مرة
                                            </span>
                                        </div>
                                    </div>
                                    <div class="text-end">
                                        <div
                                            class="text-sm font-black font-mono text-emerald-600 dark:text-emerald-400"
                                        >
                                            {{ formatCurrency(srv.revenue) }}
                                        </div>
                                        <span class="text-[10px] text-gray-400">إجمالي الدخل</span>
                                    </div>
                                </div>
                                <div
                                    v-if="topServices.length === 0"
                                    class="py-8 text-center text-gray-400 text-xs"
                                >
                                    لا توجد خدمات منفذة مسجلة في هذا النطاق الزمني
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 5. Technicians Productivity Tab -->
                <div v-if="activeTab === 'technicians'" class="space-y-6">
                    <div
                        class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700/60 shadow-sm overflow-hidden"
                    >
                        <div
                            class="p-5 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between"
                        >
                            <h4 class="text-sm font-bold text-gray-900 dark:text-white">
                                إنتاجية وأداء الفنيين
                            </h4>
                        </div>
                        <div class="p-5">
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                <div
                                    v-for="tech in technicians"
                                    :key="tech.id"
                                    class="p-4 bg-gray-50 dark:bg-gray-900/40 rounded-xl border border-gray-100 dark:border-gray-800 flex items-center justify-between"
                                >
                                    <div>
                                        <h5 class="text-sm font-bold text-gray-900 dark:text-white">
                                            {{ tech.name }}
                                        </h5>
                                        <p class="text-xs text-gray-500 mt-1">
                                            إجمالي المهام المسندة: {{ tech.total_assigned_count }}
                                        </p>
                                    </div>
                                    <div class="text-end">
                                        <span
                                            class="px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400"
                                        >
                                            {{ tech.completed_items_count }} مكتمل
                                        </span>
                                    </div>
                                </div>
                                <div
                                    v-if="technicians.length === 0"
                                    class="col-span-full py-8 text-center text-gray-400 text-xs"
                                >
                                    لا توجد سجلات فنيين خلال هذه الفترة
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';

const props = defineProps({
    filters: {
        type: Object,
        default: () => ({ range: 'this_month', date_from: '', date_to: '' }),
    },
    kpis: {
        type: Object,
        default: () => ({
            gross_revenue: 0,
            net_paid: 0,
            total_tax: 0,
            total_work_orders: 0,
            completed_work_orders: 0,
            in_progress_work_orders: 0,
            invoices_count: 0,
        }),
    },
    paymentsByMethod: {
        type: Array,
        default: () => [],
    },
    recentPayments: {
        type: Array,
        default: () => [],
    },
    recentInvoices: {
        type: Array,
        default: () => [],
    },
    recentWorkOrders: {
        type: Array,
        default: () => [],
    },
    topServices: {
        type: Array,
        default: () => [],
    },
    technicians: {
        type: Array,
        default: () => [],
    },
});

const activeTab = ref('payments');

const rangePresets = [
    { id: 'today', label: 'اليوم' },
    { id: 'yesterday', label: 'أمس' },
    { id: 'this_week', label: 'هذا الأسبوع' },
    { id: 'this_month', label: 'هذا الشهر' },
    { id: 'last_month', label: 'الشهر الماضي' },
    { id: 'this_year', label: 'هذه السنة' },
    { id: 'all', label: 'الكل' },
];

const reportTabs = [
    { id: 'payments', label: 'المدفوعات وطرق التحصيل' },
    { id: 'invoices', label: 'الفواتير والضريبة ZATCA' },
    { id: 'work_orders', label: 'كروت العمل والعمليات' },
    { id: 'services', label: 'الخدمات الأكثر طلباً' },
    { id: 'technicians', label: 'إنتاجية الفنيين' },
];

const setRange = (rangeId) => {
    router.get('/app/reports', { range: rangeId }, { preserveState: true, preserveScroll: true });
};

const formatCurrency = (val) => {
    const num = parseFloat(val || 0);
    return (
        new Intl.NumberFormat('en-US', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        }).format(num) + ' ر.س'
    );
};

const getMethodLabel = (method) => {
    const map = {
        cash: 'نقداً',
        mada: 'مدى',
        visa: 'فيزا',
        mastercard: 'ماستركارد',
        transfer: 'تحويل بنكي',
        apple_pay: 'Apple Pay',
        stc_pay: 'STC Pay',
        other: 'أخرى',
    };
    return map[method] || method || 'أخرى';
};

const printReport = () => {
    window.print();
};
</script>
