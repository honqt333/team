<template>
    <div 
        class="bg-white text-gray-900 font-mono p-4 mx-auto relative select-text text-xs leading-normal"
        :class="previewMode ? 'w-[80mm] shadow-xl border border-gray-200 rounded-lg scale-95 origin-top' : 'w-full print:p-0 print:shadow-none print:border-none'"
        :dir="isRtl ? 'rtl' : 'ltr'"
    >
        <!-- Center Header -->
        <div class="text-center pb-3 border-b transition-all duration-300" :class="isSleek ? 'border-b-2 border-slate-900' : 'border-b border-dashed border-gray-300'">
            <div v-if="visualSettings.show_logo && (centerData.logo_url || centerData.logo)" class="w-12 h-12 mx-auto mb-1">
                <img :src="centerData.logo_url || centerData.logo" alt="Logo" class="w-full h-full object-contain" />
            </div>
            <h2 class="text-sm font-bold text-gray-900 transition-all duration-300" :style="{ color: isSleek ? primaryColor : '#111827' }">{{ centerData.name || 'مركز خدمة برو' }}</h2>
            <p v-if="isTaxEnabled() && centerData.tax_number" class="text-[10px] text-gray-500 mt-0.5">{{ $t('company_profile.profile.vat_number') }}: {{ centerData.tax_number }}</p>
            <p class="text-[9px] text-gray-400 mt-0.5">{{ centerData.address }}</p>
        </div>

        <!-- Document Details -->
        <div class="py-2 border-b border-dashed border-gray-300 text-[10px] text-gray-600 space-y-0.5">
            <div class="flex justify-between items-center">
                <span class="font-bold text-gray-800 transition-all duration-300" :class="isSleek ? 'text-[10px] font-black uppercase tracking-wider px-2 py-0.5 bg-slate-900 text-white rounded' : ''" :style="isSleek ? { backgroundColor: primaryColor } : {}">
                    {{ getDocumentTitle() }}
                </span>
                <span class="font-bold" :style="{ color: primaryColor }">#{{ data.code || 'WO-100293' }}</span>
            </div>
            <div class="flex justify-between">
                <span>{{ $t('work_orders.print_view.date') }}:</span>
                <span>{{ formatDate(data.created_at || new Date()) }}</span>
            </div>
            <div v-if="data.expected_end_date" class="flex justify-between text-amber-600 font-bold">
                <span>{{ $t('work_orders.form.expected_end_date') }}:</span>
                <span>{{ formatDate(data.expected_end_date) }}</span>
            </div>
        </div>

        <!-- Customer & Vehicle Info -->
        <div class="py-2 border-b border-dashed border-gray-300 text-[10px] text-gray-600 space-y-0.5">
            <div class="flex justify-between">
                <span>{{ ['purchase_invoice', 'purchase_return'].includes(documentType) ? (isRtl ? 'المورد' : 'Supplier') : $t('work_orders.print_view.customer') }}:</span>
                <span class="font-bold text-gray-900">{{ data.customer?.name || (isRtl ? 'أحمد عبد الله' : 'Ahmed Abdullah') }}</span>
            </div>
            <div v-if="documentSettings.show_customer_address !== false && data.customer?.address" class="flex justify-between">
                <span>{{ $t('common.address') }}:</span>
                <span class="text-gray-800 text-right">{{ data.customer.address }}</span>
            </div>
            <div v-if="isTaxEnabled() && data.customer?.tax_number" class="flex justify-between">
                <span>{{ $t('company_profile.profile.vat_number') }}:</span>
                <span class="text-gray-900 font-mono">{{ data.customer.tax_number }}</span>
            </div>
            <template v-if="!['parts_invoice', 'purchase_invoice', 'purchase_return'].includes(documentType)">
                <div class="flex justify-between items-center">
                    <span>{{ $t('work_orders.print_view.vehicle') }}:</span>
                    <span class="text-gray-800 flex items-center gap-1.5">
                        <img v-if="data.vehicle?.make_logo" :src="data.vehicle.make_logo" alt="Make Logo" class="w-3.5 h-3.5 object-contain" />
                        {{ data.vehicle?.make || (isRtl ? 'تويوتا' : 'Toyota') }}
                    </span>
                </div>
                <div class="flex justify-between items-center">
                    <span>{{ $t('work_orders.print_view.plate') }}:</span>
                    <SaudiPlateDisplay v-if="data.vehicle?.plate" :plate-number="data.vehicle.plate" size="sm" />
                    <span v-else class="font-bold text-gray-900">-</span>
                </div>
                <div v-if="data.vehicle?.color" class="flex justify-between">
                    <span>{{ $t('work_orders.print_view.color') }}:</span>
                    <span class="text-gray-900 font-medium">{{ getLocalizedColor(data.vehicle.color, isRtl) }}</span>
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
            <div v-if="documentType === 'receipt'" class="bg-gray-50/70 p-3 rounded-lg border border-dashed border-gray-200 leading-relaxed text-[10px] text-gray-700">
                <div class="flex justify-between items-center mb-2 pb-1.5 border-b border-dashed border-gray-200">
                    <span class="font-bold text-gray-600">{{ $t('work_orders.print_view.receipt') }}</span>
                    <span class="font-bold text-emerald-600 text-sm" dir="ltr">{{ formatCurrency(data.amount || 250) }}</span>
                </div>
                <p>{{ $t('work_orders.print_view.received_from') }}: <strong>{{ data.customer?.name || (isRtl ? 'أحمد عبد الله' : 'Ahmed Abdullah') }}</strong></p>
                <p class="mt-1">{{ $t('common.amount') }}: <strong>{{ formatCurrency(data.amount || 250) }}</strong></p>
                <p class="mt-1 text-gray-500 italic">{{ $t('common.notes') }}: {{ data.notes || (isRtl ? 'دفعة مقدمة لصيانة السيارة المذكورة أعلاه.' : 'Down payment for maintenance of the above-mentioned vehicle.') }}</p>
            </div>

            <!-- Scenario 2: Invoice (Separated Services and Parts) -->
            <div v-else-if="['invoice', 'proforma_invoice', 'quotation', 'parts_invoice', 'purchase_invoice', 'purchase_return'].includes(documentType)" class="space-y-4">
                <!-- Services -->
                <div v-if="services.length > 0" class="space-y-2">
                    <span class="block font-bold text-[9px] text-gray-500 border-b border-gray-100 pb-0.5">
                        {{ ['parts_invoice', 'purchase_invoice', 'purchase_return'].includes(documentType) ? (isRtl ? 'قطع الغيار' : 'Spare Parts') : $t('work_orders.print_view.labor_services') }}:
                    </span>
                    <div 
                        v-for="(item, index) in services" 
                        :key="'srv-' + index" 
                        class="flex flex-col text-[10px] pb-2 border-b border-gray-50 last:border-0"
                    >
                        <div class="flex justify-between font-bold text-gray-900">
                            <span class="whitespace-pre-wrap">{{ index + 1 }}. {{ item.service_name || item.description }}</span>
                            <span v-if="showPricingColumns" dir="ltr">{{ formatCurrency(lineSubtotalDisplay(item)) }}</span>
                        </div>
                        <div v-if="item.description && item.service_name" class="text-[9px] text-gray-500 mt-0.5 max-w-[70mm] leading-snug whitespace-pre-wrap">
                             {{ item.description }}
                        </div>
                        <div v-if="showPricingColumns" class="text-[8px] text-gray-400 mt-0.5 flex justify-between">
                            <span>{{ $t('work_orders.print_view.calculation') }}: {{ item.qty || 1 }} × {{ formatCurrency(item.unit_price) }}</span>
                            <span v-if="lineDiscountDisplay(item) > 0" class="text-red-500">{{ $t('common.discount') }}: -{{ formatCurrency(lineDiscountDisplay(item)) }}</span>
                        </div>
                    </div>
                </div>

                <!-- Parts -->
                <div v-if="parts.length > 0" class="space-y-2">
                    <span class="block font-bold text-[9px] text-gray-500 border-b border-gray-100 pb-0.5 mt-2">
                        {{ ['parts_invoice', 'purchase_invoice', 'purchase_return'].includes(documentType) ? (isRtl ? 'قطع الغيار' : 'Spare Parts') : $t('work_orders.parts_total') }}:
                    </span>
                    <div 
                        v-for="(item, index) in parts" 
                        :key="'part-' + index" 
                        class="flex flex-col text-[10px] pb-2 border-b border-gray-50 last:border-0"
                    >
                        <div class="flex justify-between font-bold text-gray-900">
                            <span class="whitespace-pre-wrap">{{ index + 1 }}. {{ item.service_name || item.description }}</span>
                            <span v-if="showPricingColumns" dir="ltr">{{ formatCurrency(lineSubtotalDisplay(item)) }}</span>
                        </div>
                        <div v-if="item.description && item.service_name" class="text-[9px] text-gray-500 mt-0.5 max-w-[70mm] leading-snug whitespace-pre-wrap">
                             {{ item.description }}
                        </div>
                        <div v-if="showPricingColumns" class="text-[8px] text-gray-400 mt-0.5 flex justify-between">
                            <span>{{ $t('work_orders.print_view.calculation') }}: {{ item.qty || 1 }} × {{ formatCurrency(item.unit_price) }}</span>
                            <span v-if="lineDiscountDisplay(item) > 0" class="text-red-500">{{ $t('common.discount') }}: -{{ formatCurrency(lineDiscountDisplay(item)) }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Scenario 6: Payments Receipt List -->
            <div v-else-if="documentType === 'payments'" class="space-y-2">
                <span class="block font-bold text-[9px] text-gray-500 border-b border-gray-100 pb-0.5 mt-2">
                    {{ $t('work_orders.print_view.payments') }}:
                </span>
                <div 
                    v-for="(payment, index) in data.payments" 
                    :key="payment.id" 
                    class="flex flex-col text-[10px] pb-2 border-b border-gray-50 last:border-0"
                >
                    <div class="flex justify-between font-bold text-gray-900">
                        <span>{{ index + 1 }}. {{ getMethodLabel(payment.payment_method) }}</span>
                        <span dir="ltr">{{ formatCurrency(payment.amount) }}</span>
                    </div>
                    <div class="text-[8px] text-gray-400 mt-0.5 flex justify-between">
                        <span>{{ formatDate(payment.payment_date) }}</span>
                        <span v-if="payment.reference">{{ $t('work_orders.print_view.reference') }}: {{ payment.reference }}</span>
                    </div>
                    <div v-if="payment.notes" class="text-[8px] text-gray-500 mt-0.5 font-medium">
                        {{ $t('common.details') }}: {{ payment.notes }}
                    </div>
                </div>
                <div v-if="!data.payments || data.payments.length === 0" class="text-center py-4 text-gray-400 text-[10px]">
                    {{ $t('work_orders.print_view.no_payments') }}
                </div>
            </div>

            <!-- Scenario 3: Vehicle Condition Report -->
            <div v-else-if="documentType === 'condition_report'" class="space-y-4">
                <!-- Simple list of damage marks for thermal print -->
                <div v-if="damageMarks.length > 0" class="space-y-2">
                    <span class="block font-bold text-[9px] text-gray-500 border-b border-gray-100 pb-0.5">
                        {{ $t('work_orders.print_view.vehicle_condition') }}:
                    </span>
                    <div 
                        v-for="(mark, index) in damageMarks" 
                        :key="index" 
                        class="flex items-center gap-2 text-[10px] pb-1.5 border-b border-gray-50 last:border-0"
                    >
                        <span 
                            class="w-4 h-4 rounded-full flex items-center justify-center text-[8px] font-bold text-white flex-shrink-0"
                            :style="{ backgroundColor: getColorValue(mark.color) }"
                        >{{ index + 1 }}</span>
                        <span class="text-gray-800 font-medium">{{ mark.description || $t('work_orders.print_view.na') }}</span>
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
                        <span class="whitespace-pre-wrap">{{ index + 1 }}. {{ item.service_name || item.description }}</span>
                        <span v-if="showPricingColumns" dir="ltr">{{ formatCurrency(lineSubtotalDisplay(item)) }}</span>
                    </div>
                    <div v-if="item.description && item.service_name" class="text-[9px] text-gray-500 mt-0.5 max-w-[70mm] leading-snug">
                         {{ item.description }}
                    </div>
                    <div v-if="showPricingColumns" class="text-[8px] text-gray-400 mt-0.5 flex justify-between">
                        <span>{{ $t('work_orders.print_view.calculation') }}: {{ item.qty || 1 }} × {{ formatCurrency(item.unit_price) }}</span>
                        <span v-if="lineDiscountDisplay(item) > 0" class="text-red-500">{{ $t('common.discount') }}: -{{ formatCurrency(lineDiscountDisplay(item)) }}</span>
                    </div>
                    <div v-if="item.technician" class="text-[8px] text-indigo-600 font-semibold mt-0.5">
                        {{ $t('work_orders.print_view.technician') }}: {{ item.technician }}
                    </div>
                </div>
            </div>
        </div>

        <!-- Financial Summary -->
        <div v-if="showPricingColumns && documentType !== 'receipt' && documentType !== 'payments'" class="py-2 border-t border-dashed border-gray-300 space-y-1 text-[10px]">
            <div v-if="documentType !== 'payments'" class="flex justify-between text-gray-500">
                <span>{{ $t('quotes.form.subtotal') }}:</span>
                <span dir="ltr">{{ formatCurrency(totals.subtotal) }}</span>
            </div>
            <div v-if="documentType !== 'payments' && totals.discount > 0" class="flex justify-between text-red-500">
                <span>{{ $t('quotes.form.total_discount') }}:</span>
                <span dir="ltr">-{{ formatCurrency(totals.discount) }}</span>
            </div>
            <div v-if="documentType !== 'payments' && totals.vat > 0" class="flex justify-between text-gray-500">
                <span>{{ $t('common.vat') }}:</span>
                <span dir="ltr">{{ formatCurrency(totals.vat) }}</span>
            </div>
            <div class="flex justify-between font-bold text-sm text-gray-900 border-t border-dashed border-gray-200 pt-1.5">
                <span>{{ documentType === 'payments' ? (isRtl ? 'إجمالي الفاتورة:' : 'Invoice Total:') : (isRtl ? 'الإجمالي النهائي:' : 'Total Amount:') }}</span>
                <span :style="{ color: primaryColor }" dir="ltr">{{ formatCurrency(totals.total) }}</span>
            </div>
            <div v-if="!['quotation', 'proforma_invoice'].includes(documentType)" class="flex justify-between text-gray-500 text-[10px]">
                <span>{{ isRtl ? 'المبلغ المدفوع:' : 'Paid Amount:' }}</span>
                <span dir="ltr">{{ formatCurrency(totals.paid - (data.bad_debt || 0)) }}</span>
            </div>
            <div v-if="!['quotation', 'proforma_invoice'].includes(documentType) && (data.bad_debt || 0) > 0" class="flex justify-between text-amber-600 text-[10px]">
                <span>{{ isRtl ? 'الديون المعدومة:' : 'Bad Debt:' }}</span>
                <span dir="ltr">{{ formatCurrency(data.bad_debt) }}</span>
            </div>
            <div v-if="!['quotation', 'proforma_invoice'].includes(documentType)" class="flex justify-between font-bold text-gray-800 text-[10px] bg-gray-50/50 p-1 rounded">
                <span>{{ isRtl ? 'الباقي:' : 'Remaining:' }}</span>
                <span dir="ltr">{{ formatCurrency(totals.balance) }}</span>
            </div>
            <div v-if="centerData.iban && documentSettings.show_iban" class="bg-gray-50/70 p-2 rounded border border-dashed border-gray-200 mt-2 text-[8px] text-gray-500 leading-normal">
                <span class="block font-bold text-gray-600">{{ $t('company_profile.profile.iban') }}:</span>
                {{ centerData.iban }}
            </div>
        </div>

        <!-- Terms and Conditions -->
        <div v-if="documentSettings.print_terms && (documentSettings.terms?.length > 0 || (previewMode && dummyTerms.length > 0))" class="py-2 border-t border-dashed border-gray-300">
            <span class="block font-bold text-[9px] text-gray-500 mb-1">{{ $t('work_orders.print_view.terms_conditions') }}:</span>
            <ul class="text-[8px] text-gray-400 space-y-0.5 list-disc list-inside leading-tight">
                <li v-for="(term, idx) in documentSettings.terms?.length > 0 ? documentSettings.terms : dummyTerms" :key="idx">
                    {{ isRtl ? (term.text_ar || term) : (term.text_en || term.text_ar || term) }}
                </li>
            </ul>
        </div>

        <!-- Signatures & Stamp -->
        <div class="py-4 border-t border-dashed border-gray-300 relative min-h-[25mm]">
            <!-- Dynamic Signatures Columns -->
            <div class="grid gap-4 items-center justify-center text-center" :class="getSignatureGridClass(visibleSignatures.length || 2)">
                <div 
                    v-for="(sig, index) in visibleSignatures" 
                    :key="index" 
                    class="text-center flex flex-col items-center justify-end"
                >
                    <div v-if="isClientSignature(isRtl ? sig.name_ar : (sig.name_en || sig.name_ar)) && (data.reception_signature || data.delivery_signature)" class="h-10 flex items-center justify-center mb-1">
                        <img :src="'/storage/' + (data.reception_signature || data.delivery_signature)" class="max-h-full max-w-[80px] object-contain" />
                    </div>
                    <div v-else class="border-b border-gray-300 w-24 mx-auto mb-1 h-6"></div>
                    <span class="text-[8px] text-gray-500 font-bold">{{ isRtl ? sig.name_ar : (sig.name_en || sig.name_ar) }}</span>
                </div>
            </div>

            <!-- Thermal Small Stamp -->
            <div v-if="documentSettings.show_stamp && (previewMode || centerData.stamp_url || visualSettings.stamp_url)" class="absolute bottom-2 left-4 w-16 h-16 select-none pointer-events-none z-10 opacity-70 -rotate-12 transition-all">
                <img v-if="centerData.stamp_url || visualSettings.stamp_url" :src="centerData.stamp_url || visualSettings.stamp_url" class="w-full h-full object-contain" />
                <svg v-else-if="previewMode" class="w-full h-full text-emerald-600/80 print:hidden" fill="none" viewBox="0 0 100 100" stroke="currentColor">
                    <circle cx="50" cy="50" r="45" stroke-width="2.5" stroke-dasharray="3 3"/>
                    <circle cx="50" cy="50" r="38" stroke-width="1.5"/>
                    <text x="50" y="38" font-size="6" font-weight="bold" fill="currentColor" text-anchor="middle" font-family="sans-serif">خدمة برو</text>
                    <text x="50" y="48" font-size="7" font-weight="black" fill="currentColor" text-anchor="middle" font-family="sans-serif">APPROVED</text>
                    <text x="50" y="58" font-size="7" font-weight="black" fill="currentColor" text-anchor="middle" font-family="sans-serif">مـعـتـمـد</text>
                    <path d="M25 68 Q50 78 75 68" stroke-width="1.5"/>
                    <text x="50" y="76" font-size="5" fill="currentColor" text-anchor="middle" font-family="sans-serif">KHIDMA PRO</text>
                </svg>
            </div>
        </div>

        <!-- ZATCA / Standard QR code for Thermal print -->
        <div v-if="documentSettings.show_qr_code && data.qr_code_url" class="flex flex-col items-center justify-center mt-3 pt-3 border-t border-dashed border-gray-200">
            <img :src="data.qr_code_url" alt="QR" class="w-24 h-24 p-1 bg-white border border-gray-200 rounded" />
        </div>

        <!-- Footer Text -->
        <div v-if="visualSettings.footer_text" class="text-center text-[8px] text-gray-400 mt-2 border-t border-dashed border-gray-200 pt-2 leading-tight">
            {{ visualSettings.footer_text }}
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { usePage } from '@inertiajs/vue3';
import SaudiPlateDisplay from '@/Components/Vehicles/SaudiPlateDisplay.vue';

const { t, locale } = useI18n();
const isRtl = computed(() => locale.value === 'ar');

const props = defineProps({
    documentType: {
        type: String,
        default: 'invoice'
    },
    data: {
        type: Object,
        default: () => ({})
    },
    centerData: {
        type: Object,
        default: () => ({})
    },
    documentSettings: {
        type: Object,
        default: () => ({})
    },
    visualSettings: {
        type: Object,
        default: () => ({})
    },
    previewMode: {
        type: Boolean,
        default: false
    }
});

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
    const page = usePage();
    const taxSettings = page.props?.tenant?.tax_settings;
    return taxSettings?.show_amount_before_vat ?? true;
});

