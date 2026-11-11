<template>
    <div class="min-h-screen bg-gray-100 dark:bg-gray-900">
        <!-- Global Toast Notifications -->
        <Toast />
        <!-- Global Confirm Modal -->
        <ConfirmModal />
        <!-- Quick Command Palette (Cmd+K) -->
        <CommandPalette ref="commandPaletteRef" />

        <!-- Impersonation Warning Banner -->
        <div
            v-if="page.props.impersonating"
            class="fixed top-0 inset-x-0 z-50 bg-amber-500 text-amber-900 py-2 px-4 flex items-center justify-between shadow-lg"
        >
            <div class="flex items-center gap-2 text-sm font-medium">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
                    />
                </svg>
                <span>
                    أنت داخل حساب المستأجر:
                    <strong>{{ page.props.impersonating_tenant_name }}</strong>
                </span>
            </div>
            <button
                @click="stopImpersonation"
                class="px-3 py-1 bg-amber-600 hover:bg-amber-700 text-white rounded-lg text-sm font-medium"
            >
                العودة للوحة النظام
            </button>
        </div>

        <div
            class="flex h-screen overflow-hidden print:!overflow-visible print:!h-auto"
            :style="page.props.impersonating ? 'padding-top: 44px' : ''"
        >
            <!-- Desktop Sidebar -->
            <aside
                v-if="!hideSidebar && !isTopNav"
                :class="[
                    'hidden md:flex md:w-64 md:shrink-0 md:flex-col bg-white dark:bg-gray-800 print:hidden',
                    isRtl
                        ? 'border-l border-gray-200 dark:border-gray-700'
                        : 'border-r border-gray-200 dark:border-gray-700',
                ]"
            >
                <!-- Brand -->
                <div
                    class="h-14 flex items-center gap-3 px-4 border-b border-gray-200 dark:border-gray-700 flex-shrink-0"
                >
                    <!-- Logo -->
                    <div
                        class="w-9 h-9 rounded-lg bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 flex items-center justify-center overflow-hidden flex-shrink-0"
                    >
                        <img
                            v-if="page.props.tenant?.logo_url"
                            :src="page.props.tenant.logo_url"
                            :alt="page.props.tenant?.name"
                            class="w-full h-full object-contain"
                        />
                        <img
                            v-else
                            src="/images/brand/logo-64.png"
                            :alt="$t('common.app_name')"
                            class="w-full h-full object-contain"
                        />
                    </div>
                    <!-- Name & ID -->
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-bold text-gray-900 dark:text-white truncate">
                            {{
                                page.props.tenant?.trade_name ||
                                page.props.tenant?.name ||
                                $t('common.app_name')
                            }}
                        </p>
                        <p
                            v-if="page.props.center?.id"
                            class="text-xs text-gray-500 dark:text-gray-400"
                        >
                            #{{ page.props.center.id }}
                        </p>
                        <p
                            v-else-if="page.props.tenant?.id"
                            class="text-xs text-gray-500 dark:text-gray-400"
                        >
                            #{{ page.props.tenant.id }}
                        </p>
                    </div>
                </div>

                <!-- Navigation -->
                <nav class="flex-1 px-3 py-4 space-y-1 overflow-y-auto">
                    <!-- 1. لوحة التحكم -->
                    <a
                        href="/dashboard"
                        :class="[
                            'flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors',
                            isActive('/dashboard')
                                ? 'bg-indigo-50 dark:bg-indigo-900/50 text-indigo-700 dark:text-indigo-300'
                                : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700',
                        ]"
                    >
                        <svg
                            class="w-5 h-5 flex-shrink-0"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"
                            />
                        </svg>
                        {{ $t('nav.dashboard') }}
                    </a>

                    <!-- 2. كروت العمل -->
                    <Link
                        v-if="can('crm.work_orders.view')"
                        :href="route('work-orders.index')"
                        :class="[
                            'flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors',
                            isActive('/app/work-orders')
                                ? 'bg-indigo-50 dark:bg-indigo-900/50 text-indigo-700 dark:text-indigo-300'
                                : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700',
                        ]"
                    >
                        <svg
                            class="w-5 h-5 flex-shrink-0"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"
                            />
                        </svg>
                        {{ $t('nav.work_orders') }}
                    </Link>

                    <!-- 3. التقييمات -->
                    <a
                        v-if="can('quotes.view')"
                        href="/app/quotes"
                        :class="[
                            'flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors',
                            isActive('/app/quotes')
                                ? 'bg-amber-50 dark:bg-amber-900/50 text-amber-700 dark:text-amber-300'
                                : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700',
                        ]"
                    >
                        <svg
                            class="w-5 h-5 flex-shrink-0"
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
                        <span class="flex-1 text-start">{{ $t('nav.quotes') }}</span>
                        <span
                            v-if="page.props.auth?.approved_quotes_count > 0"
                            class="px-2 py-0.5 text-xs font-bold rounded-full bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300"
                        >
                            {{ page.props.auth.approved_quotes_count }}
                        </span>
                    </a>

                    <!-- 4. لوحة المركز -->
                    <Link
                        v-if="can('crm.work_orders.view')"
                        :href="route('app.workshop.index')"
                        :class="[
                            'flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors',
                            isActive('/app/workshop')
                                ? 'bg-emerald-50 dark:bg-emerald-900/50 text-emerald-700 dark:text-emerald-300'
                                : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700',
                        ]"
                    >
                        <svg
                            class="w-5 h-5 flex-shrink-0"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M4 6h6v6H4V6zm0 10h6v4H4v-4zm10-10h6v4h-6V6zm0 8h6v6h-6v-6z"
                            />
                        </svg>
                        {{ $t('workshop.board.title') }}
                    </Link>

                    <!-- 5. العملاء -->
                    <a
                        v-if="can('crm.customers.view')"
                        href="/app/customers"
                        :class="[
                            'flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors',
                            isActive('/app/customers')
                                ? 'bg-indigo-50 dark:bg-indigo-900/50 text-indigo-700 dark:text-indigo-300'
                                : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700',
                        ]"
                    >
                        <svg
                            class="w-5 h-5 flex-shrink-0"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"
                            />
                        </svg>
                        {{ $t('nav.customers') }}
                    </a>

                    <!-- 6. المركبات -->
                    <a
                        v-if="can('crm.vehicles.view')"
                        href="/app/vehicles"
                        :class="[
                            'flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors',
                            isActive('/app/vehicles')
                                ? 'bg-indigo-50 dark:bg-indigo-900/50 text-indigo-700 dark:text-indigo-300'
                                : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700',
                        ]"
                    >
                        <svg
                            class="w-5 h-5 flex-shrink-0"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z"
                            />
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0"
                            />
                        </svg>
                        {{ $t('nav.vehicles') }}
                    </a>

                    <!-- 7. الفواتير -->
                    <Link
                        v-if="can('invoices.view')"
                        :href="route('app.invoices.hub')"
                        :class="[
                            'flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors',
                            isActive('/app/invoices')
                                ? 'bg-green-50 dark:bg-green-900/50 text-green-700 dark:text-green-300'
                                : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700',
                        ]"
                    >
                        <svg
                            class="w-5 h-5 flex-shrink-0"
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
                        {{ $t('nav.invoices') }}
                    </Link>

                    <!-- 8. المواعيد -->
                    <Link
                        v-if="can('crm.appointments.manage')"
                        :href="route('appointments.index')"
                        :class="[
                            'flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors',
                            isActive('/app/appointments')
                                ? 'bg-emerald-50 dark:bg-emerald-900/50 text-emerald-700 dark:text-emerald-300'
                                : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700',
                        ]"
                    >
                        <svg
                            class="w-5 h-5 flex-shrink-0"
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
                        {{ $t('appointments.title') }}
                    </Link>

                    <!-- 9. سجل رسائل المواعيد -->
                    <Link
                        v-if="can('crm.appointments.manage')"
                        :href="route('communications.appointment-messages.index')"
                        :class="[
                            'flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors',
                            isActive('/app/communications/appointment-messages')
                                ? 'bg-sky-50 dark:bg-sky-900/50 text-sky-700 dark:text-sky-300'
                                : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700',
                        ]"
                    >
                        <svg
                            class="w-5 h-5 flex-shrink-0"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M8 10h.01M12 10h.01M16 10h.01M21 12c0 4.418-4.03 8-9 8a9.86 9.86 0 01-4.39-1.03L3 20l1.39-3.94A7.96 7.96 0 013 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"
                            />
                        </svg>
                        {{ $t('communications.appointment_messages.title') }}
                    </Link>

                    <!-- 10. الموردين -->
                    <a
                        v-if="can('purchasing.suppliers.view')"
                        href="/app/purchasing/suppliers"
                        :class="[
                            'flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors',
                            isActive('/app/purchasing/suppliers')
                                ? 'bg-violet-50 dark:bg-violet-900/50 text-violet-700 dark:text-violet-300'
                                : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700',
                        ]"
                    >
                        <svg
                            class="w-5 h-5 flex-shrink-0"
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
                        {{ $t('purchasing.suppliers.title') }}
                    </a>

                    <!-- Divider -->
                    <div class="my-3 border-t border-gray-200 dark:border-gray-700"></div>

                    <a
                        v-if="can('services.view')"
                        href="/app/services"
                        :class="[
                            'flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors',
                            isActive('/app/services')
                                ? 'bg-teal-50 dark:bg-teal-900/50 text-teal-700 dark:text-teal-300'
                                : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700',
                        ]"
                    >
                        <svg
                            class="w-5 h-5 flex-shrink-0"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"
                            />
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
                            />
                        </svg>
                        {{ $t('nav.services') }}
                    </a>

                    <!-- Divider: Inventory & Purchasing -->
                    <div class="my-3 border-t border-gray-200 dark:border-gray-700"></div>

                    <!-- Inventory Collapsible Section -->
                    <div v-if="can('inventory.view')">
                        <a
                            href="/app/inventory"
                            @click="inventoryExpanded = !inventoryExpanded"
                            :class="[
                                'w-full flex items-center justify-between gap-2 px-3 py-2 text-xs font-semibold uppercase tracking-wider rounded-lg transition-colors',
                                isActive('/app/inventory')
                                    ? 'bg-emerald-50 dark:bg-emerald-900/50 text-emerald-700 dark:text-emerald-300'
                                    : 'text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700',
                            ]"
                        >
                            <div class="flex items-center gap-2">
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
                                        d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"
                                    />
                                </svg>
                                {{ $t('inventory.title') }}
                            </div>
                            <svg
                                @click.stop.prevent="inventoryExpanded = !inventoryExpanded"
                                :class="[
                                    'w-4 h-4 transition-transform p-0.5 hover:bg-gray-200 dark:hover:bg-gray-600 rounded',
                                    inventoryExpanded ? 'rotate-180' : '',
                                ]"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M19 9l-7 7-7-7"
                                />
                            </svg>
                        </a>

                        <div v-show="inventoryExpanded" class="ps-4 mt-1 space-y-1">
                            <a
                                href="/app/inventory/parts"
                                :class="[
                                    'flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition-colors',
                                    isActive('/app/inventory/parts')
                                        ? 'bg-emerald-50 dark:bg-emerald-900/50 text-emerald-700 dark:text-emerald-300'
                                        : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700',
                                ]"
                            >
                                <svg
                                    class="w-4 h-4 flex-shrink-0"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"
                                    />
                                </svg>
                                {{ $t('inventory.parts.title') }}
                            </a>
                            <a
                                v-if="can('inventory.stock.view')"
                                href="/app/inventory/stock"
                                :class="[
                                    'flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition-colors',
                                    isActive('/app/inventory/stock')
                                        ? 'bg-emerald-50 dark:bg-emerald-900/50 text-emerald-700 dark:text-emerald-300'
                                        : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700',
                                ]"
                            >
                                <svg
                                    class="w-4 h-4 flex-shrink-0"
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
                                {{ $t('inventory.stock.title') }}
                            </a>
                            <a
                                v-if="can('inventory.moves.view')"
                                href="/app/inventory/moves"
                                :class="[
                                    'flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition-colors',
                                    isActive('/app/inventory/moves')
                                        ? 'bg-emerald-50 dark:bg-emerald-900/50 text-emerald-700 dark:text-emerald-300'
                                        : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700',
                                ]"
                            >
                                <svg
                                    class="w-4 h-4 flex-shrink-0"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"
                                    />
                                </svg>
                                {{ $t('inventory.moves.title') }}
                            </a>
                            <a
                                v-if="can('inventory.moves.view')"
                                href="/app/inventory/transfers"
                                :class="[
                                    'flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition-colors',
                                    isActive('/app/inventory/transfers')
                                        ? 'bg-emerald-50 dark:bg-emerald-900/50 text-emerald-700 dark:text-emerald-300'
                                        : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700',
                                ]"
                            >
                                <svg
                                    class="w-4 h-4 flex-shrink-0"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"
                                    />
                                </svg>
                                {{ $t('inventory.transfers.title') }}
                            </a>
                            <a
                                v-if="can('inventory.settings.manage')"
                                href="/app/inventory/settings"
                                :class="[
                                    'flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition-colors',
                                    isActive('/app/inventory/settings')
                                        ? 'bg-emerald-50 dark:bg-emerald-900/50 text-emerald-700 dark:text-emerald-300'
                                        : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700',
                                ]"
                            >
                                <svg
                                    class="w-4 h-4 flex-shrink-0"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"
                                    />
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
                                    />
                                </svg>
                                {{ $t('inventory.settings.title') }}
                            </a>
                        </div>
                    </div>

                    <!-- Purchasing Direct Link -->
                    <a
                        v-if="can('purchasing.suppliers.view')"
                        href="/app/purchasing"
                        :class="[
                            'flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors',
                            isActive('/app/purchasing') && !isActive('/app/purchasing/suppliers')
                                ? 'bg-violet-50 dark:bg-violet-900/50 text-violet-700 dark:text-violet-300'
                                : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700',
                        ]"
                    >
                        <svg
                            class="w-5 h-5 flex-shrink-0"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"
                            />
                        </svg>
                        {{ $t('purchasing.title') }}
                    </a>

                    <!-- HR Section -->
                    <div v-if="can('hr.view')" class="mt-2">
                        <a
                            href="/app/hr"
                            @click="hrExpanded = !hrExpanded"
                            :class="[
                                'w-full flex items-center justify-between gap-2 px-3 py-2 text-xs font-semibold uppercase tracking-wider rounded-lg transition-colors',
                                isActive('/app/hr')
                                    ? 'bg-violet-50 dark:bg-violet-900/50 text-violet-700 dark:text-violet-300'
                                    : 'text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700',
                            ]"
                        >
                            <div class="flex items-center gap-2">
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
                                        d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"
                                    />
                                </svg>
                                {{ $t('hr.title') }}
                            </div>
                            <svg
                                @click.stop.prevent="hrExpanded = !hrExpanded"
                                :class="[
                                    'w-4 h-4 transition-transform p-0.5 hover:bg-gray-200 dark:hover:bg-gray-600 rounded',
                                    hrExpanded ? 'rotate-180' : '',
                                ]"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M19 9l-7 7-7-7"
                                />
                            </svg>
                        </a>

                        <div v-show="hrExpanded" class="ps-4 mt-1 space-y-1">
                            <a
                                href="/app/hr/employees"
                                :class="[
                                    'flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition-colors',
                                    isActive('/app/hr/employees')
                                        ? 'bg-violet-50 dark:bg-violet-900/50 text-violet-700 dark:text-violet-300'
                                        : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700',
                                ]"
                            >
                                <svg
                                    class="w-4 h-4 flex-shrink-0"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"
                                    />
                                </svg>
                                {{ $t('hr.employees.title') }}
                            </a>
                            <a
                                href="/app/hr/attendance"
                                :class="[
                                    'flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition-colors',
                                    isActive('/app/hr/attendance')
                                        ? 'bg-violet-50 dark:bg-violet-900/50 text-violet-700 dark:text-violet-300'
                                        : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700',
                                ]"
                            >
                                <svg
                                    class="w-4 h-4 flex-shrink-0"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"
                                    />
                                </svg>
                                {{ $t('hr.attendance.title') }}
                            </a>
                            <a
                                v-if="can('hr.payroll.view')"
                                href="/app/hr/payroll"
                                :class="[
                                    'flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition-colors',
                                    isActive('/app/hr/payroll')
                                        ? 'bg-violet-50 dark:bg-violet-900/50 text-violet-700 dark:text-violet-300'
                                        : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700',
                                ]"
                            >
                                <svg
                                    class="w-4 h-4 flex-shrink-0"
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
                                {{ $t('nav.payroll') }}
                            </a>
                            <a
                                href="/app/hr/settings"
                                :class="[
                                    'flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition-colors',
                                    isActive('/app/hr/settings')
                                        ? 'bg-violet-50 dark:bg-violet-900/50 text-violet-700 dark:text-violet-300'
                                        : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700',
                                ]"
                            >
                                <svg
                                    class="w-4 h-4 flex-shrink-0"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"
                                    />
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
                                    />
                                </svg>
                                {{ $t('hr.settings.title') }}
                            </a>
                        </div>
                    </div>

                    <!-- Reports Link -->
                    <a
                        href="/app/reports"
                        :class="[
                            'flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors',
                            isActive('/app/reports')
                                ? 'bg-violet-50 dark:bg-violet-900/50 text-violet-700 dark:text-violet-300'
                                : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700',
                        ]"
                    >
                        <svg
                            class="w-5 h-5 flex-shrink-0"
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
                        {{ $t('nav.reports') }}
                    </a>

                    <!-- Add-ons Link (الإضافات: يضم المساعدة والشروحات، البلاغات، والتكاملات) -->
                    <a
                        href="/app/settings/integrations"
                        :class="[
                            'flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors',
                            isActive('/app/settings/integrations')
                                ? 'bg-indigo-50 dark:bg-indigo-900/50 text-indigo-700 dark:text-indigo-300 font-bold'
                                : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700',
                        ]"
                    >
                        <svg
                            class="w-5 h-5 flex-shrink-0"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M11 4a2 2 0 114 0v1a1 1 0 001 1h3a1 1 0 011 1v3a1 1 0 01-1 1h-1a2 2 0 100 4h1a1 1 0 011 1v3a1 1 0 01-1 1h-3a1 1 0 01-1-1v-1a2 2 0 10-4 0v1a1 1 0 01-1 1H7a1 1 0 01-1-1v-3a1 1 0 00-1-1H4a2 2 0 110-4h1a1 1 0 001-1V7a1 1 0 011-1h3a1 1 0 001-1V4z"
                            />
                        </svg>
                        {{ $t('nav.integrations') }}
                    </a>

                    <!-- Divider -->
                    <div class="my-3 border-t border-gray-200 dark:border-gray-700"></div>

                    <!-- Employee Portal Link -->
                    <a
                        v-if="hasRole('employee')"
                        href="/app/my"
                        :class="[
                            'flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors',
                            isActive('/app/my')
                                ? 'bg-cyan-50 dark:bg-cyan-900/50 text-cyan-700 dark:text-cyan-300'
                                : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700',
                        ]"
                    >
                        <svg
                            class="w-5 h-5 flex-shrink-0"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0zm6 2a9 9 0 11-18 0 9 9 0 0118 0z"
                            />
                        </svg>
                        {{ $t('nav.employee_portal') }}
                    </a>

                    <a
                        v-if="
                            can('settings.company.manage') ||
                            can('settings.centers.view') ||
                            can('users.view') ||
                            can('crm.vehicles.settings.view')
                        "
                        href="/app/settings"
                        :class="[
                            'flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors',
                            isActive('/app/settings')
                                ? 'bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-white'
                                : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700',
                        ]"
                    >
                        <svg
                            class="w-5 h-5 flex-shrink-0"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"
                            />
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
                            />
                        </svg>
                        {{ $t('nav.settings') }}
                    </a>
                </nav>
            </aside>

            <!-- Main Content -->
            <div class="flex-1 min-w-0 flex flex-col h-full min-h-0">
                <SystemAnnouncementBanner v-if="$page.props.system_announcements?.length > 0" />

                <!-- Top bar -->
                <header
                    class="relative z-50 h-14 flex items-center justify-between gap-2 px-3 sm:px-4 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 flex-shrink-0"
                >
                    <!-- Start Section (Right in RTL / Left in LTR): Hamburger + Brand + Active Center -->
                    <div class="flex items-center gap-2 sm:gap-3 min-w-0">
                        <!-- Mobile hamburger -->
                        <button
                            @click="mobileMenuOpen = true"
                            class="md:hidden p-1.5 -ms-1 rounded-lg text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-700 shrink-0"
                            aria-label="Toggle Navigation"
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
                                    d="M4 6h16M4 12h16M4 18h16"
                                />
                            </svg>
                        </button>

                        <!-- Mobile Brand Logo, Tenant Name & Active Center -->
                        <div class="relative flex md:hidden items-center gap-2 min-w-0">
                            <div
                                class="w-8 h-8 rounded-lg bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 flex items-center justify-center overflow-hidden shrink-0"
                            >
                                <img
                                    v-if="page.props.tenant?.logo_url"
                                    :src="page.props.tenant.logo_url"
                                    :alt="page.props.tenant?.name"
                                    class="w-full h-full object-contain"
                                />
                                <svg
                                    v-else
                                    class="w-4 h-4 text-indigo-600 dark:text-indigo-400"
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
                            <div class="flex flex-col min-w-0">
                                <span
                                    class="text-xs font-bold text-gray-900 dark:text-white truncate max-w-[135px] xs:max-w-[175px]"
                                >
                                    {{
                                        page.props.tenant?.trade_name ||
                                        page.props.tenant?.name ||
                                        $t('common.app_name')
                                    }}
                                </span>
                                <button
                                    v-if="
                                        page.props.auth.available_centers?.length > 1 &&
                                        page.props.center
                                    "
                                    type="button"
                                    @click="toggleCenterMenu"
                                    class="flex items-center gap-1 text-[11px] font-medium text-indigo-600 dark:text-indigo-400 truncate max-w-[140px] xs:max-w-[180px]"
                                >
                                    <span class="truncate">
                                        {{ getCenterName(page.props.center) }}
                                    </span>
                                    <svg
                                        class="w-3 h-3 shrink-0"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M19 9l-7 7-7-7"
                                        />
                                    </svg>
                                </button>
                                <span
                                    v-else-if="page.props.center"
                                    class="text-[11px] font-medium text-indigo-600 dark:text-indigo-400 truncate max-w-[140px] xs:max-w-[180px]"
                                >
                                    {{ getCenterName(page.props.center) }}
                                </span>
                            </div>

                            <!-- Mobile Center Switcher Dropdown -->
                            <Transition name="dropdown">
                                <div
                                    v-if="
                                        centerMenuOpen &&
                                        page.props.auth.available_centers?.length > 1
                                    "
                                    class="absolute top-full start-0 mt-2 w-60 bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 py-1 z-50 overflow-hidden"
                                >
                                    <div
                                        class="px-3 py-2 text-xs font-semibold text-gray-500 uppercase tracking-wider bg-gray-50 dark:bg-gray-700/50"
                                    >
                                        {{ $t('common.switch_center') }}
                                    </div>
                                    <button
                                        v-for="c in page.props.auth.available_centers"
                                        :key="c.id"
                                        @click="switchCenter(c.id)"
                                        :class="[
                                            'w-full text-start px-4 py-2.5 text-sm hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors block border-b last:border-0 border-gray-100 dark:border-gray-700/50',
                                            c.id === page.props.center?.id
                                                ? 'text-indigo-600 dark:text-indigo-400 font-bold bg-indigo-50/50 dark:bg-indigo-900/10'
                                                : 'text-gray-700 dark:text-gray-300',
                                        ]"
                                    >
                                        <div class="flex items-center justify-between gap-2">
                                            <span class="truncate">{{ getCenterName(c) }}</span>
                                            <span
                                                v-if="c.id === page.props.center?.id"
                                                class="text-indigo-600 dark:text-indigo-400"
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
                                                        d="M5 13l4 4L19 7"
                                                    />
                                                </svg>
                                            </span>
                                        </div>
                                    </button>
                                </div>
                            </Transition>
                        </div>

                        <!-- Desktop Brand Info when TopNav is active -->
                        <div v-if="isTopNav" class="hidden md:flex items-center gap-2.5 min-w-0">
                            <div
                                class="w-8 h-8 rounded-lg bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 flex items-center justify-center overflow-hidden shrink-0"
                            >
                                <img
                                    v-if="page.props.tenant?.logo_url"
                                    :src="page.props.tenant.logo_url"
                                    :alt="page.props.tenant?.name"
                                    class="w-full h-full object-contain"
                                />
                                <svg
                                    v-else
                                    class="w-4 h-4 text-gray-400"
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
                            <span
                                class="text-sm font-bold text-gray-900 dark:text-white truncate max-w-[190px]"
                            >
                                {{
                                    page.props.tenant?.trade_name ||
                                    page.props.tenant?.name ||
                                    $t('common.app_name')
                                }}
                            </span>
                            <span
                                v-if="page.props.center"
                                class="w-px h-5 bg-gray-200 dark:bg-gray-700 mx-0.5 shrink-0"
                            ></span>
                        </div>

                        <!-- Desktop Center Switcher / Active Center Badge (Anchored at Header Start) -->
                        <div
                            v-if="
                                page.props.auth.available_centers?.length > 1 && page.props.center
                            "
                            class="relative hidden md:block"
                        >
                            <button
                                type="button"
                                @click="toggleCenterMenu"
                                class="flex items-center gap-2 px-3 py-1.5 rounded-xl text-sm font-semibold text-gray-700 dark:text-gray-200 bg-gray-50/80 dark:bg-gray-700/40 hover:bg-gray-100 dark:hover:bg-gray-700 border border-gray-200/80 dark:border-gray-600/80 transition-colors cursor-pointer"
                            >
                                <svg
                                    class="w-4 h-4 text-indigo-600 dark:text-indigo-400 shrink-0"
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
                                <span class="max-w-[180px] truncate">
                                    {{ getCenterName(page.props.center) }}
                                </span>
                                <span
                                    class="text-[11px] font-mono text-gray-400 dark:text-gray-400"
                                >
                                    #{{ page.props.center.id }}
                                </span>
                                <svg
                                    class="w-3.5 h-3.5 text-gray-400 shrink-0"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M19 9l-7 7-7-7"
                                    />
                                </svg>
                            </button>

                            <!-- Desktop Center Switcher Dropdown -->
                            <Transition name="dropdown">
                                <div
                                    v-if="centerMenuOpen"
                                    class="absolute top-full start-0 mt-2 w-60 bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 py-1 z-50 overflow-hidden"
                                >
                                    <div
                                        class="px-3 py-2 text-xs font-semibold text-gray-500 uppercase tracking-wider bg-gray-50 dark:bg-gray-700/50"
                                    >
                                        {{ $t('common.switch_center') }}
                                    </div>
                                    <button
                                        v-for="c in page.props.auth.available_centers"
                                        :key="c.id"
                                        @click="switchCenter(c.id)"
                                        :class="[
                                            'w-full text-start px-4 py-2.5 text-sm hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors block border-b last:border-0 border-gray-100 dark:border-gray-700/50',
                                            c.id === page.props.center?.id
                                                ? 'text-indigo-600 dark:text-indigo-400 font-bold bg-indigo-50/50 dark:bg-indigo-900/10'
                                                : 'text-gray-700 dark:text-gray-300',
                                        ]"
                                    >
                                        <div class="flex items-center justify-between gap-2">
                                            <span class="truncate">{{ getCenterName(c) }}</span>
                                            <span
                                                v-if="c.id === page.props.center?.id"
                                                class="text-indigo-600 dark:text-indigo-400"
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
                                                        d="M5 13l4 4L19 7"
                                                    />
                                                </svg>
                                            </span>
                                        </div>
                                    </button>
                                </div>
                            </Transition>
                        </div>
                        <div
                            v-else-if="page.props.center"
                            class="hidden md:inline-flex items-center gap-2 px-3 py-1.5 rounded-xl bg-gray-50/80 dark:bg-gray-700/40 border border-gray-200/70 dark:border-gray-700 text-xs sm:text-sm font-semibold text-gray-700 dark:text-gray-200 max-w-[240px]"
                        >
                            <svg
                                class="w-4 h-4 text-indigo-600 dark:text-indigo-400 shrink-0"
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
                            <span class="truncate">
                                {{ getCenterName(page.props.center) }}
                            </span>
                        </div>
                    </div>

                    <!-- Center: Quick Search / Command Palette trigger -->
                    <button
                        type="button"
                        @click="openCommandPalette"
                        class="hidden md:inline-flex items-center gap-2.5 px-3 py-1.5 text-xs text-gray-500 dark:text-gray-400 bg-gray-100/80 dark:bg-gray-700/50 hover:bg-gray-200/80 dark:hover:bg-gray-700 rounded-xl border border-gray-200/80 dark:border-gray-600 transition-all cursor-pointer"
                        title="Command + K"
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
                        <span>{{ $t('common.quick_search') }}</span>
                        <kbd
                            class="px-1.5 py-0.5 text-[10px] font-mono bg-white dark:bg-gray-800 text-gray-500 dark:text-gray-400 rounded-md border border-gray-200 dark:border-gray-700 shadow-xs"
                        >
                            ⌘K
                        </kbd>
                    </button>

                    <!-- End Section (Left in RTL / Right in LTR): Utility Actions -->
                    <div class="flex items-center gap-1 sm:gap-1.5 shrink-0">
                        <!-- Mobile Quick Search trigger -->
                        <button
                            type="button"
                            @click="openCommandPalette"
                            class="md:hidden p-1.5 rounded-lg text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
                            :title="$t('common.quick_search')"
                        >
                            <svg
                                class="w-5 h-5 text-gray-500 dark:text-gray-400"
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
                        </button>
                        <!-- Nav Layout Switcher (Top vs Side) -->
                        <button
                            type="button"
                            @click="toggleNavLayout"
                            class="hidden md:flex p-2 rounded-lg text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
                            :title="
                                isTopNav
                                    ? $t('profile.preferences.layout_side')
                                    : $t('profile.preferences.layout_top')
                            "
                        >
                            <svg
                                v-if="isTopNav"
                                class="w-5 h-5"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M4 6h16M4 12h16M4 18h7"
                                />
                            </svg>
                            <svg
                                v-else
                                class="w-5 h-5"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M4 6h16M4 10h16M4 14h16M4 18h16"
                                />
                            </svg>
                        </button>

                        <!-- Language toggle -->
                        <button
                            @click="toggleLocale"
                            class="px-2 py-1 sm:px-3 sm:py-1.5 rounded-lg text-xs sm:text-sm font-semibold text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
                        >
                            <span class="sm:hidden">{{ currentLocale === 'ar' ? 'EN' : 'ع' }}</span>
                            <span class="hidden sm:inline">
                                {{ currentLocale === 'ar' ? 'English' : 'العربية' }}
                            </span>
                        </button>

                        <!-- Theme toggle -->
                        <button
                            @click="toggleThemeHandler"
                            class="p-1.5 sm:p-2 rounded-lg text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
                            :title="$t('theme.toggle')"
                        >
                            <svg
                                v-if="isDark"
                                class="w-5 h-5"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"
                                />
                            </svg>
                            <svg
                                v-else
                                class="w-5 h-5"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"
                                />
                            </svg>
                        </button>

                        <!-- Notification Bell -->
                        <NotificationBell />

                        <!-- User Dropdown -->
                        <div class="relative">
                            <button
                                @click="toggleUserMenu"
                                class="flex items-center gap-2 p-1 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
                            >
                                <img
                                    v-if="
                                        page.props.auth.user.photo_url &&
                                        !page.props.auth.user.photo_url.includes('ui-avatars.com')
                                    "
                                    :src="page.props.auth.user.photo_url"
                                    class="w-7 h-7 sm:w-8 sm:h-8 rounded-full object-cover border border-gray-200 dark:border-gray-700"
                                    alt="User Avatar"
                                />
                                <div
                                    v-else
                                    class="w-7 h-7 sm:w-8 sm:h-8 rounded-full bg-gradient-to-br from-indigo-600 to-purple-600 flex items-center justify-center text-white text-xs sm:text-sm font-bold"
                                >
                                    {{ userInitial }}
                                </div>
                            </button>

                            <!-- Dropdown Menu -->
                            <Transition name="dropdown">
                                <div
                                    v-if="userMenuOpen"
                                    :class="[
                                        'absolute top-full mt-2 w-48 bg-white dark:bg-gray-800 rounded-xl shadow-2xl border border-gray-200 dark:border-gray-700 py-1 z-50',
                                        isRtl ? 'left-0' : 'right-0',
                                    ]"
                                >
                                    <a
                                        href="/app/profile"
                                        class="flex items-center gap-3 px-4 py-2.5 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
                                        @click="userMenuOpen = false"
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
                                                d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"
                                            />
                                        </svg>
                                        {{ $t('nav.profile') }}
                                    </a>

                                    <!-- System Admin Panel Link -->
                                    <a
                                        v-if="isAnyAdmin()"
                                        href="/system"
                                        class="flex items-center gap-3 px-4 py-2.5 text-sm text-indigo-600 dark:text-indigo-400 hover:bg-indigo-50 dark:hover:bg-indigo-900/20 transition-colors"
                                        @click="userMenuOpen = false"
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
                                                d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"
                                            />
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
                                            />
                                        </svg>
                                        {{ $t('common.system') }}
                                    </a>
                                    <div
                                        class="border-t border-gray-200 dark:border-gray-700 my-1"
                                    ></div>
                                    <button
                                        @click="logout"
                                        class="w-full flex items-center gap-3 px-4 py-2.5 text-sm text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors"
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
                                                d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"
                                            />
                                        </svg>
                                        {{ $t('nav.logout') }}
                                    </button>
                                </div>
                            </Transition>
                        </div>
                    </div>
                </header>

                <!-- Horizontal Top Navigation Bar (Active in Top Nav Mode on Desktop) -->
                <nav
                    v-if="isTopNav"
                    class="relative z-40 hidden md:flex items-center gap-1 px-4 py-1.5 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 flex-shrink-0 flex-wrap lg:flex-nowrap"
                    aria-label="Top Navigation"
                >
                    <!-- 1. Dashboard -->
                    <a
                        href="/dashboard"
                        :class="[
                            'flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg text-xs font-medium transition-colors shrink-0',
                            isActive('/dashboard')
                                ? 'bg-indigo-50 dark:bg-indigo-900/50 text-indigo-700 dark:text-indigo-300 font-semibold'
                                : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700',
                        ]"
                    >
                        <svg
                            class="w-4 h-4 flex-shrink-0"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"
                            />
                        </svg>
                        <span>{{ $t('nav.dashboard') }}</span>
                    </a>

                    <!-- 2. Operations / Workshop Dropdown -->
                    <div
                        v-if="
                            can('crm.work_orders.view') ||
                            can('quotes.view') ||
                            can('crm.appointments.manage')
                        "
                        class="relative topnav-dropdown shrink-0"
                    >
                        <button
                            type="button"
                            @click="toggleTopNavDropdown('workshop')"
                            :class="[
                                'flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg text-xs font-medium transition-colors',
                                isAnyActive([
                                    '/app/work-orders',
                                    '/app/workshop',
                                    '/app/quotes',
                                    '/app/appointments',
                                    '/app/communications/appointment-messages',
                                ])
                                    ? 'bg-indigo-50 dark:bg-indigo-900/50 text-indigo-700 dark:text-indigo-300 font-semibold'
                                    : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700',
                            ]"
                        >
                            <svg
                                class="w-4 h-4 flex-shrink-0"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"
                                />
                            </svg>
                            <span>{{ $t('nav.work_orders') }}</span>
                            <span
                                v-if="page.props.auth?.approved_quotes_count > 0"
                                class="px-1.5 py-0.5 text-[10px] font-bold rounded-full bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300"
                            >
                                {{ page.props.auth.approved_quotes_count }}
                            </span>
                            <svg
                                class="w-3.5 h-3.5 transition-transform"
                                :class="{ 'rotate-180': openTopNavDropdown === 'workshop' }"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M19 9l-7 7-7-7"
                                />
                            </svg>
                        </button>

                        <Transition name="dropdown">
                            <div
                                v-if="openTopNavDropdown === 'workshop'"
                                :class="[
                                    'absolute top-full mt-1.5 w-52 bg-white dark:bg-gray-800 rounded-xl shadow-xl border border-gray-100 dark:border-gray-700 py-1.5 z-50',
                                    isRtl ? 'right-0' : 'left-0',
                                ]"
                            >
                                <Link
                                    v-if="can('crm.work_orders.view')"
                                    :href="route('work-orders.index')"
                                    @click="closeTopNavDropdown"
                                    :class="[
                                        'flex items-center gap-2.5 px-3 py-2 text-xs font-medium transition-colors',
                                        isActive('/app/work-orders')
                                            ? 'bg-indigo-50 dark:bg-indigo-900/50 text-indigo-700 dark:text-indigo-300'
                                            : 'text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-750',
                                    ]"
                                >
                                    <svg
                                        class="w-4 h-4 flex-shrink-0"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"
                                        />
                                    </svg>
                                    <span>{{ $t('nav.work_orders') }}</span>
                                </Link>

                                <a
                                    v-if="can('quotes.view')"
                                    href="/app/quotes"
                                    @click="closeTopNavDropdown"
                                    :class="[
                                        'flex items-center justify-between px-3 py-2 text-xs font-medium transition-colors',
                                        isActive('/app/quotes')
                                            ? 'bg-amber-50 dark:bg-amber-900/50 text-amber-700 dark:text-amber-300'
                                            : 'text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-750',
                                    ]"
                                >
                                    <div class="flex items-center gap-2.5">
                                        <svg
                                            class="w-4 h-4 flex-shrink-0"
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
                                        <span>{{ $t('nav.quotes') }}</span>
                                    </div>
                                    <span
                                        v-if="page.props.auth?.approved_quotes_count > 0"
                                        class="px-1.5 py-0.5 text-[10px] font-bold rounded-full bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300"
                                    >
                                        {{ page.props.auth.approved_quotes_count }}
                                    </span>
                                </a>

                                <Link
                                    v-if="can('crm.work_orders.view')"
                                    :href="route('app.workshop.index')"
                                    @click="closeTopNavDropdown"
                                    :class="[
                                        'flex items-center gap-2.5 px-3 py-2 text-xs font-medium transition-colors',
                                        isActive('/app/workshop')
                                            ? 'bg-emerald-50 dark:bg-emerald-900/50 text-emerald-700 dark:text-emerald-300'
                                            : 'text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-750',
                                    ]"
                                >
                                    <svg
                                        class="w-4 h-4 flex-shrink-0"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M4 6h6v6H4V6zm0 10h6v4H4v-4zm10-10h6v4h-6V6zm0 8h6v6h-6v-6z"
                                        />
                                    </svg>
                                    <span>{{ $t('workshop.board.title') }}</span>
                                </Link>

                                <Link
                                    v-if="can('crm.appointments.manage')"
                                    :href="route('appointments.index')"
                                    @click="closeTopNavDropdown"
                                    :class="[
                                        'flex items-center gap-2.5 px-3 py-2 text-xs font-medium transition-colors',
                                        isActive('/app/appointments')
                                            ? 'bg-emerald-50 dark:bg-emerald-900/50 text-emerald-700 dark:text-emerald-300'
                                            : 'text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-750',
                                    ]"
                                >
                                    <svg
                                        class="w-4 h-4 flex-shrink-0"
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
                                    <span>{{ $t('appointments.title') }}</span>
                                </Link>

                                <Link
                                    v-if="can('crm.appointments.manage')"
                                    :href="route('communications.appointment-messages.index')"
                                    @click="closeTopNavDropdown"
                                    :class="[
                                        'flex items-center gap-2.5 px-3 py-2 text-xs font-medium transition-colors',
                                        isActive('/app/communications/appointment-messages')
                                            ? 'bg-sky-50 dark:bg-sky-900/50 text-sky-700 dark:text-sky-300'
                                            : 'text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-750',
                                    ]"
                                >
                                    <svg
                                        class="w-4 h-4 flex-shrink-0"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M8 10h.01M12 10h.01M16 10h.01M21 12c0 4.418-4.03 8-9 8a9.86 9.86 0 01-4.39-1.03L3 20l1.39-3.94A7.96 7.96 0 013 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"
                                        />
                                    </svg>
                                    <span>
                                        {{ $t('communications.appointment_messages.title') }}
                                    </span>
                                </Link>
                            </div>
                        </Transition>
                    </div>

                    <!-- 3. CRM (Customers & Vehicles) Dropdown -->
                    <div
                        v-if="can('crm.customers.view') || can('crm.vehicles.view')"
                        class="relative topnav-dropdown shrink-0"
                    >
                        <button
                            type="button"
                            @click="toggleTopNavDropdown('crm')"
                            :class="[
                                'flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg text-xs font-medium transition-colors',
                                isAnyActive(['/app/customers', '/app/vehicles'])
                                    ? 'bg-indigo-50 dark:bg-indigo-900/50 text-indigo-700 dark:text-indigo-300 font-semibold'
                                    : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700',
                            ]"
                        >
                            <svg
                                class="w-4 h-4 flex-shrink-0"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"
                                />
                            </svg>
                            <span>{{ $t('nav.customers') }}</span>
                            <svg
                                class="w-3.5 h-3.5 transition-transform"
                                :class="{ 'rotate-180': openTopNavDropdown === 'crm' }"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M19 9l-7 7-7-7"
                                />
                            </svg>
                        </button>

                        <Transition name="dropdown">
                            <div
                                v-if="openTopNavDropdown === 'crm'"
                                :class="[
                                    'absolute top-full mt-1.5 w-48 bg-white dark:bg-gray-800 rounded-xl shadow-xl border border-gray-100 dark:border-gray-700 py-1.5 z-50',
                                    isRtl ? 'right-0' : 'left-0',
                                ]"
                            >
                                <a
                                    v-if="can('crm.customers.view')"
                                    href="/app/customers"
                                    @click="closeTopNavDropdown"
                                    :class="[
                                        'flex items-center gap-2.5 px-3 py-2 text-xs font-medium transition-colors',
                                        isActive('/app/customers')
                                            ? 'bg-indigo-50 dark:bg-indigo-900/50 text-indigo-700 dark:text-indigo-300'
                                            : 'text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-750',
                                    ]"
                                >
                                    <svg
                                        class="w-4 h-4 flex-shrink-0"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"
                                        />
                                    </svg>
                                    <span>{{ $t('nav.customers') }}</span>
                                </a>
                                <a
                                    v-if="can('crm.vehicles.view')"
                                    href="/app/vehicles"
                                    @click="closeTopNavDropdown"
                                    :class="[
                                        'flex items-center gap-2.5 px-3 py-2 text-xs font-medium transition-colors',
                                        isActive('/app/vehicles')
                                            ? 'bg-indigo-50 dark:bg-indigo-900/50 text-indigo-700 dark:text-indigo-300'
                                            : 'text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-750',
                                    ]"
                                >
                                    <svg
                                        class="w-4 h-4 flex-shrink-0"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z"
                                        />
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0"
                                        />
                                    </svg>
                                    <span>{{ $t('nav.vehicles') }}</span>
                                </a>
                            </div>
                        </Transition>
                    </div>

                    <!-- 4. Services -->
                    <a
                        v-if="can('services.view')"
                        href="/app/services"
                        :class="[
                            'flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg text-xs font-medium transition-colors shrink-0',
                            isActive('/app/services')
                                ? 'bg-teal-50 dark:bg-teal-900/50 text-teal-700 dark:text-teal-300 font-semibold'
                                : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700',
                        ]"
                    >
                        <svg
                            class="w-4 h-4 flex-shrink-0"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"
                            />
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
                            />
                        </svg>
                        <span>{{ $t('nav.services') }}</span>
                    </a>

                    <!-- 5. Invoices -->
                    <Link
                        v-if="can('invoices.view')"
                        :href="route('app.invoices.hub')"
                        :class="[
                            'flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg text-xs font-medium transition-colors shrink-0',
                            isActive('/app/invoices')
                                ? 'bg-green-50 dark:bg-green-900/50 text-green-700 dark:text-green-300 font-semibold'
                                : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700',
                        ]"
                    >
                        <svg
                            class="w-4 h-4 flex-shrink-0"
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
                        <span>{{ $t('nav.invoices') }}</span>
                    </Link>

                    <!-- 6. Purchasing Dropdown -->
                    <div
                        v-if="can('purchasing.suppliers.view')"
                        class="relative topnav-dropdown shrink-0"
                    >
                        <button
                            type="button"
                            @click="toggleTopNavDropdown('purchasing')"
                            :class="[
                                'flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg text-xs font-medium transition-colors',
                                isAnyActive(['/app/purchasing'])
                                    ? 'bg-violet-50 dark:bg-violet-900/50 text-violet-700 dark:text-violet-300 font-semibold'
                                    : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700',
                            ]"
                        >
                            <svg
                                class="w-4 h-4 flex-shrink-0"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"
                                />
                            </svg>
                            <span>{{ $t('purchasing.title') }}</span>
                            <svg
                                class="w-3.5 h-3.5 transition-transform"
                                :class="{ 'rotate-180': openTopNavDropdown === 'purchasing' }"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M19 9l-7 7-7-7"
                                />
                            </svg>
                        </button>

                        <Transition name="dropdown">
                            <div
                                v-if="openTopNavDropdown === 'purchasing'"
                                :class="[
                                    'absolute top-full mt-1.5 w-48 bg-white dark:bg-gray-800 rounded-xl shadow-xl border border-gray-100 dark:border-gray-700 py-1.5 z-50',
                                    isRtl ? 'right-0' : 'left-0',
                                ]"
                            >
                                <a
                                    href="/app/purchasing"
                                    @click="closeTopNavDropdown"
                                    :class="[
                                        'flex items-center gap-2.5 px-3 py-2 text-xs font-medium transition-colors',
                                        isActive('/app/purchasing') &&
                                        !isActive('/app/purchasing/suppliers')
                                            ? 'bg-violet-50 dark:bg-violet-900/50 text-violet-700 dark:text-violet-300'
                                            : 'text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-750',
                                    ]"
                                >
                                    <svg
                                        class="w-4 h-4 flex-shrink-0"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"
                                        />
                                    </svg>
                                    <span>{{ $t('purchasing.title') }}</span>
                                </a>
                                <a
                                    href="/app/purchasing/suppliers"
                                    @click="closeTopNavDropdown"
                                    :class="[
                                        'flex items-center gap-2.5 px-3 py-2 text-xs font-medium transition-colors',
                                        isActive('/app/purchasing/suppliers')
                                            ? 'bg-violet-50 dark:bg-violet-900/50 text-violet-700 dark:text-violet-300'
                                            : 'text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-750',
                                    ]"
                                >
                                    <svg
                                        class="w-4 h-4 flex-shrink-0"
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
                                    <span>{{ $t('purchasing.suppliers.title') }}</span>
                                </a>
                            </div>
                        </Transition>
                    </div>

                    <!-- 7. Inventory Dropdown -->
                    <div v-if="can('inventory.view')" class="relative topnav-dropdown shrink-0">
                        <button
                            type="button"
                            @click="toggleTopNavDropdown('inventory')"
                            :class="[
                                'flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg text-xs font-medium transition-colors',
                                isAnyActive(['/app/inventory'])
                                    ? 'bg-emerald-50 dark:bg-emerald-900/50 text-emerald-700 dark:text-emerald-300 font-semibold'
                                    : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700',
                            ]"
                        >
                            <svg
                                class="w-4 h-4 flex-shrink-0"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"
                                />
                            </svg>
                            <span>{{ $t('inventory.title') }}</span>
                            <svg
                                class="w-3.5 h-3.5 transition-transform"
                                :class="{ 'rotate-180': openTopNavDropdown === 'inventory' }"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M19 9l-7 7-7-7"
                                />
                            </svg>
                        </button>

                        <Transition name="dropdown">
                            <div
                                v-if="openTopNavDropdown === 'inventory'"
                                :class="[
                                    'absolute top-full mt-1.5 w-48 bg-white dark:bg-gray-800 rounded-xl shadow-xl border border-gray-100 dark:border-gray-700 py-1.5 z-50',
                                    isRtl ? 'right-0' : 'left-0',
                                ]"
                            >
                                <a
                                    href="/app/inventory/parts"
                                    @click="closeTopNavDropdown"
                                    :class="[
                                        'flex items-center gap-2.5 px-3 py-2 text-xs font-medium transition-colors',
                                        isActive('/app/inventory/parts')
                                            ? 'bg-emerald-50 dark:bg-emerald-900/50 text-emerald-700 dark:text-emerald-300'
                                            : 'text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-750',
                                    ]"
                                >
                                    <svg
                                        class="w-4 h-4 flex-shrink-0"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"
                                        />
                                    </svg>
                                    <span>{{ $t('inventory.parts.title') }}</span>
                                </a>
                                <a
                                    v-if="can('inventory.stock.view')"
                                    href="/app/inventory/stock"
                                    @click="closeTopNavDropdown"
                                    :class="[
                                        'flex items-center gap-2.5 px-3 py-2 text-xs font-medium transition-colors',
                                        isActive('/app/inventory/stock')
                                            ? 'bg-emerald-50 dark:bg-emerald-900/50 text-emerald-700 dark:text-emerald-300'
                                            : 'text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-750',
                                    ]"
                                >
                                    <svg
                                        class="w-4 h-4 flex-shrink-0"
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
                                    <span>{{ $t('inventory.stock.title') }}</span>
                                </a>
                                <a
                                    v-if="can('inventory.moves.view')"
                                    href="/app/inventory/moves"
                                    @click="closeTopNavDropdown"
                                    :class="[
                                        'flex items-center gap-2.5 px-3 py-2 text-xs font-medium transition-colors',
                                        isActive('/app/inventory/moves')
                                            ? 'bg-emerald-50 dark:bg-emerald-900/50 text-emerald-700 dark:text-emerald-300'
                                            : 'text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-750',
                                    ]"
                                >
                                    <svg
                                        class="w-4 h-4 flex-shrink-0"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"
                                        />
                                    </svg>
                                    <span>{{ $t('inventory.moves.title') }}</span>
                                </a>
                                <a
                                    v-if="can('inventory.moves.view')"
                                    href="/app/inventory/transfers"
                                    @click="closeTopNavDropdown"
                                    :class="[
                                        'flex items-center gap-2.5 px-3 py-2 text-xs font-medium transition-colors',
                                        isActive('/app/inventory/transfers')
                                            ? 'bg-emerald-50 dark:bg-emerald-900/50 text-emerald-700 dark:text-emerald-300'
                                            : 'text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-750',
                                    ]"
                                >
                                    <svg
                                        class="w-4 h-4 flex-shrink-0"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"
                                        />
                                    </svg>
                                    <span>{{ $t('inventory.transfers.title') }}</span>
                                </a>
                                <a
                                    v-if="can('inventory.settings.manage')"
                                    href="/app/inventory/settings"
                                    @click="closeTopNavDropdown"
                                    :class="[
                                        'flex items-center gap-2.5 px-3 py-2 text-xs font-medium transition-colors',
                                        isActive('/app/inventory/settings')
                                            ? 'bg-emerald-50 dark:bg-emerald-900/50 text-emerald-700 dark:text-emerald-300'
                                            : 'text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-750',
                                    ]"
                                >
                                    <svg
                                        class="w-4 h-4 flex-shrink-0"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"
                                        />
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
                                        />
                                    </svg>
                                    <span>{{ $t('inventory.settings.title') }}</span>
                                </a>
                            </div>
                        </Transition>
                    </div>

                    <!-- 8. HR Dropdown -->
                    <div v-if="can('hr.view')" class="relative topnav-dropdown shrink-0">
                        <button
                            type="button"
                            @click="toggleTopNavDropdown('hr')"
                            :class="[
                                'flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg text-xs font-medium transition-colors',
                                isAnyActive(['/app/hr'])
                                    ? 'bg-violet-50 dark:bg-violet-900/50 text-violet-700 dark:text-violet-300 font-semibold'
                                    : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700',
                            ]"
                        >
                            <svg
                                class="w-4 h-4 flex-shrink-0"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"
                                />
                            </svg>
                            <span>{{ $t('hr.title') }}</span>
                            <svg
                                class="w-3.5 h-3.5 transition-transform"
                                :class="{ 'rotate-180': openTopNavDropdown === 'hr' }"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M19 9l-7 7-7-7"
                                />
                            </svg>
                        </button>

                        <Transition name="dropdown">
                            <div
                                v-if="openTopNavDropdown === 'hr'"
                                :class="[
                                    'absolute top-full mt-1.5 w-48 bg-white dark:bg-gray-800 rounded-xl shadow-xl border border-gray-100 dark:border-gray-700 py-1.5 z-50',
                                    isRtl ? 'right-0' : 'left-0',
                                ]"
                            >
                                <a
                                    href="/app/hr/employees"
                                    @click="closeTopNavDropdown"
                                    :class="[
                                        'flex items-center gap-2.5 px-3 py-2 text-xs font-medium transition-colors',
                                        isActive('/app/hr/employees')
                                            ? 'bg-violet-50 dark:bg-violet-900/50 text-violet-700 dark:text-violet-300'
                                            : 'text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-750',
                                    ]"
                                >
                                    <svg
                                        class="w-4 h-4 flex-shrink-0"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"
                                        />
                                    </svg>
                                    <span>{{ $t('hr.employees.title') }}</span>
                                </a>
                                <a
                                    href="/app/hr/attendance"
                                    @click="closeTopNavDropdown"
                                    :class="[
                                        'flex items-center gap-2.5 px-3 py-2 text-xs font-medium transition-colors',
                                        isActive('/app/hr/attendance')
                                            ? 'bg-violet-50 dark:bg-violet-900/50 text-violet-700 dark:text-violet-300'
                                            : 'text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-750',
                                    ]"
                                >
                                    <svg
                                        class="w-4 h-4 flex-shrink-0"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"
                                        />
                                    </svg>
                                    <span>{{ $t('hr.attendance.title') }}</span>
                                </a>
                                <a
                                    v-if="can('hr.payroll.view')"
                                    href="/app/hr/payroll"
                                    @click="closeTopNavDropdown"
                                    :class="[
                                        'flex items-center gap-2.5 px-3 py-2 text-xs font-medium transition-colors',
                                        isActive('/app/hr/payroll')
                                            ? 'bg-violet-50 dark:bg-violet-900/50 text-violet-700 dark:text-violet-300'
                                            : 'text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-750',
                                    ]"
                                >
                                    <svg
                                        class="w-4 h-4 flex-shrink-0"
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
                                    <span>{{ $t('nav.payroll') }}</span>
                                </a>
                                <a
                                    href="/app/hr/settings"
                                    @click="closeTopNavDropdown"
                                    :class="[
                                        'flex items-center gap-2.5 px-3 py-2 text-xs font-medium transition-colors',
                                        isActive('/app/hr/settings')
                                            ? 'bg-violet-50 dark:bg-violet-900/50 text-violet-700 dark:text-violet-300'
                                            : 'text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-750',
                                    ]"
                                >
                                    <svg
                                        class="w-4 h-4 flex-shrink-0"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"
                                        />
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
                                        />
                                    </svg>
                                    <span>{{ $t('hr.settings.title') }}</span>
                                </a>
                            </div>
                        </Transition>
                    </div>

                    <!-- 9. Reports -->
                    <a
                        href="/app/reports"
                        :class="[
                            'flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg text-xs font-medium transition-colors shrink-0',
                            isActive('/app/reports')
                                ? 'bg-violet-50 dark:bg-violet-900/50 text-violet-700 dark:text-violet-300 font-semibold'
                                : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700',
                        ]"
                    >
                        <svg
                            class="w-4 h-4 flex-shrink-0"
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
                        <span>{{ $t('nav.reports') }}</span>
                    </a>

                    <!-- 10. Integrations -->
                    <a
                        href="/app/settings/integrations"
                        :class="[
                            'flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg text-xs font-medium transition-colors shrink-0',
                            isActive('/app/settings/integrations')
                                ? 'bg-indigo-50 dark:bg-indigo-900/50 text-indigo-700 dark:text-indigo-300 font-semibold'
                                : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700',
                        ]"
                    >
                        <svg
                            class="w-4 h-4 flex-shrink-0"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M11 4a2 2 0 114 0v1a1 1 0 001 1h3a1 1 0 011 1v3a1 1 0 01-1 1h-1a2 2 0 100 4h1a1 1 0 011 1v3a1 1 0 01-1 1h-3a1 1 0 01-1-1v-1a2 2 0 10-4 0v1a1 1 0 01-1 1H7a1 1 0 01-1-1v-3a1 1 0 00-1-1H4a2 2 0 110-4h1a1 1 0 001-1V7a1 1 0 011-1h3a1 1 0 001-1V4z"
                            />
                        </svg>
                        <span>{{ $t('nav.integrations') }}</span>
                    </a>

                    <!-- 11. Employee Portal (if employee) -->
                    <a
                        v-if="hasRole('employee')"
                        href="/app/my"
                        :class="[
                            'flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg text-xs font-medium transition-colors shrink-0',
                            isActive('/app/my')
                                ? 'bg-cyan-50 dark:bg-cyan-900/50 text-cyan-700 dark:text-cyan-300 font-semibold'
                                : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700',
                        ]"
                    >
                        <svg
                            class="w-4 h-4 flex-shrink-0"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0zm6 2a9 9 0 11-18 0 9 9 0 0118 0z"
                            />
                        </svg>
                        <span>{{ $t('nav.employee_portal') }}</span>
                    </a>

                    <!-- 12. Settings -->
                    <a
                        v-if="
                            can('settings.company.manage') ||
                            can('settings.centers.view') ||
                            can('users.view') ||
                            can('crm.vehicles.settings.view')
                        "
                        href="/app/settings"
                        :class="[
                            'flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg text-xs font-medium transition-colors shrink-0',
                            isActive('/app/settings') && !isActive('/app/settings/integrations')
                                ? 'bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-white font-semibold'
                                : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700',
                        ]"
                    >
                        <svg
                            class="w-4 h-4 flex-shrink-0"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"
                            />
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
                            />
                        </svg>
                        <span>{{ $t('nav.settings') }}</span>
                    </a>
                </nav>

                <!-- Subscription/Trial/Grace Period Expiration Notice Banner -->
                <div
                    v-if="showExpirationNotice"
                    class="border-b px-4 py-2.5 text-xs flex items-center justify-center transition-colors flex-shrink-0"
                    :class="
                        isSubscriptionExpired
                            ? 'bg-rose-500/10 dark:bg-rose-500/5 border-rose-500/20 text-rose-800 dark:text-rose-300'
                            : 'bg-amber-500/10 dark:bg-amber-500/5 border-amber-500/20 text-amber-800 dark:text-amber-300'
                    "
                >
                    <div class="flex items-center justify-center gap-2 text-center flex-wrap">
                        <svg
                            class="w-4 h-4 flex-shrink-0 animate-bounce"
                            :class="isSubscriptionExpired ? 'text-rose-500' : 'text-amber-500'"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
                            />
                        </svg>
                        <template v-if="isSubscriptionExpired">
                            <span>
                                {{ $t('system.company_subscription.banners.expired') }}
                                <a
                                    href="/app/settings/billing"
                                    class="font-bold underline text-rose-600 dark:text-rose-400 hover:text-rose-700 transition-colors ms-1"
                                >
                                    {{ $t('system.company_subscription.banners.renew_now') }}
                                </a>
                            </span>
                        </template>
                        <template v-else-if="isInGracePeriod">
                            <span>
                                {{
                                    $t('system.company_subscription.banners.grace_period', {
                                        days: graceDaysRemaining,
                                    })
                                }}
                                <a
                                    href="/app/settings/billing"
                                    class="font-bold underline text-amber-600 dark:text-amber-400 hover:text-amber-700 transition-colors ms-1"
                                >
                                    {{ $t('system.company_subscription.banners.renew_now') }}
                                </a>
                            </span>
                        </template>
                        <template v-else>
                            <span>
                                {{
                                    $t('system.company_subscription.banners.expiring_soon', {
                                        days: subscriptionDaysLeft,
                                    })
                                }}
                                <a
                                    href="/app/settings/billing"
                                    class="font-bold underline text-amber-600 dark:text-amber-400 hover:text-amber-700 transition-colors ms-1"
                                >
                                    {{ $t('system.company_subscription.banners.renew_now') }}
                                </a>
                            </span>
                        </template>
                    </div>
                </div>

                <main class="flex-1 px-4 py-6 overflow-auto print:!overflow-visible">
                    <slot />
                </main>
            </div>
        </div>

        <!-- Mobile Drawer Backdrop -->
        <Transition name="fade">
            <div
                v-if="mobileMenuOpen"
                class="fixed inset-0 bg-black/50 z-40 md:hidden"
                @click="mobileMenuOpen = false"
            ></div>
        </Transition>

        <!-- Mobile Drawer Panel -->
        <Transition :name="isRtl ? 'slide-right' : 'slide-left'">
            <aside
                v-if="mobileMenuOpen"
                :class="[
                    'fixed inset-y-0 w-64 bg-white dark:bg-gray-800 z-50 flex flex-col md:hidden shadow-xl',
                    isRtl ? 'right-0' : 'left-0',
                ]"
            >
                <div
                    class="h-14 flex items-center justify-between px-4 border-b border-gray-200 dark:border-gray-700 flex-shrink-0"
                >
                    <div class="flex items-center gap-3 flex-1 min-w-0">
                        <div
                            class="w-9 h-9 rounded-lg bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 flex items-center justify-center overflow-hidden flex-shrink-0"
                        >
                            <img
                                v-if="page.props.tenant?.logo_url"
                                :src="page.props.tenant.logo_url"
                                :alt="page.props.tenant?.name"
                                class="w-full h-full object-contain"
                            />
                            <svg
                                v-else
                                class="w-5 h-5 text-gray-400"
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
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-bold text-gray-900 dark:text-white truncate">
                                {{
                                    page.props.tenant?.trade_name ||
                                    page.props.tenant?.name ||
                                    $t('common.app_name')
                                }}
                            </p>
                            <!-- Show current center name in mobile header -->
                            <p
                                v-if="page.props.center"
                                class="text-xs text-indigo-600 dark:text-indigo-400 font-medium truncate"
                            >
                                {{ getCenterName(page.props.center) }}
                            </p>
                            <p
                                v-else-if="page.props.tenant?.id"
                                class="text-xs text-gray-500 dark:text-gray-400"
                            >
                                #{{ page.props.tenant.id }}
                            </p>
                        </div>
                    </div>
                    <button
                        @click="mobileMenuOpen = false"
                        class="p-2 rounded-lg text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-700 flex-shrink-0"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M6 18L18 6M6 6l12 12"
                            />
                        </svg>
                    </button>
                </div>

                <!-- Branch Switcher (Mobile) - shown when multiple centers available -->
                <div
                    v-if="page.props.auth.available_centers?.length > 1"
                    class="px-3 py-2 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/80"
                >
                    <p
                        class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2"
                    >
                        {{ $t('common.switch_center') }}
                    </p>
                    <div class="space-y-1">
                        <button
                            v-for="c in page.props.auth.available_centers"
                            :key="c.id"
                            @click="
                                switchCenter(c.id);
                                mobileMenuOpen = false;
                            "
                            :class="[
                                'w-full text-start flex items-center justify-between px-3 py-2 rounded-lg text-sm transition-colors',
                                c.id === page.props.center?.id
                                    ? 'bg-indigo-50 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300 font-semibold'
                                    : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700',
                            ]"
                        >
                            <div class="flex items-center gap-2 min-w-0">
                                <div
                                    :class="[
                                        'w-2 h-2 rounded-full flex-shrink-0',
                                        c.id === page.props.center?.id
                                            ? 'bg-indigo-500'
                                            : 'bg-gray-300 dark:bg-gray-600',
                                    ]"
                                ></div>
                                <span class="truncate">{{ getCenterName(c) }}</span>
                            </div>
                            <svg
                                v-if="c.id === page.props.center?.id"
                                class="w-4 h-4 text-indigo-500 flex-shrink-0"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M5 13l4 4L19 7"
                                />
                            </svg>
                        </button>
                    </div>
                </div>

                <nav class="flex-1 px-3 py-4 space-y-1 overflow-y-auto">
                    <!-- 1. لوحة التحكم -->
                    <a
                        href="/dashboard"
                        @click="mobileMenuOpen = false"
                        :class="[
                            'flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors',
                            isActive('/dashboard')
                                ? 'bg-indigo-50 dark:bg-indigo-900/50 text-indigo-700 dark:text-indigo-300'
                                : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700',
                        ]"
                    >
                        <svg
                            class="w-5 h-5 flex-shrink-0"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"
                            />
                        </svg>
                        {{ $t('nav.dashboard') }}
                    </a>

                    <!-- 2. كروت العمل -->
                    <Link
                        v-if="can('crm.work_orders.view')"
                        :href="route('work-orders.index')"
                        @click="mobileMenuOpen = false"
                        :class="[
                            'flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors',
                            isActive('/app/work-orders')
                                ? 'bg-indigo-50 dark:bg-indigo-900/50 text-indigo-700 dark:text-indigo-300'
                                : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700',
                        ]"
                    >
                        <svg
                            class="w-5 h-5 flex-shrink-0"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"
                            />
                        </svg>
                        {{ $t('nav.work_orders') }}
                    </Link>

                    <!-- 3. التقييمات -->
                    <a
                        v-if="can('quotes.view')"
                        href="/app/quotes"
                        @click="mobileMenuOpen = false"
                        :class="[
                            'flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors',
                            isActive('/app/quotes')
                                ? 'bg-amber-50 dark:bg-amber-900/50 text-amber-700 dark:text-amber-300'
                                : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700',
                        ]"
                    >
                        <svg
                            class="w-5 h-5 flex-shrink-0"
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
                        <span class="flex-1 text-start">{{ $t('nav.quotes') }}</span>
                        <span
                            v-if="page.props.auth?.approved_quotes_count > 0"
                            class="px-2 py-0.5 text-xs font-bold rounded-full bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300"
                        >
                            {{ page.props.auth.approved_quotes_count }}
                        </span>
                    </a>

                    <!-- 4. لوحة المركز -->
                    <Link
                        v-if="can('crm.work_orders.view')"
                        :href="route('app.workshop.index')"
                        @click="mobileMenuOpen = false"
                        :class="[
                            'flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors',
                            isActive('/app/workshop')
                                ? 'bg-emerald-50 dark:bg-emerald-900/50 text-emerald-700 dark:text-emerald-300'
                                : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700',
                        ]"
                    >
                        <svg
                            class="w-5 h-5 flex-shrink-0"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M4 6h6v6H4V6zm0 10h6v4H4v-4zm10-10h6v4h-6V6zm0 8h6v6h-6v-6z"
                            />
                        </svg>
                        {{ $t('workshop.board.title') }}
                    </Link>

                    <!-- 5. العملاء -->
                    <a
                        v-if="can('crm.customers.view')"
                        href="/app/customers"
                        @click="mobileMenuOpen = false"
                        :class="[
                            'flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors',
                            isActive('/app/customers')
                                ? 'bg-indigo-50 dark:bg-indigo-900/50 text-indigo-700 dark:text-indigo-300'
                                : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700',
                        ]"
                    >
                        <svg
                            class="w-5 h-5 flex-shrink-0"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"
                            />
                        </svg>
                        {{ $t('nav.customers') }}
                    </a>

                    <!-- 6. المركبات -->
                    <a
                        v-if="can('crm.vehicles.view')"
                        href="/app/vehicles"
                        @click="mobileMenuOpen = false"
                        :class="[
                            'flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors',
                            isActive('/app/vehicles')
                                ? 'bg-indigo-50 dark:bg-indigo-900/50 text-indigo-700 dark:text-indigo-300'
                                : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700',
                        ]"
                    >
                        <svg
                            class="w-5 h-5 flex-shrink-0"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z"
                            />
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0"
                            />
                        </svg>
                        {{ $t('nav.vehicles') }}
                    </a>

                    <!-- 7. الفواتير -->
                    <Link
                        v-if="can('invoices.view')"
                        :href="route('app.invoices.hub')"
                        @click="mobileMenuOpen = false"
                        :class="[
                            'flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors',
                            isActive('/app/invoices')
                                ? 'bg-green-50 dark:bg-green-900/50 text-green-700 dark:text-green-300'
                                : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700',
                        ]"
                    >
                        <svg
                            class="w-5 h-5 flex-shrink-0"
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
                        {{ $t('nav.invoices') }}
                    </Link>

                    <!-- 8. المواعيد -->
                    <Link
                        v-if="can('crm.appointments.manage')"
                        :href="route('appointments.index')"
                        @click="mobileMenuOpen = false"
                        :class="[
                            'flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors',
                            isActive('/app/appointments')
                                ? 'bg-emerald-50 dark:bg-emerald-900/50 text-emerald-700 dark:text-emerald-300'
                                : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700',
                        ]"
                    >
                        <svg
                            class="w-5 h-5 flex-shrink-0"
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
                        {{ $t('appointments.title') }}
                    </Link>

                    <!-- 9. سجل رسائل المواعيد -->
                    <Link
                        v-if="can('crm.appointments.manage')"
                        :href="route('communications.appointment-messages.index')"
                        :class="[
                            'flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors',
                            isActive('/app/communications/appointment-messages')
                                ? 'bg-sky-50 dark:bg-sky-900/50 text-sky-700 dark:text-sky-300'
                                : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700',
                        ]"
                    >
                        <svg
                            class="w-5 h-5 flex-shrink-0"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M8 10h.01M12 10h.01M16 10h.01M21 12c0 4.418-4.03 8-9 8a9.86 9.86 0 01-4.39-1.03L3 20l1.39-3.94A7.96 7.96 0 013 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"
                            />
                        </svg>
                        {{ $t('communications.appointment_messages.title') }}
                    </Link>

                    <!-- 10. الموردين -->
                    <a
                        v-if="can('purchasing.suppliers.view')"
                        href="/app/purchasing/suppliers"
                        @click="mobileMenuOpen = false"
                        :class="[
                            'flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors',
                            isActive('/app/purchasing/suppliers')
                                ? 'bg-violet-50 dark:bg-violet-900/50 text-violet-700 dark:text-violet-300'
                                : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700',
                        ]"
                    >
                        <svg
                            class="w-5 h-5 flex-shrink-0"
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
                        {{ $t('purchasing.suppliers.title') }}
                    </a>

                    <!-- Divider -->
                    <div class="my-3 border-t border-gray-200 dark:border-gray-700"></div>

                    <a
                        v-if="can('services.view')"
                        href="/app/services"
                        @click="mobileMenuOpen = false"
                        :class="[
                            'flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors',
                            isActive('/app/services')
                                ? 'bg-teal-50 dark:bg-teal-900/50 text-teal-700 dark:text-teal-300'
                                : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700',
                        ]"
                    >
                        <svg
                            class="w-5 h-5 flex-shrink-0"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"
                            />
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
                            />
                        </svg>
                        {{ $t('nav.services') }}
                    </a>

                    <!-- Divider -->
                    <div class="my-3 border-t border-gray-200 dark:border-gray-700"></div>

                    <!-- Inventory Section (Mobile) -->
                    <div v-if="can('inventory.view')">
                        <a
                            href="/app/inventory"
                            @click="inventoryExpanded = !inventoryExpanded"
                            :class="[
                                'w-full flex items-center justify-between gap-2 px-3 py-2 text-xs font-semibold uppercase tracking-wider rounded-lg transition-colors',
                                isActive('/app/inventory')
                                    ? 'bg-emerald-50 dark:bg-emerald-900/50 text-emerald-700 dark:text-emerald-300'
                                    : 'text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700',
                            ]"
                        >
                            <div class="flex items-center gap-2">
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
                                        d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"
                                    />
                                </svg>
                                {{ $t('inventory.title') }}
                            </div>
                            <svg
                                @click.stop.prevent="inventoryExpanded = !inventoryExpanded"
                                :class="[
                                    'w-4 h-4 transition-transform p-0.5 hover:bg-gray-200 dark:hover:bg-gray-600 rounded',
                                    inventoryExpanded ? 'rotate-180' : '',
                                ]"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M19 9l-7 7-7-7"
                                />
                            </svg>
                        </a>

                        <div v-show="inventoryExpanded" class="ps-4 mt-1 space-y-1">
                            <a
                                href="/app/inventory/parts"
                                @click="mobileMenuOpen = false"
                                :class="[
                                    'flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition-colors',
                                    isActive('/app/inventory/parts')
                                        ? 'bg-emerald-50 dark:bg-emerald-900/50 text-emerald-700 dark:text-emerald-300'
                                        : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700',
                                ]"
                            >
                                <svg
                                    class="w-4 h-4 flex-shrink-0"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"
                                    />
                                </svg>
                                {{ $t('inventory.parts.title') }}
                            </a>
                            <a
                                v-if="can('inventory.stock.view')"
                                href="/app/inventory/stock"
                                @click="mobileMenuOpen = false"
                                :class="[
                                    'flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition-colors',
                                    isActive('/app/inventory/stock')
                                        ? 'bg-emerald-50 dark:bg-emerald-900/50 text-emerald-700 dark:text-emerald-300'
                                        : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700',
                                ]"
                            >
                                <svg
                                    class="w-4 h-4 flex-shrink-0"
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
                                {{ $t('inventory.stock.title') }}
                            </a>
                            <a
                                v-if="can('inventory.moves.view')"
                                href="/app/inventory/moves"
                                @click="mobileMenuOpen = false"
                                :class="[
                                    'flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition-colors',
                                    isActive('/app/inventory/moves')
                                        ? 'bg-emerald-50 dark:bg-emerald-900/50 text-emerald-700 dark:text-emerald-300'
                                        : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700',
                                ]"
                            >
                                <svg
                                    class="w-4 h-4 flex-shrink-0"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"
                                    />
                                </svg>
                                {{ $t('inventory.moves.title') }}
                            </a>
                            <a
                                v-if="can('inventory.moves.view')"
                                href="/app/inventory/transfers"
                                @click="mobileMenuOpen = false"
                                :class="[
                                    'flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition-colors',
                                    isActive('/app/inventory/transfers')
                                        ? 'bg-emerald-50 dark:bg-emerald-900/50 text-emerald-700 dark:text-emerald-300'
                                        : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700',
                                ]"
                            >
                                <svg
                                    class="w-4 h-4 flex-shrink-0"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"
                                    />
                                </svg>
                                {{ $t('inventory.transfers.title') }}
                            </a>
                            <a
                                v-if="can('inventory.settings.manage')"
                                href="/app/inventory/settings"
                                @click="mobileMenuOpen = false"
                                :class="[
                                    'flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition-colors',
                                    isActive('/app/inventory/settings')
                                        ? 'bg-emerald-50 dark:bg-emerald-900/50 text-emerald-700 dark:text-emerald-300'
                                        : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700',
                                ]"
                            >
                                <svg
                                    class="w-4 h-4 flex-shrink-0"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"
                                    />
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
                                    />
                                </svg>
                                {{ $t('inventory.settings.title') }}
                            </a>
                        </div>
                    </div>

                    <!-- Purchasing Direct Link (Mobile) -->
                    <a
                        v-if="can('purchasing.suppliers.view')"
                        href="/app/purchasing"
                        @click="mobileMenuOpen = false"
                        :class="[
                            'flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors',
                            isActive('/app/purchasing') && !isActive('/app/purchasing/suppliers')
                                ? 'bg-violet-50 dark:bg-violet-900/50 text-violet-700 dark:text-violet-300'
                                : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700',
                        ]"
                    >
                        <svg
                            class="w-5 h-5 flex-shrink-0"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"
                            />
                        </svg>
                        {{ $t('purchasing.title') }}
                    </a>

                    <!-- Divider -->
                    <div class="my-3 border-t border-gray-200 dark:border-gray-700"></div>

                    <!-- HR Section (Mobile) -->
                    <div>
                        <a
                            href="/app/hr"
                            @click="hrExpanded = !hrExpanded"
                            :class="[
                                'w-full flex items-center justify-between gap-2 px-3 py-2 text-xs font-semibold uppercase tracking-wider rounded-lg transition-colors',
                                isActive('/app/hr')
                                    ? 'bg-violet-50 dark:bg-violet-900/50 text-violet-700 dark:text-violet-300'
                                    : 'text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700',
                            ]"
                        >
                            <div class="flex items-center gap-2">
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
                                        d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"
                                    />
                                </svg>
                                {{ $t('hr.title') }}
                            </div>
                            <svg
                                @click.stop.prevent="hrExpanded = !hrExpanded"
                                :class="[
                                    'w-4 h-4 transition-transform p-0.5 hover:bg-gray-200 dark:hover:bg-gray-600 rounded',
                                    hrExpanded ? 'rotate-180' : '',
                                ]"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M19 9l-7 7-7-7"
                                />
                            </svg>
                        </a>

                        <div v-show="hrExpanded" class="ps-4 mt-1 space-y-1">
                            <a
                                href="/app/hr/employees"
                                @click="mobileMenuOpen = false"
                                :class="[
                                    'flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition-colors',
                                    isActive('/app/hr/employees')
                                        ? 'bg-violet-50 dark:bg-violet-900/50 text-violet-700 dark:text-violet-300'
                                        : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700',
                                ]"
                            >
                                <svg
                                    class="w-4 h-4 flex-shrink-0"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"
                                    />
                                </svg>
                                {{ $t('hr.employees.title') }}
                            </a>
                            <a
                                href="/app/hr/attendance"
                                @click="mobileMenuOpen = false"
                                :class="[
                                    'flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition-colors',
                                    isActive('/app/hr/attendance')
                                        ? 'bg-violet-50 dark:bg-violet-900/50 text-violet-700 dark:text-violet-300'
                                        : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700',
                                ]"
                            >
                                <svg
                                    class="w-4 h-4 flex-shrink-0"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"
                                    />
                                </svg>
                                {{ $t('hr.attendance.title') }}
                            </a>
                            <a
                                v-if="can('hr.payroll.view')"
                                href="/app/hr/payroll"
                                @click="mobileMenuOpen = false"
                                :class="[
                                    'flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors',
                                    isActive('/app/hr/payroll')
                                        ? 'bg-violet-50 dark:bg-violet-900/50 text-violet-700 dark:text-violet-300'
                                        : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700',
                                ]"
                            >
                                <svg
                                    class="w-4 h-4 flex-shrink-0"
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
                                {{ $t('nav.payroll') }}
                            </a>
                            <a
                                href="/app/hr/settings"
                                @click="mobileMenuOpen = false"
                                :class="[
                                    'flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition-colors',
                                    isActive('/app/hr/settings')
                                        ? 'bg-violet-50 dark:bg-violet-900/50 text-violet-700 dark:text-violet-300'
                                        : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700',
                                ]"
                            >
                                <svg
                                    class="w-4 h-4 flex-shrink-0"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"
                                    />
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
                                    />
                                </svg>
                                {{ $t('hr.settings.title') }}
                            </a>
                        </div>
                    </div>

                    <!-- Divider -->
                    <div class="my-3 border-t border-gray-200 dark:border-gray-700"></div>

                    <!-- Reports Link (Mobile) -->
                    <a
                        href="/app/reports"
                        @click="mobileMenuOpen = false"
                        :class="[
                            'flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors',
                            isActive('/app/reports')
                                ? 'bg-violet-50 dark:bg-violet-900/50 text-violet-700 dark:text-violet-300'
                                : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700',
                        ]"
                    >
                        <svg
                            class="w-5 h-5 flex-shrink-0"
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
                        {{ $t('nav.reports') }}
                    </a>

                    <!-- Add-ons Link (Mobile) -->
                    <a
                        href="/app/settings/integrations"
                        @click="mobileMenuOpen = false"
                        :class="[
                            'flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors',
                            isActive('/app/settings/integrations')
                                ? 'bg-indigo-50 dark:bg-indigo-900/50 text-indigo-700 dark:text-indigo-300 font-bold'
                                : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700',
                        ]"
                    >
                        <svg
                            class="w-5 h-5 flex-shrink-0"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M11 4a2 2 0 114 0v1a1 1 0 001 1h3a1 1 0 011 1v3a1 1 0 01-1 1h-1a2 2 0 100 4h1a1 1 0 011 1v3a1 1 0 01-1 1h-3a1 1 0 01-1-1v-1a2 2 0 10-4 0v1a1 1 0 01-1 1H7a1 1 0 01-1-1v-3a1 1 0 00-1-1H4a2 2 0 110-4h1a1 1 0 001-1V7a1 1 0 011-1h3a1 1 0 001-1V4z"
                            />
                        </svg>
                        {{ $t('nav.integrations') }}
                    </a>

                    <!-- Employee Portal Link (Mobile) -->
                    <a
                        v-if="hasRole('employee')"
                        href="/app/my"
                        @click="mobileMenuOpen = false"
                        :class="[
                            'flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors',
                            isActive('/app/my')
                                ? 'bg-cyan-50 dark:bg-cyan-900/50 text-cyan-700 dark:text-cyan-300'
                                : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700',
                        ]"
                    >
                        <svg
                            class="w-5 h-5 flex-shrink-0"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0zm6 2a9 9 0 11-18 0 9 9 0 0118 0z"
                            />
                        </svg>
                        {{ $t('nav.employee_portal') }}
                    </a>

                    <a
                        v-if="
                            can('settings.company.manage') ||
                            can('settings.centers.view') ||
                            can('users.view') ||
                            can('crm.vehicles.settings.view')
                        "
                        href="/app/settings"
                        @click="mobileMenuOpen = false"
                        :class="[
                            'flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors',
                            isActive('/app/settings')
                                ? 'bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-white'
                                : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700',
                        ]"
                    >
                        <svg
                            class="w-5 h-5 flex-shrink-0"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"
                            />
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
                            />
                        </svg>
                        {{ $t('nav.settings') }}
                    </a>
                </nav>
            </aside>
        </Transition>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import { usePage, router, Link } from '@inertiajs/vue3';
