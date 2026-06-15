<template>
    <AppLayout>
        <div class="space-y-6">
            <!-- Header Actions Bar (extracted to WorkOrderHeader) -->
            <WorkOrderHeader
                :work-order="workOrder"
                :is-read-only="isReadOnly"
                @print="showPrintModal = true"
                @payments="showPaymentsListModal = true"
                @edit="showEditModal = true"
                @change-status="changeStatus"
            />

            <!-- Top Section: Financial Summary & Customer Info -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- 1. Right Card: Vehicle & Customer Info (extracted to WorkOrderCustomerCard) -->
                <WorkOrderCustomerCard :work-order="workOrder" :colors="colors" />

                <!-- 2. Left Card: Financial Summary (extracted to WorkOrderFinancialSummary) -->
                <WorkOrderFinancialSummary
                    :totals="totals"
                    :has-tax="hasTax"
                    :tax-rate="workOrder.tax_rate_snapshot || 15"
                    :total-paid="workOrderTotalPaid"
                    :balance="workOrderBalance"
                />
            </div>

            <!-- Main Content Container with Info Cards -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700">
                <!-- Info Cards (extracted to WorkOrderInfoCards) -->
                <WorkOrderInfoCards :work-order="workOrder" :is-read-only="isReadOnly" />

                <!-- Customer Complaint & Initial Assessment (extracted to WorkOrderComplaintAssessment) -->
                <WorkOrderComplaintAssessment :work-order="workOrder" />

                <!-- Tabs container (extracted to WorkOrderTabsContainer) -->
                <WorkOrderTabsContainer
                    :tabs="tabs"
                    v-model:active-tab="activeTab"
                >
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
                            @delete-item="deleteServiceItem"
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
                        <PartsDisplay :parts="normalizedPartsForDisplay" :read-only="isReadOnly"
                            storage-key="work_orders_parts_view_mode" :empty-message="$t('work_orders.show.no_parts')"
                            :add-button-text="$t('inventory.parts.add_to_wo')" @delete="deleteWorkOrderPart"
                            @edit="editWorkOrderPart" @add="openAddPartModal"
                            @click-service="handlePartServiceClick" />
                    </div>

                    <!-- Technicians Tab -->
                    <div v-if="activeTab === 'technicians'" key="tab-technicians">
                        <TechniciansSection 
                            :work-order="workOrder" 
                            :items-by-department="itemsByDepartment"
                            :technicians="technicians"
                            :read-only="isReadOnly"
                            @click-service="openServiceTechniciansModal"
                        />
                    </div>

                    <!-- Payments Tab -->
                    <div v-if="activeTab === 'payments'" key="tab-payments">
                        <PaymentsSection :work-order-id="workOrder.id" :payments="workOrder.payments || []"
                            :grand-total="workOrderTotal" :total-paid="workOrderTotalPaid" :balance="workOrderBalance"
                            :read-only="isReadOnly" @refresh="refreshWorkOrder" />
                    </div>

                    <!-- Condition Report Tab -->
                    <div v-if="activeTab === 'condition'" key="tab-condition" class="max-w-4xl mx-auto">
                        <VehicleConditionReport v-model:damageMarks="workOrder.damage_marks"
                            v-model:fuelLevel="workOrder.fuel_level" :class="{ 'pointer-events-none': isReadOnly }" />
                    </div>

                    <!-- Photos Tab -->
                    <div v-if="activeTab === 'photos'" key="tab-photos" class="space-y-4">
                        <!-- Toolbar: Title & Add Button next to it -->
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
                            <div class="flex items-center gap-4">
                                <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
                                    <span class="text-xl">📸</span>
                                    {{ $t('work_orders.show.tabs.photos') }}
                                </h3>

                                <button v-if="!isReadOnly" @click="showPhotoModal = true"
                                    class="inline-flex items-center gap-2 px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 active:bg-indigo-800 text-white text-xs font-bold rounded-xl transition-all shadow-sm shadow-indigo-100 dark:shadow-none">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 4v16m8-8H4" />
                                    </svg>
                                    {{ $t('common.add') }}
                                </button>
                            </div>
                        </div>

                        <div v-if="workOrder.photos?.length" class="grid grid-cols-2 md:grid-cols-4 gap-4">
                            <div v-for="photo in workOrder.photos" :key="photo.id"
                                class="relative group aspect-square rounded-xl overflow-hidden bg-gray-100 dark:bg-gray-900 border border-gray-200 dark:border-gray-700">
                                <img :src="`/storage/${photo.path}`" class="w-full h-full object-cover" />

                                <!-- Hover Overlay with Actions -->
                                <div
                                    class="absolute inset-0 bg-black/60 opacity-0 group-hover:opacity-100 transition-all flex items-center justify-center gap-3">
                                    <!-- View Button -->
                                    <a :href="`/storage/${photo.path}`" target="_blank"
                                        class="w-10 h-10 rounded-full bg-white/20 hover:bg-white/40 flex items-center justify-center text-white transition-all"
                                        :title="$t('common.view')">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                    </a>
                                    <!-- Download Button -->
                                    <a :href="`/storage/${photo.path}`" :download="photo.path.split('/').pop()"
                                        class="w-10 h-10 rounded-full bg-white/20 hover:bg-white/40 flex items-center justify-center text-white transition-all"
                                        :title="$t('common.download')">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                        </svg>
                                    </a>
                                    <!-- Delete Button -->
                                    <button v-if="!isReadOnly" type="button" @click="deletePhoto(photo)"
                                        class="w-10 h-10 rounded-full bg-red-500/80 hover:bg-red-600 flex items-center justify-center text-white transition-all"
                                        :title="$t('common.delete')">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </div>

                                <!-- Photo Info Overlay -->
                                <div
                                    class="absolute inset-x-0 bottom-0 bg-black/60 p-2 text-white text-xs backdrop-blur-sm">
                                    <p class="font-bold uppercase">{{ $t(`work_orders.photos.types.${photo.type}`) }}
                                    </p>
                                    <p v-if="photo.caption" class="truncate">{{ photo.caption }}</p>
                                </div>
                            </div>
                        </div>
                        <div v-else class="text-center py-12">
                            <div
                                class="w-16 h-16 mx-auto bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center mb-4">
                                <span class="text-2xl">📸</span>
                            </div>
                            <p class="text-gray-500 dark:text-gray-400">{{ $t('work_orders.photos.no_photos') }}</p>
                        </div>
                    </div>

                    <!-- Activities Tab -->
                    <div v-if="activeTab === 'activities'" key="tab-activities" class="space-y-6">
                        <div v-if="workOrder.activities?.length" class="relative">
                            <!-- Timeline Line -->
                            <div class="absolute start-8 top-0 bottom-0 w-0.5 bg-gray-200 dark:bg-gray-700"></div>

                            <div class="space-y-8 relative">
                                <div v-for="activity in workOrder.activities" :key="activity.id" class="flex gap-4">
                                    <!-- Icon/Marker -->
                                    <div class="w-16 flex justify-center flex-shrink-0 relative">
                                        <div
                                            class="w-10 h-10 rounded-full bg-white dark:bg-gray-800 border-2 border-indigo-500 flex items-center justify-center z-10 shadow-sm">
                                            <span v-if="activity.action === 'created'">🆕</span>
                                            <span v-else-if="activity.action === 'status_changed'">🔄</span>
                                            <span v-else-if="activity.action === 'item_added'">🔧</span>
                                            <span v-else-if="activity.action === 'item_updated'">📝</span>
                                            <span v-else-if="activity.action === 'item_deleted'">🗑️</span>
                                            <span v-else-if="activity.action === 'payment_added'">💰</span>
                                            <span v-else-if="activity.action === 'condition_updated'">🚗</span>
                                            <span v-else-if="activity.action === 'photos_uploaded'">📸</span>
                                            <span v-else-if="activity.action === 'attachments_uploaded'">📎</span>
                                            <span v-else-if="activity.action.includes('part')">🔩</span>
                                            <span v-else-if="activity.action.includes('technician')">👷</span>
                                            <span v-else>📝</span>
                                        </div>
                                    </div>

                                    <!-- Content -->
                                    <div
                                        class="flex-1 bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4 shadow-sm">
                                        <div class="flex flex-wrap items-center justify-between gap-2 mb-2">
                                            <h4 class="text-sm font-bold text-gray-900 dark:text-white">
                                                {{ getActivityDescription(activity) }}
                                            </h4>
                                            <span class="text-xs text-gray-400 font-medium">{{
                                                formatDateTime(activity.created_at)
                                                }}</span>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <div
                                                class="w-6 h-6 rounded-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center text-[10px] font-bold">
                                                {{ activity.user?.name?.charAt(0) }}
                                            </div>
                                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                                {{ activity.user?.name || $t('common.system') }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div v-else class="text-center py-12">
                            <div
                                class="w-16 h-16 mx-auto bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center mb-4">
                                <span class="text-2xl">📋</span>
                            </div>
                            <p class="text-gray-500 dark:text-gray-400">{{ $t('work_orders.activities.no_activities') }}
                            </p>
                        </div>
                    </div>

                    <!-- Attachments Tab -->
                    <div v-if="activeTab === 'attachments'" key="tab-attachments" class="space-y-4">
                        <!-- Toolbar: Title & Add Button next to it -->
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
                            <div class="flex items-center gap-4">
                                <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
                                    <span class="text-xl">📎</span>
                                    {{ $t('work_orders.show.tabs.attachments') }}
                                </h3>

                                <button v-if="!isReadOnly" @click="showAttachmentModal = true"
                                    class="inline-flex items-center gap-2 px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 active:bg-indigo-800 text-white text-xs font-bold rounded-xl transition-all shadow-sm shadow-indigo-100 dark:shadow-none">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 4v16m8-8H4" />
                                    </svg>
                                    {{ $t('common.add') }}
                                </button>
                            </div>
                        </div>

                        <div v-if="workOrder.attachments?.length"
                            class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                            <div v-for="attachment in workOrder.attachments" :key="attachment.id"
                                class="flex items-center justify-between p-3 bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm hover:shadow-md transition-shadow">
                                <div class="flex items-center gap-3 min-w-0">
                                    <div
                                        class="w-12 h-12 rounded-lg bg-gray-50 dark:bg-gray-900 flex items-center justify-center flex-shrink-0">
                                        <svg v-if="attachment.file_type === 'pdf'" class="w-6 h-6 text-red-500"
                                            fill="currentColor" viewBox="0 0 24 24">
                                            <path
                                                d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 14H9v-2h2v2zm0-4H9V7h2v5z" />
                                        </svg>
                                        <svg v-else class="w-6 h-6 text-blue-500" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                        </svg>
                                    </div>
                                    <div class="min-w-0">
                                        <p class="text-sm font-bold text-gray-900 dark:text-white truncate"
                                            :title="attachment.file_name">{{ attachment.file_name }}</p>
                                        <p class="text-[10px] text-gray-400 font-bold uppercase tracking-wider">
                                            {{ formatFileSize(attachment.file_size) }} • {{ attachment.user?.name }}
                                        </p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-1">
                                    <a :href="`/storage/${attachment.path}`" target="_blank"
                                        class="p-2 text-gray-400 hover:text-indigo-500 transition-colors"
                                        :title="$t('common.view')">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                    </a>
                                    <button v-if="!isReadOnly" type="button" @click="deleteAttachment(attachment)"
                                        class="p-2 text-gray-400 hover:text-red-500 transition-colors"
                                        :title="$t('common.delete')">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div v-else class="text-center py-12">
                            <div
                                class="w-16 h-16 mx-auto bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center mb-4">
                                <span class="text-2xl">📎</span>
                            </div>
                            <p class="text-gray-500 dark:text-gray-400">{{ $t('common.no_data') }}</p>
                        </div>
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
        <WorkOrderFormModal :show="showEditModal" :work-order="workOrder" :customers="customers"
            :departments="departments" :makes="makes" :colors="colors" :modelsByMake="modelsByMake"
            @close="showEditModal = false" @saved="handleSaved" />

        <!-- Item Edit/Add Modal (unified) -->
        <WorkOrderServiceModal v-if="showItemModal || showServiceModal" :show="showItemModal || showServiceModal"
            :work-order="workOrder" :item="selectedItem" :department-id="selectedDepartmentId" :services="services"
            :technicians="technicians" :inventory-units="inventoryUnits" :warehouses="warehouses"
            :read-only="isReadOnly" :initial-tab="serviceModalInitialTab" @close="showItemModal ? closeItemModal() : closeServiceModal()"
            @saved="showItemModal ? handleItemSaved() : handleServiceSaved()" />

        <!-- Add Part Modal -->
        <WorkOrderPartModal :show="showAddPartModal" :workOrder="workOrder" :part="selectedPartToEdit"
            :units="inventoryUnits" :warehouses="warehouses" :show-service-select="true"
            :workOrderItems="workOrder.items" @close="closeAddPartModal" @saved="handlePartSaved" />

        <!-- Print Options Modal -->
        <PrintOptionsModal :show="showPrintModal" :work-order="workOrder" @close="showPrintModal = false"
            @print="handlePrint" />

        <!-- Payments List Modal (New) -->
        <PaymentsListModal :show="showPaymentsListModal" :work-order-id="workOrder.id"
            :payments="workOrder.payments || []" :grand-total="workOrderTotal" :total-paid="workOrderTotalPaid"
            :balance="workOrderBalance" @close="showPaymentsListModal = false" @refresh="refreshWorkOrder" />
        <WorkOrderPhotoModal v-if="showPhotoModal" :show="showPhotoModal" :work-order="workOrder"
            @close="showPhotoModal = false" @saved="refreshWorkOrder" />

        <WorkOrderAttachmentModal v-if="showAttachmentModal" :show="showAttachmentModal" :work-order="workOrder"
            @close="showAttachmentModal = false" @saved="refreshWorkOrder" />



        <!-- Add Note Modal -->
        <BaseModal :show="showAddNoteModal" @close="showAddNoteModal = false" size="md">
            <template #title>
                {{ $t('work_orders.item.add_note') }}
            </template>

            <form @submit.prevent="handleAddNote" class="space-y-4 text-right">
                <div class="space-y-1">
                    <!-- Note Content -->
                    <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">
                        {{ $t('work_orders.item.tab_notes') }}
                    </label>
                    <textarea v-model="newNoteContent" required rows="5"
                        :placeholder="$t('work_orders.item.note_placeholder')"
                        class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-white px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 focus:border-teal-500 resize-none"></textarea>
                </div>
            </form>

            <template #footer>
                <button type="button" @click="showAddNoteModal = false"
                    class="px-5 py-2 text-sm font-semibold text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/50 rounded-xl transition-all">
                    {{ $t('common.cancel') }}
                </button>
                <button type="submit" @click="handleAddNote" :disabled="isSubmittingNote || !newNoteContent.trim()"
                    class="px-5 py-2 text-sm font-semibold text-white bg-teal-600 hover:bg-teal-700 disabled:bg-gray-300 dark:disabled:bg-gray-700 disabled:text-gray-500 disabled:cursor-not-allowed rounded-xl transition-all shadow-sm flex items-center gap-2">
                    <svg v-if="isSubmittingNote" class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span>{{ $t('common.save') }}</span>
                </button>
            </template>
        </BaseModal>
    </AppLayout>
</template>

<script setup>
import BackButton from '@/Components/BackButton.vue';
import { ref, computed, watch } from 'vue';
import { Link, router } from '@inertiajs/vue3';
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
import WorkOrderCustomerCard from '@/Components/WorkOrders/WorkOrderCustomerCard.vue';
import WorkOrderFinancialSummary from '@/Components/WorkOrders/WorkOrderFinancialSummary.vue';
import WorkOrderInfoCards from '@/Components/WorkOrders/WorkOrderInfoCards.vue';
import WorkOrderComplaintAssessment from '@/Components/WorkOrders/WorkOrderComplaintAssessment.vue';
import WorkOrderTabsContainer from '@/Components/WorkOrders/WorkOrderTabsContainer.vue';
import WorkOrderServicesTab from '@/Components/WorkOrders/WorkOrderServicesTab.vue';
import WorkOrderNotesTab from '@/Components/WorkOrders/WorkOrderNotesTab.vue';
import BaseModal from '@/Components/BaseModal.vue';

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
    warehouses: { type: Array, default: () => [] },
    inventoryParts: { type: Array, default: () => [] },
    inventoryUnits: { type: Array, default: () => [] },
    enableSystematicInspections: { type: Boolean, default: true },
});

