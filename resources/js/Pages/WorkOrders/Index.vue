<template>
    <AppLayout>
        <PrintEngine document-type="report" class="space-y-6">
            <!-- Header Section -->
            <PageHeader
                :title="pageTitle"
                :subtitle="pageSubtitle"
                :totalCount="workOrders ? toEnglish(workOrders.total) : null"
                :countLabel="$t('work_orders.total_count')"
                gradientFrom="from-action-primary"
                gradientTo="to-action-primary-hover"
                glowFrom="from-action-primary/20"
                glowTo="to-action-primary/10"
                badgeBg="bg-action-primary-soft"
                badgeText="text-action-primary"
                badgeBorder="border-action-primary/20"
                badgeDot="bg-action-primary"
            >
                <template #back v-if="statusFilter">
                    <!-- Standard Back Button -->
                    <BackButton :href="route('work-orders.index')" />
                </template>

                <template #icon>
                    <svg
                        v-if="statusFilter === 'closed'"
                        class="w-9 h-9 text-white"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2.5"
                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
                        />
                    </svg>
                    <svg
                        v-else-if="statusFilter === 'open'"
                        class="w-9 h-9 text-white"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2.5"
                            d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"
                        />
                    </svg>
                    <svg
                        v-else
                        class="w-9 h-9 text-white"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2.5"
                            d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"
                        />
                    </svg>
                </template>

                <template #actions v-if="statusFilter">
                    <div class="flex items-center gap-4">
                        <!-- Actions Group -->
                        <div
                            class="flex items-center gap-1.5 p-1.5 bg-surface-muted/60 backdrop-blur-md rounded-2xl border border-border/60 shadow-xs"
                        >
                            <button
                                v-if="can('crm.work_orders.export') || isAnyAdmin()"
                                @click="exportWorkOrders"
                                :disabled="exporting"
                                :title="$t('common.export')"
                                class="p-2.5 text-text-muted hover:text-action-primary hover:bg-surface rounded-xl transition-all shadow-xs hover:shadow-md disabled:opacity-50 cursor-pointer"
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
                                        d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                                    />
                                </svg>
                            </button>
                            <AppPrintButton
                                v-if="can('crm.work_orders.print') || isAnyAdmin()"
                                variant="icon"
                                @click="printWorkOrders"
                            />

                            <div class="w-px h-6 bg-border mx-1"></div>

                            <AppViewModeToggle v-model="viewMode" storage-key="work_orders" />
                        </div>

                        <!-- Create Button -->
                        <button
                            v-if="can('crm.work_orders.create') || isAnyAdmin()"
                            @click="showCreateModal = true"
                            class="flex items-center justify-center gap-3 px-6 py-3 bg-action-primary hover:bg-action-primary-hover text-action-on rounded-2xl font-black shadow-md hover:-translate-y-0.5 transition-all group/add cursor-pointer"
                        >
                            <div
                                class="w-7 h-7 rounded-lg bg-white/20 flex items-center justify-center group-hover/add:rotate-90 transition-transform duration-300"
                            >
                                <svg
                                    class="w-5 h-5 text-action-on"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="3"
                                        d="M12 6v6m0 0v6m0-6h6m-6 0H6"
                                    />
                                </svg>
                            </div>
                            <span class="tracking-tight">{{ $t('work_orders.hub.new_card') }}</span>
                        </button>
                    </div>
                </template>

                <template #filters v-if="statusFilter">
                    <div class="flex flex-col gap-6">
                        <div
                            class="grid grid-cols-2 md:grid-cols-3 lg:flex lg:flex-wrap items-end gap-3"
                        >
                            <!-- Search -->
                            <div class="col-span-2 md:col-span-1 lg:w-48">
                                <div class="relative group">
                                    <div
                                        class="absolute inset-y-0 start-0 flex items-center ps-4 pointer-events-none group-focus-within:text-action-primary transition-colors text-text-muted"
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
                                                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
                                            />
                                        </svg>
                                    </div>
                                    <input
                                        type="text"
                                        v-model="tableFilters.search"
                                        :placeholder="$t('work_orders.search')"
                                        class="block w-full ps-11 pe-4 py-3 text-sm text-text-primary bg-surface border border-border rounded-2xl focus:ring-4 focus:ring-focus-ring/10 focus:border-action-primary transition-all outline-none shadow-xs"
                                    />
                                </div>
                            </div>

                            <!-- Date From -->
                            <div class="lg:w-36">
                                <label
                                    class="block text-[10px] font-bold text-text-muted mb-1 px-1 uppercase tracking-wider"
                                >
                                    {{ $t('common.date_from') }}
                                </label>
                                <CustomDatePicker
                                    v-model="tableFilters.date_from"
                                    @change="() => applyFilters()"
                                    class="!rounded-2xl"
                                />
                            </div>

                            <!-- Date To -->
                            <div class="lg:w-36">
                                <label
                                    class="block text-[10px] font-bold text-text-muted mb-1 px-1 uppercase tracking-wider"
                                >
                                    {{ $t('common.date_to') }}
                                </label>
                                <CustomDatePicker
                                    v-model="tableFilters.date_to"
                                    @change="() => applyFilters()"
                                    class="!rounded-2xl"
                                />
                            </div>

                            <!-- Customer Type Filter -->
                            <div class="lg:w-40">
                                <label
                                    class="block text-[10px] font-bold text-text-muted mb-1 px-1 uppercase tracking-wider"
                                >
                                    {{ $t('work_orders.filters.customer_type') }}
                                </label>
                                <SearchableSelect
                                    v-model="tableFilters.customer_type"
                                    :options="customerTypeOptions"
                                    option-label="label"
                                    option-value="value"
                                    :placeholder="$t('work_orders.filters.all_types')"
                                    :label="''"
                                    @update:modelValue="() => applyFilters()"
                                    class="!rounded-2xl"
                                />
                            </div>

                            <button
                                type="button"
                                @click="resetFilters"
                                class="p-3 text-text-muted hover:text-danger hover:bg-danger-soft rounded-2xl border border-border bg-surface transition-all shadow-xs cursor-pointer"
                                :title="$t('common.reset')"
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
                                        d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"
                                    />
                                </svg>
                            </button>
                        </div>

                        <!-- Filter Tabs (for open and closed status) -->
                        <AppTabs
                            v-if="statusFilter === 'open' || statusFilter === 'closed'"
                            :model-value="currentSubFilter"
                            :tabs="filterTabs"
                            @change="setSubFilter"
                        />
                    </div>
                </template>
            </PageHeader>

            <!-- Dashboard / Quick Access View -->
            <div v-if="!statusFilter" class="space-y-8">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <!-- New Card -->
                    <button
                        @click="showCreateModal = true"
                        class="group relative bg-surface hover:bg-surface-muted/50 rounded-3xl p-8 border-2 border-dashed border-border hover:border-action-primary hover:shadow-xl transition-all duration-300 text-center cursor-pointer"
                    >
                        <div
                            class="w-20 h-20 mx-auto rounded-2xl bg-action-primary text-action-on flex items-center justify-center shadow-lg group-hover:scale-110 transition-all duration-300"
                        >
                            <svg
                                class="w-10 h-10"
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
                        </div>
                        <h3
                            class="mt-6 text-xl font-bold text-text-primary group-hover:text-action-primary transition-colors"
                        >
                            {{ $t('work_orders.hub.new_card') }}
                        </h3>
                        <p class="mt-2 text-sm text-text-muted">
                            {{ $t('work_orders.hub.new_card_hint') }}
                        </p>
                    </button>

                    <!-- Open Cards -->
                    <Link
                        :href="route('work-orders.index', { status: 'open' })"
                        class="group relative bg-surface hover:bg-surface-muted/50 rounded-3xl p-8 border border-border hover:border-success hover:shadow-xl transition-all duration-300 text-center cursor-pointer"
                    >
                        <div
                            class="w-20 h-20 mx-auto rounded-2xl bg-success text-white flex items-center justify-center shadow-lg group-hover:scale-110 transition-all duration-300"
                        >
                            <svg
                                class="w-10 h-10"
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
                        <h3
                            class="mt-6 text-xl font-bold text-text-primary group-hover:text-success transition-colors"
                        >
                            {{ $t('work_orders.hub.open_cards') }}
                        </h3>
                        <p class="mt-2 text-sm text-text-muted">
                            {{ $t('work_orders.hub.open_cards_hint') }}
                        </p>
                        <div
                            v-if="counts?.open?.count > 0"
                            class="absolute top-4 end-4 flex flex-col items-end gap-1"
                        >
                            <span
                                class="px-3 py-1 bg-success text-white text-sm font-black rounded-full shadow-md"
                            >
                                {{ toEnglish(counts.open.count) }}
                            </span>
                            <span
                                class="px-2 py-0.5 bg-surface text-success text-[10px] font-black rounded-lg shadow-xs border border-border"
                            >
                                {{ formatNumber(counts.open.balance || 0) }}
                            </span>
                        </div>
                    </Link>

                    <!-- Closed Cards -->
                    <Link
                        :href="route('work-orders.index', { status: 'closed' })"
                        class="group relative bg-surface hover:bg-surface-muted/50 rounded-3xl p-8 border border-border hover:border-text-secondary hover:shadow-xl transition-all duration-300 text-center cursor-pointer"
                    >
                        <div
                            class="w-20 h-20 mx-auto rounded-2xl bg-text-secondary text-white flex items-center justify-center shadow-lg group-hover:scale-110 transition-all duration-300"
                        >
                            <svg
                                class="w-10 h-10"
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
                        <h3
                            class="mt-6 text-xl font-bold text-text-primary group-hover:text-text-secondary transition-colors"
                        >
                            {{ $t('work_orders.hub.closed_cards') }}
                        </h3>
                        <p class="mt-2 text-sm text-text-muted">
                            {{ $t('work_orders.hub.closed_cards_hint') }}
                        </p>
                        <div
                            v-if="counts?.closed?.count > 0"
                            class="absolute top-4 end-4 flex flex-col items-end gap-1"
                        >
                            <span
                                class="px-3 py-1 bg-text-secondary text-white text-sm font-black rounded-full shadow-md"
                            >
                                {{ toEnglish(counts.closed.count) }}
                            </span>
                            <span
                                class="px-2 py-0.5 bg-surface text-text-secondary text-[10px] font-black rounded-lg shadow-xs border border-border"
                            >
                                {{ formatNumber(counts.closed.balance || 0) }}
                            </span>
                        </div>
                    </Link>
                </div>
            </div>

            <!-- List/Grid View Section (visible only when status filter is active) -->
            <div v-if="statusFilter" class="space-y-6">
                <!-- Grid View -->
                <div v-if="viewMode === 'grid'" class="space-y-6">
                    <!-- Empty State for Grid -->
                    <AppEmptyState
                        v-if="
                            !isLoading &&
                            (!workOrders || !workOrders.data || workOrders.data.length === 0)
                        "
                        :title="$t('work_orders.empty')"
                        :description="$t('work_orders.empty_hint')"
                        :action-text="$t('work_orders.add')"
                        @action="showCreateModal = true"
                    >
                        <template #icon>
                            <svg
                                class="w-8 h-8 text-action-primary"
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

                    <!-- Initial Loading Skeleton for Grid -->
                    <AppTableSkeleton
                        v-else-if="
                            isLoading &&
                            (!workOrders || !workOrders.data || workOrders.data.length === 0)
                        "
                        :rows="8"
                        :cols="4"
                    />

                    <!-- Grid View: Modern Beautiful Cards -->
                    <div
                        v-else
                        class="space-y-6 transition-opacity duration-200"
                        :class="{ 'opacity-60 pointer-events-none': isLoading }"
                    >
                        <div
                            class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-5 gap-4"
                        >
                            <div
                                v-for="order in workOrders.data"
                                :key="order.id"
                                @click="router.visit(route('work-orders.show', order.id))"
                                class="group relative bg-surface rounded-3xl border border-border hover:border-action-primary/50 hover:shadow-xl hover:-translate-y-1.5 cursor-pointer transition-all duration-500 overflow-hidden flex flex-col"
                            >
                                <!-- Top Accent / Status Gradient Line -->
                                <div
                                    class="h-1.5 w-full bg-gradient-to-r"
                                    :class="getStatusGradient(order.status)"
                                ></div>

                                <!-- Card Header: Code & Status -->
                                <div class="px-4 py-3">
                                    <div class="flex items-center justify-between mb-2">
                                        <div class="flex flex-col gap-0.5">
                                            <div class="flex items-center gap-2">
                                                <span
                                                    class="text-[10px] font-black text-text-muted tracking-widest uppercase"
                                                >
                                                    #{{ toEnglish(order.code || order.id) }}
                                                </span>
                                            </div>
                                            <div
                                                v-if="order.entry_date"
                                                class="text-[9px] font-bold text-text-muted"
                                            >
                                                {{ formatDate(order.entry_date) }}
                                            </div>
                                        </div>
                                        <div class="flex items-center gap-1.5">
                                            <div
                                                v-if="
                                                    order.status === 'done' &&
                                                    (order.balance || 0) > 0
                                                "
                                                class="px-2 py-0.5 rounded-full text-[9px] font-bold bg-danger-soft text-danger animate-pulse border border-danger/30"
                                            >
                                                {{ $t('work_orders.pending_payment_badge') }}
                                            </div>
                                            <div
                                                :class="[
                                                    'px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider shadow-xs',
                                                    getStatusClass(order.status),
                                                ]"
                                            >
                                                {{ $t(`work_orders.status.${order.status}`) }}
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Main Vehicle Info Section -->
                                <div class="px-4 py-3 flex-1">
                                    <!-- Saudi Plate Component & Brand Logo -->
                                    <div class="flex items-center justify-center gap-3 mb-4">
                                        <!-- Manufacturer Logo -->
                                        <Tooltip
                                            :text="order.vehicle?.make?.name || ''"
                                            v-if="order.vehicle?.make?.logo_path"
                                        >
                                            <div
                                                class="w-10 h-10 flex items-center justify-center p-1 bg-surface rounded-xl border border-border shadow-xs transition-transform hover:scale-110 cursor-pointer"
                                            >
                                                <img
                                                    :src="`/storage/${order.vehicle.make.logo_path}`"
                                                    class="w-full h-full object-contain filter dark:brightness-110"
                                                    alt="Brand"
                                                />
                                            </div>
                                        </Tooltip>

                                        <!-- Saudi Plate -->
                                        <SaudiPlateDisplay
                                            :plate-number="order.vehicle?.plate_number"
                                            size="sm"
                                        />
                                    </div>

                                    <!-- Outstanding Balance Notice -->
                                    <div
                                        v-if="
                                            order.status === 'done' &&
                                            (order.balance || 0) > 0 &&
                                            (!order.invoice || isInvoiceOverdue(order))
                                        "
                                        class="mb-4 p-2 rounded-xl bg-danger-soft border border-danger/20 text-center shadow-xs"
                                    >
                                        <span class="text-xs font-bold text-danger">
                                            {{ $t('work_orders.remaining_dues_alert') }}:
                                            {{ formatNumber(order.balance || 0) }}
                                        </span>
                                    </div>

                                    <!-- Deferred Invoice Notice -->
                                    <div
                                        v-if="
                                            order.status === 'done' &&
                                            (order.balance || 0) > 0 &&
                                            order.invoice &&
                                            !isInvoiceOverdue(order)
                                        "
                                        class="mb-4 p-2 rounded-xl bg-warning-soft border border-warning/20 text-center shadow-xs"
                                    >
                                        <span class="text-xs font-bold text-warning">
                                            {{ $t('invoices.deferred_invoice') }}:
                                            {{ formatNumber(order.balance || 0) }}
                                        </span>
                                    </div>

                                    <!-- Bad Debt Notice -->
                                    <div
                                        v-if="(order.bad_debt || 0) > 0"
                                        class="mb-4 p-2 rounded-xl bg-warning-soft border border-warning/20 text-center shadow-xs"
                                    >
                                        <span class="text-xs font-bold text-warning">
                                            {{ $t('payments.types.bad_debt') }}:
                                            {{ formatNumber(order.bad_debt || 0) }}
                                        </span>
                                    </div>

                                    <!-- Vehicle Name & Customer -->
                                    <div class="space-y-3">
                                        <div class="text-center">
                                            <div
                                                class="flex flex-col items-center justify-center gap-1 mb-1"
                                            >
                                                <Tooltip :text="getVehicleName(order.vehicle)">
                                                    <h3
                                                        class="text-sm font-black text-text-primary leading-tight group-hover:text-action-primary transition-colors cursor-pointer line-clamp-1"
                                                    >
                                                        {{ getVehicleName(order.vehicle) }}
                                                    </h3>
                                                </Tooltip>
                                                <!-- Vehicle Color Badge -->
                                                <div
                                                    v-if="order.vehicle?.color"
                                                    class="flex items-center gap-1.5 px-2 py-0.5 text-[9px] font-bold bg-surface-muted text-text-secondary rounded-md border border-border cursor-default"
                                                >
                                                    <span
                                                        class="w-2 h-2 rounded-full border border-border shadow-xs"
                                                        :style="{
                                                            backgroundColor: getColorHex(
                                                                order.vehicle.color
                                                            ),
                                                        }"
                                                    ></span>
                                                    {{ order.vehicle.color }}
                                                </div>
                                            </div>
                                            <div class="flex items-center justify-center gap-1.5">
                                                <div
                                                    class="w-4 h-4 rounded-full bg-surface-muted flex items-center justify-center"
                                                >
                                                    <svg
                                                        class="w-2.5 h-2.5 text-text-muted"
                                                        fill="currentColor"
                                                        viewBox="0 0 20 20"
                                                    >
                                                        <path
                                                            d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z"
                                                        />
                                                    </svg>
                                                </div>
                                                <Tooltip :text="$t('common.customer')">
                                                    <span
                                                        class="text-xs font-bold text-text-secondary truncate max-w-[120px] cursor-pointer"
                                                    >
                                                        {{
                                                            order.customer?.name ||
                                                            $t('common.unknown')
                                                        }}
                                                    </span>
                                                </Tooltip>
                                            </div>
                                        </div>

                                        <!-- Financial Metrics Grid -->
                                        <div
                                            class="grid grid-cols-3 gap-1 py-2 border-y border-border/60"
                                        >
                                            <!-- Total -->
                                            <Tooltip
                                                :text="$t('work_orders.invoice_total')"
                                                class="flex-1"
                                            >
                                                <div
                                                    class="group/stat flex flex-col items-center justify-center border-e border-border/60 cursor-pointer transition-colors hover:bg-surface-muted/60 rounded-lg py-1 w-full h-full"
                                                >
                                                    <span
                                                        class="text-[9px] font-bold text-text-muted uppercase tracking-tighter"
                                                    >
                                                        {{ $t('work_orders.total') }}
                                                    </span>
                                                    <span
                                                        class="text-[11px] font-black text-text-primary"
                                                    >
                                                        {{ formatNumber(order.total || 0) }}
                                                    </span>
                                                </div>
                                            </Tooltip>
                                            <!-- Paid -->
                                            <Tooltip :text="$t('work_orders.paid')" class="flex-1">
                                                <div
                                                    class="group/stat flex flex-col items-center justify-center border-e border-border/60 cursor-pointer transition-colors hover:bg-surface-muted/60 rounded-lg py-1 w-full h-full"
                                                >
                                                    <span
                                                        class="text-[9px] font-bold text-text-muted uppercase tracking-tighter"
                                                    >
                                                        {{ $t('work_orders.paid') }}
                                                    </span>
                                                    <span
                                                        class="text-[11px] font-black text-success"
                                                    >
                                                        {{
                                                            formatNumber(
                                                                (order.total_paid || 0) -
                                                                    (order.bad_debt || 0)
                                                            )
                                                        }}
                                                    </span>
                                                </div>
                                            </Tooltip>
                                            <!-- Balance -->
                                            <Tooltip
                                                :text="$t('work_orders.remaining')"
                                                class="flex-1"
                                            >
                                                <div
                                                    class="group/stat flex flex-col items-center justify-center cursor-pointer transition-colors hover:bg-surface-muted/60 rounded-lg py-1 w-full h-full"
                                                >
                                                    <span
                                                        class="text-[9px] font-bold text-text-muted uppercase tracking-tighter"
                                                    >
                                                        {{ $t('work_orders.remaining') }}
                                                    </span>
                                                    <span
                                                        class="text-[11px] font-black"
                                                        :class="
                                                            (order.balance || 0) > 0
                                                                ? 'text-danger'
                                                                : 'text-text-muted'
                                                        "
                                                    >
                                                        {{ formatNumber(order.balance || 0) }}
                                                    </span>
                                                </div>
                                            </Tooltip>
                                        </div>
                                    </div>
                                </div>

                                <!-- Footer: Status/Date -->
                                <div
                                    class="px-5 py-4 bg-surface-muted/50 border-t border-border flex items-center justify-between mt-auto"
                                >
                                    <span
                                        class="text-[10px] font-black text-text-muted uppercase tracking-widest"
                                    >
                                        {{ getDateLabel(order) }}
                                    </span>
                                    <div
                                        class="flex items-center gap-1.5 px-2.5 py-1 text-[11px] font-bold rounded-lg border shadow-xs"
                                        :class="getDateClass(order)"
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
                                                d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"
                                            />
                                        </svg>
                                        {{ formatDate(getDisplayDate(order)) }}
                                    </div>
                                </div>

                                <!-- View Details Arrow (Hover Only) -->
                                <div
                                    class="absolute top-1/2 -end-4 group-hover:end-4 opacity-0 group-hover:opacity-100 transition-all duration-500"
                                    @click.stop
                                >
                                    <div
                                        class="w-10 h-10 rounded-2xl bg-action-primary text-action-on flex items-center justify-center shadow-lg"
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
                                                stroke-width="2.5"
                                                d="M9 5l7 7-7 7"
                                            />
                                        </svg>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Pagination for Grid View -->
                        <div v-if="workOrders?.links?.length > 3" class="flex justify-center">
                            <AppPagination
                                :links="workOrders.links"
                                :from="workOrders.from"
                                :to="workOrders.to"
                                :total="workOrders.total"
                            />
                        </div>
                    </div>
                </div>

                <!-- Table View via Unified DataTable -->
                <DataTable
                    v-else
                    :columns="columns"
                    :data="workOrders"
                    :loading="isLoading"
                    :empty-title="$t('work_orders.empty')"
                    :empty-description="$t('work_orders.empty_hint')"
                    :empty-action-text="$t('work_orders.add')"
                    @emptyAction="showCreateModal = true"
                    @rowClick="(row) => router.visit(route('work-orders.show', row.id))"
                >
                    <!-- Index Cell -->
                    <template #cell-index="{ index }">
                        <span class="text-xs font-semibold text-text-muted">
                            {{ toEnglish(index + 1) }}
                        </span>
                    </template>

                    <!-- Code Cell -->
                    <template #cell-code="{ item }">
                        <div class="flex flex-col gap-1">
                            <span class="font-mono font-bold text-action-primary">
                                #{{ toEnglish(item.code || item.id) }}
                            </span>
                            <div class="flex flex-wrap gap-1">
                                <span
                                    :class="[
                                        'px-1.5 py-0.5 rounded text-[9px] font-black uppercase tracking-wider',
                                        getStatusClass(item.status),
                                    ]"
                                >
                                    {{ $t(`work_orders.status.${item.status}`) }}
                                </span>
                                <span
                                    v-if="
                                        item.status === 'done' &&
                                        (item.balance || 0) > 0 &&
                                        (!item.invoice || isInvoiceOverdue(item))
                                    "
                                    class="px-1.5 py-0.5 rounded text-[9px] font-bold bg-danger-soft text-danger"
                                >
                                    {{ $t('work_orders.pending_payment_badge') }}
                                </span>
                                <span
                                    v-if="
                                        item.status === 'done' &&
                                        (item.balance || 0) > 0 &&
                                        item.invoice &&
                                        !isInvoiceOverdue(item)
                                    "
                                    class="px-1.5 py-0.5 rounded text-[9px] font-bold bg-warning-soft text-warning"
                                >
                                    {{ $t('invoices.deferred_invoice') }}
                                </span>
                                <span
                                    v-if="(item.bad_debt || 0) > 0"
                                    class="px-1.5 py-0.5 rounded text-[9px] font-bold bg-warning-soft text-warning"
                                >
                                    {{ $t('payments.types.bad_debt') }}
                                </span>
                            </div>
                        </div>
                    </template>

                    <!-- Expected End Date Cell -->
                    <template #cell-expected_end_date="{ item }">
                        <div class="flex items-center gap-1.5">
                            <span
                                class="inline-flex items-center gap-1.5 px-2 py-1 text-xs font-medium rounded-lg border"
                                :class="getDateClass(item)"
                            >
                                <svg
                                    v-if="isOverdue(item)"
                                    class="w-3.5 h-3.5"
                                    fill="currentColor"
                                    viewBox="0 0 20 20"
                                >
                                    <path
                                        fill-rule="evenodd"
                                        d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"
                                        clip-rule="evenodd"
                                    />
                                </svg>
                                {{ formatDate(item.expected_end_date) }}
                            </span>
                        </div>
                    </template>

                    <!-- Vehicle Cell -->
                    <template #cell-vehicle="{ item }">
                        <div class="flex items-center gap-2">
                            <SaudiPlateDisplay
                                :plate-number="item.vehicle?.plate_number"
                                size="sm"
                            />
                            <span class="text-xs text-text-secondary truncate max-w-[120px]">
                                {{ getVehicleName(item.vehicle) }}
                            </span>
                        </div>
                    </template>

                    <!-- Contact Name Cell -->
                    <template #cell-contact_name="{ item }">
                        <span class="text-sm text-text-primary">
                            {{ item.contact_name || item.customer?.name || '-' }}
                        </span>
                    </template>

                    <!-- Services Cell -->
                    <template #cell-services="{ item }">
                        <span
                            class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-action-primary-soft text-action-primary text-sm font-bold"
                        >
                            {{
                                toEnglish(item.items_count || (item.items ? item.items.length : 0))
                            }}
                        </span>
                    </template>

                    <!-- Total Cell -->
                    <template #cell-total="{ item }">
                        <span class="font-mono font-semibold text-text-primary">
                            {{ formatNumber(item.total || 0) }}
                        </span>
                    </template>

                    <!-- Paid Cell -->
                    <template #cell-paid="{ item }">
                        <span class="font-mono font-medium text-success">
                            {{ formatNumber((item.total_paid || 0) - (item.bad_debt || 0)) }}
                        </span>
                    </template>

                    <!-- Balance Cell -->
                    <template #cell-balance="{ item }">
                        <span class="font-mono font-bold" :class="getBalanceColor(item)">
                            {{ formatNumber(item.balance || 0) }}
                        </span>
                    </template>
                </DataTable>
            </div>
        </PrintEngine>

        <!-- Create Modal -->
        <WorkOrderFormModal
            :show="showCreateModal"
            :customers="customers"
            :departments="departments"
            :makes="makes"
            :colors="colors"
            :modelsByMake="modelsByMake"
            @close="showCreateModal = false"
            @saved="handleSaved"
        />

        <!-- Print Section (Teleported to body for printing) -->
        <Teleport to="body">
            <div class="print-section hidden">
                <StandardReportHeader
                    :title="
                        statusFilter
                            ? $t('work_orders.list.' + statusFilter + '_title')
                            : $t('work_orders.title')
                    "
                />

                <!-- Table -->
                <DataTable structured class="print-table">
                    <thead>
                        <tr>
                            <th class="align-middle text-center">#</th>
                            <th class="align-middle text-center">
                                {{ $t('work_orders.columns.code') }}
                            </th>
                            <th class="align-middle text-center">{{ $t('common.date') }}</th>
                            <th class="align-middle text-center">
                                {{ $t('work_orders.columns.vehicle') }}
                            </th>
                            <th class="align-middle text-center">
                                {{ $t('work_orders.columns.customer') }}
                            </th>
                            <th class="align-middle text-center">
                                {{ $t('work_orders.columns.status') }}
                            </th>
                            <th class="align-middle text-center">
                                {{ $t('work_orders.columns.total') }}
                            </th>
                            <th class="align-middle text-center">
                                {{ $t('work_orders.columns.paid') }}
                            </th>
                            <th class="align-middle text-center">
                                {{ $t('work_orders.columns.balance') }}
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(order, index) in workOrders?.data || []" :key="order.id">
                            <td class="align-middle">{{ toEnglish(index + 1) }}</td>
                            <td class="align-middle">{{ toEnglish(order.code) }}</td>
                            <td class="align-middle">{{ formatDate(order.entry_date) }}</td>
                            <td dir="ltr" class="text-left font-sans align-middle">
                                {{ toEnglish(order.vehicle?.plate_number) || '-' }}
                            </td>
                            <td class="align-middle">{{ order.customer?.name || '-' }}</td>
                            <td class="align-middle">
                                <span class="print-badge">
                                    {{ $t(`work_orders.status.${order.status}`) }}
                                </span>
                            </td>
                            <td class="align-middle">{{ formatNumber(order.total || 0) }}</td>
                            <td class="align-middle">{{ formatNumber(order.paid_amount || 0) }}</td>
                            <td class="align-middle">
                                {{ formatNumber((order.total || 0) - (order.paid_amount || 0)) }}
                            </td>
                        </tr>
                    </tbody>
                </DataTable>

                <StandardReportFooter class="mt-8 border-t pt-4" />
            </div>
        </Teleport>
    </AppLayout>
