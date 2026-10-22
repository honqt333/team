<template>
    <AppLayout>
        <div class="space-y-6">
            <!-- Header & Navigation -->
            <div
                class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-2 border-b border-gray-200 dark:border-gray-800 print:hidden"
            >
                <div>
                    <div
                        class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400 mb-1"
                    >
                        <a
                            href="/app/reports"
                            class="hover:text-emerald-600 dark:hover:text-emerald-400 transition-colors flex items-center gap-1"
                        >
                            <svg
                                class="w-4 h-4 rtl:rotate-180"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M15 19l-7-7 7-7"
                                />
                            </svg>
                            <span>{{ $t('reports.title') }}</span>
                        </a>
                        <span>/</span>
                        <span class="text-gray-900 dark:text-white font-medium">
                            {{ $t('reports.payments') }}
                        </span>
                    </div>
                    <div class="flex items-center gap-3">
                        <div
                            class="w-10 h-10 rounded-xl bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 flex items-center justify-center font-bold"
                        >
                            <svg
                                class="w-6 h-6"
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
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                                {{ $t('reports.payments') }}
                            </h1>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                كشف تحليلي وتفصيلي لجميع المقبوضات والمرتجعات وحركات الصندوق
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Export & Print Actions -->
                <div class="flex items-center gap-2.5">
                    <button
                        type="button"
                        @click="handlePrint"
                        class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700 text-sm font-semibold transition-colors shadow-sm"
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
                        <span>طباعة</span>
                    </button>

                    <a
                        :href="exportUrl"
                        class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold transition-colors shadow-sm shadow-emerald-600/20"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"
                            />
                        </svg>
                        <span>تصدير Excel (CSV)</span>
                    </a>
                </div>
            </div>

            <!-- Print Header (Visible only on print) -->
            <div class="hidden print:block pb-4 mb-4 border-b border-gray-300">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-xl font-bold text-black">تقرير المدفوعات والتحصيلات</h1>
                        <p class="text-xs text-gray-600 mt-1">
                            الفترة: {{ filters.start_date || 'من البداية' }} إلى
                            {{ filters.end_date || 'الآن' }}
                        </p>
                    </div>
                    <div class="text-end text-xs text-gray-500">
                        <p>تاريخ الاستخراج: {{ new Date().toLocaleString('ar-SA') }}</p>
                        <p>إجمالي العمليات: {{ kpis.total_count }} عملية</p>
                    </div>
                </div>
            </div>

            <!-- KPI Summary Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Net Collections -->
                <div
                    class="bg-white dark:bg-gray-800 rounded-2xl p-5 border border-emerald-100 dark:border-emerald-900/40 shadow-sm relative overflow-hidden"
                >
                    <div class="flex items-center justify-between">
                        <div>
                            <span
                                class="text-xs font-semibold uppercase tracking-wider text-emerald-600 dark:text-emerald-400"
                            >
                                صافي التحصيل
                            </span>
                            <div class="text-2xl font-black text-gray-900 dark:text-white mt-1">
                                {{ formatMoney(kpis.net_collected) }}
                            </div>
                        </div>
                        <div
                            class="w-12 h-12 rounded-xl bg-emerald-50 dark:bg-emerald-950/50 text-emerald-600 dark:text-emerald-400 flex items-center justify-center"
                        >
                            <svg
                                class="w-6 h-6"
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
                    <div class="mt-2 text-[11px] text-gray-500 dark:text-gray-400">
                        إجمالي المقبوضات مخصوماً منه المرتجعات
                    </div>
                </div>

                <!-- Total Collected -->
                <div
                    class="bg-white dark:bg-gray-800 rounded-2xl p-5 border border-blue-100 dark:border-blue-900/40 shadow-sm relative overflow-hidden"
                >
                    <div class="flex items-center justify-between">
                        <div>
                            <span
                                class="text-xs font-semibold uppercase tracking-wider text-blue-600 dark:text-blue-400"
                            >
                                إجمالي المقبوضات
                            </span>
                            <div class="text-2xl font-black text-gray-900 dark:text-white mt-1">
                                {{ formatMoney(kpis.total_collected) }}
                            </div>
                        </div>
                        <div
                            class="w-12 h-12 rounded-xl bg-blue-50 dark:bg-blue-950/50 text-blue-600 dark:text-blue-400 flex items-center justify-center"
                        >
                            <svg
                                class="w-6 h-6"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M7 11l5-5m0 0l5 5m-5-5v12"
                                />
                            </svg>
                        </div>
                    </div>
                    <div class="mt-2 text-[11px] text-gray-500 dark:text-gray-400">
                        مجموع سندات القبض المدفوعة
                    </div>
                </div>

                <!-- Total Refunded -->
                <div
                    class="bg-white dark:bg-gray-800 rounded-2xl p-5 border border-rose-100 dark:border-rose-900/40 shadow-sm relative overflow-hidden"
                >
                    <div class="flex items-center justify-between">
                        <div>
                            <span
                                class="text-xs font-semibold uppercase tracking-wider text-rose-600 dark:text-rose-400"
                            >
                                المرتجعات
                            </span>
                            <div class="text-2xl font-black text-rose-600 dark:text-rose-400 mt-1">
                                {{ formatMoney(kpis.total_refunded) }}
                            </div>
                        </div>
                        <div
                            class="w-12 h-12 rounded-xl bg-rose-50 dark:bg-rose-950/50 text-rose-600 dark:text-rose-400 flex items-center justify-center"
                        >
                            <svg
                                class="w-6 h-6"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M17 13l-5 5m0 0l-5-5m5 5V6"
                                />
                            </svg>
                        </div>
                    </div>
                    <div class="mt-2 text-[11px] text-gray-500 dark:text-gray-400">
                        المبالغ المسترجعة للعملاء
                    </div>
                </div>

                <!-- Transaction Count -->
                <div
                    class="bg-white dark:bg-gray-800 rounded-2xl p-5 border border-purple-100 dark:border-purple-900/40 shadow-sm relative overflow-hidden"
                >
                    <div class="flex items-center justify-between">
                        <div>
                            <span
                                class="text-xs font-semibold uppercase tracking-wider text-purple-600 dark:text-purple-400"
                            >
                                عدد العمليات
                            </span>
                            <div class="text-2xl font-black text-gray-900 dark:text-white mt-1">
                                {{ kpis.total_count }}
                            </div>
                        </div>
                        <div
                            class="w-12 h-12 rounded-xl bg-purple-50 dark:bg-purple-950/50 text-purple-600 dark:text-purple-400 flex items-center justify-center"
                        >
                            <svg
                                class="w-6 h-6"
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
                    <div class="mt-2 text-[11px] text-gray-500 dark:text-gray-400">
                        إجمالي السندات المسجلة في الفترة
                    </div>
                </div>
            </div>

            <!-- Methods Breakdown Strip -->
            <div
                v-if="methodsBreakdown && methodsBreakdown.length > 0"
                class="bg-white dark:bg-gray-800 rounded-2xl p-4 border border-gray-200 dark:border-gray-800 shadow-sm"
            >
                <span class="text-xs font-bold text-gray-500 dark:text-gray-400 block mb-3">
                    توزيع المبالغ حسب طريقة الدفع:
                </span>
                <div class="flex flex-wrap items-center gap-3">
                    <div
                        v-for="item in methodsBreakdown"
                        :key="item.method"
                        class="flex items-center gap-2.5 px-3 py-2 rounded-xl bg-gray-50 dark:bg-gray-750 border border-gray-200 dark:border-gray-700 text-xs"
                    >
                        <span class="font-bold text-gray-700 dark:text-gray-300">
                            {{ getMethodLabel(item.method) }}:
                        </span>
                        <span class="font-black text-emerald-600 dark:text-emerald-400 font-mono">
                            {{ formatMoney(item.total) }}
                        </span>
                        <span
                            class="text-[10px] px-1.5 py-0.5 rounded-full bg-gray-200 dark:bg-gray-600 text-gray-600 dark:text-gray-300"
                        >
                            {{ item.count }} عملية
                        </span>
                    </div>
                </div>
            </div>

            <!-- Filter Controls Bar -->
            <div
                class="bg-white dark:bg-gray-800 rounded-2xl p-4 border border-gray-200 dark:border-gray-800 shadow-sm space-y-4 print:hidden"
            >
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-3">
                    <!-- Search Input -->
                    <div>
                        <label
                            class="block text-xs font-bold text-gray-600 dark:text-gray-300 mb-1"
                        >
                            بحث سريع
                        </label>
                        <div class="relative">
                            <input
                                type="text"
                                v-model="filterForm.search"
                                @keyup.enter="applyFilters"
                                placeholder="رقم السند، العميل، اللوحة، الفاتورة..."
                                class="w-full text-xs rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 px-3 py-2.5 pe-8 focus:ring-2 focus:ring-emerald-500 focus:outline-none dark:text-white"
                            />
                            <button
                                v-if="filterForm.search"
                                @click="
                                    filterForm.search = '';
                                    applyFilters();
                                "
                                class="absolute end-2.5 top-2.5 text-gray-400 hover:text-gray-600"
                            >
                                ×
                            </button>
                        </div>
                    </div>

                    <!-- Range Preset Selector -->
                    <div>
                        <label
                            class="block text-xs font-bold text-gray-600 dark:text-gray-300 mb-1"
                        >
                            الفترة الزمنية
                        </label>
                        <select
                            v-model="filterForm.range"
                            @change="onRangeChange"
                            class="w-full text-xs rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 px-3 py-2.5 focus:ring-2 focus:ring-emerald-500 focus:outline-none dark:text-white"
                        >
                            <option value="today">اليوم</option>
                            <option value="this_week">هذا الأسبوع</option>
                            <option value="this_month">هذا الشهر</option>
                            <option value="this_year">هذا العام</option>
                            <option value="custom">تاريخ مخصص (من - إلى)</option>
                            <option value="all">كل الفترات</option>
                        </select>
                    </div>

                    <!-- Payment Method Filter -->
                    <div>
                        <label
                            class="block text-xs font-bold text-gray-600 dark:text-gray-300 mb-1"
                        >
                            طريقة الدفع
                        </label>
                        <select
                            v-model="filterForm.method"
                            @change="applyFilters"
                            class="w-full text-xs rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 px-3 py-2.5 focus:ring-2 focus:ring-emerald-500 focus:outline-none dark:text-white"
                        >
                            <option value="all">جميع الطرق</option>
                            <option value="mada">مدى (Mada)</option>
                            <option value="cash">نقداً (Cash)</option>
                            <option value="transfer">تحويل بنكي</option>
                            <option value="apple_pay">Apple Pay</option>
                            <option value="visa">Visa</option>
                            <option value="mastercard">MasterCard</option>
                            <option value="stc_pay">STC Pay</option>
                            <option value="tabby">تابي</option>
                            <option value="tamara">تمارا</option>
                            <option value="credit">آجل</option>
                        </select>
                    </div>

                    <!-- Type Filter -->
                    <div>
                        <label
                            class="block text-xs font-bold text-gray-600 dark:text-gray-300 mb-1"
                        >
                            نوع العملية
                        </label>
                        <select
                            v-model="filterForm.type"
                            @change="applyFilters"
                            class="w-full text-xs rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 px-3 py-2.5 focus:ring-2 focus:ring-emerald-500 focus:outline-none dark:text-white"
                        >
                            <option value="all">جميع السندات</option>
                            <option value="payment">سند قبض (مدفوعات)</option>
                            <option value="refund">سند استرجاع (مرتجعات)</option>
                            <option value="bad_debt">ديون معدومة</option>
                        </select>
                    </div>
                </div>

                <!-- Custom Date Inputs (when range === 'custom') -->
                <div
                    v-if="filterForm.range === 'custom'"
                    class="pt-2 border-t border-gray-100 dark:border-gray-700/60 grid grid-cols-1 sm:grid-cols-2 gap-3"
                >
                    <div>
                        <label
                            class="block text-[11px] font-semibold text-gray-500 dark:text-gray-400 mb-1"
                        >
                            من تاريخ
                        </label>
                        <input
                            type="date"
                            v-model="filterForm.start_date"
                            class="w-full text-xs rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 px-3 py-2 text-gray-900 dark:text-white"
                        />
                    </div>
                    <div>
                        <label
                            class="block text-[11px] font-semibold text-gray-500 dark:text-gray-400 mb-1"
                        >
                            إلى تاريخ
                        </label>
                        <input
                            type="date"
                            v-model="filterForm.end_date"
                            class="w-full text-xs rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 px-3 py-2 text-gray-900 dark:text-white"
                        />
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2 pt-1">
                    <button
                        type="button"
                        @click="resetFilters"
                        class="px-3 py-1.5 rounded-lg text-xs font-semibold text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200"
                    >
                        إعادة ضبط
                    </button>
                    <button
                        type="button"
                        @click="applyFilters"
                        class="px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold shadow-sm transition-colors"
                    >
                        تطبيق الفلتر
                    </button>
                </div>
            </div>

            <!-- Table of Payments -->
            <div
                class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-800 shadow-sm overflow-hidden"
            >
                <div class="overflow-x-auto">
                    <table class="w-full text-start text-xs text-gray-700 dark:text-gray-300">
                        <thead
                            class="bg-gray-50 dark:bg-gray-900/60 text-gray-500 dark:text-gray-400 font-bold border-b border-gray-200 dark:border-gray-800 text-[11px]"
                        >
                            <tr>
                                <th class="px-4 py-3.5 text-start">رقم السند</th>
                                <th class="px-4 py-3.5 text-start">التاريخ والوقت</th>
                                <th class="px-4 py-3.5 text-start">العميل / المورد</th>
                                <th class="px-4 py-3.5 text-start">أمر العمل / الفاتورة</th>
                                <th class="px-4 py-3.5 text-start">المركبة</th>
                                <th class="px-4 py-3.5 text-start">طريقة الدفع</th>
                                <th class="px-4 py-3.5 text-start">نوع السند</th>
                                <th class="px-4 py-3.5 text-start">المبلغ</th>
                                <th class="px-4 py-3.5 text-start">المستلم</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800 font-medium">
                            <tr
                                v-for="payment in payments.data"
                                :key="payment.id"
                                class="hover:bg-gray-50/70 dark:hover:bg-gray-750 transition-colors"
                            >
                                <!-- Ref / ID -->
                                <td
                                    class="px-4 py-3 font-mono font-bold text-gray-900 dark:text-white"
                                >
                                    {{ payment.reference || 'REC-' + payment.id }}
                                </td>

                                <!-- Date -->
                                <td class="px-4 py-3 text-gray-500 dark:text-gray-400 text-[11px]">
                                    {{ formatDate(payment.payment_date) }}
                                </td>

                                <!-- Customer / Party -->
                                <td class="px-4 py-3">
                                    <div class="font-bold text-gray-900 dark:text-white">
                                        {{ getPartyName(payment) }}
                                    </div>
                                    <div
                                        v-if="getPartyPhone(payment)"
                                        class="text-[10px] text-gray-400 font-mono"
                                    >
                                        {{ getPartyPhone(payment) }}
                                    </div>
                                </td>

                                <!-- Links (Work Order / Invoice) -->
                                <td class="px-4 py-3">
                                    <div v-if="payment.work_order" class="flex items-center gap-1">
                                        <a
                                            :href="'/app/work-orders/' + payment.work_order.id"
                                            class="text-emerald-600 dark:text-emerald-400 hover:underline font-mono text-xs font-bold"
                                        >
                                            {{ payment.work_order.work_order_number }}
                                        </a>
                                    </div>
                                    <div v-if="payment.invoice" class="text-[11px] text-gray-500">
                                        <span>فاتورة:</span>
                                        <a
                                            :href="'/app/invoices/' + payment.invoice.id"
                                            class="hover:underline font-mono text-gray-700 dark:text-gray-300 font-semibold"
                                        >
                                            {{ payment.invoice.invoice_number }}
                                        </a>
                                    </div>
                                    <div
                                        v-if="!payment.work_order && !payment.invoice"
                                        class="text-gray-400 text-xs"
                                    >
                                        —
                                    </div>
                                </td>

                                <!-- Vehicle -->
                                <td class="px-4 py-3">
                                    <div v-if="payment.work_order?.vehicle" class="space-y-0.5">
                                        <span
                                            class="inline-block px-1.5 py-0.5 rounded bg-gray-100 dark:bg-gray-700 font-mono text-[10px] font-bold text-gray-800 dark:text-gray-200"
                                        >
                                            {{ payment.work_order.vehicle.plate_number }}
                                        </span>
                                        <div class="text-[10px] text-gray-500">
                                            {{ payment.work_order.vehicle.make }}
                                            {{ payment.work_order.vehicle.model }}
                                        </div>
                                    </div>
                                    <span v-else class="text-gray-400">—</span>
                                </td>

                                <!-- Payment Method -->
                                <td class="px-4 py-3">
                                    <span
                                        :class="getMethodBadgeClass(payment.payment_method)"
                                        class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold"
                                    >
                                        {{ getMethodLabel(payment.payment_method) }}
                                    </span>
                                </td>

                                <!-- Type -->
                                <td class="px-4 py-3">
                                    <span
                                        v-if="payment.type === 'refund'"
                                        class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-100 text-rose-800 dark:bg-rose-900/40 dark:text-rose-300"
                                    >
                                        استرجاع
                                    </span>
                                    <span
                                        v-else-if="payment.type === 'bad_debt'"
                                        class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300"
                                    >
                                        دين معدوم
                                    </span>
                                    <span
                                        v-else
                                        class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300"
                                    >
                                        سند قبض
                                    </span>
                                </td>

                                <!-- Amount -->
                                <td
                                    class="px-4 py-3 font-mono font-black text-sm"
                                    :class="
                                        payment.type === 'refund'
                                            ? 'text-rose-600 dark:text-rose-400'
                                            : 'text-gray-900 dark:text-white'
                                    "
                                >
                                    {{ payment.type === 'refund' ? '-' : ''
                                    }}{{ formatMoney(payment.amount) }}
                                </td>

                                <!-- Received By -->
                                <td class="px-4 py-3 text-xs text-gray-500 dark:text-gray-400">
                                    {{ payment.received_by?.name || '—' }}
                                </td>
                            </tr>

                            <!-- Empty State -->
                            <tr v-if="!payments.data || payments.data.length === 0">
                                <td colspan="9" class="px-6 py-12 text-center">
                                    <div
                                        class="w-12 h-12 mx-auto rounded-full bg-gray-100 dark:bg-gray-700 text-gray-400 flex items-center justify-center mb-3"
                                    >
                                        <svg
                                            class="w-6 h-6"
                                            fill="none"
                                            stroke="currentColor"
                                            viewBox="0 0 24 24"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                                            />
                                        </svg>
                                    </div>
                                    <p class="text-sm font-bold text-gray-700 dark:text-gray-300">
                                        لا توجد حركات دفع مسجلة
                                    </p>
                                    <p class="text-xs text-gray-400 mt-1">
                                        جرب تغيير الفترة الزمنية أو معايير الفلترة
                                    </p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination Footer -->
                <div
                    v-if="payments.links && payments.links.length > 3"
                    class="px-4 py-3 border-t border-gray-100 dark:border-gray-800 flex items-center justify-between text-xs print:hidden"
                >
                    <span class="text-gray-500">
                        عرض {{ payments.from || 0 }} إلى {{ payments.to || 0 }} من إجمالي
                        {{ payments.total }} سند
                    </span>
                    <div class="flex items-center gap-1">
                        <component
                            :is="link.url ? 'a' : 'span'"
                            v-for="(link, idx) in payments.links"
                            :key="idx"
                            :href="link.url"
                            v-html="link.label"
                            :class="[
                                'px-3 py-1.5 rounded-lg transition-colors font-medium',
                                link.active
                                    ? 'bg-emerald-600 text-white'
                                    : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700',
                                !link.url ? 'opacity-40 cursor-not-allowed' : '',
                            ]"
                        />
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { reactive, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    filters: {
        type: Object,
        default: () => ({ range: 'this_month', method: 'all', type: 'all', search: '' }),
    },
    kpis: {
        type: Object,
        default: () => ({
            total_collected: 0,
            total_refunded: 0,
            net_collected: 0,
            total_count: 0,
        }),
    },
    methodsBreakdown: {
        type: Array,
        default: () => [],
    },
    payments: {
        type: Object,
        default: () => ({ data: [], links: [] }),
    },
});

