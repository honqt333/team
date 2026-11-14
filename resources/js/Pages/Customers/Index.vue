<template>
    <Head :title="$t('customers.title')" />
    <AppLayout :title="$t('customers.title')">
        <div class="space-y-6">
            <!-- Header Section -->
            <PageHeader
                :title="$t('customers.title')"
                :subtitle="$t('customers.subtitle')"
                :totalCount="customers ? toEnglish(customers.total) : null"
                :countLabel="$t('customers.total_count')"
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
                            stroke-width="2.2"
                            d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"
                        />
                    </svg>
                </template>

                <template #actions>
                    <div class="flex items-center gap-3">
                        <!-- Actions Toolbar -->
                        <div
                            class="flex items-center gap-1.5 p-1 bg-surface-muted rounded-xl border border-border"
                        >
                            <!-- Export Button -->
                            <button
                                v-if="can('crm.customers.export') || isAnyAdmin()"
                                type="button"
                                @click="exportCustomers"
                                :disabled="exporting"
                                :title="$t('common.export')"
                                class="p-2 text-text-secondary hover:text-action-primary hover:bg-surface rounded-lg transition-colors cursor-pointer disabled:opacity-50"
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
                                        d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                                    />
                                </svg>
                            </button>

                            <!-- Import Button -->
                            <button
                                v-if="can('crm.customers.import') || isAnyAdmin()"
                                type="button"
                                @click="showImportModal = true"
                                :title="$t('common.import')"
                                class="p-2 text-text-secondary hover:text-action-primary hover:bg-surface rounded-lg transition-colors cursor-pointer"
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
                                        d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"
                                    />
                                </svg>
                            </button>

                            <!-- Print Button -->
                            <AppPrintButton
                                v-if="can('crm.customers.print') || isAnyAdmin()"
                                variant="icon"
                                @click="printCustomers"
                            />

                            <div class="w-px h-5 bg-border mx-0.5"></div>

                            <!-- View Mode Toggle -->
                            <AppViewModeToggle v-model="viewMode" storage-key="customersViewMode" />
                        </div>

                        <!-- CTA Button (Anchored to the left) -->
                        <button
                            v-if="can('crm.customers.create') || isAnyAdmin()"
                            type="button"
                            @click="openCreateModal"
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
                            <span class="text-sm">{{ $t('customers.add') }}</span>
                        </button>
                    </div>
                </template>

                <template #filters>
                    <div class="flex flex-wrap items-end gap-3">
                        <!-- Search Input -->
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
                                type="text"
                                v-model="tableFilters.search"
                                class="block w-full ps-10 pe-4 py-2.5 text-sm text-text-primary bg-surface border border-border rounded-xl focus:ring-2 focus:ring-focus-ring focus:border-action-primary transition-all outline-none"
                                :placeholder="$t('customers.search')"
                            />
                        </div>

                        <!-- Type Dropdown -->
                        <div class="w-full sm:w-48">
                            <label class="block text-[11px] font-bold text-text-secondary mb-1">
                                {{ $t('customers.columns.classification') }}
                            </label>
                            <SearchableSelect
                                v-model="tableFilters.type"
                                :options="typeOptions"
                                option-label="label"
                                option-value="value"
                                :placeholder="$t('customers.filter.all_types')"
                                @update:modelValue="() => applyFilters()"
                                compact
                            />
                        </div>

                        <!-- Date From -->
                        <div class="w-full sm:w-36">
                            <label class="block text-[11px] font-bold text-text-secondary mb-1">
                                {{ $t('common.date_from') }}
                            </label>
                            <CustomDatePicker
                                v-model="tableFilters.date_from"
                                @update:modelValue="() => applyFilters()"
                            />
                        </div>

                        <!-- Date To -->
                        <div class="w-full sm:w-36">
                            <label class="block text-[11px] font-bold text-text-secondary mb-1">
                                {{ $t('common.date_to') }}
                            </label>
                            <CustomDatePicker
                                v-model="tableFilters.date_to"
                                @update:modelValue="() => applyFilters()"
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
                    <!-- Empty State -->
                    <AppEmptyState
                        v-if="!customerList.length"
                        :title="$t('customers.empty')"
                        :description="$t('customers.empty_hint')"
                        :action-text="
                            can('crm.customers.create') || isAnyAdmin() ? $t('customers.add') : ''
                        "
                        @action="openCreateModal"
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
                                    d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"
                                />
                            </svg>
                        </template>
                    </AppEmptyState>

                    <!-- Cards Grid -->
                    <div
                        v-else
                        class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-5 gap-4"
                    >
                        <div
                            v-for="customer in customerList"
                            :key="customer.id"
                            @click="goToCustomer(customer)"
                            class="group relative flex flex-col justify-between h-[320px] bg-surface rounded-2xl border border-border hover:border-action-primary hover:shadow-md transition-all duration-200 cursor-pointer overflow-hidden p-5"
                        >
                            <!-- Watermark Initial -->
                            <div
                                class="absolute -end-3 -bottom-4 opacity-[0.04] pointer-events-none select-none group-hover:scale-105 transition-transform"
                            >
                                <span class="text-[140px] font-black italic text-action-primary">
                                    {{ (customer.name || '').charAt(0) }}
                                </span>
                            </div>

                            <!-- Top Section -->
                            <div class="relative z-10 space-y-3">
                                <div class="flex items-start justify-between gap-2">
                                    <div class="min-w-0">
                                        <h3
                                            class="text-base font-bold text-text-primary group-hover:text-action-primary transition-colors line-clamp-1"
                                        >
                                            {{ customer.name }}
                                        </h3>
                                        <div class="mt-1.5 flex flex-wrap gap-1.5 items-center">
                                            <span
                                                class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-semibold border"
                                                :class="getTypeBadgeClass(customer.type)"
                                            >
                                                {{
                                                    customer.type
                                                        ? $t(`customers.type.${customer.type}`)
                                                        : '—'
                                                }}
                                            </span>
                                            <span
                                                v-if="customer.classification"
                                                class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-semibold border"
                                                :class="
                                                    getClassificationBadgeClass(
                                                        customer.classification
                                                    )
                                                "
                                            >
                                                {{
                                                    $t(
                                                        `customers.classification.${customer.classification}`
                                                    )
                                                }}
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Contact Info -->
                                <div class="space-y-1.5 text-xs text-text-secondary pt-1">
                                    <p
                                        v-if="customer.contact_name"
                                        class="truncate flex items-center gap-1.5"
                                    >
                                        <svg
                                            class="w-3.5 h-3.5 text-text-muted shrink-0"
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
                                        <span class="truncate">{{ customer.contact_name }}</span>
                                    </p>
                                    <p class="font-mono flex items-center gap-1.5" dir="ltr">
                                        <svg
                                            class="w-3.5 h-3.5 text-text-muted shrink-0"
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
                                        <span>{{ customer.phone }}</span>
                                    </p>
                                    <p
                                        v-if="customer.email"
                                        class="truncate flex items-center gap-1.5"
                                    >
                                        <svg
                                            class="w-3.5 h-3.5 text-text-muted shrink-0"
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
                                        <span class="truncate">{{ customer.email }}</span>
                                    </p>
                                </div>
                            </div>

                            <!-- Bottom Section: Balance & Quick Communication -->
                            <div class="relative z-10 pt-3 border-t border-border/60 space-y-3">
                                <div class="flex items-center justify-between text-xs">
                                    <span class="text-text-secondary">
                                        {{ $t('customers.columns.balance') }}
                                    </span>
                                    <span
                                        class="font-mono font-bold"
                                        :class="
                                            Number(customer.balance || 0) > 0
                                                ? 'text-danger'
                                                : 'text-success'
                                        "
                                        dir="ltr"
                                    >
                                        {{ formatCurrency(customer.balance) }}
                                    </span>
                                </div>

                                <div class="grid grid-cols-2 gap-2">
                                    <a
                                        :href="`tel:${customer.phone}`"
                                        @click.stop
                                        class="flex items-center justify-center gap-1.5 py-2 px-2.5 rounded-xl bg-surface-muted hover:bg-action-primary hover:text-action-on text-text-secondary transition-colors text-xs font-semibold"
                                    >
                                        <svg
                                            class="w-3.5 h-3.5 shrink-0"
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
                                        <span class="truncate">
                                            {{ $t('customers.quick.call') }}
                                        </span>
                                    </a>
                                    <a
                                        v-if="customer.whatsapp || customer.phone"
                                        :href="`https://wa.me/${getWhatsAppNumber(customer)}`"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        @click.stop
                                        class="flex items-center justify-center gap-1.5 py-2 px-2.5 rounded-xl bg-success-soft hover:bg-success hover:text-white text-success transition-colors text-xs font-semibold"
                                    >
                                        <svg
                                            class="w-3.5 h-3.5 shrink-0"
                                            fill="currentColor"
                                            viewBox="0 0 24 24"
                                        >
                                            <path
                                                d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.355-5.033c0-5.458 4.441-9.898 9.899-9.898 2.645 0 5.132 1.03 7.002 2.901l.001.001c1.868 1.87 2.898 4.357 2.898 7.002 0 5.459-4.441 9.899-9.897 9.899L12.05 21.785z"
                                            />
                                        </svg>
                                        <span class="truncate">WhatsApp</span>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Pagination for Grid View -->
                    <div v-if="customers?.links?.length >= 3" class="mt-6">
                        <AppPagination
                            :links="customers.links"
                            :from="customers.from"
                            :to="customers.to"
                            :total="customers.total"
                        />
                    </div>
                </div>

                <!-- LIST VIEW (DataTable) -->
                <div v-else>
                    <DataTable
                        :columns="columns"
                        :data="customers"
                        :loading="isLoading"
                        :sort-column="sortColumn"
                        :sort-direction="sortDirection"
                        @sort="toggleSort"
                        :empty-title="$t('customers.empty')"
                        :empty-description="$t('customers.empty_hint')"
                        :empty-action-text="
                            can('crm.customers.create') || isAnyAdmin() ? $t('customers.add') : ''
                        "
                        @emptyAction="openCreateModal"
                    >
                        <!-- Index Column -->
                        <template #cell-index="{ index }">
                            <span class="text-xs text-text-muted font-mono">
                                {{ toEnglish((customers?.from || 1) + index) }}
                            </span>
                        </template>

                        <!-- Classification / Type Column -->
                        <template #cell-type="{ item }">
                            <span
                                v-if="item.type"
                                class="inline-flex items-center px-2 py-0.5 text-[11px] font-semibold rounded-md border"
                                :class="getTypeBadgeClass(item.type)"
                            >
                                {{ $t(`customers.type.${item.type}`) }}
                            </span>
                            <span v-else class="text-text-muted text-xs">—</span>
                        </template>

                        <!-- Name Column -->
                        <template #cell-name="{ item }">
                            <a
                                href="#"
                                @click.prevent="goToCustomer(item)"
                                class="font-bold text-text-primary hover:text-action-primary transition-colors block truncate max-w-[200px]"
                            >
                                {{ item.name }}
                            </a>
                        </template>

                        <!-- Contact Name Column -->
                        <template #cell-contact_name="{ item }">
                            <span class="text-sm text-text-secondary truncate block max-w-[160px]">
                                {{ item.contact_name || '—' }}
                            </span>
                        </template>

                        <!-- Email Column -->
                        <template #cell-email="{ item }">
                            <span
                                class="text-xs text-text-secondary font-mono truncate block max-w-[180px]"
                            >
                                {{ item.email || '—' }}
                            </span>
                        </template>

                        <!-- Phone Column -->
                        <template #cell-phone="{ item }">
                            <span class="text-xs font-medium text-text-primary font-mono" dir="ltr">
                                {{ item.phone }}
                            </span>
                        </template>

                        <!-- Balance Column -->
                        <template #cell-balance="{ item }">
                            <span
                                class="text-sm font-mono font-bold"
                                :class="
                                    Number(item.balance || 0) > 0 ? 'text-danger' : 'text-success'
                                "
                                dir="ltr"
                            >
                                {{ formatCurrency(item.balance) }}
                            </span>
                        </template>

                        <!-- Actions Column -->
                        <template #cell-actions="{ item }">
                            <div class="flex items-center justify-center gap-1">
                                <Tooltip :content="$t('common.view')">
                                    <button
                                        type="button"
                                        @click="goToCustomer(item)"
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
                                <Tooltip
                                    v-if="can('crm.customers.update') || isAnyAdmin()"
                                    :content="$t('common.edit')"
                                >
                                    <button
                                        type="button"
                                        @click="openEditModal(item)"
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
                            </div>
                        </template>
                    </DataTable>
                </div>
            </div>
        </div>

        <!-- Customer Form Modal -->
        <CustomerFormModal
            :show="showModal"
            :customer="selectedCustomer"
            @close="closeModal"
            @saved="handleSaved"
        />

        <!-- Customer Import Modal -->
        <CustomerImportModal
            :show="showImportModal"
            @close="showImportModal = false"
            @imported="router.reload({ only: ['customers'] })"
        />
    </AppLayout>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import DataTable from '@/Components/Common/DataTable.vue';
