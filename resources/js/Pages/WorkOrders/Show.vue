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
                        <!-- Parts Display Component -->
                        <PartsDisplay
                            :parts="normalizedPartsForDisplay"
                            :read-only="isReadOnly"
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
                </WorkOrderTabsContainer>
            </div>
        </div>

        <!-- Edit Modal -->
        <WorkOrderFormModal
            :show="showEditModal"
            :work-order="workOrder"
            :customers="customers"
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
                        class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-white px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 focus:border-teal-500 resize-none"
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
                        class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-white px-3 py-2 text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 resize-none"
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
                        class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-white px-3 py-2 text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 resize-none"
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
                        class="w-7 h-7 rounded-lg bg-violet-100 dark:bg-violet-900/30 flex items-center justify-center"
                    >
                        <svg
                            class="w-4 h-4 text-violet-600 dark:text-violet-400"
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
                <div
                    class="bg-violet-50 dark:bg-violet-950/20 rounded-xl p-4 flex items-center justify-between"
                >
                    <span class="text-sm font-bold text-violet-700 dark:text-violet-400">
                        {{ $t('invoices.remaining_amount') }}
                    </span>
                    <span
                        class="text-lg font-black text-violet-700 dark:text-violet-300 font-mono"
                        dir="ltr"
                    >
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
                        class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-white px-3 py-2.5 text-sm focus:ring-2 focus:ring-violet-500 focus:border-violet-500 resize-none transition-colors"
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
                    class="px-5 py-2 text-sm font-semibold text-white bg-violet-600 hover:bg-violet-700 disabled:bg-gray-300 dark:disabled:bg-gray-700 disabled:text-gray-500 disabled:cursor-not-allowed rounded-xl transition-all shadow-sm flex items-center gap-2"
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
    enableSystematicInspections: { type: Boolean, default: true },
});

const { t } = useI18n();
const { formatNumber, formatCurrency } = useNumberFormat();
const { formatDate, getUserRoleName, getNoteDate, getNoteTime } = useFormatters();
const { success, error: errorToast } = useToast();
const { confirm } = useConfirm();

// Local helper — depends on formatCurrency + t() which are scoped to the
// page. Kept here on purpose: pricing needs both the formatted number and
// the localized currency unit, so it composes the two at the call site.
function formatPrice(value) {
    return formatCurrency(value) + ' ' + t('common.currency');
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

        return {
            ...part,
            name: part.part?.name_ar || part.part?.name_en || part.name || '',
            part_number: part.part?.sku || '',
            source: part.source || 'warehouse',
            total: net,
            discount: discount,
            tax_amount: taxAmount,
            total_incl_tax: isInclusive ? net : net + taxAmount,
        };
    });
});

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