function lineDiscountDisplay(item) {
    // الخصم يُطبَّق على قيمة الخدمة الكاملة (شاملة الضريبة)
    // لا يتغير بتغيير وضع عرض الضريبة — يبقى بقيمته الأصلية
    return item.discount || 0;
}

function lineSubtotalDisplay(item) {
    if (item.line_total_excl_tax !== undefined && item.line_total_excl_tax !== null && item.line_total_excl_tax !== 0) {
        return showExclusive.value ? Number(item.line_total_excl_tax) : Number(item.line_total_incl_tax);
    }
    // Fallback for mock/preview data
    const qty = item.qty || 1;
    const price = item.unit_price || 0;
    const discount = item.discount || 0;
    const lineTotalIncl = Math.max(price * qty - discount, 0);
    if (props.data.pricing_mode_snapshot === 'inclusive') {
        if (showExclusive.value) {
            const rate = isTaxEnabled() && item.is_taxable !== false ? (item.tax_rate_snapshot != null ? item.tax_rate_snapshot / 100 : 0.15) : 0;
            const tax = lineTotalIncl - (lineTotalIncl / (1 + rate));
            return lineTotalIncl - tax;
        }
        return lineTotalIncl;
    } else {
        return lineTotalIncl;
    }
}


// Services and Parts computeds for Invoice separation
const filteredItems = computed(() => {
    const items = props.data.items || dummyItems;
    return items.filter(item => item.status !== 'cancelled');
});