import { useTheme } from '@/Composables/useTheme';
import { setLocale, getCurrentLocale } from '@/i18n/index.js';
import Toast from '@/Components/Toast.vue';
import ConfirmModal from '@/Components/ConfirmModal.vue';
import CommandPalette from '@/Components/Common/CommandPalette.vue';
import SystemAnnouncementBanner from '@/Components/SystemAnnouncementBanner.vue';
import NotificationBell from '@/Components/NotificationBell.vue';
import { usePermission } from '@/Composables/usePermission';
import { useToast } from '@/Composables/useToast';

const props = defineProps({
    hideSidebar: { type: Boolean, default: false },
});

const { locale } = useI18n();
const page = usePage();
const { can, hasRole, isAnyAdmin } = usePermission();
const { success: toastSuccess, error: toastError } = useToast();

const commandPaletteRef = ref(null);
const openCommandPalette = () => {
    commandPaletteRef.value?.open();
};

// ─── Global Flash Handler ─────────────────────────────────────────────────────
// Automatically display server flash messages as toasts on every Inertia
// navigation (including redirects from the backend after form submissions).
// We defer by one event-loop turn (setTimeout 0) so that any onSuccess/onFinish
// callbacks fired synchronously by the Inertia router (which update
// lastFrontendToastAt) always run *before* we try to show the flash toast.
// This prevents duplicate toasts when Inertia v2 updates page.props before
// invoking the router's onSuccess callback.
watch(
    () => page.props.flash,
    (flash) => {
        setTimeout(() => {
            if (flash?.success) toastSuccess(flash.success, { fromFlash: true });
            if (flash?.error) toastError(flash.error, { fromFlash: true });
        }, 0);
    },
    { deep: true }
);

