<template>
    <AppLayout :title="$t('inventory.parts.title')">
        <div class="space-y-6">
            <!-- Page Header -->
            <PageHeader
                :title="$t('inventory.parts.title')"
                :subtitle="$t('inventory.parts.subtitle')"
                :totalCount="parts ? toEnglish(parts.total) : '0'"
                :countLabel="$t('inventory.parts.title')"
            >
                <template #back>
                    <BackButton :href="route('app.inventory.hub')" />
                </template>

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
                            stroke-width="2.2"
                            d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"
                        />
                    </svg>
                </template>

                <template #actions>
                    <div class="flex items-center gap-3">
                        <!-- Actions Toolbar -->
                        <div
                            class="flex items-center gap-1.5 p-1 bg-surface-muted rounded-xl border border-border"
                        >
                            <!-- Print Button -->
                            <AppPrintButton variant="icon" @click="handlePrint" />

                            <div class="w-px h-5 bg-border mx-0.5"></div>

                            <!-- View Mode Toggle -->
                            <AppViewModeToggle
                                v-model="viewMode"
                                storage-key="inventory_parts_view_mode"
                            />
                        </div>

                        <!-- Add Part Button (Anchored Left) -->
                        <button
                            v-if="can('inventory.moves.create') || isAnyAdmin()"
                            type="button"
                            @click="createPart"
                            class="inline-flex items-center gap-2 px-4 py-2.5 bg-action-primary hover:bg-action-primary-hover text-action-on rounded-xl font-bold shadow-xs transition-colors cursor-pointer"
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
                                    stroke-width="2.5"
                                    d="M12 4v16m8-8H4"
                                />
                            </svg>
                            <span class="text-sm">{{ $t('inventory.parts.add') }}</span>
                        </button>
                    </div>
                </template>

                <template #filters>
                    <div class="flex flex-wrap items-end gap-3">
                        <!-- Search Box -->
                        <div class="relative group min-w-[220px] flex-1 sm:flex-initial">
                            <div
                                class="absolute inset-y-0 start-0 ps-3.5 flex items-center pointer-events-none text-text-muted group-focus-within:text-action-primary transition-colors"
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
                                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
                                    />
                                </svg>
                            </div>
                            <input
                                v-model="tableFilters.search"
                                type="text"
                                :placeholder="$t('inventory.parts.search_placeholder')"
                                class="block w-full ps-10 pe-4 py-2.5 text-sm text-text-primary bg-surface border border-border rounded-xl focus:ring-2 focus:ring-focus-ring focus:border-action-primary transition-all outline-none"
                            />
                        </div>

                        <!-- Status Filter -->
                        <div class="w-full sm:w-44">
                            <label class="block text-[11px] font-bold text-text-secondary mb-1">
                                {{ $t('common.status') }}
                            </label>
                            <SearchableSelect
                                v-model="tableFilters.status"
                                :options="statusOptions"
                                option-label="label"
                                option-value="value"
                                :placeholder="$t('common.status')"
                                @update:modelValue="() => applyFilters()"
                                compact
                            />
                        </div>

                        <!-- Category Filter -->
                        <div class="w-full sm:w-56" v-if="categories.length">
                            <label class="block text-[11px] font-bold text-text-secondary mb-1">
                                {{ $t('inventory.parts.category') }}
                            </label>
                            <SearchableSelect
                                v-model="tableFilters.category"
                                :options="computedCategories"
                                option-label="name"
                                option-value="id"
                                :placeholder="$t('inventory.parts.all_categories')"
                                @update:modelValue="() => applyFilters()"
                                compact
                            />
                        </div>

                        <!-- Vehicle Make Filter -->
                        <div class="w-full sm:w-44" v-if="vehicleMakes.length">
                            <label class="block text-[11px] font-bold text-text-secondary mb-1">
                                {{ $t('inventory.parts.compatibility.make') }}
                            </label>
                            <SearchableSelect
                                v-model="tableFilters.vehicle_make_id"
                                :options="computedMakes"
                                option-label="name"
                                option-value="id"
                                :placeholder="$t('inventory.parts.compatibility.all_makes')"
                                @update:modelValue="onMakeFilterChange"
                                compact
                            />
                        </div>

                        <!-- Vehicle Model Filter -->
                        <div
                            class="w-full sm:w-44"
                            v-if="vehicleMakes.length && filteredModels.length > 1"
                        >
                            <label class="block text-[11px] font-bold text-text-secondary mb-1">
                                {{ $t('inventory.parts.compatibility.model') }}
                            </label>
                            <SearchableSelect
                                v-model="tableFilters.vehicle_model_id"
                                :options="filteredModels"
                                option-label="name"
                                option-value="id"
                                :placeholder="$t('inventory.parts.compatibility.all_models')"
                                @update:modelValue="() => applyFilters()"
                                compact
                            />
                        </div>

                        <!-- Manufacture Year Filter -->
                        <div class="w-28" v-if="vehicleMakes.length">
                            <label class="block text-[11px] font-bold text-text-secondary mb-1">
                                {{ $t('inventory.parts.compatibility.manufacture_year') }}
                            </label>
                            <input
                                v-model="tableFilters.manufacture_year"
                                type="number"
                                min="1950"
                                max="2100"
                                dir="ltr"
                                lang="en"
                                :placeholder="$t('inventory.parts.compatibility.manufacture_year')"
                                class="block w-full px-3 py-2 text-xs text-text-primary bg-surface border border-border rounded-xl focus:ring-2 focus:ring-focus-ring focus:border-action-primary transition-all outline-none font-mono"
                            />
                        </div>

                        <!-- Reset Filters Button -->
                        <button
                            v-if="hasActiveFilters"
                            type="button"
                            @click="resetAllFilters"
                            class="px-3 py-2.5 text-xs font-semibold text-text-secondary hover:text-danger hover:bg-danger-soft rounded-xl border border-border transition-colors flex items-center gap-1.5 cursor-pointer"
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
                                    d="M6 18L18 6M6 6l12 12"
                                />
                            </svg>
                            <span>{{ $t('common.reset_filters') }}</span>
                        </button>
                    </div>
                </template>
            </PageHeader>

            <!-- Main Content Area -->
            <div class="space-y-6">
                <!-- GRID VIEW (Cards) -->
                <div v-if="viewMode === 'grid'">
                    <AppEmptyState
                        v-if="!partsList.length"
                        :title="$t('inventory.parts.empty')"
                        :description="
                            $t('common.no_records_found', 'لم يتم العثور على أي سجلات مطابقة')
                        "
                        :action-text="
                            can('inventory.moves.create') || isAnyAdmin()
                                ? $t('inventory.parts.add')
                                : ''
                        "
                        @action="createPart"
                    />

                    <div
                        v-else
                        class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4"
                    >
                        <div
                            v-for="part in partsList"
                            :key="part.id"
                            class="bg-surface rounded-2xl border border-border hover:border-action-primary hover:shadow-md transition-all group relative flex flex-col overflow-hidden"
                        >
                            <!-- Image Area -->
                            <div
                                @click="editPart(part)"
                                class="h-32 bg-surface-muted relative overflow-hidden group/img cursor-pointer"
                            >
                                <img
                                    v-if="part.image_url"
                                    :src="part.image_url"
                                    class="w-full h-full object-cover transition-transform duration-300 group-hover/img:scale-105"
                                />
                                <div
                                    v-else
                                    class="w-full h-full flex items-center justify-center text-text-muted"
                                >
                                    <svg
                                        class="w-12 h-12"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="1.5"
                                            d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"
                                        />
                                    </svg>
                                </div>
                            </div>

                            <!-- Top Row: SKU & Unit -->
                            <div
                                class="px-4 pt-3 flex justify-between items-start text-xs text-text-secondary"
                            >
                                <span class="font-mono font-bold text-action-primary">
                                    {{ part.sku }}
                                </span>
                                <span v-if="part.unit" class="text-text-muted text-[11px]">
                                    {{
                                        locale === 'ar'
                                            ? part.unit.name_ar
                                            : part.unit.name_en || part.unit.name_ar
                                    }}
                                </span>
                            </div>

                            <!-- Middle: Name & Barcode -->
                            <div class="px-4 py-2 flex-1 flex flex-col justify-between">
                                <div>
                                    <h3
                                        @click="editPart(part)"
                                        class="font-bold text-sm text-text-primary hover:text-action-primary transition-colors cursor-pointer line-clamp-2"
                                    >
                                        {{ part.name_ar }}
                                    </h3>
                                    <span
                                        v-if="part.name_en"
                                        class="block text-[11px] text-text-secondary mt-0.5 line-clamp-1"
                                    >
                                        {{ part.name_en }}
                                    </span>
                                    <span
                                        v-if="part.barcode"
                                        class="block text-[10px] text-text-muted font-mono mt-1"
                                    >
                                        {{ part.barcode }}
                                    </span>
                                </div>

                                <div
                                    class="mt-3 pt-2 border-t border-border/60 flex items-center justify-between"
                                >
                                    <span class="text-xs text-text-secondary">
                                        {{ $t('inventory.stock.qty') }}:
                                    </span>
                                    <span
                                        :class="[
                                            'inline-flex px-2 py-0.5 rounded-full text-xs font-bold border',
                                            getStockBadgeClass(part),
                                        ]"
                                    >
                                        {{
                                            formatQuantity(
                                                part.inventory_balances_sum_qty_on_hand ?? 0
                                            )
                                        }}
                                    </span>
                                </div>
                            </div>

                            <!-- Bottom: Warehouses Accordion Toggle -->
                            <div class="border-t border-border/60">
                                <button
                                    type="button"
                                    @click="toggleExpanded(part.id)"
                                    class="w-full flex items-center justify-between px-4 py-2 text-xs text-text-secondary hover:bg-surface-muted transition-colors cursor-pointer"
                                >
                                    <span>{{ $t('inventory.stock.title') }}</span>
                                    <svg
                                        class="w-3.5 h-3.5 transition-transform"
                                        :class="{ 'rotate-180': expandedPartId === part.id }"
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

                                <!-- Collapsible Warehouses List -->
                                <div
                                    v-show="expandedPartId === part.id"
                                    class="px-4 py-2 bg-surface-muted border-t border-border/60 text-xs space-y-1.5"
                                >
                                    <div
                                        v-for="balance in part.inventory_balances"
                                        :key="balance.id"
                                        class="flex items-center justify-between py-1 border-b border-border/40 last:border-b-0 text-[11px]"
                                    >
                                        <span class="text-text-secondary truncate max-w-[120px]">
                                            {{
                                                balance.warehouse?.center?.name ||
                                                balance.warehouse?.name ||
                                                '—'
                                            }}
                                        </span>
                                        <span
                                            class="font-mono font-bold text-text-primary"
                                            dir="ltr"
                                        >
                                            {{ formatQuantity(balance.qty_on_hand) }}
                                        </span>
                                    </div>
                                    <div
                                        v-if="!part.inventory_balances?.length"
                                        class="text-text-muted text-[11px] text-center py-1"
                                    >
                                        {{ $t('common.no_records_found') }}
                                    </div>
                                </div>
                            </div>

                            <!-- Actions Footer on Card -->
                            <div
                                class="px-3 py-2 bg-surface-muted/50 border-t border-border/60 flex items-center justify-end gap-1"
                            >
                                <Tooltip :content="$t('common.edit')">
                                    <button
                                        v-if="can('inventory.moves.create') || isAnyAdmin()"
                                        type="button"
                                        @click="editPart(part)"
                                        class="p-1.5 text-text-muted hover:text-action-primary hover:bg-surface rounded-lg transition-colors cursor-pointer"
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
                                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"
                                            />
                                        </svg>
                                    </button>
                                </Tooltip>
                                <Tooltip :content="$t('inventory.parts.warehouse_stock')">
                                    <button
                                        v-if="can('inventory.moves.create') || isAnyAdmin()"
                                        type="button"
                                        @click="editPartStock(part)"
                                        class="p-1.5 text-text-muted hover:text-action-primary hover:bg-surface rounded-lg transition-colors cursor-pointer"
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
                                                d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"
                                            />
                                        </svg>
                                    </button>
                                </Tooltip>
                            </div>
                        </div>
                    </div>

                    <!-- Pagination for Grid View -->
                    <div v-if="parts?.links?.length >= 3" class="mt-6">
                        <AppPagination
                            :links="parts.links"
                            :from="parts.from"
                            :to="parts.to"
                            :total="parts.total"
                        />
                    </div>
                </div>

                <!-- LIST VIEW (DataTable) -->
                <div v-else>
                    <DataTable
                        :columns="columns"
                        :data="parts"
                        :loading="isLoading"
                        :sort-column="sortColumn"
                        :sort-direction="sortDirection"
                        @sort="toggleSort"
                        :empty-title="$t('inventory.parts.empty')"
                        :empty-description="
                            $t('common.no_records_found', 'لم يتم العثور على أي سجلات مطابقة')
                        "
                        :empty-action-text="
                            can('inventory.moves.create') || isAnyAdmin()
                                ? $t('inventory.parts.add')
                                : ''
                        "
                        @emptyAction="createPart"
                    >
                        <!-- Index Column -->
                        <template #cell-index="{ index }">
                            <span class="text-xs text-text-muted font-mono">
                                {{ toEnglish((parts?.from || 1) + index) }}
                            </span>
                        </template>

                        <!-- Photo Column -->
                        <template #cell-photo="{ item }">
                            <div class="flex justify-center">
                                <img
                                    v-if="item.image_url"
                                    :src="item.image_url"
                                    class="w-9 h-9 rounded-lg object-cover border border-border"
                                />
                                <div
                                    v-else
                                    class="w-9 h-9 rounded-lg bg-surface-muted border border-border flex items-center justify-center text-text-muted"
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
                                            stroke-width="1.5"
                                            d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"
                                        />
                                    </svg>
                                </div>
                            </div>
                        </template>

                        <!-- SKU & Barcode Column -->
                        <template #cell-sku="{ item }">
                            <div class="flex flex-col items-center">
                                <span class="text-xs font-mono font-bold text-action-primary">
                                    {{ item.sku }}
                                </span>
                                <span
                                    v-if="item.barcode"
                                    class="text-[10px] text-text-muted font-mono"
                                >
                                    {{ item.barcode }}
                                </span>
                            </div>
                        </template>

                        <!-- Name Column -->
                        <template #cell-name="{ item }">
                            <div>
                                <button
                                    type="button"
                                    @click="editPart(item)"
                                    class="text-sm font-bold text-text-primary hover:text-action-primary transition-colors text-start cursor-pointer"
                                >
                                    {{ item.name_ar }}
                                </button>
                                <span
                                    v-if="item.name_en"
                                    class="block text-[11px] text-text-secondary"
                                >
                                    {{ item.name_en }}
                                </span>
                                <div
                                    v-if="item.compatibilities?.length"
                                    class="flex flex-wrap gap-1 mt-1"
                                >
                                    <span
                                        v-for="c in item.compatibilities.slice(0, 3)"
                                        :key="c.id"
                                        class="inline-flex items-center px-1.5 py-0.5 rounded-md text-[10px] font-medium bg-surface-muted text-text-secondary border border-border/60"
                                    >
                                        {{ formatCompatBadge(c) }}
                                    </span>
                                    <span
                                        v-if="item.compatibilities.length > 3"
                                        class="inline-flex items-center px-1.5 py-0.5 rounded-md text-[10px] font-bold bg-surface-muted text-text-muted"
                                    >
                                        +{{ toEnglish(item.compatibilities.length - 3) }}
                                    </span>
                                </div>
                            </div>
                        </template>

                        <!-- Category Column -->
                        <template #cell-category="{ item }">
                            <span class="text-xs text-text-secondary">
                                {{
                                    (locale === 'ar'
                                        ? item.category?.name_ar
                                        : item.category?.name_en || item.category?.name_ar) || '—'
                                }}
                            </span>
                        </template>

                        <!-- Unit Column -->
                        <template #cell-unit="{ item }">
                            <span class="text-xs text-text-secondary">
                                {{
                                    (locale === 'ar'
                                        ? item.unit?.name_ar
                                        : item.unit?.name_en || item.unit?.name_ar) || '—'
                                }}
                            </span>
                        </template>

                        <!-- Qty Column -->
                        <template #cell-qty="{ item }">
                            <span
                                :class="[
                                    'inline-flex px-2.5 py-0.5 rounded-full text-xs font-bold border',
                                    getStockBadgeClass(item),
                                ]"
                            >
                                {{ formatQuantity(item.inventory_balances_sum_qty_on_hand ?? 0) }}
                            </span>
                        </template>

                        <!-- Status Column -->
                        <template #cell-status="{ item }">
                            <button
                                v-if="can('inventory.moves.create') || isAnyAdmin()"
                                type="button"
                                @click="toggleActive(item)"
                                class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-semibold border transition-colors cursor-pointer"
                                :class="
                                    item.is_active
                                        ? 'bg-success-soft text-success border-success/20'
                                        : 'bg-surface-muted text-text-secondary border-border'
                                "
                            >
                                {{ item.is_active ? $t('common.active') : $t('common.inactive') }}
                            </button>
                            <span
                                v-else
                                class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-semibold border"
                                :class="
                                    item.is_active
                                        ? 'bg-success-soft text-success border-success/20'
                                        : 'bg-surface-muted text-text-secondary border-border'
                                "
                            >
                                {{ item.is_active ? $t('common.active') : $t('common.inactive') }}
                            </span>
                        </template>

                        <!-- Actions Column -->
                        <template #cell-actions="{ item }">
                            <div class="flex items-center justify-center gap-1">
                                <Tooltip :content="$t('common.edit')">
                                    <button
                                        v-if="can('inventory.moves.create') || isAnyAdmin()"
                                        type="button"
                                        @click="editPart(item)"
                                        class="p-2 rounded-lg text-text-muted hover:text-action-primary hover:bg-action-primary-soft transition-colors cursor-pointer"
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
                                </Tooltip>
                                <Tooltip :content="$t('inventory.parts.warehouse_stock')">
                                    <button
                                        v-if="can('inventory.moves.create') || isAnyAdmin()"
                                        type="button"
                                        @click="editPartStock(item)"
                                        class="p-2 rounded-lg text-text-muted hover:text-action-primary hover:bg-action-primary-soft transition-colors cursor-pointer"
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
                                                d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"
                                            />
                                        </svg>
                                    </button>
                                </Tooltip>
                            </div>
                        </template>
                    </DataTable>
                </div>
            </div>

            <!-- Create/Edit Modal -->
            <CreateModal
                :show="showCreateModal"
                :part="editingPart"
                :units="units"
                :categories="categories"
                :warehouses="warehouses"
                :vehicle-makes="vehicleMakes"
                :vehicle-models="vehicleModels"
                @close="closeModal"
            />

            <!-- Stock Modal -->
            <StockModal
                :show="showStockModal"
                :part="stockEditingPart"
                :warehouses="warehouses"
                :existing-balances="stockEditingPart?.inventory_balances || []"
                @close="closeStockModal"
            />
        </div>
    </AppLayout>

    <!-- Print Section -->
    <Teleport to="body">
        <div class="print-section hidden">
            <StandardReportHeader :title="$t('inventory.parts.title')" />
            <DataTable structured class="w-full text-xs border border-gray-300">
                <thead>
                    <tr class="bg-gray-100">
                        <th style="width: 40px" class="p-2 border border-gray-300 text-center">
                            #
                        </th>
                        <th class="p-2 border border-gray-300 text-center">
                            {{ $t('inventory.parts.sku') }}
                        </th>
                        <th class="p-2 border border-gray-300 text-start">
                            {{ $t('inventory.parts.name') }}
                        </th>
                        <th class="p-2 border border-gray-300 text-center">
                            {{ $t('inventory.parts.category') }}
                        </th>
                        <th class="p-2 border border-gray-300 text-center">
                            {{ $t('inventory.parts.unit') }}
                        </th>
                        <th class="p-2 border border-gray-300 text-center">
                            {{ $t('inventory.stock.qty') }}
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="(part, index) in partsList" :key="part.id">
                        <td class="p-2 border border-gray-300 text-center">{{ index + 1 }}</td>
                        <td class="p-2 border border-gray-300 font-mono font-bold text-center">
                            {{ part.sku }}
                        </td>
                        <td class="p-2 border border-gray-300 text-start">
                            {{ part.name_ar }}
                            <div v-if="part.name_en" class="text-[10px] text-gray-500">
                                {{ part.name_en }}
                            </div>
                        </td>
                        <td class="p-2 border border-gray-300 text-center">
                            {{
                                (locale === 'ar'
                                    ? part.category?.name_ar
                                    : part.category?.name_en || part.category?.name_ar) || '—'
                            }}
                        </td>
                        <td class="p-2 border border-gray-300 text-center">
                            {{
                                (locale === 'ar'
                                    ? part.unit?.name_ar
                                    : part.unit?.name_en || part.unit?.name_ar) || '—'
                            }}
                        </td>
                        <td class="p-2 border border-gray-300 text-center font-mono font-bold">
                            {{ formatQuantity(part.inventory_balances_sum_qty_on_hand ?? 0) }}
                        </td>
                    </tr>
                </tbody>
            </DataTable>
            <StandardReportFooter class="mt-8 border-t pt-4" />
        </div>
    </Teleport>
