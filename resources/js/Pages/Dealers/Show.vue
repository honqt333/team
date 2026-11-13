<template>
    <Head :title="dealer.name" />
    <AppLayout :title="dealer.name">
        <div class="space-y-6">
            <!-- Breadcrumbs -->
            <Breadcrumb :items="breadcrumbItems" class="px-1" />

            <!-- Profile Hero Card with Glow Effect -->
            <div class="relative group">
                <div
                    class="absolute -inset-1 bg-action-primary hover:bg-action-primary-hover rounded-[2.5rem] blur opacity-[0.06] group-hover:opacity-[0.10] transition duration-700"
                ></div>

                <div
                    class="relative bg-white dark:bg-gray-800 rounded-[2.25rem] shadow-2xl shadow-focus-ring-soft border border-gray-100 dark:border-gray-700 overflow-hidden"
                >
                    <!-- Top Action Bar -->
                    <div
                        class="absolute top-0 inset-x-0 p-4 flex items-center justify-between z-20"
                        :dir="isRtl ? 'rtl' : 'ltr'"
                    >
                        <BackButton :href="route('app.dealers.index')" />

                        <div
                            class="flex items-center gap-2 p-2 bg-white/80 dark:bg-gray-800/80 backdrop-blur-md rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm"
                        >
                            <!-- Statement Modal Button -->
                            <button
                                type="button"
                                @click="showStatementModal = true"
                                :title="$t('dealers.show.statement_title')"
                                class="inline-flex items-center gap-2 px-3 py-2 rounded-xl font-black text-xs border border-purple-100/70 dark:border-purple-800/30 bg-purple-50/70 dark:bg-purple-900/20 text-purple-700 dark:text-purple-300 hover:bg-white dark:hover:bg-purple-900/40 hover:shadow-md transition-all cursor-pointer"
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
                                        stroke-width="2.5"
                                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                                    />
                                </svg>
                                <span class="hidden sm:inline">
                                    {{ $t('dealers.show.statement_title') }}
                                </span>
                            </button>

                            <!-- Toggle Status Button -->
                            <button
                                v-if="can('crm.customers.update') || isAnyAdmin()"
                                type="button"
                                @click="toggleActiveStatus"
                                :title="
                                    dealer.is_active
                                        ? $t('dealers.show.status_toggle_inactive')
                                        : $t('dealers.show.status_toggle_active')
                                "
                                :class="[
                                    'inline-flex items-center gap-2 px-3 py-2 rounded-xl font-black text-xs border transition-all cursor-pointer',
                                    dealer.is_active
                                        ? 'border-emerald-200/70 dark:border-emerald-800/30 bg-emerald-50/70 dark:bg-emerald-900/20 text-emerald-700 dark:text-emerald-300 hover:bg-white dark:hover:bg-emerald-900/40 hover:shadow-md'
                                        : 'border-gray-200 dark:border-gray-700 bg-gray-100/70 dark:bg-gray-800/40 text-gray-600 dark:text-gray-400 hover:bg-white hover:shadow-md',
                                ]"
                            >
                                <span
                                    class="w-2 h-2 rounded-full"
                                    :class="
                                        dealer.is_active
                                            ? 'bg-emerald-500 animate-pulse'
                                            : 'bg-gray-400'
                                    "
                                ></span>
                                <span class="hidden sm:inline">
                                    {{
                                        dealer.is_active
                                            ? $t('dealers.status_active')
                                            : $t('dealers.status_inactive')
                                    }}
                                </span>
                            </button>

                            <!-- Edit Button -->
                            <button
                                v-if="can('crm.customers.update') || isAnyAdmin()"
                                type="button"
                                @click="showEditModal = true"
                                :title="$t('common.edit')"
                                class="inline-flex items-center gap-2 px-3 py-2 rounded-xl font-black text-xs border border-action-primary/25 bg-selection-bg text-selection-text hover:bg-white dark:hover:bg-gray-800 hover:shadow-md transition-all cursor-pointer"
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
                                        stroke-width="2.5"
                                        d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"
                                    />
                                </svg>
                                <span class="hidden sm:inline">{{ $t('common.edit') }}</span>
                            </button>

                            <!-- Delete Button -->
                            <button
                                v-if="can('crm.customers.delete') || isAnyAdmin()"
                                type="button"
                                @click="confirmDelete"
                                :disabled="!canDelete"
                                :title="
                                    canDelete
                                        ? $t('common.delete')
                                        : $t('dealers.show.cannot_delete_has_data')
                                "
                                :class="[
                                    'inline-flex items-center gap-2 px-3 py-2 rounded-xl font-black text-xs border transition-all',
                                    canDelete
                                        ? 'border-red-100/70 dark:border-red-800/30 bg-red-50/70 dark:bg-red-900/20 text-red-700 dark:text-red-300 hover:bg-white dark:hover:bg-red-900/30 hover:shadow-md cursor-pointer'
                                        : 'border-gray-200 dark:border-gray-700 bg-gray-100/70 dark:bg-gray-900/30 text-gray-400 cursor-not-allowed opacity-70',
                                ]"
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
                                        stroke-width="2.5"
                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                                    />
                                </svg>
                                <span class="hidden sm:inline">{{ $t('common.delete') }}</span>
                            </button>
                        </div>
                    </div>

                    <!-- Watermark Background Graphic -->
                    <div
                        class="absolute top-0 right-0 p-12 opacity-[0.03] dark:opacity-[0.05] pointer-events-none select-none"
                    >
                        <svg
                            class="w-80 h-80 text-selection-text dark:text-white"
                            fill="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"
                            />
                        </svg>
                    </div>

                    <!-- Hero Body -->
                    <div class="relative z-10 p-6 pt-16">
                        <div class="flex flex-col lg:flex-row lg:items-end justify-between gap-6">
                            <!-- Left: Profile Info -->
                            <div
                                class="flex flex-col sm:flex-row items-center sm:items-start text-center sm:text-start gap-6 flex-1"
                            >
                                <!-- Avatar Box -->
                                <div class="relative">
                                    <div
                                        class="w-20 h-20 lg:w-24 lg:h-24 rounded-[2.25rem] bg-action-primary hover:bg-action-primary-hover shadow-xl shadow-focus-ring-soft flex items-center justify-center text-white ring-4 ring-action-primary/20 transition-transform group-hover:scale-[1.03] duration-500"
                                    >
                                        <span class="text-3xl lg:text-5xl font-black">
                                            {{ dealer.name?.charAt(0)?.toUpperCase() || '?' }}
                                        </span>
                                    </div>
                                    <div
                                        class="absolute -bottom-2 -right-2 w-10 h-10 rounded-2xl bg-white dark:bg-gray-700 shadow-lg border-4 border-action-primary/25 flex items-center justify-center"
                                    >
                                        <svg
                                            class="w-5 h-5 text-indigo-500"
                                            fill="none"
                                            stroke="currentColor"
                                            viewBox="0 0 24 24"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"
                                            />
                                        </svg>
                                    </div>
                                </div>

                                <!-- Text Details -->
                                <div class="flex-1">
                                    <div
                                        class="flex flex-wrap items-center justify-center sm:justify-start gap-2 lg:gap-3 mb-1"
                                    >
                                        <h1
                                            class="text-2xl lg:text-4xl font-black text-gray-900 dark:text-white leading-tight tracking-tight"
                                        >
                                            {{ dealer.name }}
                                        </h1>
                                        <div
                                            class="px-3 py-1.5 rounded-xl text-[10px] font-black tracking-[0.2em] uppercase border shadow-sm bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 border-indigo-100 dark:border-indigo-800/30"
                                        >
                                            {{ $t(`dealers.types.${dealer.type}`) || dealer.type }}
                                        </div>
                                        <div
                                            class="px-3 py-1.5 rounded-xl text-[10px] font-black tracking-[0.2em] uppercase border shadow-sm"
                                            :class="
                                                dealer.is_active
                                                    ? 'bg-emerald-50 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400 border-emerald-100 dark:border-emerald-800/30'
                                                    : 'bg-gray-100 dark:bg-gray-800 text-gray-500 border-gray-200 dark:border-gray-700'
                                            "
                                        >
                                            {{
                                                dealer.is_active
                                                    ? $t('dealers.status_active')
                                                    : $t('dealers.status_inactive')
                                            }}
                                        </div>
                                    </div>

                                    <p
                                        v-if="dealer.name_en"
                                        class="text-sm font-semibold text-gray-500 dark:text-gray-400 mb-3"
                                        dir="ltr"
                                    >
                                        {{ dealer.name_en }}
                                    </p>

                                    <!-- Information Badges & Contact Pills -->
                                    <div
                                        class="flex flex-wrap items-center justify-center sm:justify-start gap-3 text-sm font-bold text-gray-500 dark:text-gray-400 mt-2"
                                    >
                                        <!-- Contact Person -->
                                        <span
                                            v-if="dealer.contact_name"
                                            class="flex items-center gap-2.5 px-4 py-2.5 rounded-2xl bg-selection-bg text-selection-text border border-action-primary/25"
                                        >
                                            <svg
                                                class="w-5 h-5 text-action-primary"
                                                fill="none"
                                                stroke="currentColor"
                                                viewBox="0 0 24 24"
                                            >
                                                <path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    stroke-width="2"
                                                    d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"
                                                />
                                            </svg>
                                            {{ dealer.contact_name }}
                                        </span>

                                        <!-- Phone -->
                                        <a
                                            v-if="dealer.phone"
                                            :href="`tel:${dealer.phone}`"
                                            class="flex items-center gap-3 px-4 py-2.5 rounded-2xl bg-gray-50/50 dark:bg-gray-700/30 hover:bg-white dark:hover:bg-gray-700 hover:text-action-primary hover:shadow-xl hover:shadow-focus-ring-soft transition-all border border-transparent hover:border-action-primary/25"
                                            dir="ltr"
                                        >
                                            <div
                                                class="w-8 h-8 rounded-xl bg-white dark:bg-gray-800 shadow-sm flex items-center justify-center"
                                            >
                                                <svg
                                                    class="w-4.5 h-4.5 text-action-primary"
                                                    fill="none"
                                                    stroke="currentColor"
                                                    viewBox="0 0 24 24"
                                                >
                                                    <path
                                                        stroke-linecap="round"
                                                        stroke-linejoin="round"
                                                        stroke-width="2.5"
                                                        d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"
                                                    />
                                                </svg>
                                            </div>
                                            {{ dealer.phone }}
                                        </a>

                                        <!-- WhatsApp -->
                                        <a
                                            v-if="dealer.whatsapp || dealer.phone"
                                            :href="`https://wa.me/${getWhatsAppNumber(dealer)}`"
                                            target="_blank"
                                            class="flex items-center gap-3 px-4 py-2.5 rounded-2xl bg-emerald-50/50 dark:bg-emerald-900/20 text-emerald-700 dark:text-emerald-400 hover:bg-white dark:hover:bg-emerald-800 hover:shadow-xl hover:shadow-emerald-500/5 transition-all border border-transparent hover:border-emerald-100"
                                            dir="ltr"
                                        >
                                            <div
                                                class="w-8 h-8 rounded-xl bg-white dark:bg-gray-800 shadow-sm flex items-center justify-center"
                                            >
                                                <svg
                                                    class="w-4.5 h-4.5 text-emerald-500"
                                                    fill="currentColor"
                                                    viewBox="0 0 24 24"
                                                >
                                                    <path
                                                        d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"
                                                    />
                                                </svg>
                                            </div>
                                            {{ dealer.whatsapp || dealer.phone }}
                                        </a>

                                        <!-- Email -->
                                        <a
                                            v-if="dealer.email"
                                            :href="`mailto:${dealer.email}`"
                                            :title="$t('common.email')"
                                            class="flex items-center gap-3 px-4 py-2.5 rounded-2xl bg-purple-50/50 dark:bg-purple-900/20 hover:bg-white dark:hover:bg-gray-800 hover:text-purple-700 dark:hover:text-purple-300 hover:shadow-xl hover:shadow-focus-ring-soft transition-all border border-transparent hover:border-purple-100 dark:hover:border-purple-800/50"
                                            dir="ltr"
                                        >
                                            <div
                                                class="w-8 h-8 rounded-xl bg-white dark:bg-gray-800 shadow-sm flex items-center justify-center"
                                            >
                                                <svg
                                                    class="w-4.5 h-4.5 text-purple-500"
                                                    fill="none"
                                                    stroke="currentColor"
                                                    viewBox="0 0 24 24"
                                                >
                                                    <path
                                                        stroke-linecap="round"
                                                        stroke-linejoin="round"
                                                        stroke-width="2.5"
                                                        d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"
                                                    />
                                                </svg>
                                            </div>
                                            <span class="max-w-[220px] truncate">
                                                {{ dealer.email }}
                                            </span>
                                        </a>

                                        <!-- VAT Number -->
                                        <div
                                            v-if="dealer.tax_number"
                                            :title="$t('dealers.fields.tax_number')"
                                            class="flex items-center gap-3 px-4 py-2.5 rounded-2xl bg-blue-50/50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-300 border border-blue-100/60 dark:border-blue-800/40"
                                        >
                                            <div
                                                class="w-8 h-8 rounded-xl bg-white dark:bg-gray-800 shadow-sm flex items-center justify-center font-black text-xs text-blue-600"
                                            >
                                                VAT
                                            </div>
                                            <span class="font-mono text-xs font-bold" dir="ltr">
                                                {{ dealer.tax_number }}
                                            </span>
                                        </div>

                                        <!-- National Address -->
                                        <div
                                            v-if="headerAddress"
                                            :title="
                                                $t('dealers.fields.address_line') +
                                                ': ' +
                                                headerAddress
                                            "
                                            class="flex items-center gap-3 px-4 py-2.5 rounded-2xl bg-amber-50/50 dark:bg-amber-900/20 hover:bg-white dark:hover:bg-amber-900/30 hover:shadow-xl hover:shadow-amber-500/5 transition-all border border-transparent hover:border-amber-100"
                                        >
                                            <div
                                                class="w-8 h-8 rounded-xl bg-white dark:bg-gray-800 shadow-sm flex items-center justify-center"
                                            >
                                                <svg
                                                    class="w-4.5 h-4.5 text-amber-500"
                                                    fill="none"
                                                    stroke="currentColor"
                                                    viewBox="0 0 24 24"
                                                >
                                                    <path
                                                        stroke-linecap="round"
                                                        stroke-linejoin="round"
                                                        stroke-width="2.5"
                                                        d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"
                                                    />
                                                    <path
                                                        stroke-linecap="round"
                                                        stroke-linejoin="round"
                                                        stroke-width="2.5"
                                                        d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"
                                                    />
                                                </svg>
                                            </div>
                                            <span class="max-w-[320px] truncate">
                                                {{ headerAddress }}
                                            </span>
                                        </div>

                                        <!-- Credit Limit & Payment Terms -->
                                        <div
                                            v-if="dealer.credit_limit || dealer.payment_terms_days"
                                            class="flex items-center gap-2.5 px-4 py-2.5 rounded-2xl bg-selection-bg text-selection-text border border-action-primary/25 text-xs font-bold"
                                        >
                                            <svg
                                                class="w-4 h-4 text-action-primary"
                                                fill="none"
                                                stroke="currentColor"
                                                viewBox="0 0 24 24"
                                            >
                                                <path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    stroke-width="2"
                                                    d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"
                                                />
                                            </svg>
                                            <span v-if="dealer.credit_limit">
                                                {{ $t('dealers.fields.credit_limit') }}:
                                                <strong
                                                    class="font-mono text-gray-900 dark:text-white"
                                                >
                                                    {{ formatPrice(dealer.credit_limit) }}
                                                </strong>
                                            </span>
                                            <span
                                                v-if="
                                                    dealer.credit_limit && dealer.payment_terms_days
                                                "
                                                class="text-border-strong"
                                            >
                                                |
                                            </span>
                                            <span v-if="dealer.payment_terms_days">
                                                {{ $t('dealers.fields.payment_terms_days') }}:
                                                <strong
                                                    class="font-mono text-gray-900 dark:text-white"
                                                >
                                                    {{ dealer.payment_terms_days }}
                                                </strong>
                                                {{ $t('dealers.show.days_unit') }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Right: Stat KPI Cards -->
                            <div
                                class="grid grid-cols-2 sm:grid-cols-4 lg:flex lg:flex-wrap items-center gap-2 lg:gap-3 w-full lg:w-auto"
                            >
                                <!-- Vehicles Count -->
                                <div
                                    class="group/stat flex flex-col items-center justify-center h-20 lg:w-24 lg:h-24 bg-teal-50/50 dark:bg-teal-900/20 rounded-3xl border border-teal-100/50 dark:border-teal-800/30 hover:bg-white dark:hover:bg-teal-900/40 transition-all hover:shadow-lg hover:shadow-teal-500/10"
                                >
                                    <span
                                        class="text-2xl font-black text-teal-600 dark:text-teal-400 group-hover/stat:scale-110 transition-transform"
                                    >
                                        {{ formatNumber(counts.vehicles, 0) }}
                                    </span>
                                    <span
                                        class="text-[10px] font-black text-gray-400 uppercase tracking-widest mt-1 text-center"
                                    >
                                        {{ $t('dealers.columns.vehicles_count') }}
                                    </span>
                                </div>

                                <!-- Work Orders Count -->
                                <div
                                    class="group/stat flex flex-col items-center justify-center h-20 lg:w-24 lg:h-24 bg-selection-bg rounded-3xl border border-action-primary/25 hover:bg-white transition-all hover:shadow-lg hover:shadow-focus-ring-soft"
                                >
                                    <span
                                        class="text-2xl font-black text-action-primary group-hover/stat:scale-110 transition-transform"
                                    >
                                        {{ formatNumber(counts.workOrders, 0) }}
                                    </span>
                                    <span
                                        class="text-[10px] font-black text-gray-400 uppercase tracking-widest mt-1 text-center"
                                    >
                                        {{ $t('dealers.columns.work_orders_count') }}
                                    </span>
                                </div>

                                <!-- Invoices Count -->
                                <div
                                    class="group/stat flex flex-col items-center justify-center h-20 lg:w-24 lg:h-24 bg-rose-50/50 dark:bg-rose-900/20 rounded-3xl border border-rose-100/50 dark:border-rose-800/30 hover:bg-white dark:hover:bg-rose-900/40 transition-all hover:shadow-lg hover:shadow-rose-500/10"
                                >
                                    <span
                                        class="text-2xl font-black text-rose-600 dark:text-rose-400 group-hover/stat:scale-110 transition-transform"
                                    >
                                        {{ formatNumber(counts.invoices || 0, 0) }}
                                    </span>
                                    <span
                                        class="text-[10px] font-black text-gray-400 uppercase tracking-widest mt-1 text-center"
                                    >
                                        {{ $t('dealers.show.invoices_tab') }}
                                    </span>
                                </div>

                                <!-- Balance Due (Clickable to open Statement Modal) -->
                                <button
                                    type="button"
                                    @click="showStatementModal = true"
                                    class="group/stat flex flex-col items-center justify-center h-20 lg:w-28 lg:h-24 px-2 rounded-3xl border transition-all hover:shadow-lg cursor-pointer text-center"
                                    :class="
                                        (financialSummary?.total_due || 0) > 0
                                            ? 'bg-rose-50/70 dark:bg-rose-900/30 border-rose-200 dark:border-rose-800/50 hover:bg-white dark:hover:bg-rose-900/50 hover:shadow-rose-500/15'
                                            : 'bg-emerald-50/50 dark:bg-emerald-900/20 border-emerald-100/50 dark:border-emerald-800/30 hover:bg-white dark:hover:bg-emerald-900/40 hover:shadow-emerald-500/10'
                                    "
                                >
                                    <span
                                        class="text-sm font-black font-mono transition-transform group-hover/stat:scale-105"
                                        :class="
                                            (financialSummary?.total_due || 0) > 0
                                                ? 'text-rose-600 dark:text-rose-400'
                                                : 'text-emerald-600 dark:text-emerald-400'
                                        "
                                    >
                                        {{ formatPrice(financialSummary?.total_due || 0) }}
                                    </span>
                                    <span
                                        class="text-[9px] font-black uppercase tracking-wider mt-1"
                                        :class="
                                            (financialSummary?.total_due || 0) > 0
                                                ? 'text-rose-500'
                                                : 'text-emerald-500'
                                        "
                                    >
                                        {{ $t('dealers.show.balance_due') }}
                                    </span>
                                </button>
                            </div>
                        </div>

                        <!-- Credit Limit Utilization Bar (Shown when limit is configured) -->
                        <div
                            v-if="dealer.credit_limit > 0"
                            class="mt-6 pt-5 border-t border-gray-100 dark:border-gray-700/70"
                        >
                            <div
                                class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mb-2"
                            >
                                <div class="flex items-center gap-2">
                                    <span
                                        class="text-xs font-black text-gray-700 dark:text-gray-300"
                                    >
                                        {{ $t('dealers.show.credit_utilization') }}
                                    </span>
                                    <span
                                        v-if="dealer.is_credit_exceeded"
                                        class="px-2 py-0.5 rounded-full text-[10px] font-black bg-rose-100 text-rose-700 dark:bg-rose-900/40 dark:text-rose-300 animate-pulse"
                                    >
                                        {{ $t('dealers.show.credit_exceeded_warning') }}
                                    </span>
                                </div>
                                <div class="flex items-center gap-4 text-xs font-mono">
                                    <span class="text-gray-500">
                                        {{ $t('dealers.show.credit_used') }}:
                                        <strong class="text-gray-900 dark:text-white font-bold">
                                            {{ formatPrice(financialSummary?.total_due || 0) }}
                                        </strong>
                                        / {{ formatPrice(dealer.credit_limit) }}
                                    </span>
                                    <span
                                        class="font-black px-2 py-0.5 rounded-lg"
                                        :class="
                                            creditUsagePercent >= 100
                                                ? 'bg-rose-100 text-rose-700 dark:bg-rose-900/40 dark:text-rose-300'
                                                : creditUsagePercent >= 80
                                                  ? 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300'
                                                  : 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300'
                                        "
                                    >
                                        {{ creditUsagePercent }}%
                                    </span>
                                </div>
                            </div>

                            <!-- Progress Track -->
                            <div
                                class="w-full h-2.5 bg-gray-100 dark:bg-gray-700 rounded-full overflow-hidden shadow-inner"
                            >
                                <div
                                    class="h-full rounded-full transition-all duration-700"
                                    :class="
                                        creditUsagePercent >= 100
                                            ? 'bg-gradient-to-r from-rose-500 to-red-600'
                                            : creditUsagePercent >= 80
                                              ? 'bg-gradient-to-r from-amber-500 to-orange-500'
                                              : 'bg-gradient-to-r from-emerald-500 to-teal-500'
                                    "
                                    :style="{ width: `${Math.min(100, creditUsagePercent)}%` }"
                                ></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Notes Section -->
            <div v-if="dealer.notes" class="relative group">
                <div
                    class="absolute -inset-1 bg-gradient-to-r from-amber-500 to-orange-600 rounded-[2rem] blur opacity-[0.04] group-hover:opacity-[0.08] transition duration-700"
                ></div>
                <div
                    class="relative bg-white dark:bg-gray-800 rounded-[1.75rem] shadow-xl border border-amber-100/50 dark:border-amber-900/20 p-6 overflow-hidden"
                >
                    <div
                        class="absolute top-0 right-0 p-6 opacity-[0.02] pointer-events-none select-none"
                    >
                        <svg
                            class="w-24 h-24 text-amber-900 dark:text-white"
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
                    <div class="flex items-start gap-4">
                        <div
                            class="w-12 h-12 rounded-2xl bg-amber-50 dark:bg-amber-900/30 flex items-center justify-center text-amber-600 dark:text-amber-400 shadow-inner shrink-0"
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
                                    d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"
                                />
                            </svg>
                        </div>
                        <div class="flex-1 space-y-1 text-start">
                            <h3
                                class="text-xs font-black text-amber-600 dark:text-amber-400 uppercase tracking-widest"
                            >
                                {{ $t('dealers.fields.notes') }}
                            </h3>
                            <p
                                class="text-sm font-medium text-gray-700 dark:text-gray-300 whitespace-pre-line leading-relaxed"
                            >
                                {{ dealer.notes }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabs Section -->
            <div class="space-y-4">
                <AppTabs v-model="activeTab" :tabs="tabs" />

                <!-- Tab Content Container -->
                <div
                    class="bg-white dark:bg-gray-800 rounded-[2rem] shadow-2xl shadow-focus-ring-soft border border-gray-100/50 dark:border-gray-700/50 p-6 min-h-[400px] relative overflow-hidden"
                >
                    <!-- Background Decoration -->
                    <div
                        class="absolute top-0 right-0 p-16 opacity-[0.01] dark:opacity-[0.02] pointer-events-none select-none -translate-y-8 translate-x-8"
                    >
                        <component
                            :is="tabs.find((t) => t.id === activeTab)?.icon"
                            class="w-64 h-64"
                        />
                    </div>

                    <!-- ========== WORK ORDERS TAB ========== -->
                    <div v-if="activeTab === 'work_orders'" class="space-y-6">
                        <!-- Toolbar -->
                        <div
                            class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3"
                        >
                            <div class="flex items-center gap-3">
                                <!-- Search -->
                                <div class="relative group">
                                    <div
                                        class="absolute inset-y-0 start-0 ps-3 flex items-center pointer-events-none"
                                    >
                                        <svg
                                            class="w-4 h-4 text-gray-400"
                                            fill="none"
                                            stroke="currentColor"
                                            viewBox="0 0 24 24"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
                                            />
                                        </svg>
                                    </div>
                                    <input
                                        type="text"
                                        v-model="workOrderSearch"
                                        :placeholder="$t('dealers.show.search_work_orders')"
                                        class="w-full sm:w-64 ps-10 pe-4 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-xl bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-focus-ring"
                                    />
                                </div>
                                <!-- View Toggle -->
                                <AppViewModeToggle
                                    v-model="workOrderViewMode"
                                    storage-key="dealer_work_order_view_mode"
                                    size="sm"
                                />
                            </div>
                        </div>

                        <!-- Grid View -->
                        <div
                            v-if="workOrderViewMode === 'grid' && filteredWorkOrders.length > 0"
                            class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4"
                        >
                            <div
                                v-for="order in filteredWorkOrders"
                                :key="order.id"
                                @click="router.visit(route('work-orders.show', order.id))"
                                class="group relative bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 hover:border-action-primary hover:shadow-xl hover:-translate-y-1 cursor-pointer transition-all duration-300 p-5 flex flex-col justify-between"
                            >
                                <div>
                                    <div class="flex items-center justify-between mb-3">
                                        <span
                                            class="font-mono font-bold text-sm text-action-primary group-hover:underline"
                                        >
                                            {{ order.code }}
                                        </span>
                                        <span
                                            :class="getStatusClass(order.status)"
                                            class="px-2.5 py-0.5 text-xs font-bold rounded-full"
                                        >
                                            {{
                                                $t(`work_orders.status.${order.status}`) ||
                                                order.status
                                            }}
                                        </span>
                                    </div>

                                    <div class="flex items-center gap-2 mb-2">
                                        <SaudiPlateDisplay
                                            :plate-number="order.vehicle?.plate_number"
                                            size="sm"
                                        />
                                        <span
                                            class="text-xs font-medium text-gray-500 dark:text-gray-400 truncate"
                                        >
                                            {{ getVehicleName(order.vehicle) }}
                                        </span>
                                    </div>

                                    <p
                                        v-if="order.customer?.name"
                                        class="text-xs text-gray-600 dark:text-gray-300 mb-3 truncate"
                                    >
                                        <span class="text-gray-400">
                                            {{ $t('reports.dealers_report.col_customer') }}:
                                        </span>
                                        {{ order.customer.name }}
                                    </p>

                                    <!-- Billing Badge -->
                                    <div class="mb-3">
                                        <span
                                            class="inline-flex px-2 py-0.5 rounded-full text-[10px] font-bold"
                                            :class="
                                                order.is_paid_by_dealer
                                                    ? 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/40 dark:text-indigo-300'
                                                    : 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300'
                                            "
                                        >
                                            {{
                                                order.is_paid_by_dealer
                                                    ? $t('dealers.show.billed_to_dealer')
                                                    : $t('dealers.show.billed_to_customer')
                                            }}
                                        </span>
                                    </div>
                                </div>

                                <!-- Financial Metrics -->
                                <div class="pt-3 border-t border-gray-100 dark:border-gray-700/60">
                                    <div class="grid grid-cols-3 gap-1 text-center">
                                        <div>
                                            <span
                                                class="text-[9px] font-bold text-gray-400 uppercase block"
                                            >
                                                {{ $t('work_orders.invoice_total') }}
                                            </span>
                                            <span
                                                class="text-xs font-bold text-gray-900 dark:text-white font-mono"
                                            >
                                                {{
                                                    formatPrice(
                                                        order.total || order.total_incl_tax || 0
                                                    )
                                                }}
                                            </span>
                                        </div>
                                        <div>
                                            <span
                                                class="text-[9px] font-bold text-gray-400 uppercase block"
                                            >
                                                {{ $t('work_orders.columns.paid') }}
                                            </span>
                                            <span
                                                class="text-xs font-bold text-emerald-600 dark:text-emerald-400 font-mono"
                                            >
                                                {{ formatPrice(order.total_paid || 0) }}
                                            </span>
                                        </div>
                                        <div>
                                            <span
                                                class="text-[9px] font-bold text-gray-400 uppercase block"
                                            >
                                                {{ $t('work_orders.columns.balance') }}
                                            </span>
                                            <span
                                                class="text-xs font-black font-mono"
                                                :class="
                                                    (order.balance || 0) > 0
                                                        ? 'text-rose-600 dark:text-rose-400'
                                                        : 'text-gray-400'
                                                "
                                            >
                                                {{ formatPrice(order.balance || 0) }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- List View -->
                        <div
                            v-else-if="
                                (workOrderViewMode === 'list' || workOrderViewMode === 'table') &&
                                filteredWorkOrders.length > 0
                            "
                            class="bg-gray-50 dark:bg-gray-900/50 rounded-xl border border-gray-200 dark:border-gray-700 overflow-x-auto"
                        >
                            <DataTable structured class="min-w-full">
                                <thead>
                                    <tr class="bg-gray-100 dark:bg-gray-800">
                                        <th
                                            class="px-4 py-3 text-center text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase whitespace-nowrap align-middle"
                                        >
                                            {{ $t('reports.dealers_report.col_code') }}
                                        </th>
                                        <th
                                            class="px-4 py-3 text-center text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase whitespace-nowrap align-middle"
                                        >
                                            {{ $t('reports.dealers_report.col_vehicle') }}
                                        </th>
                                        <th
                                            class="px-4 py-3 text-center text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase whitespace-nowrap align-middle"
                                        >
                                            {{ $t('reports.dealers_report.col_customer') }}
                                        </th>
                                        <th
                                            class="px-4 py-3 text-center text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase whitespace-nowrap align-middle"
                                        >
                                            {{ $t('dealers.columns.status') }}
                                        </th>
                                        <th
                                            class="px-4 py-3 text-center text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase whitespace-nowrap align-middle"
                                        >
                                            {{ $t('reports.dealers_report.col_billing') }}
                                        </th>
                                        <th
                                            class="px-4 py-3 text-center text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase whitespace-nowrap align-middle"
                                        >
                                            {{ $t('common.date') }}
                                        </th>
                                        <th
                                            class="px-4 py-3 text-center text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase whitespace-nowrap align-middle"
                                        >
                                            {{ $t('reports.dealers_report.col_due') }}
                                        </th>
                                        <th
                                            class="px-4 py-3 text-center text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase whitespace-nowrap align-middle"
                                        >
                                            {{ $t('reports.dealers_report.col_paid') }}
                                        </th>
                                        <th
                                            class="px-4 py-3 text-center text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase whitespace-nowrap align-middle"
                                        >
                                            {{ $t('reports.dealers_report.col_remaining') }}
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                    <tr
                                        v-for="item in filteredWorkOrders"
                                        :key="item.id"
                                        @click="router.visit(route('work-orders.show', item.id))"
                                        class="hover:bg-gray-100 dark:hover:bg-gray-800 cursor-pointer"
                                    >
                                        <td
                                            class="px-4 py-3 text-center font-mono font-bold text-action-primary whitespace-nowrap align-middle"
                                        >
                                            {{ item.code }}
                                        </td>
                                        <td
                                            class="px-4 py-3 text-center text-gray-700 dark:text-gray-300 whitespace-nowrap align-middle"
                                        >
                                            <div class="flex flex-col items-center gap-1">
                                                <SaudiPlateDisplay
                                                    :plate-number="item.vehicle?.plate_number"
                                                    size="sm"
                                                />
                                                <span class="text-xs text-gray-500">
                                                    {{ getVehicleName(item.vehicle) }}
                                                </span>
                                            </div>
                                        </td>
                                        <td
                                            class="px-4 py-3 text-center text-xs text-gray-800 dark:text-gray-200 whitespace-nowrap align-middle"
                                        >
                                            {{ item.customer?.name || '-' }}
                                        </td>
                                        <td
                                            class="px-4 py-3 text-center whitespace-nowrap align-middle"
                                        >
                                            <span
                                                :class="getStatusClass(item.status)"
                                                class="px-2.5 py-0.5 text-xs font-bold rounded-full"
                                            >
                                                {{
                                                    $t(`work_orders.status.${item.status}`) ||
                                                    item.status
                                                }}
                                            </span>
                                        </td>
                                        <td
                                            class="px-4 py-3 text-center whitespace-nowrap align-middle"
                                        >
                                            <span
                                                class="inline-flex px-2 py-0.5 rounded-full text-[11px] font-bold"
                                                :class="
                                                    item.is_paid_by_dealer
                                                        ? 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/40 dark:text-indigo-300'
                                                        : 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300'
                                                "
                                            >
                                                {{
                                                    item.is_paid_by_dealer
                                                        ? $t('dealers.show.billed_to_dealer')
                                                        : $t('dealers.show.billed_to_customer')
                                                }}
                                            </span>
                                        </td>
                                        <td
                                            class="px-4 py-3 text-center text-xs text-gray-500 font-mono whitespace-nowrap align-middle"
                                        >
                                            {{ formatDate(item.created_at || item.entry_date) }}
                                        </td>
                                        <td
                                            class="px-4 py-3 text-center font-mono font-bold text-gray-900 dark:text-white whitespace-nowrap align-middle"
                                        >
                                            {{
                                                formatPrice(item.total || item.total_incl_tax || 0)
                                            }}
                                        </td>
                                        <td
                                            class="px-4 py-3 text-center font-mono font-bold text-emerald-600 dark:text-emerald-400 whitespace-nowrap align-middle"
                                        >
                                            {{ formatPrice(item.total_paid || 0) }}
                                        </td>
                                        <td
                                            class="px-4 py-3 text-center font-mono font-black whitespace-nowrap align-middle"
                                            :class="
                                                (item.balance || 0) > 0
                                                    ? 'text-rose-600 dark:text-rose-400'
                                                    : 'text-gray-400'
                                            "
                                        >
                                            {{ formatPrice(item.balance || 0) }}
                                        </td>
                                    </tr>
                                </tbody>
                            </DataTable>
                        </div>

                        <!-- Empty State -->
                        <AppEmptyState
                            v-else-if="filteredWorkOrders.length === 0"
                            :title="
                                workOrderSearch
                                    ? $t('common.no_results')
                                    : $t('dealers.show.no_work_orders')
                            "
                            :description="workOrderSearch ? $t('common.try_adjusting_search') : ''"
                        >
                            <template #icon>
                                <svg
                                    class="w-7 h-7 sm:w-8 sm:h-8"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="1.75"
                                        d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"
                                    />
                                </svg>
                            </template>
                        </AppEmptyState>
                    </div>

                    <!-- ========== INVOICES TAB ========== -->
                    <div v-else-if="activeTab === 'invoices'" class="space-y-4">
                        <!-- Search Box -->
                        <div class="flex items-center gap-3">
                            <div class="relative group">
                                <div
                                    class="absolute inset-y-0 start-0 ps-3 flex items-center pointer-events-none"
                                >
                                    <svg
                                        class="w-4 h-4 text-gray-400"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
                                        />
                                    </svg>
                                </div>
                                <input
                                    type="text"
                                    v-model="invoiceSearch"
                                    :placeholder="$t('dealers.show.search_invoices')"
                                    class="w-full sm:w-64 ps-10 pe-4 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-xl bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-focus-ring"
                                />
                            </div>
                        </div>

                        <!-- Invoices Table -->
                        <div
                            v-if="filteredInvoices.length > 0"
                            class="bg-gray-50 dark:bg-gray-900/50 rounded-xl border border-gray-200 dark:border-gray-700 overflow-x-auto"
                        >
                            <DataTable structured class="min-w-full">
                                <thead>
                                    <tr class="bg-gray-100 dark:bg-gray-800">
                                        <th
                                            class="px-4 py-3 text-center text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase whitespace-nowrap align-middle"
                                        >
                                            {{ $t('invoices.invoice_number') }}
                                        </th>
                                        <th
                                            class="px-4 py-3 text-center text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase whitespace-nowrap align-middle"
                                        >
                                            {{ $t('invoices.columns.issue_date') }}
                                        </th>
                                        <th
                                            class="px-4 py-3 text-center text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase whitespace-nowrap align-middle"
                                        >
                                            {{ $t('work_orders.work_order') }}
                                        </th>
                                        <th
                                            class="px-4 py-3 text-center text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase whitespace-nowrap align-middle"
                                        >
                                            {{ $t('reports.dealers_report.col_customer') }}
                                        </th>
                                        <th
                                            class="px-4 py-3 text-center text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase whitespace-nowrap align-middle"
                                        >
                                            {{ $t('reports.dealers_report.col_billing') }}
                                        </th>
                                        <th
                                            class="px-4 py-3 text-center text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase whitespace-nowrap align-middle"
                                        >
                                            {{ $t('invoices.columns.status') }}
                                        </th>
                                        <th
                                            class="px-4 py-3 text-center text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase whitespace-nowrap align-middle"
                                        >
                                            {{ $t('invoices.columns.total') }}
                                        </th>
                                        <th
                                            class="px-4 py-3 text-center text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase whitespace-nowrap align-middle"
                                        >
                                            {{ $t('invoices.columns.paid') }}
                                        </th>
                                        <th
                                            class="px-4 py-3 text-center text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase whitespace-nowrap align-middle"
                                        >
                                            {{ $t('invoices.columns.balance') }}
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                    <tr
                                        v-for="item in filteredInvoices"
                                        :key="item.id"
                                        @click="router.visit(route('app.invoices.show', item.id))"
                                        class="hover:bg-gray-100 dark:hover:bg-gray-800 cursor-pointer"
                                    >
                                        <td
                                            class="px-4 py-3 text-center font-mono font-bold text-amber-600 dark:text-amber-400 whitespace-nowrap align-middle hover:underline"
                                        >
                                            #{{ item.invoice_number }}
                                        </td>
                                        <td
                                            class="px-4 py-3 text-center text-xs text-gray-500 dark:text-gray-400 font-mono whitespace-nowrap align-middle"
                                        >
                                            {{ formatDate(item.issue_date) }}
                                        </td>
                                        <td
                                            class="px-4 py-3 text-center text-gray-700 dark:text-gray-300 whitespace-nowrap align-middle"
                                        >
                                            <div
                                                v-if="item.work_order"
                                                class="flex flex-col items-center gap-1"
                                            >
                                                <SaudiPlateDisplay
                                                    :plate-number="
                                                        item.work_order.vehicle?.plate_number
                                                    "
                                                    size="sm"
                                                />
                                                <span class="text-xs text-gray-500 font-mono">
                                                    {{ item.work_order.code }}
                                                </span>
                                            </div>
                                            <span v-else class="text-gray-400">—</span>
                                        </td>
                                        <td
                                            class="px-4 py-3 text-center text-xs text-gray-700 dark:text-gray-300 whitespace-nowrap align-middle"
                                        >
                                            {{
                                                item.customer?.name ||
                                                item.customer_name_snapshot ||
                                                '-'
                                            }}
                                        </td>
                                        <td
                                            class="px-4 py-3 text-center whitespace-nowrap align-middle"
                                        >
                                            <span
                                                class="inline-flex px-2 py-0.5 rounded-full text-[11px] font-bold"
                                                :class="
                                                    item.is_paid_by_dealer
                                                        ? 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/40 dark:text-indigo-300'
                                                        : 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300'
                                                "
                                            >
                                                {{
                                                    item.is_paid_by_dealer
                                                        ? $t('dealers.show.billed_to_dealer')
                                                        : $t('dealers.show.billed_to_customer')
                                                }}
                                            </span>
                                        </td>
                                        <td
                                            class="px-4 py-3 text-center whitespace-nowrap align-middle"
                                        >
                                            <span
                                                :class="
                                                    getInvoicePaymentStatusClass(
                                                        item.payment_status
                                                    )
                                                "
                                                class="px-2 py-0.5 text-xs font-semibold rounded-full"
                                            >
                                                {{
                                                    $t(
                                                        `invoices.payment_status.${item.payment_status}`
                                                    ) || item.payment_status
                                                }}
                                            </span>
                                        </td>
                                        <td
                                            class="px-4 py-3 text-center font-mono font-bold text-gray-900 dark:text-white whitespace-nowrap align-middle"
                                        >
                                            {{ formatPrice(item.total_incl_tax || 0) }}
                                        </td>
                                        <td
                                            class="px-4 py-3 text-center font-mono font-bold text-emerald-600 dark:text-emerald-400 whitespace-nowrap align-middle"
                                        >
                                            {{ formatPrice(item.total_paid || 0) }}
                                        </td>
                                        <td
                                            class="px-4 py-3 text-center font-mono font-black whitespace-nowrap align-middle"
                                            :class="
                                                (item.balance || 0) > 0
                                                    ? 'text-rose-600 dark:text-rose-400'
                                                    : 'text-gray-400'
                                            "
                                        >
                                            {{ formatPrice(item.balance || 0) }}
                                        </td>
                                    </tr>
                                </tbody>
                            </DataTable>
                        </div>

                        <!-- Empty State -->
                        <AppEmptyState
                            v-else
                            :title="
                                invoiceSearch
                                    ? $t('common.no_results')
                                    : $t('dealers.show.no_invoices')
                            "
                            :description="invoiceSearch ? $t('common.try_adjusting_search') : ''"
                        >
                            <template #icon>
                                <svg
                                    class="w-7 h-7 sm:w-8 sm:h-8"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="1.75"
                                        d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z"
                                    />
                                </svg>
                            </template>
                        </AppEmptyState>
                    </div>

                    <!-- ========== VEHICLES TAB ========== -->
                    <div v-else-if="activeTab === 'vehicles'" class="space-y-6">
                        <!-- Toolbar -->
                        <div
                            class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3"
                        >
                            <div class="flex items-center gap-3">
                                <!-- Search -->
                                <div class="relative group">
                                    <div
                                        class="absolute inset-y-0 start-0 ps-3 flex items-center pointer-events-none"
                                    >
                                        <svg
                                            class="w-4 h-4 text-gray-400"
                                            fill="none"
                                            stroke="currentColor"
                                            viewBox="0 0 24 24"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
                                            />
                                        </svg>
                                    </div>
                                    <input
                                        type="text"
                                        v-model="vehicleSearch"
                                        :placeholder="$t('dealers.show.search_vehicles')"
                                        class="w-full sm:w-64 ps-10 pe-4 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-xl bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-focus-ring"
                                    />
                                </div>
                                <!-- View Toggle -->
                                <AppViewModeToggle
                                    v-model="vehicleViewMode"
                                    storage-key="dealer_vehicle_view_mode"
                                    size="sm"
                                />
                            </div>
                            <button
                                v-if="can('crm.vehicles.create') || isAnyAdmin()"
                                type="button"
                                @click="openAddVehicleModal"
                                class="inline-flex items-center justify-center gap-2 px-4 py-2 text-sm font-bold text-white bg-action-primary hover:bg-action-primary-hover rounded-xl shadow-sm transition-all cursor-pointer"
                            >
                                <svg
                                    class="w-4 h-4"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M12 4v16m8-8H4"
                                    />
                                </svg>
                                {{ $t('dealers.show.add_vehicle') }}
                            </button>
                        </div>

                        <!-- Grid View -->
                        <div
                            v-if="vehicleViewMode === 'grid' && filteredVehicles.length > 0"
                            class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4"
                        >
                            <div
                                v-for="vehicle in filteredVehicles"
                                :key="vehicle.id"
                                @click="router.visit(route('vehicles.show', vehicle.id))"
                                class="group relative bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 hover:border-action-primary hover:shadow-xl hover:-translate-y-1 cursor-pointer transition-all duration-300 overflow-hidden"
                            >
                                <!-- Background Logo Watermark -->
                                <div
                                    v-if="vehicle.make?.logo_path"
                                    class="absolute inset-0 flex items-center justify-center opacity-[0.06] dark:opacity-[0.25] pointer-events-none select-none z-0 overflow-hidden"
                                >
                                    <img
                                        :src="`/storage/${vehicle.make.logo_path}`"
                                        class="w-3/4 h-3/4 object-contain grayscale dark:brightness-150 transform -rotate-12 group-hover:scale-110 group-hover:-rotate-6 transition-transform duration-700"
                                        alt=""
                                    />
                                </div>

                                <!-- Card Content -->
                                <div class="relative z-10 flex flex-col h-full">
                                    <!-- Header: Plate & Color -->
                                    <div
                                        class="px-4 py-3 border-b border-gray-100 dark:border-gray-700/50 flex items-center justify-between bg-gradient-to-r from-gray-50/50 to-transparent dark:from-gray-900/50"
                                    >
                                        <SaudiPlateDisplay
                                            :plate-number="vehicle.plate_number"
                                            size="sm"
                                        />
                                        <span
                                            v-if="vehicle.color"
                                            class="w-3 h-3 rounded-full border border-white dark:border-gray-700 shadow-sm"
                                            :style="{ backgroundColor: getColorHex(vehicle.color) }"
                                            :title="vehicle.color"
                                        ></span>
                                    </div>

                                    <!-- Body: Vehicle Info -->
                                    <div class="p-4 flex-1">
                                        <div class="mb-3">
                                            <div
                                                class="flex items-baseline justify-between mb-1 gap-1"
                                            >
                                                <h3
                                                    class="text-sm font-bold text-gray-900 dark:text-white group-hover:text-action-primary transition-colors truncate"
                                                >
                                                    {{ getMakeName(vehicle) || $t('common.na') }}
                                                </h3>
                                                <span
                                                    v-if="vehicle.year"
                                                    class="px-1.5 py-0.5 text-[10px] font-bold bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 rounded-md"
                                                >
                                                    {{ vehicle.year }}
                                                </span>
                                            </div>
                                            <p
                                                class="text-xs font-medium text-gray-500 dark:text-gray-400 truncate"
                                            >
                                                {{ getModelName(vehicle) }}
                                            </p>
                                        </div>

                                        <p
                                            v-if="vehicle.customer?.name"
                                            class="text-xs text-gray-600 dark:text-gray-300 truncate mb-2"
                                        >
                                            <span class="text-gray-400">
                                                {{ $t('reports.dealers_report.col_customer') }}:
                                            </span>
                                            {{ vehicle.customer.name }}
                                        </p>

                                        <!-- Odometer & VIN Footer -->
                                        <div
                                            class="text-[10px] text-gray-500 dark:text-gray-400 pt-2 border-t border-gray-100 dark:border-gray-700/50 flex flex-col gap-1.5 mt-auto"
                                        >
                                            <div
                                                v-if="vehicle.odometer"
                                                class="flex items-center gap-1.5"
                                            >
                                                <svg
                                                    class="w-3.5 h-3.5 text-teal-500"
                                                    fill="none"
                                                    stroke="currentColor"
                                                    viewBox="0 0 24 24"
                                                >
                                                    <path
                                                        stroke-linecap="round"
                                                        stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M13 10V3L4 14h7v7l9-11h-7z"
                                                    />
                                                </svg>
                                                <span>
                                                    {{ formatNumber(vehicle.odometer, 0) }}
                                                    {{ $t('common.km') }}
                                                </span>
                                            </div>
                                            <div
                                                v-if="vehicle.vin"
                                                class="flex items-center gap-1.5 font-mono"
                                            >
                                                <span class="text-gray-400">VIN:</span>
                                                <span class="truncate">{{ vehicle.vin }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- List View -->
                        <div
                            v-else-if="
                                (vehicleViewMode === 'list' || vehicleViewMode === 'table') &&
                                filteredVehicles.length > 0
                            "
                            class="bg-gray-50 dark:bg-gray-900/50 rounded-xl border border-gray-200 dark:border-gray-700 overflow-x-auto"
                        >
                            <DataTable structured class="min-w-full">
                                <thead>
                                    <tr class="bg-gray-100 dark:bg-gray-800">
                                        <th
                                            class="px-4 py-3 text-center text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase whitespace-nowrap align-middle"
                                        >
                                            {{ $t('vehicles.form.plate') }}
                                        </th>
                                        <th
                                            class="px-4 py-3 text-center text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase whitespace-nowrap align-middle"
                                        >
                                            {{ $t('vehicles.form.make') }}
                                        </th>
                                        <th
                                            class="px-4 py-3 text-center text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase whitespace-nowrap align-middle"
                                        >
                                            {{ $t('vehicles.form.model') }}
                                        </th>
                                        <th
                                            class="px-4 py-3 text-center text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase whitespace-nowrap align-middle"
                                        >
                                            {{ $t('vehicles.form.year') }}
                                        </th>
                                        <th
                                            class="px-4 py-3 text-center text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase whitespace-nowrap align-middle"
                                        >
                                            {{ $t('reports.dealers_report.col_customer') }}
                                        </th>
                                        <th
                                            class="px-4 py-3 text-center text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase whitespace-nowrap align-middle"
                                        >
                                            {{ $t('vehicles.form.odometer') }}
                                        </th>
                                        <th
                                            class="px-4 py-3 text-center text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase whitespace-nowrap align-middle"
                                        >
                                            {{ $t('vehicles.form.vin') }}
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                    <tr
                                        v-for="item in filteredVehicles"
                                        :key="item.id"
                                        @click="router.visit(route('vehicles.show', item.id))"
                                        class="hover:bg-gray-100 dark:hover:bg-gray-800 cursor-pointer"
                                    >
                                        <td
                                            class="px-4 py-3 text-center font-bold text-gray-800 dark:text-gray-200 whitespace-nowrap align-middle"
                                        >
                                            <div class="inline-flex justify-center">
                                                <SaudiPlateDisplay
                                                    :plate-number="item.plate_number"
                                                    size="sm"
                                                />
                                            </div>
                                        </td>
                                        <td
                                            class="px-4 py-3 text-center text-gray-700 dark:text-gray-300 whitespace-nowrap align-middle"
                                        >
                                            {{ getMakeName(item) || '—' }}
                                        </td>
                                        <td
                                            class="px-4 py-3 text-center text-gray-700 dark:text-gray-300 whitespace-nowrap align-middle"
                                        >
                                            {{ getModelName(item) || '—' }}
                                        </td>
                                        <td
                                            class="px-4 py-3 text-center text-gray-500 dark:text-gray-400 whitespace-nowrap align-middle"
                                        >
                                            {{ item.year || '—' }}
                                        </td>
                                        <td
                                            class="px-4 py-3 text-center text-xs text-gray-700 dark:text-gray-300 whitespace-nowrap align-middle"
                                        >
                                            {{ item.customer?.name || '-' }}
                                        </td>
                                        <td
                                            class="px-4 py-3 text-center text-xs font-mono text-gray-600 dark:text-gray-400 whitespace-nowrap align-middle"
                                        >
                                            {{
                                                item.odometer
                                                    ? `${formatNumber(item.odometer, 0)} ${$t('common.km')}`
                                                    : '—'
                                            }}
                                        </td>
                                        <td
                                            class="px-4 py-3 text-center text-xs font-mono text-gray-500 whitespace-nowrap align-middle"
                                        >
                                            {{ item.vin || '—' }}
                                        </td>
                                    </tr>
                                </tbody>
                            </DataTable>
                        </div>

                        <!-- Empty State -->
                        <AppEmptyState
                            v-else-if="filteredVehicles.length === 0"
                            :title="
                                vehicleSearch
                                    ? $t('common.no_results')
                                    : $t('dealers.show.no_vehicles')
                            "
                            :description="vehicleSearch ? $t('common.try_adjusting_search') : ''"
                            :action-text="
                                !vehicleSearch && (can('crm.vehicles.create') || isAnyAdmin())
                                    ? $t('dealers.show.add_vehicle')
                                    : ''
                            "
                            @action="openAddVehicleModal"
                        >
                            <template #icon>
                                <svg
                                    class="w-7 h-7 sm:w-8 sm:h-8"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="1.75"
                                        d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z"
                                    />
                                </svg>
                            </template>
                        </AppEmptyState>
                    </div>

                    <!-- ========== PAYMENTS TAB ========== -->
                    <div v-else-if="activeTab === 'payments'" class="space-y-4">
                        <!-- Search Box -->
                        <div class="flex items-center gap-3">
                            <div class="relative group">
                                <div
                                    class="absolute inset-y-0 start-0 ps-3 flex items-center pointer-events-none"
                                >
                                    <svg
                                        class="w-4 h-4 text-gray-400"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
                                        />
                                    </svg>
                                </div>
                                <input
                                    type="text"
                                    v-model="paymentSearch"
                                    :placeholder="$t('dealers.show.search_payments')"
                                    class="w-full sm:w-64 ps-10 pe-4 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-xl bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-focus-ring"
                                />
                            </div>
                        </div>

                        <!-- Payments Table -->
                        <div
                            v-if="filteredPayments.length > 0"
                            class="bg-gray-50 dark:bg-gray-900/50 rounded-xl border border-gray-200 dark:border-gray-700 overflow-x-auto"
                        >
                            <DataTable structured class="min-w-full">
                                <thead>
                                    <tr class="bg-gray-100 dark:bg-gray-800">
                                        <th
                                            class="px-4 py-3 text-center text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase whitespace-nowrap align-middle"
                                        >
                                            {{ $t('dealers.show.reference') }}
                                        </th>
                                        <th
                                            class="px-4 py-3 text-center text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase whitespace-nowrap align-middle"
                                        >
                                            {{ $t('common.date') }}
                                        </th>
                                        <th
                                            class="px-4 py-3 text-center text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase whitespace-nowrap align-middle"
                                        >
                                            {{ $t('dealers.columns.type') }}
                                        </th>
                                        <th
                                            class="px-4 py-3 text-center text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase whitespace-nowrap align-middle"
                                        >
                                            {{ $t('invoices.columns.source') }}
                                        </th>
                                        <th
                                            class="px-4 py-3 text-center text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase whitespace-nowrap align-middle"
                                        >
                                            {{ $t('dealers.show.payment_method') }}
                                        </th>
                                        <th
                                            class="px-4 py-3 text-center text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase whitespace-nowrap align-middle"
                                        >
                                            {{ $t('common.amount') }}
                                        </th>
                                        <th
                                            class="px-4 py-3 text-center text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase whitespace-nowrap align-middle"
                                        >
                                            {{ $t('common.created_by') }}
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                    <tr
                                        v-for="item in filteredPayments"
                                        :key="item.id"
                                        class="hover:bg-gray-100 dark:hover:bg-gray-800"
                                    >
                                        <td
                                            class="px-4 py-3 text-center font-mono font-bold text-gray-900 dark:text-white whitespace-nowrap align-middle"
                                        >
                                            {{ item.reference || `REC-${item.id}` }}
                                        </td>
                                        <td
                                            class="px-4 py-3 text-center text-xs text-gray-500 font-mono whitespace-nowrap align-middle"
                                        >
                                            {{ formatDate(item.payment_date || item.created_at) }}
                                        </td>
                                        <td
                                            class="px-4 py-3 text-center whitespace-nowrap align-middle"
                                        >
                                            <span
                                                class="inline-block px-2 py-0.5 rounded-full text-xs font-semibold"
                                                :class="
                                                    item.type === 'refund' ||
                                                    item.type?.value === 'refund'
                                                        ? 'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-400'
                                                        : 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400'
                                                "
                                            >
                                                {{
                                                    item.type === 'refund' ||
                                                    item.type?.value === 'refund'
                                                        ? $t('dealers.show.refund_voucher')
                                                        : $t('dealers.show.receipt_voucher')
                                                }}
                                            </span>
                                        </td>
                                        <td
                                            class="px-4 py-3 text-center whitespace-nowrap align-middle"
                                        >
                                            <div
                                                v-if="item.work_order"
                                                class="flex flex-col items-center gap-1"
                                            >
                                                <SaudiPlateDisplay
                                                    :plate-number="
                                                        item.work_order.vehicle?.plate_number
                                                    "
                                                    size="sm"
                                                />
                                                <span class="text-xs text-gray-500 font-mono">
                                                    {{ item.work_order.code }}
                                                </span>
                                            </div>
                                            <span
                                                v-else-if="item.invoice"
                                                class="font-mono text-xs text-action-primary"
                                            >
                                                #{{ item.invoice.invoice_number }}
                                            </span>
                                            <span v-else class="text-gray-400">—</span>
                                        </td>
                                        <td
                                            class="px-4 py-3 text-center whitespace-nowrap align-middle"
                                        >
                                            <span
                                                class="inline-block px-2.5 py-0.5 rounded-md text-xs font-medium"
                                                :class="getPaymentMethodClass(item.payment_method)"
                                            >
                                                {{
                                                    $t(`payments.methods.${item.payment_method}`) ||
                                                    item.payment_method
                                                }}
                                            </span>
                                        </td>
                                        <td
                                            class="px-4 py-3 text-center font-mono font-bold whitespace-nowrap align-middle"
                                            :class="
                                                item.type === 'refund' ||
                                                item.type?.value === 'refund'
                                                    ? 'text-rose-600 dark:text-rose-400'
                                                    : 'text-emerald-600 dark:text-emerald-400'
                                            "
                                        >
                                            <span dir="ltr" class="inline-block">
                                                {{
                                                    item.type === 'refund' ||
                                                    item.type?.value === 'refund'
                                                        ? '-'
                                                        : '+'
                                                }}{{ formatPrice(item.amount || 0) }}
                                            </span>
                                        </td>
                                        <td
                                            class="px-4 py-3 text-center text-xs text-gray-600 dark:text-gray-400 whitespace-nowrap align-middle"
                                        >
                                            {{ item.received_by?.name || '-' }}
                                        </td>
                                    </tr>
                                </tbody>
                            </DataTable>
                        </div>

                        <!-- Empty State -->
                        <AppEmptyState
                            v-else
                            :title="
                                paymentSearch
                                    ? $t('common.no_results')
                                    : $t('dealers.show.no_payments')
                            "
                            :description="paymentSearch ? $t('common.try_adjusting_search') : ''"
                        >
                            <template #icon>
                                <svg
                                    class="w-7 h-7 sm:w-8 sm:h-8"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="1.75"
                                        d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"
                                    />
                                </svg>
                            </template>
                        </AppEmptyState>
                    </div>

                    <!-- ========== PAYMENT TERMS TAB ========== -->
                    <div v-else-if="activeTab === 'payment_terms'" class="space-y-4">
                        <!-- Toolbar -->
                        <div
                            class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3"
                        >
                            <div class="relative group">
                                <div
                                    class="absolute inset-y-0 start-0 ps-3 flex items-center pointer-events-none"
                                >
                                    <svg
                                        class="w-4 h-4 text-gray-400"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
                                        />
                                    </svg>
                                </div>
                                <input
                                    type="text"
                                    v-model="paymentTermSearch"
                                    :placeholder="$t('dealers.show.search_payment_terms')"
                                    class="w-full sm:w-64 ps-10 pe-4 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-xl bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-focus-ring"
                                />
                            </div>
                            <button
                                v-if="can('crm.customers.update') || isAnyAdmin()"
                                type="button"
                                @click="openCreatePaymentTermModal"
                                class="inline-flex items-center justify-center gap-2 px-4 py-2 text-sm font-bold text-white bg-action-primary hover:bg-action-primary-hover rounded-xl shadow-sm transition-all cursor-pointer"
                            >
                                <svg
                                    class="w-4 h-4"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M12 4v16m8-8H4"
                                    />
                                </svg>
                                {{ $t('dealers.show.add_payment_term') }}
                            </button>
                        </div>

                        <!-- Payment Terms Table -->
                        <div
                            v-if="filteredPaymentTerms.length > 0"
                            class="bg-gray-50 dark:bg-gray-900/50 rounded-xl border border-gray-200 dark:border-gray-700 overflow-x-auto"
                        >
                            <DataTable structured class="min-w-full">
                                <thead>
                                    <tr class="bg-gray-100 dark:bg-gray-800">
                                        <th
                                            class="w-16 px-4 py-3 text-center text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase whitespace-nowrap align-middle"
                                        >
                                            #
                                        </th>
                                        <th
                                            class="w-32 px-4 py-3 text-center text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase whitespace-nowrap align-middle"
                                        >
                                            {{ $t('dealers.show.payment_term_sort_order') }}
                                        </th>
                                        <th
                                            class="px-4 py-3 text-start text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase align-middle"
                                        >
                                            {{ $t('dealers.show.payment_term_text') }}
                                        </th>
                                        <th
                                            class="w-32 px-4 py-3 text-center text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase whitespace-nowrap align-middle"
                                        >
                                            {{ $t('common.actions') }}
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                    <tr
                                        v-for="(term, idx) in filteredPaymentTerms"
                                        :key="term.id"
                                        class="hover:bg-gray-100 dark:hover:bg-gray-800"
                                    >
                                        <td
                                            class="px-4 py-3 text-center text-xs font-mono text-gray-500 dark:text-gray-400 whitespace-nowrap align-middle"
                                        >
                                            {{ idx + 1 }}
                                        </td>
                                        <td
                                            class="px-4 py-3 text-center text-xs font-mono font-bold text-gray-700 dark:text-gray-300 whitespace-nowrap align-middle"
                                        >
                                            {{ term.sort_order }}
                                        </td>
                                        <td
                                            class="px-4 py-3 text-start text-sm text-gray-800 dark:text-gray-200 align-middle"
                                        >
                                            {{ term.term_text }}
                                        </td>
                                        <td
                                            class="px-4 py-3 text-center whitespace-nowrap align-middle"
                                        >
                                            <div
                                                class="inline-flex items-center justify-center gap-1.5"
                                            >
                                                <button
                                                    v-if="
                                                        can('crm.customers.update') || isAnyAdmin()
                                                    "
                                                    type="button"
                                                    @click="openEditPaymentTermModal(term)"
                                                    class="p-1.5 rounded-lg text-gray-500 hover:text-action-primary hover:bg-gray-200/60 dark:hover:bg-gray-700/60 transition-colors cursor-pointer"
                                                    :title="$t('common.edit')"
                                                >
                                                    <svg
                                                        class="w-4 h-4"
                                                        fill="none"
                                                        stroke="currentColor"
                                                        viewBox="0 0 24 24"
                                                    >
                                                        <path
                                                            stroke-linecap="round"
                                                            stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"
                                                        />
                                                    </svg>
                                                </button>
                                                <button
                                                    v-if="
                                                        can('crm.customers.update') || isAnyAdmin()
                                                    "
                                                    type="button"
                                                    @click="confirmDeletePaymentTerm(term)"
                                                    class="p-1.5 rounded-lg text-gray-500 hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-900/20 transition-colors cursor-pointer"
                                                    :title="$t('common.delete')"
                                                >
                                                    <svg
                                                        class="w-4 h-4"
                                                        fill="none"
                                                        stroke="currentColor"
                                                        viewBox="0 0 24 24"
                                                    >
                                                        <path
                                                            stroke-linecap="round"
                                                            stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                                                        />
                                                    </svg>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </DataTable>
                        </div>

                        <!-- Empty State -->
                        <AppEmptyState
                            v-else
                            :title="
                                paymentTermSearch
                                    ? $t('common.no_results')
                                    : $t('dealers.show.no_payment_terms')
                            "
                            :description="
                                paymentTermSearch ? $t('common.try_adjusting_search') : ''
                            "
                            :action-text="
                                !paymentTermSearch && (can('crm.customers.update') || isAnyAdmin())
                                    ? $t('dealers.show.add_payment_term')
                                    : ''
                            "
                            @action="openCreatePaymentTermModal"
                        >
                            <template #icon>
                                <svg
                                    class="w-7 h-7 sm:w-8 sm:h-8"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="1.75"
                                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                                    />
                                </svg>
                            </template>
                        </AppEmptyState>
                    </div>
                </div>
            </div>
        </div>

        <!-- Edit Dealer Modal -->
        <DealerFormModal
            :show="showEditModal"
            :dealer="dealer"
            @close="showEditModal = false"
            @saved="handleDealerSaved"
        />

        <!-- Statement of Account Modal -->
        <DealerStatementModal
            :show="showStatementModal"
            :dealer="dealer"
            :financial-summary="financialSummary"
            :ledger="ledger"
            :company-info="companyInfo"
            @close="showStatementModal = false"
        />

        <!-- Delete Confirmation Modal -->
        <ConfirmModal
            :show="showDeleteModal"
            :title="$t('dealers.show.confirm_delete_title')"
            :message="$t('dealers.show.confirm_delete_message')"
            :confirm-text="$t('common.delete')"
            variant="danger"
            @confirm="executeDelete"
            @close="showDeleteModal = false"
        />

        <!-- Toggle Status Confirmation Modal -->
        <ConfirmModal
            :show="showToggleModal"
            :title="
                dealer.is_active
                    ? $t('dealers.show.status_toggle_inactive')
                    : $t('dealers.show.status_toggle_active')
            "
            :message="$t('dealers.show.status_toggle_confirm')"
            :confirm-text="$t('common.confirm')"
            variant="warning"
            @confirm="executeToggle"
            @close="showToggleModal = false"
        />

        <!-- Add / Edit Payment Term Modal -->
        <BaseModal
            :show="showPaymentTermModal"
            :title="
                editingPaymentTerm
                    ? $t('dealers.show.edit_payment_term')
                    : $t('dealers.show.add_payment_term')
            "
            max-width="md"
            @close="closePaymentTermModal"
        >
            <form @submit.prevent="submitPaymentTerm" class="space-y-4">
                <FormField
                    :label="$t('dealers.show.payment_term_text')"
                    required
                    :error="paymentTermForm.errors.term_text"
                >
                    <textarea
                        v-model="paymentTermForm.term_text"
                        rows="3"
                        :placeholder="$t('dealers.show.payment_term_text_placeholder')"
                        class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-focus-ring"
                    ></textarea>
                </FormField>

                <FormField
                    :label="$t('dealers.show.payment_term_sort_order')"
                    :error="paymentTermForm.errors.sort_order"
                >
                    <input
                        type="number"
                        min="0"
                        v-model.number="paymentTermForm.sort_order"
                        class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-focus-ring font-mono"
                    />
                </FormField>
            </form>

            <template #footer>
                <div class="flex items-center justify-end gap-3">
                    <button
                        type="button"
                        @click="closePaymentTermModal"
                        class="px-5 py-2.5 text-xs font-bold text-text-secondary hover:bg-surface-secondary border border-border rounded-xl transition-colors cursor-pointer"
                    >
                        {{ $t('common.cancel') }}
                    </button>
                    <button
                        type="button"
                        @click="submitPaymentTerm"
                        :disabled="paymentTermForm.processing"
                        class="inline-flex items-center justify-center gap-2 px-6 py-2.5 text-xs font-bold text-white bg-action-primary hover:bg-action-primary-hover rounded-xl shadow-xs transition-all disabled:opacity-50 cursor-pointer"
                    >
                        {{ $t('common.save') }}
                    </button>
                </div>
            </template>
        </BaseModal>

        <!-- Delete Payment Term Confirmation Modal -->
        <ConfirmModal
            :show="showDeletePaymentTermModal"
            :title="$t('dealers.show.confirm_delete_payment_term_title')"
            :message="$t('dealers.show.confirm_delete_payment_term_message')"
            :confirm-text="$t('common.delete')"
            variant="danger"
            @confirm="executeDeletePaymentTerm"
            @close="showDeletePaymentTermModal = false"
        />

        <!-- Add Vehicle Modal (Linked to Partner) -->
        <BaseModal
            :show="showAddVehicleModal"
            :title="$t('dealers.show.add_vehicle')"
            max-width="2xl"
            @close="closeAddVehicleModal"
        >
            <form @submit.prevent="submitAddVehicle" class="space-y-5">
                <!-- Linked Partner Badge -->
                <div
                    class="flex items-center justify-between px-4 py-2.5 rounded-xl bg-teal-50/70 dark:bg-teal-900/20 border border-teal-200/70 dark:border-teal-800/50"
                >
                    <span class="text-xs font-medium text-teal-700 dark:text-teal-300">
                        {{ $t('dealers.show.linked_partner') }}
                    </span>
                    <span class="text-sm font-bold text-teal-900 dark:text-teal-100">
                        {{ dealer.name }}
                    </span>
                </div>

                <!-- Saudi Plate Input -->
                <div
                    class="bg-gray-50/80 dark:bg-gray-900/40 rounded-xl p-4 border border-gray-200/60 dark:border-gray-700/50"
                >
                    <SaudiPlateInput
                        v-model="vehicleForm.plate_number"
                        :error="vehicleForm.errors.plate_number"
                        required
                    />
                </div>

                <!-- Customer Selection -->
                <div>
                    <div class="flex items-center justify-between mb-1.5">
                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-400">
                            {{ $t('vehicles.form.customer') }}
                            <span class="text-red-500">*</span>
                        </label>
                        <button
                            v-if="can('crm.customers.create') || isAnyAdmin()"
                            type="button"
                            @click="showQuickCustomerModal = true"
                            class="text-xs font-bold text-action-primary hover:underline flex items-center gap-1 cursor-pointer"
                        >
                            <svg
                                class="w-3.5 h-3.5"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M12 4v16m8-8H4"
                                />
                            </svg>
                            {{ $t('customers.add_customer') }}
                        </button>
                    </div>
                    <SearchableSelect
                        v-model="vehicleForm.customer_id"
                        :options="customerOptions"
                        label-key="display_name"
                        value-key="id"
                        :placeholder="$t('vehicles.form.select_customer')"
                        :error="vehicleForm.errors.customer_id"
                    />
                </div>

                <!-- Make & Model -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label
                            class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1.5"
                        >
                            {{ $t('vehicles.form.make') }}
                            <span class="text-red-500">*</span>
                        </label>
                        <SearchableSelect
                            v-model="vehicleForm.vehicle_make_id"
                            :options="makesWithOther"
                            :placeholder="$t('vehicles.form.select_make')"
                            :label-key="locale === 'ar' ? 'name_ar' : 'name_en'"
                            value-key="id"
                            :error="vehicleForm.errors.vehicle_make_id"
                        />
                        <div v-if="vehicleForm.vehicle_make_id === 'other'" class="mt-2">
                            <input
                                type="text"
                                v-model="vehicleForm.make_other"
                                :placeholder="$t('vehicles.form.enter_make')"
                                class="w-full px-3.5 py-2 text-sm rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100"
                            />
                        </div>
                    </div>

                    <div>
                        <label
                            class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1.5"
                        >
                            {{ $t('vehicles.form.model') }}
                            <span
                                v-if="vehicleForm.vehicle_make_id !== 'other'"
                                class="text-red-500"
                            >
                                *
                            </span>
                        </label>
                        <SearchableSelect
                            v-if="vehicleForm.vehicle_make_id !== 'other'"
                            v-model="vehicleForm.vehicle_model_id"
                            :options="availableModelsWithOther"
                            :placeholder="
                                vehicleForm.vehicle_make_id
                                    ? $t('vehicles.form.select_model')
                                    : $t('vehicles.form.select_make_first')
                            "
                            :disabled="!vehicleForm.vehicle_make_id"
                            :label-key="locale === 'ar' ? 'name_ar' : 'name_en'"
                            value-key="id"
                            :error="vehicleForm.errors.vehicle_model_id"
                        />
                        <div
                            v-if="
                                vehicleForm.vehicle_make_id === 'other' ||
                                vehicleForm.vehicle_model_id === 'other'
                            "
                            :class="{ 'mt-2': vehicleForm.vehicle_make_id !== 'other' }"
                        >
                            <input
                                type="text"
                                v-model="vehicleForm.model_other"
                                :placeholder="$t('vehicles.form.enter_model')"
                                class="w-full px-3.5 py-2 text-sm rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100"
                            />
                        </div>
                    </div>
                </div>

                <!-- Year, Color, Odometer -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label
                            class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1.5"
                        >
                            {{ $t('vehicles.form.year') }}
                        </label>
                        <SearchableSelect
                            v-model="vehicleForm.year"
                            :options="yearOptions"
                            :placeholder="$t('vehicles.form.select_year')"
                            label-key="name"
                            value-key="id"
                            :error="vehicleForm.errors.year"
                        />
                    </div>
                    <div>
                        <label
                            class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1.5"
                        >
                            {{ $t('vehicles.form.color') }}
                        </label>
                        <SearchableSelect
                            v-model="vehicleForm.color"
                            :options="colorsWithOther"
                            :placeholder="$t('vehicles.form.select_color')"
                            :label-key="locale === 'ar' ? 'name_ar' : 'name_en'"
                            :value-key="locale === 'ar' ? 'name_ar' : 'name_en'"
                            :error="vehicleForm.errors.color"
                        />
                    </div>
                    <div>
                        <label
                            class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1.5"
                        >
                            {{ $t('vehicles.form.odometer') }}
                        </label>
                        <input
                            type="number"
                            min="0"
                            v-model="vehicleForm.odometer"
                            placeholder="0"
                            dir="ltr"
                            class="w-full px-3.5 py-2 text-sm font-mono rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100"
                        />
                    </div>
                </div>

                <!-- VIN -->
                <FormField :label="$t('vehicles.form.vin')" :error="vehicleForm.errors.vin">
                    <input
                        type="text"
                        v-model="vehicleForm.vin"
                        maxlength="17"
                        dir="ltr"
                        :placeholder="$t('vehicles.form.vin_placeholder')"
                        class="w-full px-3.5 py-2 text-sm font-mono uppercase rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100"
                    />
                </FormField>
            </form>

            <template #footer>
                <div class="flex items-center justify-end gap-3">
                    <button
                        type="button"
                        @click="closeAddVehicleModal"
                        class="px-5 py-2.5 text-xs font-bold text-text-secondary hover:bg-surface-secondary border border-border rounded-xl transition-colors cursor-pointer"
                    >
                        {{ $t('common.cancel') }}
                    </button>
                    <button
                        type="button"
                        @click="submitAddVehicle"
                        :disabled="vehicleForm.processing"
                        class="inline-flex items-center justify-center gap-2 px-6 py-2.5 text-xs font-bold text-white bg-action-primary hover:bg-action-primary-hover rounded-xl shadow-xs transition-all disabled:opacity-50 cursor-pointer"
                    >
                        {{ $t('common.save') }}
                    </button>
                </div>
            </template>
        </BaseModal>

        <!-- Quick Customer Creation Modal -->
        <CustomerFormModal
            :show="showQuickCustomerModal"
            @close="showQuickCustomerModal = false"
            @saved="handleQuickCustomerSaved"
        />
    </AppLayout>
</template>

<script setup>
import { ref, computed, watch, onMounted, h } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import AppLayout from '@/Layouts/AppLayout.vue';
import Breadcrumb from '@/Components/Breadcrumb.vue';
import BackButton from '@/Components/BackButton.vue';
import BaseModal from '@/Components/BaseModal.vue';
import FormField from '@/Components/Common/FormField.vue';
import AppEmptyState from '@/Components/Common/AppEmptyState.vue';
import AppViewModeToggle from '@/Components/Common/AppViewModeToggle.vue';
import AppTabs from '@/Components/Common/AppTabs.vue';
import DataTable from '@/Components/Common/DataTable.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import SaudiPlateDisplay from '@/Components/Vehicles/SaudiPlateDisplay.vue';
import SaudiPlateInput from '@/Components/Vehicles/SaudiPlateInput.vue';
import ConfirmModal from '@/Components/ConfirmModal.vue';
import CustomerFormModal from '@/Components/Customers/CustomerFormModal.vue';
import DealerFormModal from '@/Components/Dealers/DealerFormModal.vue';
import DealerStatementModal from '@/Components/Dealers/DealerStatementModal.vue';
import { usePermission } from '@/Composables/usePermission';
import { useNumberFormat } from '@/Composables/useNumberFormat';

const props = defineProps({
    dealer: {
        type: Object,
        required: true,
    },
    types: {
        type: Array,
        default: () => ['insurance', 'fleet', 'rental', 'corporate'],
    },
    outstandingBalance: {
        type: [Number, String],
        default: 0,
    },
    financialSummary: {
        type: Object,
        default: () => ({
            total_invoiced: 0,
            total_paid: 0,
            total_due: 0,
            open_work_orders_count: 0,
        }),
    },
    ledger: {
        type: Array,
        default: () => [],
    },
    counts: {
        type: Object,
        default: () => ({
            vehicles: 0,
            workOrders: 0,
            invoices: 0,
            payments: 0,
            paymentTerms: 0,
        }),
    },
    canDelete: {
        type: Boolean,
        default: true,
    },
    vehicles: {
        type: Array,
        default: () => [],
    },
    workOrders: {
        type: Array,
        default: () => [],
    },
    invoices: {
        type: Array,
        default: () => [],
    },
    payments: {
        type: Array,
        default: () => [],
    },
    paymentTerms: {
        type: Array,
        default: () => [],
    },
    customers: {
        type: Array,
        default: () => [],
    },
    makes: {
        type: Array,
        default: () => [],
    },
    colors: {
        type: Array,
        default: () => [],
    },
    modelsByMake: {
        type: Object,
        default: () => ({}),
    },
    companyInfo: {
        type: Object,
        default: null,
    },
});

const { t, locale } = useI18n();
const isRtl = computed(() => locale.value === 'ar');
const { can, isAnyAdmin } = usePermission();
const { formatNumber } = useNumberFormat();

const breadcrumbItems = computed(() => [
    { label: t('dealers.title'), href: route('app.dealers.index') },
    { label: props.dealer?.name || '' },
]);

const headerAddress = computed(() => {
    const d = props.dealer || {};
    const parts = [
        d.address_line,
        d.building_number,
        d.district,
        d.city,
        d.region,
        d.postal_code,
        d.country,
    ].filter(Boolean);

    return parts.join(isRtl.value ? '، ' : ', ');
});

const creditUsagePercent = computed(() => {
    const limit = Number(props.dealer?.credit_limit || 0);
    const due = Number(props.financialSummary?.total_due ?? props.outstandingBalance ?? 0);
    if (limit <= 0) return 0;
    return Math.round((due / limit) * 100);
});

// Modals state
const showEditModal = ref(false);
const showStatementModal = ref(false);
const showDeleteModal = ref(false);
const showToggleModal = ref(false);
const showPaymentTermModal = ref(false);
const showDeletePaymentTermModal = ref(false);
const editingPaymentTerm = ref(null);
const deletingPaymentTerm = ref(null);
const showAddVehicleModal = ref(false);
const showQuickCustomerModal = ref(false);
const localCustomers = ref([...(props.customers || [])]);

watch(
    () => props.customers,
    (val) => {
        localCustomers.value = [...(val || [])];
    },
    { deep: true }
);

// Tab and view persistence
const validTabs = ['work_orders', 'invoices', 'vehicles', 'payments', 'payment_terms'];
const getInitialTab = () => {
    if (typeof window !== 'undefined') {
        const urlParams = new URLSearchParams(window.location.search);
        const tabFromUrl = urlParams.get('tab');
        if (tabFromUrl && validTabs.includes(tabFromUrl)) {
            return tabFromUrl;
        }
    }
    return localStorage.getItem('dealer_active_tab') || 'work_orders';
};

const activeTab = ref(getInitialTab());
const vehicleViewMode = ref(localStorage.getItem('dealer_vehicle_view_mode') || 'grid');
const workOrderViewMode = ref(localStorage.getItem('dealer_work_order_view_mode') || 'grid');

// Search states
const vehicleSearch = ref('');
const workOrderSearch = ref('');
const invoiceSearch = ref('');
const paymentSearch = ref('');
const paymentTermSearch = ref('');

// Persistence and URL synchronization
onMounted(() => {
    if (typeof window !== 'undefined') {
        const urlParams = new URLSearchParams(window.location.search);
        const tabFromUrl = urlParams.get('tab');
        if (tabFromUrl && validTabs.includes(tabFromUrl)) {
            activeTab.value = tabFromUrl;
        }
    }
});

watch(activeTab, (val) => {
    localStorage.setItem('dealer_active_tab', val);
    if (typeof window !== 'undefined') {
        const url = new URL(window.location.href);
        if (url.searchParams.get('tab') !== val) {
            url.searchParams.set('tab', val);
            window.history.replaceState({}, '', url.toString());
        }
    }
});

watch(vehicleViewMode, (val) => localStorage.setItem('dealer_vehicle_view_mode', val));
watch(workOrderViewMode, (val) => localStorage.setItem('dealer_work_order_view_mode', val));

// Tab icons
const ClipboardIcon = () =>
    h('svg', { class: 'w-4 h-4', fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
        h('path', {
            'stroke-linecap': 'round',
            'stroke-linejoin': 'round',
            'stroke-width': '2',
            d: 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2',
        }),
    ]);

const InvoiceIcon = () =>
    h('svg', { class: 'w-4 h-4', fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
        h('path', {
            'stroke-linecap': 'round',
            'stroke-linejoin': 'round',
            'stroke-width': '2',
            d: 'M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z',
        }),
    ]);

const CarIcon = () =>
    h('svg', { class: 'w-4 h-4', fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
        h('path', {
            'stroke-linecap': 'round',
            'stroke-linejoin': 'round',
            'stroke-width': '2',
            d: 'M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z',
        }),
    ]);

const PaymentIcon = () =>
    h('svg', { class: 'w-4 h-4', fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
        h('path', {
            'stroke-linecap': 'round',
            'stroke-linejoin': 'round',
            'stroke-width': '2',
            d: 'M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z',
        }),
    ]);

const PaymentTermsIcon = () =>
    h('svg', { class: 'w-4 h-4', fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
        h('path', {
            'stroke-linecap': 'round',
            'stroke-linejoin': 'round',
            'stroke-width': '2',
            d: 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
        }),
    ]);

const tabs = computed(() => [
    {
        id: 'work_orders',
        label: t('dealers.show.work_orders_tab'),
        count:
            props.counts?.workOrders ??
            (props.workOrders?.length || props.dealer?.work_orders?.length || 0),
        icon: ClipboardIcon,
    },
    {
        id: 'invoices',
        label: t('dealers.show.invoices_tab'),
        count:
            props.counts?.invoices ??
            (props.invoices?.length || props.dealer?.invoices?.length || 0),
        icon: InvoiceIcon,
    },
    {
        id: 'vehicles',
        label: t('dealers.show.vehicles_tab'),
        count:
            props.counts?.vehicles ??
            (props.vehicles?.length || props.dealer?.vehicles?.length || 0),
        icon: CarIcon,
    },
    {
        id: 'payments',
        label: t('dealers.show.payments_tab'),
        count: props.counts?.payments ?? (props.payments?.length || 0),
        icon: PaymentIcon,
    },
    {
        id: 'payment_terms',
        label: t('dealers.show.payment_terms_tab'),
        count:
            props.counts?.paymentTerms ??
            (props.paymentTerms?.length || props.dealer?.payment_terms_items?.length || 0),
        icon: PaymentTermsIcon,
    },
]);

// Resolved lists supporting both direct props and legacy dealer relations
const allVehicles = computed(() =>
    props.vehicles?.length ? props.vehicles : props.dealer?.vehicles || []
);
const allWorkOrders = computed(() =>
    props.workOrders?.length
        ? props.workOrders
        : props.dealer?.work_orders || props.dealer?.workOrders || []
);
const allInvoices = computed(() =>
    props.invoices?.length ? props.invoices : props.dealer?.invoices || []
);
const allPayments = computed(() => props.payments || []);
const allPaymentTerms = computed(() =>
    props.paymentTerms?.length ? props.paymentTerms : props.dealer?.payment_terms_items || []
);

// Filtered lists
const filteredVehicles = computed(() => {
    if (!allVehicles.value) return [];
    if (!vehicleSearch.value) return allVehicles.value;
    const q = vehicleSearch.value.toLowerCase();
    return allVehicles.value.filter(
        (v) =>
            v.plate_number?.toLowerCase().includes(q) ||
            v.customer?.name?.toLowerCase().includes(q) ||
            getMakeName(v)?.toLowerCase().includes(q) ||
            getModelName(v)?.toLowerCase().includes(q)
    );
});

const filteredWorkOrders = computed(() => {
    if (!allWorkOrders.value) return [];
    if (!workOrderSearch.value) return allWorkOrders.value;
    const q = workOrderSearch.value.toLowerCase();
    return allWorkOrders.value.filter(
        (w) =>
            w.code?.toLowerCase().includes(q) ||
            w.vehicle?.plate_number?.toLowerCase().includes(q) ||
            w.customer?.name?.toLowerCase().includes(q)
    );
});

const filteredInvoices = computed(() => {
    if (!allInvoices.value) return [];
    if (!invoiceSearch.value) return allInvoices.value;
    const q = invoiceSearch.value.toLowerCase();
    return allInvoices.value.filter(
        (inv) =>
            inv.invoice_number?.toLowerCase().includes(q) ||
            inv.work_order?.code?.toLowerCase().includes(q) ||
            inv.work_order?.vehicle?.plate_number?.toLowerCase().includes(q) ||
            inv.customer?.name?.toLowerCase().includes(q)
    );
});

const filteredPayments = computed(() => {
    if (!allPayments.value) return [];
    if (!paymentSearch.value) return allPayments.value;
    const q = paymentSearch.value.toLowerCase();
    return allPayments.value.filter(
        (p) =>
            p.reference?.toLowerCase().includes(q) ||
            p.work_order?.code?.toLowerCase().includes(q) ||
            p.work_order?.vehicle?.plate_number?.toLowerCase().includes(q) ||
            p.invoice?.invoice_number?.toLowerCase().includes(q)
    );
});

const filteredPaymentTerms = computed(() => {
    if (!allPaymentTerms.value) return [];
    if (!paymentTermSearch.value) return allPaymentTerms.value;
    const q = paymentTermSearch.value.toLowerCase();
    return allPaymentTerms.value.filter(
        (item) =>
            item.term_text?.toLowerCase().includes(q) || String(item.sort_order ?? '').includes(q)
    );
});

// Payment Terms Form & Actions
const paymentTermForm = useForm({
    term_text: '',
    sort_order: 1,
});

function openCreatePaymentTermModal() {
    editingPaymentTerm.value = null;
    paymentTermForm.reset();
    paymentTermForm.clearErrors();
    const maxOrder = allPaymentTerms.value.reduce(
        (max, item) => Math.max(max, Number(item.sort_order) || 0),
        0
    );
    paymentTermForm.sort_order = maxOrder + 1;
    showPaymentTermModal.value = true;
}

function openEditPaymentTermModal(term) {
    editingPaymentTerm.value = term;
    paymentTermForm.clearErrors();
    paymentTermForm.term_text = term.term_text || '';
    paymentTermForm.sort_order = Number(term.sort_order ?? 0);
    showPaymentTermModal.value = true;
}

function closePaymentTermModal() {
    showPaymentTermModal.value = false;
    editingPaymentTerm.value = null;
}

function submitPaymentTerm() {
    if (editingPaymentTerm.value) {
        paymentTermForm.put(
            route('app.dealers.payment-terms.update', [
                props.dealer.id,
                editingPaymentTerm.value.id,
            ]),
            {
                preserveScroll: true,
                onSuccess: () => {
                    closePaymentTermModal();
                },
            }
        );
    } else {
        paymentTermForm.post(route('app.dealers.payment-terms.store', props.dealer.id), {
            preserveScroll: true,
            onSuccess: () => {
                closePaymentTermModal();
            },
        });
    }
}

function confirmDeletePaymentTerm(term) {
    deletingPaymentTerm.value = term;
    showDeletePaymentTermModal.value = true;
}

function executeDeletePaymentTerm() {
    if (!deletingPaymentTerm.value) return;
    router.delete(
        route('app.dealers.payment-terms.destroy', [props.dealer.id, deletingPaymentTerm.value.id]),
        {
            preserveScroll: true,
            onFinish: () => {
                showDeletePaymentTermModal.value = false;
                deletingPaymentTerm.value = null;
            },
        }
    );
}

// Add Vehicle Form & Actions (linked to current dealer)
const vehicleForm = useForm({
    customer_id: '',
    dealer_id: props.dealer.id,
    plate_number: '',
    vehicle_make_id: '',
    make_other: '',
    vehicle_model_id: '',
    model_other: '',
    year: '',
    color: '',
    odometer: '',
    vin: '',
});

const customerOptions = computed(() =>
    (localCustomers.value || []).map((c) => ({
        ...c,
        display_name: c.phone ? `${c.name} - ${c.phone}` : c.name,
    }))
);

const currentYear = new Date().getFullYear() + 1;
const yearOptions = computed(() => {
    const years = [];
    for (let y = currentYear; y >= 1980; y--) {
        years.push({ id: y, name: String(y) });
    }
    return years;
});

const makesWithOther = computed(() => [
    ...(props.makes || []),
    { id: 'other', name_ar: t('common.other'), name_en: t('common.other') },
]);

const availableModelsWithOther = computed(() => {
    const makeId = vehicleForm.vehicle_make_id;
    const list = makeId && props.modelsByMake?.[makeId] ? props.modelsByMake[makeId] : [];
    return [...list, { id: 'other', name_ar: t('common.other'), name_en: t('common.other') }];
});

const colorsWithOther = computed(() => props.colors || []);

watch(
    () => vehicleForm.vehicle_make_id,
    () => {
        vehicleForm.vehicle_model_id = '';
    }
);

function openAddVehicleModal() {
    vehicleForm.reset();
    vehicleForm.clearErrors();
    vehicleForm.dealer_id = props.dealer.id;
    showAddVehicleModal.value = true;
}

function closeAddVehicleModal() {
    showAddVehicleModal.value = false;
}

function submitAddVehicle() {
    vehicleForm
        .transform((data) => ({
            ...data,
            dealer_id: props.dealer.id,
            vehicle_make_id: data.vehicle_make_id === 'other' ? null : data.vehicle_make_id || null,
            vehicle_model_id:
                data.vehicle_make_id === 'other' || data.vehicle_model_id === 'other'
                    ? null
                    : data.vehicle_model_id || null,
            year: data.year || null,
            odometer: data.odometer !== '' && data.odometer !== null ? Number(data.odometer) : null,
        }))
        .post(route('vehicles.store'), {
            preserveScroll: true,
            onSuccess: () => {
                closeAddVehicleModal();
                router.reload({ preserveScroll: true });
            },
        });
}

function handleQuickCustomerSaved(newCustomer) {
    showQuickCustomerModal.value = false;
    if (newCustomer && newCustomer.id) {
        const exists = localCustomers.value.some((c) => c.id === newCustomer.id);
        if (!exists) {
            localCustomers.value.unshift(newCustomer);
        }
        vehicleForm.customer_id = newCustomer.id;
    }
}

// Table columns
const workOrderColumns = computed(() => [
    { key: 'code', label: t('reports.dealers_report.col_code') },
    { key: 'vehicle', label: t('reports.dealers_report.col_vehicle') },
    { key: 'customer', label: t('reports.dealers_report.col_customer') },
    { key: 'status', label: t('dealers.columns.status') },
    { key: 'billing', label: t('reports.dealers_report.col_billing') },
    { key: 'created_at', label: t('common.date') },
    { key: 'total', label: t('reports.dealers_report.col_due'), align: 'end' },
    { key: 'paid', label: t('reports.dealers_report.col_paid'), align: 'end' },
    { key: 'balance', label: t('reports.dealers_report.col_remaining'), align: 'end' },
]);

const invoiceColumns = computed(() => [
    { key: 'invoice_number', label: t('invoices.invoice_number') },
    { key: 'issue_date', label: t('invoices.columns.issue_date') },
    { key: 'vehicle_wo', label: t('work_orders.work_order') },
    { key: 'customer', label: t('reports.dealers_report.col_customer') },
    { key: 'billing', label: t('reports.dealers_report.col_billing') },
    { key: 'status', label: t('invoices.columns.status') },
    { key: 'total', label: t('invoices.columns.total'), align: 'end' },
    { key: 'paid', label: t('invoices.columns.paid'), align: 'end' },
    { key: 'balance', label: t('invoices.columns.balance'), align: 'end' },
]);

const vehicleColumns = computed(() => [
    { key: 'plate', label: t('reports.dealers_report.col_vehicle') },
    { key: 'make_model', label: t('vehicles.form.model') },
    { key: 'customer', label: t('reports.dealers_report.col_customer') },
    { key: 'odometer', label: t('vehicles.form.odometer') },
    { key: 'vin', label: t('vehicles.form.vin') },
]);

const paymentColumns = computed(() => [
    { key: 'reference', label: t('dealers.show.reference') },
    { key: 'payment_date', label: t('common.date') },
    { key: 'type', label: t('dealers.columns.type') },
    { key: 'source', label: t('invoices.columns.source') },
    { key: 'method', label: t('dealers.show.payment_method') },
    { key: 'amount', label: t('common.amount'), align: 'end' },
    { key: 'received_by', label: t('common.created_by') },
]);

// Formatting helpers
function formatPrice(amount) {
    return new Intl.NumberFormat('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(Number(amount) || 0);
}

function formatDate(date) {
    if (!date) return '-';
    return new Date(date).toLocaleDateString(locale.value === 'ar' ? 'ar-SA' : 'en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
}

function getMakeName(vehicle) {
    if (!vehicle) return '';
    if (vehicle.make) {
        return locale.value === 'en'
            ? vehicle.make.name_en || vehicle.make.name_ar
            : vehicle.make.name_ar || vehicle.make.name_en;
    }
    return vehicle.make_other || '';
}

function getModelName(vehicle) {
    if (!vehicle) return '';
    if (vehicle.model) {
        return locale.value === 'en'
            ? vehicle.model.name_en || vehicle.model.name_ar
            : vehicle.model.name_ar || vehicle.model.name_en;
    }
    return vehicle.model_other || '';
}

function getVehicleName(vehicle) {
    if (!vehicle) return '';
    const make = getMakeName(vehicle);
    const model = getModelName(vehicle);
    return `${make} ${model}`.trim();
}

function getWhatsAppNumber(dealer) {
    const phone = dealer?.whatsapp || dealer?.phone;
    return phone ? phone.replace(/[^\d+]/g, '').replace(/^\+/, '') : '';
}

function getStatusClass(status) {
    const classes = {
        open: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
        in_progress: 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
        done: 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-400',
        completed: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400',
        closed: 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300',
        cancelled: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
    };
    return classes[status] || classes.open;
}

function getInvoicePaymentStatusClass(status) {
    const classes = {
        paid: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400',
        partial: 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
        unpaid: 'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-400',
    };
    return classes[status] || classes.unpaid;
}

function getPaymentMethodClass(method) {
    const classes = {
        cash: 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
        mada: 'bg-purple-100 text-purple-700',
        visa: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
        mastercard: 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400',
        transfer: 'bg-cyan-100 text-cyan-700 dark:bg-cyan-900/30 dark:text-cyan-400',
        apple_pay: 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300',
        stc_pay: 'bg-purple-100 text-purple-700',
        credit: 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400',
        other: 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300',
    };
    return classes[method] || classes.other;
}

function getColorHex(colorName) {
    if (!colorName) return '#9ca3af';
    const colorMap = {
        أبيض: '#ffffff',
        أسود: '#1f2937',
        فضي: '#9ca3af',
        رمادي: '#6b7280',
        أحمر: '#ef4444',
        أزرق: '#3b82f6',
        أخضر: '#22c55e',
        ذهبي: '#eab308',
        بني: '#92400e',
        برتقالي: '#f97316',
        بيج: '#d4c4a8',
        white: '#ffffff',
        black: '#1f2937',
        silver: '#9ca3af',
        gray: '#6b7280',
        red: '#ef4444',
        blue: '#3b82f6',
        green: '#22c55e',
        gold: '#eab308',
        brown: '#92400e',
        orange: '#f97316',
        beige: '#d4c4a8',
    };
    return colorMap[colorName.toLowerCase()] || colorMap[colorName] || '#9ca3af';
}

// Action handlers
function handleDealerSaved() {
    showEditModal.value = false;
    router.reload({ preserveScroll: true });
}

function confirmDelete() {
    if (!props.canDelete) return;
    showDeleteModal.value = true;
}

function executeDelete() {
    router.delete(route('app.dealers.destroy', props.dealer.id), {
        onFinish: () => {
            showDeleteModal.value = false;
        },
    });
}

function toggleActiveStatus() {
    showToggleModal.value = true;
}

function executeToggle() {
    router.patch(
        route('app.dealers.toggle', props.dealer.id),
        {},
        {
            preserveScroll: true,
            onFinish: () => {
                showToggleModal.value = false;
            },
        }
    );
}
</script>
