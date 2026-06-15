<template>
    <BaseModal :show="show" @close="$emit('close')" size="lg" :overflow-visible="true" scroll-entire>
        <template #title>
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-indigo-500 to-purple-500 flex items-center justify-center">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                    </svg>
                </div>
                {{ item ? getName(item.service) : $t('work_orders.show.add_service') }}
            </div>
        </template>

        <!-- Tabs -->
        <div class="mb-6 p-1.5 bg-gray-100/80 dark:bg-gray-800/80 rounded-2xl flex gap-1.5 overflow-x-auto no-scrollbar">
            <button
                v-for="tab in tabs"
                :key="tab.id"
                @click="activeTab = tab.id"
                :class="[
                    'flex-1 py-2.5 px-4 text-sm font-bold rounded-xl transition-all duration-300 whitespace-nowrap flex items-center justify-center gap-2',
                    activeTab === tab.id
                        ? 'bg-white dark:bg-gray-700 text-indigo-600 dark:text-indigo-400 shadow-sm'
                        : 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300 hover:bg-gray-200/50 dark:hover:bg-gray-700/50'
                ]"
            >
                {{ tab.name }}
                <span v-if="tab.id === 'parts' && (linkedParts.length > 0 || pendingParts.length > 0)" class="ms-2 px-2 py-0.5 text-xs rounded-full bg-gray-100 dark:bg-gray-800">
                    {{ linkedParts.length + pendingParts.length }}
                </span>
                <span v-if="tab.id === 'technicians' && (localTechnicians.length > 0 || pendingTechnicians.length > 0)" class="ms-2 px-2 py-0.5 text-xs rounded-full bg-gray-100 dark:bg-gray-800">
                    {{ localTechnicians.length + pendingTechnicians.length }}
                </span>
                <span v-if="tab.id === 'notes' && (localNotes.length > 0 || pendingNotes.length > 0)" class="ms-2 px-2 py-0.5 text-xs rounded-full bg-gray-100 dark:bg-gray-800">
                    {{ localNotes.length + pendingNotes.length }}
                </span>
            </button>
        </div>

        <!-- Tab Content -->
        <div class="min-h-[300px]">
            <!-- Service Tab -->
            <div v-show="activeTab === 'service'" class="space-y-4">
                <form @submit.prevent="submitForm" class="space-y-4">
                    <!-- Service Select (Read-only for edit, editable for add) and Description -->
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                {{ $t('quotes.service_modal.service') }}
                            </label>
                            <SearchableSelect
                                v-if="!item && !isReadOnly"
                                v-model="form.service_id"
                                :options="serviceOptions"
                                option-label="label"
                                option-value="value"
                                :placeholder="$t('common.choose')"
                                class="w-full"
                            />
                            <div 
                                v-else 
                                class="px-4 py-2.5 bg-gray-100 dark:bg-gray-900 rounded-xl text-gray-900 dark:text-white border border-gray-200 dark:border-gray-800"
                            >
                                {{ getName(item?.service) }}
                            </div>
                        </div>

                        <!-- Description/Title -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                {{ $t('quotes.service_modal.description') }}
                            </label>
                            <textarea
                                v-model="form.title"
                                rows="2"
                                :disabled="isDescriptionDisabled || isReadOnly"
                                class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-700 rounded-xl bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 resize-none text-sm disabled:bg-gray-100 disabled:text-gray-500 disabled:cursor-not-allowed dark:disabled:bg-gray-900"
                                :placeholder="$t('quotes.service_modal.description_placeholder')"
                            ></textarea>
                            <p v-if="form.errors.title" class="mt-1 text-xs text-red-500">{{ form.errors.title }}</p>
                        </div>
                    </div>

                    <!-- Duration & Warranty Row -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Service Duration -->
                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    {{ $t('work_orders.service_modal.duration_value') }}
                                </label>
                                <input type="number" v-model="form.duration_value" :disabled="isDurationDisabled || isReadOnly" class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-700 rounded-xl bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 text-sm disabled:bg-gray-100 disabled:text-gray-500 disabled:cursor-not-allowed dark:disabled:bg-gray-900" />
                                <p v-if="form.errors.duration_value" class="mt-1 text-xs text-red-500">{{ form.errors.duration_value }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    {{ $t('work_orders.service_modal.duration_unit') }}
                                </label>
                                <SearchableSelect
                                    v-model="form.duration_unit"
                                    :options="durationUnitOptions"
                                    option-label="label"
                                    option-value="value"
                                    :disabled="isDurationDisabled || isReadOnly"
                                    class="w-full"
                                />
                            </div>
                        </div>

                        <!-- Service Warranty -->
                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    {{ $t('work_orders.service_modal.warranty_value') }}
                                </label>
                                <input type="number" v-model="form.warranty_value_snapshot" :disabled="isWarrantyDisabled || isReadOnly" class="w-full px-4 py-2.5 border border-gray-200 dark:border-gray-700 rounded-xl bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 text-sm disabled:bg-gray-100 disabled:text-gray-500 disabled:cursor-not-allowed dark:disabled:bg-gray-900" />
                                <p v-if="form.errors.warranty_value_snapshot" class="mt-1 text-xs text-red-500">{{ form.errors.warranty_value_snapshot }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    {{ $t('work_orders.service_modal.warranty_unit') }}
                                </label>
                                <SearchableSelect
                                    v-model="form.warranty_unit_snapshot"
                                    :options="warrantyUnitOptions"
                                    option-label="label"
                                    option-value="value"
                                    :disabled="isWarrantyDisabled || isReadOnly"
                                    class="w-full"
                                />
                            </div>
                        </div>
                    </div>

                    <!-- Pricing & Timeline Section (Two Columns) -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 pt-2">
                        <!-- Left Column: Timeline -->
                        <div class="space-y-4">
                            <h4 class="text-sm font-bold text-gray-700 dark:text-gray-300 border-b border-gray-200 dark:border-gray-700 pb-2 flex items-center gap-2">
                                <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                {{ $t('work_orders.service_modal.timeline') }}
                            </h4>

                            <!-- Start Date -->
                            <div>
                                <label class="block text-xs font-semibold text-gray-600 dark:text-gray-400 mb-1">
                                    {{ $t('work_orders.service_modal.started_at') }}
                                </label>
                                <CustomDatePicker 
                                    v-model="form.started_at" 
                                    :disabled="isReadOnly" 
                                    :min-date="workOrder?.entry_date"
                                />
                                <p v-if="form.errors.started_at" class="mt-1 text-xs text-red-500">{{ form.errors.started_at }}</p>
                            </div>

                            <!-- Due Date -->
                            <div>
                                <label class="block text-xs font-semibold text-gray-600 dark:text-gray-400 mb-1">
                                    {{ $t('work_orders.service_modal.due_date') }}
                                </label>
                                <CustomDatePicker 
                                    v-model="form.due_date" 
                                    :disabled="isReadOnly" 
                                    :min-date="form.started_at || workOrder?.entry_date"
                                />
                                <p v-if="form.errors.due_date" class="mt-1 text-xs text-red-500">{{ form.errors.due_date }}</p>
                            </div>

                            <!-- Expected closing date of maintenance card -->
                            <div>
                                <label class="block text-xs font-semibold text-gray-600 dark:text-gray-400 mb-1">
                                    {{ $t('work_orders.service_modal.work_order_expected_end_date') }}
                                </label>
                                <div class="px-4 py-2.5 bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl text-sm text-gray-700 dark:text-gray-300 font-mono">
                                    {{ displayedExpectedEndDate ? formatDate(displayedExpectedEndDate) : '—' }}
                                </div>
                            </div>
                        </div>

                        <!-- Right Column: Pricing -->
                        <div class="space-y-4">
                            <h4 class="text-sm font-bold text-gray-700 dark:text-gray-300 border-b border-gray-200 dark:border-gray-700 pb-2 flex items-center gap-2">
                                <svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                {{ $t('work_orders.service_modal.pricing') }}
                            </h4>

                            <!-- Price -->
                            <div>
                                <label class="block text-xs font-semibold text-gray-600 dark:text-gray-400 mb-1">
                                    {{ $t('work_orders.item.price') }}
                                </label>
                                <div class="relative">
                                    <input type="text" inputmode="decimal" v-model="form.unit_price" dir="ltr"
                                        @input="form.unit_price = toEnglish($event.target.value).replace(/[^0-9.]/g, '').replace(/(\..*?)\..*/g, '$1')"
                                        :disabled="isPriceLocked || isReadOnly" :class="[
                                            'w-full py-2.5 pl-4 pr-16 border rounded-xl font-mono text-right text-sm focus:ring-2 focus:border-indigo-500',
                                            isPriceLocked || isReadOnly
                                                ? 'bg-gray-100 dark:bg-gray-900 text-gray-500 dark:text-gray-400 cursor-not-allowed border-gray-200 dark:border-gray-700'
                                                : isPriceBelowMinimum
                                                    ? 'bg-white dark:bg-gray-800 text-gray-900 dark:text-white border-red-300 dark:border-red-700 focus:ring-red-500'
                                                    : 'bg-white dark:bg-gray-800 text-gray-900 dark:text-white border-gray-200 dark:border-gray-700 focus:ring-indigo-500'
                                        ]" required />
                                    <div class="absolute inset-y-0 right-0 pr-4 flex items-center pointer-events-none">
                                        <span class="text-xs text-gray-500">{{ $t('common.currency') }}</span>
                                    </div>
                                </div>
                                <p v-if="isPriceBelowMinimum" class="mt-1 text-[11px] text-red-600 dark:text-red-400 flex items-center gap-1">
                                    {{ $t('quotes.min_price_warning', { min: formatCurrency(selectedServiceMinPrice) }) }}
                                </p>
                            </div>

                            <!-- Discount Method -->
                            <div class="bg-gray-50 dark:bg-gray-900/50 rounded-xl p-3 border border-gray-200 dark:border-gray-700 space-y-2">
                                <div class="flex items-center justify-between">
                                    <label class="text-xs font-semibold text-gray-600 dark:text-gray-400">
                                        {{ $t('quotes.service_modal.discount_method') }}
                                    </label>
                                    <div class="flex items-center gap-1">
                                        <button type="button" :disabled="isReadOnly" @click="form.discount_type = 'none'" :class="['px-2.5 py-1 text-xs font-bold rounded-lg border transition-all', form.discount_type === 'none' ? 'bg-indigo-600 border-indigo-600 text-white shadow-sm' : 'bg-white dark:bg-gray-800 border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700', isReadOnly ? 'opacity-70 cursor-not-allowed' : '']">
                                            {{ $t('quotes.service_modal.no_discount') }}
                                        </button>
                                        <button type="button" :disabled="isReadOnly" @click="form.discount_type = 'fixed'" :class="['px-2.5 py-1 text-xs font-bold rounded-lg border transition-all', form.discount_type === 'fixed' ? 'bg-indigo-600 border-indigo-600 text-white shadow-sm' : 'bg-white dark:bg-gray-800 border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700', isReadOnly ? 'opacity-70 cursor-not-allowed' : '']">
                                            {{ $t('quotes.service_modal.fixed') }}
                                        </button>
                                        <button type="button" :disabled="isReadOnly" @click="form.discount_type = 'percentage'" :class="['px-2.5 py-1 text-xs font-bold rounded-lg border transition-all', form.discount_type === 'percentage' ? 'bg-indigo-600 border-indigo-600 text-white shadow-sm' : 'bg-white dark:bg-gray-800 border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700', isReadOnly ? 'opacity-70 cursor-not-allowed' : '']">
                                            %
                                        </button>
                                    </div>
                                </div>

                                <!-- Discount Value Input -->
                                <div v-if="form.discount_type !== 'none'">
                                    <input type="text" inputmode="decimal" v-model="form.discount_value" dir="ltr"
                                        :disabled="isReadOnly"
                                        @input="form.discount_value = toEnglish($event.target.value).replace(/[^0-9.]/g, '').replace(/(\..*?)\..*/g, '$1')"
                                        class="w-full px-3 py-2 border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 rounded-xl font-mono text-end text-sm focus:ring-2 focus:ring-indigo-500 disabled:bg-gray-100 disabled:text-gray-500 disabled:cursor-not-allowed dark:disabled:bg-gray-900" />
                                </div>
                            </div>

                            <!-- Calculated Cost (Final Price) -->
                            <div class="p-3 bg-gradient-to-r from-indigo-50 to-purple-50 dark:from-indigo-900/20 dark:to-purple-900/20 border border-indigo-200 dark:border-indigo-800 rounded-xl flex items-center justify-between">
                                <span class="text-xs font-bold text-gray-700 dark:text-gray-300">
                                    {{ $t('quotes.service_modal.total_cost') }}
                                </span>
                                <div class="flex items-baseline gap-1 font-mono">
                                    <span class="text-xl font-black text-indigo-600 dark:text-indigo-400">
                                        {{ formatCurrency(calculatedTotal) }}
                                    </span>
                                    <span class="text-[9px] font-bold text-gray-500 dark:text-gray-400 uppercase">
                                        {{ $t('common.currency') }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>


                </form>
            </div>

            <!-- Parts Tab -->
            <div v-show="activeTab === 'parts'" class="space-y-4">
                <PartsDisplay 
                    :parts="allParts" 
                    :read-only="isReadOnly" 
                    :show-vat="workOrder.is_taxed" 
                    :show-service="false"
                    :compact-grid="true" 
                    :pending-check="part => !part.id" 
                    storage-key="workorder_service_modal_parts_view"
                    :empty-message="$t('work_orders.item.no_parts')" 
                    :add-button-text="$t('work_orders.item.add_part')"
                    @edit="handlePartEdit" 
                    @delete="handlePartDelete" 
                    @add="openPartModal()" 
                />

                <!-- Parts Total -->
                <div v-if="allParts.length > 0"
                    class="flex justify-between items-center p-3 bg-green-50 dark:bg-green-900/20 rounded-xl border border-green-200 dark:border-green-800">
                    <span class="text-sm font-medium text-green-700 dark:text-green-300">
                        {{ $t('work_orders.item.parts_cost') }}
                    </span>
                    <span class="font-bold text-green-600 dark:text-green-400 font-mono" dir="ltr">
                        {{ formatCurrency(partsTotalCost) }} {{ $t('common.currency') }}
                    </span>
                </div>
            </div>

            <!-- Part Modal -->
            <WorkOrderPartModal 
                :show="showLinkedPartModal" 
                :workOrder="workOrder" 
                :part="editingLinkedPart" 
                :workOrderItemId="item?.id"
                :units="inventoryUnits" 
                :warehouses="warehouses"
                :show-service-select="false" 
                :show-toggles="true" 
                :pending-mode="!item"
                @close="closePartModal" 
                @saved="onPartSaved" 
            />

            <!-- Issue More Modal -->
            <WorkOrderIssueMoreModal 
                :show="showIssueMoreModal" 
                :workOrder="workOrder" 
                :part="editingIssueMorePart" 
                @close="closeIssueMoreModal" 
                @saved="onPartSaved" 
            />

            <!-- Return Modal -->
            <WorkOrderReturnModal 
                :show="showReturnModal" 
                :workOrder="workOrder" 
                :part="editingReturnPart" 
                @close="closeReturnModal" 
                @saved="onPartSaved" 
            />

            <!-- Technicians Tab -->
            <div v-show="activeTab === 'technicians'" class="space-y-4">
                <div class="bg-gray-50 dark:bg-gray-900/50 rounded-xl p-4 border border-gray-200 dark:border-gray-700">
                    <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-4">{{ $t('work_orders.item.assign_technician') }}</h4>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 max-h-[200px] overflow-y-auto pr-2">
                        <div 
                            v-for="tech in props.technicians" 
                            :key="tech.id" 
                            @click="toggleTechnician(tech)"
                            class="flex items-center justify-between p-3 bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 hover:border-indigo-500 dark:hover:border-indigo-500 transition-all cursor-pointer select-none"
                        >
                            <div class="flex items-center gap-3">
                                <input 
                                    type="checkbox" 
                                    :checked="isTechnicianSelected(tech.id)"
                                    :disabled="isReadOnly"
                                    class="w-4 h-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 disabled:opacity-50 cursor-pointer"
                                    @click.stop="toggleTechnician(tech)"
                                />
                                <span class="text-sm font-medium text-gray-900 dark:text-white">{{ getTechnicianName(tech) }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Checked Technicians List with Share Inputs -->
                <div v-if="selectedTechniciansList.length > 0" class="space-y-3">
                    <h5 class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                        {{ $t('work_orders.item.assigned_technicians') }}
                    </h5>
                    
                    <div class="space-y-2">
                        <div 
                            v-for="tech in selectedTechniciansList" 
                            :key="tech.user_id" 
                            class="flex items-center justify-between p-3.5 bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm"
                        >
                            <span class="text-sm font-semibold text-gray-900 dark:text-white">{{ getTechnicianName(tech) }}</span>
                            
                            <!-- Share input if count > 1 -->
                            <div v-if="selectedTechniciansList.length > 1" class="flex items-center gap-2">
                                <span class="text-xs text-gray-500 dark:text-gray-400">{{ $t('work_orders.item.technician_share') }}:</span>
                                <div class="relative w-24">
                                    <input 
                                        type="number" 
                                        v-model.number="tech.share" 
                                        :disabled="isReadOnly"
                                        min="0"
                                        max="100"
                                        step="0.01"
                                        class="w-full py-1.5 pl-3 pr-8 border border-gray-200 dark:border-gray-700 rounded-lg font-mono text-right text-xs focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-white"
                                    />
                                    <div class="absolute inset-y-0 right-0 pr-2.5 flex items-center pointer-events-none">
                                        <span class="text-[10px] text-gray-500">%</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Sum warning if count > 1 and sum !== 100 -->
                    <div v-if="selectedTechniciansList.length > 1 && Math.abs(techniciansShareSum - 100) > 0.01" class="p-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-xl flex items-center justify-between">
                        <span class="text-xs text-red-600 dark:text-red-400 font-medium">
                            {{ $t('work_orders.item.share_sum_warning') }}
                        </span>
                        <span class="text-xs font-mono font-bold text-red-600 dark:text-red-400">
                            {{ parseFloat(techniciansShareSum.toFixed(2)) }}% / 100%
                        </span>
                    </div>
                </div>
            </div>

            <!-- Notes Tab -->
            <div v-show="activeTab === 'notes'" class="space-y-4">
                <div class="flex justify-between items-center mb-2">
                    <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 flex items-center gap-2">
                        <div class="w-1.5 h-4 bg-indigo-500 rounded-full"></div>
                        {{ $t('work_orders.item.tab_notes') }}
                    </h4>
                    <button v-if="!isReadOnly" type="button" @click="showAddNoteModal = true"
                        class="px-3 py-1.5 text-xs bg-gradient-to-r from-indigo-500 to-purple-500 text-white rounded-lg hover:from-indigo-600 hover:to-purple-600 transition-all flex items-center gap-1 shadow-sm">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        {{ $t('work_orders.item.add_note') }}
                    </button>
                </div>

                <!-- Notes List -->
                <div class="space-y-2">
                    <div
                        v-for="note in (item ? localNotes : pendingNotes)"
                        :key="note.id || note.created_at"
                        class="p-3 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700"
                    >
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <div class="text-sm text-gray-900 dark:text-white whitespace-pre-wrap">{{ note.content }}</div>
                                <div class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                                    {{ note.user?.name }} {{ note.created_at ? '• ' + formatDate(note.created_at) : '' }}
                                </div>
                            </div>
                            <button
                                v-if="!isReadOnly"
                                type="button"
                                @click="removeNote(note)"
                                class="p-2 text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <template #footer>
            <button
                type="button"
                @click="$emit('close')"
                class="px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors"
            >
                {{ $t('common.close') }}
            </button>
            <button
                v-if="!isReadOnly"
                type="button"
                @click="submitForm"
                :disabled="saving || isPriceBelowMinimum || isTechniciansShareInvalid"
                class="px-6 py-2 bg-gradient-to-r from-indigo-500 to-purple-500 text-white rounded-lg hover:from-indigo-600 hover:to-purple-600 disabled:opacity-50 transition-all"
            >
                {{ saving ? $t('common.loading') : $t('common.save') }}
            </button>
        </template>

        <!-- Add Note Modal -->
        <BaseModal :show="showAddNoteModal" @close="showAddNoteModal = false" size="md" z-index-class="z-[60]">
            <template #title>
                {{ $t('work_orders.item.add_note') }}
            </template>

            <form @submit.prevent="addNote" class="space-y-4 text-right">
                <div class="space-y-1">
                    <!-- Note Content -->
                    <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">
                        {{ $t('work_orders.item.tab_notes') }}
                    </label>
                    <textarea v-model="noteForm.content" required rows="5"
                        :placeholder="$t('work_orders.item.note_placeholder')"
                        class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-white px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 focus:border-teal-500 resize-none"></textarea>
                </div>
            </form>

            <template #footer>
                <button type="button" @click="showAddNoteModal = false"
                    class="px-5 py-2 text-sm font-semibold text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/50 rounded-xl transition-all">
                    {{ $t('common.cancel') }}
                </button>
                <button type="submit" @click="addNote" :disabled="notesLoading || !noteForm.content.trim()"
                    class="px-5 py-2 text-sm font-semibold text-white bg-indigo-500 hover:bg-indigo-600 disabled:bg-gray-300 dark:disabled:bg-gray-700 disabled:text-gray-500 disabled:cursor-not-allowed rounded-xl transition-all shadow-sm flex items-center gap-2">
                    <svg v-if="notesLoading" class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span>{{ $t('common.save') }}</span>
                </button>
            </template>
        </BaseModal>
    </BaseModal>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { useLocalized } from '@/Composables/useLocalized';
import { useNumberFormat } from '@/Composables/useNumberFormat';
import BaseModal from '@/Components/BaseModal.vue';
import PartsDisplay from '@/Components/Common/PartsDisplay.vue';
import WorkOrderPartModal from '@/Components/WorkOrders/WorkOrderPartModal.vue';
import WorkOrderIssueMoreModal from '@/Components/WorkOrders/WorkOrderIssueMoreModal.vue';
import WorkOrderReturnModal from '@/Components/WorkOrders/WorkOrderReturnModal.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import CustomDatePicker from '@/Components/CustomDatePicker.vue';
import axios from 'axios';
import { useToast } from '@/Composables/useToast';

const { success: toastSuccess } = useToast();

const props = defineProps({
    show: Boolean,
    workOrder: Object,
    item: Object,
    departmentId: [Number, String],
    services: Array,
    technicians: { type: Array, default: () => [] },
    inventoryUnits: { type: Array, default: () => [] },
    warehouses: { type: Array, default: () => [] },
    readOnly: { type: Boolean, default: false },
    initialTab: { type: String, default: 'service' },
});

const emit = defineEmits(['close', 'saved']);
const { t, locale } = useI18n();
const { getName, getDescription } = useLocalized();

function getTechnicianName(tech) {
    const userId = tech.user_id || tech.id;
    const found = props.technicians?.find(t => t.id === userId);
    if (found) {
        return locale.value === 'ar' 
            ? (found.name_ar || found.name_en || found.name) 
            : (found.name_en || found.name_ar || found.name);
    }
    const assigned = props.item?.technicians?.find(t => t.id === userId);
    if (assigned) {
        return locale.value === 'ar' 
            ? (assigned.employee?.name_ar || assigned.employee?.name_en || assigned.name) 
            : (assigned.employee?.name_en || assigned.employee?.name_ar || assigned.name);
    }
    return tech.name;
}

function getPreferredTitle(service) {
    if (!service) return '';
    const name = getName(service) || '';
    const normalized = name.trim().toLowerCase();
    if (normalized === 'أخرى' || normalized === 'other') {
        return '';
    }
    return getDescription(service) || name;
}
const { formatCurrency, toEnglish } = useNumberFormat();

// State
const activeTab = ref('service');
const isReadOnly = computed(() => {
    if (props.readOnly) return true;
    if (props.item && !['pending', 'in_progress'].includes(props.item.status)) {
        return true;
    }
    return false;
});
const saving = ref(false);
const techniciansLoading = ref(false);
const notesLoading = ref(false);
const isEdit = computed(() => !!props.item);
const showAddNoteModal = ref(false);

// Local data
const linkedParts = ref([]);
const pendingParts = ref([]);
const localTechnicians = ref([]);
const pendingTechnicians = ref([]);
const localNotes = ref([]);
const pendingNotes = ref([]);

// Modals
const showLinkedPartModal = ref(false);
const editingLinkedPart = ref(null);
const showIssueMoreModal = ref(false);
const editingIssueMorePart = ref(null);
const showReturnModal = ref(false);
const editingReturnPart = ref(null);

// Helper function to format date for input (YYYY-MM-DD)
function formatDateForInput(dateStr) {
    if (!dateStr) return '';
    const date = new Date(dateStr);
    if (isNaN(date.getTime())) return '';
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
}

const durationUnitOptions = computed(() => [
    { value: 'minutes', label: t('work_orders.service_modal.minutes') || 'دقائق' },
    { value: 'hours', label: t('work_orders.service_modal.hours') || 'ساعات' },
    { value: 'days', label: t('work_orders.service_modal.days') || 'أيام' },
]);

const warrantyUnitOptions = computed(() => [
    { value: 'days', label: t('work_orders.service_modal.days') || 'أيام' },
    { value: 'weeks', label: t('work_orders.service_modal.weeks') || 'أسابيع' },
    { value: 'months', label: t('work_orders.service_modal.months') || 'أشهر' },
    { value: 'years', label: t('work_orders.service_modal.years') || 'سنوات' },
]);

// Form
const form = useForm({
    service_id: props.item ? (props.item.service_id || 'other') : '',
    title: props.item ? (props.item.title || getPreferredTitle(props.item.service)) : '',
    qty: props.item?.qty || 1,
    unit_price: props.item ? (props.item.unit_price !== undefined && props.item.unit_price !== null ? props.item.unit_price : (props.item.service?.base_price || 0)) : 0,
    discount_type: props.item ? (props.item.discount_type || props.item.service?.default_discount_type || 'none') : 'none',
    discount_value: props.item ? (props.item.discount_value !== undefined && props.item.discount_value !== null ? props.item.discount_value : (props.item.service?.default_discount_value || 0)) : 0,
    status: props.item?.status || 'pending',
    duration_value: props.item ? (props.item.duration_value !== undefined && props.item.duration_value !== null && props.item.duration_value !== '' ? props.item.duration_value : (props.item.service?.duration_value || '')) : '',
    duration_unit: props.item ? (props.item.duration_unit || props.item.service?.duration_unit || 'minutes') : 'minutes',
    warranty_value_snapshot: props.item ? (props.item.warranty_value_snapshot !== undefined && props.item.warranty_value_snapshot !== null && props.item.warranty_value_snapshot !== '' ? props.item.warranty_value_snapshot : (props.item.service?.warranty_value || '')) : '',
    warranty_unit_snapshot: props.item ? (props.item.warranty_unit_snapshot || props.item.service?.warranty_unit || 'days') : 'days',
    started_at: props.item?.started_at ? formatDateForInput(props.item.started_at) : formatDateForInput(new Date()),
    completed_at: props.item?.completed_at ? formatDateForInput(props.item.completed_at) : '',
    due_date: props.item?.due_date ? formatDateForInput(props.item.due_date) : formatDateForInput(new Date()),
});

const noteForm = ref({ content: '' });

// Technicians multi-select state and helpers
const selectedTechniciansList = computed(() => {
    return props.item ? localTechnicians.value : pendingTechnicians.value;
});

const techniciansShareSum = computed(() => {
    return selectedTechniciansList.value.reduce((sum, t) => sum + (parseFloat(t.share) || 0), 0);
});

const isTechniciansShareInvalid = computed(() => {
    const list = selectedTechniciansList.value;
    if (list.length <= 1) return false;
    return Math.abs(techniciansShareSum.value - 100) > 0.01;
});

function isTechnicianSelected(techId) {
    return selectedTechniciansList.value.some(t => t.user_id === techId);
}

function toggleTechnician(tech) {
    if (isReadOnly.value) return;
    
    const list = props.item ? localTechnicians.value : pendingTechnicians.value;
    const index = list.findIndex(t => t.user_id === tech.id);
    
    if (index !== -1) {
        list.splice(index, 1);
    } else {
        list.push({
            user_id: tech.id,
            name: locale.value === 'ar' 
                ? (tech.name_ar || tech.name_en || tech.name) 
                : (tech.name_en || tech.name_ar || tech.name),
            share: 0
        });
    }
    
    recalculateShares(list);
}

function recalculateShares(list) {
    const count = list.length;
    if (count === 0) return;
    
    if (count === 1) {
        list[0].share = 100;
        return;
    }
    
    const baseShare = Math.floor((100 / count) * 100) / 100;
    let sum = 0;
    list.forEach((t, i) => {
        if (i === count - 1) {
            t.share = parseFloat((100 - sum).toFixed(2));
        } else {
            t.share = baseShare;
            sum += baseShare;
        }
    });
}

// Computed
const allParts = computed(() => [...linkedParts.value, ...pendingParts.value]);
const partsTotalCost = computed(() => allParts.value.reduce((sum, p) => sum + parseFloat(p.total || calculatePartTotal(p)), 0));

const tabs = computed(() => [
    { id: 'service', name: t('work_orders.item.tab_service') },
    { id: 'parts', name: t('work_orders.item.tab_parts') },
    { id: 'technicians', name: t('work_orders.item.tab_technicians') },
    { id: 'notes', name: t('work_orders.item.tab_notes') },
]);

const statuses = computed(() => [
    { value: 'pending', label: t('work_orders.item.status_pending'), icon: '⏳', activeClass: 'bg-gray-500 border-gray-600 text-white' },
    { value: 'in_progress', label: t('work_orders.item.status_in_progress'), icon: '🔧', activeClass: 'bg-blue-500 border-blue-600 text-white' },
    { value: 'completed', label: t('work_orders.item.status_completed'), icon: '✅', activeClass: 'bg-green-500 border-green-600 text-white' },
    { value: 'on_hold', label: t('work_orders.item.status_on_hold'), icon: '⏸️', activeClass: 'bg-amber-500 border-amber-600 text-white' },
    { value: 'cancelled', label: t('work_orders.item.status_cancelled'), icon: '❌', activeClass: 'bg-red-500 border-red-600 text-white' },
]);

const calculatedTotal = computed(() => {
    const price = effectivePrice.value;
    const qty = 1; // Always 1
    let discount = 0;

    if (form.discount_type === 'fixed') {
        discount = parseFloat(toEnglish(form.discount_value)) || 0;
    } else if (form.discount_type === 'percentage') {
        discount = (price * (parseFloat(toEnglish(form.discount_value)) || 0)) / 100;
    }

    return Math.max(0, (price - discount) * qty);
});

// Get the currently selected service
const selectedService = computed(() => {
    // In edit mode, use the line's service directly
    if (props.item?.service) {
        return props.item.service;
    }
    // In add mode, find from services list
    if (!form.service_id || form.service_id === 'other') return null;
    const serviceId = parseInt(form.service_id);
    return props.services?.find(s => s.id === serviceId) || null;
});

// Check if price is locked (allow_price_override = false)
const isPriceLocked = computed(() => {
    if (!selectedService.value) return false;
    return selectedService.value.allow_price_override === false;
});

const isDescriptionDisabled = computed(() => {
    if (isReadOnly.value) return true;
    if (!selectedService.value) return false;
    return !!(getDescription(selectedService.value) || getName(selectedService.value));
});

const isDurationDisabled = computed(() => {
    if (isReadOnly.value) return true;
    if (!selectedService.value) return false;
    const val = selectedService.value.duration_value;
    return val !== undefined && val !== null && val !== '';
});

const isWarrantyDisabled = computed(() => {
    if (isReadOnly.value) return true;
    if (!selectedService.value) return false;
    const val = selectedService.value.warranty_value;
    return val !== undefined && val !== null && val !== '';
});

function getMidnightTimestamp(dateStr) {
    if (!dateStr) return null;
    const date = new Date(dateStr);
    if (isNaN(date.getTime())) return null;
    date.setHours(0, 0, 0, 0);
    return date.getTime();
}

function calculateDueDate(startedAt, durationValue, durationUnit) {
    if (!startedAt) return '';
    
    const startDate = new Date(startedAt);
    if (isNaN(startDate.getTime())) return '';
    
    const val = parseInt(durationValue);
    if (isNaN(val) || val <= 0) {
        return startedAt;
    }
    
    const unit = durationUnit || 'minutes';
    
    if (unit === 'days') {
        startDate.setDate(startDate.getDate() + val);
    } else if (unit === 'weeks') {
        startDate.setDate(startDate.getDate() + (val * 7));
    } else if (unit === 'months') {
        startDate.setMonth(startDate.getMonth() + val);
    } else if (unit === 'years') {
        startDate.setFullYear(startDate.getFullYear() + val);
    }
    
    return formatDateForInput(startDate);
}

// Watch for start date or duration changes to auto-calculate due date
watch(() => [form.started_at, form.duration_value, form.duration_unit], ([newStart, newVal, newUnit]) => {
    if (!props.item) {
        form.due_date = calculateDueDate(newStart, newVal, newUnit);
    }
});

const displayedExpectedEndDate = computed(() => {
    const cardEndStr = props.workOrder.expected_end_date;
    if (!cardEndStr) {
        const dates = [form.started_at, form.due_date].filter(Boolean);
        if (dates.length === 0) return '';
        dates.sort();
        return dates[dates.length - 1];
    }
    
    const cardEndTime = getMidnightTimestamp(cardEndStr);
    let maxTime = cardEndTime;
    
    if (form.started_at) {
        const startTime = getMidnightTimestamp(form.started_at);
        if (startTime && startTime > maxTime) {
            maxTime = startTime;
        }
    }
    
    if (form.due_date) {
        const dueTime = getMidnightTimestamp(form.due_date);
        if (dueTime && dueTime > maxTime) {
            maxTime = dueTime;
        }
    }
    
    if (maxTime === cardEndTime) return cardEndStr;
    return formatDateForInput(new Date(maxTime));
});

// Get min price for selected service
const selectedServiceMinPrice = computed(() => {
    if (!selectedService.value) return 0;
    return parseFloat(selectedService.value.min_price) || 0;
});

// Calculate the effective price (base_price for locked, unit_price for unlocked)
const effectivePrice = computed(() => {
    if (isPriceLocked.value && selectedService.value) {
        return parseFloat(selectedService.value.base_price) || 0;
    }
    return parseFloat(toEnglish(form.unit_price)) || 0;
});

// Max allowed fixed discount (price - min_price)
const maxAllowedFixedDiscount = computed(() => {
    const minPrice = selectedServiceMinPrice.value;
    if (minPrice <= 0) return effectivePrice.value; // No limit
    return Math.max(0, effectivePrice.value - minPrice);
});

// Max allowed percentage discount ((price - min_price) / price * 100)
const maxAllowedPercentageDiscount = computed(() => {
    const minPrice = selectedServiceMinPrice.value;
    const price = effectivePrice.value;
    if (minPrice <= 0 || price <= 0) return 100; // No limit
    return Math.max(0, ((price - minPrice) / price) * 100);
});

// Check if FINAL price (after discount) is below minimum
const isPriceBelowMinimum = computed(() => {
    if (!selectedService.value) return false;
    const minPrice = selectedServiceMinPrice.value;
    if (minPrice <= 0) return false;

    // Calculate the final total using effective price
    const price = effectivePrice.value;
    let discount = 0;
    if (form.discount_type === 'fixed') {
        discount = parseFloat(toEnglish(form.discount_value)) || 0;
    } else if (form.discount_type === 'percentage') {
        discount = (price * (parseFloat(toEnglish(form.discount_value)) || 0)) / 100;
    }
    const finalPrice = Math.max(0, price - discount);

    return finalPrice < minPrice;
});

const serviceOptions = computed(() => {
    let filteredServices = props.services || [];
    if (props.departmentId === 'packages') {
        filteredServices = filteredServices.filter(s => s.type === 'package');
    } else if (props.departmentId) {
        filteredServices = filteredServices.filter(s => s.department_id === props.departmentId && s.type !== 'package');
    } else {
        filteredServices = filteredServices.filter(s => s.type !== 'package');
    }
    const list = filteredServices.map(s => ({
        value: s.id,
        label: getName(s)
    }));
    list.push({
        value: 'other',
        label: t('common.other') || 'أخرى'
    });
    return list;
});

// Methods
function formatDate(date) {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('ar-SA-u-nu-latn');
}

function calculatePartTotal(part) {
    const qty = parseFloat(part.qty) || 0;
    const price = parseFloat(part.unit_price) || 0;
    const discount = parseFloat(part.discount) || 0;
    return Math.max(0, (qty * price) - discount);
}

function openPartModal(part = null) {
    editingLinkedPart.value = part;
    showLinkedPartModal.value = true;
}

function closePartModal() {
    showLinkedPartModal.value = false;
    setTimeout(() => { editingLinkedPart.value = null; }, 300);
}

function openIssueMoreModal(part = null) {
    editingIssueMorePart.value = part;
    showIssueMoreModal.value = true;
}

function closeIssueMoreModal() {
    showIssueMoreModal.value = false;
    setTimeout(() => { editingIssueMorePart.value = null; }, 300);
}

function openReturnModal(part = null) {
    editingReturnPart.value = part;
    showReturnModal.value = true;
}

function closeReturnModal() {
    showReturnModal.value = false;
    setTimeout(() => { editingReturnPart.value = null; }, 300);
}

function onPartSaved(savedPart, options = {}) {
    if (savedPart.isPending || !props.item) {
        if (savedPart.tempId) {
            const index = pendingParts.value.findIndex(p => p.tempId === savedPart.tempId);
            if (index !== -1) {
                pendingParts.value[index] = { ...savedPart, total: calculatePartTotal(savedPart) };
            } else {
                pendingParts.value.push({ ...savedPart, total: calculatePartTotal(savedPart) });
            }
        } else {
            const newTempId = Date.now();
            pendingParts.value.push({ ...savedPart, total: calculatePartTotal(savedPart), tempId: newTempId });
        }
    } else {
        if ((editingLinkedPart.value && editingLinkedPart.value.id) || (editingIssueMorePart.value && editingIssueMorePart.value.id) || (editingReturnPart.value && editingReturnPart.value.id)) {
            const index = linkedParts.value.findIndex(p => p.id === savedPart.id);
            if (index !== -1) {
                if (savedPart.qty === 0 || savedPart.status === 'cancelled') {
                    linkedParts.value.splice(index, 1);
                } else {
                    linkedParts.value[index] = savedPart;
                }
            }
        } else {
            if (savedPart.qty !== 0 && savedPart.status !== 'cancelled') {
                linkedParts.value.push(savedPart);
            }
        }
        // In edit mode, we should refresh from backend to get fresh data
        router.reload({ only: ['workOrder', 'itemsByDepartment'] });
    }
    
    toastSuccess(t('common.saved_success'));
    
    if (options.close !== false) {
        closePartModal();
        closeIssueMoreModal();
        closeReturnModal();
    }
}

function handlePartEdit(part) {
    if (part.source === 'warehouse' && part.status === 'issued') {
        openIssueMoreModal(part);
    } else {
        openPartModal(part);
    }
}

function handlePartDelete(part) {
    if (!part.id) {
        pendingParts.value = pendingParts.value.filter(p => p.tempId !== part.tempId);
        return;
    }
    if (part.source === 'warehouse' && part.status === 'issued') {
        openReturnModal(part);
    } else {
        if (confirm(t('common.confirm_delete'))) {
            router.delete(route('work-orders.parts.destroy', { workOrderPart: part.id }), {
                onSuccess: () => { 
                    linkedParts.value = linkedParts.value.filter(p => p.id !== part.id);
                    router.reload({ only: ['workOrder', 'itemsByDepartment'] });
                }
            });
        }
    }
}

function changeStatus(newStatus) { form.status = newStatus; }



async function addNote() {
    if (!noteForm.value.content) return;
    if (!props.item) {
        pendingNotes.value.push({ content: noteForm.value.content, created_at: new Date().toISOString() });
        noteForm.value.content = '';
        showAddNoteModal.value = false;
        return;
    }
    notesLoading.value = true;
    try {
        const response = await axios.post(route('work-orders.items.notes.store', { work_order: props.workOrder.id, item: props.item.id }), noteForm.value);
        localNotes.value = response.data.notes || [];
        noteForm.value.content = '';
        showAddNoteModal.value = false;
        router.reload({ only: ['workOrder'] });
    } finally { notesLoading.value = false; }
}

async function removeNote(note) {
    if (!props.item) {
        pendingNotes.value = pendingNotes.value.filter(n => n !== note);
        return;
    }
    notesLoading.value = true;
    try {
        const response = await axios.delete(route('work-orders.items.notes.destroy', { work_order: props.workOrder.id, item: props.item.id, note: note.id }));
        localNotes.value = response.data.notes || [];
        router.reload({ only: ['workOrder'] });
    } finally { notesLoading.value = false; }
}

function submitForm() {
    if (saving.value) return;
    saving.value = true;
    
    // Normalize data before sending
    form.qty = toEnglish(form.qty);
    form.unit_price = toEnglish(form.unit_price);
    form.discount_value = toEnglish(form.discount_value);

    form.clearErrors();

    if (isPriceBelowMinimum.value) {
        saving.value = false;
        return;
    }

    if (form.service_id === 'other' && !form.title?.trim()) {
        form.setError('title', t('validation.required') || 'هذا الحقل مطلوب');
        saving.value = false;
        return;
    }

    // Validate dates against work order active range (only checking before entry date, not after expected end date as it auto-extends)
    const cardEntryTime = getMidnightTimestamp(props.workOrder.entry_date);

    if (form.started_at) {
        const startTime = getMidnightTimestamp(form.started_at);
        if (cardEntryTime && startTime < cardEntryTime) {
            form.setError('started_at', t('validation.before_card_entry_date') || 'تاريخ البداية لا يمكن أن يكون قبل تاريخ دخول كرت الصيانة');
            saving.value = false;
            return;
        }
    }

    if (form.due_date) {
        const dueTime = getMidnightTimestamp(form.due_date);
        if (cardEntryTime && dueTime < cardEntryTime) {
            form.setError('due_date', t('validation.before_card_entry_date') || 'تاريخ الاستحقاق لا يمكن أن يكون قبل تاريخ دخول كرت الصيانة');
            saving.value = false;
            return;
        }
        
        if (form.started_at) {
            const startTime = getMidnightTimestamp(form.started_at);
            if (dueTime < startTime) {
                form.setError('due_date', t('validation.due_date_before_start') || 'تاريخ الاستحقاق لا يمكن أن يكون قبل تاريخ البداية');
                saving.value = false;
                return;
            }
        }
    }

    const formData = {
        ...form.data(),
        service_id: form.service_id === 'other' ? null : form.service_id,
        department_id: props.departmentId,
        pending_parts: pendingParts.value,
        pending_technicians: pendingTechnicians.value,
        pending_notes: pendingNotes.value,
        technicians: localTechnicians.value
    };

    if (props.item) {
        // Update existing item
        router.put(route('work-orders.items.update', { work_order: props.workOrder.id, item: props.item.id }), formData, {
            onSuccess: () => {
                emit('saved');
                emit('close');
            },
            onFinish: () => {
                saving.value = false;
            }
        });
    } else {
        // Add new item
        router.post(route('work-orders.items.store', { work_order: props.workOrder.id }), formData, {
            onSuccess: () => {
                emit('saved');
                emit('close');
            },
            onFinish: () => {
                saving.value = false;
            }
        });
    }
}

// Watch for service selection to auto-fill price and other fields
watch(() => form.service_id, (serviceId) => {
    if (serviceId && !props.item) {
        if (serviceId === 'other') {
            form.unit_price = 0;
            form.title = '';
            form.duration_value = '';
            form.duration_unit = 'minutes';
            form.warranty_value_snapshot = '';
            form.warranty_unit_snapshot = 'days';
            form.discount_type = 'none';
            form.discount_value = 0;
            // When selecting 'other', due_date falls back to started_at
            form.due_date = form.started_at;
            return;
        }
        const service = props.services.find(s => s.id == serviceId);
        if (service) {
            form.unit_price = service.base_price || 0;
            form.title = getPreferredTitle(service);
            form.duration_value = service.duration_value !== undefined && service.duration_value !== null ? service.duration_value : '';
            form.duration_unit = service.duration_unit || 'minutes';
            form.warranty_value_snapshot = service.warranty_value !== undefined && service.warranty_value !== null ? service.warranty_value : '';
            form.warranty_unit_snapshot = service.warranty_unit || 'days';
            form.discount_type = service.default_discount_type || 'none';
            form.discount_value = service.default_discount_value || 0;
            
            // Auto-calculate due date
            form.due_date = calculateDueDate(form.started_at, form.duration_value, form.duration_unit);
        }
    }
});

// Watch for item changes to sync local data
watch(() => props.item, (newItem) => {
    if (newItem) {
        const itemService = newItem.service;
        form.service_id = newItem.service_id || 'other';
        form.title = newItem.title || getPreferredTitle(itemService);
        form.qty = newItem.qty;
        form.unit_price = newItem.unit_price !== undefined && newItem.unit_price !== null ? newItem.unit_price : (itemService ? (itemService.base_price || 0) : 0);
        form.discount_type = newItem.discount_type || itemService?.default_discount_type || 'none';
        form.discount_value = newItem.discount_value !== undefined && newItem.discount_value !== null ? newItem.discount_value : (itemService ? (itemService.default_discount_value || 0) : 0);
        form.status = newItem.status || 'pending';
        form.duration_value = newItem.duration_value !== undefined && newItem.duration_value !== null && newItem.duration_value !== '' ? newItem.duration_value : (itemService ? (itemService.duration_value !== undefined && itemService.duration_value !== null ? itemService.duration_value : '') : '');
        form.duration_unit = newItem.duration_unit || itemService?.duration_unit || 'minutes';
        form.warranty_value_snapshot = newItem.warranty_value_snapshot !== undefined && newItem.warranty_value_snapshot !== null && newItem.warranty_value_snapshot !== '' ? newItem.warranty_value_snapshot : (itemService ? (itemService.warranty_value !== undefined && itemService.warranty_value !== null ? itemService.warranty_value : '') : '');
        form.warranty_unit_snapshot = newItem.warranty_unit_snapshot || itemService?.warranty_unit || 'days';
        form.started_at = newItem.started_at ? formatDateForInput(newItem.started_at) : '';
        form.completed_at = newItem.completed_at ? formatDateForInput(newItem.completed_at) : '';
        form.due_date = newItem.due_date ? formatDateForInput(newItem.due_date) : '';
        
        linkedParts.value = newItem.parts || [];
        localTechnicians.value = (newItem.technicians || []).map(t => ({
            user_id: t.id,
            name: locale.value === 'ar' 
                ? (t.employee?.name_ar || t.employee?.name_en || t.name) 
                : (t.employee?.name_en || t.employee?.name_ar || t.name),
            share: t.pivot?.share !== undefined ? parseFloat(t.pivot.share) : 100.00
        }));
        localNotes.value = newItem.item_notes || [];
    }
}, { deep: true });

// Watch for initialTab changes to sync activeTab
watch(() => props.initialTab, (newTab) => {
    if (newTab) {
        activeTab.value = newTab;
    }
}, { immediate: true });

// Reset form when modal opens
watch(() => props.show, (isOpen) => {
    if (isOpen) {
        activeTab.value = props.initialTab || 'service';
        if (props.item) {
            const itemService = props.item.service;
            form.service_id = props.item.service_id || 'other';
            form.title = props.item.title || getPreferredTitle(itemService);
            form.qty = props.item.qty;
            form.unit_price = props.item.unit_price !== undefined && props.item.unit_price !== null ? props.item.unit_price : (itemService ? (itemService.base_price || 0) : 0);
            form.discount_type = props.item.discount_type || itemService?.default_discount_type || 'none';
            form.discount_value = props.item.discount_value !== undefined && props.item.discount_value !== null ? props.item.discount_value : (itemService ? (itemService.default_discount_value || 0) : 0);
            form.status = props.item.status || 'pending';
            form.duration_value = props.item.duration_value !== undefined && props.item.duration_value !== null && props.item.duration_value !== '' ? props.item.duration_value : (itemService ? (itemService.duration_value !== undefined && itemService.duration_value !== null ? itemService.duration_value : '') : '');
            form.duration_unit = props.item.duration_unit || itemService?.duration_unit || 'minutes';
            form.warranty_value_snapshot = props.item.warranty_value_snapshot !== undefined && props.item.warranty_value_snapshot !== null && props.item.warranty_value_snapshot !== '' ? props.item.warranty_value_snapshot : (itemService ? (itemService.warranty_value !== undefined && itemService.warranty_value !== null ? itemService.warranty_value : '') : '');
            form.warranty_unit_snapshot = props.item.warranty_unit_snapshot || itemService?.warranty_unit || 'days';
            form.started_at = props.item.started_at ? formatDateForInput(props.item.started_at) : formatDateForInput(new Date());
            form.completed_at = props.item.completed_at ? formatDateForInput(props.item.completed_at) : '';
            form.due_date = props.item.due_date ? formatDateForInput(props.item.due_date) : formatDateForInput(new Date());
            
            linkedParts.value = props.item.parts || [];
            pendingParts.value = [];
            localTechnicians.value = (props.item.technicians || []).map(t => ({
                user_id: t.id,
                name: locale.value === 'ar' 
                    ? (t.employee?.name_ar || t.employee?.name_en || t.name) 
                    : (t.employee?.name_en || t.employee?.name_ar || t.name),
                share: t.pivot?.share !== undefined ? parseFloat(t.pivot.share) : 100.00
            }));
            localNotes.value = props.item.item_notes || [];
        } else {
            form.reset();
            form.qty = 1;
            form.duration_value = '';
            form.duration_unit = 'minutes';
            form.warranty_value_snapshot = '';
            form.warranty_unit_snapshot = 'days';
            form.started_at = formatDateForInput(new Date());
            form.completed_at = '';
            form.due_date = formatDateForInput(new Date());
            linkedParts.value = [];
            pendingParts.value = [];
            localTechnicians.value = [];
            localNotes.value = [];
        }
    }
}, { immediate: true });
</script>
