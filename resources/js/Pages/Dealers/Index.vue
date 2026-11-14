<template>
    <Head :title="$t('dealers.title')" />
    <AppLayout :title="$t('dealers.title')">
        <div class="space-y-6">
            <PageHeader
                :title="$t('dealers.title')"
                :subtitle="$t('dealers.subtitle')"
                :totalCount="dealers ? toEnglish(dealers.total) : null"
                :countLabel="$t('dealers.total_count')"
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
                            d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"
                        />
                    </svg>
                </template>

                <template #actions>
                    <div class="flex items-center gap-3">
                        <div
                            class="flex items-center gap-1.5 p-1 bg-surface-muted rounded-xl border border-border"
                        >
                            <!-- Export Button -->
                            <button
                                v-if="can('crm.customers.export') || isAnyAdmin()"
                                type="button"
                                @click="exportDealers"
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

                            <!-- Print Button -->
                            <AppPrintButton
                                v-if="can('crm.customers.print') || isAnyAdmin()"
                                variant="icon"
                                @click="printDealers"
                            />

                            <div class="w-px h-5 bg-border mx-0.5"></div>

                            <AppViewModeToggle v-model="viewMode" storage-key="dealersViewMode" />
                        </div>

                        <button
                            v-if="can('crm.customers.create') || isAnyAdmin()"
                            type="button"
                            @click="openCreateModal"
                            class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-action-primary hover:bg-action-primary-hover text-action-on text-xs font-bold shadow-xs transition-colors cursor-pointer"
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
                            <span>{{ $t('dealers.add') }}</span>
                        </button>
                    </div>
                </template>

                <template #filters>
                    <div class="grid grid-cols-1 md:grid-cols-12 gap-3 items-center">
                        <div class="md:col-span-7 relative group">
                            <input
                                v-model="filterForm.search"
                                type="text"
                                :placeholder="$t('dealers.search_placeholder')"
                                class="w-full ps-10 pe-4 py-2.5 text-sm rounded-xl border border-border bg-surface text-text-primary focus:ring-2 focus:ring-focus-ring focus:border-action-primary transition-all outline-none"
                                @input="debouncedFilter"
                            />
                            <div
                                class="absolute inset-y-0 start-0 flex items-center ps-3 pointer-events-none text-text-muted group-focus-within:text-action-primary transition-colors"
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
                        </div>

                        <div class="md:col-span-4">
                            <SearchableSelect
                                v-model="filterForm.status"
                                :options="statusFilterOptions"
                                option-label="label"
                                option-value="id"
                                :placeholder="$t('dealers.all_statuses')"
                                teleport
                                @update:modelValue="applyFilters"
                            />
                        </div>

                        <div class="md:col-span-1 flex justify-end">
                            <button
                                v-if="filterForm.search || filterForm.status"
                                type="button"
                                @click="resetFilters"
                                :title="$t('common.reset_filters')"
                                class="p-2.5 rounded-xl border border-border text-text-muted hover:text-text-primary hover:bg-surface-muted transition-colors cursor-pointer"
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
                                        d="M6 18L18 6M6 6l12 12"
                                    />
                                </svg>
                            </button>
                        </div>
                    </div>
                </template>
            </PageHeader>

            <!-- GRID VIEW -->
            <div v-if="viewMode === 'grid'">
                <AppEmptyState
                    v-if="!dealers?.data?.length"
                    :title="$t('dealers.empty_title')"
                    :description="$t('dealers.empty_hint')"
                    :action-text="
                        can('crm.customers.create') || isAnyAdmin() ? $t('dealers.add') : ''
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
                                d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"
                            />
                        </svg>
                    </template>
                </AppEmptyState>

                <div v-else>
                    <div
                        class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-5 gap-4"
                    >
                        <div
                            v-for="dealer in dealers.data"
                            :key="dealer.id"
                            @click="goToDealer(dealer)"
                            class="group relative flex flex-col justify-between h-[320px] bg-surface rounded-2xl border border-border hover:border-action-primary hover:shadow-md transition-all duration-200 cursor-pointer overflow-hidden p-5"
                        >
                            <!-- Watermark Initial -->
                            <div
                                class="absolute -end-3 -bottom-4 opacity-[0.04] pointer-events-none select-none group-hover:scale-105 transition-transform"
                            >
                                <span class="text-[140px] font-black italic text-action-primary">
                                    {{ (dealer.name || '').charAt(0) }}
                                </span>
                            </div>

                            <!-- Top Section -->
                            <div class="relative z-10 space-y-3">
                                <div class="flex items-start justify-between gap-2">
                                    <div class="min-w-0">
                                        <h3
                                            class="text-base font-bold text-text-primary group-hover:text-action-primary transition-colors line-clamp-1"
                                        >
                                            {{ dealer.name }}
                                        </h3>
                                        <p
                                            v-if="dealer.name_en"
                                            class="text-[11px] text-text-muted truncate"
                                            dir="ltr"
                                        >
                                            {{ dealer.name_en }}
                                        </p>
                                    </div>
                                </div>

                                <!-- Contact & Meta Info -->
                                <div class="space-y-1.5 text-xs text-text-secondary pt-1">
                                    <p
                                        v-if="dealer.contact_name"
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
                                        <span class="truncate">{{ dealer.contact_name }}</span>
                                    </p>
                                    <p
                                        v-if="dealer.phone"
                                        class="font-mono flex items-center gap-1.5"
                                        dir="ltr"
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
                                                d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"
                                            />
                                        </svg>
                                        <span>{{ dealer.phone }}</span>
                                    </p>
                                    <p
                                        v-if="dealer.tax_number"
                                        class="font-mono flex items-center gap-1.5 text-text-muted"
                                        dir="ltr"
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
                                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                                            />
                                        </svg>
                                        <span class="truncate">{{ dealer.tax_number }}</span>
                                    </p>
                                </div>
                            </div>

                            <!-- Bottom Section: Stats, Balance & Quick Actions -->
                            <div class="relative z-10 pt-3 border-t border-border/60 space-y-2.5">
                                <div
                                    class="flex items-center justify-between text-[11px] text-text-muted"
                                >
                                    <span>
                                        {{ $t('dealers.columns.vehicles_count') }}:
                                        <strong class="font-mono text-text-primary">
                                            {{ toEnglish(dealer.vehicles_count ?? 0) }}
                                        </strong>
                                    </span>
                                    <span>
                                        {{ $t('dealers.columns.work_orders_count') }}:
                                        <strong class="font-mono text-text-primary">
                                            {{ toEnglish(dealer.work_orders_count ?? 0) }}
                                        </strong>
                                    </span>
                                </div>

                                <div class="flex items-center justify-between text-xs">
                                    <span class="text-text-secondary">
                                        {{ $t('dealers.columns.outstanding_balance') }}
                                    </span>
                                    <span
                                        class="font-mono font-bold"
                                        :class="
                                            Number(dealer.outstanding_balance) > 0
                                                ? 'text-amber-600 dark:text-amber-400'
                                                : 'text-emerald-600 dark:text-emerald-400'
                                        "
                                        dir="ltr"
                                    >
                                        {{ formatMoney(dealer.outstanding_balance) }}
                                        {{ $t('common.currency') }}
                                    </span>
                                </div>

                                <div class="grid grid-cols-2 gap-2 pt-0.5">
                                    <a
                                        v-if="dealer.phone"
                                        :href="`tel:${dealer.phone}`"
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
                                        v-if="dealer.phone"
                                        :href="`https://wa.me/${getWhatsAppNumber(dealer)}`"
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
                    <div v-if="dealers?.links?.length >= 3" class="mt-6">
                        <AppPagination
                            :links="dealers.links"
                            :from="dealers.from"
                            :to="dealers.to"
                            :total="dealers.total"
                        />
                    </div>
                </div>
            </div>

            <!-- LIST VIEW (DataTable) -->
            <div v-else>
                <div v-if="dealers?.data?.length" class="space-y-4">
                    <DataTable
                        :columns="columns"
                        :data="dealers"
                        :sort-column="filters.sort || 'id'"
                        :sort-direction="filters.direction || 'desc'"
                        @sort="handleSort"
                    >
                        <template #cell-name="{ item: row }">
                            <div class="flex flex-col">
                                <Link
                                    :href="route('app.dealers.show', row.id)"
                                    class="font-bold text-text-primary hover:text-action-primary transition-colors"
                                >
                                    {{ row.name }}
                                </Link>
                                <span v-if="row.name_en" class="text-xs text-text-muted" dir="ltr">
                                    {{ row.name_en }}
                                </span>
                            </div>
                        </template>

                        <template #cell-contact="{ item: row }">
                            <div class="flex flex-col text-xs space-y-0.5">
                                <span
                                    v-if="row.contact_name"
                                    class="font-semibold text-text-primary"
                                >
                                    {{ row.contact_name }}
                                </span>
                                <span
                                    v-if="row.phone"
                                    class="font-mono text-text-secondary"
                                    dir="ltr"
                                >
                                    {{ row.phone }}
                                </span>
                            </div>
                        </template>

                        <template #cell-tax_number="{ item: row }">
                            <span class="font-mono text-xs text-text-secondary" dir="ltr">
                                {{ row.tax_number || '-' }}
                            </span>
                        </template>

                        <template #cell-vehicles_count="{ item: row }">
                            <span class="font-mono font-bold text-text-primary">
                                {{ toEnglish(row.vehicles_count ?? 0) }}
                            </span>
                        </template>

                        <template #cell-work_orders_count="{ item: row }">
                            <span class="font-mono font-bold text-text-primary">
                                {{ toEnglish(row.work_orders_count ?? 0) }}
                            </span>
                        </template>

                        <template #cell-outstanding_balance="{ item: row }">
                            <span
                                class="font-mono font-bold"
                                :class="
                                    Number(row.outstanding_balance) > 0
                                        ? 'text-amber-600 dark:text-amber-400'
                                        : 'text-emerald-600 dark:text-emerald-400'
                                "
                            >
                                {{ formatMoney(row.outstanding_balance) }}
                                {{ $t('common.currency') }}
                            </span>
                        </template>

                        <template #cell-is_active="{ item: row }">
                            <AppToggle
                                :model-value="Boolean(row.is_active)"
                                size="sm"
                                color="success"
                                :disabled="!(can('crm.customers.update') || isAnyAdmin())"
                                @update:modelValue="toggleDealerStatus(row)"
                            />
                        </template>

                        <template #cell-actions="{ item: row }">
                            <div class="flex items-center justify-end gap-1.5">
                                <Link
                                    :href="route('app.dealers.show', row.id)"
                                    :title="$t('common.view')"
                                    class="p-2 rounded-lg text-text-secondary hover:text-action-primary hover:bg-surface-muted transition-colors"
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
                                </Link>
                                <button
                                    v-if="can('crm.customers.update') || isAnyAdmin()"
                                    type="button"
                                    @click="openEditModal(row)"
                                    :title="$t('common.edit')"
                                    class="p-2 rounded-lg text-text-secondary hover:text-action-primary hover:bg-surface-muted transition-colors cursor-pointer"
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
                                    v-if="can('crm.customers.delete') || isAnyAdmin()"
                                    type="button"
                                    @click="confirmDeleteDealer(row)"
                                    :title="$t('common.delete')"
                                    class="p-2 rounded-lg text-text-secondary hover:text-danger hover:bg-danger/10 transition-colors cursor-pointer"
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
                        </template>
                    </DataTable>
                </div>

                <AppEmptyState
                    v-else
                    :title="$t('dealers.empty_title')"
                    :description="$t('dealers.empty_hint')"
                    :action-text="
                        can('crm.customers.create') || isAnyAdmin() ? $t('dealers.add') : ''
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
                                d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"
                            />
                        </svg>
                    </template>
                </AppEmptyState>
            </div>
        </div>

        <DealerFormModal
            :show="showFormModal"
            :dealer="selectedDealer"
            :types="types"
            @close="showFormModal = false"
        />

        <ConfirmModal
            :show="showDeleteModal"
            :title="$t('dealers.show.confirm_delete_title')"
            :message="$t('dealers.show.confirm_delete_message')"
            :confirm-text="$t('common.delete')"
            variant="danger"
            @confirm="executeDelete"
            @close="showDeleteModal = false"
        />
    </AppLayout>