const mobileMenuOpen = ref(false);
const userMenuOpen = ref(false);
const centerMenuOpen = ref(false);
const { isDark, toggle: toggleThemeHandler } = useTheme();
const currentLocale = ref('ar');

// Impersonation
const stopImpersonation = () => {
    router.post('/impersonate/stop');
};

// Persist sidebar expanded state in localStorage
const getStoredState = (key, defaultValue) => {
    if (typeof window !== 'undefined') {
        const stored = localStorage.getItem(key);
        return stored !== null ? stored === 'true' : defaultValue;
    }
    return defaultValue;
};

const inventoryExpanded = ref(getStoredState('inventoryExpanded', false));
const purchasingExpanded = ref(getStoredState('purchasingExpanded', false));

const hrExpanded = ref(getStoredState('hrExpanded', false));

// Watch and persist changes
watch(inventoryExpanded, (val) => {
    if (typeof window !== 'undefined') {
        localStorage.setItem('inventoryExpanded', val.toString());
    }
});

watch(purchasingExpanded, (val) => {
    if (typeof window !== 'undefined') {
        localStorage.setItem('purchasingExpanded', val.toString());
    }
});

watch(hrExpanded, (val) => {
    if (typeof window !== 'undefined') {
        localStorage.setItem('hrExpanded', val.toString());
    }
});