const { t, te } = useI18n();
const { getName } = useLocalized();
const { formatNumber, formatCurrency } = useNumberFormat();
const {
    formatDate,
    formatDateTime,
    formatFileSize,
    getUserRoleName,
    getNoteDate,
    getNoteTime,
} = useFormatters();
const { success, error: errorToast } = useToast();
const { confirm } = useConfirm();

// Local helper — depends on formatCurrency + t() which are scoped to the
// page. Kept here on purpose: pricing needs both the formatted number and
// the localized currency unit, so it composes the two at the call site.
function formatPrice(value) {
    return formatCurrency(value) + ' ' + t('common.currency');
}

function getActivityDescription(activity) {
    const key = `work_orders.activities.actions.${activity.action}`;
    if (!te(key)) {
        return activity.description;
    }
    const translation = t(key);
    if (translation.includes(':') || translation.includes('{')) {
        return activity.description;
    }
    return translation;
}

// Sentinel keys used inside the `itemsByDepartment` map. The controller
// groups items either by department id (positive int) or by these two
// virtual buckets: "packages" (service bundles) and "0" (legacy/unassigned).
// Centralizing them prevents typos when reading the map and when comparing
// to the same key in the template.
const PACKAGES_DEPT_KEY = 'packages';
const UNASSIGNED_DEPT_KEY = '0';