const services = computed(() => {
    if (['parts_invoice', 'purchase_invoice', 'purchase_return'].includes(props.documentType)) return [];
    return filteredItems.value.filter(item => !item.is_part);
});

const parts = computed(() => {
    if (['parts_invoice', 'purchase_invoice', 'purchase_return'].includes(props.documentType)) {
        return filteredItems.value;
    }
    return filteredItems.value.filter(item => item.is_part);
});

const isSleek = computed(() => props.visualSettings.active_template === 'TemplateSleekThermal');

const damageMarks = computed(() => props.data.damage_marks || []);
const getColorValue = (color) => ({ red: '#ef4444', blue: '#3b82f6', gray: '#6b7280' }[color] || '#ef4444');

// Format Currency
function formatCurrency(value) {
    const suffix = ' ' + t('common.currency');
    if (value === undefined || value === null) return '0.00' + suffix;
    return Number(value).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + suffix;
}

// Format Date
function formatDate(date) {
    if (!date) return '-';
    const d = new Date(date);
    return `${d.getDate()}/${d.getMonth() + 1}/${d.getFullYear()}`;
}

// Compute dynamic signature columns
function getSignatureGridClass(count) {
    if (count === 1) return 'grid-cols-1 justify-items-center';
    if (count === 2) return 'grid-cols-2';
    if (count === 3) return 'grid-cols-3';
    return 'grid-cols-4';
}

