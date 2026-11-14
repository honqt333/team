<template>
    <BaseModal :show="show" @close="handleClose" size="lg">
        <template #title>
            <div class="flex items-center justify-between gap-2">
                <span class="text-xl font-bold">
                    {{ form.id ? $t('inventory.parts.edit') : $t('inventory.parts.add') }}
                </span>
                <span
                    v-if="form.id"
                    class="text-sm font-normal text-gray-500 bg-gray-100 dark:bg-gray-700 px-3 py-1 rounded-full"
                >
                    {{ form.sku }}
                </span>
            </div>
        </template>

        <div class="space-y-6">
            <!-- Row 1: Image & Names Stacked -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                <!-- Image Upload Section -->
                <div class="md:col-span-1 flex flex-col min-w-0">
                    <AppFileUpload
                        v-model="form.image"
                        variant="image"
                        compact-preview
                        :label="$t('inventory.parts.image')"
                        :existing-url="imagePreview || ''"
                        accept="image/*"
                        :max-size-mb="2"
                        :error="form.errors.image"
                        @remove-existing="removeImage"
                    />
                </div>

                <!-- Names Stacked -->
                <div class="md:col-span-3 flex flex-col justify-between gap-4 min-w-0">
                    <!-- Name AR -->
                    <div
                        :class="isAr ? 'order-1' : 'order-2'"
                        class="flex-1 flex flex-col justify-center"
                    >
                        <AppInput
                            id="name_ar"
                            v-model="form.name_ar"
                            type="text"
                            :label="$t('inventory.parts.name_ar')"
                            :placeholder="$t('inventory.parts.name_ar_placeholder')"
                            :error="form.errors.name_ar"
                            required
                        />
                    </div>

                    <!-- Name EN -->
                    <div
                        :class="isAr ? 'order-2' : 'order-1'"
                        class="flex-1 flex flex-col justify-center"
                    >
                        <AppInput
                            id="name_en"
                            v-model="form.name_en"
                            type="text"
                            dir="ltr"
                            :label="$t('inventory.parts.name_en')"
                            :placeholder="$t('inventory.parts.name_en_placeholder')"
                            :error="form.errors.name_en"
                            required
                        />
                    </div>
                </div>
            </div>

            <!-- Row 2: رقم القطعة / باركود  +  وحدة التخزين -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- SKU / Barcode (merged) -->
                <div>
                    <AppInput
                        id="sku"
                        v-model="form.sku"
                        type="text"
                        dir="ltr"
                        @input="sanitizeInput($event, 'sku')"
                        :label="`${$t('inventory.parts.sku')} / ${$t('inventory.parts.barcode')}`"
                        :placeholder="$t('inventory.parts.sku_placeholder')"
                        :error="form.errors.sku"
                        class="font-mono"
                        required
                    />

                    <!-- Live Barcode Preview -->
                    <div v-if="form.sku" class="mt-3 flex items-center gap-3">
                        <div class="flex-1 flex justify-center">
                            <canvas ref="barcodeRef" class="max-w-full"></canvas>
                        </div>
                        <button
                            type="button"
                            @click="showBarcodePreview = true"
                            class="flex-shrink-0 text-gray-400 hover:text-action-primary p-1"
                            :title="$t('common.expand')"
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
                                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v6m3-3H7"
                                />
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Unit (moved here from Row 3) -->
                <div>
                    <label
                        class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5"
                    >
                        {{ $t('inventory.parts.unit') }}
                        <span class="text-red-500">*</span>
                    </label>
                    <SearchableSelect
                        v-model="form.unit_id"
                        :options="unitOptions"
                        option-label="label"
                        option-value="value"
                        :label="''"
                        :placeholder="$t('common.select')"
                        :error="form.errors.unit_id"
                        required
                    />
                </div>
            </div>

            <!-- Row 2b: وحدة الشراء ومعامل التحويل -->
            <div
                class="grid grid-cols-1 md:grid-cols-2 gap-6 bg-blue-50/40 dark:bg-blue-900/10 p-4 rounded-2xl border border-blue-100 dark:border-blue-800/30"
            >
                <!-- وحدة الشراء -->
                <div>
                    <label
                        class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5"
                    >
                        {{ $t('purchasing.items.purchase_unit') }}
                    </label>
                    <SearchableSelect
                        v-model="form.purchase_unit_id"
                        :options="unitOptions"
                        option-label="label"
                        option-value="value"
                        :label="''"
                        :placeholder="$t('common.optional')"
                        :error="form.errors.purchase_unit_id"
                    />
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        {{ $t('inventory.parts.purchase_unit_hint') }}
                    </p>
                </div>

                <!-- معامل التحويل -->
                <div>
                    <label
                        class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5"
                    >
                        {{ $t('purchasing.items.conversion_factor') }}
                    </label>
                    <div class="relative">
                        <AppInput
                            id="purchase_conversion_factor"
                            v-model="form.purchase_conversion_factor"
                            type="number"
                            min="0.0001"
                            step="0.0001"
                            dir="ltr"
                            lang="en"
                            :disabled="!form.purchase_unit_id"
                            :class="form.purchase_unit_id ? 'pl-20 font-mono' : 'font-mono'"
                            placeholder="1"
                        />
                        <span
                            v-if="form.purchase_unit_id"
                            class="absolute inset-y-0 left-3 flex items-center text-xs text-gray-400 pointer-events-none"
                        >
                            {{ unitOptions.find((u) => u.value === form.purchase_unit_id)?.label }}
                            &nbsp;=&nbsp;
                        </span>
                    </div>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        {{ $t('inventory.parts.conversion_factor_hint') }}
                    </p>
                </div>
            </div>

            <!-- Row 3: Category only -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                    {{ $t('inventory.parts.category') }}
                </label>
                <SearchableSelect
                    v-model="form.category_id"
                    :options="categoryOptions"
                    option-label="label"
                    option-value="value"
                    :label="''"
                    :placeholder="$t('common.select')"
                    :error="form.errors.category_id"
                />
                <p class="mt-2 text-xs text-gray-500 flex items-center gap-1">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                        />
                    </svg>
                    {{ $t('inventory.parts.make_model_hint') }}
                </p>
            </div>

            <!-- Row 3b: Vehicle Compatibilities -->
            <div
                class="border border-gray-200 dark:border-gray-700 rounded-2xl p-4 bg-gray-50/40 dark:bg-gray-900/20 space-y-3"
            >
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <h3
                        class="text-sm font-bold text-gray-900 dark:text-white flex items-center gap-2"
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
                                d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z"
                            />
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0"
                            />
                        </svg>
                        {{ $t('inventory.parts.compatibility.title') }}
                    </h3>
                    <AppButton variant="action-xs" @click="addCompatibilityRow">
                        <template #iconLeft>
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
                        </template>
                        {{ $t('inventory.parts.compatibility.add_vehicle') }}
                    </AppButton>
                </div>

                <div v-if="form.compatibilities.length > 0" class="space-y-2.5">
                    <div
                        v-for="(compat, idx) in form.compatibilities"
                        :key="idx"
                        class="grid grid-cols-2 sm:grid-cols-12 gap-2.5 items-end bg-white dark:bg-gray-800 p-3 rounded-xl border border-gray-200 dark:border-gray-700"
                    >
                        <div class="col-span-2 sm:col-span-4 min-w-0">
                            <label
                                class="block text-[11px] font-semibold text-gray-600 dark:text-gray-400 mb-1"
                            >
                                {{ $t('inventory.parts.compatibility.make') }}
                                <span class="text-red-500">*</span>
                            </label>
                            <SearchableSelect
                                v-model="compat.vehicle_make_id"
                                :options="makeOptions"
                                option-label="label"
                                option-value="value"
                                :placeholder="$t('inventory.parts.compatibility.make')"
                                @update:modelValue="compat.vehicle_model_id = null"
                                compact
                            />
                        </div>

                        <div class="col-span-2 sm:col-span-3 min-w-0">
                            <label
                                class="block text-[11px] font-semibold text-gray-600 dark:text-gray-400 mb-1"
                            >
                                {{ $t('inventory.parts.compatibility.model') }}
                            </label>
                            <SearchableSelect
                                v-model="compat.vehicle_model_id"
                                :options="getModelOptionsForMake(compat.vehicle_make_id)"
                                option-label="label"
                                option-value="value"
                                :placeholder="$t('inventory.parts.compatibility.all_models')"
                                :disabled="!compat.vehicle_make_id"
                                compact
                            />
                        </div>

                        <div class="col-span-1 sm:col-span-2 min-w-0">
                            <label
                                class="block text-[11px] font-semibold text-gray-600 dark:text-gray-400 mb-1"
                            >
                                {{ $t('inventory.parts.compatibility.year_from') }}
                            </label>
                            <AppInput
                                v-model="compat.year_from"
                                type="number"
                                min="1950"
                                max="2100"
                                dir="ltr"
                                lang="en"
                                placeholder="2015"
                                class="h-[38px] px-2.5 text-xs font-mono"
                            />
                        </div>

                        <div class="col-span-1 sm:col-span-2 min-w-0">
                            <label
                                class="block text-[11px] font-semibold text-gray-600 dark:text-gray-400 mb-1"
                            >
                                {{ $t('inventory.parts.compatibility.year_to') }}
                            </label>
                            <AppInput
                                v-model="compat.year_to"
                                type="number"
                                min="1950"
                                max="2100"
                                dir="ltr"
                                lang="en"
                                placeholder="2026"
                                class="h-[38px] px-2.5 text-xs font-mono"
                            />
                        </div>

                        <div class="col-span-2 sm:col-span-1 flex justify-end">
                            <AppButton
                                type="button"
                                variant="ghost"
                                size="sm"
                                @click="removeCompatibilityRow(idx)"
                                class="!h-[38px] !px-2.5 !text-red-500 hover:!bg-red-50 dark:hover:!bg-red-900/20"
                                :title="$t('common.delete')"
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
                            </AppButton>
                        </div>
                    </div>
                </div>
                <p v-else class="text-xs text-gray-500 dark:text-gray-400 text-center py-2">
                    {{ $t('inventory.parts.compatibility.empty') }}
                </p>
            </div>

            <!-- Row 4: Description -->
            <div>
                <AppTextarea
                    id="description"
                    v-model="form.description"
                    :rows="3"
                    :label="$t('inventory.parts.description')"
                    :placeholder="$t('inventory.parts.description_placeholder')"
                />
            </div>

            <!-- Row 5: Warehouse Stock Section -->
            <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
                <div class="flex flex-wrap items-center justify-between gap-2 mb-4">
                    <h3
                        class="text-base font-bold text-gray-900 dark:text-white flex items-center gap-2"
                    >
                        <svg
                            class="w-5 h-5 text-action-primary"
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
                        {{ $t('inventory.parts.warehouse_stock') }}
                    </h3>
                    <AppButton variant="action-xs" @click="openAddWarehouseModal">
                        <template #iconLeft>
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
                                    d="M12 4v16m8-8H4"
                                />
                            </svg>
                        </template>
                        {{ $t('common.add') }}
                    </AppButton>
                </div>

                <div
                    v-if="form.warehouse_data.length > 0"
                    class="overflow-x-auto border border-gray-200 dark:border-gray-700 rounded-xl"
                >
                    <DataTable structured class="min-w-full text-xs">
                        <thead class="bg-gray-50 dark:bg-gray-800">
                            <tr>
                                <th class="px-2 py-2.5 text-center text-gray-500 font-semibold w-8">
                                    #
                                </th>
                                <th class="px-2 py-2.5 text-start text-gray-500 font-semibold">
                                    {{ $t('inventory.parts.warehouse') }}
                                </th>
                                <th class="px-2 py-2.5 text-center text-gray-500 font-semibold">
                                    {{ $t('inventory.parts.wac_cost') }}
                                </th>
                                <th class="px-2 py-2.5 text-center text-gray-500 font-semibold">
                                    {{ $t('inventory.parts.sale_price') }}
                                </th>
                                <th class="px-2 py-2.5 text-center text-gray-500 font-semibold">
                                    {{ $t('inventory.parts.min_sale_price') }}
                                </th>
                                <th class="px-2 py-2.5 text-center text-gray-500 font-semibold">
                                    {{ $t('inventory.parts.initial_stock') }}
                                </th>
                                <th class="px-2 py-2.5 text-center text-gray-500 font-semibold">
                                    {{ $t('inventory.parts.min_stock') }}
                                </th>
                                <th class="px-2 py-2.5 text-center text-gray-500 font-semibold">
                                    {{ $t('inventory.stock.current_stock') }}
                                </th>
                                <th class="px-2 py-2.5 text-center text-gray-500 font-semibold">
                                    {{ $t('common.status') }}
                                </th>
                                <th class="px-2 py-2.5 text-center text-gray-500 font-semibold">
                                    {{ $t('inventory.parts.allow_price_change') }}
                                </th>
                                <th class="px-2 py-2.5 text-center text-gray-500 font-semibold">
                                    {{ $t('inventory.parts.storage_location') }}
                                </th>
                                <th class="px-2 py-2.5 w-8"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            <tr v-for="(row, index) in form.warehouse_data" :key="index">
                                <td class="px-2 py-2 text-center text-gray-400 font-mono">
                                    {{ index + 1 }}
                                </td>
                                <td class="px-2 py-2">
                                    <div class="text-sm font-medium text-gray-900 dark:text-white">
                                        {{ getWarehouseName(row.warehouse_id) }}
                                    </div>
                                    <div class="text-[10px] text-gray-500">
                                        {{ getWarehouseCenter(row.warehouse_id) }}
                                    </div>
                                </td>
                                <td class="px-2 py-2 text-center">
                                    <span class="font-mono">
                                        {{ formatNumber(row.cost_price) }}
                                    </span>
                                </td>
                                <td class="px-2 py-2 text-center">
                                    <span class="font-mono">
                                        {{ formatNumber(row.sale_price) }}
                                    </span>
                                </td>
                                <td class="px-2 py-2 text-center">
                                    <span class="font-mono">
                                        {{ formatNumber(row.min_sale_price) }}
                                    </span>
                                </td>
                                <td class="px-2 py-2 text-center">
                                    <span class="font-mono">
                                        {{ formatNumber(row.initial_stock) }}
                                    </span>
                                </td>
                                <td class="px-2 py-2 text-center">
                                    <span class="font-mono">
                                        {{ formatNumber(row.min_stock) }}
                                    </span>
                                </td>
                                <td class="px-2 py-2 text-center">
                                    <span class="font-mono">
                                        {{ formatNumber(row.initial_stock) }}
                                    </span>
                                </td>
                                <td class="px-2 py-2 text-center">
                                    <span
                                        class="px-2 py-0.5 rounded text-[10px] font-medium"
                                        :class="
                                            row.is_active
                                                ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400'
                                                : 'bg-gray-100 text-gray-500'
                                        "
                                    >
                                        {{
                                            row.is_active
                                                ? $t('common.active')
                                                : $t('common.inactive')
                                        }}
                                    </span>
                                </td>
                                <td class="px-2 py-2 text-center">
                                    <span
                                        class="px-2 py-0.5 rounded text-[10px] font-medium"
                                        :class="
                                            row.allow_price_change
                                                ? 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400'
                                                : 'bg-gray-100 text-gray-500'
                                        "
                                    >
                                        {{
                                            row.allow_price_change
                                                ? $t('common.yes')
                                                : $t('common.no')
                                        }}
                                    </span>
                                </td>
                                <td class="px-2 py-2 text-center">
                                    <span class="font-mono">
                                        {{ row.storage_location || '-' }}
                                    </span>
                                </td>
                                <td class="px-2 py-2 text-center">
                                    <button
                                        type="button"
                                        @click.stop="editWarehouseRow(index)"
                                        class="text-blue-500 hover:text-blue-700 p-1"
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
                                </td>
                            </tr>
                        </tbody>
                    </DataTable>
                </div>
                <div v-else class="text-center py-6 text-gray-500 dark:text-gray-400 text-sm">
                    {{ $t('common.no_records') }}
                </div>
            </div>
        </div>

        <template #footer>
            <div class="flex flex-wrap items-center justify-end gap-3 w-full">
                <AppButton variant="secondary" @click="close">
                    {{ $t('common.cancel') }}
                </AppButton>
                <AppButton
                    variant="action"
                    @click="submit"
                    :disabled="form.processing"
                    :loading="form.processing"
                >
                    {{ form.processing ? $t('common.saving') : $t('common.save') }}
                </AppButton>
            </div>
        </template>
    </BaseModal>

    <!-- Barcode Fullscreen Modal -->
    <BaseModal
        :show="showBarcodePreview"
        :title="$t('inventory.parts.barcode_preview')"
        size="sm"
        @close="showBarcodePreview = false"
    >
        <div class="flex flex-col items-center gap-6 py-4">
            <div
                class="bg-white rounded-xl border-2 border-gray-200 dark:border-gray-700 p-8 w-full"
            >
                <div class="flex flex-col items-center gap-3">
                    <canvas ref="barcodeFullRef" class="max-w-full"></canvas>
                    <span class="font-mono text-2xl font-bold text-gray-900 tracking-widest">
                        {{ form.sku }}
                    </span>
                </div>
            </div>
            <div class="text-center">
                <span class="text-xs text-gray-500 dark:text-gray-400">
                    {{ $t('inventory.parts.sku') }}
                </span>
            </div>
        </div>
        <template #footer>
            <div class="flex justify-center w-full">
                <AppButton variant="secondary" @click="showBarcodePreview = false">
                    {{ $t('common.close') }}
                </AppButton>
            </div>
        </template>
    </BaseModal>

    <!-- Warehouse Stock Modal -->
    <WarehouseStockModal
        :show="showAddWarehouseModal"
        :edit-row-data="editingWarehouseData"
        :warehouses="props.warehouses"
        :used-warehouse-ids="usedWarehouseIds"
        :part-name="
            (isAr ? form.name_ar : form.name_en) || form.name_ar || form.name_en || form.sku
        "
        @close="closeAddWarehouseModal"
        @save="
            editingWarehouseIndex !== null
                ? updateWarehouseFromModal($event)
                : addWarehouseFromModal($event)
        "
    />