</template>

<script setup>
import AppTabs from '@/Components/Common/AppTabs.vue';
import PrintEngine from '@/Components/Print/PrintEngine.vue';
import { printDocument } from '@/Composables/usePrint';
import { ref, computed, watch, h } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import BackButton from '@/Components/BackButton.vue';
import AppPrintButton from '@/Components/Common/AppPrintButton.vue';
import AppViewModeToggle from '@/Components/Common/AppViewModeToggle.vue';
import AppEmptyState from '@/Components/Common/AppEmptyState.vue';
import AppTableSkeleton from '@/Components/Common/AppTableSkeleton.vue';
import AppPagination from '@/Components/Common/AppPagination.vue';
import DataTable from '@/Components/Common/DataTable.vue';
import WorkOrderFormModal from '@/Components/WorkOrders/WorkOrderFormModal.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import CustomDatePicker from '@/Components/CustomDatePicker.vue';
import Tooltip from '@/Components/Tooltip.vue';
import StandardReportHeader from '@/Components/Print/StandardReportHeader.vue';
import StandardReportFooter from '@/Components/Print/StandardReportFooter.vue';
import SaudiPlateDisplay from '@/Components/Vehicles/SaudiPlateDisplay.vue';
import { useToast } from '@/Composables/useToast';
import { useNumberFormat } from '@/Composables/useNumberFormat';
import { usePermission } from '@/Composables/usePermission';
import { useServerTable } from '@/Composables/useServerTable';