// Map document keys to localized Titles
function getDocTypeTitle(type) {
    const taxActive = isTaxEnabled();
    const titlesAr = {
        invoice: taxActive ? 'فاتورة ضريبية مبسطة' : 'فاتورة مبسطة',
        proforma_invoice: 'فاتورة أولية مبسطة',
        quotation: 'عرض سعر صيانة',
        parts_invoice: taxActive ? 'فاتورة قطع غيار ضريبية مبسطة' : 'فاتورة قطع غيار مبسطة',
        work_order: 'كرت الصيانة',
        receipt: 'سند قبض مالي',
        checklist: 'الفحص المنهجي',
        delivery_note: 'سند تسليم مركبة',
        condition_report: 'تقرير حالة المركبة',
        payments: 'سندات الدفع والمدفوعات المبسطة',
        purchase_invoice: taxActive ? 'فاتورة مشتريات ضريبية مبسطة' : 'فاتورة مشتريات مبسطة',
        purchase_return: taxActive ? 'فاتورة مرتجع مشتريات ضريبية مبسطة' : 'فاتورة مرتجع مشتريات مبسطة'
    };
    const titlesEn = {
        invoice: taxActive ? 'Simplified Tax Invoice' : 'Simplified Invoice',
        proforma_invoice: 'Simplified Proforma Invoice',
        quotation: 'Maintenance Quote',
        parts_invoice: taxActive ? 'Simplified Tax Parts Invoice' : 'Simplified Parts Invoice',
        work_order: 'Work Order',
        receipt: 'Receipt',
        checklist: 'Systematic Checklist',
        delivery_note: 'Vehicle Delivery Note',
        condition_report: 'Vehicle Condition Report',
        payments: 'Payments Receipt',
        purchase_invoice: taxActive ? 'Simplified Tax Purchase Invoice' : 'Simplified Purchase Invoice',
        purchase_return: taxActive ? 'Simplified Tax Purchase Return Invoice' : 'Simplified Purchase Return Invoice'
    };
    if (isRtl.value) {
        return titlesAr[type] || 'وثيقة رسمية';
    } else {
        return titlesEn[type] || 'Official Document';
    }
}