import AppPagination from '@/Components/Common/AppPagination.vue';
import AppViewModeToggle from '@/Components/Common/AppViewModeToggle.vue';
import AppPrintButton from '@/Components/Common/AppPrintButton.vue';
import AppEmptyState from '@/Components/Common/AppEmptyState.vue';
import CustomerFormModal from '@/Components/Customers/CustomerFormModal.vue';
import CustomerImportModal from '@/Components/Customers/CustomerImportModal.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import CustomDatePicker from '@/Components/CustomDatePicker.vue';
import { useAutoOpenCreate } from '@/Composables/useAutoOpenCreate';

useAutoOpenCreate(() => openCreateModal());
import Tooltip from '@/Components/Tooltip.vue';
import { useToast } from '@/Composables/useToast';
import { usePermission } from '@/Composables/usePermission';
import { useNumberFormat } from '@/Composables/useNumberFormat';
import { useServerTable } from '@/Composables/useServerTable';

const props = defineProps({
    customers: {
        type: Object,
        default: () => ({}),
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
});

const { t } = useI18n();
const { success } = useToast();
const { can, isAnyAdmin } = usePermission();
const { toEnglish } = useNumberFormat();

// View Mode (persisted)
const viewMode = ref(localStorage.getItem('customersViewMode') || 'grid');
watch(viewMode, (newMode) => {
    localStorage.setItem('customersViewMode', newMode);
});

// Modals State
const showModal = ref(false);
const showImportModal = ref(false);
const selectedCustomer = ref(null);
const exporting = ref(false);

// Dropdown filter options
const typeOptions = computed(() => [
    { value: '', label: t('customers.filter.all_types') },
    { value: 'individual', label: t('customers.type.individual') },
    { value: 'company', label: t('customers.type.company') },
    { value: 'government', label: t('customers.type.government') },
]);

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
    routeUrl: '/app/customers',
    initialFilters: {
        search: props.filters?.search || '',
        type: props.filters?.type || '',
        date_from: props.filters?.date_from || '',
        date_to: props.filters?.date_to || '',
    },
    initialSort: {
        column: props.filters?.sort || '',
        direction: props.filters?.direction || 'desc',
    },
    debounceMs: 350,
});