const showEditModal = ref(false);
const activeTab = ref('services');
const showDeptMenu = ref(false);
const expandedDepartments = ref([]);
const showAddPartModal = ref(false);
const showPrintModal = ref(false);
const showPaymentsListModal = ref(false);
const showPhotoModal = ref(false);
const showAttachmentModal = ref(false);
const selectedPartToEdit = ref(null);

function openAddPartModal() {
    selectedPartToEdit.value = null;
    showAddPartModal.value = true;
}

function editWorkOrderPart(part) {
    selectedPartToEdit.value = part;
    showAddPartModal.value = true;
}

function closeAddPartModal() {
    showAddPartModal.value = false;
    selectedPartToEdit.value = null;
}

// Read-only mode for closed work orders
const isReadOnly = computed(() => {
    const closedStatuses = ['done', 'cancelled', 'closed'];
    return closedStatuses.includes(props.workOrder.status);
});

// All parts from the work order (using the loaded relationship)
const allWorkOrderParts = computed(() => {
    return props.workOrder.parts || [];
});

const normalizedPartsForDisplay = computed(() => {
    const isInclusive = props.workOrder?.pricing_mode_snapshot === 'inclusive';
    const taxRate = Number(props.workOrder?.tax_rate_snapshot || 15);
    const taxFactor = 1 + (taxRate / 100);
    const taxEnabled = !!props.workOrder?.tax_enabled_snapshot;

    return allWorkOrderParts.value.map(part => {
        const qty = Number(part.qty || 0);
        const unitPrice = Number(part.unit_price || 0);
        const discount = Number(part.discount || 0);
        const net = (qty * unitPrice) - discount;

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
            total_incl_tax: isInclusive ? net : (net + taxAmount)
        };
    });
});

