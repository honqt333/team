<template>
    <AppLayout>
        <div class="space-y-6">
            <!-- Screen Only Header Section (AUDIT_STANDARDS.md compliant) -->
            <div
                class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-2 border-b border-gray-200 dark:border-gray-800 print:hidden"
            >
                <div class="flex items-center gap-4">
                    <!-- Premium Back Button -->
                    <a
                        href="/app/reports"
                        class="p-2.5 rounded-2xl backdrop-blur-md bg-white/80 dark:bg-gray-800/80 border border-gray-200/80 dark:border-gray-700/80 text-indigo-600 dark:text-indigo-400 hover:shadow-md hover:border-indigo-300 dark:hover:border-indigo-600 transition-all duration-300 group shrink-0"
                        title="الرجوع لكافة التقارير"
                    >
                        <svg
                            class="w-5 h-5 rtl:rotate-180 group-hover:-translate-x-0.5 rtl:group-hover:translate-x-0.5 transition-transform"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M10 19l-7-7m0 0l7-7m-7 7h18"
                            />
                        </svg>
                    </a>

                    <div>
                        <div
                            class="flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400 mb-1"
                        >
                            <span>{{ $t('reports.title') }}</span>
                            <span>/</span>
                            <span class="text-indigo-600 dark:text-indigo-400 font-bold">
                                {{ $t('reports.payments') }}
                            </span>
                        </div>
                        <div class="flex items-center gap-3">
                            <div
                                class="w-12 h-12 rounded-2xl bg-gradient-to-br from-indigo-600 to-purple-600 text-white flex items-center justify-center font-bold shadow-lg shadow-indigo-500/25 shrink-0"
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
                                <h1
                                    class="text-2xl sm:text-3xl font-black text-gray-900 dark:text-white"
                                >
                                    {{ $t('reports.payments') }}
                                </h1>
                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                    كشف تحليلي وتفصيلي لجميع المقبوضات والمرتجعات وحركات السندات
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Export & Print Actions -->
                <div class="flex items-center gap-2.5 shrink-0">
                    <button
                        type="button"
                        @click="handlePrint"
                        class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-750 text-xs font-bold transition-all shadow-sm"
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
                        <span>طباعة التقرير</span>
                    </button>

                    <a
                        :href="exportUrl"
                        class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white text-xs font-bold transition-all shadow-lg shadow-indigo-500/25 hover:-translate-y-0.5"
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

            <!-- AUDIT_STANDARDS.md Section 5: Standard Official Print Section -->
            <div class="print-section hidden">
                <div class="grid grid-cols-3 items-center pb-4 mb-4 border-b-2 border-gray-800">
                    <!-- Column 1: Company Info -->
                    <div class="text-start space-y-1 text-xs">
                        <h2 class="text-base font-black text-black">{{ companyInfo.name }}</h2>
                        <p
                            v-if="companyInfo.center_name && centers?.length > 1"
                            class="font-bold text-gray-700"
                        >
                            الفرع: {{ companyInfo.center_name }}
                        </p>
                        <p v-if="companyInfo.vat_number" class="text-gray-600">
                            الرقم الضريبي:
                            <span class="font-mono font-bold">{{ companyInfo.vat_number }}</span>
                        </p>
                        <p v-if="companyInfo.cr_number" class="text-gray-600">
                            السجل التجاري:
                            <span class="font-mono">{{ companyInfo.cr_number }}</span>
                        </p>
                    </div>

                    <!-- Column 2 (Center): Official Logo -->
                    <div class="text-center flex flex-col items-center justify-center">
                        <img
                            v-if="companyInfo.logo_path"
                            :src="'/storage/' + companyInfo.logo_path"
                            alt="Logo"
                            class="h-14 w-auto object-contain mx-auto mb-1"
                        />
                        <div v-else class="text-center">
                            <span class="text-lg font-black tracking-wider text-black">CARAG</span>
                        </div>
                        <span class="text-xs font-bold text-gray-700 uppercase tracking-wider">
                            Payments Audit Report
                        </span>
                    </div>

                    <!-- Column 3: Report Metadata -->
                    <div class="text-end space-y-1 text-xs text-gray-600">
                        <p class="font-bold text-black">تقرير سندات القبض والمدفوعات</p>
                        <p>
                            الفترة:
                            <span class="font-mono">{{ filters.start_date || 'All' }}</span>
                            إلى
                            <span class="font-mono">{{ filters.end_date || 'Now' }}</span>
                        </p>
                        <p>
                            تاريخ الطباعة:
                            <span class="font-mono">{{ printDateFormatted }}</span>
                        </p>
                        <p>
                            إجمالي العمليات:
                            <span class="font-mono font-bold text-black">
                                {{ kpis.total_count }}
                            </span>
                        </p>
                    </div>
                </div>

                <!-- Print Financial Summary Banner -->
                <div
                    class="grid grid-cols-3 gap-2 mb-4 p-3 bg-gray-100 rounded border border-gray-300 text-center text-xs"
                >
                    <div>
                        <span class="text-gray-600 block">إجمالي المقبوضات:</span>
                        <strong class="font-mono text-sm">
                            {{ formatMoney(kpis.total_collected) }}
                        </strong>
                    </div>
                    <div>
                        <span class="text-gray-600 block">إجمالي المرتجعات:</span>
                        <strong class="font-mono text-sm text-red-700">
                            {{ formatMoney(kpis.total_refunded) }}
                        </strong>
                    </div>
                    <div>
                        <span class="text-gray-600 block">صافي التحصيل:</span>
                        <strong class="font-mono text-sm text-black">
                            {{ formatMoney(kpis.net_collected) }}
                        </strong>
                    </div>
                </div>
            </div>

            <!-- KPI Summary Cards (Screen View) -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 print:hidden">
                <!-- Net Collections -->
                <div
                    class="bg-white dark:bg-gray-800 rounded-3xl p-5 border border-indigo-100 dark:border-indigo-900/40 shadow-xl shadow-indigo-500/5 relative overflow-hidden"
                >
                    <div class="flex items-center justify-between">
                        <div>
                            <span
                                class="text-[11px] font-bold uppercase tracking-wider text-indigo-600 dark:text-indigo-400"
                            >
                                صافي التحصيل
                            </span>
                            <div
                                class="text-2xl font-black text-gray-900 dark:text-white mt-1 font-mono"
                            >
                                {{ formatMoney(kpis.net_collected) }}
                            </div>
                        </div>
                        <div
                            class="w-12 h-12 rounded-2xl bg-indigo-50 dark:bg-indigo-950/50 text-indigo-600 dark:text-indigo-400 flex items-center justify-center shadow-inner"
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
                    class="bg-white dark:bg-gray-800 rounded-3xl p-5 border border-blue-100 dark:border-blue-900/40 shadow-xl shadow-blue-500/5 relative overflow-hidden"
                >
                    <div class="flex items-center justify-between">
                        <div>
                            <span
                                class="text-[11px] font-bold uppercase tracking-wider text-blue-600 dark:text-blue-400"
                            >
                                إجمالي المقبوضات
                            </span>
                            <div
                                class="text-2xl font-black text-gray-900 dark:text-white mt-1 font-mono"
                            >
                                {{ formatMoney(kpis.total_collected) }}
                            </div>
                        </div>
                        <div
                            class="w-12 h-12 rounded-2xl bg-blue-50 dark:bg-blue-950/50 text-blue-600 dark:text-blue-400 flex items-center justify-center shadow-inner"
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
                    class="bg-white dark:bg-gray-800 rounded-3xl p-5 border border-rose-100 dark:border-rose-900/40 shadow-xl shadow-rose-500/5 relative overflow-hidden"
                >
                    <div class="flex items-center justify-between">
                        <div>
                            <span
                                class="text-[11px] font-bold uppercase tracking-wider text-rose-600 dark:text-rose-400"
                            >
                                المرتجعات
                            </span>
                            <div
                                class="text-2xl font-black text-rose-600 dark:text-rose-400 mt-1 font-mono"
                            >
                                {{ formatMoney(kpis.total_refunded) }}
                            </div>
                        </div>
                        <div
                            class="w-12 h-12 rounded-2xl bg-rose-50 dark:bg-rose-950/50 text-rose-600 dark:text-rose-400 flex items-center justify-center shadow-inner"
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
                    class="bg-white dark:bg-gray-800 rounded-3xl p-5 border border-purple-100 dark:border-purple-900/40 shadow-xl shadow-purple-500/5 relative overflow-hidden"
                >
                    <div class="flex items-center justify-between">
                        <div>
                            <span
                                class="text-[11px] font-bold uppercase tracking-wider text-purple-600 dark:text-purple-400"
                            >
                                عدد العمليات
                            </span>
                            <div
                                class="text-2xl font-black text-gray-900 dark:text-white mt-1 font-mono"
                            >
                                {{ kpis.total_count }}
                            </div>
                        </div>
                        <div
                            class="w-12 h-12 rounded-2xl bg-purple-50 dark:bg-purple-950/50 text-purple-600 dark:text-purple-400 flex items-center justify-center shadow-inner"
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
                class="bg-white dark:bg-gray-800 rounded-2xl p-4 border border-gray-200 dark:border-gray-800 shadow-sm print:hidden"
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
                        <span class="font-black text-indigo-600 dark:text-indigo-400 font-mono">
                            {{ formatMoney(item.total) }}
                        </span>
                        <span
                            class="text-[10px] px-1.5 py-0.5 rounded-full bg-gray-200 dark:bg-gray-600 text-gray-600 dark:text-gray-300 font-mono"
                        >
                            {{ item.count }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- AUDIT_STANDARDS.md Section 6: Advanced Filters Bar with SearchableSelect & CustomDatePicker -->
            <div
                class="bg-white dark:bg-gray-800 rounded-2xl p-4 border border-gray-200 dark:border-gray-800 shadow-sm space-y-4 print:hidden"
            >
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-3">
                    <!-- Search Input -->
                    <div>
                        <label
                            class="block text-xs font-bold text-gray-600 dark:text-gray-300 mb-1"
                        >
                            بحث نصي مباشر
                        </label>
                        <div class="relative">
                            <input
                                type="text"
                                v-model="filterForm.search"
                                @keyup.enter="applyFilters"
                                placeholder="رقم السند، المرجع، اللوحة، الفاتورة..."
                                class="w-full text-xs rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 px-3 py-2.5 pe-8 focus:ring-2 focus:ring-indigo-500 focus:outline-none dark:text-white"
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
                            class="w-full text-xs rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 px-3 py-2.5 focus:ring-2 focus:ring-indigo-500 focus:outline-none dark:text-white"
                        >
                            <option value="today">اليوم</option>
                            <option value="this_week">هذا الأسبوع</option>
                            <option value="this_month">هذا الشهر</option>
                            <option value="this_year">هذا العام</option>
                            <option value="custom">تاريخ مخصص (Custom Date)</option>
                            <option value="all">كل الفترات</option>
                        </select>
                    </div>

                    <!-- SearchableSelect: Payment Method -->
                    <div>
                        <label
                            class="block text-xs font-bold text-gray-600 dark:text-gray-300 mb-1"
                        >
                            طريقة الدفع
                        </label>
                        <SearchableSelect
                            v-model="filterForm.method"
                            :options="methodOptions"
                            optionLabel="name"
                            optionValue="id"
                            placeholder="اختر طريقة الدفع..."
                            searchPlaceholder="بحث في طرق الدفع..."
                            @update:modelValue="applyFilters"
                        />
                    </div>

                    <!-- SearchableSelect: Customer -->
                    <div>
                        <label
                            class="block text-xs font-bold text-gray-600 dark:text-gray-300 mb-1"
                        >
                            العميل
                        </label>
                        <SearchableSelect
                            v-model="filterForm.customer_id"
                            :options="customerOptions"
                            optionLabel="name"
                            optionValue="id"
                            placeholder="اختر العميل..."
                            searchPlaceholder="بحث باسم أو جوال العميل..."
                            @update:modelValue="applyFilters"
                        />
                    </div>
                </div>

                <!-- Custom Date Inputs using CustomDatePicker (AUDIT_STANDARDS.md Section 6) -->
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
                        <CustomDatePicker
                            v-model="filterForm.start_date"
                            placeholder="YYYY-MM-DD"
                        />
                    </div>
                    <div>
                        <label
                            class="block text-[11px] font-semibold text-gray-500 dark:text-gray-400 mb-1"
                        >
                            إلى تاريخ
                        </label>
                        <CustomDatePicker v-model="filterForm.end_date" placeholder="YYYY-MM-DD" />
                    </div>
                </div>

                <div
                    class="flex items-center justify-between pt-1 border-t border-gray-100 dark:border-gray-700/60"
                >
                    <div class="flex items-center gap-4 text-xs">
                        <label class="flex items-center gap-1.5 cursor-pointer">
                            <input
                                type="radio"
                                v-model="filterForm.type"
                                value="all"
                                @change="applyFilters"
                                class="text-indigo-600 focus:ring-indigo-500"
                            />
                            <span class="text-gray-700 dark:text-gray-300 font-medium">الكل</span>
                        </label>
                        <label class="flex items-center gap-1.5 cursor-pointer">
                            <input
                                type="radio"
                                v-model="filterForm.type"
                                value="payment"
                                @change="applyFilters"
                                class="text-emerald-600 focus:ring-emerald-500"
                            />
                            <span class="text-emerald-700 dark:text-emerald-400 font-bold">
                                سندات قبض
                            </span>
                        </label>
                        <label class="flex items-center gap-1.5 cursor-pointer">
                            <input
                                type="radio"
                                v-model="filterForm.type"
                                value="refund"
                                @change="applyFilters"
                                class="text-rose-600 focus:ring-rose-500"
                            />
                            <span class="text-rose-700 dark:text-rose-400 font-bold">
                                استرجاعات
                            </span>
                        </label>
                    </div>

                    <div class="flex items-center gap-2">
                        <button
                            type="button"
                            @click="resetFilters"
                            class="px-3 py-1.5 rounded-xl text-xs font-semibold text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 transition-colors"
                        >
                            إعادة ضبط
                        </button>
                        <button
                            type="button"
                            @click="applyFilters"
                            class="px-5 py-2 rounded-xl bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white text-xs font-bold shadow-md shadow-indigo-500/20 transition-all hover:-translate-y-0.5"
                        >
                            تطبيق الفلتر
                        </button>
                    </div>
                </div>
            </div>

            <!-- Table of Payments (Screen + Print) -->
            <div
                class="bg-white dark:bg-gray-800 rounded-3xl border border-gray-200 dark:border-gray-800 shadow-xl shadow-indigo-500/5 overflow-hidden"
            >
                <div class="overflow-x-auto">
                    <table class="w-full text-start text-xs text-gray-700 dark:text-gray-300">
                        <thead
                            class="bg-gray-50 dark:bg-gray-900/60 text-gray-500 dark:text-gray-400 font-bold border-b border-gray-200 dark:border-gray-800 text-[11px]"
                        >
                            <tr>
                                <th class="px-4 py-3.5 text-center">#</th>
                                <th class="px-4 py-3.5 text-center">رقم السند</th>
                                <th class="px-4 py-3.5 text-center">التاريخ والوقت</th>
                                <th class="px-4 py-3.5 text-center">الطرف (العميل / المورد)</th>
                                <th class="px-4 py-3.5 text-center">أمر العمل / الفاتورة</th>
                                <th class="px-4 py-3.5 text-center">المركبة</th>
                                <th class="px-4 py-3.5 text-center">طريقة الدفع</th>
                                <th class="px-4 py-3.5 text-center">نوع السند</th>
                                <th class="px-4 py-3.5 text-center">المبلغ</th>
                                <th class="px-4 py-3.5 text-center">المستلم</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800 font-medium">
                            <tr
                                v-for="(payment, index) in payments.data"
                                :key="payment.id"
                                class="hover:bg-gray-50/70 dark:hover:bg-gray-750 transition-colors text-center"
                            >
                                <!-- Row number -->
                                <td class="px-4 py-3 font-mono text-gray-400 text-xs">
                                    {{ (payments.from || 1) + index }}
                                </td>

                                <!-- Ref / ID -->
                                <td
                                    class="px-4 py-3 font-mono font-bold text-gray-900 dark:text-white"
                                >
                                    {{ payment.reference || 'REC-' + payment.id }}
                                </td>

                                <!-- Date (AUDIT_STANDARDS.md Section 3: English numbers) -->
                                <td
                                    class="px-4 py-3 font-mono text-gray-500 dark:text-gray-400 text-[11px]"
                                >
                                    {{ formatDate(payment.payment_date) }}
                                </td>

                                <!-- Customer / Party -->
                                <td class="px-4 py-3 text-start">
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
                                <td class="px-4 py-3 text-center">
                                    <div
                                        v-if="payment.work_order"
                                        class="flex items-center justify-center gap-1"
                                    >
                                        <a
                                            :href="'/app/work-orders/' + payment.work_order.id"
                                            class="text-indigo-600 dark:text-indigo-400 hover:underline font-mono text-xs font-bold"
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
                                <td class="px-4 py-3 text-center">
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
                                <td class="px-4 py-3 text-center">
                                    <span
                                        :class="getMethodBadgeClass(payment.payment_method)"
                                        class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold"
                                    >
                                        {{ getMethodLabel(payment.payment_method) }}
                                    </span>
                                </td>

                                <!-- Type -->
                                <td class="px-4 py-3 text-center">
                                    <span
                                        v-if="payment.type === 'refund'"
                                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-rose-100 text-rose-800 dark:bg-rose-900/40 dark:text-rose-300"
                                    >
                                        استرجاع
                                    </span>
                                    <span
                                        v-else-if="payment.type === 'bad_debt'"
                                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300"
                                    >
                                        دين معدوم
                                    </span>
                                    <span
                                        v-else
                                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300"
                                    >
                                        سند قبض
                                    </span>
                                </td>

                                <!-- Amount (AUDIT_STANDARDS.md Section 3: English numbers) -->
                                <td
                                    class="px-4 py-3 font-mono font-black text-sm text-center"
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
                                <td
                                    class="px-4 py-3 text-xs text-gray-500 dark:text-gray-400 text-center"
                                >
                                    {{ payment.received_by?.name || '—' }}
                                </td>
                            </tr>

                            <!-- Empty State -->
                            <tr v-if="!payments.data || payments.data.length === 0">
                                <td colspan="10" class="px-6 py-12 text-center">
                                    <div
                                        class="w-14 h-14 mx-auto rounded-2xl bg-gray-100 dark:bg-gray-700 text-gray-400 flex items-center justify-center mb-3"
                                    >
                                        <svg
                                            class="w-7 h-7"
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
                        عرض
                        <span class="font-mono">{{ payments.from || 0 }}</span>
                        إلى
                        <span class="font-mono">{{ payments.to || 0 }}</span>
                        من إجمالي
                        <span class="font-mono font-bold">{{ payments.total }}</span>
                        سند
                    </span>
                    <div class="flex items-center gap-1">
                        <component
                            :is="link.url ? 'a' : 'span'"
                            v-for="(link, idx) in payments.links"
                            :key="idx"
                            :href="link.url"
                            v-html="link.label"
                            :class="[
                                'px-3 py-1.5 rounded-xl transition-all text-xs font-mono font-bold',
                                link.active
                                    ? 'bg-gradient-to-r from-indigo-600 to-purple-600 text-white shadow-sm'
                                    : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-750',
                                !link.url ? 'opacity-40 cursor-not-allowed' : '',
                            ]"
                        />
                    </div>
                </div>
            </div>

            <!-- AUDIT_STANDARDS.md Section 5: Standard Print Footer -->
            <div
                class="print-footer hidden print:block pt-4 mt-6 border-t border-gray-300 text-center text-xs text-gray-600"
            >
                <p>
                    Prepared by:
                    <span class="font-bold">
                        {{ $page.props.auth?.user?.name || 'Administrator' }}
                    </span>
                    | Printed on:
                    <span class="font-mono">{{ printDateFormatted }}</span>
                </p>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { reactive, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import CustomDatePicker from '@/Components/CustomDatePicker.vue';

const props = defineProps({
    filters: {
        type: Object,
        default: () => ({
            range: 'this_month',
            method: 'all',
            type: 'all',
            customer_id: 'all',
            search: '',
        }),
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
    customers: {
        type: Array,
        default: () => [],
    },
    centers: {
        type: Array,
        default: () => [],
    },
    companyInfo: {
        type: Object,
        default: () => ({
            name: '',
            vat_number: '',
            cr_number: '',
            logo_path: '',
            center_name: '',
        }),
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
    customer_id: props.filters.customer_id || 'all',
    search: props.filters.search || '',
});

const methodOptions = computed(() => [
    { id: 'all', name: 'جميع الطرق (All Methods)' },
    { id: 'mada', name: 'مدى (Mada)' },
    { id: 'cash', name: 'نقداً (Cash)' },
    { id: 'transfer', name: 'تحويل بنكي (Transfer)' },
    { id: 'apple_pay', name: 'Apple Pay' },
    { id: 'visa', name: 'Visa' },
    { id: 'mastercard', name: 'MasterCard' },
    { id: 'stc_pay', name: 'STC Pay' },
    { id: 'tabby', name: 'تابي (Tabby)' },
    { id: 'tamara', name: 'تمارا (Tamara)' },
    { id: 'credit', name: 'آجل (Credit)' },
]);

const customerOptions = computed(() => [
    { id: 'all', name: 'جميع العملاء (All Customers)' },
    ...props.customers.map((c) => ({
        id: c.id,
        name: c.name + (c.phone ? ` (${c.phone})` : ''),
    })),
]);

const exportUrl = computed(() => {
    const params = new URLSearchParams({
        range: filterForm.range,
        start_date: filterForm.start_date || '',
        end_date: filterForm.end_date || '',
        method: filterForm.method,
        type: filterForm.type,
        customer_id: filterForm.customer_id,
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
            customer_id: filterForm.customer_id,
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
    filterForm.customer_id = 'all';
    filterForm.search = '';
    applyFilters();
};

const handlePrint = () => {
    window.print();
};

// AUDIT_STANDARDS.md Section 3: All numbers & prices formatted in English digits (0-9)
const formatMoney = (val) => {
    const num = Number(val) || 0;
    return (
        new Intl.NumberFormat('en-US', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        }).format(num) + ' SAR'
    );
};

const formatDate = (dateStr) => {
    if (!dateStr) return '—';
    try {
        const d = new Date(dateStr);
        return d.toLocaleDateString('en-GB', {
            year: 'numeric',
            month: '2-digit',
            day: '2-digit',
            hour: '2-digit',
            minute: '2-digit',
        });
    } catch {
        return dateStr;
    }
};

const printDateFormatted = computed(() => {
    return new Date().toLocaleString('en-GB', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
    });
});

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
/* AUDIT_STANDARDS.md Section 5: Standard Printing */
@media print {
    body {
        background: white !important;
        color: black !important;
        font-family: 'Tajawal', 'Noto Kufi Arabic', sans-serif !important;
    }

    .print-section {
        display: block !important;
    }

    .print-footer {
        display: block !important;
    }

    table {
        width: 100% !important;
        border-collapse: collapse !important;
        font-size: 11px !important;
    }

    th,
    td {
        border: 1px solid #d1d5db !important;
        padding: 6px 4px !important;
        text-align: center !important;
    }

    th {
        background-color: #f3f4f6 !important;
        color: #111827 !important;
        font-weight: bold !important;
    }
}
</style>