const isRtl = computed(() => currentLocale.value === 'ar');
const userInitial = computed(() => {
    const user = page.props?.auth?.user;
    if (user?.name) {
        return user.name.charAt(0).toUpperCase();
    }
    return 'U';
});
const subscriptionLifecycle = computed(() => page.props.tenant?.subscription_lifecycle || null);

const subscriptionDaysLeft = computed(() => {
    const tenant = page.props.tenant;
    if (!tenant) return null;

    if (subscriptionLifecycle.value && subscriptionLifecycle.value.days_remaining !== null) {
        return subscriptionLifecycle.value.days_remaining;
    }

    const dateStr = tenant.subscription_ends_at || tenant.trial_ends_at;
    if (!dateStr) return null;

    const end = new Date(dateStr);
    const now = new Date();
    const diffTime = end - now;
    return Math.ceil(diffTime / (1000 * 60 * 60 * 24));
});

const isInGracePeriod = computed(() => {
    return subscriptionLifecycle.value?.state === 'grace_period';
});

const graceDaysRemaining = computed(() => {
    return subscriptionLifecycle.value?.grace_days_remaining ?? 0;
});

const isSubscriptionExpired = computed(() => {
    const tenant = page.props.tenant;
    if (!tenant) return false;
    if (subscriptionLifecycle.value) {
        return ['expired', 'suspended'].includes(subscriptionLifecycle.value.state);
    }
    if (tenant.status === 'expired') return true;

    const days = subscriptionDaysLeft.value;
    return days !== null && days <= 0;
});