// Payment computed properties
const hasTax = computed(() => {
    return !!props.workOrder?.tax_enabled_snapshot;
});

const totals = computed(() => {
    const t = {
        services: { price: 0, discount: 0, amount: 0, tax: 0, total: 0 },
        parts: { price: 0, discount: 0, amount: 0, tax: 0, total: 0 },
        grand: { price: 0, discount: 0, amount: 0, tax: 0, total: 0 },
    };

    const isInclusive = props.workOrder?.pricing_mode_snapshot === 'inclusive';
    const taxRate = Number(props.workOrder?.tax_rate_snapshot || 15);
    const taxFactor = 1 + (taxRate / 100);
    const taxEnabled = !!props.workOrder?.tax_enabled_snapshot;

    // Services calculation
    if (props.workOrder?.items && props.workOrder.items.length > 0) {
        props.workOrder.items.forEach(line => {
            const price = Number(line.unit_price || 0) * Number(line.qty || 1);
            const discount = Number(line.discount_amount || 0);

            let amount = price - discount;
            let calculatedTax = Number(line.tax_amount || 0);
            let total = Number(line.line_total || price);

            // Compute frontend tax correctly if missing or for inclusive view separation
            if (taxEnabled) {
                if (isInclusive) {
                    amount = (price - discount) / taxFactor;
                    calculatedTax = (price - discount) - amount;
                } else if (calculatedTax === 0) {
                    calculatedTax = amount * (taxRate / 100);
                    total = amount + calculatedTax;
                }
            }

            // Always trust line_total if provided directly, otherwise compute
            if (!line.line_total) {
                total = isInclusive ? (price - discount) : (amount + calculatedTax);
            }

            t.services.price += price;
            t.services.discount += discount;
            t.services.tax += calculatedTax;
            t.services.total += total;
            t.services.amount += amount;
        });
    }

    // Parts calculation (using unified parts relationship)
    if (props.workOrder?.parts && props.workOrder.parts.length > 0) {
        props.workOrder.parts.forEach(part => {
            const partQty = Number(part.qty || 0);
            const partUnitPrice = Number(part.unit_price || 0);
            const partDiscount = Number(part.discount || 0);

            const partPrice = partQty * partUnitPrice;
            const partNet = partPrice - partDiscount;

            t.parts.price += partPrice;
            t.parts.discount += partDiscount;

            let partAmount = partNet;
            let partTax = 0;

            if (taxEnabled) {
                if (isInclusive) {
                    partAmount = partNet / taxFactor;
                    partTax = partNet - partAmount;
                } else {
                    partTax = partNet * (taxRate / 100);
                }
            }

            t.parts.amount += partAmount;
            t.parts.tax += partTax;
            t.parts.total += partTax + partAmount;
        });
    }

    t.grand.price = t.services.price + t.parts.price;
    t.grand.discount = t.services.discount + t.parts.discount;
    t.grand.amount = t.services.amount + t.parts.amount;
    t.grand.tax = t.services.tax + t.parts.tax;
    t.grand.total = t.services.total + t.parts.total;

    return t;
});

