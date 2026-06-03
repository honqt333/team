<template>
    <DialogModal :show="show" @close="handleClose" max-width="3xl">
        <template #title>
            <div class="flex items-center justify-between">
                <span class="text-xl font-bold">{{ form.id ? $t('inventory.parts.edit') : $t('inventory.parts.add') }}</span>
                <span v-if="form.id" class="text-sm font-normal text-gray-500 bg-gray-100 dark:bg-gray-700 px-3 py-1 rounded-full">{{ form.sku }}</span>
            </div>
        </template>

        <template #content>
            <div class="space-y-6">
                <!-- Row 1: Image & Names Stacked -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                    <!-- Image Upload Section -->
                    <div class="md:col-span-1 flex flex-col">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                            {{ $t('inventory.parts.image') }}
                        </label>
                        <div 
                            @click="imageInput.click()" 
                            class="flex-1 flex flex-col items-center justify-center p-4 border-2 border-dashed border-gray-200 dark:border-gray-700 rounded-2xl bg-gray-50/50 dark:bg-gray-900/30 transition-all hover:bg-gray-100 dark:hover:bg-gray-800/50 group relative overflow-hidden min-h-[146px] h-full cursor-pointer"
                        >
                            <input
                                type="file"
                                ref="imageInput"
                                class="hidden"
                                accept="image/*"
                                @change="handleImageChange"
                            />
                            
                            <div v-if="imagePreview" class="relative group/preview w-full h-full flex items-center justify-center">
                                <img :src="imagePreview" class="max-h-28 object-contain rounded-xl shadow-md border-2 border-white dark:border-gray-800 transition-transform group-hover/preview:scale-105" />
                                <button 
                                    type="button"
                                    @click.stop="removeImage"
                                    class="absolute -top-2 -right-2 p-1 bg-red-500 text-white rounded-full shadow-lg opacity-0 group-hover/preview:opacity-100 transition-opacity"
                                >
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" /></svg>
                                </button>
                            </div>
                            
                            <div v-else class="flex flex-col items-center text-center">
                                <div class="w-10 h-10 rounded-xl bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 flex items-center justify-center mb-1.5 group-hover:scale-110 transition-transform">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                </div>
                                <span class="text-xs font-bold text-gray-600 dark:text-gray-400 leading-tight">{{ $t('inventory.parts.add_image') }}</span>
                                <span class="text-[9px] text-gray-400 mt-0.5">{{ $t('common.max_size', {size: '2MB'}) }}</span>
                            </div>
                        </div>
                        <p v-if="form.errors.image" class="mt-1 text-xs text-red-500">{{ form.errors.image }}</p>
                    </div>

                    <!-- Names Stacked -->
                    <div class="md:col-span-3 flex flex-col justify-between gap-4">
                        <!-- Name AR -->
                        <div :class="isAr ? 'order-1' : 'order-2'" class="flex-1 flex flex-col justify-center">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                                {{ $t('inventory.parts.name_ar') }} <span class="text-red-500">*</span>
                            </label>
                            <input
                                v-model="form.name_ar"
                                type="text"
                                :class="['w-full px-4 py-2.5 rounded-xl border bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 shadow-sm transition-all', form.errors.name_ar ? 'border-red-500 focus:ring-red-500' : 'border-gray-300 dark:border-gray-600']"
                                :placeholder="$t('inventory.parts.name_ar_placeholder')"
                            />
                            <p v-if="form.errors.name_ar" class="mt-1 text-xs text-red-500">{{ form.errors.name_ar }}</p>
                        </div>

                        <!-- Name EN -->
                        <div :class="isAr ? 'order-2' : 'order-1'" class="flex-1 flex flex-col justify-center">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                                {{ $t('inventory.parts.name_en') }} <span class="text-red-500">*</span>
                            </label>
                            <input
                                v-model="form.name_en"
                                type="text"
                                dir="ltr"
                                required
                                :class="['w-full px-4 py-2.5 rounded-xl border bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 shadow-sm transition-all', form.errors.name_en ? 'border-red-500' : 'border-gray-300 dark:border-gray-600']"
                                :placeholder="$t('inventory.parts.name_en_placeholder')"
                            />
                            <p v-if="form.errors.name_en" class="mt-1 text-xs text-red-500">{{ form.errors.name_en }}</p>
                        </div>
                    </div>
                </div>

                <!-- Row 2: SKU & Barcode -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- SKU -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                            {{ $t('inventory.parts.sku') }} <span class="text-red-500">*</span>
                        </label>
                        <input
                            v-model="form.sku"
                            type="text"
                            dir="ltr"
                            @input="sanitizeInput($event, 'sku')"
                            :class="['w-full px-4 py-2.5 rounded-xl border bg-white dark:bg-gray-900 text-gray-900 dark:text-white font-mono focus:ring-2 focus:ring-indigo-500 shadow-sm transition-all', form.errors.sku ? 'border-red-500 focus:ring-red-500' : 'border-gray-300 dark:border-gray-600']"
                            :placeholder="$t('inventory.parts.sku_placeholder')"
                        />
                        <p v-if="form.errors.sku" class="mt-1 text-xs text-red-500">{{ form.errors.sku }}</p>

                        <!-- Live Barcode Preview -->
                        <div v-if="form.sku" class="mt-3 flex items-center gap-3">
                            <div class="flex-1 flex justify-center">
                                <canvas ref="barcodeRef" class="max-w-full"></canvas>
                            </div>
                            <button
                                type="button"
                                @click="showBarcodePreview = true"
                                class="flex-shrink-0 text-gray-400 hover:text-indigo-600 dark:hover:text-indigo-400 p-1"
                                :title="$t('common.expand')"
                            >
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v6m3-3H7" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- Barcode -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                            {{ $t('inventory.parts.barcode') }}
                        </label>
                        <div class="relative">
                            <input
                                v-model="form.barcode"
                                type="text"
                                dir="ltr"
                                @input="sanitizeInput($event, 'barcode')"
                                :class="['w-full pl-10 pr-4 py-2.5 rounded-xl border bg-white dark:bg-gray-900 text-gray-900 dark:text-white font-mono focus:ring-2 focus:ring-indigo-500 shadow-sm transition-all', form.errors.barcode ? 'border-red-500 focus:ring-red-500' : 'border-gray-300 dark:border-gray-600']"
                                placeholder="Scan..."
                            />
                            <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-gray-400">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 5h2v14H3V5zm4 0h2v14H7V5zm4 0h1v14h-1V5zm3 0h2v14h-2V5zm4 0h1v14h-1V5zm3 0h2v14h-2V5z" />
                                </svg>
                            </div>
                        </div>
                        <p v-if="form.errors.barcode" class="mt-1 text-xs text-red-500">{{ form.errors.barcode }}</p>
                    </div>
                </div>

                <!-- Row 3: Unit & Category -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Unit -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                            {{ $t('inventory.parts.unit') }} <span class="text-red-500">*</span>
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

                    <!-- Category -->
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
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            {{ $t('inventory.parts.make_model_hint') }}
                        </p>
                    </div>
                </div>

                <!-- Row 4: Description -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                        {{ $t('inventory.parts.description') }}
                    </label>
                    <textarea
                        v-model="form.description"
                        rows="3"
                        :placeholder="$t('inventory.parts.description_placeholder')"
                        class="w-full px-4 py-2.5 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 shadow-sm transition-all resize-none"
                    ></textarea>
                </div>

                <!-- Row 5: Warehouse Stock Section -->
                <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-base font-bold text-gray-900 dark:text-white flex items-center gap-2">
                            <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                            </svg>
                            {{ $t('inventory.parts.warehouse_stock') }}
                        </h3>
                        <button
                            type="button"
                            @click="openAddWarehouseModal"
                            class="px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-xs rounded-lg flex items-center gap-1"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                            {{ $t('common.add') }}
                        </button>
                    </div>

                    <div v-if="form.warehouse_data.length > 0" class="overflow-x-auto border border-gray-200 dark:border-gray-700 rounded-xl">
                        <table class="min-w-full text-xs">
                            <thead class="bg-gray-50 dark:bg-gray-800">
                                <tr>
                                    <th class="px-2 py-2.5 text-center text-gray-500 font-semibold w-8">#</th>
                                    <th class="px-2 py-2.5 text-start text-gray-500 font-semibold">{{ $t('inventory.parts.warehouse') }}</th>
                                    <th class="px-2 py-2.5 text-center text-gray-500 font-semibold">{{ $t('inventory.parts.wac_cost') }}</th>
                                    <th class="px-2 py-2.5 text-center text-gray-500 font-semibold">{{ $t('inventory.parts.sale_price') }}</th>
                                    <th class="px-2 py-2.5 text-center text-gray-500 font-semibold">{{ $t('inventory.parts.min_sale_price') }}</th>
                                    <th class="px-2 py-2.5 text-center text-gray-500 font-semibold">{{ $t('inventory.parts.initial_stock') }}</th>
                                    <th class="px-2 py-2.5 text-center text-gray-500 font-semibold">{{ $t('inventory.parts.min_stock') }}</th>
                                    <th class="px-2 py-2.5 text-center text-gray-500 font-semibold">{{ $t('inventory.stock.current_stock') }}</th>
                                    <th class="px-2 py-2.5 text-center text-gray-500 font-semibold">{{ $t('common.status') }}</th>
                                    <th class="px-2 py-2.5 text-center text-gray-500 font-semibold">{{ $t('inventory.parts.allow_price_change') }}</th>
                                    <th class="px-2 py-2.5 text-center text-gray-500 font-semibold">{{ $t('inventory.parts.storage_location') }}</th>
                                    <th class="px-2 py-2.5 w-8"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                <tr v-for="(row, index) in form.warehouse_data" :key="index">
                                    <td class="px-2 py-2 text-center text-gray-400 font-mono">{{ index + 1 }}</td>
                                    <td class="px-2 py-2">
                                        <div class="text-sm font-medium text-gray-900 dark:text-white">{{ getWarehouseName(row.warehouse_id) }}</div>
                                        <div class="text-[10px] text-gray-500">{{ getWarehouseCenter(row.warehouse_id) }}</div>
                                    </td>
                                    <td class="px-2 py-2 text-center"><span class="font-mono">{{ formatNumber(row.cost_price) }}</span></td>
                                    <td class="px-2 py-2 text-center"><span class="font-mono">{{ formatNumber(row.sale_price) }}</span></td>
                                    <td class="px-2 py-2 text-center"><span class="font-mono">{{ formatNumber(row.min_sale_price) }}</span></td>
                                    <td class="px-2 py-2 text-center"><span class="font-mono">{{ formatNumber(row.initial_stock) }}</span></td>
                                    <td class="px-2 py-2 text-center"><span class="font-mono">{{ formatNumber(row.min_stock) }}</span></td>
                                    <td class="px-2 py-2 text-center"><span class="font-mono">{{ formatNumber(row.initial_stock) }}</span></td>
                                    <td class="px-2 py-2 text-center">
                                        <span class="px-2 py-0.5 rounded text-[10px] font-medium" :class="row.is_active ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' : 'bg-gray-100 text-gray-500'">
                                            {{ row.is_active ? $t('common.active') : $t('common.inactive') }}
                                        </span>
                                    </td>
                                    <td class="px-2 py-2 text-center">
                                        <span class="px-2 py-0.5 rounded text-[10px] font-medium" :class="row.allow_price_change ? 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400' : 'bg-gray-100 text-gray-500'">
                                            {{ row.allow_price_change ? $t('common.yes') : $t('common.no') }}
                                        </span>
                                    </td>
                                    <td class="px-2 py-2 text-center"><span class="font-mono">{{ row.storage_location || '-' }}</span></td>
                                    <td class="px-2 py-2 text-center">
                                        <button type="button" @click.stop="editWarehouseRow(index)" class="text-blue-500 hover:text-blue-700 p-1">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                            </svg>
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div v-else class="text-center py-6 text-gray-500 dark:text-gray-400 text-sm">
                        {{ $t('common.no_records') }}
                    </div>
                </div>

                <!-- Row 6: Pricing & Inventory Group -->
                <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
                    <h3 class="text-base font-bold text-gray-900 dark:text-white mb-4">
                        {{ $t('inventory.parts.inventory_pricing') }}
                    </h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <!-- Default Sale Price -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                                {{ $t('inventory.parts.default_sale_price') }}
                            </label>
                            <input 
                                v-model="form.default_sale_price" 
                                type="text" 
                                @input="sanitizeInput($event, 'default_sale_price')" 
                                class="w-full px-4 py-2.5 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 shadow-sm transition-all font-mono" 
                                dir="ltr" 
                            />
                        </div>
                        
                        <!-- Min Sale Price -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                                {{ $t('inventory.parts.min_sale_price') }}
                            </label>
                            <input 
                                v-model="form.min_sale_price" 
                                type="text" 
                                @input="sanitizeInput($event, 'min_sale_price')" 
                                class="w-full px-4 py-2.5 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 shadow-sm transition-all font-mono" 
                                dir="ltr" 
                            />
                        </div>

                        <!-- Cost (Read Only) -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                                {{ $t('inventory.stock.wac') }} ({{ $t('common.read_only') }})
                            </label>
                            <input 
                                disabled 
                                :value="0" 
                                type="text" 
                                class="w-full px-4 py-2.5 rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-100 dark:bg-gray-800 text-gray-500 focus:ring-0 shadow-sm font-mono cursor-not-allowed" 
                                dir="ltr" 
                            />
                        </div>

                        <!-- Min Qty -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                                {{ $t('inventory.parts.min_qty') }}
                            </label>
                            <input 
                                v-model="form.min_qty" 
                                type="text" 
                                @input="sanitizeInput($event, 'min_qty')" 
                                class="w-full px-4 py-2.5 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 shadow-sm transition-all font-mono" 
                                dir="ltr" 
                            />
                        </div>

                        <!-- Reorder Qty -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                                {{ $t('inventory.parts.reorder_qty') }}
                            </label>
                            <input 
                                v-model="form.reorder_qty" 
                                type="text" 
                                @input="sanitizeInput($event, 'reorder_qty')" 
                                class="w-full px-4 py-2.5 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 shadow-sm transition-all font-mono" 
                                dir="ltr" 
                            />
                        </div>

                        <!-- Active Toggle -->
                        <div class="flex items-center justify-between p-3 border border-gray-200 dark:border-gray-700 rounded-xl bg-gray-50/50 dark:bg-gray-900/30">
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ $t('common.active') }}</span>
                            <button 
                                type="button" 
                                class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2" 
                                :class="[form.is_active ? 'bg-indigo-600' : 'bg-gray-200 dark:bg-gray-700']" 
                                role="switch" 
                                @click="form.is_active = !form.is_active"
                            >
                                <span 
                                    class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out" 
                                    :class="[
                                        form.is_active 
                                            ? (isAr ? '-translate-x-5' : 'translate-x-5') 
                                            : 'translate-x-0'
                                    ]"
                                ></span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </template>

        <template #footer>
            <div class="flex justify-between w-full">
                <button
                    @click="close"
                    class="px-6 py-2.5 text-gray-700 dark:text-gray-300 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 rounded-xl font-medium transition-colors"
                >
                    {{ $t('common.cancel') }}
                </button>
                <div class="flex gap-3">
                    <button
                        @click="submit"
                        :disabled="form.processing"
                        class="px-8 py-2.5 bg-teal-600 hover:bg-teal-700 text-white rounded-xl font-medium shadow-lg shadow-teal-500/30 transition-all disabled:opacity-50 disabled:shadow-none"
                    >
                        {{ form.processing ? $t('common.saving') : $t('common.save') }}
                    </button>
                </div>
            </div>
        </template>
    </DialogModal>

    <!-- Barcode Fullscreen Modal -->
    <DialogModal :show="showBarcodePreview" @close="showBarcodePreview = false" max-width="sm">
        <template #title>
            <span class="text-xl font-bold">{{ $t('inventory.parts.barcode_preview') }}</span>
        </template>
        <template #content>
            <div class="flex flex-col items-center gap-6 py-8">
                <div class="bg-white rounded-xl border-2 border-gray-200 p-8 w-full">
                    <div class="flex flex-col items-center gap-3">
                        <canvas ref="barcodeFullRef" class="max-w-full"></canvas>
                        <span class="font-mono text-2xl font-bold text-gray-900 tracking-widest">{{ form.sku }}</span>
                    </div>
                </div>
                <div class="text-center">
                    <span class="text-xs text-gray-500 dark:text-gray-400">{{ $t('inventory.parts.sku') }}</span>
                </div>
            </div>
        </template>
        <template #footer>
            <div class="flex justify-center">
                <button
                    @click="showBarcodePreview = false"
                    class="px-6 py-2 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-lg font-medium transition-colors"
                >
                    {{ $t('common.close') }}
                </button>
            </div>
        </template>
    </DialogModal>

    <!-- Warehouse Stock Modal -->
    <WarehouseStockModal
        :show="showAddWarehouseModal"
        :edit-row-data="editingWarehouseData"
        :warehouses="props.warehouses"
        :used-warehouse-ids="usedWarehouseIds"
        @close="closeAddWarehouseModal"
        @save="editingWarehouseIndex !== null ? updateWarehouseFromModal($event) : addWarehouseFromModal($event)"
    />
