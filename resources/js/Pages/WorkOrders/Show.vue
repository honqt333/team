<template>
    <Head :title="workOrder.work_order_number" />
    <AppLayout>
        <div class="space-y-6">
            <!-- Header Actions Bar (extracted to WorkOrderHeader) -->
            <WorkOrderHeader
                :work-order="workOrder"
                :is-read-only="isReadOnly"
                :balance="workOrderBalance"
                @print="showPrintModal = true"
                @payments="showPaymentsListModal = true"
                @edit="showEditModal = true"
                @change-status="changeStatus"
                @create-deferred-invoice="showDeferredInvoiceModal = true"
                @create-credit-note="showCreditNoteModal = true"
            />

            <!-- Partner Payment Badge & Multi-Invoice / Return Pipe Header -->
            <div
                v-if="workOrder.dealer_id || workOrder.dealer || allLinkedInvoices.length > 0"
                class="flex flex-wrap items-center justify-between gap-3 bg-white dark:bg-gray-800 px-4 py-3 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-xs"
            >
                <div class="flex flex-wrap items-center gap-2">
                    <span
                        v-if="workOrder.dealer_id || workOrder.dealer"
                        class="inline-flex items-center gap-1.5 px-3 py-1 rounded-xl text-xs font-black bg-emerald-50 text-emerald-700 border border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-300 dark:border-emerald-800/60"
                    >
                        <svg
                            class="w-3.5 h-3.5 text-emerald-600 dark:text-emerald-400"
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
                        <span>{{ $t('work_orders.paid_by_partner') }}</span>
                        <span v-if="workOrder.dealer?.name" class="font-bold">
                            ({{ workOrder.dealer.name }})
                        </span>
                    </span>
                </div>

                <!-- Linked Invoices Badges -->
                <div v-if="allLinkedInvoices.length > 0" class="flex flex-wrap items-center gap-2">
                    <LinkedInvoiceBadge
                        v-for="inv in allLinkedInvoices"
                        :key="inv.id"
                        :href="route('app.invoices.show', inv.id)"
                        :invoice="inv"
                    />
                </div>
            </div>

            <!-- Top Section: Financial Summary & Customer Info -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- 1. Right Card: Vehicle & Customer Info (extracted to WorkOrderCustomerCard) -->
                <WorkOrderCustomerCard :work-order="workOrder" :colors="colors" />

                <!-- 2. Left Card: Financial Summary (extracted to WorkOrderFinancialSummary) -->
                <WorkOrderFinancialSummary
                    :work-order="workOrder"
                    :totals="totals"
                    :has-tax="hasTax"
                    :tax-rate="workOrder.tax_rate_snapshot || 15"
                    :total-paid="workOrderTotalPaid"
                    :bad-debt="workOrderBadDebt"
                    :balance="workOrderBalance"
                    :excess-paid="workOrderExcessPaid"
                    :credit-notes-total="workOrderTotalCreditNotes"
                />
            </div>

            <!-- Main Content Container with Info Cards -->
            <div
                class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700"
            >
                <!-- Info Cards (extracted to WorkOrderInfoCards) -->
                <WorkOrderInfoCards :work-order="workOrder" :is-read-only="isReadOnly" />

                <!-- Customer Complaint & Initial Assessment (extracted to WorkOrderComplaintAssessment) -->
                <WorkOrderComplaintAssessment :work-order="workOrder" />

                <WorkOrderSuggestionsPanel
                    v-if="!isReadOnly && canUseSuggestions"
                    :work-order="workOrder"
                    :endpoint="route('work-orders.suggestions', workOrder.id)"
                    @add="reviewSuggestedItem"
                />

                <!-- Tabs container (extracted to WorkOrderTabsContainer) -->
                <WorkOrderTabsContainer :tabs="tabs" v-model:active-tab="activeTab">
                    <!-- Tab content is rendered inside the container's slot. -->

                    <!-- Services Tab (extracted to WorkOrderServicesTab) -->
                    <div v-if="activeTab === 'services'" key="tab-services">
                        <WorkOrderServicesTab
                            :work-order="workOrder"
                            :items-by-department="itemsByDepartment"
                            :display-departments="displayDepartments"
                            :available-departments="availableDepartments"
                            :departments="departments"
                            :is-read-only="isReadOnly"
                            :supplier-service-allocations="linkedServiceAllocations"
                            @unlink-supplier-service="unlinkSupplierServiceMapping"
                            @add-department="addDepartment"
                            @remove-department="removeDepartment"
                            @add-service="openAddServiceModal"
                            @edit-item="openEditServiceModal"
                            @assign-technician="openServiceTechniciansModal"
                            @print-department="handlePrintDepartment"
                        />
                    </div>

                    <!-- Notes Tab (extracted to WorkOrderNotesTab) -->
                    <div v-if="activeTab === 'notes'" key="tab-notes">
                        <WorkOrderNotesTab
                            :notes="allNotes"
                            :is-read-only="isReadOnly"
                            @open-service-notes="openServiceNotesModal"
                            @delete-note="handleDeleteNote"
                            @open-add-note="showAddNoteModal = true"
                        />
                    </div>

                    <!-- Spare Parts Tab -->
                    <div v-if="activeTab === 'parts'" key="tab-parts" class="space-y-4">
                        <div
                            v-if="externalPartsList.length > 0"
                            class="flex flex-wrap items-center gap-2 bg-amber-50/60 dark:bg-amber-950/20 px-4 py-3 rounded-2xl border border-amber-200/70 dark:border-amber-800/40"
                        >
                            <span
                                class="px-2.5 py-1 rounded-lg text-xs font-black bg-amber-500 text-white"
                            >
                                {{ $t('work_orders.parts.external_purchase') }}
                            </span>
                            <div class="flex flex-wrap items-center gap-2">
                                <span
                                    v-for="extPart in externalPartsList"
                                    :key="extPart.id"
                                    class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-bold bg-white dark:bg-gray-800 text-gray-800 dark:text-gray-200 border border-amber-200 dark:border-amber-800/50"
                                >
                                    <span>{{ extPart.name }}</span>
                                    <span
                                        class="px-1.5 py-0.5 rounded text-[10px] font-black bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300"
                                    >
                                        {{ $t('work_orders.parts.external_purchase') }}
                                    </span>
                                </span>
                            </div>
                        </div>

                        <!-- Parts Display Component -->
                        <PartsDisplay
                            :parts="normalizedPartsForDisplay"
                            :read-only="isReadOnly"
                            :part-allocations="linkedPartAllocations"
                            @unlink-part-allocation="unlinkPartAllocationMapping"
                            storage-key="work_orders_parts_view_mode"
                            :empty-message="$t('work_orders.show.no_parts')"
                            :add-button-text="$t('inventory.parts.add_to_wo')"
                            @delete="deleteWorkOrderPart"
                            @edit="editWorkOrderPart"
                            @add="openAddPartModal"
                            @click-service="handlePartServiceClick"
                        />
                    </div>

                    <!-- Technicians Tab -->
                    <div v-if="activeTab === 'technicians'" key="tab-technicians">
                        <TechniciansSection
                            :work-order="workOrder"
                            :items-by-department="itemsByDepartment"
                            :technicians="technicians"
                            :read-only="isReadOnly"
                            :labor-entries="laborEntries"
                            :server-now="serverNow"
                            @click-service="openServiceTechniciansModal"
                        />
                    </div>

                    <!-- Payments Tab -->
                    <div v-if="activeTab === 'payments'" key="tab-payments">
                        <PaymentsSection
                            :work-order-id="workOrder.id"
                            :payments="workOrder.payments || []"
                            :grand-total="workOrderTotal"
                            :total-paid="workOrderTotalPaid"
                            :bad-debt="workOrderBadDebt"
                            :balance="workOrderBalance"
                            :excess-paid="workOrderExcessPaid"
                            :read-only="isReadOnly"
                            :has-invoice="!!workOrder.invoice"
                            :status="workOrder.status"
                            @refresh="refreshWorkOrder"
                        />
                    </div>

                    <!-- Condition Report Tab -->
                    <div
                        v-if="activeTab === 'condition'"
                        key="tab-condition"
                        class="max-w-4xl mx-auto"
                    >
                        <VehicleConditionReport
                            v-model:damageMarks="conditionDamageMarks"
                            v-model:fuelLevel="conditionFuelLevel"
                            :readonly="isReadOnly"
                            :class="{ 'pointer-events-none': isReadOnly }"
                        />
                    </div>

                    <!-- Photos Tab -->
                    <div v-if="activeTab === 'photos'" key="tab-photos">
                        <WorkOrderPhotosTab
                            :photos="workOrder.photos || []"
                            :is-read-only="isReadOnly"
                            @add="showPhotoModal = true"
                            @delete="deletePhoto"
                        />
                    </div>

                    <!-- Activities Tab -->
                    <div v-if="activeTab === 'activities'" key="tab-activities">
                        <WorkOrderActivitiesTab :activities="workOrder.activities || []" />
                    </div>

                    <!-- Attachments Tab -->
                    <div v-if="activeTab === 'attachments'" key="tab-attachments">
                        <WorkOrderAttachmentsTab
                            :attachments="workOrder.attachments || []"
                            :is-read-only="isReadOnly"
                            @add="showAttachmentModal = true"
                            @delete="deleteAttachment"
                        />
                    </div>

                    <div v-if="activeTab === 'inspections'" key="tab-inspections">
                        <InspectionChecklist :work-order="workOrder" :read-only="isReadOnly" />
                    </div>

                    <!-- Signatures Tab -->
                    <div v-if="activeTab === 'signatures'" key="tab-signatures">
                        <WorkOrderSignatures :work-order="workOrder" :is-read-only="isReadOnly" />
                    </div>

                    <!-- Purchase Invoices Tab -->
                    <div
                        v-if="activeTab === 'purchase_invoices'"
                        id="ticketPurchaseInvoices"
                        key="tab-purchase-invoices"
                        class="space-y-6"
                    >
                        <!-- A. Informative Alert Notice & Profitability / Cost vs Revenue Banner -->
                        <div class="space-y-3">
                            <!-- Informative Alert Notice -->
                            <div
                                class="flex items-start gap-3 p-4 rounded-2xl border border-sky-200/80 dark:border-sky-800/60 bg-sky-50/70 dark:bg-sky-950/30 text-sky-900 dark:text-sky-200"
                            >
                                <span class="text-xl shrink-0 mt-0.5">ℹ️</span>
                                <div class="text-xs sm:text-sm font-medium leading-relaxed">
                                    {{ $t('work_orders.purchases.profitability_notice') }}
                                </div>
                            </div>

                            <!-- Profitability / Cost vs Revenue Banner -->
                            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3">
                                <!-- 1. External Parts Cost -->
                                <div
                                    class="p-3.5 rounded-2xl border border-amber-200/70 dark:border-amber-800/50 bg-amber-50/50 dark:bg-amber-950/20 flex flex-col justify-between"
                                >
                                    <div
                                        class="flex items-center gap-1.5 text-amber-800 dark:text-amber-300"
                                    >
                                        <span
                                            class="w-2 h-2 rounded-full bg-amber-500 shrink-0"
                                        ></span>
                                        <span class="text-[11px] font-bold leading-tight truncate">
                                            {{ $t('work_orders.purchases.external_parts_cost') }}
                                        </span>
                                    </div>
                                    <p
                                        class="text-sm font-black font-mono mt-2 text-amber-950 dark:text-amber-100"
                                        dir="ltr"
                                    >
                                        {{ formatPrice(externalPartsCost) }}
                                    </p>
                                </div>

                                <!-- 2. Warehouse Parts Cost -->
                                <div
                                    class="p-3.5 rounded-2xl border border-sky-200/70 dark:border-sky-800/50 bg-sky-50/50 dark:bg-sky-950/20 flex flex-col justify-between"
                                >
                                    <div
                                        class="flex items-center gap-1.5 text-sky-800 dark:text-sky-300"
                                    >
                                        <span
                                            class="w-2 h-2 rounded-full bg-sky-500 shrink-0"
                                        ></span>
                                        <span class="text-[11px] font-bold leading-tight truncate">
                                            {{ $t('work_orders.purchases.warehouse_parts_cost') }}
                                        </span>
                                    </div>
                                    <p
                                        class="text-sm font-black font-mono mt-2 text-sky-950 dark:text-sky-100"
                                        dir="ltr"
                                    >
                                        {{ formatPrice(warehousePartsCost) }}
                                    </p>
                                </div>

                                <!-- 3. Supplier Services Cost -->
                                <div
                                    class="p-3.5 rounded-2xl border border-purple-200/70 dark:border-purple-800/50 bg-purple-50/50 dark:bg-purple-950/20 flex flex-col justify-between"
                                >
                                    <div
                                        class="flex items-center gap-1.5 text-purple-800 dark:text-purple-300"
                                    >
                                        <span
                                            class="w-2 h-2 rounded-full bg-purple-500 shrink-0"
                                        ></span>
                                        <span class="text-[11px] font-bold leading-tight truncate">
                                            {{ $t('work_orders.purchases.supplier_services_cost') }}
                                        </span>
                                    </div>
                                    <p
                                        class="text-sm font-black font-mono mt-2 text-purple-950 dark:text-purple-100"
                                        dir="ltr"
                                    >
                                        {{ formatPrice(supplierServicesCost) }}
                                    </p>
                                </div>

                                <!-- 4. Total Purchases Cost -->
                                <div
                                    class="p-3.5 rounded-2xl border border-rose-200/70 dark:border-rose-800/50 bg-rose-50/50 dark:bg-rose-950/20 flex flex-col justify-between"
                                >
                                    <div
                                        class="flex items-center gap-1.5 text-rose-800 dark:text-rose-300"
                                    >
                                        <span
                                            class="w-2 h-2 rounded-full bg-rose-500 shrink-0"
                                        ></span>
                                        <span class="text-[11px] font-bold leading-tight truncate">
                                            {{ $t('work_orders.purchases.total_purchases_cost') }}
                                        </span>
                                    </div>
                                    <p
                                        class="text-sm font-black font-mono mt-2 text-rose-950 dark:text-rose-100"
                                        dir="ltr"
                                    >
                                        {{ formatPrice(totalPurchasesCost) }}
                                    </p>
                                </div>

                                <!-- 5. Work Order Revenue -->
                                <div
                                    class="p-3.5 rounded-2xl border border-blue-200/70 dark:border-blue-800/50 bg-blue-50/50 dark:bg-blue-950/20 flex flex-col justify-between"
                                >
                                    <div
                                        class="flex items-center gap-1.5 text-blue-800 dark:text-blue-300"
                                    >
                                        <span
                                            class="w-2 h-2 rounded-full bg-blue-500 shrink-0"
                                        ></span>
                                        <span class="text-[11px] font-bold leading-tight truncate">
                                            {{ $t('work_orders.purchases.work_order_revenue') }}
                                        </span>
                                    </div>
                                    <p
                                        class="text-sm font-black font-mono mt-2 text-blue-950 dark:text-blue-100"
                                        dir="ltr"
                                    >
                                        {{ formatPrice(workOrderRevenue) }}
                                    </p>
                                </div>

                                <!-- 6. Net Margin / Profit -->
                                <div
                                    :class="[
                                        'p-3.5 rounded-2xl border flex flex-col justify-between transition-colors',
                                        isNetMarginNegative
                                            ? 'border-rose-300 bg-rose-100/70 dark:bg-rose-950/50 dark:border-rose-800 text-rose-900 dark:text-rose-200'
                                            : 'border-emerald-300 bg-emerald-50 dark:bg-emerald-950/50 dark:border-emerald-800 text-emerald-900 dark:text-emerald-200',
                                    ]"
                                >
                                    <div class="flex items-center gap-1.5 font-bold">
                                        <span
                                            :class="[
                                                'w-2 h-2 rounded-full shrink-0',
                                                isNetMarginNegative
                                                    ? 'bg-rose-600'
                                                    : 'bg-emerald-600',
                                            ]"
                                        ></span>
                                        <span class="text-[11px] font-bold leading-tight truncate">
                                            {{ $t('work_orders.purchases.net_margin') }}
                                        </span>
                                    </div>
                                    <p
                                        :class="[
                                            'text-sm font-black font-mono mt-2',
                                            isNetMarginNegative
                                                ? 'text-rose-700 dark:text-rose-300 font-extrabold'
                                                : 'text-emerald-700 dark:text-emerald-300',
                                        ]"
                                        dir="ltr"
                                    >
                                        {{ formatNetMargin(netProfitMargin) }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Action Toolbar -->
                        <div
                            class="flex flex-wrap items-center justify-between gap-3 bg-surface-secondary/50 dark:bg-gray-800/60 p-4 rounded-2xl border border-border-subtle"
                        >
                            <div>
                                <h3 class="text-sm font-bold text-text-primary">
                                    {{ $t('work_orders.purchases.title') }}
                                </h3>
                            </div>
                            <div v-if="!isReadOnly" class="flex flex-wrap items-center gap-2">
                                <button
                                    type="button"
                                    @click="openAllocationModal"
                                    class="px-3.5 py-2 bg-action-primary hover:bg-action-primary-hover text-white text-xs font-bold rounded-xl flex items-center gap-1.5 transition-all shadow-sm cursor-pointer"
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
                                            stroke-width="2.5"
                                            d="M12 4v16m8-8H4"
                                        />
                                    </svg>
                                    {{ $t('work_orders.purchases.select_to_add') }}
                                </button>
                                <button
                                    type="button"
                                    @click="openQuickPurchaseModal"
                                    class="px-3.5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-xl flex items-center gap-1.5 transition-all shadow-sm cursor-pointer"
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
                                            stroke-width="2.5"
                                            d="M12 4v16m8-8H4"
                                        />
                                    </svg>
                                    {{ $t('work_orders.purchases.quick_create_invoice') }}
                                </button>
                            </div>
                        </div>

                        <!-- B. Three Dedicated Tables/Sections for Allocations -->

                        <!-- 1. External Parts Purchases Table -->
                        <div
                            class="rounded-2xl border border-border-subtle bg-surface overflow-hidden shadow-xs"
                        >
                            <div
                                class="flex items-center justify-between px-4 py-3 bg-amber-500/10 border-b border-border-subtle"
                            >
                                <div class="flex items-center gap-2">
                                    <span class="text-lg">🛒</span>
                                    <h4 class="text-xs font-bold text-text-primary">
                                        {{ $t('work_orders.purchases.external_parts_purchases') }}
                                    </h4>
                                    <span
                                        class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300 font-mono"
                                    >
                                        {{ externalPartAllocationsList.length }}
                                    </span>
                                </div>
                                <span
                                    class="text-xs font-black font-mono text-amber-700 dark:text-amber-300"
                                    dir="ltr"
                                >
                                    {{ formatPrice(externalPartsCost) }}
                                </span>
                            </div>

                            <div
                                v-if="externalPartAllocationsList.length > 0"
                                class="overflow-x-auto"
                            >
                                <DataTable structured class="min-w-full text-xs">
                                    <thead
                                        class="bg-surface-secondary/70 dark:bg-gray-800 text-text-secondary"
                                    >
                                        <tr>
                                            <th class="px-4 py-2.5 text-start font-semibold">#</th>
                                            <th class="px-4 py-2.5 text-start font-semibold">
                                                {{ $t('work_orders.purchases.purchased_part') }}
                                            </th>
                                            <th class="px-4 py-2.5 text-start font-semibold">
                                                {{ $t('invoices.purchases.invoice_number') }}
                                            </th>
                                            <th class="px-4 py-2.5 text-start font-semibold">
                                                {{ $t('purchasing.orders.supplier') }}
                                            </th>
                                            <th class="px-4 py-2.5 text-end font-semibold">
                                                {{ $t('work_orders.purchases.cost_price') }}
                                            </th>
                                            <th class="px-4 py-2.5 text-start font-semibold">
                                                {{ $t('work_orders.purchases.linked_part') }}
                                            </th>
                                            <th class="px-4 py-2.5 text-center font-semibold">
                                                {{ $t('common.quantity') }}
                                            </th>
                                            <th class="px-4 py-2.5 text-end font-semibold">
                                                {{ $t('common.total') }}
                                            </th>
                                            <th class="px-4 py-2.5 text-center font-semibold">
                                                {{ $t('common.actions') }}
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-border-subtle">
                                        <tr
                                            v-for="(alloc, idx) in externalPartAllocationsList"
                                            :key="'ext-' + alloc.id"
                                            class="hover:bg-surface-secondary/40 transition-colors"
                                        >
                                            <td class="px-4 py-3 font-mono text-text-secondary">
                                                {{ idx + 1 }}
                                            </td>
                                            <td class="px-4 py-3 font-bold text-text-primary">
                                                {{ alloc.description }}
                                            </td>
                                            <td class="px-4 py-3 font-mono">
                                                <Link
                                                    :href="
                                                        route(
                                                            'app.invoices.purchases.show',
                                                            alloc.invoice_id
                                                        )
                                                    "
                                                    class="text-action-primary hover:underline font-bold"
                                                >
                                                    {{ alloc.invoice_code || alloc.invoice_number }}
                                                </Link>
                                            </td>
                                            <td class="px-4 py-3">
                                                {{ alloc.supplier_name || '—' }}
                                            </td>
                                            <td class="px-4 py-3 text-end font-mono" dir="ltr">
                                                {{ formatPrice(alloc.unit_cost) }}
                                            </td>
                                            <td class="px-4 py-3">
                                                <!-- If linked to an external part -->
                                                <div
                                                    v-if="alloc.linked_part"
                                                    class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 text-xs font-bold text-emerald-800 dark:text-emerald-300"
                                                >
                                                    <span>🔗 {{ alloc.linked_part.name }}</span>
                                                    <button
                                                        v-if="!isReadOnly"
                                                        type="button"
                                                        @click="unlinkPartFromAllocation(alloc.id)"
                                                        class="text-rose-500 hover:text-rose-700 p-0.5 rounded cursor-pointer"
                                                        :title="
                                                            $t('work_orders.purchases.unlink_btn')
                                                        "
                                                    >
                                                        🗑️
                                                    </button>
                                                </div>
                                                <!-- If not linked: dropdown + link button -->
                                                <div
                                                    v-else-if="
                                                        !isReadOnly &&
                                                        externalPartsOptions.length > 0
                                                    "
                                                    class="flex items-center gap-1.5"
                                                >
                                                    <div class="w-44">
                                                        <SearchableSelect
                                                            v-model="pendingPartLinks[alloc.id]"
                                                            :options="externalPartsOptions"
                                                            option-label="label"
                                                            option-value="value"
                                                            :placeholder="
                                                                $t(
                                                                    'work_orders.purchases.select_part_to_link'
                                                                )
                                                            "
                                                            compact
                                                        />
                                                    </div>
                                                    <button
                                                        type="button"
                                                        :disabled="!pendingPartLinks[alloc.id]"
                                                        @click="
                                                            linkPartToAllocation(
                                                                alloc.id,
                                                                pendingPartLinks[alloc.id],
                                                                alloc
                                                            )
                                                        "
                                                        class="px-2.5 py-1.5 bg-amber-500 hover:bg-amber-600 disabled:opacity-40 text-white rounded-lg text-xs font-bold transition-colors cursor-pointer"
                                                    >
                                                        {{
                                                            $t('work_orders.purchases.link_action')
                                                        }}
                                                    </button>
                                                </div>
                                                <span v-else class="text-text-muted text-xs">
                                                    —
                                                </span>
                                            </td>
                                            <td class="px-4 py-3 text-center font-mono">
                                                {{ alloc.qty }}
                                            </td>
                                            <td
                                                class="px-4 py-3 text-end font-mono font-bold"
                                                dir="ltr"
                                            >
                                                {{ formatPrice(alloc.total) }}
                                            </td>
                                            <td class="px-4 py-3 text-center">
                                                <button
                                                    v-if="!isReadOnly"
                                                    type="button"
                                                    @click="deleteAllocation(alloc)"
                                                    class="text-rose-500 hover:text-rose-700 p-1 rounded transition-colors cursor-pointer"
                                                    :title="$t('common.delete')"
                                                >
                                                    🗑️
                                                </button>
                                            </td>
                                        </tr>
                                    </tbody>
                                </DataTable>
                            </div>
                            <div v-else class="text-center py-6 px-4 text-xs text-text-secondary">
                                {{ $t('work_orders.purchases.empty_external') }}
                            </div>
                        </div>

                        <!-- 2. Warehouse Stock Purchases Table -->
                        <div
                            class="rounded-2xl border border-border-subtle bg-surface overflow-hidden shadow-xs"
                        >
                            <div
                                class="flex items-center justify-between px-4 py-3 bg-sky-500/10 border-b border-border-subtle"
                            >
                                <div class="flex items-center gap-2">
                                    <span class="text-lg">🏭</span>
                                    <h4 class="text-xs font-bold text-text-primary">
                                        {{ $t('work_orders.purchases.warehouse_stock_purchases') }}
                                    </h4>
                                    <span
                                        class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-sky-100 text-sky-800 dark:bg-sky-900/40 dark:text-sky-300 font-mono"
                                    >
                                        {{ warehouseInvoicesList.length }}
                                    </span>
                                </div>
                                <span
                                    class="text-xs font-black font-mono text-sky-700 dark:text-sky-300"
                                    dir="ltr"
                                >
                                    {{ formatPrice(warehousePartsCost) }}
                                </span>
                            </div>

                            <div v-if="warehouseInvoicesList.length > 0" class="overflow-x-auto">
                                <DataTable structured class="min-w-full text-xs">
                                    <thead
                                        class="bg-surface-secondary/70 dark:bg-gray-800 text-text-secondary"
                                    >
                                        <tr>
                                            <th class="px-4 py-2.5 text-start font-semibold">#</th>
                                            <th class="px-4 py-2.5 text-start font-semibold">
                                                {{
                                                    $t('work_orders.purchases.part_or_description')
                                                }}
                                            </th>
                                            <th class="px-4 py-2.5 text-start font-semibold">
                                                {{ $t('invoices.purchases.invoice_number') }}
                                            </th>
                                            <th class="px-4 py-2.5 text-start font-semibold">
                                                {{ $t('purchasing.orders.supplier') }}
                                            </th>
                                            <th class="px-4 py-2.5 text-end font-semibold">
                                                {{ $t('work_orders.purchases.cost_price') }}
                                            </th>
                                            <th class="px-4 py-2.5 text-center font-semibold">
                                                {{ $t('common.quantity') }}
                                            </th>
                                            <th class="px-4 py-2.5 text-end font-semibold">
                                                {{ $t('common.total') }}
                                            </th>
                                            <th class="px-4 py-2.5 text-center font-semibold">
                                                {{ $t('common.status') }}
                                            </th>
                                            <th class="px-4 py-2.5 text-center font-semibold">
                                                {{ $t('common.actions') }}
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-border-subtle">
                                        <tr
                                            v-for="(winv, idx) in warehouseInvoicesList"
                                            :key="'wh-' + winv.id"
                                            class="hover:bg-surface-secondary/40 transition-colors"
                                        >
                                            <td class="px-4 py-3 font-mono text-text-secondary">
                                                {{ idx + 1 }}
                                            </td>
                                            <td class="px-4 py-3 font-bold text-text-primary">
                                                {{ winv.description }}
                                            </td>
                                            <td class="px-4 py-3 font-mono">
                                                <Link
                                                    :href="
                                                        route(
                                                            'app.invoices.purchases.show',
                                                            winv.id
                                                        )
                                                    "
                                                    class="text-action-primary hover:underline font-bold"
                                                >
                                                    {{ winv.code || winv.invoice_number }}
                                                </Link>
                                            </td>
                                            <td class="px-4 py-3">
                                                {{ winv.supplier?.name || '—' }}
                                            </td>
                                            <td class="px-4 py-3 text-end font-mono" dir="ltr">
                                                {{ formatPrice(winv.subtotal) }}
                                            </td>
                                            <td class="px-4 py-3 text-center font-mono">
                                                {{ winv.qty || 1 }}
                                            </td>
                                            <td
                                                class="px-4 py-3 text-end font-mono font-bold"
                                                dir="ltr"
                                            >
                                                {{ formatPrice(winv.total) }}
                                            </td>
                                            <td class="px-4 py-3 text-center">
                                                <span
                                                    class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-surface-secondary text-text-primary border border-border-subtle"
                                                >
                                                    {{
                                                        $t(
                                                            `invoices.purchases.statuses.${winv.status}`,
                                                            winv.status
                                                        )
                                                    }}
                                                </span>
                                            </td>
                                            <td class="px-4 py-3 text-center">
                                                <button
                                                    v-if="!isReadOnly"
                                                    type="button"
                                                    @click="unlinkPurchaseInvoice(winv)"
                                                    class="text-rose-500 hover:text-rose-700 p-1 rounded transition-colors cursor-pointer"
                                                    :title="$t('work_orders.purchases.unlink_btn')"
                                                >
                                                    🗑️
                                                </button>
                                            </td>
                                        </tr>
                                    </tbody>
                                </DataTable>
                            </div>
                            <div v-else class="text-center py-6 px-4 text-xs text-text-secondary">
                                {{ $t('work_orders.purchases.empty_warehouse') }}
                            </div>
                        </div>

                        <!-- 3. Supplier Services Table -->
                        <div
                            class="rounded-2xl border border-border-subtle bg-surface overflow-hidden shadow-xs"
                        >
                            <div
                                class="flex items-center justify-between px-4 py-3 bg-purple-500/10 border-b border-border-subtle"
                            >
                                <div class="flex items-center gap-2">
                                    <span class="text-lg">🤝</span>
                                    <h4 class="text-xs font-bold text-text-primary">
                                        {{ $t('work_orders.purchases.supplier_services') }}
                                    </h4>
                                    <span
                                        class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-purple-100 text-purple-800 dark:bg-purple-900/40 dark:text-purple-300 font-mono"
                                    >
                                        {{ supplierServiceAllocationsList.length }}
                                    </span>
                                </div>
                                <span
                                    class="text-xs font-black font-mono text-purple-700 dark:text-purple-300"
                                    dir="ltr"
                                >
                                    {{ formatPrice(supplierServicesCost) }}
                                </span>
                            </div>

                            <div
                                v-if="supplierServiceAllocationsList.length > 0"
                                class="overflow-x-auto"
                            >
                                <DataTable structured class="min-w-full text-xs">
                                    <thead
                                        class="bg-surface-secondary/70 dark:bg-gray-800 text-text-secondary"
                                    >
                                        <tr>
                                            <th class="px-4 py-2.5 text-start font-semibold">#</th>
                                            <th class="px-4 py-2.5 text-start font-semibold">
                                                {{ $t('work_orders.purchases.external_service') }}
                                            </th>
                                            <th class="px-4 py-2.5 text-start font-semibold">
                                                {{ $t('invoices.purchases.invoice_number') }}
                                            </th>
                                            <th class="px-4 py-2.5 text-start font-semibold">
                                                {{ $t('work_orders.purchases.service_supplier') }}
                                            </th>
                                            <th class="px-4 py-2.5 text-end font-semibold">
                                                {{ $t('work_orders.purchases.cost_price') }}
                                            </th>
                                            <th class="px-4 py-2.5 text-start font-semibold">
                                                {{ $t('work_orders.purchases.linked_service') }}
                                            </th>
                                            <th class="px-4 py-2.5 text-end font-semibold">
                                                {{ $t('common.total') }}
                                            </th>
                                            <th class="px-4 py-2.5 text-center font-semibold">
                                                {{ $t('common.actions') }}
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-border-subtle">
                                        <tr
                                            v-for="(alloc, idx) in supplierServiceAllocationsList"
                                            :key="'srv-' + alloc.id"
                                            class="hover:bg-surface-secondary/40 transition-colors"
                                        >
                                            <td class="px-4 py-3 font-mono text-text-secondary">
                                                {{ idx + 1 }}
                                            </td>
                                            <td class="px-4 py-3 font-bold text-text-primary">
                                                {{ alloc.description }}
                                            </td>
                                            <td class="px-4 py-3 font-mono">
                                                <Link
                                                    :href="
                                                        route(
                                                            'app.invoices.purchases.show',
                                                            alloc.invoice_id
                                                        )
                                                    "
                                                    class="text-action-primary hover:underline font-bold"
                                                >
                                                    {{ alloc.invoice_code || alloc.invoice_number }}
                                                </Link>
                                            </td>
                                            <td class="px-4 py-3">
                                                {{ alloc.supplier_name || '—' }}
                                            </td>
                                            <td class="px-4 py-3 text-end font-mono" dir="ltr">
                                                {{ formatPrice(alloc.unit_cost) }}
                                            </td>
                                            <td class="px-4 py-3">
                                                <!-- If linked to a work order service -->
                                                <div
                                                    v-if="alloc.linked_service"
                                                    class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 text-xs font-bold text-emerald-800 dark:text-emerald-300"
                                                >
                                                    <span>🔗 {{ alloc.linked_service.title }}</span>
                                                    <button
                                                        v-if="!isReadOnly"
                                                        type="button"
                                                        @click="
                                                            unlinkServiceFromAllocation(alloc.id)
                                                        "
                                                        class="text-rose-500 hover:text-rose-700 p-0.5 rounded cursor-pointer"
                                                        :title="
                                                            $t('work_orders.purchases.unlink_btn')
                                                        "
                                                    >
                                                        🗑️
                                                    </button>
                                                </div>
                                                <!-- If not linked: dropdown + link button -->
                                                <div
                                                    v-else-if="
                                                        !isReadOnly && serviceOptions.length > 0
                                                    "
                                                    class="flex items-center gap-1.5"
                                                >
                                                    <div class="w-44">
                                                        <SearchableSelect
                                                            v-model="pendingServiceLinks[alloc.id]"
                                                            :options="serviceOptions"
                                                            option-label="label"
                                                            option-value="value"
                                                            :placeholder="
                                                                $t(
                                                                    'work_orders.purchases.select_service_to_link'
                                                                )
                                                            "
                                                            compact
                                                        />
                                                    </div>
                                                    <button
                                                        type="button"
                                                        :disabled="!pendingServiceLinks[alloc.id]"
                                                        @click="
                                                            linkServiceToAllocation(
                                                                alloc.id,
                                                                pendingServiceLinks[alloc.id],
                                                                alloc
                                                            )
                                                        "
                                                        class="px-2.5 py-1.5 bg-purple-600 hover:bg-purple-700 disabled:opacity-40 text-white rounded-lg text-xs font-bold transition-colors cursor-pointer"
                                                    >
                                                        {{
                                                            $t('work_orders.purchases.link_action')
                                                        }}
                                                    </button>
                                                </div>
                                                <span v-else class="text-text-muted text-xs">
                                                    —
                                                </span>
                                            </td>
                                            <td
                                                class="px-4 py-3 text-end font-mono font-bold"
                                                dir="ltr"
                                            >
                                                {{ formatPrice(alloc.total) }}
                                            </td>
                                            <td class="px-4 py-3 text-center">
                                                <button
                                                    v-if="!isReadOnly"
                                                    type="button"
                                                    @click="deleteAllocation(alloc)"
                                                    class="text-rose-500 hover:text-rose-700 p-1 rounded transition-colors cursor-pointer"
                                                    :title="$t('common.delete')"
                                                >
                                                    🗑️
                                                </button>
                                            </td>
                                        </tr>
                                    </tbody>
                                </DataTable>
                            </div>
                            <div v-else class="text-center py-6 px-4 text-xs text-text-secondary">
                                {{ $t('work_orders.purchases.empty_services') }}
                            </div>
                        </div>
                    </div>
                </WorkOrderTabsContainer>
            </div>
        </div>

        <!-- Edit Modal -->
        <WorkOrderFormModal
            :show="showEditModal"
            :work-order="workOrder"
            :customers="customers"
            :dealers="dealers"
            :departments="departments"
            :makes="makes"
            :colors="colors"
            :modelsByMake="modelsByMake"
            @close="showEditModal = false"
            @saved="handleSaved"
        />

        <!-- Item Edit/Add Modal (unified) -->
        <WorkOrderServiceModal
            v-if="showItemModal || showServiceModal"
            :show="showItemModal || showServiceModal"
            :work-order="workOrder"
            :item="selectedItem"
            :department-id="selectedDepartmentId"
            :services="services"
            :technicians="technicians"
            :inventory-units="inventoryUnits"
            :warehouses="warehouses"
            :read-only="isReadOnly"
            :initial-tab="serviceModalInitialTab"
            :initial-service-id="suggestedServiceId"
            @close="showItemModal ? closeItemModal() : closeServiceModal()"
            @saved="showItemModal ? handleItemSaved() : handleServiceSaved()"
        />

        <!-- Add Part Modal -->
        <WorkOrderPartModal
            :show="showAddPartModal"
            :suggested-part="suggestedPart"
            :workOrder="workOrder"
            :part="selectedPartToEdit"
            :units="inventoryUnits"
            :warehouses="warehouses"
            :show-service-select="true"
            :workOrderItems="workOrder.items"
            @close="closeAddPartModal"
            @saved="handlePartSaved"
        />

        <!-- Issue More Modal -->
        <WorkOrderIssueMoreModal
            :show="showIssueMoreModal"
            :workOrder="workOrder"
            :part="editingIssueMorePart"
            @close="
                showIssueMoreModal = false;
                editingIssueMorePart = null;
            "
            @saved="onPartSaved"
        />

        <!-- Return Modal -->
        <WorkOrderReturnModal
            :show="showReturnModal"
            :workOrder="workOrder"
            :part="editingReturnPart"
            @close="
                showReturnModal = false;
                editingReturnPart = null;
            "
            @saved="onPartSaved"
        />

        <!-- Print Options Modal -->
        <PrintOptionsModal
            :show="showPrintModal"
            :work-order="workOrder"
            @close="showPrintModal = false"
            @print="handlePrint"
        />

        <!-- Payments List Modal (New) -->
        <PaymentsListModal
            :show="showPaymentsListModal"
            :work-order-id="workOrder.id"
            :payments="workOrder.payments || []"
            :grand-total="workOrderTotal"
            :total-paid="workOrderTotalPaid"
            :bad-debt="workOrderBadDebt"
            :balance="workOrderBalance"
            :excess-paid="workOrderExcessPaid"
            :has-invoice="!!workOrder.invoice"
            :status="workOrder.status"
            @close="showPaymentsListModal = false"
            @refresh="refreshWorkOrder"
        />
        <WorkOrderPhotoModal
            v-if="showPhotoModal"
            :show="showPhotoModal"
            :work-order="workOrder"
            @close="showPhotoModal = false"
            @saved="refreshWorkOrder"
        />

        <WorkOrderAttachmentModal
            v-if="showAttachmentModal"
            :show="showAttachmentModal"
            :work-order="workOrder"
            @close="showAttachmentModal = false"
            @saved="refreshWorkOrder"
        />

        <!-- Add Note Modal -->
        <BaseModal :show="showAddNoteModal" @close="showAddNoteModal = false" size="md">
            <template #title>
                {{ $t('work_orders.item.add_note') }}
            </template>

            <form @submit.prevent="handleAddNote" class="space-y-4 text-right">
                <div class="space-y-1">
                    <!-- Note Content -->
                    <label
                        class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1"
                    >
                        {{ $t('work_orders.item.tab_notes') }}
                    </label>
                    <textarea
                        v-model="newNoteContent"
                        required
                        rows="5"
                        :placeholder="$t('work_orders.item.note_placeholder')"
                        class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-white px-3 py-2 text-sm focus:ring-2 focus:ring-focus-ring focus:border-action-primary resize-none"
                    ></textarea>
                </div>
            </form>

            <template #footer>
                <button
                    type="button"
                    @click="showAddNoteModal = false"
                    class="px-5 py-2 text-sm font-semibold text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/50 rounded-xl transition-all"
                >
                    {{ $t('common.cancel') }}
                </button>
                <button
                    type="submit"
                    @click="handleAddNote"
                    :disabled="isSubmittingNote || !newNoteContent.trim()"
                    class="px-5 py-2 text-sm font-semibold text-white bg-teal-600 hover:bg-teal-700 disabled:bg-gray-300 dark:disabled:bg-gray-700 disabled:text-gray-500 disabled:cursor-not-allowed rounded-xl transition-all shadow-sm flex items-center gap-2"
                >
                    <svg
                        v-if="isSubmittingNote"
                        class="animate-spin h-4 w-4 text-white"
                        fill="none"
                        viewBox="0 0 24 24"
                    >
                        <circle
                            class="opacity-25"
                            cx="12"
                            cy="12"
                            r="10"
                            stroke="currentColor"
                            stroke-width="4"
                        ></circle>
                        <path
                            class="opacity-75"
                            fill="currentColor"
                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                        ></path>
                    </svg>
                    <span>{{ $t('common.save') }}</span>
                </button>
            </template>
        </BaseModal>

        <!-- Hold Reason Modal -->
        <BaseModal :show="showHoldModal" @close="cancelHold" size="md">
            <template #title>
                {{ $t('work_orders.actions.put_on_hold') }}
            </template>

            <div class="space-y-4 text-right">
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    {{ $t('work_orders.messages.hold_reason_prompt') }}
                </p>
                <div class="space-y-1">
                    <label
                        class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1"
                    >
                        {{ $t('work_orders.hold_reason_label') }}
                    </label>
                    <textarea
                        v-model="holdReason"
                        required
                        rows="3"
                        :placeholder="$t('work_orders.hold_reason_placeholder')"
                        class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-white px-3 py-2 text-sm focus:ring-2 focus:ring-focus-ring focus:border-action-primary resize-none"
                    ></textarea>
                </div>
            </div>

            <template #footer>
                <button
                    type="button"
                    @click="cancelHold"
                    class="px-5 py-2 text-sm font-semibold text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/50 rounded-xl transition-all"
                >
                    {{ $t('common.cancel') }}
                </button>
                <button
                    type="button"
                    @click="confirmHold"
                    :disabled="!holdReason.trim()"
                    class="px-5 py-2 text-sm font-semibold text-white bg-amber-500 hover:bg-amber-600 disabled:bg-gray-300 dark:disabled:bg-gray-700 disabled:text-gray-500 disabled:cursor-not-allowed rounded-xl transition-all shadow-sm"
                >
                    {{ $t('work_orders.actions.put_on_hold') }}
                </button>
            </template>
        </BaseModal>

        <!-- Vehicle Exit Modal -->
        <BaseModal :show="showExitModal" @close="cancelExit" size="md">
            <template #title>
                {{ $t('work_orders.confirm_exit_title') }}
            </template>

            <div class="space-y-4 text-right">
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    {{ $t('work_orders.messages.confirm_complete') }}
                </p>

                <!-- Exit Date -->
                <div class="space-y-1">
                    <label
                        class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1"
                    >
                        {{ $t('work_orders.exit_date_label') }}
                    </label>
                    <CustomDatePicker v-model="exitDate" required />
                </div>

                <!-- Remaining Balance -->
                <div class="space-y-1">
                    <label
                        class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1"
                    >
                        {{ $t('work_orders.balance') }}
                    </label>
                    <div
                        class="w-full bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-white px-3 py-2 text-sm rounded-xl border border-gray-200 dark:border-gray-700 font-mono font-bold"
                    >
                        {{ formatPrice(workOrderBalance) }}
                    </div>
                    <p
                        v-if="workOrderBalance > 0"
                        class="text-xs text-amber-500 dark:text-amber-400 font-bold mt-1"
                    >
                        {{ $t('work_orders.outstanding_balance_warning') }}
                    </p>
                </div>

                <!-- Deferred Invoice (Credit) Options if balance > 0 -->
                <div
                    v-if="workOrderBalance > 0"
                    class="space-y-3 p-3 bg-amber-500/5 dark:bg-amber-500/10 rounded-xl border border-amber-500/20 text-right"
                >
                    <label class="flex items-center gap-2 cursor-pointer select-none">
                        <AppToggle v-model="isDeferred" />
                        <span class="text-sm font-bold text-gray-700 dark:text-gray-300">
                            {{ $t('work_orders.is_deferred_label') }}
                        </span>
                    </label>

                    <div v-if="isDeferred" class="space-y-1">
                        <label
                            class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1"
                        >
                            {{ $t('work_orders.due_date_label') }}
                        </label>
                        <CustomDatePicker v-model="dueDate" required :min-date="exitDate" />
                    </div>
                </div>

                <!-- Exit Notes -->
                <div class="space-y-1">
                    <label
                        class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1"
                    >
                        {{ $t('work_orders.exit_notes_label') }}
                    </label>
                    <textarea
                        v-model="exitNotes"
                        rows="3"
                        :placeholder="$t('work_orders.exit_notes_placeholder')"
                        class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-white px-3 py-2 text-sm focus:ring-2 focus:ring-focus-ring focus:border-action-primary resize-none"
                    ></textarea>
                </div>
            </div>

            <template #footer>
                <button
                    type="button"
                    @click="cancelExit"
                    class="px-5 py-2 text-sm font-semibold text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/50 rounded-xl transition-all"
                >
                    {{ $t('common.cancel') }}
                </button>
                <button
                    type="button"
                    @click="confirmExit"
                    :disabled="!exitDate || (isDeferred && !dueDate)"
                    class="px-5 py-2 text-sm font-semibold text-white bg-emerald-600 hover:bg-emerald-700 disabled:bg-gray-300 dark:disabled:bg-gray-700 disabled:text-gray-500 disabled:cursor-not-allowed rounded-xl transition-all shadow-sm"
                >
                    {{ $t('work_orders.actions.complete') }}
                </button>
            </template>
        </BaseModal>

        <!-- Deferred Invoice Modal -->
        <BaseModal :show="showDeferredInvoiceModal" @close="closeDeferredInvoiceModal" size="md">
            <template #title>
                <div class="flex items-center gap-2">
                    <div
                        class="w-7 h-7 rounded-lg bg-selection-bg flex items-center justify-center"
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
                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                            />
                        </svg>
                    </div>
                    {{ $t('invoices.create_deferred') }}
                </div>
            </template>

            <form @submit.prevent="confirmDeferredInvoice" class="space-y-5 text-right" dir="rtl">
                <!-- Balance (read-only) -->
                <div class="bg-selection-bg rounded-xl p-4 flex items-center justify-between">
                    <span class="text-sm font-bold text-selection-text">
                        {{ $t('invoices.remaining_amount') }}
                    </span>
                    <span class="text-lg font-black text-selection-text font-mono" dir="ltr">
                        {{ formatCurrency(workOrderBalance) }}
                    </span>
                </div>

                <!-- Due Date -->
                <div class="space-y-1">
                    <label
                        class="block text-xs font-bold text-gray-600 dark:text-gray-400 uppercase tracking-wider"
                    >
                        {{ $t('invoices.due_date') }}
                        <span class="text-red-500 ms-0.5">*</span>
                    </label>
                    <CustomDatePicker v-model="deferredDueDate" required :min-date="todayDate" />
                </div>

                <!-- Notes -->
                <div class="space-y-1">
                    <label
                        class="block text-xs font-bold text-gray-600 dark:text-gray-400 uppercase tracking-wider"
                    >
                        {{ $t('common.notes') }}
                    </label>
                    <textarea
                        v-model="deferredNotes"
                        rows="3"
                        :placeholder="$t('invoices.deferred_notes_placeholder')"
                        class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-white px-3 py-2.5 text-sm focus:ring-2 focus:ring-focus-ring focus:border-action-primary resize-none transition-colors"
                    ></textarea>
                </div>
            </form>

            <template #footer>
                <button
                    type="button"
                    @click="closeDeferredInvoiceModal"
                    class="px-5 py-2 text-sm font-semibold text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/50 rounded-xl transition-all"
                >
                    {{ $t('common.cancel') }}
                </button>
                <button
                    type="button"
                    @click="confirmDeferredInvoice"
                    :disabled="!deferredDueDate || isDeferredSubmitting"
                    class="px-5 py-2 text-sm font-semibold text-white bg-action-primary hover:bg-action-primary-hover disabled:bg-gray-300 dark:disabled:bg-gray-700 disabled:text-gray-500 disabled:cursor-not-allowed rounded-xl transition-all shadow-sm flex items-center gap-2"
                >
                    <svg
                        v-if="isDeferredSubmitting"
                        class="w-4 h-4 animate-spin"
                        fill="none"
                        viewBox="0 0 24 24"
                    >
                        <circle
                            class="opacity-25"
                            cx="12"
                            cy="12"
                            r="10"
                            stroke="currentColor"
                            stroke-width="4"
                        />
                        <path
                            class="opacity-75"
                            fill="currentColor"
                            d="M4 12a8 8 0 018-8V0C5.373 0 22 6.477 22 12h-4z"
                        />
                    </svg>
                    {{ $t('invoices.create_deferred') }}
                </button>
            </template>
        </BaseModal>

        <!-- Return Invoice / Credit Note Modal -->
        <WorkOrderCreditNoteModal
            :show="showCreditNoteModal"
            :work-order="workOrder"
            @close="showCreditNoteModal = false"
            @created="refreshWorkOrder"
        />

        <!-- New Linked Purchase Invoice Modal -->
        <PurchaseInvoiceFormModal
            v-if="showNewPurchaseInvoiceModal"
            :show="showNewPurchaseInvoiceModal"
            :suppliers="suppliers"
            :warehouses="warehouses"
            :units="inventoryUnits"
            :default-warehouse="warehouses?.[0] || null"
            :work-orders="[workOrder]"
            :default-work-order-id="workOrder.id"
            :purpose="purchaseInvoicePurpose"
            @close="showNewPurchaseInvoiceModal = false"
            @saved="onNewPurchaseInvoiceSaved"
        />

        <!-- Allocation Modal ("اختر للإضافة") -->
        <BaseModal :show="showAllocationModal" @close="showAllocationModal = false" size="xl">
            <template #title>
                <div class="flex items-center justify-between w-full">
                    <div class="flex items-center gap-2">
                        <span class="text-lg">🧾</span>
                        <h3 class="text-base font-bold text-text-primary">
                            {{ $t('work_orders.purchases.select_to_add') }}
                        </h3>
                    </div>
                    <button
                        type="button"
                        @click="openQuickPurchaseFromAllocation"
                        class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-xl flex items-center gap-1 transition-colors cursor-pointer"
                    >
                        <span>+</span>
                        <span>{{ $t('work_orders.purchases.quick_create_invoice') }}</span>
                    </button>
                </div>
            </template>

            <div class="space-y-4 py-2">
                <!-- Search & Filters -->
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div class="relative flex-1">
                        <input
                            v-model="allocationSearchQuery"
                            type="text"
                            :placeholder="$t('work_orders.purchases.search_invoices_placeholder')"
                            class="w-full px-3 py-2 text-xs rounded-xl border border-border-subtle bg-surface text-text-primary focus:outline-none focus:ring-2 focus:ring-action-primary/30"
                        />
                    </div>
                    <!-- Category Filter Tabs -->
                    <div
                        class="flex items-center gap-1 bg-surface-secondary/60 p-1 rounded-xl border border-border-subtle text-xs"
                    >
                        <button
                            v-for="cat in [
                                'all',
                                'external_parts',
                                'supplier_service',
                                'warehouse',
                            ]"
                            :key="cat"
                            type="button"
                            @click="allocationCategoryFilter = cat"
                            :class="[
                                'px-2.5 py-1 rounded-lg font-bold transition-all cursor-pointer',
                                allocationCategoryFilter === cat
                                    ? 'bg-surface text-action-primary shadow-xs'
                                    : 'text-text-secondary hover:text-text-primary',
                            ]"
                        >
                            {{
                                cat === 'all'
                                    ? $t('work_orders.purchases.filter_all')
                                    : cat === 'external_parts'
                                      ? $t('work_orders.purchases.external_parts_purchases')
                                      : cat === 'supplier_service'
                                        ? $t('work_orders.purchases.supplier_services')
                                        : $t('work_orders.purchases.warehouse_stock_purchases')
                            }}
                        </button>
                    </div>
                </div>

                <!-- Available Invoices List -->
                <div
                    v-if="filteredAvailableInvoices.length > 0"
                    class="space-y-3 max-h-[50vh] overflow-y-auto pr-1"
                >
                    <div
                        v-for="inv in filteredAvailableInvoices"
                        :key="inv.id"
                        class="p-3.5 rounded-2xl border border-border-subtle bg-surface hover:border-action-primary/40 transition-colors"
                    >
                        <div class="flex items-center justify-between gap-3">
                            <div class="flex items-center gap-3">
                                <AppCheckbox
                                    :model-value="isInvoiceSelected(inv.id)"
                                    @change="toggleInvoiceSelection(inv)"
                                />
                                <div>
                                    <div class="flex items-center gap-2">
                                        <span class="font-bold text-xs text-text-primary">
                                            {{ inv.invoice_number || inv.code }}
                                        </span>
                                        <span class="font-mono text-[11px] text-text-secondary">
                                            ({{ inv.code }})
                                        </span>
                                        <span
                                            :class="[
                                                'px-2 py-0.5 rounded-full text-[10px] font-bold',
                                                getInvoiceCategory(inv) === 'warehouse'
                                                    ? 'bg-sky-50 text-sky-700 border border-sky-200'
                                                    : getInvoiceCategory(inv) === 'supplier_service'
                                                      ? 'bg-purple-50 text-purple-700 border border-purple-200'
                                                      : 'bg-amber-50 text-amber-700 border border-amber-200',
                                            ]"
                                        >
                                            {{
                                                getInvoiceCategory(inv) === 'warehouse'
                                                    ? $t(
                                                          'work_orders.purchases.warehouse_stock_purchases'
                                                      )
                                                    : getInvoiceCategory(inv) === 'supplier_service'
                                                      ? $t(
                                                            'work_orders.purchases.supplier_services'
                                                        )
                                                      : $t(
                                                            'work_orders.purchases.external_parts_purchases'
                                                        )
                                            }}
                                        </span>
                                    </div>
                                    <p class="text-[11px] text-text-secondary mt-0.5">
                                        {{ inv.supplier?.name || '—' }} •
                                        {{ formatDate(inv.issue_date) }}
                                    </p>
                                </div>
                            </div>
                            <div class="text-end">
                                <p class="text-xs font-black font-mono text-text-primary" dir="ltr">
                                    {{ formatPrice(inv.total) }}
                                </p>
                            </div>
                        </div>

                        <!-- Invoice Lines (if expanded or available) -->
                        <div
                            v-if="inv.lines && inv.lines.length > 0"
                            class="mt-3 pt-3 border-t border-border-subtle"
                        >
                            <p class="text-[11px] font-bold text-text-secondary mb-2">
                                {{ $t('work_orders.purchases.lines_title') }}:
                            </p>
                            <DataTable structured class="min-w-full text-xs">
                                <thead
                                    class="text-text-muted text-[10px] border-b border-border-subtle"
                                >
                                    <tr>
                                        <th class="pb-1 text-start">
                                            {{ $t('work_orders.purchases.item_description') }}
                                        </th>
                                        <th class="pb-1 text-end">
                                            {{ $t('work_orders.purchases.cost_price') }}
                                        </th>
                                        <th class="pb-1 text-center">
                                            {{ $t('work_orders.purchases.total_qty') }}
                                        </th>
                                        <th class="pb-1 text-end">{{ $t('common.total') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr
                                        v-for="line in inv.lines"
                                        :key="line.id"
                                        class="border-b border-border-subtle/50"
                                    >
                                        <td class="py-1.5">
                                            {{ line.part?.name || line.description || '—' }}
                                        </td>
                                        <td class="py-1.5 text-end font-mono" dir="ltr">
                                            {{ formatPrice(line.unit_cost) }}
                                        </td>
                                        <td class="py-1.5 text-center font-mono">{{ line.qty }}</td>
                                        <td class="py-1.5 text-end font-mono font-bold" dir="ltr">
                                            {{ formatPrice(line.total) }}
                                        </td>
                                    </tr>
                                </tbody>
                            </DataTable>
                        </div>

                        <!-- Allocation inputs when selected -->
                        <div
                            v-if="isInvoiceSelected(inv.id)"
                            class="mt-3 pt-3 border-t border-border-subtle bg-surface-secondary/40 p-3 rounded-xl flex flex-wrap items-center gap-3"
                        >
                            <div class="flex items-center gap-2">
                                <label class="text-xs font-semibold text-text-secondary">
                                    {{ $t('work_orders.purchases.allocated_qty') }}:
                                </label>
                                <div class="inline-flex items-center gap-1">
                                    <input
                                        v-model.number="allocationForms[inv.id].qty"
                                        type="number"
                                        min="1"
                                        step="1"
                                        class="w-20 px-2.5 py-1 text-xs font-mono font-bold text-center rounded-lg border border-border-subtle bg-surface text-text-primary"
                                    />
                                    <span class="text-[10px] text-text-muted font-bold">
                                        {{ $t('common.piece') }}
                                    </span>
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                <label class="text-xs font-semibold text-text-secondary">
                                    {{ $t('work_orders.purchases.allocated_amount') }}:
                                </label>
                                <input
                                    v-model="allocationForms[inv.id].amount"
                                    type="number"
                                    min="0"
                                    step="0.01"
                                    class="w-28 px-2.5 py-1 text-xs font-mono rounded-lg border border-border-subtle bg-surface text-text-primary"
                                />
                            </div>

                            <!-- Optional Link to External Part (if external) -->
                            <div
                                v-if="
                                    getInvoiceCategory(inv) === 'external_parts' &&
                                    externalPartsOptions.length > 0
                                "
                                class="flex items-center gap-2"
                            >
                                <label class="text-xs font-semibold text-text-secondary">
                                    {{ $t('work_orders.purchases.linked_part') }}:
                                </label>
                                <div class="w-44">
                                    <SearchableSelect
                                        v-model="allocationForms[inv.id].linked_part_id"
                                        :options="externalPartsOptions"
                                        option-label="label"
                                        option-value="value"
                                        :placeholder="
                                            $t('work_orders.purchases.select_part_to_link')
                                        "
                                        compact
                                    />
                                </div>
                            </div>

                            <!-- Optional Link to Service (if supplier service) -->
                            <div
                                v-if="
                                    getInvoiceCategory(inv) === 'supplier_service' &&
                                    serviceOptions.length > 0
                                "
                                class="flex items-center gap-2"
                            >
                                <label class="text-xs font-semibold text-text-secondary">
                                    {{ $t('work_orders.purchases.linked_service') }}:
                                </label>
                                <div class="w-44">
                                    <SearchableSelect
                                        v-model="allocationForms[inv.id].linked_service_id"
                                        :options="serviceOptions"
                                        option-label="label"
                                        option-value="value"
                                        :placeholder="
                                            $t('work_orders.purchases.select_service_to_link')
                                        "
                                        compact
                                    />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div
                    v-else
                    class="text-center py-8 px-4 border border-dashed border-border-subtle rounded-2xl bg-surface-secondary/20"
                >
                    <p class="text-xs text-text-secondary">
                        {{ $t('work_orders.purchases.no_available_invoices') }}
                    </p>
                </div>
            </div>

            <template #footer>
                <div class="flex items-center justify-end gap-2">
                    <button
                        type="button"
                        @click="showAllocationModal = false"
                        class="px-4 py-2 text-xs font-bold text-text-secondary hover:bg-surface-secondary rounded-xl transition-colors cursor-pointer"
                    >
                        {{ $t('common.cancel') }}
                    </button>
                    <button
                        type="button"
                        :disabled="
                            selectedAllocationInvoiceIds.length === 0 || isSubmittingAllocation
                        "
                        @click="submitAllocationsToOrder"
                        class="px-5 py-2 bg-action-primary hover:bg-action-primary-hover disabled:opacity-50 text-white text-xs font-bold rounded-xl transition-colors cursor-pointer flex items-center gap-1.5"
                    >
                        <span>{{ $t('work_orders.purchases.add_to_order') }}</span>
                        <span v-if="selectedAllocationInvoiceIds.length > 0">
                            ({{ selectedAllocationInvoiceIds.length }})
                        </span>
                    </button>
                </div>
            </template>
        </BaseModal>
    </AppLayout>
</template>

<script setup lang="ts">
// @ts-nocheck — Show.vue is the largest page in the app (~650 lines of
// script + 400 lines of template). Tight typing of the workOrder object
// would require defining a complete WorkOrder interface, then a full
// audit of every prop passed to the ~15 child components. That work
// belongs in a dedicated refactor (Phase 3 candidate).
//
// What this file gains from `lang="ts"`:
//   - Imports are now type-checked (e.g. `useWorkOrderStatus` exports
//     `StatusAction` which is available to consumers)
//   - Type annotations on local helpers (e.g. `formatPrice(value: number)`)
//   - vue-tsc validates the script setup block, not just the .js fallback
//
// What it doesn't get (intentionally):
//   - Full `defineProps<ShowPageProps>()` — kept as runtime props for now
//   - Type-safe child component props — requires each child to export its
//     own prop types first (out of scope for Goal 2)

import CustomDatePicker from '@/Components/CustomDatePicker.vue';
import AppCheckbox from '@/Components/App/AppCheckbox.vue';
import AppToggle from '@/Components/Common/AppToggle.vue';
import BackButton from '@/Components/BackButton.vue';
import { ref, computed, watch, onBeforeUnmount } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import { useI18n } from 'vue-i18n';
import AppLayout from '@/Layouts/AppLayout.vue';
import { useLocalized } from '@/Composables/useLocalized';
import { useNumberFormat } from '@/Composables/useNumberFormat';
import { useFormatters } from '@/Composables/useFormatters';
import { useToast } from '@/Composables/useToast';
import { useConfirm } from '@/Composables/useConfirm';
import VehicleConditionReport from '@/Components/WorkOrders/VehicleConditionReport.vue';
import WorkOrderHeader from '@/Components/WorkOrders/WorkOrderHeader.vue';
import LinkedInvoiceBadge from '@/Components/Common/LinkedInvoiceBadge.vue';
import WorkOrderFormModal from '@/Components/WorkOrders/WorkOrderFormModal.vue';
import WorkOrderServiceModal from '@/Components/WorkOrders/WorkOrderServiceModal.vue';
import WorkOrderPartModal from '@/Components/WorkOrders/WorkOrderPartModal.vue';
import PrintOptionsModal from '@/Components/WorkOrders/PrintOptionsModal.vue';
import PaymentsSection from '@/Components/WorkOrders/PaymentsSection.vue';
import TechniciansSection from '@/Components/WorkOrders/TechniciansSection.vue';
import PaymentsListModal from '@/Components/WorkOrders/PaymentsListModal.vue';
import WorkOrderPhotoModal from '@/Components/WorkOrders/WorkOrderPhotoModal.vue';
import WorkOrderAttachmentModal from '@/Components/WorkOrders/WorkOrderAttachmentModal.vue';
import InspectionChecklist from '@/Components/WorkOrders/InspectionChecklist.vue';
import WorkOrderSignatures from '@/Components/WorkOrders/WorkOrderSignatures.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import DataTable from '@/Components/Common/DataTable.vue';
import PurchaseInvoiceFormModal from '@/Components/Purchasing/PurchaseInvoiceFormModal.vue';
import PartsDisplay from '@/Components/Common/PartsDisplay.vue';
import WorkOrderIssueMoreModal from '@/Components/WorkOrders/WorkOrderIssueMoreModal.vue';
import WorkOrderReturnModal from '@/Components/WorkOrders/WorkOrderReturnModal.vue';
import WorkOrderCreditNoteModal from '@/Components/WorkOrders/WorkOrderCreditNoteModal.vue';
import WorkOrderSuggestionsPanel from '@/Components/WorkOrders/WorkOrderSuggestionsPanel.vue';
import WorkOrderCustomerCard from '@/Components/WorkOrders/WorkOrderCustomerCard.vue';
import WorkOrderFinancialSummary from '@/Components/WorkOrders/WorkOrderFinancialSummary.vue';
import WorkOrderInfoCards from '@/Components/WorkOrders/WorkOrderInfoCards.vue';
import WorkOrderComplaintAssessment from '@/Components/WorkOrders/WorkOrderComplaintAssessment.vue';
import WorkOrderTabsContainer from '@/Components/WorkOrders/WorkOrderTabsContainer.vue';
import WorkOrderServicesTab from '@/Components/WorkOrders/WorkOrderServicesTab.vue';
import WorkOrderNotesTab from '@/Components/WorkOrders/WorkOrderNotesTab.vue';
import WorkOrderPhotosTab from '@/Components/WorkOrders/WorkOrderPhotosTab.vue';
import WorkOrderActivitiesTab from '@/Components/WorkOrders/WorkOrderActivitiesTab.vue';
import WorkOrderAttachmentsTab from '@/Components/WorkOrders/WorkOrderAttachmentsTab.vue';
import BaseModal from '@/Components/BaseModal.vue';
import { PACKAGES_DEPT_KEY, useWorkOrderItems } from '@/Composables/useWorkOrderItems';
import { useWorkOrderStatus, type StatusAction } from '@/Composables/useWorkOrderStatus';
import { useWorkOrderNotes } from '@/Composables/useWorkOrderNotes';

const props = defineProps({
    workOrder: Object,
    itemsByDepartment: { type: Object, default: () => ({}) },
    customers: { type: Array, default: () => [] },
    dealers: { type: Array, default: () => [] },
    makes: { type: Array, default: () => [] },
    colors: { type: Array, default: () => [] },
    modelsByMake: { type: Object, default: () => ({}) },
    departments: { type: Array, default: () => [] },
    services: { type: Array, default: () => [] },
    technicians: { type: Array, default: () => [] },
    laborEntries: { type: Array, default: () => [] },
    serverNow: { type: String, default: null },
    warehouses: { type: Array, default: () => [] },
    inventoryParts: { type: Array, default: () => [] },
    inventoryUnits: { type: Array, default: () => [] },
    availablePurchaseInvoices: { type: Array, default: () => [] },
    suppliers: { type: Array, default: () => [] },
    enableSystematicInspections: { type: Boolean, default: true },
});

const { t } = useI18n();
const { formatNumber, formatCurrency, formatMoney } = useNumberFormat();
const { formatDate, getUserRoleName, getNoteDate, getNoteTime } = useFormatters();
const { success, error: errorToast } = useToast();
const { confirm } = useConfirm();

function formatPrice(value) {
    return formatMoney(value, props.workOrder?.currency_code);
}

const showEditModal = ref(false);
const activeTab = ref('services');
const showAddPartModal = ref(false);
const showPrintModal = ref(false);
const showPaymentsListModal = ref(false);
const showPhotoModal = ref(false);
const showAttachmentModal = ref(false);
const selectedPartToEdit = ref(null);
const showIssueMoreModal = ref(false);
const editingIssueMorePart = ref(null);
const showReturnModal = ref(false);
const editingReturnPart = ref(null);
const showCreditNoteModal = ref(false);
const selectedPurchaseInvoiceId = ref('');
const allocatedAmountInput = ref('');
const isLinkingPurchaseInvoice = ref(false);
const showNewPurchaseInvoiceModal = ref(false);
const purchaseInvoicePurpose = ref('external_part');

// ─── Purchase Allocations & Supplier Services State ───────────────────────────
const showAllocationModal = ref(false);
const isSubmittingAllocation = ref(false);
const allocationSearchQuery = ref('');
const allocationCategoryFilter = ref('all');
const selectedAllocationInvoiceIds = ref([]);
const allocationForms = ref({});
const pendingPartLinks = ref({});
const pendingServiceLinks = ref({});

// Load persistent mappings from localStorage
const partStorageKey = computed(() => `wo_part_allocations_${props.workOrder?.id}`);
const serviceStorageKey = computed(() => `wo_service_allocations_${props.workOrder?.id}`);

const linkedPartAllocations = ref({});
const linkedServiceAllocations = ref({});

function loadAllocationsFromStorage() {
    try {
        const savedParts = localStorage.getItem(partStorageKey.value);
        if (savedParts) {
            linkedPartAllocations.value = JSON.parse(savedParts);
        }
        const savedServices = localStorage.getItem(serviceStorageKey.value);
        if (savedServices) {
            linkedServiceAllocations.value = JSON.parse(savedServices);
        }
    } catch (e) {
        // ignore parse error
    }
}

function savePartAllocationsToStorage() {
    try {
        localStorage.setItem(partStorageKey.value, JSON.stringify(linkedPartAllocations.value));
    } catch (e) {
        // ignore
    }
}

function saveServiceAllocationsToStorage() {
    try {
        localStorage.setItem(
            serviceStorageKey.value,
            JSON.stringify(linkedServiceAllocations.value)
        );
    } catch (e) {
        // ignore
    }
}

loadAllocationsFromStorage();

function openAllocationModal() {
    selectedAllocationInvoiceIds.value = [];
    allocationForms.value = {};
    allocationSearchQuery.value = '';
    allocationCategoryFilter.value = 'all';
    showAllocationModal.value = true;
}

function openQuickPurchaseModal() {
    purchaseInvoicePurpose.value = 'external_part';
    showNewPurchaseInvoiceModal.value = true;
}

function openQuickPurchaseFromAllocation() {
    showAllocationModal.value = false;
    purchaseInvoicePurpose.value = 'external_part';
    showNewPurchaseInvoiceModal.value = true;
}

const allLinkedPurchaseInvoices = computed(() => props.workOrder?.purchase_invoices || []);

function isInvoiceSupplierService(inv) {
    if (inv.source_type === 'supplier_service' || inv.type === 'supplier_service') return true;
    const notes = String(inv.notes || '');
    if (
        notes.includes('[خدمات المورد]') ||
        notes.includes('supplier_service') ||
        notes.includes('خدمة') ||
        notes.includes('مخرطة')
    )
        return true;
    return Object.values(linkedServiceAllocations.value).some(
        (alloc) => alloc.invoice_id === inv.id || alloc.allocationId === inv.id
    );
}

const warehouseInvoicesList = computed(() => {
    return allLinkedPurchaseInvoices.value.filter(
        (inv) => inv.source_type === 'warehouse' || inv.purchase_order_id !== null
    );
});

const supplierServiceAllocationsList = computed(() => {
    return allLinkedPurchaseInvoices.value
        .filter((inv) => inv.source_type !== 'warehouse' && isInvoiceSupplierService(inv))
        .map((inv) => {
            const linkedService = Object.entries(linkedServiceAllocations.value).find(
                ([_, alloc]) => alloc.invoice_id === inv.id || alloc.allocationId === inv.id
            );
            const serviceItem = linkedService
                ? (props.workOrder?.items || []).find((it) => it.id === Number(linkedService[0]))
                : null;
            return {
                id: inv.id,
                invoice_id: inv.id,
                invoice_code: inv.code,
                invoice_number: inv.invoice_number,
                supplier_name: inv.supplier?.name,
                description:
                    inv.notes?.replace(/^\[.*?\]\s*/, '') || inv.invoice_number || inv.code,
                unit_cost: Number(inv.subtotal || inv.total || 0),
                total: Number(inv.total || 0),
                linked_service: serviceItem,
            };
        });
});

const externalPartAllocationsList = computed(() => {
    return allLinkedPurchaseInvoices.value
        .filter((inv) => inv.source_type !== 'warehouse' && !isInvoiceSupplierService(inv))
        .map((inv) => {
            const linkedPart = Object.entries(linkedPartAllocations.value).find(
                ([_, alloc]) => alloc.invoice_id === inv.id || alloc.allocationId === inv.id
            );
            const partObj = linkedPart
                ? (props.workOrder?.parts || []).find((p) => p.id === Number(linkedPart[0]))
                : null;
            return {
                id: inv.id,
                invoice_id: inv.id,
                invoice_code: inv.code,
                invoice_number: inv.invoice_number,
                supplier_name: inv.supplier?.name,
                description:
                    inv.notes?.replace(/^\[.*?\]\s*/, '') || inv.invoice_number || inv.code,
                unit_cost: Number(inv.subtotal || inv.total || 0),
                qty: Number(inv.allocated_qty || 1),
                total: Number(inv.total || 0),
                linked_part: partObj,
            };
        });
});

const externalPartsCost = computed(() =>
    externalPartAllocationsList.value.reduce((sum, item) => sum + Number(item.total || 0), 0)
);

const warehousePartsCost = computed(() =>
    warehouseInvoicesList.value.reduce((sum, inv) => sum + Number(inv.total || 0), 0)
);

const supplierServicesCost = computed(() =>
    supplierServiceAllocationsList.value.reduce((sum, item) => sum + Number(item.total || 0), 0)
);

const totalPurchasesCost = computed(
    () => externalPartsCost.value + warehousePartsCost.value + supplierServicesCost.value
);

const workOrderRevenue = computed(() => workOrderTotal.value);

const netProfitMargin = computed(() => workOrderRevenue.value - totalPurchasesCost.value);

const isNetMarginNegative = computed(() => netProfitMargin.value < 0);

function formatNetMargin(val) {
    const num = Number(val || 0);
    if (num < 0) {
        return `( ${formatPrice(Math.abs(num))} )`;
    }
    return formatPrice(num);
}

const externalPartsOptions = computed(() => {
    return (props.workOrder?.parts || [])
        .filter((part) => part.source === 'external')
        .map((part) => ({
            value: part.id,
            label: `${part.part?.name_ar || part.part?.name_en || part.name || 'قطعة'} (${part.part?.sku || part.part_number || '—'})`,
        }));
});

const serviceOptions = computed(() => {
    return (props.workOrder?.items || [])
        .filter((item) => item.status !== 'cancelled')
        .map((item) => ({
            value: item.id,
            label: item.service
                ? item.service.name_ar || item.service.name || item.title
                : item.title,
        }));
});

function getInvoiceCategory(inv) {
    if (inv.source_type === 'warehouse' || inv.purchase_order_id !== null) {
        return 'warehouse';
    }
    const notes = String(inv.notes || '');
    if (
        notes.includes('[خدمات المورد]') ||
        notes.includes('supplier_service') ||
        notes.includes('خدمة') ||
        notes.includes('مخرطة')
    ) {
        return 'supplier_service';
    }
    const hasServiceLink = Object.values(linkedServiceAllocations.value).some(
        (alloc) => alloc.invoice_id === inv.id || alloc.allocationId === inv.id
    );
    if (hasServiceLink) return 'supplier_service';
    return 'external_parts';
}

const filteredAvailableInvoices = computed(() => {
    let list = props.availablePurchaseInvoices || [];
    const query = allocationSearchQuery.value.trim().toLowerCase();
    if (query) {
        list = list.filter(
            (inv) =>
                (inv.code && inv.code.toLowerCase().includes(query)) ||
                (inv.invoice_number && inv.invoice_number.toLowerCase().includes(query)) ||
                (inv.supplier?.name && inv.supplier.name.toLowerCase().includes(query))
        );
    }
    if (allocationCategoryFilter.value !== 'all') {
        list = list.filter((inv) => getInvoiceCategory(inv) === allocationCategoryFilter.value);
    }
    return list;
});

function isInvoiceSelected(invId) {
    return selectedAllocationInvoiceIds.value.includes(invId);
}

function toggleInvoiceSelection(inv) {
    const idx = selectedAllocationInvoiceIds.value.indexOf(inv.id);
    if (idx > -1) {
        selectedAllocationInvoiceIds.value.splice(idx, 1);
        delete allocationForms.value[inv.id];
    } else {
        selectedAllocationInvoiceIds.value.push(inv.id);
        const defaultQty = inv.lines?.length
            ? inv.lines.reduce((s, l) => s + Math.max(1, Math.floor(Number(l.qty || 1))), 0)
            : 1;
        allocationForms.value[inv.id] = {
            qty: defaultQty,
            amount: Number(inv.total || 0),
            linked_part_id: null,
            linked_service_id: null,
        };
    }
}

function linkPartToAllocation(allocationId, partId, alloc) {
    if (!partId) return;
    const part = (props.workOrder?.parts || []).find((p) => p.id === Number(partId));
    if (!part) return;
    linkedPartAllocations.value[partId] = {
        allocationId: allocationId,
        invoice_id: alloc.invoice_id || allocationId,
        name: alloc.description || alloc.invoice_number || alloc.invoice_code,
        unit_cost: alloc.unit_cost,
        qty: alloc.qty,
        total: alloc.total,
    };
    savePartAllocationsToStorage();
    delete pendingPartLinks.value[allocationId];
    success(t('work_orders.purchases.link_success'));
}

function unlinkPartFromAllocation(allocationId) {
    const entry = Object.entries(linkedPartAllocations.value).find(
        ([_, a]) => a.allocationId === allocationId || a.invoice_id === allocationId
    );
    if (entry) {
        delete linkedPartAllocations.value[entry[0]];
        savePartAllocationsToStorage();
        success(t('work_orders.purchases.unlink_success'));
    }
}

function unlinkPartAllocationMapping(partId) {
    if (linkedPartAllocations.value[partId]) {
        delete linkedPartAllocations.value[partId];
        savePartAllocationsToStorage();
        success(t('work_orders.purchases.unlink_success'));
    }
}

function linkServiceToAllocation(allocationId, serviceId, alloc) {
    if (!serviceId) return;
    const item = (props.workOrder?.items || []).find((i) => i.id === Number(serviceId));
    if (!item) return;
    linkedServiceAllocations.value[serviceId] = {
        allocationId: allocationId,
        invoice_id: alloc.invoice_id || allocationId,
        name: alloc.description || alloc.invoice_number || alloc.invoice_code,
        cost: alloc.unit_cost,
    };
    saveServiceAllocationsToStorage();
    delete pendingServiceLinks.value[allocationId];
    success(t('work_orders.purchases.link_success'));
}

function unlinkServiceFromAllocation(allocationId) {
    const entry = Object.entries(linkedServiceAllocations.value).find(
        ([_, a]) => a.allocationId === allocationId || a.invoice_id === allocationId
    );
    if (entry) {
        delete linkedServiceAllocations.value[entry[0]];
        saveServiceAllocationsToStorage();
        success(t('work_orders.purchases.unlink_success'));
    }
}

function unlinkSupplierServiceMapping(serviceId) {
    if (linkedServiceAllocations.value[serviceId]) {
        delete linkedServiceAllocations.value[serviceId];
        saveServiceAllocationsToStorage();
        success(t('work_orders.purchases.unlink_success'));
    }
}

async function submitAllocationsToOrder() {
    if (selectedAllocationInvoiceIds.value.length === 0 || isSubmittingAllocation.value) return;
    isSubmittingAllocation.value = true;

    const targetUrl = route().has('app.work-orders.purchase-allocations.store')
        ? route('app.work-orders.purchase-allocations.store', props.workOrder.id)
        : route('work-orders.purchase-invoices.link', props.workOrder.id);

    try {
        for (const invoiceId of selectedAllocationInvoiceIds.value) {
            const form = allocationForms.value[invoiceId] || {};
            const inv = (props.availablePurchaseInvoices || []).find((i) => i.id === invoiceId);
            const cat = inv ? getInvoiceCategory(inv) : 'external_parts';
            const allocType =
                cat === 'supplier_service'
                    ? 'supplier_service'
                    : cat === 'warehouse'
                      ? 'warehouse_part'
                      : 'external_part';

            const qty = Math.max(1, Math.floor(Number(form.qty || 1)));
            const amount = Number(
                form.amount !== undefined && form.amount !== null && form.amount !== ''
                    ? form.amount
                    : inv?.total || 0
            );

            const payload = {
                purchase_invoice_id: invoiceId,
                type: allocType,
                item_description: inv?.invoice_number || inv?.code || 'بند مشتريات',
                allocated_qty: qty,
                unit_cost: qty > 0 ? Math.round((amount / qty) * 100) / 100 : amount,
                total_cost: amount,
                allocated_amount: amount,
                work_order_item_part_id: form.linked_part_id || null,
                work_order_item_id: form.linked_service_id || null,
            };

            await axios.post(targetUrl, payload);

            if (form.linked_part_id) {
                linkedPartAllocations.value[form.linked_part_id] = {
                    allocationId: invoiceId,
                    invoice_id: invoiceId,
                    name: inv?.invoice_number || inv?.code || 'قطعة شراء',
                    unit_cost: form.amount || inv?.total || 0,
                    qty: qty,
                    total: form.amount || inv?.total || 0,
                };
                savePartAllocationsToStorage();
            }

            if (form.linked_service_id) {
                linkedServiceAllocations.value[form.linked_service_id] = {
                    allocationId: invoiceId,
                    invoice_id: invoiceId,
                    name: inv?.invoice_number || inv?.code || 'خدمة مورد',
                    cost: form.amount || inv?.total || 0,
                };
                saveServiceAllocationsToStorage();
            }
        }

        showAllocationModal.value = false;
        selectedAllocationInvoiceIds.value = [];
        success(t('work_orders.purchases.link_success'));
        router.reload({ only: ['workOrder', 'availablePurchaseInvoices'] });
    } catch (err) {
        errorToast(err?.response?.data?.message || t('common.error_occurred'));
    } finally {
        isSubmittingAllocation.value = false;
    }
}

const warehousePurchaseInvoices = computed(() =>
    (props.workOrder?.purchase_invoices || []).filter((pinv) => pinv.source_type === 'warehouse')
);

const externalPurchaseInvoices = computed(() =>
    (props.workOrder?.purchase_invoices || []).filter((pinv) => pinv.source_type !== 'warehouse')
);

const warehousePurchaseTotal = computed(() =>
    warehousePurchaseInvoices.value.reduce((sum, pinv) => sum + Number(pinv.total || 0), 0)
);

const externalPurchaseTotal = computed(() =>
    externalPurchaseInvoices.value.reduce((sum, pinv) => sum + Number(pinv.total || 0), 0)
);

const allLinkedInvoices = computed(() => {
    const list = [];
    const seen = new Set();

    if (Array.isArray(props.workOrder?.invoices) && props.workOrder.invoices.length > 0) {
        for (const inv of props.workOrder.invoices) {
            if (inv && inv.id && !seen.has(inv.id)) {
                seen.add(inv.id);
                list.push(inv);
            }
        }
    }

    if (props.workOrder?.invoice?.id && !seen.has(props.workOrder.invoice.id)) {
        seen.add(props.workOrder.invoice.id);
        list.push(props.workOrder.invoice);
    }

    if (Array.isArray(props.workOrder?.credit_notes)) {
        for (const cn of props.workOrder.credit_notes) {
            if (cn && cn.id && !seen.has(cn.id)) {
                seen.add(cn.id);
                list.push(cn);
            }
        }
    }

    return list;
});

function getInvoiceBadgeMeta(inv) {
    if (!inv) return null;

    if (inv.type === 'credit_note') {
        const num = inv.invoice_number || inv.code || '';
        const titleText = `${t('invoices.types.credit_note')} #${num}`;
        return {
            type: 'credit_note',
            number: num,
            title: titleText,
            tooltip: `${t('invoices.credit_note.title')} #${num}`,
            badgeClass:
                'bg-rose-50 text-rose-700 border-rose-200/80 dark:bg-rose-950/40 dark:text-rose-300 dark:border-rose-800/60 hover:bg-rose-100 dark:hover:bg-rose-900/60 hover:border-rose-300',
            icon: 'credit_note',
        };
    }

    const isDeferred =
        !!(inv.due_date && inv.payment_status !== 'paid') ||
        inv.payment_status === 'unpaid' ||
        inv.payment_status === 'partial';

    if (isDeferred) {
        const num = inv.invoice_number || inv.code || '';
        const dueText = inv.due_date
            ? ` (${t('invoices.due_date')}: ${formatDate(inv.due_date)})`
            : '';
        return {
            type: 'deferred',
            number: num,
            title: `${t('invoices.deferred_invoice')} #${num}${dueText}`,
            tooltip: `${t('invoices.deferred_invoice')} #${num}${dueText}`,
            badgeClass:
                'bg-amber-50 text-amber-700 border-amber-200/80 dark:bg-amber-950/40 dark:text-amber-300 dark:border-amber-800/60 hover:bg-amber-100 dark:hover:bg-amber-900/60 hover:border-amber-300',
            icon: 'clock',
        };
    }

    const isPaid = inv.payment_status === 'paid';
    const num = inv.invoice_number || inv.code || '';
    const statusText = isPaid ? t('invoices.status_paid') : t('invoices.types.invoice');

    return {
        type: isPaid ? 'paid' : 'standard',
        number: num,
        title: `${t('invoices.types.invoice')} #${num} (${statusText})`,
        tooltip: `${t('invoices.types.invoice')} #${num} - ${statusText}`,
        badgeClass: isPaid
            ? 'bg-emerald-50 text-emerald-700 border-emerald-200/80 dark:bg-emerald-950/40 dark:text-emerald-300 dark:border-emerald-800/60 hover:bg-emerald-100 dark:hover:bg-emerald-900/60 hover:border-emerald-300'
            : 'bg-sky-50 text-sky-700 border-sky-200/80 dark:bg-sky-950/40 dark:text-sky-300 dark:border-sky-800/60 hover:bg-sky-100 dark:hover:bg-sky-900/60 hover:border-sky-300',
        icon: isPaid ? 'check' : 'document',
    };
}

const linkablePurchaseInvoiceOptions = computed(() =>
    (props.availablePurchaseInvoices || []).map((inv) => ({
        value: inv.id,
        label: [
            inv.code,
            inv.invoice_number,
            inv.supplier?.name,
            formatPrice(inv.total),
            inv.source_type === 'warehouse'
                ? t('work_orders.purchase_invoices.warehouse_purchase')
                : t('work_orders.purchase_invoices.external_purchase'),
        ]
            .filter(Boolean)
            .join(' • '),
    }))
);

function linkSelectedPurchaseInvoice() {
    if (!selectedPurchaseInvoiceId.value || isLinkingPurchaseInvoice.value) return;
    isLinkingPurchaseInvoice.value = true;
    const payload = {
        purchase_invoice_id: selectedPurchaseInvoiceId.value,
    };
    if (allocatedAmountInput.value !== '' && allocatedAmountInput.value !== null) {
        payload.allocated_amount = Number(allocatedAmountInput.value);
    }
    router.post(route('work-orders.purchase-invoices.link', props.workOrder.id), payload, {
        preserveScroll: true,
        onSuccess: () => {
            selectedPurchaseInvoiceId.value = '';
            allocatedAmountInput.value = '';
            success(t('common.saved_success'));
        },
        onFinish: () => {
            isLinkingPurchaseInvoice.value = false;
        },
    });
}

async function unlinkPurchaseInvoice(pinv) {
    const confirmed = await confirm({
        title: t('common.confirm_delete_title'),
        message: t('work_orders.purchase_invoices.confirm_unlink'),
        confirmText: t('work_orders.purchase_invoices.unlink_btn'),
        cancelText: t('common.cancel'),
        type: 'warning',
    });
    if (!confirmed) return;
    router.delete(
        route('work-orders.purchase-invoices.unlink', {
            workOrder: props.workOrder.id,
            purchaseInvoice: pinv.id || pinv.invoice_id,
        }),
        {
            preserveScroll: true,
            onSuccess: () => {
                const invId = pinv.id || pinv.invoice_id;
                for (const [pId, a] of Object.entries(linkedPartAllocations.value)) {
                    if (a.invoice_id === invId || a.allocationId === invId) {
                        delete linkedPartAllocations.value[pId];
                    }
                }
                savePartAllocationsToStorage();
                for (const [sId, a] of Object.entries(linkedServiceAllocations.value)) {
                    if (a.invoice_id === invId || a.allocationId === invId) {
                        delete linkedServiceAllocations.value[sId];
                    }
                }
                saveServiceAllocationsToStorage();
                success(t('common.deleted_success'));
            },
        }
    );
}

function deleteAllocation(alloc) {
    unlinkPurchaseInvoice(alloc);
}

function onNewPurchaseInvoiceSaved() {
    showNewPurchaseInvoiceModal.value = false;
    router.reload({ only: ['workOrder', 'availablePurchaseInvoices'] });
}

const showExclusive = computed(() => {
    if (props.workOrder?.pricing_mode_snapshot !== 'inclusive') {
        return true;
    }
    const taxSettings = usePage().props.tenant?.tax_settings;
    return taxSettings?.show_amount_before_vat ?? true;
});

// ─── Totals (moved up so composables can reference workOrderBalance) ──────────
const totals = computed(() => {
    const t = {
        services: { price: 0, discount: 0, amount: 0, tax: 0, total: 0 },
        parts: { price: 0, discount: 0, amount: 0, tax: 0, total: 0 },
        grand: { price: 0, discount: 0, amount: 0, tax: 0, total: 0 },
    };

    const isInclusive = props.workOrder?.pricing_mode_snapshot === 'inclusive';
    const taxRate = Number(props.workOrder?.tax_rate_snapshot || 15);
    const taxFactor = 1 + taxRate / 100;
    const taxEnabled = !!props.workOrder?.tax_enabled_snapshot;

    const activeItemIds =
        props.workOrder?.items
            ?.filter((line) => line.status !== 'cancelled')
            .map((line) => line.id) || [];

    if (props.workOrder?.items && props.workOrder.items.length > 0) {
        props.workOrder.items
            .filter((line) => line.status !== 'cancelled')
            .forEach((line) => {
                const price = Number(line.unit_price || 0) * Number(line.qty || 1);
                const discountVal = Number(line.discount_amount || 0);

                let amount = price - discountVal;
                let discount = discountVal;
                let calculatedTax = 0;
                let total = price - discountVal;

                if (taxEnabled) {
                    if (isInclusive) {
                        if (showExclusive.value) {
                            // الخصم يبقى بقيمته الكاملة (شامل الضريبة)
                            // فقط عمود المبلغ يتغير ليعكس القيمة قبل الضريبة
                            amount = (price - discountVal) / taxFactor;
                            discount = discountVal;
                            calculatedTax = price - discountVal - amount;
                        } else {
                            amount = price - discountVal;
                            discount = discountVal;
                            calculatedTax = price - discountVal - (price - discountVal) / taxFactor;
                        }
                        total = price - discountVal;
                    } else {
                        amount = price - discountVal;
                        discount = discountVal;
                        calculatedTax = amount * (taxRate / 100);
                        total = amount + calculatedTax;
                    }
                } else {
                    amount = price - discountVal;
                    discount = discountVal;
                    total = price - discountVal;
                }

                t.services.price += price;
                t.services.discount += discount;
                t.services.tax += calculatedTax;
                t.services.total += total;
                t.services.amount += amount;
            });
    }

    if (props.workOrder?.parts && props.workOrder.parts.length > 0) {
        props.workOrder.parts
            .filter((part) => {
                if (['cancelled', 'reversed'].includes(part.status)) {
                    return false;
                }
                if (
                    part.work_order_item_id !== null &&
                    !activeItemIds.includes(part.work_order_item_id)
                ) {
                    return false;
                }
                return true;
            })
            .forEach((part) => {
                const partQty = Number(part.qty || 0);
                const partUnitPrice = Number(part.unit_price || 0);
                const partDiscountVal = Number(part.discount || 0);
                const partPrice = partQty * partUnitPrice;
                const partNet = partPrice - partDiscountVal;

                let partAmount = partNet;
                let partDiscount = partDiscountVal;
                let partTax = 0;
                let partTotal = partNet;

                if (taxEnabled) {
                    if (isInclusive) {
                        if (showExclusive.value) {
                            // الخصم يبقى بقيمته الكاملة (شامل الضريبة)
                            partAmount = partNet / taxFactor;
                            partDiscount = partDiscountVal;
                            partTax = partNet - partAmount;
                        } else {
                            partAmount = partNet;
                            partDiscount = partDiscountVal;
                            partTax = partNet - partNet / taxFactor;
                        }
                        partTotal = partNet;
                    } else {
                        partAmount = partNet;
                        partDiscount = partDiscountVal;
                        partTax = partNet * (taxRate / 100);
                        partTotal = partNet + partTax;
                    }
                } else {
                    partAmount = partNet;
                    partDiscount = partDiscountVal;
                    partTotal = partNet;
                }

                t.parts.price += partPrice;
                t.parts.discount += partDiscount;
                t.parts.tax += partTax;
                t.parts.total += partTotal;
                t.parts.amount += partAmount;
            });
    }

    t.grand.price = t.services.price + t.parts.price;
    t.grand.discount = t.services.discount + t.parts.discount;
    t.grand.amount = t.services.amount + t.parts.amount;
    t.grand.tax = t.services.tax + t.parts.tax;
    t.grand.total = t.services.total + t.parts.total;

    return t;
});

const workOrderTotal = computed(() => totals.value.grand.total);

const workOrderTotalPaid = computed(() => {
    return (
        props.workOrder.payments?.reduce((sum, p) => {
            const amount = parseFloat(p.amount || 0);
            if (p.type === 'bad_debt') return sum;
            return p.type === 'refund' ? sum - amount : sum + amount;
        }, 0) || 0
    );
});

const workOrderBadDebt = computed(() => {
    return (
        props.workOrder.payments?.reduce((sum, p) => {
            const amount = parseFloat(p.amount || 0);
            return p.type === 'bad_debt' ? sum + amount : sum;
        }, 0) || 0
    );
});

const workOrderTotalCreditNotes = computed(() => {
    return (
        props.workOrder.credit_notes?.reduce((sum, cn) => {
            if (cn.status === 'cancelled') return sum;
            return sum + parseFloat(cn.total_incl_tax || 0);
        }, 0) || 0
    );
});

const workOrderNetTotal = computed(() =>
    Math.max(0, workOrderTotal.value - workOrderTotalCreditNotes.value)
);

const workOrderExcessPaid = computed(() =>
    Math.max(0, Math.round((workOrderTotalPaid.value - workOrderNetTotal.value) * 100) / 100)
);

const workOrderBalance = computed(() => {
    return Math.max(0, workOrderNetTotal.value - workOrderTotalPaid.value - workOrderBadDebt.value);
});

// ─── Composable layer ──────────────────────────────────────────────────────────
const items = useWorkOrderItems({
    workOrder: () => props.workOrder,
    itemsByDepartment: () => props.itemsByDepartment,
    departments: props.departments,
    services: props.services,
});

const status = useWorkOrderStatus({
    workOrder: () => props.workOrder,
    workOrderBalance,
    workOrderExcessPaid,
});

const notes = useWorkOrderNotes({
    workOrder: () => props.workOrder,
    services: props.services,
});

// Re-export for template (Vue auto-unwraps top-level refs/computeds)
const {
    isReadOnly,
    showExitModal,
    exitDate,
    exitNotes,
    isDeferred,
    dueDate,
    cancelExit,
    confirmExit,
    showHoldModal,
    holdReason,
    cancelHold,
    confirmHold,
    changeStatus,
} = status;

const { displayDepartments, availableDepartments, addDepartment, removeDepartment } = items;

const {
    showAddNoteModal,
    newNoteContent,
    isSubmittingNote,
    showServiceModal,
    showItemModal,
    selectedItemId,
    selectedDepartmentId,
    serviceModalInitialTab,
    selectedItem,
    departmentServices,
    allNotes,
    handleAddNote,
    handleDeleteNote,
    openAddServiceModal,
    openEditServiceModal,
    openServiceModal,
    openServiceNotesModal,
    openServicePartsModal,
    openServiceTechniciansModal,
    handlePartServiceClick,
    closeServiceModal,
    closeItemModal,
    handleServiceSaved,
    handleItemSaved,
} = notes;
// ─────────────────────────────────────────────────────────────────────────────

const suggestedServiceId = ref(null);
const suggestedPart = ref(null);
const suggestionPage = usePage();
const canUseSuggestions = computed(() =>
    (suggestionPage.props.auth?.permissions || []).includes('crm.work_orders.update')
);

async function reviewSuggestedItem(suggestion) {
    if (isReadOnly.value || !canUseSuggestions.value) return;
    if (suggestion.itemType === 'service') {
        const service = props.services.find((item) => Number(item.id) === suggestion.itemId);
        if (!service) return errorToast(t('work_orders.suggestions.unavailable'));
        suggestedServiceId.value = service.id;
        openAddServiceModal(service.department_id);
    } else if (suggestion.itemType === 'part') {
        try {
            const { data } = await axios.get(route('app.inventory.parts.search'), {
                params: { q: suggestion.name },
            });
            const part = data.find((item) => Number(item.id) === suggestion.itemId);
            if (!part) return errorToast(t('work_orders.suggestions.unavailable'));
            suggestedPart.value = part;
            selectedPartToEdit.value = null;
            showAddPartModal.value = true;
        } catch {
            errorToast(t('work_orders.suggestions.unavailable'));
        }
    }
}

watch(showServiceModal, (open) => {
    if (!open) suggestedServiceId.value = null;
});
watch(showAddPartModal, (open) => {
    if (!open) suggestedPart.value = null;
});

function openAddPartModal() {
    selectedPartToEdit.value = null;
    showAddPartModal.value = true;
}

function editWorkOrderPart(part) {
    if (part.source === 'warehouse' && part.status === 'issued') {
        editingIssueMorePart.value = part;
        showIssueMoreModal.value = true;
    } else {
        selectedPartToEdit.value = part;
        showAddPartModal.value = true;
    }
}

function closeAddPartModal() {
    showAddPartModal.value = false;
    selectedPartToEdit.value = null;
}

// All parts from the work order (using the loaded relationship)
const allWorkOrderParts = computed(() => {
    return props.workOrder.parts || [];
});

const normalizedPartsForDisplay = computed(() => {
    const isInclusive = props.workOrder?.pricing_mode_snapshot === 'inclusive';
    const taxRate = Number(props.workOrder?.tax_rate_snapshot || 15);
    const taxFactor = 1 + taxRate / 100;
    const taxEnabled = !!props.workOrder?.tax_enabled_snapshot;

    return allWorkOrderParts.value.map((part) => {
        const qty = Number(part.qty || 0);
        const unitPrice = Number(part.unit_price || 0);
        const discount = Number(part.discount || 0);
        const net = qty * unitPrice - discount;

        let taxAmount = 0;
        let amount = net;

        if (taxEnabled) {
            if (isInclusive) {
                amount = net / taxFactor;
                taxAmount = net - amount;
            } else {
                taxAmount = net * (taxRate / 100);
            }
        }

        const resolvedSource =
            part.source === 'external' || (!part.part_id && part.source !== 'customer')
                ? 'external'
                : part.source || 'warehouse';

        return {
            ...part,
            name: part.part?.name_ar || part.part?.name_en || part.name || '',
            part_number: part.part?.sku || part.part_number || '',
            source: resolvedSource,
            is_external: resolvedSource === 'external',
            mapped_allocation: linkedPartAllocations.value[part.id] || null,
            total: net,
            discount: discount,
            tax_amount: taxAmount,
            total_incl_tax: isInclusive ? net : net + taxAmount,
        };
    });
});

const externalPartsList = computed(() =>
    normalizedPartsForDisplay.value.filter((part) => part.source === 'external')
);

// Payment computed properties
const hasTax = computed(() => {
    return !!props.workOrder?.tax_enabled_snapshot;
});

// Refresh work order data// Refresh work order data
function refreshWorkOrder() {
    router.reload({ only: ['workOrder'] });
}

// Debounced auto-save for the condition report (fuel_level, damage_marks).
// Uses a queue pattern: if changes happen while a save is in flight, the
// latest values are saved again when the in-flight request finishes. This
// prevents the previous race where changes made during the request were
// silently dropped by the `isSavingCondition` guard.
const CONDITION_DEBOUNCE_MS = 600;
let conditionReportTimer = null;
let conditionReportInFlight = false;
let conditionReportPending = false;

const normalizeDamageMarks = (marks) => (Array.isArray(marks) ? marks.map((m) => ({ ...m })) : []);

const conditionFuelLevel = ref(props.workOrder?.fuel_level ?? 50);
const conditionDamageMarks = ref(normalizeDamageMarks(props.workOrder?.damage_marks));

function buildConditionSignature(fuel, marks) {
    return JSON.stringify({
        fuel_level: Number(fuel ?? 50),
        damage_marks: normalizeDamageMarks(marks),
    });
}

let lastSavedConditionSignature = buildConditionSignature(
    props.workOrder?.fuel_level,
    props.workOrder?.damage_marks
);

function getCurrentConditionPayload() {
    return {
        fuel_level: Number(conditionFuelLevel.value ?? 50),
        damage_marks: normalizeDamageMarks(conditionDamageMarks.value),
    };
}

function sendConditionReport() {
    if (isReadOnly.value) return;

    const payload = getCurrentConditionPayload();
    const payloadSignature = JSON.stringify(payload);
    if (payloadSignature === lastSavedConditionSignature) {
        return;
    }

    conditionReportInFlight = true;
    axios
        .put(route('app.work-orders.update-condition', props.workOrder.id), payload)
        .then(() => {
            lastSavedConditionSignature = payloadSignature;
            conditionReportInFlight = false;
            // If the user changed values while we were saving, persist
            // the latest snapshot now (single follow-up call).
            if (conditionReportPending) {
                conditionReportPending = false;
                sendConditionReport();
            }
        })
        .catch((err) => {
            conditionReportInFlight = false;
            conditionReportPending = false;
            const message =
                err?.response?.data?.message ||
                t('messages.error_occurred', 'Error saving changes');
            errorToast(message);
        });
}

function flushConditionReportSave() {
    if (conditionReportTimer) {
        clearTimeout(conditionReportTimer);
        conditionReportTimer = null;
        sendConditionReport();
    }
}

function scheduleConditionReportSave() {
    if (isReadOnly.value) return;

    const currentSignature = JSON.stringify(getCurrentConditionPayload());
    if (currentSignature === lastSavedConditionSignature) {
        return;
    }

    // Always reset the debounce window so we wait for the user to settle.
    if (conditionReportTimer) clearTimeout(conditionReportTimer);

    // A save is currently in flight — queue one follow-up with the latest
    // values. The onFinish handler will fire it.
    if (conditionReportInFlight) {
        conditionReportPending = true;
        return;
    }

    conditionReportTimer = setTimeout(() => {
        conditionReportTimer = null;
        sendConditionReport();
    }, CONDITION_DEBOUNCE_MS);
}

watch(
    () => [props.workOrder?.id, props.workOrder?.fuel_level, props.workOrder?.damage_marks],
    ([, newFuel, newMarks]) => {
        if (conditionReportInFlight || conditionReportTimer) return;
        const incomingSignature = buildConditionSignature(newFuel, newMarks);
        lastSavedConditionSignature = incomingSignature;
        conditionFuelLevel.value = Number(newFuel ?? 50);
        conditionDamageMarks.value = normalizeDamageMarks(newMarks);
    },
    { deep: true }
);

watch(conditionFuelLevel, () => {
    scheduleConditionReportSave();
});

watch(
    conditionDamageMarks,
    () => {
        scheduleConditionReportSave();
    },
    { deep: true }
);

watch(activeTab, (newTab, oldTab) => {
    if (oldTab === 'condition' && newTab !== 'condition') {
        flushConditionReportSave();
    }
});

onBeforeUnmount(() => {
    flushConditionReportSave();
});

// Handle part saved
function handlePartSaved(data, options = {}) {
    if (options.close !== false) {
        showAddPartModal.value = false;
    }
    success(t('common.saved_success'));
    router.reload({ only: ['workOrder', 'itemsByDepartment'] });
}

// Delete photo
async function deletePhoto(photo) {
    const confirmed = await confirm({
        title: t('common.confirm_delete'),
        message: t('work_orders.photos.delete_confirm'),
        confirmText: t('common.delete'),
        cancelText: t('common.cancel'),
        type: 'danger',
    });
    if (!confirmed) return;

    router.delete(route('work-orders.photos.destroy', [props.workOrder.id, photo.id]), {
        onSuccess: () => {
            success(t('common.deleted_success'));
        },
    });
}

// Delete attachment
async function deleteAttachment(attachment) {
    const confirmed = await confirm({
        title: t('common.confirm_delete'),
        message: t('common.confirm_delete_message'),
        confirmText: t('common.delete'),
        cancelText: t('common.cancel'),
        type: 'danger',
    });
    if (!confirmed) return;

    router.delete(route('work-orders.attachments.destroy', [props.workOrder.id, attachment.id]), {
        onSuccess: () => {
            success(t('common.deleted_success'));
        },
    });
}

// Handle print option selection
function handlePrint(type) {
    const woId = props.workOrder.id;
    let url = '';

    switch (type) {
        case 'condition':
            url = route('work-orders.print.condition', woId);
            break;
        case 'work_order':
            url = route('work-orders.print.services', woId);
            break;
        case 'proforma':
            if (props.workOrder.invoice && props.workOrder.invoice.id) {
                url = route('app.invoices.print', props.workOrder.invoice.id);
            } else {
                url = route('work-orders.print.proforma', woId);
            }
            break;
        case 'payments':
            url = route('work-orders.print.payments', woId);
            break;
    }

    if (url) {
        window.open(url, '_blank');
    }
}

// Handle printing a specific department's services
function handlePrintDepartment(deptId) {
    const url = route('work-orders.print.services', [
        props.workOrder.id,
        { department_id: deptId },
    ]);
    window.open(url, '_blank');
}

// Delete work order part
async function deleteWorkOrderPart(part) {
    if (part.source === 'warehouse' && part.status === 'issued') {
        editingReturnPart.value = part;
        showReturnModal.value = true;
    } else {
        const confirmed = await confirm({
            title: t('common.confirm_delete_title'),
            message: t('common.confirm_delete_message'),
            confirmText: t('common.delete'),
            cancelText: t('common.cancel'),
            type: 'danger',
        });

        if (confirmed) {
            router.delete(route('work-orders.parts.destroy', part.id), {
                onSuccess: () => success(t('common.deleted_success')),
            });
        }
    }
}

function onPartSaved() {
    success(t('common.saved_success'));
    router.reload({ only: ['workOrder', 'itemsByDepartment'] });
    showIssueMoreModal.value = false;
    showReturnModal.value = false;
    editingIssueMorePart.value = null;
    editingReturnPart.value = null;
}

const tabs = computed(() => {
    const allTabs = [
        { key: 'services', label: t('work_orders.show.tabs.services'), icon: '🔧' },
        { key: 'parts', label: t('work_orders.show.tabs.parts'), icon: '🔩' },
        { key: 'technicians', label: t('work_orders.show.tabs.technicians'), icon: '👷' },
        { key: 'payments', label: t('work_orders.show.tabs.payments'), icon: '💰' },
        {
            key: 'purchase_invoices',
            label: t('work_orders.show.tabs.purchase_invoices'),
            icon: '🧾',
        },
        { key: 'condition', label: t('work_orders.show.tabs.condition'), icon: '🚗' },
        { key: 'photos', label: t('work_orders.show.tabs.photos'), icon: '📸' },
        { key: 'notes', label: t('work_orders.show.tabs.notes'), icon: '📝' },
        { key: 'attachments', label: t('work_orders.show.tabs.attachments'), icon: '📎' },
        { key: 'inspections', label: t('work_orders.show.tabs.inspections'), icon: '🔍' },
        { key: 'signatures', label: t('work_orders.show.tabs.signatures'), icon: '✍️' },
        { key: 'activities', label: t('work_orders.show.tabs.activities'), icon: '📋' },
    ];

    if (props.enableSystematicInspections) {
        return allTabs;
    }

    return allTabs.filter((tab) => tab.key !== 'inspections');
});

function handleSaved() {
    showEditModal.value = false;
    success(t('common.saved_success'));
    router.reload();
}

// ─── Deferred Invoice ────────────────────────────────────────────────────────
const showDeferredInvoiceModal = ref(false);
const deferredDueDate = ref('');
const deferredNotes = ref('');
const isDeferredSubmitting = ref(false);

const todayDate = computed(() => new Date().toISOString().split('T')[0]);

function closeDeferredInvoiceModal() {
    showDeferredInvoiceModal.value = false;
    deferredDueDate.value = '';
    deferredNotes.value = '';
    isDeferredSubmitting.value = false;
}

function confirmDeferredInvoice() {
    if (!deferredDueDate.value || isDeferredSubmitting.value) return;
    isDeferredSubmitting.value = true;
    router.post(
        route('app.work-orders.invoice', props.workOrder.id),
        {
            due_date: deferredDueDate.value,
            notes: deferredNotes.value || null,
        },
        {
            onSuccess: () => {
                closeDeferredInvoiceModal();
            },
            onError: () => {
                isDeferredSubmitting.value = false;
            },
            onFinish: () => {
                isDeferredSubmitting.value = false;
            },
        }
    );
}
// ─────────────────────────────────────────────────────────────────────────────

// changeStatus, exit/hold modals live in useWorkOrderStatus composable
</script>