const servicesTotal = computed(() => totals.value.services.total);
const partsTotal = computed(() => totals.value.parts.total);
const workOrderTotal = computed(() => totals.value.grand.total);

const workOrderTotalPaid = computed(() => {
    return props.workOrder.payments?.reduce((sum, p) => {
        const amount = parseFloat(p.amount || 0);
        return p.type === 'refund' ? sum - amount : sum + amount;
    }, 0) || 0;
});

const workOrderBalance = computed(() => {
    return workOrderTotal.value - workOrderTotalPaid.value;
});

// Refresh work order data
function refreshWorkOrder() {
    router.reload({ only: ['workOrder'] });
}

// Debounced auto-save for the condition report (fuel_level, damage_marks).
// Uses a queue pattern: if changes happen while a save is in flight, the
// latest values are saved again when the in-flight request finishes. This
// prevents the previous race where changes made during the request were
// silently dropped by the `isSavingCondition` guard.
const CONDITION_DEBOUNCE_MS = 1000;
let conditionReportTimer = null;
let conditionReportInFlight = false;
let conditionReportPending = false;

function getCurrentConditionPayload() {
    return {
        fuel_level: props.workOrder.fuel_level,
        damage_marks: props.workOrder.damage_marks,
    };
}

function sendConditionReport() {
    if (isReadOnly.value) return;

    conditionReportInFlight = true;
    axios.put(
        route('app.work-orders.update-condition', props.workOrder.id),
        getCurrentConditionPayload()
    ).then(() => {
        conditionReportInFlight = false;
        // If the user changed values while we were saving, persist
        // the latest snapshot now (single follow-up call).
        if (conditionReportPending) {
            conditionReportPending = false;
            sendConditionReport();
        }
    }).catch(() => {
        conditionReportInFlight = false;
        conditionReportPending = false;
    });
}