function getDocumentTitle() {
    const taxActive = isTaxEnabled();
    let title = isRtl.value
        ? (props.documentSettings?.title_ar || getDocTypeTitle(props.documentType))
        : (props.documentSettings?.title_en || getDocTypeTitle(props.documentType));

    title = String(title || '');

    if (!taxActive) {
        if (isRtl.value) {
            title = title
                .replace(/فاتورة ضريبية مبسطة/g, 'فاتورة مبسطة')
                .replace(/فاتورة ضريبية/g, 'فاتورة')
                .replace(/ضريبية/g, '')
                .trim();
        } else {
            title = title
                .replace(/Simplified Tax Invoice/gi, 'Simplified Invoice')
                .replace(/Tax Invoice/gi, 'Invoice')
                .replace(/Tax/gi, '')
                .trim();
        }
        if (!title) {
            title = isRtl.value ? 'فاتورة' : 'Invoice';
        }
    }

    return title;
}

// Dummy Fallback Data
const dummyItems = [
    { service_name: 'فحص كمبيوتر وبرمجة كاملة', description: 'فحص الحساسات ومسح الأخطاء', qty: 1, unit_price: 150 },
    { service_name: 'تغيير زيت المحرك مع الفلتر', description: 'زيت سينثيتك 10,000 كم مع السيفون', qty: 1, unit_price: 280 },
    { service_name: 'تغيير فحمات الفرامل الأمامية', description: 'خرط الهوبات وضبط الميزانية', qty: 1, unit_price: 320, technician: 'م. سليم' }
];