const filterForm = reactive({
    range: props.filters.range || 'this_month',
    start_date: props.filters.start_date || '',
    end_date: props.filters.end_date || '',
    method: props.filters.method || 'all',
    type: props.filters.type || 'all',
    search: props.filters.search || '',
});

const exportUrl = computed(() => {
    const params = new URLSearchParams({
        range: filterForm.range,
        start_date: filterForm.start_date || '',
        end_date: filterForm.end_date || '',
        method: filterForm.method,
        type: filterForm.type,
        search: filterForm.search,
    });
    return `/app/reports/payments/export?${params.toString()}`;
});

const applyFilters = () => {
    router.get(
        '/app/reports/payments',
        {
            range: filterForm.range,
            start_date: filterForm.range === 'custom' ? filterForm.start_date : '',
            end_date: filterForm.range === 'custom' ? filterForm.end_date : '',
            method: filterForm.method,
            type: filterForm.type,
            search: filterForm.search,
        },
        {
            preserveState: true,
            preserveScroll: true,
        }
    );
};

const onRangeChange = () => {
    if (filterForm.range !== 'custom') {
        applyFilters();
    }
};

const resetFilters = () => {
    filterForm.range = 'this_month';
    filterForm.start_date = '';
    filterForm.end_date = '';
    filterForm.method = 'all';
    filterForm.type = 'all';
    filterForm.search = '';
    applyFilters();
};

