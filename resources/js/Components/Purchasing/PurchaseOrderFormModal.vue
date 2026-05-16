<template>
    <BaseModal :show="show" @close="handleModalClose" size="5xl">
        <template #title>
            <div class="flex items-center gap-3">
                <div
                    class="w-10 h-10 rounded-xl bg-gradient-to-br from-indigo-600 to-purple-600 flex items-center justify-center shadow-lg shadow-indigo-500/20">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                    </svg>
                </div>
                {{ order ? $t('purchasing.orders.edit') : $t('purchasing.orders.add') }}
            </div>
        </template>

        <form @submit.prevent="submit" class="space-y-6">
            <!-- Error Alert -->
            <div v-if="form.hasErrors"
                class="rounded-xl border border-red-200 bg-red-50 p-4 dark:border-red-900/50 dark:bg-red-900/20">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd"
                                d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                                clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-red-800 dark:text-red-200">
                            {{ $t('common.error_alert_title') || 'There were errors with your submission' }}
                        </h3>
                        <div class="mt-2 text-sm text-red-700 dark:text-red-300">
                            <ul role="list" class="list-disc pl-5 space-y-1">
                                <li v-for="(error, key) in form.errors" :key="key">{{ error }}</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Header Info -->
            <div class="bg-gray-50 dark:bg-gray-900/50 rounded-xl p-4 border border-gray-200 dark:border-gray-700">
                <div class="grid grid-cols-1 md:grid-cols-12 gap-4">
                    <!-- Warehouse (Span 6) -->
                    <div class="md:col-span-6">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                            {{ $t('inventory.warehouses.title') }} <span class="text-red-500">*</span>
                        </label>
                        <SearchableSelect 
                            v-model="form.warehouse_id" 
                            :options="warehouses"
                            option-label="name"
                            option-value="id"
                            :placeholder="$t('inventory.warehouses.select')"
                            required
                        />
                        <div v-if="form.errors.warehouse_id" class="text-red-500 text-xs mt-1">{{
                            form.errors.warehouse_id }}
                        </div>
                    </div>

                    <!-- Supplier (Span 6) -->
                    <div class="md:col-span-6">
                        <div class="flex items-center justify-between mb-1.5">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                {{ $t('purchasing.orders.supplier') }} <span class="text-red-500">*</span>
                            </label>
                            <button type="button" @click="showSupplierModal = true"
                                class="text-xs text-blue-600 hover:text-blue-700 font-bold flex items-center gap-1 transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                </svg>
                                {{ $t('purchasing.suppliers.add') }}
                            </button>
                        </div>
                        <SearchableSelect v-model="form.supplier_id" :options="allSuppliers" option-label="name"
                            option-value="id" :placeholder="$t('purchasing.orders.select_supplier')" class="w-full"
                            required />
                        <div v-if="form.errors.supplier_id" class="text-red-500 text-xs mt-1">{{ form.errors.supplier_id }}</div>
                    </div>

                    <!-- Date (Span 3) -->
                    <div class="md:col-span-3">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                            {{ $t('purchasing.orders.date') }} <span class="text-red-500">*</span>
                        </label>
                        <CustomDatePicker v-model="form.order_date" :placeholder="$t('purchasing.orders.date')" />
                    </div>

                    <!-- Expected Date (Span 3) -->
                    <div class="md:col-span-3">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                            {{ $t('purchasing.orders.expected_date') }}
                        </label>
                        <CustomDatePicker v-model="form.expected_date" :placeholder="$t('purchasing.orders.expected_date')" />
                    </div>

                    <!-- Supplier Reference (Span 6) -->
                    <div class="md:col-span-6">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                            {{ $t('purchasing.orders.supplier_reference') }} <span class="text-red-500">*</span>
                        </label>
                        <input type="text" v-model="form.supplier_reference" required
                            :placeholder="$t('purchasing.orders.supplier_reference_placeholder')"
                            class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500" />
                    </div>


                </div>
            </div>

            <!-- Items Section -->
            <div class="space-y-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white">{{ $t('purchasing.orders.items') }}
                        </h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400" dir="ltr" lang="en">
                            {{ itemsTotal > 0 ? formatCurrency(itemsTotal) : $t('purchasing.orders.no_items') }}
                        </p>
                    </div>
                    <button type="button" @click.stop="openItemModal()"
                        class="flex items-center gap-2 px-4 py-2 bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400 rounded-xl hover:bg-blue-100 dark:hover:bg-blue-900/40 transition-all font-bold text-sm select-none active:scale-95 shadow-sm border border-blue-100 dark:border-blue-800/30">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        {{ $t('purchasing.items.add') }}
                    </button>
                </div>

                <!-- Items Table -->
                <div
                    class="overflow-hidden rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm">
                    <table class="w-full text-sm">
                        <thead
                            class="bg-gray-50 dark:bg-gray-900/50 text-gray-500 dark:text-gray-400 border-b border-gray-200 dark:border-gray-700">
                            <tr>
                                <th class="px-4 py-3 text-center font-medium bg-gray-50 dark:bg-gray-800/50 w-12">#</th>
                                <th class="px-4 py-3 text-center font-medium">{{ $t('inventory.parts.part_number') }}
                                </th>
                                <th class="px-4 py-3 text-center font-medium">{{ $t('inventory.parts.unit_price') }}</th>
                                <th class="px-4 py-3 text-center font-medium">{{ $t('common.discount') }}</th>
                                <th class="px-4 py-3 text-center font-medium">{{ $t('common.quantity') }}</th>
                                <th class="px-4 py-3 text-center font-medium">{{ $t('common.amount') }}</th>
                                <th class="px-4 py-3 text-center font-medium w-20">{{ $t('common.actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            <tr v-for="(item, index) in form.items" :key="index"
                                class="group hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                                <td class="px-4 py-3 text-center text-gray-400 font-mono" dir="ltr" lang="en">{{ index + 1 }}</td>
                                <td class="px-4 py-3 text-center">
                                    <div class="font-medium text-gray-900 dark:text-white">{{ item.part?.name_ar }}
                                    </div>
                                    <div class="text-xs text-gray-500 font-mono mt-0.5">{{ item.part?.sku }}</div>
                                </td>
                                <td class="px-4 py-3 text-center font-mono text-gray-600 dark:text-gray-300" dir="ltr" lang="en" style="font-family: 'Inter', system-ui, sans-serif !important;">
                                    {{ formatCurrency(item.unit_cost) }}
                                </td>
                                <td class="px-4 py-3 text-center font-mono text-gray-600 dark:text-gray-300" dir="ltr" lang="en" style="font-family: 'Inter', system-ui, sans-serif !important;">
                                    {{ item.discount > 0 ? formatCurrency(item.discount) : '-' }}
                                </td>
                                <td class="px-4 py-3 text-center" dir="ltr" lang="en" style="font-family: 'Inter', system-ui, sans-serif !important;">
                                    <span
                                        class="inline-flex items-center justify-center px-2 py-1 rounded-md bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 font-mono font-bold text-xs">
                                        {{ toEnglish(item.qty) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-center font-mono font-bold text-gray-900 dark:text-white" dir="ltr" lang="en" style="font-family: 'Inter', system-ui, sans-serif !important;">
                                    {{ formatCurrency(item.total) }}
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <div
                                        class="flex items-center justify-center gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                        <button type="button" @click="openItemModal(item, index)"
                                            class="p-1 text-gray-400 hover:text-blue-600 dark:hover:text-blue-400 transition-colors">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                            </svg>
                                        </button>
                                        <button type="button" @click="removeItem(index)"
                                            class="p-1 text-gray-400 hover:text-red-600 dark:hover:text-red-400 transition-colors">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="form.items.length === 0">
                                <td colspan="7" class="px-4 py-12 text-center">
                                    <div
                                        class="flex flex-col items-center justify-center text-gray-400 dark:text-gray-500">
                                        <svg class="w-12 h-12 mb-3 opacity-20" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                        </svg>
                                        <p class="text-base font-medium">{{ $t('purchasing.orders.no_items') }}</p>
                                        <p class="text-sm mt-1">{{ $t('inventory.parts.search_label') }}</p>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Totals Summary Card -->
                <div v-if="form.items.length > 0" class="flex justify-end">
                    <div
                        class="bg-gray-50 dark:bg-gray-900/50 rounded-xl p-4 w-full sm:w-1/3 border border-gray-200 dark:border-gray-700 space-y-2">
                        <div class="flex justify-between items-center text-sm text-gray-600 dark:text-gray-400">
                            <span>{{ $t('common.total') }}</span>
                            <span class="font-mono" dir="ltr" lang="en">{{ formatCurrency(itemsTotal) }}</span>
                        </div>
                        <div v-if="form.tax_included"
                            class="flex justify-between items-center text-sm text-gray-500 dark:text-gray-500 text-xs">
                            <span>{{ $t('common.vat_included') }}</span>
                            <span class="font-mono" dir="ltr" lang="en">{{ formatCurrency(itemsTotal * 0.15) }}</span>
                            <!-- Simplified tax calc display -->
                        </div>
                    </div>
                </div>
            </div>

            <!-- Payments Section -->
            <div class="space-y-4 pt-4 border-t border-gray-100 dark:border-gray-800">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-green-50 dark:bg-green-900/20 flex items-center justify-center">
                            <span class="text-xl">💰</span>
                        </div>
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white">{{ $t('payments.title') }}</h3>
                    </div>
                    <button type="button" @click.stop="openPaymentModal()" :disabled="form.items.length === 0"
                        class="flex items-center gap-2 px-4 py-2 bg-gradient-to-r from-green-600 to-emerald-600 text-white rounded-xl hover:from-green-700 hover:to-emerald-700 transition-all shadow-lg shadow-green-500/20 text-sm font-bold active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        {{ $t('payments.add_payment') }}
                    </button>
                </div>

                <div v-if="form.payments.length > 0"
                    class="overflow-hidden rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm">
                    <table class="w-full text-sm">
                        <thead
                            class="bg-gray-50 dark:bg-gray-900/50 text-gray-500 dark:text-gray-400 border-b border-gray-200 dark:border-gray-700">
                            <tr>
                                <th class="px-4 py-3 text-center w-12 text-xs uppercase tracking-widest font-black">#</th>
                                <th class="px-4 py-3 text-center text-xs uppercase tracking-widest font-black">{{ $t('payments.form.method') }}</th>
                                <th class="px-4 py-3 text-center text-xs uppercase tracking-widest font-black">{{ $t('payments.form.date') }}</th>
                                <th class="px-4 py-3 text-center text-xs uppercase tracking-widest font-black">{{ $t('payments.form.amount') }}</th>
                                <th class="px-4 py-3 text-center">{{ $t('common.actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            <tr v-for="(payment, index) in form.payments" :key="index"
                                class="bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors group">
                                <td class="px-4 py-3 text-center text-gray-400 font-mono" dir="ltr" lang="en">{{ index + 1 }}</td>
                                <td class="px-4 py-3 text-center">
                                    <span class="inline-flex px-2.5 py-1 rounded-lg text-[10px] font-black uppercase tracking-widest bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 border border-blue-100/50 dark:border-blue-800/30">
                                        {{ $t(`payments.methods.${payment.payment_method}`) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-center text-gray-600 dark:text-gray-400 font-mono" dir="ltr" lang="en">
                                    {{ payment.payment_date }}
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <span class="font-mono font-bold text-gray-900 dark:text-white" dir="ltr" lang="en">
                                        {{ formatCurrency(payment.amount) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <div class="flex items-center justify-center gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                        <button type="button" @click="openPaymentModal(payment, index)"
                                            class="p-1 text-gray-400 hover:text-blue-600 dark:hover:text-blue-400 transition-colors">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                            </svg>
                                        </button>
                                        <button type="button" @click="removePayment(index)"
                                            class="p-1 text-gray-400 hover:text-red-600 dark:hover:text-red-400 transition-colors">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div v-else class="text-center py-8 bg-gray-50/50 dark:bg-gray-900/30 rounded-2xl border border-dashed border-gray-200 dark:border-gray-700">
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ $t('payments.no_payments') }}</p>
                </div>
            </div>
                <!-- Final Balance -->
                <div class="flex justify-end pt-2">
                    <div
                        class="bg-blue-50 dark:bg-blue-900/20 px-6 py-4 rounded-xl border border-blue-100 dark:border-blue-800 text-end">
                        <span
                            class="block text-sm text-blue-600 dark:text-blue-400 mb-1 font-medium uppercase tracking-wide">{{
                                $t('purchasing.orders.balance') }}</span>
                        <span class="text-3xl font-bold"
                            :class="{ 'text-green-600 dark:text-green-400': remainingBalance === 0, 'text-red-500 dark:text-red-400': remainingBalance > 0 }"
                            dir="ltr" lang="en">
                            {{ formatCurrency(remainingBalance) }}
                        </span>
                    </div>
                </div>


            <!-- Credit Invoice Toggle (Conditional) -->
            <div v-if="remainingBalance > 0" class="space-y-4 pt-4 border-t border-gray-100 dark:border-gray-800">
                <div class="flex items-center justify-between bg-blue-50/50 dark:bg-blue-900/10 p-4 rounded-2xl border border-blue-100 dark:border-blue-900/30">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-blue-100 dark:bg-blue-900/40 flex items-center justify-center text-blue-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-base font-bold text-gray-900 dark:text-white">{{ $t('purchasing.orders.create_credit_invoice') }}</h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ $t('purchasing.orders.credit_invoice_hint') || 'Remaining amount will be recorded as a debt' }}</p>
                        </div>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" v-model="form.create_credit_invoice" class="sr-only peer">
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-blue-600"></div>
                    </label>
                </div>

                <!-- Due Date (Shown when credit invoice is enabled) -->
                <div v-if="form.create_credit_invoice" class="grid grid-cols-1 md:grid-cols-2 gap-4 bg-white dark:bg-gray-800 p-4 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm animate-in fade-in slide-in-from-top-2 duration-300">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                            {{ $t('purchasing.orders.due_date') }} <span class="text-red-500">*</span>
                        </label>
                        <CustomDatePicker v-model="form.due_date" :placeholder="$t('purchasing.orders.due_date')" />
                    </div>
                </div>
            </div>

            <!-- Notes -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    {{ $t('common.notes') }}
                </label>
                <textarea v-model="form.notes" rows="3"
                    class="w-full px-4 py-3 border border-gray-200 dark:border-gray-700 rounded-xl bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500"></textarea>
            </div>

        </form>


        <template #footer>
            <div class="flex items-center justify-between w-full">
                <div class="flex items-center gap-2">
                    <p v-if="!isFormValid" class="text-xs text-amber-600 dark:text-amber-400 font-bold bg-amber-50 dark:bg-amber-900/20 px-3 py-1.5 rounded-lg border border-amber-100 dark:border-amber-800/30">
                        ⚠️ {{ $t('common.please_complete_required_fields') }}
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <button type="button" @click="$emit('close')"
                        class="px-5 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-xl transition-colors font-medium">
                        {{ $t('common.cancel') }}
                    </button>
                    <button type="button" @click="submit" :disabled="!isFormValid || form.processing || submitting"
                        class="px-8 py-2.5 bg-gradient-to-r from-indigo-600 to-purple-600 text-white rounded-xl hover:from-indigo-700 hover:to-purple-700 disabled:opacity-40 disabled:cursor-not-allowed transition-all shadow-lg shadow-indigo-500/30 font-black uppercase tracking-widest text-xs flex items-center gap-2">
                        <svg v-if="form.processing || submitting" class="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        {{ form.processing || submitting ? $t('common.loading') : $t('common.save') }}
                    </button>
                </div>
            </div>
        </template>
    </BaseModal>

    <!-- Item Modal -->
    <PurchaseOrderItemModal v-if="showItemModal" :show="showItemModal" :item="editingItem" :units="units" :tax-included="form.tax_included" :tenant-tax-settings="tenantTaxSettings" @close="closeItemModal"
        @saved="onItemSaved" @update:taxIncluded="form.tax_included = $event" />

    <!-- Supplier Modal -->
    <SupplierCreateModal v-if="showSupplierModal" :show="showSupplierModal" @close="showSupplierModal = false" @saved="onSupplierSaved" />

    <!-- Payment Modal -->
    <PurchaseOrderPaymentModal v-if="showPaymentModal" :show="showPaymentModal" :payment="editingPayment" :balance="remainingBalance"
        @close="showPaymentModal = false" @saved="onPaymentSaved" />
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { useForm, usePage, router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import BaseModal from '@/Components/BaseModal.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import CustomDatePicker from '@/Components/CustomDatePicker.vue';
import PurchaseOrderItemModal from './PurchaseOrderItemModal.vue';
import SupplierCreateModal from '@/Pages/Purchasing/Suppliers/CreateModal.vue';
import PurchaseOrderPaymentModal from './PurchaseOrderPaymentModal.vue';
import { useNumberFormat } from '@/Composables/useNumberFormat';

const props = defineProps({
    show: Boolean,
    order: Object,
    suppliers: Array,
    warehouses: Array,
    units: Array,
    defaultWarehouse: Object,
});

const tenantTaxSettings = computed(() => {
    return usePage().props.tenant?.tax_settings || {};
});

const emit = defineEmits(['close', 'saved']);
const { t } = useI18n();
const { sanitizeInput, formatCurrency: formatCurrencyEn, toEnglish } = useNumberFormat();

const showItemModal = ref(false);
const showSupplierModal = ref(false);
const showPaymentModal = ref(false);
const editingItem = ref(null);
const editingItemIndex = ref(-1);
const editingPayment = ref(null);
const editingPaymentIndex = ref(-1);
const submitting = ref(false);

const localAddedSuppliers = ref([]);
const allSuppliers = computed(() => {
    return [...props.suppliers, ...localAddedSuppliers.value];
});

const paymentMethods = computed(() => [
    { id: 'cash', name: t('payments.methods.cash') || 'Cash' },
    { id: 'transfer', name: t('payments.methods.transfer') || 'Transfer' },
    { id: 'check', name: t('payments.methods.check') || 'Check' },
]);

const form = useForm({
    warehouse_id: props.order?.warehouse_id || props.defaultWarehouse?.id || '',
    supplier_id: props.order?.supplier_id || '',
    supplier_reference: props.order?.supplier_reference || '',
    order_date: props.order?.order_date || new Date().toISOString().split('T')[0],
    tax_included: false,
    notes: props.order?.notes || '',
    items: [],
    payments: [],
    create_credit_invoice: false,
    due_date: new Date(new Date().setDate(new Date().getDate() + 30)).toISOString().split('T')[0],
});

watch(() => props.show, (newVal) => {
    if (newVal) {
        if (!props.order) {
            form.reset();
            form.warehouse_id = props.defaultWarehouse?.id || (props.warehouses?.length > 0 ? props.warehouses[0].id : '');
            form.order_date = new Date().toISOString().split('T')[0];
            form.items = [];
            form.payments = [];
        } else {
            // Load existing order... (omitted for brevity as we are likely creating mostly)
            form.warehouse_id = props.order.warehouse_id;
            form.supplier_id = props.order.supplier_id;
            form.supplier_reference = props.order.code; // Or whatever field
            form.order_date = props.order.order_date;
            form.items = props.order.items || [];
            // If backend supports payments, load them here
            form.payments = []; // Placeholder
        }
    }
});

// Items Logic
const openItemModal = (item = null, index = -1) => {
    console.log('Opening Item Modal', { item, index });
    editingItem.value = item;
    editingItemIndex.value = index;
    showItemModal.value = true;
};

const closeItemModal = () => {
    showItemModal.value = false;
    editingItem.value = null;
    editingItemIndex.value = -1;
};

const handleModalClose = () => {
    if (showItemModal.value) return;
    emit('close');
};

const onItemSaved = (item) => {
    if (editingItemIndex.value > -1) {
        form.items[editingItemIndex.value] = item;
    } else {
        form.items.push(item);
    }
    closeItemModal();
};

const onSupplierSaved = (supplier) => {
    // Add to local list and select
    if (supplier && supplier.id) {
        localAddedSuppliers.value.push(supplier);
        form.supplier_id = supplier.id;
    }
    showSupplierModal.value = false;
    // Optional: Refresh the full suppliers list from server
    router.reload({ only: ['suppliers'] });
};

const removeItem = (index) => {
    form.items.splice(index, 1);
};

// Payments Logic
const openPaymentModal = (payment = null, index = -1) => {
    console.log('Opening Payment Modal', { payment, index });
    editingPayment.value = payment ? { ...payment } : null;
    editingPaymentIndex.value = index;
    showPaymentModal.value = true;
};

const onPaymentSaved = (payment) => {
    if (editingPaymentIndex.value > -1) {
        form.payments[editingPaymentIndex.value] = payment;
    } else {
        form.payments.push(payment);
    }
    showPaymentModal.value = false;
    editingPayment.value = null;
    editingPaymentIndex.value = -1;
};

const removePayment = (index) => {
    form.payments.splice(index, 1);
};

// Calculations
const itemsTotal = computed(() => {
    return form.items.reduce((sum, item) => sum + (Number(item.total) || 0), 0);
});

const paymentsTotal = computed(() => {
    return form.payments.reduce((sum, p) => sum + (Number(p.amount) || 0), 0);
});

const remainingBalance = computed(() => {
    return Math.max(0, itemsTotal.value - paymentsTotal.value);
});

const formatCurrency = (value) => {
    return formatCurrencyEn(value);
};

const isFormValid = computed(() => {
    const basicFields = form.warehouse_id && 
                       form.supplier_id && 
                       form.supplier_reference && 
                       form.items.length > 0;
    
    if (!basicFields) return false;

    // Validation: Balance must be covered by payments OR a credit invoice
    if (remainingBalance.value > 0.01) {
        return form.payments.length > 0 || form.create_credit_invoice;
    }

    return true;
});

const submit = () => {
    if (submitting.value) return;

    const url = props.order
        ? route('app.purchasing.orders.update', props.order.id)
        : route('app.purchasing.orders.store');

    // Validation: Payment Type
    if (form.payment_type === 'cash') {
        // For cash, we expect the user to add payments covering the total
        // OR the user considers "Cash" to mean "I will pay now" and the backend should handle it.
        // But since we have a payments table, we enforce adding a row.
        if (remainingBalance.value > 0.01) {
            // Show error
            console.error('Validation: Full payment required for Cash orders.');
            // Since we have the error alert, we can manually push an error?
            // Using Inertia'ssetError
            form.setError('payments', t('purchasing.orders.errors.full_payment_required') || 'Full payment is required for Cash orders.');
            return;
        }
    } else {
        // Deferred: Can be 0 or partial.
        // No restriction.
    }

    submitting.value = true;

    const options = {
        onSuccess: () => {
            emit('saved');
            emit('close');
        },
        onError: (errors) => {
            console.error('Validation Errors:', errors);
            submitting.value = false;
        },
        onFinish: () => {
            submitting.value = false;
        }
    };

    // Transform items for backend
    const transformedItems = form.items.map(item => ({
        part_id: item.part_id,
        qty_ordered: item.qty,
        unit_cost: item.unit_cost,
        tax_rate: form.tax_included ? 15 : 0,
        // discount is not supported by backend yet, so we ignore it or handle via notes?
        // For now we just send required fields.
    }));

    if (props.order) {
        form.transform((data) => ({
            ...data,
            items: transformedItems,
        })).put(url, options);
    } else {
        form.transform((data) => ({
            ...data,
            items: transformedItems,
        })).post(url, options);
    }
};
</script>