const dummyTerms = [
    'الضمان يسري على الأجزاء والخدمات المستبدلة فقط.',
    'المركز غير مسؤول عن فقدان أي مقتنيات شخصية تترك داخل السيارة.'
];

const defaultSignatures = [
    { name_ar: 'المستلم', name_en: 'Recipient', show: true },
    { name_ar: 'العميل', name_en: 'Customer', show: true }
];

// Only render signatures with show !== false
const visibleSignatures = computed(() => {
    const sigs = props.documentSettings?.signatures?.length > 0
        ? props.documentSettings.signatures
        : defaultSignatures;
    return sigs.filter(s => s.show !== false);
});

function isTaxEnabled() {
    const val = props.data.tax_enabled_snapshot;
    if (val === true || val === 1 || val === '1') return true;
    if (val === false || val === 0 || val === '0') return false;
    if (props.data.total_tax !== undefined && props.data.total_tax !== null) {
        return Number(props.data.total_tax) > 0;
    }
    return true; // Default fallback for dummy/preview data
}

function getMethodLabel(method) {
    if (!method) return '-';
    const methodsAr = {
        cash: 'نقداً',
        card: 'بطاقة مدى / ائتمانية',
        bank_transfer: 'تحويل بنكي',
        check: 'شيك',
        online: 'دفع إلكتروني',
        other: 'آخر'
    };
    const methodsEn = {
        cash: 'Cash',
        card: 'Card',
        bank_transfer: 'Bank Transfer',
        check: 'Cheque',
        online: 'Online',
        other: 'Other'
    };
    const methodKey = method.toLowerCase();
    if (isRtl.value) {
        return methodsAr[methodKey] || method;
    } else {
        return methodsEn[methodKey] || method;
    }
}