// Synchronize props.filters when Inertia updates
watch(
    () => props.filters,
    (newFilters) => {
        if (newFilters) {
            if (newFilters.search !== undefined && newFilters.search !== tableFilters.search) {
                tableFilters.search = newFilters.search || '';
            }
            if (newFilters.type !== undefined && newFilters.type !== tableFilters.type) {
                tableFilters.type = newFilters.type || '';
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
            if (newFilters.sort !== undefined && newFilters.sort !== sortColumn.value) {
                sortColumn.value = newFilters.sort || '';
            }
            if (
                newFilters.direction !== undefined &&
                newFilters.direction !== sortDirection.value
            ) {
                sortDirection.value = newFilters.direction || 'desc';
            }
        }
    },
    { deep: true }
);

// Reset Filters
const hasActiveFilters = computed(() => {
    return Boolean(
        tableFilters.search ||
        tableFilters.type ||
        tableFilters.date_from ||
        tableFilters.date_to ||
        sortColumn.value
    );
});

const resetAllFilters = () => {
    cancelDebouncedSearch?.();
    tableFilters.search = '';
    tableFilters.type = '';
    tableFilters.date_from = '';
    tableFilters.date_to = '';
    sortColumn.value = '';
    sortDirection.value = 'desc';
    applyFilters();
};

// Customer List
const customerList = computed(() => props.customers?.data || []);

// Columns Definitions
const columns = computed(() => [
    { key: 'index', label: '#', align: 'center', width: 'w-12', sortable: false },
    { key: 'type', label: t('customers.columns.classification'), align: 'center', sortable: true },
    { key: 'name', label: t('customers.columns.name'), align: 'start', sortable: true },
    {
        key: 'contact_name',
        label: t('customers.columns.contact_name'),
        align: 'start',
        sortable: true,
    },
    { key: 'email', label: t('customers.columns.email'), align: 'start', sortable: true },
    { key: 'phone', label: t('customers.columns.phone'), align: 'center', sortable: true },
    { key: 'balance', label: t('customers.columns.balance'), align: 'end', sortable: true },
    { key: 'actions', label: t('common.actions'), align: 'center', width: 'w-24', sortable: false },
]);

// Navigation & Modals Handlers
function openCreateModal() {
    selectedCustomer.value = null;
    showModal.value = true;
}

function openEditModal(customer) {
    selectedCustomer.value = customer;
    showModal.value = true;
}

function closeModal() {
    showModal.value = false;
    selectedCustomer.value = null;
}

function goToCustomer(customer) {
    if (customer?.id) {
        router.visit(route('customers.show', customer.id));
    }
}

function handleSaved(customer) {
    const isNew = !selectedCustomer.value;
    closeModal();
    success(t('common.saved_success'));
    if (isNew && customer && customer.id) {
        router.visit(route('customers.show', customer.id));
    } else {
        router.reload({ only: ['customers'] });
    }
}

// Print & Export Handlers
function printCustomers() {
    const params = new URLSearchParams();
    if (tableFilters.search) params.set('search', tableFilters.search);
    if (tableFilters.type) params.set('type', tableFilters.type);
    if (tableFilters.date_from) params.set('date_from', tableFilters.date_from);
    if (tableFilters.date_to) params.set('date_to', tableFilters.date_to);
    if (sortColumn.value) {
        params.set('sort', sortColumn.value);
        params.set('direction', sortDirection.value);
    }
    const queryString = params.toString();
    const url = route('customers.print') + (queryString ? `?${queryString}` : '');
    window.open(url, '_blank');
}

function exportCustomers() {
    exporting.value = true;
    const params = new URLSearchParams();
    if (tableFilters.search) params.set('search', tableFilters.search);
    if (tableFilters.type) params.set('type', tableFilters.type);
    if (tableFilters.date_from) params.set('date_from', tableFilters.date_from);
    if (tableFilters.date_to) params.set('date_to', tableFilters.date_to);

    const url = route('customers.export') + '?' + params.toString();
    const link = document.createElement('a');
    link.href = url;
    link.setAttribute('download', '');
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);

    setTimeout(() => {
        exporting.value = false;
    }, 2000);
}

// Formatting Helpers
const { formatMoney: formatCurrency } = useNumberFormat();

function getWhatsAppNumber(customer) {
    if (!customer) return null;
    const phone = customer.whatsapp || customer.phone;
    if (!phone) return null;
    return phone.replace(/[^\d+]/g, '').replace(/^\+/, '');
}

function getTypeBadgeClass(type) {
    const classes = {
        company: 'bg-action-primary-soft text-action-primary border-action-primary/20',
        individual: 'bg-success-soft text-success border-success/20',
        government: 'bg-surface-muted text-text-secondary border-border',
    };
    return classes[type] || 'bg-surface-muted text-text-secondary border-border';
}

function getClassificationBadgeClass(classification) {
    const classes = {
        vip: 'bg-warning-soft text-warning border-warning/20',
        new: 'bg-success-soft text-success border-success/20',
        potential: 'bg-action-primary-soft text-action-primary border-action-primary/20',
        regular: 'bg-surface-muted text-text-secondary border-border',
        inactive: 'bg-danger-soft text-danger border-danger/20',
    };
    return classes[classification] || classes.regular;
}
</script>