</template>

<script setup>
import { printDocument } from '@/Composables/usePrint';
import { ref, computed, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import DataTable from '@/Components/Common/DataTable.vue';
import AppPagination from '@/Components/Common/AppPagination.vue';
import AppViewModeToggle from '@/Components/Common/AppViewModeToggle.vue';
import AppPrintButton from '@/Components/Common/AppPrintButton.vue';
import AppEmptyState from '@/Components/Common/AppEmptyState.vue';
import BackButton from '@/Components/BackButton.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import Tooltip from '@/Components/Tooltip.vue';
import StandardReportHeader from '@/Components/Print/StandardReportHeader.vue';
import StandardReportFooter from '@/Components/Print/StandardReportFooter.vue';
import CreateModal from './CreateModal.vue';
import StockModal from './StockModal.vue';
import { useNumberFormat } from '@/Composables/useNumberFormat';
import { usePermission } from '@/Composables/usePermission';
import { useServerTable } from '@/Composables/useServerTable';

const props = defineProps({
    parts: {
        type: Object,
        default: () => ({}),
    },
    categories: {
        type: Array,
        default: () => [],
    },
    units: {
        type: Array,
        default: () => [],
    },
    warehouses: {
        type: Array,
        default: () => [],
    },
    vehicleMakes: {
        type: Array,
        default: () => [],
    },
    vehicleModels: {
        type: Array,
        default: () => [],
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
});

const { t, locale } = useI18n();
const { can, isAnyAdmin } = usePermission();
const { formatQuantity, toEnglish } = useNumberFormat();

// View Mode (persisted)
const viewMode = ref(localStorage.getItem('inventory_parts_view_mode') || 'list');
watch(viewMode, (newMode) => {
    localStorage.setItem('inventory_parts_view_mode', newMode);
});

// Modals State
const showCreateModal = ref(false);
const editingPart = ref(null);
const showStockModal = ref(false);
const stockEditingPart = ref(null);
const expandedPartId = ref(null);

// Status filter options
const statusOptions = computed(() => [
    { value: '', label: t('common.all') },
    { value: 'active', label: t('common.active') },
    { value: 'inactive', label: t('common.inactive') },
]);

// Categories options for dropdown
const computedCategories = computed(() => {
    const allOption = { id: '', name: t('inventory.parts.all_categories') };
    const cats = props.categories.map((cat) => ({
        ...cat,
        name: locale.value === 'ar' ? cat.name_ar : cat.name_en || cat.name_ar,
    }));
    return [allOption, ...cats];
});

const computedMakes = computed(() => {
    const allOption = { id: '', name: t('inventory.parts.compatibility.all_makes') };
    const makes = (props.vehicleMakes || []).map((m) => ({
        ...m,
        name: locale.value === 'ar' ? m.name_ar || m.name_en : m.name_en || m.name_ar,
    }));
    return [allOption, ...makes];
});

const filteredModels = computed(() => {
    const allOption = { id: '', name: t('inventory.parts.compatibility.all_models') };
    const makeId = tableFilters.vehicle_make_id;
    const models = (props.vehicleModels || [])
        .filter((m) => !makeId || Number(m.make_id) === Number(makeId))
        .map((m) => ({
            ...m,
            name: locale.value === 'ar' ? m.name_ar || m.name_en : m.name_en || m.name_ar,
        }));
    return [allOption, ...models];
});

const formatCompatBadge = (compat) => {
    const makeName =
        locale.value === 'ar'
            ? compat.make?.name_ar || compat.make?.name_en || ''
            : compat.make?.name_en || compat.make?.name_ar || '';
    const modelName = compat.model
        ? locale.value === 'ar'
            ? compat.model.name_ar || compat.model.name_en || ''
            : compat.model.name_en || compat.model.name_ar || ''
        : '';
    const years =
        compat.year_from || compat.year_to
            ? ` (${compat.year_from || '...'}–${compat.year_to || '...'})`
            : '';
    return `${makeName}${modelName ? ' ' + modelName : ''}${years}`.trim();
};

const onMakeFilterChange = () => {
    tableFilters.vehicle_model_id = '';
    applyFilters();
};

// Server Table Composable
const {
    filters: tableFilters,
    sortColumn,
    sortDirection,
    isLoading,
    applyFilters,
    toggleSort,
    cancelDebouncedSearch,
} = useServerTable({
    routeUrl: '/app/inventory/parts',
    initialFilters: {
        search: props.filters?.search || '',
        category: props.filters?.category || '',
        status: props.filters?.status || '',
        vehicle_make_id: props.filters?.vehicle_make_id || '',
        vehicle_model_id: props.filters?.vehicle_model_id || '',
        manufacture_year: props.filters?.manufacture_year || '',
    },
    initialSort: {
        column: props.filters?.sort || 'name_ar',
        direction: props.filters?.direction || 'asc',
    },
    debounceMs: 350,
});

// Synchronize props.filters with tableFilters
watch(
    () => props.filters,
    (newFilters) => {
        if (newFilters) {
            if (newFilters.search !== undefined && newFilters.search !== tableFilters.search) {
                tableFilters.search = newFilters.search || '';
            }
            if (
                newFilters.category !== undefined &&
                newFilters.category !== tableFilters.category
            ) {
                tableFilters.category = newFilters.category || '';
            }
            if (newFilters.status !== undefined && newFilters.status !== tableFilters.status) {
                tableFilters.status = newFilters.status || '';
            }
            if (newFilters.sort !== undefined && newFilters.sort !== sortColumn.value) {
                sortColumn.value = newFilters.sort || '';
            }
            if (
                newFilters.direction !== undefined &&
                newFilters.direction !== sortDirection.value
            ) {
                sortDirection.value = newFilters.direction || 'asc';
            }
        }
    },
    { deep: true }
);

// Reset Filters
const hasActiveFilters = computed(() => {
    return Boolean(
        tableFilters.search ||
        tableFilters.category ||
        tableFilters.status ||
        tableFilters.vehicle_make_id ||
        tableFilters.vehicle_model_id ||
        tableFilters.manufacture_year ||
        sortColumn.value !== 'name_ar'
    );
});

const resetAllFilters = () => {
    cancelDebouncedSearch?.();
    tableFilters.search = '';
    tableFilters.category = '';
    tableFilters.status = '';
    tableFilters.vehicle_make_id = '';
    tableFilters.vehicle_model_id = '';
    tableFilters.manufacture_year = '';
    sortColumn.value = 'name_ar';
    sortDirection.value = 'asc';
    applyFilters();
};

// Parts list
const partsList = computed(() => props.parts?.data || []);

// Columns Definitions
const columns = computed(() => [
    { key: 'index', label: '#', align: 'center', width: 'w-12', sortable: false },
    {
        key: 'photo',
        label: t('inventory.parts.photo'),
        align: 'center',
        width: 'w-14',
        sortable: false,
    },
    {
        key: 'sku',
        label: `${t('inventory.parts.sku')} / ${t('inventory.parts.barcode')}`,
        align: 'center',
        sortable: true,
    },
    { key: 'name', label: t('inventory.parts.name'), align: 'start', sortable: true },
    { key: 'category', label: t('inventory.parts.category'), align: 'center', sortable: false },
    { key: 'unit', label: t('inventory.parts.unit'), align: 'center', sortable: false },
    { key: 'qty', label: t('inventory.stock.qty'), align: 'center', sortable: true },
    { key: 'status', label: t('common.status'), align: 'center', width: 'w-24', sortable: true },
    { key: 'actions', label: t('common.actions'), align: 'center', width: 'w-28', sortable: false },
]);

// Modal & Action Handlers
const createPart = () => {
    editingPart.value = null;
    showCreateModal.value = true;
};

const editPart = (part) => {
    editingPart.value = part;
    showCreateModal.value = true;
};

const editPartStock = (part) => {
    stockEditingPart.value = part;
    showStockModal.value = true;
};

const closeModal = () => {
    showCreateModal.value = false;
    editingPart.value = null;
};

const closeStockModal = () => {
    showStockModal.value = false;
    stockEditingPart.value = null;
};

const toggleActive = (part) => {
    router.patch(
        route('app.inventory.parts.toggle', part.id),
        {},
        {
            preserveScroll: true,
        }
    );
};

const toggleExpanded = (partId) => {
    expandedPartId.value = expandedPartId.value === partId ? null : partId;
};

const handlePrint = () => {
    const url = new URL(window.location.href);
    url.searchParams.set('print', '1');
    url.searchParams.delete('page');
    window.open(url.toString(), '_blank');
};

// Stock Helpers
const getMinQty = (part) => {
    if (parseFloat(part.min_qty) > 0) return parseFloat(part.min_qty);
    return (part.inventory_balances || []).reduce(
        (sum, b) => sum + (parseFloat(b.min_stock) || 0),
        0
    );
};

const getStockBadgeClass = (part) => {
    const qty = parseFloat(part.inventory_balances_sum_qty_on_hand) || 0;
    const min = getMinQty(part);
    if (qty <= 0) {
        return 'bg-danger-soft text-danger border-danger/20';
    }
    if (min > 0 && qty <= min) {
        return 'bg-warning-soft text-warning border-warning/20';
    }
    return 'bg-success-soft text-success border-success/20';
};
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
}
</style>