const handlePrint = () => {
    window.print();
};

const formatMoney = (val) => {
    const num = Number(val) || 0;
    return (
        new Intl.NumberFormat('en-US', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        }).format(num) + ' ر.س'
    );
};

const formatDate = (dateStr) => {
    if (!dateStr) return '—';
    try {
        const d = new Date(dateStr);
        return d.toLocaleDateString('ar-SA', {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
        });
    } catch {
        return dateStr;
    }
};

const getPartyName = (p) => {
    return (
        p.work_order?.customer?.name ||
        p.invoice?.customer?.name ||
        p.purchase_invoice?.supplier?.name ||
        'عميل نقدي'
    );
};

const getPartyPhone = (p) => {
    return p.work_order?.customer?.phone || p.invoice?.customer?.phone || '';
};

const getMethodLabel = (m) => {
    const map = {
        mada: 'مدى',
        cash: 'نقداً',
        transfer: 'تحويل بنكي',
        apple_pay: 'Apple Pay',
        visa: 'Visa',
        mastercard: 'MasterCard',
        stc_pay: 'STC Pay',
        tabby: 'تابي',
        tamara: 'تمارا',
        credit: 'آجل',
        other: 'أخرى',
    };
    return map[m] || m || 'غير محدد';
};

const getMethodBadgeClass = (m) => {
    switch (m) {
        case 'mada':
            return 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300';
        case 'cash':
            return 'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300';
        case 'apple_pay':
            return 'bg-gray-900 text-white dark:bg-gray-700 dark:text-gray-100';
        case 'transfer':
            return 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300';
        case 'visa':
        case 'mastercard':
            return 'bg-purple-100 text-purple-800 dark:bg-purple-900/40 dark:text-purple-300';
        default:
            return 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300';
    }
};
</script>

<style scoped>
@media print {
    body {
        background: white !important;
        color: black !important;
    }
}
</style>