// Computed Totals
const totals = computed(() => {
    // Prioritize database-stored totals (from work order or invoice) to avoid line-item calculation mismatches
    if (props.data.total_incl_tax !== undefined && props.data.total_incl_tax !== null) {
        const taxEnabled = isTaxEnabled();
        const total = taxEnabled ? Number(props.data.total_incl_tax || 0) : Number(props.data.total_excl_tax || props.data.total_incl_tax || 0);
        const vat = taxEnabled ? Number(props.data.total_tax || 0) : 0;
        
        // Sum discount from items to show in the discount row if present, or use global discount if provided
        let discountVal = Number(props.data.discount_amount || props.data.discount || 0);
        if (discountVal === 0) {
            const items = props.data.items || [];
            items.forEach(item => {
                discountVal += Number(item.discount || 0);
            });
        }

        const isInclusive = props.data.pricing_mode_snapshot === 'inclusive';
        let discount = discountVal;
        let subtotal = 0;

        if (isInclusive) {
            if (showExclusive.value) {
                // الخصم يبقى بقيمته الكاملة (شامل الضريبة) — لا يُقسم على معامل الضريبة
                discount = discountVal;
                subtotal = Number(props.data.total_excl_tax || 0) + discountVal;
            } else {
                discount = discountVal;
                subtotal = total + discountVal;
            }
        } else {
            discount = discountVal;
            subtotal = Number(props.data.total_excl_tax || 0) + discount;
        }

        const paid = Number(props.data.total_paid !== undefined ? props.data.total_paid : 0);
        const balance = Number(props.data.balance !== undefined ? props.data.balance : Math.max(total - paid, 0));

        return {
            subtotal,
            discount,
            vat,
            total,
            paid,
            balance
        };
    }

    const items = (props.data.items || dummyItems).filter(item => item.status !== 'cancelled');
    let grossSubtotal = 0;
    let discountAmt = 0;
    let taxAmt = 0;
    let totalAmt = 0;
    
    const taxEnabled = isTaxEnabled();
    const isInclusive = props.data.pricing_mode_snapshot === 'inclusive';

    items.forEach(item => {
        const itemQty = item.qty || 1;
        const itemPrice = item.unit_price || 0;
        const itemDiscount = item.discount || 0;
        const rate = item.tax_rate_snapshot != null ? item.tax_rate_snapshot / 100 : 0.15;

        let lineTotal = 0;
        let lineTax = 0;
        let lineExcl = 0;
        let lineDisc = 0;

        if (isInclusive) {
            lineTotal = Math.max(itemPrice * itemQty - itemDiscount, 0);
            if (taxEnabled && item.is_taxable !== false) {
                lineTax = lineTotal - (lineTotal / (1 + rate));
            }
            lineExcl = lineTotal - lineTax;
            lineDisc = taxEnabled && item.is_taxable !== false ? (itemDiscount / (1 + rate)) : itemDiscount;
        } else {
            lineExcl = Math.max(itemPrice * itemQty - itemDiscount, 0);
            if (taxEnabled && item.is_taxable !== false) {
                lineTax = lineExcl * rate;
            }
            lineTotal = lineExcl + lineTax;
            lineDisc = itemDiscount;
        }

        taxAmt += lineTax;
        totalAmt += lineTotal;

        if (showExclusive.value) {
            grossSubtotal += lineExcl + lineDisc;
            discountAmt += lineDisc;
        } else {
            grossSubtotal += (itemPrice * itemQty);
            discountAmt += itemDiscount;
        }
    });

    const paid = props.data.total_paid !== undefined ? props.data.total_paid : 0;
    const balance = props.data.balance !== undefined ? props.data.balance : Math.max(totalAmt - paid, 0);

    return {
        subtotal: grossSubtotal,
        discount: discountAmt,
        vat: taxAmt,
        total: totalAmt,
        paid,
        balance
    };
});