function scheduleConditionReportSave() {
    if (isReadOnly.value) return;

    // Always reset the debounce window so we wait for the user to settle.
    if (conditionReportTimer) clearTimeout(conditionReportTimer);

    // A save is currently in flight — queue one follow-up with the latest
    // values. The onFinish handler will fire it.
    if (conditionReportInFlight) {
        conditionReportPending = true;
        return;
    }

    conditionReportTimer = setTimeout(() => {
        sendConditionReport();
    }, CONDITION_DEBOUNCE_MS);
}

// Watch fuel level and damage marks changes (skip initial value).
// The queueing logic in scheduleConditionReportSave makes the watcher
// safe to fire on every change, even during an in-flight save.
watch(() => props.workOrder.fuel_level, (newVal, oldVal) => {
    if (oldVal !== undefined && newVal !== oldVal) {
        scheduleConditionReportSave();
    }
});
watch(() => props.workOrder.damage_marks, (newVal, oldVal) => {
    if (oldVal !== undefined && JSON.stringify(newVal) !== JSON.stringify(oldVal)) {
        scheduleConditionReportSave();
    }
}, { deep: true });

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
        }
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
        }
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
            url = route('work-orders.print.proforma', woId);
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
    const url = route('work-orders.print.services', [props.workOrder.id, { department_id: deptId }]);
    window.open(url, '_blank');
}