const showExpirationNotice = computed(() => {
    const tenant = page.props.tenant;
    if (!tenant) return false;

    if (isSubscriptionExpired.value || isInGracePeriod.value) return true;

    const days = subscriptionDaysLeft.value;
    if (days === null) return false;

    return days <= 7;
});

watch(
    isRtl,
    (rtl) => {
        document.documentElement.dir = rtl ? 'rtl' : 'ltr';
        document.documentElement.lang = currentLocale.value;
    },
    { immediate: true }
);

// Close user menu and top nav dropdowns when clicking outside
function handleClickOutside(event) {
    if ((userMenuOpen.value || centerMenuOpen.value) && !event.target.closest('.relative')) {
        userMenuOpen.value = false;
        centerMenuOpen.value = false;
    }
    if (openTopNavDropdown.value && !event.target.closest('.topnav-dropdown')) {
        openTopNavDropdown.value = null;
    }
}

function handleKeyDown(event) {
    if (event.key === 'Escape') {
        userMenuOpen.value = false;
        centerMenuOpen.value = false;
        openTopNavDropdown.value = null;
    }
}

// ─── Top Navigation Mode & Preferences ─────────────────────────────────────────
const localNavLayout = ref(
    typeof window !== 'undefined' ? localStorage.getItem('user_nav_layout') : null
);