function isClientSignature(name) {
    if (!name) return false;
    const n = name.toLowerCase();
    return n.includes('عميل') || n.includes('العميل') || n.includes('customer') || n.includes('client');
}

function getLocalizedColor(colorName, isRtlVal) {
    if (!colorName) return '';
    const colorMap = {
        'white': { ar: 'أبيض', en: 'White' },
        'أبيض': { ar: 'أبيض', en: 'White' },
        'black': { ar: 'أسود', en: 'Black' },
        'أسود': { ar: 'أسود', en: 'Black' },
        'silver': { ar: 'فضي', en: 'Silver' },
        'فضي': { ar: 'فضي', en: 'Silver' },
        'gray': { ar: 'رمادي', en: 'Gray' },
        'grey': { ar: 'رمادي', en: 'Gray' },
        'رمادي': { ar: 'رمادي', en: 'Gray' },
        'red': { ar: 'أحمر', en: 'Red' },
        'أحمر': { ar: 'أحمر', en: 'Red' },
        'blue': { ar: 'أزرق', en: 'Blue' },
        'أزرق': { ar: 'أزرق', en: 'Blue' },
        'brown': { ar: 'بني', en: 'Brown' },
        'بني': { ar: 'بني', en: 'Brown' },
        'green': { ar: 'أخضر', en: 'Green' },
        'أخضر': { ar: 'أخضر', en: 'Green' },
        'beige': { ar: 'بيج', en: 'Beige' },
        'بيج': { ar: 'بيج', en: 'Beige' },
        'orange': { ar: 'برتقالي', en: 'Orange' },
        'برتقالي': { ar: 'برتقالي', en: 'Orange' },
        'gold': { ar: 'ذهبي', en: 'Gold' },
        'ذهبي': { ar: 'ذهبي', en: 'Gold' },
        'yellow': { ar: 'أصفر', en: 'Yellow' },
        'أصفر': { ar: 'أصفر', en: 'Yellow' },
        'purple': { ar: 'بنفسجي', en: 'Purple' },
        'بنفسجي': { ar: 'بنفسجي', en: 'Purple' },
        'bronze': { ar: 'برونزي', en: 'Bronze' },
        'برونزي': { ar: 'برونزي', en: 'Bronze' },
        'maroon': { ar: 'كستنائي', en: 'Maroon' },
        'كستنائي': { ar: 'كستنائي', en: 'Maroon' },
        'navy': { ar: 'كحلي', en: 'Navy' },
        'كحلي': { ar: 'كحلي', en: 'Navy' }
    };
    const key = colorName.trim().toLowerCase();
    const match = colorMap[key];
    if (match) {
        return isRtlVal ? match.ar : match.en;
    }
    return colorName;
}
</script>