</template>

<script setup>
import { ref, reactive, computed, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageHeader from '@/Components/PageHeader.vue';
import DataTable from '@/Components/Common/DataTable.vue';
import AppPagination from '@/Components/Common/AppPagination.vue';
import AppViewModeToggle from '@/Components/Common/AppViewModeToggle.vue';
import AppPrintButton from '@/Components/Common/AppPrintButton.vue';
import AppEmptyState from '@/Components/Common/AppEmptyState.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import AppToggle from '@/Components/Common/AppToggle.vue';
import ConfirmModal from '@/Components/ConfirmModal.vue';
import DealerFormModal from '@/Components/Dealers/DealerFormModal.vue';
import { usePermission } from '@/Composables/usePermission';
import { useNumberFormat } from '@/Composables/useNumberFormat';
import { useAutoOpenCreate } from '@/Composables/useAutoOpenCreate';

useAutoOpenCreate(() => openCreateModal());

const { t } = useI18n();
const { can, isAnyAdmin } = usePermission();
const { toEnglish } = useNumberFormat();

const viewMode = ref(localStorage.getItem('dealersViewMode') || 'grid');
watch(viewMode, (newMode) => {
    localStorage.setItem('dealersViewMode', newMode);
});

const props = defineProps({
    dealers: {
        type: Object,
        required: true,
    },
    types: {
        type: Array,
        default: () => ['insurance', 'fleet', 'rental', 'corporate'],
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
});

const filterForm = reactive({
    search: props.filters.search || '',
    status: props.filters.status || '',
    sort: props.filters.sort || '',
    direction: props.filters.direction || 'desc',
});

const showFormModal = ref(false);
const selectedDealer = ref(null);
const showDeleteModal = ref(false);
const dealerToDelete = ref(null);
const exporting = ref(false);

const statusFilterOptions = computed(() => [
    { id: '', label: t('dealers.all_statuses'), name: t('dealers.all_statuses') },
    { id: 'active', label: t('dealers.status_active'), name: t('dealers.status_active') },
    { id: 'inactive', label: t('dealers.status_inactive'), name: t('dealers.status_inactive') },
]);

const columns = computed(() => [
    { key: 'name', label: t('dealers.columns.name'), sortable: true },
    { key: 'contact', label: t('dealers.columns.contact') },
    { key: 'tax_number', label: t('dealers.columns.tax_number') },
    { key: 'vehicles_count', label: t('dealers.columns.vehicles_count'), align: 'center' },
    { key: 'work_orders_count', label: t('dealers.columns.work_orders_count'), align: 'center' },
    { key: 'outstanding_balance', label: t('dealers.columns.outstanding_balance'), align: 'end' },
    { key: 'is_active', label: t('dealers.columns.status'), sortable: true, align: 'center' },
    { key: 'actions', label: t('dealers.columns.actions'), align: 'end' },
]);

let searchTimeout = null;
const debouncedFilter = () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => applyFilters(), 300);
};