const layoutMode = computed(() => {
    if (localNavLayout.value) {
        return localNavLayout.value;
    }
    return page.props.preferences?.layout || 'side';
});

const isTopNav = computed(() => layoutMode.value === 'top');

const openTopNavDropdown = ref(null);

function toggleUserMenu() {
    openTopNavDropdown.value = null;
    centerMenuOpen.value = false;
    userMenuOpen.value = !userMenuOpen.value;
}

function toggleCenterMenu() {
    openTopNavDropdown.value = null;
    userMenuOpen.value = false;
    centerMenuOpen.value = !centerMenuOpen.value;
}

function toggleTopNavDropdown(name) {
    userMenuOpen.value = false;
    centerMenuOpen.value = false;
    openTopNavDropdown.value = openTopNavDropdown.value === name ? null : name;
}

function closeTopNavDropdown() {
    openTopNavDropdown.value = null;
}

function isAnyActive(paths) {
    if (typeof window === 'undefined') return false;
    return paths.some((p) => isActive(p));
}

function toggleNavLayout() {
    const next = isTopNav.value ? 'side' : 'top';
    localNavLayout.value = next;
    if (typeof window !== 'undefined') {
        localStorage.setItem('user_nav_layout', next);
    }
    router.post(
        '/app/profile/preferences',
        {
            layout: next,
        },
        {
            preserveScroll: true,
            preserveState: true,
        }
    );
}