</template>

<script setup>
import DataTable from '@/Components/Common/DataTable.vue';
import AppFileUpload from '@/Components/Common/AppFileUpload.vue';
import AppButton from '@/Components/App/AppButton.vue';
import AppInput from '@/Components/App/AppInput.vue';
import AppTextarea from '@/Components/App/AppTextarea.vue';
import BaseModal from '@/Components/BaseModal.vue';
import { useForm, usePage } from '@inertiajs/vue3';
import { ref, watch, computed } from 'vue';
import JsBarcode from 'jsbarcode';
import { useI18n } from 'vue-i18n';
import { useConfirm } from '@/Composables/useConfirm';
import { useFormScrollToError } from '@/Composables/useFormScrollToError';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import { useNumberFormat } from '@/Composables/useNumberFormat';
import WarehouseStockModal from './WarehouseStockModal.vue';

const props = defineProps({
    show: Boolean,
    part: Object,
    units: {
        type: Array,
        default: () => [],
    },
    categories: {
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
});

const emit = defineEmits(['close']);
const { t, locale } = useI18n();
const { confirm } = useConfirm();
const page = usePage();
const {
    formatQuantity,
    formatCurrency,
    toEnglish,
    sanitizeInput: internalSanitize,
} = useNumberFormat();

const isAr = computed(() => locale.value === 'ar');

const imageInput = ref(null);
const imagePreview = ref(null);
const showBarcodePreview = ref(false);
const barcodeRef = ref(null);
const barcodeFullRef = ref(null);
const showAddWarehouseModal = ref(false);
const editingWarehouseIndex = ref(null);
const editingWarehouseData = ref(null);

const handleImageChange = (e) => {
    const file = e.target.files[0];
    if (!file) return;

    form.image = file;
    const reader = new FileReader();
    reader.onload = (e) => {
        imagePreview.value = e.target.result;
    };
    reader.readAsDataURL(file);
};

const removeImage = () => {
    form.image = null;
    form.remove_image = true;
    imagePreview.value = null;
    if (imageInput.value) imageInput.value.value = '';
};

const sanitizeInput = (event, field) => {
    const sanitized = internalSanitize(event);
    form[field] = sanitized;
};

const unitOptions = computed(() => {
    return props.units.map((unit) => ({
        value: unit.id,
        label: isAr.value ? unit.name_ar : unit.name_en || unit.name_ar,
    }));
});

const categoryOptions = computed(() => {
    return props.categories.map((cat) => ({
        value: cat.id,
        label: isAr.value ? cat.name_ar : cat.name_en || cat.name_ar,
    }));
});

const makeOptions = computed(() => {
    return (props.vehicleMakes || []).map((make) => ({
        value: make.id,
        label: isAr.value ? make.name_ar || make.name_en : make.name_en || make.name_ar,
    }));
});

const getModelOptionsForMake = (makeId) => {
    const allOption = { value: null, label: t('inventory.parts.compatibility.all_models') };
    if (!makeId) return [allOption];
    const models = (props.vehicleModels || [])
        .filter((m) => Number(m.make_id) === Number(makeId))
        .map((m) => ({
            value: m.id,
            label: isAr.value ? m.name_ar || m.name_en : m.name_en || m.name_ar,
        }));
    return [allOption, ...models];
};

const addCompatibilityRow = () => {
    form.compatibilities.push({
        vehicle_make_id: '',
        vehicle_model_id: null,
        year_from: '',
        year_to: '',
        notes: '',
    });
};

const removeCompatibilityRow = (index) => {
    form.compatibilities.splice(index, 1);
};

const isDirty = ref(false);
const initialFormData = ref(null);
const isConfirming = ref(false);

const form = useForm({
    id: null,
    sku: '',
    barcode: '',
    name_ar: '',
    name_en: '',
    unit_id: '',
    purchase_unit_id: '',
    purchase_conversion_factor: 1,
    category_id: '',
    description: '',
    min_qty: 0,
    reorder_qty: 0,
    default_sale_price: 0,
    min_sale_price: 0,
    is_active: true,
    image: null,
    remove_image: false,
    warehouse_data: [],
    compatibilities: [],
    _method: 'POST',
});

// Must be declared before the watch below (const arrow functions are NOT hoisted)
const getInitialCompatibilities = () => {
    if (props.part?.compatibilities && props.part.compatibilities.length > 0) {
        return props.part.compatibilities.map((c) => ({
            vehicle_make_id: c.vehicle_make_id,
            vehicle_model_id: c.vehicle_model_id ?? null,
            year_from: c.year_from || '',
            year_to: c.year_to || '',
            notes: c.notes || '',
        }));
    }
    return [];
};

const getInitialWarehouseData = () => {
    if (props.part?.inventory_balances && props.part.inventory_balances.length > 0) {
        return props.part.inventory_balances.map((balance) => ({
            warehouse_id: balance.warehouse_id,
            cost_price: balance.wac_cost || 0,
            sale_price: balance.sale_price || 0,
            min_sale_price: balance.min_sale_price || 0,
            initial_stock: balance.qty_on_hand || 0,
            min_stock: balance.min_stock || 0,
            storage_location: balance.storage_location || '',
            is_active: balance.is_active ?? true,
            allow_price_change: balance.allow_price_change ?? false,
        }));
    }
    return [];
};

watch(
    () => props.part,
    (part) => {
        if (part) {
            form.id = part.id;
            form.sku = part.sku;
            form.barcode = part.barcode;
            form.name_ar = part.name_ar;
            form.name_en = part.name_en;
            form.unit_id = part.unit_id;
            form.purchase_unit_id = part.purchase_unit_id || '';
            form.purchase_conversion_factor = parseFloat(part.purchase_conversion_factor) || 1;
            form.category_id = part.category_id;
            form.description = part.description;
            form.min_qty = parseFloat(part.min_qty || 0);
            form.reorder_qty = parseFloat(part.reorder_qty || 0);
            form.default_sale_price = parseFloat(part.default_sale_price || 0);
            form.min_sale_price = parseFloat(part.min_sale_price || 0);
            form.is_active = part.is_active;
            form.image = null;
            form.remove_image = false;
            form.warehouse_data = getInitialWarehouseData();
            form.compatibilities = getInitialCompatibilities();
            imagePreview.value = part.image_url;
        } else {
            form.reset();
            form.id = null;
            form.is_active = true;
            form.unit_id = props.units.length > 0 ? props.units[0].id : '';
            form.purchase_unit_id = '';
            form.purchase_conversion_factor = 1;
            form.image = null;
            form.remove_image = false;
            form.warehouse_data = getInitialWarehouseData();
            form.compatibilities = [];
            imagePreview.value = null;
        }
    },
    { immediate: true }
);

// Warehouse helpers
const usedWarehouseIds = computed(() => {
    const ids = form.warehouse_data.map((r) => r.warehouse_id);
    if (editingWarehouseIndex.value !== null) {
        return ids.filter((id) => id !== editingWarehouseData.value?.warehouse_id);
    }
    return ids;
});

const getWarehouseName = (warehouseId) => {
    const warehouse = props.warehouses.find((w) => w.id === warehouseId);
    return warehouse?.name || '';
};

const getWarehouseCenter = (warehouseId) => {
    const warehouse = props.warehouses.find((w) => w.id === warehouseId);
    return warehouse?.center_name || '';
};

const formatNumber = (num) => {
    return parseFloat(Number(num || 0).toFixed(4)).toString();
};

const openAddWarehouseModal = () => {
    editingWarehouseIndex.value = null;
    editingWarehouseData.value = null;
    showAddWarehouseModal.value = true;
};

const editWarehouseRow = (index) => {
    const row = form.warehouse_data[index];
    editingWarehouseIndex.value = index;
    editingWarehouseData.value = { ...row };
    showAddWarehouseModal.value = true;
};

const closeAddWarehouseModal = () => {
    showAddWarehouseModal.value = false;
    editingWarehouseIndex.value = null;
    editingWarehouseData.value = null;
};

const addWarehouseFromModal = (data) => {
    form.warehouse_data.push(data);
};

const updateWarehouseFromModal = (data) => {
    if (editingWarehouseIndex.value !== null) {
        form.warehouse_data[editingWarehouseIndex.value] = { ...data };
    }
    closeAddWarehouseModal();
};

watch(
    () => props.show,
    (open) => {
        if (open) {
            // Reset sections state if desired, or keep as is
            setTimeout(() => {
                initialFormData.value = JSON.stringify(form.data());
                isDirty.value = false;
                // Render barcode if sku exists
                if (form.sku && barcodeRef.value) {
                    renderBarcode(barcodeRef.value, form.sku, 40);
                }
            }, 100);
        }
    }
);

// Real barcode renderer using JsBarcode (Code128)
function renderBarcode(canvas, text, height = 40) {
    if (!canvas || !text) return;
    try {
        JsBarcode(canvas, text, {
            format: 'CODE128',
            width: 1.5,
            height: height,
            displayValue: false,
            margin: 4,
            background: '#ffffff',
            lineColor: '#111111',
        });
    } catch (e) {
        // Invalid barcode value — clear canvas
        const ctx = canvas.getContext('2d');
        ctx.clearRect(0, 0, canvas.width, canvas.height);
    }
}

// Watch form.sku for live barcode preview
watch(
    () => form.sku,
    (sku) => {
        if (!sku || !barcodeRef.value) return;
        renderBarcode(barcodeRef.value, sku, 40);
    }
);

// Watch showBarcodePreview for fullscreen barcode
watch(showBarcodePreview, (show) => {
    if (!show || !form.sku || !barcodeFullRef.value) return;
    setTimeout(() => {
        renderBarcode(barcodeFullRef.value, form.sku, 90);
    }, 50);
});

// Track form changes
watch(
    () => form.data(),
    (newData) => {
        if (initialFormData.value) {
            const currentData = JSON.stringify(newData);
            isDirty.value = currentData !== initialFormData.value;
        }
    },
    { deep: true }
);

const close = () => {
    emit('close');
    form.reset();
    form.clearErrors();
    isDirty.value = false;
    initialFormData.value = null;
    imagePreview.value = null;
};

async function handleClose() {
    if (isConfirming.value) return;

    if (isDirty.value) {
        isConfirming.value = true;
        try {
            const confirmed = await confirm({
                title: t('common.unsaved_changes'),
                message: t('common.unsaved_changes_message'),
                confirmText: t('common.yes_close'),
                cancelText: t('common.cancel'),
                type: 'warning',
            });
            if (!confirmed) return;
        } finally {
            isConfirming.value = false;
        }
    }
    close();
}

const { scrollToError } = useFormScrollToError();

const submit = () => {
    const cleanCompatibilities = (form.compatibilities || []).filter((c) => c.vehicle_make_id);
    // For file uploads, we use POST with _method spoofing if it's an update
    if (form.id) {
        form.transform((data) => ({
            ...data,
            compatibilities: cleanCompatibilities,
            sync_compatibilities: 1,
            purchase_conversion_factor: data.purchase_conversion_factor
                ? Number(data.purchase_conversion_factor)
                : 1,
            _method: 'PUT',
        })).post(route('app.inventory.parts.update', form.id), {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => close(),
            onError: (errors) => scrollToError(errors),
        });
    } else {
        form.transform((data) => ({
            ...data,
            compatibilities: cleanCompatibilities,
            purchase_conversion_factor: data.purchase_conversion_factor
                ? Number(data.purchase_conversion_factor)
                : 1,
        })).post(route('app.inventory.parts.store'), {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => close(),
            onError: (errors) => scrollToError(errors),
        });
    }
};
</script>