const applyFilters = () => {
    router.get(
        route('app.dealers.index'),
        {
            search: filterForm.search || undefined,
            status: filterForm.status || undefined,
            sort: filterForm.sort || undefined,
            direction: filterForm.sort ? filterForm.direction : undefined,
        },
        { preserveState: true, preserveScroll: true }
    );
};

const resetFilters = () => {
    filterForm.search = '';
    filterForm.status = '';
    filterForm.sort = '';
    filterForm.direction = 'desc';
    applyFilters();
};

const handleSort = (key) => {
    if (filterForm.sort === key) {
        filterForm.direction = filterForm.direction === 'asc' ? 'desc' : 'asc';
    } else {
        filterForm.sort = key;
        filterForm.direction = 'asc';
    }
    applyFilters();
};

const openCreateModal = () => {
    selectedDealer.value = null;
    showFormModal.value = true;
};

const openEditModal = (dealer) => {
    selectedDealer.value = dealer;
    showFormModal.value = true;
};

const toggleDealerStatus = (dealer) => {
    router.patch(route('app.dealers.toggle', dealer.id), {}, { preserveScroll: true });
};

const confirmDeleteDealer = (dealer) => {
    dealerToDelete.value = dealer;
    showDeleteModal.value = true;
};

const executeDelete = () => {
    if (!dealerToDelete.value) return;
    router.delete(route('app.dealers.destroy', dealerToDelete.value.id), {
        preserveScroll: true,
        onFinish: () => {
            showDeleteModal.value = false;
            dealerToDelete.value = null;
        },
    });
};

