<template>
    <AppLayout>
        <PrintEngine document-type="report" class="space-y-6">
            <!-- Header -->
            <PageHeader
                :title="$t('purchasing.suppliers.title')"
                :subtitle="$t('purchasing.suppliers.subtitle')"
                :totalCount="suppliers ? toEnglish(suppliers.total) : null"
                :countLabel="$t('purchasing.suppliers.title')"
            >
                <template #icon>
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"
                        />
                    </svg>
                </template>

                <template #actions>
                    <div
                        class="flex items-center gap-1.5 p-1.5 bg-surface-secondary/70 backdrop-blur-md rounded-2xl border border-border-default shadow-inner"
                    >
                        <Tooltip :text="$t('common.import')">
                            <button
                                @click="showImportModal = true"
                                class="p-2.5 text-content-secondary hover:text-action-primary hover:bg-surface-primary rounded-xl transition-all shadow-sm hover:shadow-md"
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
                                        d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"
                                    />
                                </svg>
                            </button>
                        </Tooltip>

                        <Tooltip :text="$t('common.export')">
                            <button
                                @click="exportSuppliers"
                                :disabled="exporting"
                                class="p-2.5 text-content-secondary hover:text-action-primary hover:bg-surface-primary rounded-xl transition-all shadow-sm hover:shadow-md disabled:opacity-50"
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
                        </Tooltip>

                        <AppPrintButton variant="icon" @click="printSuppliers" />

                        <div class="w-px h-6 bg-border-default mx-1"></div>

                        <AppViewModeToggle v-model="viewMode" storage-key="suppliers" />
                    </div>

                    <button
                        v-if="can('purchasing.suppliers.create') || isAnyAdmin()"
                        @click="createSupplier"
                        class="flex items-center justify-center gap-3 px-6 py-3 bg-action-primary hover:bg-action-primary-hover text-white rounded-2xl font-bold shadow-sm transition-all group/add"
                    >
                        <div
                            class="w-7 h-7 rounded-lg bg-white/20 flex items-center justify-center group-hover/add:rotate-90 transition-transform duration-300"
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
                                    stroke-width="3"
                                    d="M12 4v16m8-8H4"
                                />
                            </svg>
                        </div>
                        <span class="text-sm tracking-tight">
                            {{ $t('purchasing.suppliers.add') }}
                        </span>
                    </button>
                </template>

                <template #filters>
                    <div class="flex flex-col md:flex-row items-center gap-4">
                        <!-- Search Box -->
                        <div class="relative group flex-1 w-full">
                            <div
                                class="absolute inset-y-0 start-0 flex items-center ps-4 pointer-events-none text-content-secondary group-focus-within:text-action-primary transition-colors"
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
                                v-model="tableFilters.search"
                                type="text"
                                :placeholder="$t('purchasing.suppliers.search_placeholder')"
                                class="block w-full ps-11 pe-4 py-3.5 text-sm text-content-primary bg-surface-primary border border-border-default rounded-2xl focus:ring-4 focus:ring-action-primary/10 focus:border-action-primary transition-all outline-none shadow-sm placeholder-content-secondary"
                            />
                        </div>

                        <div class="flex items-center gap-3 w-full md:w-auto">
                            <!-- Type Filter -->
                            <div class="w-full md:w-44">
                                <SearchableSelect
                                    v-model="tableFilters.type"
                                    :options="typeOptions"
                                    option-label="label"
                                    option-value="value"
                                    :placeholder="$t('purchasing.suppliers.type')"
                                    :label="''"
                                    class="!rounded-2xl"
                                    @change="applyFilters"
                                />
                            </div>

                            <!-- Status Filter -->
                            <div class="w-full md:w-40">
                                <SearchableSelect
                                    v-model="tableFilters.status"
                                    :options="statusOptions"
                                    option-label="label"
                                    option-value="value"
                                    :placeholder="$t('common.all')"
                                    :label="''"
                                    class="!rounded-2xl"
                                    @change="applyFilters"
                                />
                            </div>

                            <!-- Reset Button -->
                            <Tooltip :text="$t('common.reset')">
                                <button
                                    @click="resetFilters"
                                    class="p-3.5 text-content-secondary hover:text-danger hover:bg-danger-soft rounded-2xl border border-border-default bg-surface-primary transition-all shadow-sm"
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
                            </Tooltip>
                        </div>
                    </div>
                </template>
            </PageHeader>

            <!-- Loading State -->
            <AppTableSkeleton v-if="!suppliers" :rows="6" :cols="7" />

            <!-- Empty State -->
            <AppEmptyState
                v-else-if="suppliers.data.length === 0"
                :title="$t('purchasing.suppliers.empty')"
                :description="$t('purchasing.suppliers.empty_hint')"
                :action-text="
                    can('purchasing.suppliers.create') || isAnyAdmin()
                        ? $t('purchasing.suppliers.add')
                        : ''
                "
                @action="createSupplier"
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
                            d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"
                        />
                    </svg>
                </template>
            </AppEmptyState>

            <!-- GRID VIEW -->
            <div v-else-if="viewMode === 'grid'" class="space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-6">
                    <div
                        v-for="supplier in suppliers.data"
                        :key="supplier.id"
                        @click="router.visit(route('app.purchasing.suppliers.show', supplier.id))"
                        class="group relative flex flex-col h-[340px] bg-surface-primary rounded-[2.5rem] border border-border-default hover:border-action-primary hover:shadow-xl hover:-translate-y-1 transition-all duration-300 cursor-pointer overflow-hidden p-6 pb-8"
                    >
                        <!-- Watermark -->
                        <div
                            class="absolute -right-4 -bottom-6 opacity-[0.03] dark:opacity-[0.06] pointer-events-none select-none group-hover:scale-110 transition-transform duration-700"
                        >
                            <span class="text-[180px] font-black italic text-content-primary">
                                {{ supplier.name.charAt(0) }}
                            </span>
                        </div>

                        <div class="relative z-10 flex flex-col h-full">
                            <!-- 1. Header: Name & Type -->
                            <div class="flex items-start justify-between gap-4 mb-4">
                                <div class="min-w-0">
                                    <h3
                                        class="text-base font-bold text-content-primary group-hover:text-action-primary transition-colors line-clamp-2 leading-tight"
                                    >
                                        {{ supplier.name }}
                                    </h3>
                                    <div class="flex items-center gap-1.5 mt-1.5 flex-wrap">
                                        <span
                                            class="inline-flex items-center px-2 py-0.5 rounded-lg text-[9px] font-bold border border-border-default bg-surface-secondary text-content-secondary"
                                        >
                                            {{
                                                supplier.type === 'parts'
                                                    ? $t('purchasing.suppliers.type_parts')
                                                    : $t('purchasing.suppliers.type_services')
                                            }}
                                        </span>

                                        <span
                                            :class="[
                                                'inline-flex items-center px-2 py-0.5 rounded-lg text-[9px] font-bold',
                                                supplier.is_active
                                                    ? 'bg-success-soft text-success'
                                                    : 'bg-surface-secondary text-content-secondary border border-border-default',
                                            ]"
                                        >
                                            {{
                                                supplier.is_active
                                                    ? $t('common.active')
                                                    : $t('common.inactive')
                                            }}
                                        </span>
                                    </div>
                                </div>

                                <!-- Avatar -->
                                <div
                                    class="w-12 h-12 rounded-2xl flex-shrink-0 flex items-center justify-center text-lg font-bold text-white bg-action-primary shadow-sm"
                                >
                                    {{ supplier.name.charAt(0).toUpperCase() }}
                                </div>
                            </div>

                            <!-- 2. Stats Grid -->
                            <div class="grid grid-cols-2 gap-3 mb-5">
                                <div
                                    class="flex items-center gap-2 p-2 rounded-2xl bg-surface-secondary/50 border border-border-default"
                                >
                                    <div
                                        class="p-1.5 rounded-lg bg-surface-primary shadow-sm border border-border-default"
                                    >
                                        <svg
                                            class="w-3.5 h-3.5 text-action-primary"
                                            fill="none"
                                            stroke="currentColor"
                                            viewBox="0 0 24 24"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2.5"
                                                d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"
                                            />
                                        </svg>
                                    </div>
                                    <div class="flex flex-col">
                                        <span class="text-xs font-bold text-content-primary">
                                            {{ toEnglish(supplier.purchase_orders_count || 0) }}
                                        </span>
                                        <span
                                            class="text-[9px] font-medium text-content-secondary uppercase"
                                        >
                                            {{ $t('purchasing.suppliers.orders') }}
                                        </span>
                                    </div>
                                </div>

                                <div
                                    class="flex items-center gap-2 p-2 rounded-2xl bg-surface-secondary/50 border border-border-default"
                                >
                                    <div
                                        class="p-1.5 rounded-lg bg-surface-primary shadow-sm border border-border-default"
                                    >
                                        <svg
                                            class="w-3.5 h-3.5 text-content-secondary"
                                            fill="none"
                                            stroke="currentColor"
                                            viewBox="0 0 24 24"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2.5"
                                                d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                                            />
                                        </svg>
                                    </div>
                                    <div class="flex flex-col">
                                        <span
                                            :class="[
                                                'text-xs font-bold',
                                                supplier.balance < -0.01
                                                    ? 'text-danger font-bold'
                                                    : 'text-content-primary',
                                            ]"
                                        >
                                            {{ formatCurrency(supplier.balance) }}
                                        </span>
                                        <span
                                            class="text-[9px] font-medium text-content-secondary uppercase"
                                        >
                                            {{ $t('purchasing.suppliers.balance') }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <!-- 3. Contact Box -->
                            <div class="mb-4">
                                <div
                                    class="flex items-center gap-2.5 px-3 py-2 rounded-xl bg-surface-secondary/40 border border-border-default"
                                >
                                    <div
                                        class="w-7 h-7 rounded-lg bg-surface-primary flex items-center justify-center shadow-sm border border-border-default"
                                    >
                                        <svg
                                            class="w-3.5 h-3.5 text-content-secondary"
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
                                    </div>
                                    <span class="text-xs font-medium text-content-primary truncate">
                                        {{ supplier.contact_person || $t('common.no_contact') }}
                                    </span>
                                </div>
                            </div>

                            <!-- 4. Fixed Actions Footer -->
                            <div class="mt-auto grid grid-cols-2 gap-2">
                                <a
                                    :href="supplier.phone ? `tel:${supplier.phone}` : '#'"
                                    @click.stop
                                    :class="[
                                        'flex items-center justify-center gap-1.5 h-10 rounded-xl transition-all font-bold text-xs shadow-sm border',
                                        supplier.phone
                                            ? 'bg-surface-primary text-content-primary hover:bg-surface-secondary border-border-default'
                                            : 'bg-surface-secondary text-content-secondary opacity-50 border-border-default cursor-not-allowed',
                                    ]"
                                >
                                    <svg
                                        class="w-3.5 h-3.5 flex-shrink-0"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"
                                        />
                                    </svg>
                                    <span class="truncate">{{ $t('common.call') }}</span>
                                </a>

                                <a
                                    :href="supplier.email ? `mailto:${supplier.email}` : '#'"
                                    @click.stop
                                    :class="[
                                        'flex items-center justify-center gap-1.5 h-10 rounded-xl transition-all font-bold text-xs shadow-sm border',
                                        supplier.email
                                            ? 'bg-surface-primary text-content-primary hover:bg-surface-secondary border-border-default'
                                            : 'bg-surface-secondary text-content-secondary opacity-50 border-border-default cursor-not-allowed',
                                    ]"
                                >
                                    <svg
                                        class="w-3.5 h-3.5 flex-shrink-0"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"
                                        />
                                    </svg>
                                    <span class="truncate">{{ $t('common.email') }}</span>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Grid Pagination -->
                <AppPagination
                    :links="suppliers.links"
                    :from="suppliers.from"
                    :to="suppliers.to"
                    :total="suppliers.total"
                />
            </div>

            <!-- LIST VIEW (DataTable) -->
            <div v-else class="space-y-4">
                <DataTable
                    :data="suppliers"
                    :columns="columns"
                    :loading="isLoading"
                    :sort-column="sortColumn"
                    :sort-direction="sortDirection"
                    @sort="toggleSort"
                    @row-click="
                        (item) => router.visit(route('app.purchasing.suppliers.show', item.id))
                    "
                >
                    <template #cell-name="{ item }">
                        <span
                            class="text-sm font-bold text-content-primary group-hover:text-action-primary transition-colors"
                        >
                            {{ item.name }}
                        </span>
                    </template>

                    <template #cell-contact_person="{ item }">
                        <span class="text-sm text-content-secondary">
                            {{ item.contact_person || '-' }}
                        </span>
                    </template>

                    <template #cell-phone="{ item }">
                        <span dir="ltr" class="text-sm text-content-secondary font-mono">
                            {{ toEnglish(item.phone) || '-' }}
                        </span>
                    </template>

                    <template #cell-type="{ item }">
                        <span
                            class="inline-flex items-center px-2 py-0.5 rounded-lg text-[10px] font-bold border border-border-default bg-surface-secondary text-content-secondary"
                        >
                            {{
                                item.type === 'parts'
                                    ? $t('purchasing.suppliers.type_parts')
                                    : $t('purchasing.suppliers.type_services')
                            }}
                        </span>
                    </template>

                    <template #cell-balance="{ item }">
                        <span
                            dir="ltr"
                            :class="[
                                'text-sm font-bold',
                                item.balance < -0.01 ? 'text-danger' : 'text-content-primary',
                            ]"
                        >
                            {{ formatCurrency(item.balance) }}
                        </span>
                    </template>

                    <template #cell-purchase_orders_count="{ item }">
                        <span
                            class="inline-flex px-2 py-0.5 rounded-full text-xs font-bold bg-surface-secondary text-content-primary border border-border-default"
                        >
                            {{ toEnglish(item.purchase_orders_count || 0) }}
                        </span>
                    </template>

                    <template #cell-status="{ item }">
                        <span
                            :class="[
                                'inline-flex px-2.5 py-0.5 rounded-lg text-[10px] font-bold uppercase tracking-wider',
                                item.is_active
                                    ? 'bg-success-soft text-success'
                                    : 'bg-surface-secondary text-content-secondary border border-border-default',
                            ]"
                        >
                            {{ item.is_active ? $t('common.active') : $t('common.inactive') }}
                        </span>
                    </template>

                    <template #cell-actions="{ item }">
                        <div class="flex items-center justify-center gap-1.5" @click.stop>
                            <Tooltip :text="$t('common.view')">
                                <button
                                    @click="
                                        router.visit(
                                            route('app.purchasing.suppliers.show', item.id)
                                        )
                                    "
                                    class="p-2 text-content-secondary hover:text-action-primary hover:bg-surface-secondary rounded-lg transition-colors"
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
                                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
                                        />
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"
                                        />
                                    </svg>
                                </button>
                            </Tooltip>
                            <a
                                v-if="item.phone"
                                :href="`tel:${item.phone}`"
                                class="p-2 text-content-secondary hover:text-action-primary hover:bg-surface-secondary rounded-lg transition-colors"
                                :title="$t('common.call')"
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
                                        d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"
                                    />
                                </svg>
                            </a>
                        </div>
                    </template>
                </DataTable>
            </div>

            <!-- Create/Edit Modal -->
            <CreateModal :show="showCreateModal" :supplier="editingSupplier" @close="closeModal" />

            <!-- Import Modal -->
            <SupplierImportModal
                :show="showImportModal"
                @close="showImportModal = false"
                @imported="router.reload()"
            />

            <!-- Print Section -->
            <Teleport to="body">
                <div class="print-section hidden">
                    <StandardReportHeader :title="$t('purchasing.suppliers.title')" />

                    <!-- Table -->
                    <DataTable structured class="print-table">
                        <thead>
                            <tr>
                                <th class="align-middle text-center">#</th>
                                <th class="align-middle text-center">
                                    {{ $t('purchasing.suppliers.name') }}
                                </th>
                                <th class="align-middle text-center">
                                    {{ $t('purchasing.suppliers.phone') }}
                                </th>
                                <th class="align-middle text-center">
                                    {{ $t('purchasing.suppliers.email') }}
                                </th>
                                <th class="align-middle text-center">
                                    {{ $t('purchasing.suppliers.type') }}
                                </th>
                                <th class="align-middle text-center">
                                    {{ $t('purchasing.suppliers.balance') }}
                                </th>
                                <th class="align-middle text-center">{{ $t('common.status') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(supplier, index) in suppliers.data" :key="supplier.id">
                                <td class="align-middle">{{ toEnglish(index + 1) }}</td>
                                <td class="align-middle">{{ supplier.name }}</td>
                                <td dir="ltr" class="text-left font-sans align-middle">
                                    {{ toEnglish(supplier.phone) || '-' }}
                                </td>
                                <td dir="ltr" class="text-left align-middle">
                                    {{ supplier.email || '-' }}
                                </td>
                                <td class="align-middle">
                                    {{
                                        supplier.type === 'parts'
                                            ? $t('purchasing.suppliers.type_parts')
                                            : $t('purchasing.suppliers.type_services')
                                    }}
                                </td>
                                <td
                                    dir="ltr"
                                    :class="[
                                        'text-left font-bold',
                                        supplier.balance < -0.01 ? 'text-danger' : '',
                                    ]"
                                    class="align-middle"
                                >
                                    {{ formatCurrency(supplier.balance) }}
                                </td>
                                <td class="align-middle">
                                    {{
                                        supplier.is_active
                                            ? $t('common.active')
                                            : $t('common.inactive')
                                    }}
                                </td>
                            </tr>
                        </tbody>
                    </DataTable>

                    <StandardReportFooter class="mt-8 border-t pt-4" />
                </div>
            </Teleport>
        </PrintEngine>
    </AppLayout>
</template>

<script setup>
import PrintEngine from '@/Components/Print/PrintEngine.vue';
import { printDocument } from '@/Composables/usePrint';
import { ref, computed, watch } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import CreateModal from './CreateModal.vue';
import SupplierImportModal from '@/Components/Purchasing/SupplierImportModal.vue';
import { useI18n } from 'vue-i18n';
import { usePermission } from '@/Composables/usePermission';
import { useNumberFormat } from '@/Composables/useNumberFormat';
import { useServerTable } from '@/Composables/useServerTable';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import PageHeader from '@/Components/PageHeader.vue';
import AppPrintButton from '@/Components/Common/AppPrintButton.vue';
import AppViewModeToggle from '@/Components/Common/AppViewModeToggle.vue';
import AppEmptyState from '@/Components/Common/AppEmptyState.vue';
import AppTableSkeleton from '@/Components/Common/AppTableSkeleton.vue';
import DataTable from '@/Components/Common/DataTable.vue';
import AppPagination from '@/Components/Common/AppPagination.vue';
import Tooltip from '@/Components/Tooltip.vue';
import StandardReportHeader from '@/Components/Print/StandardReportHeader.vue';
import StandardReportFooter from '@/Components/Print/StandardReportFooter.vue';

const { toEnglish } = useNumberFormat();

const props = defineProps({
    suppliers: Object,
    filters: Object,
});

const page = usePage();
const { t, locale } = useI18n();
const { can, isAnyAdmin } = usePermission();

const exporting = ref(false);
const showCreateModal = ref(false);
const showImportModal = ref(false);
const editingSupplier = ref(null);
const viewMode = ref(localStorage.getItem('suppliers-view-mode') || 'grid');

watch(viewMode, (newVal) => {
    localStorage.setItem('suppliers-view-mode', newVal);
});

const {
    filters: tableFilters,
    sortColumn,
    sortDirection,
    isLoading,
    applyFilters,
    toggleSort,
    resetFilters,
} = useServerTable({
    routeUrl: '/app/purchasing/suppliers',
    initialFilters: {
        search: props.filters?.search || '',
        status: props.filters?.status || '',
        type: props.filters?.type || '',
    },
    initialSort: {
        column: props.filters?.sort || 'name',
        direction: props.filters?.direction || 'asc',
    },
    debounceMs: 350,
});

const statusOptions = computed(() => [
    { value: '', label: t('common.all') },
    { value: 'active', label: t('common.active') },
    { value: 'inactive', label: t('common.inactive') },
]);

const typeOptions = computed(() => [
    {
        value: '',
        label: t('common.all') + ' (' + t('purchasing.suppliers.type') + ')',
    },
    { value: 'parts', label: t('purchasing.suppliers.type_parts') },
    { value: 'services', label: t('purchasing.suppliers.type_services') },
]);

const columns = computed(() => [
    { key: 'name', label: t('purchasing.suppliers.name'), sortable: true, align: 'center' },
    {
        key: 'contact_person',
        label: t('purchasing.suppliers.contact'),
        sortable: true,
        align: 'center',
    },
    { key: 'phone', label: t('purchasing.suppliers.phone'), sortable: true, align: 'center' },
    { key: 'type', label: t('purchasing.suppliers.type'), sortable: true, align: 'center' },
    { key: 'balance', label: t('purchasing.suppliers.balance'), sortable: false, align: 'center' },
    {
        key: 'purchase_orders_count',
        label: t('purchasing.suppliers.orders_count'),
        sortable: true,
        align: 'center',
    },
    { key: 'status', label: t('common.status'), sortable: true, align: 'center' },
    { key: 'actions', label: t('common.actions'), sortable: false, align: 'center' },
]);

const createSupplier = () => {
    editingSupplier.value = null;
    showCreateModal.value = true;
};

const closeModal = () => {
    showCreateModal.value = false;
    editingSupplier.value = null;
};

function formatCurrency(value) {
    if (!value && value !== 0) return '-';
    const val = Math.abs(value) < 0.01 ? 0 : value;
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'SAR',
        minimumFractionDigits: 0,
    }).format(val);
}

function exportSuppliers() {
    exporting.value = true;
    const params = new URLSearchParams();
    if (tableFilters.search) params.set('search', tableFilters.search);
    if (tableFilters.status) params.set('status', tableFilters.status);
    if (tableFilters.type) params.set('type', tableFilters.type);
    params.set('locale', locale.value);

    window.location.href = route('app.purchasing.suppliers.export') + '?' + params.toString();
    setTimeout(() => {
        exporting.value = false;
    }, 2000);
}

function printSuppliers() {
    printDocument();
}
</script>

<style scoped>
@media print {
    .print-section {
        display: block !important;
        font-family:
            'IBM Plex Sans Arabic',
            system-ui,
            -apple-system,
            sans-serif !important;
        padding: 20px;
        background: white;
    }

    .print-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 12px;
    }

    .print-table th,
    .print-table td {
        border: 1px solid #e5e7eb;
        padding: 8px;
        text-align: center !important;
    }

    .print-table th {
        background-color: #f9fafb !important;
        -webkit-print-color-adjust: exact;
        font-weight: bold;
    }
}
</style>