// Delete work order part
async function deleteWorkOrderPart(part) {
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

// Get departments that have items or are linked to work order
const displayDepartments = computed(() => {
    const deptIds = new Set();

    // Add departments with items
    Object.keys(props.itemsByDepartment).forEach(id => {
        if (id !== UNASSIGNED_DEPT_KEY && id !== PACKAGES_DEPT_KEY) deptIds.add(parseInt(id));
    });

    // Add work order's linked departments
    props.workOrder.departments?.forEach(dept => deptIds.add(dept.id));

    // Get database departments matching active list
    const list = props.departments.filter(d => deptIds.has(d.id));

    // Virtual packages section - only show if it has package items or show_packages_section is active
    const packageItems = props.itemsByDepartment[PACKAGES_DEPT_KEY];
    const hasPackageItems = packageItems && packageItems.length > 0;
    const showPackagesSection = props.workOrder.show_packages_section;

    if (hasPackageItems || showPackagesSection) {
        list.push({
            id: PACKAGES_DEPT_KEY,
            name_ar: 'باقات الخدمات',
            name_en: 'Service Packages',
            is_virtual: true
        });
    }

    return list;
});

// Departments that can still be added
const availableDepartments = computed(() => {
    const usedIds = displayDepartments.value.map(d => d.id);
    const list = props.departments.filter(d => !usedIds.includes(d.id));

    // Append virtual packages department if not added and available
    const canEdit = !isReadOnly.value;
    const hasAvailablePackages = props.services?.some(s => s.type === 'package');
    if (canEdit && hasAvailablePackages && !usedIds.includes(PACKAGES_DEPT_KEY)) {
        list.push({
            id: PACKAGES_DEPT_KEY,
            name_ar: 'باقات الخدمات',
            name_en: 'Service Packages',
            name: 'Service Packages'
        });
    }

    return list;
});

// Get items for a specific department
function getDepartmentItems(deptId) {
    return props.itemsByDepartment[deptId] || [];
}

// Toggle department expansion
function toggleDepartment(deptId) {
    const idx = expandedDepartments.value.indexOf(deptId);
    if (idx > -1) {
        expandedDepartments.value.splice(idx, 1);
    } else {
        expandedDepartments.value.push(deptId);
    }
}

// Add department to work order
function addDepartment(deptId) {
    showDeptMenu.value = false;
    router.post(route('work-orders.departments.store', props.workOrder.id), {
        department_id: deptId,
    }, {
        onSuccess: () => {
            success(t('common.saved_success'));
            if (!expandedDepartments.value.includes(deptId)) {
                expandedDepartments.value.push(deptId);
            }
        },
    });
}

// Service modal state
const showServiceModal = ref(false);
const selectedItemId = ref(null);
const selectedDepartmentId = ref(null);

const selectedItem = computed(() => {
    if (!selectedItemId.value) return null;
    return props.workOrder?.items?.find(i => i.id === selectedItemId.value) || null;
});

// Services filtered by department
const departmentServices = computed(() => {
    if (!selectedDepartmentId.value) return [];
    if (selectedDepartmentId.value === PACKAGES_DEPT_KEY) {
        return props.services.filter(s => s.type === 'package');
    }
    return props.services.filter(s => s.department_id === selectedDepartmentId.value && s.type !== 'package');
});

// Open add service modal
function openAddServiceModal(deptId) {
    selectedDepartmentId.value = deptId;
    selectedItemId.value = null;
    showServiceModal.value = true;
}

// Open edit service modal (advanced modal with tabs)
function openEditServiceModal(item) {
    selectedItemId.value = item.id;
    serviceModalInitialTab.value = 'service';
    showItemModal.value = true;
}

const serviceModalInitialTab = ref('service');

function openServiceNotesModal(itemId) {
    const item = props.workOrder.items.find(i => i.id === itemId);
    if (item) {
        selectedItemId.value = itemId;
        serviceModalInitialTab.value = 'notes';
        showItemModal.value = true;
    }
}

function openServicePartsModal(itemId) {
    const item = props.workOrder.items.find(i => i.id === itemId);
    if (item) {
        selectedItemId.value = itemId;
        serviceModalInitialTab.value = 'parts';
        showItemModal.value = true;
    }
}

function openServiceTechniciansModal(itemId) {
    const item = props.workOrder.items.find(i => i.id === itemId);
    if (item) {
        selectedItemId.value = itemId;
        serviceModalInitialTab.value = 'technicians';
        showItemModal.value = true;
    }
}

function handlePartServiceClick(part) {
    const itemId = part.work_order_item_id || part.work_order_item?.id || part.workOrderItem?.id;
    if (itemId) {
        openServicePartsModal(itemId);
    }
}

// Close service modal
function closeServiceModal() {
    showServiceModal.value = false;
    selectedItemId.value = null;
    selectedDepartmentId.value = null;
    serviceModalInitialTab.value = 'service';
}

// Handle service saved
function handleServiceSaved() {
    closeServiceModal();
    success(t('common.saved_success'));
    router.reload({ only: ['workOrder', 'itemsByDepartment'] });
}

// Item Modal state
const showItemModal = ref(false);

// Close item modal
function closeItemModal() {
    showItemModal.value = false;
    selectedItemId.value = null;
    serviceModalInitialTab.value = 'service';
}

// Handle item saved
function handleItemSaved() {
    router.reload({ only: ['workOrder', 'itemsByDepartment'] });
}

// Delete service item
async function deleteServiceItem(item) {
    const confirmed = await confirm({
        title: t('common.confirm_delete_title'),
        message: t('common.confirm_delete_message'),
        confirmText: t('common.delete'),
        cancelText: t('common.cancel'),
        type: 'danger',
    });

    if (confirmed) {
        router.delete(route('work-orders.items.destroy', { work_order: props.workOrder.id, item: item.id }), {
            preserveScroll: true
        });
    }
}

const showAddNoteModal = ref(false);
const newNoteContent = ref('');
const isSubmittingNote = ref(false);

// Collect only general notes from work order general_notes relation (notes from services are not shown here)
const allNotes = computed(() => {
    const notes = props.workOrder?.general_notes || props.workOrder?.generalNotes || [];
    return notes
        .map(note => {
            const serviceTitle = note.work_order_item
                ? (getName(note.work_order_item.service) || note.work_order_item.title)
                : '';
            return {
                id: note.id,
                content: note.content,
                created_at: note.created_at,
                user: note.user,
                item_id: note.work_order_item_id || null,
                service_title_formatted: serviceTitle || t('work_orders.general_note'),
            };
        })
        .sort((a, b) => new Date(b.created_at) - new Date(a.created_at));
});
// Filtering of notes by search query now lives inside WorkOrderNotesTab
// (the tab owns its own `viewMode` + `searchQuery` state). Show.vue
// still owns `allNotes` because it derives the `service_title_formatted`
// field from the work-order tree, which is data the tab shouldn't touch.

// Add a note (General Note)
function handleAddNote() {
    if (!newNoteContent.value.trim()) return;
    
    isSubmittingNote.value = true;
    router.post(route('work-orders.notes.store', { 
        work_order: props.workOrder.id
    }), {
        content: newNoteContent.value
    }, {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            newNoteContent.value = '';
            showAddNoteModal.value = false;
            success(t('common.saved_success'));
        },
        onFinish: () => {
            isSubmittingNote.value = false;
        }
    });
}