const formatMoney = (val) => {
    return new Intl.NumberFormat('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(Number(val) || 0);
};

const goToDealer = (dealer) => {
    router.visit(route('app.dealers.show', dealer.id));
};

const buildFilterQueryParams = () => {
    const params = new URLSearchParams();
    if (filterForm.search) params.set('search', filterForm.search);
    if (filterForm.status) params.set('status', filterForm.status);
    if (filterForm.sort) {
        params.set('sort', filterForm.sort);
        params.set('direction', filterForm.direction || 'desc');
    }
    return params.toString();
};

const printDealers = () => {
    const queryString = buildFilterQueryParams();
    const url = route('app.dealers.print') + (queryString ? `?${queryString}` : '');
    window.open(url, '_blank');
};

const exportDealers = () => {
    exporting.value = true;
    const queryString = buildFilterQueryParams();
    const url = route('app.dealers.export') + (queryString ? `?${queryString}` : '');

    const link = document.createElement('a');
    link.href = url;
    link.setAttribute('download', '');
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);

    setTimeout(() => {
        exporting.value = false;
    }, 2000);
};

const getWhatsAppNumber = (dealer) => {
    let clean = String(dealer?.phone || '').replace(/\D/g, '');
    if (clean.startsWith('05')) clean = '966' + clean.slice(1);
    return clean;
};
</script>