watch(
    () => page.props.preferences?.layout,
    (serverLayout) => {
        if (serverLayout && typeof window !== 'undefined') {
            localNavLayout.value = serverLayout;
            localStorage.setItem('user_nav_layout', serverLayout);
        }
    }
);

// Close open dropdowns on Inertia page navigation
let unregisterNavListener = null;

onMounted(() => {
    currentLocale.value = getCurrentLocale();

    document.documentElement.dir = isRtl.value ? 'rtl' : 'ltr';
    document.documentElement.lang = currentLocale.value;

    document.addEventListener('click', handleClickOutside);
    document.addEventListener('keydown', handleKeyDown);

    unregisterNavListener = router.on('navigate', () => {
        openTopNavDropdown.value = null;
        mobileMenuOpen.value = false;
    });
});

onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside);
    document.removeEventListener('keydown', handleKeyDown);
    if (unregisterNavListener) {
        unregisterNavListener();
    }
});

// toggleThemeHandler is provided by useTheme() above (named `toggle`).
// Kept this comment block intentionally short — see useTheme for rationale.

function toggleLocale() {
    const newLocale = currentLocale.value === 'ar' ? 'en' : 'ar';
    router.post(
        route('locale.set'),
        { locale: newLocale },
        {
            onSuccess: () => {
                setLocale(newLocale);
                currentLocale.value = newLocale;
            },
        }
    );
}

