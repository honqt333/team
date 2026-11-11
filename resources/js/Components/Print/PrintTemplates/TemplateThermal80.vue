<template>
    <div
        class="bg-white text-gray-900 font-mono p-4 mx-auto relative select-text text-xs leading-normal"
        :class="
            previewMode
                ? 'w-[80mm] shadow-xl border border-gray-200 rounded-lg scale-95 origin-top'
                : 'w-[80mm] max-w-full mx-auto shadow-lg border border-gray-200 print:w-full print:p-0 print:shadow-none print:border-none'
        "
        :dir="isRtl ? 'rtl' : 'ltr'"
    >
        <!-- Center Header -->
        <div
            class="text-center pb-3 border-b transition-all duration-300"
            :class="
                isSleek ? 'border-b-2 border-slate-900' : 'border-b border-dashed border-gray-300'
            "
        >
            <div
                v-if="visualSettings.show_logo && (centerData.logo_url || centerData.logo)"
                class="w-12 h-12 mx-auto mb-1"
            >
                <img
                    :src="centerData.logo_url || centerData.logo"
                    alt="Logo"
                    class="w-full h-full object-contain"
                />
            </div>
            <h2
                class="text-sm font-bold text-gray-900 transition-all duration-300"
                :style="{ color: isSleek ? primaryColor : '#111827' }"
            >
                {{ centerData.name || $t('common.app_name') }}
            </h2>
            <p
                v-if="printHelpers.isTaxEnabled(props.data) && centerData.tax_number"
                class="text-[10px] text-gray-500 mt-0.5"
            >
                {{ $t('company_profile.profile.vat_number') }}: {{ centerData.tax_number }}
            </p>
            <p class="text-[9px] text-gray-400 mt-0.5">{{ centerData.address }}</p>
        </div>

        <!-- Document Details -->
        <div
            class="py-2 border-b border-dashed border-gray-300 text-[10px] text-gray-600 space-y-0.5"
        >
            <div class="flex justify-between items-center">
                <span
                    class="font-bold text-gray-800 transition-all duration-300"
                    :class="
                        isSleek
                            ? 'text-[10px] font-black uppercase tracking-wider px-2 py-0.5 bg-slate-900 text-white rounded'
                            : ''
                    "
                    :style="isSleek ? { backgroundColor: primaryColor } : {}"
                >
                    {{ getDocumentTitle() }}
                </span>
                <span class="font-bold" :style="{ color: primaryColor }">
                    #{{ data.code || 'WO-100293' }}
                </span>
            </div>
            <div class="flex justify-between">
                <span>{{ $t('work_orders.print_view.date') }}:</span>
                <span>{{ printHelpers.formatDate(data.created_at || new Date()) }}</span>
            </div>
            <div
                v-if="data.original_invoice_number"
                class="flex justify-between text-indigo-700 font-bold"
            >
                <span>{{ isRtl ? 'الفاتورة الأصلية' : 'Ref. Invoice' }}:</span>
                <span>#{{ data.original_invoice_number }}</span>
            </div>
            <div v-if="data.return_reason" class="flex justify-between text-gray-500 text-[9px]">
                <span>{{ isRtl ? 'سبب الإرجاع' : 'Reason' }}:</span>
                <span>{{ data.return_reason }}</span>
            </div>
            <div
                v-if="data.expected_end_date"
                class="flex justify-between text-amber-600 font-bold"
            >
                <span>{{ $t('work_orders.form.expected_end_date') }}:</span>
                <span>{{ printHelpers.formatDate(data.expected_end_date) }}</span>
            </div>
            <div
                v-if="data.printed_department"
                class="flex justify-between text-indigo-700 font-semibold"
            >
                <span>{{ $t('work_orders.print_view.department') }}:</span>
                <span>
                    {{
                        isRtl
                            ? data.printed_department.name_ar || data.printed_department.name_en
                            : data.printed_department.name_en || data.printed_department.name_ar
                    }}
                </span>
            </div>
            <div
                v-if="data.printed_technician"
                class="flex justify-between text-indigo-800 font-bold"
            >
                <span>{{ $t('work_orders.print_view.assigned_technician') }}:</span>
                <span>
                    {{
                        isRtl
                            ? data.printed_technician.name_ar || data.printed_technician.name_en
                            : data.printed_technician.name_en || data.printed_technician.name_ar
                    }}
                </span>
            </div>
        </div>

        <!-- Customer & Vehicle Info -->
        <div
            class="py-2 border-b border-dashed border-gray-300 text-[10px] text-gray-600 space-y-0.5"
        >
            <div class="flex justify-between">
                <span>
                    {{
                        ['purchase_invoice', 'purchase_return'].includes(documentType)
                            ? isRtl
                                ? 'المورد'
                                : 'Supplier'
                            : ['payslip', 'employee', 'attendance'].includes(documentType)
                              ? isRtl
                                  ? 'الموظف'
                                  : 'Employee'
                              : ['payroll', 'customers', 'vehicles'].includes(documentType)
                                ? isRtl
                                    ? 'معلومات التقرير'
                                    : 'Report Info'
                                : $t('work_orders.print_view.customer')
                    }}:
                </span>
                <span class="font-bold text-gray-900">
                    {{ data.customer?.name || (isRtl ? 'أحمد عبد الله' : 'Ahmed Abdullah') }}
                </span>
            </div>
            <div
                v-if="documentSettings.show_customer_address !== false && data.customer?.address"
                class="flex justify-between"
            >
                <span>{{ $t('common.address') }}:</span>
                <span class="text-gray-800 text-right">{{ data.customer.address }}</span>
            </div>
            <div
                v-if="printHelpers.isTaxEnabled(props.data) && data.customer?.tax_number"
                class="flex justify-between"
            >
                <span>{{ $t('company_profile.profile.vat_number') }}:</span>
                <span class="text-gray-900 font-mono">{{ data.customer.tax_number }}</span>
            </div>
            <template
                v-if="
                    ![
                        'parts_invoice',
                        'purchase_invoice',
                        'purchase_return',
                        'payslip',
                        'payroll',
                        'attendance',
                        'employee',
                        'customers',
                        'vehicles',
                    ].includes(documentType)
                "
            >
                <div class="flex justify-between items-center">
                    <span>{{ $t('work_orders.print_view.vehicle') }}:</span>
                    <span class="text-gray-800 flex items-center gap-1.5">
                        <img
                            v-if="data.vehicle?.make_logo"
                            :src="data.vehicle.make_logo"
                            alt="Make Logo"
                            class="w-3.5 h-3.5 object-contain"
                        />
                        {{ data.vehicle?.make || (isRtl ? 'تويوتا' : 'Toyota') }}
                    </span>
                </div>
                <div class="flex justify-between items-center">
                    <span>{{ $t('work_orders.print_view.plate') }}:</span>
                    <SaudiPlateDisplay
                        v-if="data.vehicle?.plate"
                        :plate-number="data.vehicle.plate"
                        size="sm"
                    />
                    <span v-else class="font-bold text-gray-900">-</span>
                </div>
                <div v-if="data.vehicle?.color" class="flex justify-between">
                    <span>{{ $t('work_orders.print_view.color') }}:</span>
                    <span class="text-gray-900 font-medium">
                        {{ printHelpers.getLocalizedColor(data.vehicle.color, isRtl) }}
                    </span>
                </div>
                <div class="flex justify-between">
                    <span>{{ $t('work_orders.print_view.odometer') }}:</span>
                    <span>{{ data.odometer || '45,210' }}{{ ' ' + $t('common.km') }}</span>
                </div>
                <div v-if="data.fuel_level" class="flex justify-between">
                    <span>{{ $t('work_orders.print_view.fuel_level') }}:</span>
                    <span>{{ data.fuel_level }}%</span>
                </div>
            </template>
        </div>

        <!-- Content Body -->
        <div class="py-3">
            <!-- Scenario 1: Receipt -->
            <div
                v-if="documentType === 'receipt'"
                class="bg-gray-50/70 p-3 rounded-lg border border-dashed border-gray-200 leading-relaxed text-[10px] text-gray-700"
            >
                <div
                    class="flex justify-between items-center mb-2 pb-1.5 border-b border-dashed border-gray-200"
                >
                    <span class="font-bold text-gray-600">
                        {{ $t('work_orders.print_view.receipt') }}
                    </span>
                    <span class="font-bold text-emerald-600 text-sm" dir="ltr">
                        {{ printHelpers.formatCurrency(data.amount || 250) }}
                    </span>
                </div>
                <p>
                    {{ $t('work_orders.print_view.received_from') }}:
                    <strong>
                        {{ data.customer?.name || (isRtl ? 'أحمد عبد الله' : 'Ahmed Abdullah') }}
                    </strong>
                </p>
                <p class="mt-1">
                    {{ $t('common.amount') }}:
                    <strong>{{ printHelpers.formatCurrency(data.amount || 250) }}</strong>
                </p>
                <p class="mt-1 text-gray-500 italic">
                    {{ $t('common.notes') }}:
                    {{
                        data.notes ||
                        (isRtl
                            ? 'دفعة مقدمة لصيانة السيارة المذكورة أعلاه.'
                            : 'Down payment for maintenance of the above-mentioned vehicle.')
                    }}
                </p>
            </div>

            <!-- Scenario 2: Invoice (Separated Services and Parts) -->
            <div
                v-else-if="
                    [
                        'invoice',
                        'proforma_invoice',
                        'quotation',
                        'parts_invoice',
                        'purchase_invoice',
                        'purchase_return',
                        'credit_note',
                        'subscription_invoice',
                        'delivery_note',
                        'receipt',
                    ].includes(documentType)
                "
                class="space-y-4"
            >
                <!-- Services -->
                <div v-if="services.length > 0" class="space-y-2">
                    <span
                        class="block font-bold text-[9px] text-gray-500 border-b border-gray-100 pb-0.5"
                    >
                        {{
                            ['parts_invoice', 'purchase_invoice', 'purchase_return'].includes(
                                documentType
                            )
                                ? isRtl
                                    ? 'قطع الغيار'
                                    : 'Spare Parts'
                                : $t('work_orders.print_view.labor_services')
                        }}:
                    </span>
                    <div
                        v-for="(item, index) in services"
                        :key="'srv-' + index"
                        class="flex flex-col text-[10px] pb-2 border-b border-gray-50 last:border-0"
                    >
                        <div class="flex justify-between font-bold text-gray-900">
                            <span class="whitespace-pre-wrap">
                                {{ index + 1 }}. {{ item.service_name || item.description }}
                            </span>
                            <span v-if="showPricingColumns" dir="ltr">
                                {{
                                    printHelpers.formatCurrency(
                                        printTotals.lineSubtotalDisplay(item)
                                    )
                                }}
                            </span>
                        </div>
                        <div
                            v-if="item.description && item.service_name"
                            class="text-[9px] text-gray-500 mt-0.5 max-w-[70mm] leading-snug whitespace-pre-wrap"
                        >
                            {{ item.description }}
                        </div>
                        <div
                            v-if="showPricingColumns"
                            class="text-[8px] text-gray-400 mt-0.5 flex justify-between"
                        >
                            <span>
                                {{ $t('work_orders.print_view.calculation') }}:
                                {{ item.qty || 1 }} ×
                                {{ printHelpers.formatCurrency(item.unit_price) }}
                            </span>
                            <span
                                v-if="printTotals.lineDiscountDisplay(item) > 0"
                                class="text-red-500"
                            >
                                {{ $t('common.discount') }}: -{{
                                    printHelpers.formatCurrency(
                                        printTotals.lineDiscountDisplay(item)
                                    )
                                }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Parts -->
                <div v-if="parts.length > 0" class="space-y-2">
                    <span
                        class="block font-bold text-[9px] text-gray-500 border-b border-gray-100 pb-0.5 mt-2"
                    >
                        {{
                            ['parts_invoice', 'purchase_invoice', 'purchase_return'].includes(
                                documentType
                            )
                                ? isRtl
                                    ? 'قطع الغيار'
                                    : 'Spare Parts'
                                : $t('work_orders.parts_total')
                        }}:
                    </span>
                    <div
                        v-for="(item, index) in parts"
                        :key="'part-' + index"
                        class="flex flex-col text-[10px] pb-2 border-b border-gray-50 last:border-0"
                    >
                        <div class="flex justify-between font-bold text-gray-900">
                            <span class="whitespace-pre-wrap">
                                {{ index + 1 }}. {{ item.service_name || item.description }}
                            </span>
                            <span v-if="showPricingColumns" dir="ltr">
                                {{
                                    printHelpers.formatCurrency(
                                        printTotals.lineSubtotalDisplay(item)
                                    )
                                }}
                            </span>
                        </div>
                        <div
                            v-if="item.description && item.service_name"
                            class="text-[9px] text-gray-500 mt-0.5 max-w-[70mm] leading-snug whitespace-pre-wrap"
                        >
                            {{ item.description }}
                        </div>
                        <div
                            v-if="showPricingColumns"
                            class="text-[8px] text-gray-400 mt-0.5 flex justify-between"
                        >
                            <span>
                                {{ $t('work_orders.print_view.calculation') }}:
                                {{ item.qty || 1 }} ×
                                {{ printHelpers.formatCurrency(item.unit_price) }}
                            </span>
                            <span
                                v-if="printTotals.lineDiscountDisplay(item) > 0"
                                class="text-red-500"
                            >
                                {{ $t('common.discount') }}: -{{
                                    printHelpers.formatCurrency(
                                        printTotals.lineDiscountDisplay(item)
                                    )
                                }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Scenario 6: Payments Receipt List -->
            <div v-else-if="documentType === 'payments'" class="space-y-2">
                <span
                    class="block font-bold text-[9px] text-gray-500 border-b border-gray-100 pb-0.5 mt-2"
                >
                    {{ $t('work_orders.print_view.payments') }}:
                </span>
                <div
                    v-for="(payment, index) in data.payments"
                    :key="payment.id"
                    class="flex flex-col text-[10px] pb-2 border-b border-gray-50 last:border-0"
                >
                    <div class="flex justify-between font-bold text-gray-900">
                        <span>
                            {{ index + 1 }}.
                            {{ printHelpers.getMethodLabel(payment.payment_method) }}
                        </span>
                        <span dir="ltr">{{ printHelpers.formatCurrency(payment.amount) }}</span>
                    </div>
                    <div class="text-[8px] text-gray-400 mt-0.5 flex justify-between">
                        <span>{{ printHelpers.formatDate(payment.payment_date) }}</span>
                        <span v-if="payment.reference">
                            {{ $t('work_orders.print_view.reference') }}: {{ payment.reference }}
                        </span>
                    </div>
                    <div v-if="payment.notes" class="text-[8px] text-gray-500 mt-0.5 font-medium">
                        {{ $t('common.details') }}: {{ payment.notes }}
                    </div>
                </div>
                <div
                    v-if="!data.payments || data.payments.length === 0"
                    class="text-center py-4 text-gray-400 text-[10px]"
                >
                    {{ $t('work_orders.print_view.no_payments') }}
                </div>
            </div>

            <!-- Scenario 3: Vehicle Condition Report -->
            <div v-else-if="documentType === 'condition_report'" class="space-y-4">
                <!-- Simple list of damage marks for thermal print -->
                <div v-if="damageMarks.length > 0" class="space-y-2">
                    <span
                        class="block font-bold text-[9px] text-gray-500 border-b border-gray-100 pb-0.5"
                    >
                        {{ $t('work_orders.print_view.vehicle_condition') }}:
                    </span>
                    <div
                        v-for="(mark, index) in damageMarks"
                        :key="index"
                        class="flex items-center gap-2 text-[10px] pb-1.5 border-b border-gray-50 last:border-0"
                    >
                        <span
                            class="w-4 h-4 rounded-full flex items-center justify-center text-[8px] font-bold text-white flex-shrink-0"
                            :style="{ backgroundColor: printHelpers.getColorValue(mark.color) }"
                        >
                            {{ index + 1 }}
                        </span>
                        <span class="text-gray-800 font-medium">
                            {{ mark.description || $t('work_orders.print_view.na') }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Scenario 5: Standard Item List (work order, etc.) -->
            <div v-else class="space-y-3">
                <div
                    v-for="(item, index) in filteredItems"
                    :key="index"
                    class="flex flex-col text-[10px] pb-2 border-b border-gray-50 last:border-0"
                >
                    <div class="flex justify-between font-bold text-gray-900">
                        <span class="whitespace-pre-wrap">
                            {{ index + 1 }}. {{ item.service_name || item.description }}
                        </span>
                        <span v-if="showPricingColumns" dir="ltr">
                            {{ printHelpers.formatCurrency(printTotals.lineSubtotalDisplay(item)) }}
                        </span>
                    </div>
                    <div
                        v-if="item.description && item.service_name"
                        class="text-[9px] text-gray-500 mt-0.5 max-w-[70mm] leading-snug"
                    >
                        {{ item.description }}
                    </div>
                    <div
                        v-if="showPricingColumns"
                        class="text-[8px] text-gray-400 mt-0.5 flex justify-between"
                    >
                        <span>
                            {{ $t('work_orders.print_view.calculation') }}: {{ item.qty || 1 }} ×
                            {{ printHelpers.formatCurrency(item.unit_price) }}
                        </span>
                        <span v-if="printTotals.lineDiscountDisplay(item) > 0" class="text-red-500">
                            {{ $t('common.discount') }}: -{{
                                printHelpers.formatCurrency(printTotals.lineDiscountDisplay(item))
                            }}
                        </span>
                    </div>
                    <div
                        v-if="item.technician"
                        class="text-[8px] text-indigo-600 font-semibold mt-0.5"
                    >
                        {{ $t('work_orders.print_view.technician') }}: {{ item.technician }}
                    </div>
                </div>
            </div>
        </div>

        <!-- Financial Summary -->
        <div
            v-if="showPricingColumns && documentType !== 'receipt' && documentType !== 'payments'"
            class="py-2 border-t border-dashed border-gray-300 space-y-1 text-[10px]"
        >
            <div v-if="documentType !== 'payments'" class="flex justify-between text-gray-500">
                <span>{{ $t('quotes.form.subtotal') }}:</span>
                <span dir="ltr">{{ printHelpers.formatCurrency(totals.subtotal) }}</span>
            </div>
            <div
                v-if="documentType !== 'payments' && totals.discount > 0"
                class="flex justify-between text-red-500"
            >
                <span>{{ $t('quotes.form.total_discount') }}:</span>
                <span dir="ltr">-{{ printHelpers.formatCurrency(totals.discount) }}</span>
            </div>
            <div
                v-if="documentType !== 'payments' && totals.vat > 0"
                class="flex justify-between text-gray-500"
            >
                <span>{{ $t('common.vat') }}:</span>
                <span dir="ltr">{{ printHelpers.formatCurrency(totals.vat) }}</span>
            </div>
            <div
                class="flex justify-between font-bold text-sm text-gray-900 border-t border-dashed border-gray-200 pt-1.5"
            >
                <span>
                    {{
                        documentType === 'payments'
                            ? isRtl
                                ? 'إجمالي الفاتورة:'
                                : 'Invoice Total:'
                            : isRtl
                              ? 'الإجمالي النهائي:'
                              : 'Total Amount:'
                    }}
                </span>
                <span :style="{ color: primaryColor }" dir="ltr">
                    {{ printHelpers.formatCurrency(totals.total) }}
                </span>
            </div>
            <div
                v-if="!['quotation', 'proforma_invoice'].includes(documentType)"
                class="flex justify-between text-gray-500 text-[10px]"
            >
                <span>{{ isRtl ? 'المبلغ المدفوع:' : 'Paid Amount:' }}</span>
                <span dir="ltr">
                    {{ printHelpers.formatCurrency(totals.paid - (data.bad_debt || 0)) }}
                </span>
            </div>
            <div
                v-if="
                    !['quotation', 'proforma_invoice'].includes(documentType) &&
                    (data.bad_debt || 0) > 0
                "
                class="flex justify-between text-amber-600 text-[10px]"
            >
                <span>{{ isRtl ? 'الديون المعدومة:' : 'Bad Debt:' }}</span>
                <span dir="ltr">{{ printHelpers.formatCurrency(data.bad_debt) }}</span>
            </div>
            <div
                v-if="!['quotation', 'proforma_invoice'].includes(documentType)"
                class="flex justify-between font-bold text-gray-800 text-[10px] bg-gray-50/50 p-1 rounded"
            >
                <span>{{ isRtl ? 'الباقي:' : 'Remaining:' }}</span>
                <span dir="ltr">{{ printHelpers.formatCurrency(totals.balance) }}</span>
            </div>
            <div
                v-if="centerData.iban && documentSettings.show_iban"
                class="bg-gray-50/70 p-2 rounded border border-dashed border-gray-200 mt-2 text-[8px] text-gray-500 leading-normal"
            >
                <span class="block font-bold text-gray-600">
                    {{ $t('company_profile.profile.iban') }}:
                </span>
                {{ centerData.iban }}
            </div>
        </div>

        <!-- Terms and Conditions -->
        <div
            v-if="
                documentSettings.print_terms &&
                (documentSettings.terms?.length > 0 || (previewMode && dummyTerms.length > 0))
            "
            class="py-2 border-t border-dashed border-gray-300"
        >
            <span class="block font-bold text-[9px] text-gray-500 mb-1">
                {{ $t('work_orders.print_view.terms_conditions') }}:
            </span>
            <ul class="text-[8px] text-gray-400 space-y-0.5 list-disc list-inside leading-tight">
                <li
                    v-for="(term, idx) in documentSettings.terms?.length > 0
                        ? documentSettings.terms
                        : dummyTerms"
                    :key="idx"
                >
                    {{ isRtl ? term.text_ar || term : term.text_en || term.text_ar || term }}
                </li>
            </ul>
        </div>

        <!-- Signatures & Stamp -->
        <div class="py-4 border-t border-dashed border-gray-300 relative min-h-[25mm]">
            <!-- Dynamic Signatures Columns -->
            <div
                class="grid gap-4 items-center justify-center text-center"
                :class="printHelpers.getSignatureGridClass(visibleSignatures.length || 2)"
            >
                <div
                    v-for="(sig, index) in visibleSignatures"
                    :key="index"
                    class="text-center flex flex-col items-center justify-end"
                >
                    <div v-if="sig.url" class="h-10 flex items-center justify-center mb-1">
                        <img
                            :src="printHelpers.formatSignatureUrl(sig.url)"
                            class="max-h-full max-w-[80px] object-contain"
                        />
                    </div>
                    <div
                        v-else-if="
                            printHelpers.isClientSignature(
                                isRtl ? sig.name_ar : sig.name_en || sig.name_ar
                            ) &&
                            (data.signature_url ||
                                data.customer_signature ||
                                data.reception_signature ||
                                data.delivery_signature)
                        "
                        class="h-10 flex items-center justify-center mb-1"
                    >
                        <img
                            :src="
                                printHelpers.formatSignatureUrl(
                                    data.signature_url ||
                                        data.customer_signature ||
                                        data.reception_signature ||
                                        data.delivery_signature
                                )
                            "
                            class="max-h-full max-w-[80px] object-contain"
                        />
                    </div>
                    <div v-else class="border-b border-gray-300 w-24 mx-auto mb-1 h-6"></div>
                    <span class="text-[8px] text-gray-500 font-bold">
                        {{ isRtl ? sig.name_ar : sig.name_en || sig.name_ar }}
                    </span>
                </div>
            </div>

            <!-- Thermal Small Stamp -->
            <div
                v-if="
                    showStamp && (previewMode || centerData.stamp_url || visualSettings.stamp_url)
                "
                class="absolute bottom-2 left-4 w-16 h-16 select-none pointer-events-none z-10 opacity-70 -rotate-12 transition-all"
            >
                <img
                    v-if="centerData.stamp_url || visualSettings.stamp_url"
                    :src="centerData.stamp_url || visualSettings.stamp_url"
                    class="w-full h-full object-contain"
                />
                <svg
                    v-else-if="previewMode"
                    class="w-full h-full text-emerald-600/80 print:hidden"
                    fill="none"
                    viewBox="0 0 100 100"
                    stroke="currentColor"
                >
                    <circle cx="50" cy="50" r="45" stroke-width="2.5" stroke-dasharray="3 3" />
                    <circle cx="50" cy="50" r="38" stroke-width="1.5" />
                    <text
                        x="50"
                        y="38"
                        font-size="6"
                        font-weight="bold"
                        fill="currentColor"
                        text-anchor="middle"
                        font-family="sans-serif"
                    >
                        {{ visualSettings.footer_text || $t('common.app_name') }}
                    </text>
                    <text
                        x="50"
                        y="48"
                        font-size="7"
                        font-weight="black"
                        fill="currentColor"
                        text-anchor="middle"
                        font-family="sans-serif"
                    >
                        APPROVED
                    </text>
                    <text
                        x="50"
                        y="58"
                        font-size="7"
                        font-weight="black"
                        fill="currentColor"
                        text-anchor="middle"
                        font-family="sans-serif"
                    >
                        مـعـتـمـد
                    </text>
                    <path d="M25 68 Q50 78 75 68" stroke-width="1.5" />
                    <text
                        x="50"
                        y="76"
                        font-size="5"
                        fill="currentColor"
                        text-anchor="middle"
                        font-family="sans-serif"
                    >
                        KHIDMA PRO
                    </text>
                </svg>
            </div>
        </div>

        <!-- ZATCA / Standard QR code for Thermal print -->
        <div
            v-if="showQrCode && data.qr_code_url"
            class="flex flex-col items-center justify-center mt-3 pt-3 border-t border-dashed border-gray-200"
        >
            <img
                :src="data.qr_code_url"
                alt="QR"
                class="w-24 h-24 p-1 bg-white border border-gray-200 rounded"
            />
        </div>

        <!-- Footer Text -->
        <div
            v-if="visualSettings.footer_text"
            class="text-center text-[8px] text-gray-400 mt-2 border-t border-dashed border-gray-200 pt-2 leading-tight"
        >
            {{ visualSettings.footer_text }}
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { usePage } from '@inertiajs/vue3';
import SaudiPlateDisplay from '@/Components/Vehicles/SaudiPlateDisplay.vue';
import { usePrint } from '@/Composables/usePrint';
import { usePrintTotals } from '@/Composables/usePrintTotals';

const page = usePage();
const { t, locale } = useI18n();
const isRtl = computed(() => locale.value === 'ar');

const props = defineProps({
    documentType: {
        type: String,
        default: 'invoice',
    },
    data: {
        type: Object,
        default: () => ({}),
    },
    centerData: {
        type: Object,
        default: () => ({}),
    },
    documentSettings: {
        type: Object,
        default: () => ({}),
    },
    visualSettings: {
        type: Object,
        default: () => ({}),
    },
    previewMode: {
        type: Boolean,
        default: false,
    },
});

// Use shared print composables
const printHelpers = usePrint();
const printTotals = usePrintTotals();

// Destructure helpers for use in template
const {
    formatCurrency,
    formatDate,
    getMethodLabel,
    isTaxEnabled: isTaxEnabledHelper,
    getLocalizedColor,
    formatSignatureUrl,
    isClientSignature,
    getColorValue,
    getSignatureGridClass,
    getDocTypeTitle,
    cleanDocumentTitle,
    buildPrintSignatures,
} = printHelpers;

const {
    lineDiscountDisplay,
    lineSubtotalDisplay,
    srvLineTotal,
    srvLineVat,
    srvLineExclTax,
    partLineTotal,
    partLineVat,
    partLineExclTax,
    isShowExclusive,
    calculateTotals,
} = printTotals;

// Primary Color
const primaryColor = computed(() => props.visualSettings.primary_color || '#fbbf24');

// Decide if we show pricing columns
const showPricingColumns = computed(() => {
    const hiddenTypes = ['work_order', 'checklist', 'job_card', 'condition_report'];
    return !hiddenTypes.includes(props.documentType);
});

const showExclusive = computed(() => {
    if (props.data.pricing_mode_snapshot !== 'inclusive') {
        return true;
    }
    const taxSettings = page?.props?.tenant?.tax_settings;
    return taxSettings?.show_amount_before_vat ?? true;
});

// Services and Parts computeds for Invoice separation
const filteredItems = computed(() => {
    const items = props.data.items || dummyItems;
    return items.filter((item) => item.status !== 'cancelled');
});

const services = computed(() => {
    if (['parts_invoice', 'purchase_invoice', 'purchase_return'].includes(props.documentType))
        return [];
    return filteredItems.value.filter((item) => !item.is_part);
});

const parts = computed(() => {
    if (['parts_invoice', 'purchase_invoice', 'purchase_return'].includes(props.documentType)) {
        return filteredItems.value;
    }
    return filteredItems.value.filter((item) => item.is_part);
});

const effectiveTemplate = computed(
    () =>
        props.documentSettings?.template ||
        props.visualSettings?.active_template ||
        'TemplateThermal80'
);
const isSleek = computed(() => effectiveTemplate.value === 'TemplateSleekThermal');
const showQrCode = computed(
    () => printHelpers.isTaxEnabled(props.data) && Boolean(props.data?.qr_code_url)
);
const showStamp = computed(
    () => (props.documentSettings?.show_stamp ?? props.visualSettings?.show_stamp ?? true) !== false
);

function getDocumentTitle() {
    const taxActive = printHelpers.isTaxEnabled(props.data);
    let title = isRtl.value
        ? props.documentSettings?.title_ar ||
          printHelpers.getDocTypeTitle(props.documentType, taxActive)
        : props.documentSettings?.title_en ||
          printHelpers.getDocTypeTitle(props.documentType, taxActive);

    title = String(title || '');
    return printHelpers.cleanDocumentTitle(title, taxActive);
}

const damageMarks = computed(() => props.data.damage_marks || []);

// Compute dynamic signature columns

// Map document keys to localized Titles

// Dummy Fallback Data
const dummyItems = [
    {
        service_name: 'فحص كمبيوتر وبرمجة كاملة',
        description: 'فحص الحساسات ومسح الأخطاء',
        qty: 1,
        unit_price: 150,
    },
    {
        service_name: 'تغيير زيت المحرك مع الفلتر',
        description: 'زيت سينثيتك 10,000 كم مع السيفون',
        qty: 1,
        unit_price: 280,
    },
    {
        service_name: 'تغيير فحمات الفرامل الأمامية',
        description: 'خرط الهوبات وضبط الميزانية',
        qty: 1,
        unit_price: 320,
        technician: 'م. سليم',
    },
];

const dummyTerms = [
    'الضمان يسري على الأجزاء والخدمات المستبدلة فقط.',
    'المركز غير مسؤول عن فقدان أي مقتنيات شخصية تترك داخل السيارة.',
];

const defaultSignatures = [
    { name_ar: 'المستلم', name_en: 'Recipient', show: true },
    { name_ar: 'العميل', name_en: 'Customer', show: true },
];

// Only render signatures with show !== false
const visibleSignatures = computed(() => {
    const sigs =
        props.documentSettings?.signatures?.length > 0
            ? props.documentSettings.signatures
            : defaultSignatures;
    return sigs.filter((s) => s.show !== false);
});

// Computed Totals - using shared composable
const totals = computed(() => {
    return printTotals.calculateTotals(
        (props.data.items || dummyItems).filter((item) => item.status !== 'cancelled'),
        props.data
    );
});
</script>