const { t } = useI18n();
const { success } = useToast();
const { toEnglish, formatNumber } = useNumberFormat();
const { can, isAnyAdmin } = usePermission();

const props = defineProps({
    workOrders: {
        type: Object,
        default: null,
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
    departments: {
        type: Array,
        default: () => [],
    },
    services: {
        type: Object,
        default: () => ({}),
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
    statusFilter: {
        type: String,
        default: null,
    },
    subFilter: {
        type: String,
        default: null,
    },
    filterCounts: {
        type: Object,
        default: () => ({}),
    },
    counts: {
        type: Object,
        default: () => ({ open: 0, closed: 0, completed: 0 }),
    },
});

const pageTitle = computed(() => {
    if (!props.statusFilter) return t('work_orders.hub.title');
    return props.statusFilter === 'closed'
        ? t('work_orders.list.closed_title')
        : t('work_orders.list.open_title');
});

const pageSubtitle = computed(() => {
    if (!props.statusFilter) return t('work_orders.hub.subtitle');
    return props.statusFilter === 'closed'
        ? t('work_orders.list.closed_subtitle')
        : t('work_orders.list.open_subtitle');
});

// View mode & modals
const showCreateModal = ref(false);
const viewMode = ref(localStorage.getItem('workOrdersViewMode') || 'grid');
const exporting = ref(false);

watch(viewMode, (newMode) => {
    localStorage.setItem('workOrdersViewMode', newMode);
});

// Server table composable — unified reactive filters and debounced search
const {
    filters: tableFilters,
    isLoading,
    applyFilters,
    cancelDebouncedSearch,
} = useServerTable({
    routeUrl: '/app/work-orders',
    initialFilters: {
        status: props.statusFilter || '',
        sub_filter: props.subFilter || '',
        search: props.filters?.search || '',
        date_from: props.filters?.date_from || '',
        date_to: props.filters?.date_to || '',
        customer_type: props.filters?.customer_type || '',
    },
    debounceMs: 350,
});

// Synchronize status, sub_filter, and filters when Inertia page props change
watch(
    () => props.statusFilter,
    (newStatus) => {
        tableFilters.status = newStatus || '';
    }
);
watch(
    () => props.subFilter,
    (newSub) => {
        tableFilters.sub_filter = newSub || '';
    }
);
watch(
    () => props.filters,
    (newFilters) => {
        if (newFilters) {
            if (newFilters.search !== undefined && newFilters.search !== tableFilters.search) {
                tableFilters.search = newFilters.search || '';
            }
            if (
                newFilters.date_from !== undefined &&
                newFilters.date_from !== tableFilters.date_from
            ) {
                tableFilters.date_from = newFilters.date_from || '';
            }
            if (newFilters.date_to !== undefined && newFilters.date_to !== tableFilters.date_to) {
                tableFilters.date_to = newFilters.date_to || '';
            }
            if (
                newFilters.customer_type !== undefined &&
                newFilters.customer_type !== tableFilters.customer_type
            ) {
                tableFilters.customer_type = newFilters.customer_type || '';
            }
        }
    },
    { deep: true }
);

// Customer type options for dropdown
const customerTypeOptions = computed(() => [
    { value: '', label: t('work_orders.filters.all_types') },
    { value: 'individual', label: t('customers.type.individual') },
    { value: 'company', label: t('customers.type.company') },
]);

// Icons for filter tabs (Render functions for Vue 3 SFC compatibility)
const createIcon =
    (d, strokeWidth = 2) =>
    (props, { attrs }) =>
        h(
            'svg',
            {
                fill: 'none',
                stroke: 'currentColor',
                viewBox: '0 0 24 24',
                ...attrs,
            },
            [
                h('path', {
                    'stroke-linecap': 'round',
                    'stroke-linejoin': 'round',
                    'stroke-width': strokeWidth,
                    d,
                }),
            ]
        );

const IconOpen = createIcon(
    'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2'
);
const IconDraft = createIcon(
    'M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z'
);
const IconOverdue = createIcon('M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z');
const IconPending = createIcon(
    'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z'
);
const IconDone = createIcon('M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z', 2.5);
const IconBadDebt = createIcon(
    'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z'
);
const IconAll = createIcon('M4 6h16M4 10h16M4 14h16M4 18h16');

const filterTabs = computed(() => {
    if (props.statusFilter === 'closed') {
        return [
            {
                id: 'all',
                label: t('work_orders.filters.all_cards'),
                icon: IconAll,
                count: props.filterCounts.all || 0,
            },
            {
                id: 'closed',
                label: t('work_orders.filters.closed_cards'),
                icon: IconDone,
                count: props.filterCounts.closed || 0,
            },
            {
                id: 'credit_invoices',
                label: t('work_orders.filters.credit_invoices'),
                icon: IconPending,

                count: props.filterCounts.credit_invoices || 0,
            },
            {
                id: 'bad_debts',
                label: t('work_orders.filters.bad_debts'),
                icon: IconBadDebt,

                count: props.filterCounts.bad_debts || 0,
            },
            {
                id: 'cancelled',
                label: t('work_orders.filters.cancelled_cards'),
                icon: IconDraft,

                count: props.filterCounts.cancelled || 0,
            },
        ];
    }

    return [
        {
            id: 'in_progress',
            label: t('work_orders.filters.open'),
            icon: IconOpen,

            count: props.filterCounts.open || 0,
        },
        {
            id: 'draft',
            label: t('work_orders.filters.draft'),
            icon: IconDraft,

            count: props.filterCounts.draft || 0,
        },
        {
            id: 'overdue',
            label: t('work_orders.filters.overdue'),
            icon: IconOverdue,

            count: props.filterCounts.overdue || 0,
        },
        {
            id: 'pending_payment',
            label: t('work_orders.filters.pending_payment'),
            icon: IconPending,

            count: props.filterCounts.pending_payment || 0,
        },
        {
            id: 'completed',
            label: t('work_orders.filters.completed'),
            icon: IconDone,

            count: props.filterCounts.completed || 0,
        },
    ];
});

const currentSubFilter = computed(() => {
    if (tableFilters.sub_filter) return tableFilters.sub_filter;
    if (props.subFilter) return props.subFilter;
    return props.statusFilter === 'closed' ? 'all' : 'in_progress';
});

function setSubFilter(filter) {
    cancelDebouncedSearch?.();
    tableFilters.status = props.statusFilter || tableFilters.status || '';
    tableFilters.sub_filter = filter;
    applyFilters();
}

const resetFilters = () => {
    cancelDebouncedSearch?.();
    tableFilters.status = props.statusFilter || tableFilters.status || '';
    tableFilters.search = '';
    tableFilters.date_from = '';
    tableFilters.date_to = '';
    tableFilters.customer_type = '';
    applyFilters();
};

// DataTable Columns Definition
const columns = computed(() => [
    { key: 'index', label: '#', align: 'center', width: 'w-12' },
    { key: 'code', label: t('work_orders.columns.code'), align: 'start' },
    { key: 'expected_end_date', label: t('work_orders.columns.expected_end'), align: 'start' },
    { key: 'vehicle', label: t('work_orders.columns.vehicle'), align: 'start' },
    { key: 'contact_name', label: t('work_orders.columns.contact_name'), align: 'start' },
    { key: 'services', label: t('work_orders.columns.services'), align: 'center' },
    { key: 'total', label: t('work_orders.columns.total'), align: 'end' },
    { key: 'paid', label: t('work_orders.columns.paid'), align: 'end' },
    { key: 'balance', label: t('work_orders.columns.balance'), align: 'end' },
]);

// Print & Export
function printWorkOrders() {
    printDocument();
}

async function exportWorkOrders() {
    exporting.value = true;
    try {
        const params = new URLSearchParams();
        const activeStatus = tableFilters.status || props.statusFilter;
        const activeSubFilter = tableFilters.sub_filter || props.subFilter;
        if (activeStatus) params.append('status', activeStatus);
        if (activeSubFilter) params.append('sub_filter', activeSubFilter);
        if (tableFilters.search) params.append('search', tableFilters.search);
        if (tableFilters.date_from) params.append('date_from', tableFilters.date_from);
        if (tableFilters.date_to) params.append('date_to', tableFilters.date_to);
        if (tableFilters.customer_type) params.append('customer_type', tableFilters.customer_type);

        window.location.href = `${route('work-orders.export')}?${params.toString()}`;
    } finally {
        setTimeout(() => {
            exporting.value = false;
        }, 2000);
    }
}

// Helpers
function getStatusClass(status) {
    const classes = {
        draft: 'bg-surface-muted text-text-muted border border-border',
        open: 'bg-info-soft text-info border border-info/20',
        in_progress: 'bg-action-primary-soft text-action-primary border border-action-primary/20',
        done: 'bg-success-soft text-success border border-success/20',
        cancelled: 'bg-danger-soft text-danger border border-danger/20',
    };
    return classes[status] || classes.draft;
}

function getVehicleName(vehicle) {
    if (!vehicle) return '';
    if (vehicle.make?.name) {
        return `${vehicle.make.name} ${vehicle.model?.name || ''}`.trim();
    }
    return vehicle.make_other || '';
}

function formatDate(dateString) {
    if (!dateString) return '-';
    return new Date(dateString).toLocaleDateString('en-GB', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        numberingSystem: 'latn',
    });
}

function isOverdue(order) {
    if (!order.expected_end_date) return false;
    if (['done', 'cancelled'].includes(order.status)) return false;
    const dueDate = new Date(order.expected_end_date);
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    return dueDate < today;
}

function isInvoiceOverdue(order) {
    if (!order.invoice?.due_date) return false;
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    const dueDate = new Date(order.invoice.due_date.split('T')[0]);
    dueDate.setHours(0, 0, 0, 0);
    return today >= dueDate;
}

function getDisplayDate(order) {
    if (['done', 'cancelled'].includes(order.status)) {
        return order.closed_at || order.updated_at;
    }
    return order.expected_end_date || order.entry_date;
}

function getDateLabel(order) {
    if (['done', 'cancelled'].includes(order.status)) {
        return t('work_orders.closed_date');
    }
    return t('work_orders.due_date');
}

function getDateClass(order) {
    if (['done', 'cancelled'].includes(order.status)) {
        return 'bg-surface-muted text-text-muted border-border';
    }
    if (isOverdue(order)) {
        return 'bg-danger-soft text-danger border-danger/30';
    }
    return 'bg-info-soft text-info border-info/30';
}

function getColorHex(colorName) {
    if (!colorName) return '#e2e8f0';
    const colors = {
        أبيض: '#ffffff',
        White: '#ffffff',
        أسود: '#000000',
        Black: '#000000',
        فضي: '#c0c0c0',
        Silver: '#c0c0c0',
        رمادي: '#808080',
        Gray: '#808080',
        أحمر: '#ff0000',
        Red: '#ff0000',
        أزرق: '#0000ff',
        Blue: '#0000ff',
        أصفر: '#ffff00',
        Yellow: '#ffff00',
        أخضر: '#008000',
        Green: '#008000',
    };
    return colors[colorName] || '#e2e8f0';
}

function getStatusGradient(status) {
    const classes = {
        draft: 'from-surface-muted to-border',
        open: 'from-info to-info/70',
        in_progress: 'from-action-primary to-action-primary-hover',
        done: 'from-success to-success/70',
        cancelled: 'from-danger to-danger/70',
    };
    return classes[status] || classes.draft;
}

function getBalance(order) {
    return (parseFloat(order.total) || 0) - (parseFloat(order.paid_amount) || 0);
}

function getBalanceColor(order) {
    const balance = getBalance(order);
    if (balance > 0) return 'text-danger';
    if (balance < 0) return 'text-warning';
    return 'text-success';
}

function handleSaved() {
    showCreateModal.value = false;
    success(t('common.saved_success'));
    router.reload({ only: ['workOrders'] });
}
</script>