function isActive(path) {
    if (typeof window !== 'undefined') {
        return window.location.pathname === path || window.location.pathname.startsWith(path + '/');
    }
    return false;
}

function getCenterName(c) {
    if (!c) return '';
    return isRtl.value
        ? c.name_ar || c.name || c.name_en || c.display_name || ''
        : c.name_en || c.name || c.name_ar || c.display_name || '';
}

function logout() {
    userMenuOpen.value = false;
    router.post('/logout');
}

function switchCenter(centerId) {
    if (page.props.center?.id === centerId) {
        centerMenuOpen.value = false;
        return;
    }
    router.post(
        route('profile.switch-center'),
        {
            center_id: centerId,
        },
        {
            preserveState: false,
            onSuccess: () => (centerMenuOpen.value = false),
        }
    );
}
</script>

<style scoped>
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.2s ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}

.slide-left-enter-active,
.slide-left-leave-active {
    transition: transform 0.2s ease;
}

.slide-left-enter-from,
.slide-left-leave-to {
    transform: translateX(-100%);
}

.slide-right-enter-active,
.slide-right-leave-active {
    transition: transform 0.2s ease;
}

.slide-right-enter-from,
.slide-right-leave-to {
    transform: translateX(100%);
}

.dropdown-enter-active,
.dropdown-leave-active {
    transition:
        opacity 0.15s ease,
        transform 0.15s ease;
}

.dropdown-enter-from,
.dropdown-leave-to {
    opacity: 0;
    transform: translateY(-8px);
}
</style>