// Delete a note
async function handleDeleteNote(itemId, noteId) {
    const confirmed = await confirm({
        title: t('common.confirm_delete_title'),
        message: t('common.confirm_delete_message'),
        confirmText: t('common.delete'),
        cancelText: t('common.cancel'),
        type: 'danger',
    });

    if (confirmed) {
        const deleteRoute = itemId 
            ? route('work-orders.items.notes.destroy', { work_order: props.workOrder.id, item: itemId, note: noteId })
            : route('work-orders.notes.destroy', { work_order: props.workOrder.id, note: noteId });

        router.delete(deleteRoute, {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {
                success(t('common.deleted_success'));
            }
        });
    }
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

    return allTabs.filter(tab => tab.key !== 'inspections');
});

function handleSaved() {
    showEditModal.value = false;
    success(t('common.saved_success'));
    router.reload();
}

// Remove department
async function removeDepartment(deptId) {
    const confirmed = await confirm({
        title: t('common.confirm_delete_title'),
        message: t('common.confirm_delete_message'),
        confirmText: t('common.delete'),
        cancelText: t('common.cancel'),
        type: 'danger',
    });

    if (confirmed) {
        router.delete(route('work-orders.departments.destroy', { work_order: props.workOrder.id, department_id: deptId }), {
            onSuccess: () => {
                success(t('common.deleted_success'));
            },
        });
    }
}

async function changeStatus(newStatus) {
    const statusLabels = {
        in_progress: t('work_orders.actions.start_work'),
        done: t('work_orders.actions.complete'),
        cancelled: t('work_orders.actions.cancel'),
    };

    const confirmed = await confirm({
        title: statusLabels[newStatus],
        message: t('work_orders.messages.confirm_status_change'),
        confirmText: t('common.confirm'),
        cancelText: t('common.cancel'),
        type: newStatus === 'cancelled' ? 'danger' : 'success',
    });

    if (confirmed) {
        router.put(route('work-orders.update', props.workOrder.id), {
            status: newStatus,
        }, {
            onSuccess: () => {
                success(t('common.saved_success'));
            },
            onError: (err) => {
                const msg = err.status || Object.values(err)[0] || t('common.error');
                errorToast(msg);
            }
        });
    }
}
</script>