</template>

<script setup>
import { useForm, usePage } from '@inertiajs/vue3';
import DialogModal from '@/Components/DialogModal.vue';
import { ref, watch, computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { useConfirm } from '@/Composables/useConfirm';
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
});

const emit = defineEmits(['close']);
const { t, locale } = useI18n();
const { confirm } = useConfirm();
const page = usePage();
const { formatQuantity, formatCurrency, toEnglish, sanitizeInput: internalSanitize } = useNumberFormat();

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
    return props.units.map(unit => ({
        value: unit.id,
        label: isAr.value ? unit.name_ar : (unit.name_en || unit.name_ar)
    }));
});

const categoryOptions = computed(() => {
    return props.categories.map(cat => ({
        value: cat.id,
        label: isAr.value ? cat.name_ar : (cat.name_en || cat.name_ar)
    }));
});

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
    _method: 'POST',
});

// Must be declared before the watch below (const arrow functions are NOT hoisted)
const getInitialWarehouseData = () => {
    if (props.part?.inventory_balances && props.part.inventory_balances.length > 0) {
        return props.part.inventory_balances.map(balance => ({
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

watch(() => props.part, (part) => {
    if (part) {
        form.id = part.id;
        form.sku = part.sku;
        form.barcode = part.barcode;
        form.name_ar = part.name_ar;
        form.name_en = part.name_en;
        form.unit_id = part.unit_id;
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
        imagePreview.value = part.image_url;
    } else {
        form.reset();
        form.id = null;
        form.is_active = true;
        form.unit_id = props.units.length > 0 ? props.units[0].id : '';
        form.image = null;
        form.remove_image = false;
        form.warehouse_data = getInitialWarehouseData();
        imagePreview.value = null;
    }
}, { immediate: true });

// Warehouse helpers
const usedWarehouseIds = computed(() => {
    const ids = form.warehouse_data.map(r => r.warehouse_id);
    if (editingWarehouseIndex.value !== null) {
        return ids.filter(id => id !== editingWarehouseData.value?.warehouse_id);
    }
    return ids;
});

const getWarehouseName = (warehouseId) => {
    const warehouse = props.warehouses.find(w => w.id === warehouseId);
    return warehouse?.name || '';
};

const getWarehouseCenter = (warehouseId) => {
    const warehouse = props.warehouses.find(w => w.id === warehouseId);
    return warehouse?.center_name || '';
};

const formatNumber = (num) => {
    return Number(num || 0).toFixed(2);
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


watch(() => props.show, (open) => {
    if (open) {
        // Reset sections state if desired, or keep as is
        setTimeout(() => {
            initialFormData.value = JSON.stringify(form.data());
            isDirty.value = false;
            // Render barcode if sku exists
            if (form.sku && barcodeRef.value) {
                renderBarcode(barcodeRef.value, form.sku, 2, 60);
            }
        }, 100);
    }
});

// Barcode renderer using Canvas (no external lib)
function renderBarcode(canvas, text, barWidth = 2, height = 80) {
    if (!canvas || !text) return;
    const ctx = canvas.getContext('2d');
    canvas.width = Math.max(text.length * (barWidth + 1) + 20, 200);
    canvas.height = height + 30;
    ctx.clearRect(0, 0, canvas.width, canvas.height);
    ctx.fillStyle = '#ffffff';
    ctx.fillRect(0, 0, canvas.width, canvas.height);
    ctx.fillStyle = '#111111';
    let x = 10;
    for (let i = 0; i < text.length; i++) {
        const charCode = text.charCodeAt(i);
        const bars = (charCode * 17 + i * 7) % 5 + 1;
        const bw = bars * barWidth;
        if (i % 2 === 0) {
            ctx.fillRect(x, 0, bw, height);
        }
        x += bw + 1;
        if (x > canvas.width - 10) break;
    }
}

// Watch form.sku for live barcode preview
watch(() => form.sku, (sku) => {
    if (!sku || !barcodeRef.value) return;
    renderBarcode(barcodeRef.value, sku, 2, 60);
});

// Watch showBarcodePreview for fullscreen barcode
watch(showBarcodePreview, (show) => {
    if (!show || !form.sku || !barcodeFullRef.value) return;
    setTimeout(() => {
        renderBarcode(barcodeFullRef.value, form.sku, 3, 100);
    }, 50);
});

// Track form changes
watch(() => form.data(), (newData) => {
    if (initialFormData.value) {
        const currentData = JSON.stringify(newData);
        isDirty.value = currentData !== initialFormData.value;
    }
}, { deep: true });

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

const submit = () => {
    // For file uploads, we use POST with _method spoofing if it's an update
    if (form.id) {
        form.transform((data) => ({
            ...data,
            _method: 'PUT',
        })).post(route('app.inventory.parts.update', form.id), {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => close(),
        });
    } else {
        form.post(route('app.inventory.parts.store'), {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => close(),
        });
    }
};
</script>
