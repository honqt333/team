<template>
    <div 
        class="bg-white text-gray-900 font-mono p-4 mx-auto relative select-text text-xs leading-normal"
        :class="previewMode ? 'w-[80mm] shadow-xl border border-gray-200 rounded-lg scale-95 origin-top' : 'w-full print:p-0 print:shadow-none print:border-none'"
        :dir="isRtl ? 'rtl' : 'ltr'"
    >
        <!-- Center Header -->
        <div class="text-center pb-3 border-b transition-all duration-300" :class="isSleek ? 'border-b-2 border-slate-900' : 'border-b border-dashed border-gray-300'">
            <div v-if="visualSettings.show_logo && centerData.logo" class="w-12 h-12 mx-auto mb-1">
                <img :src="centerData.logo" alt="Logo" class="w-full h-full object-contain" />
            </div>
            <h2 class="text-sm font-bold text-gray-900 transition-all duration-300" :style="{ color: isSleek ? primaryColor : '#111827' }">{{ centerData.name || 'مركز خدمة برو' }}</h2>
            <p v-if="centerData.tax_number" class="text-[10px] text-gray-500 mt-0.5">{{ isRtl ? 'الرقم الضريبي:' : 'Tax Number:' }} {{ centerData.tax_number }}</p>
            <p class="text-[9px] text-gray-400 mt-0.5">{{ centerData.address }}</p>
        </div>

        <!-- Document Details -->
        <div class="py-2 border-b border-dashed border-gray-300 text-[10px] text-gray-600 space-y-0.5">
            <div class="flex justify-between items-center">
                <span class="font-bold text-gray-800 transition-all duration-300" :class="isSleek ? 'text-[10px] font-black uppercase tracking-wider px-2 py-0.5 bg-slate-900 text-white rounded' : ''" :style="isSleek ? { backgroundColor: primaryColor } : {}">
                    {{ isRtl ? (documentSettings.title_ar || getDocTypeTitle(documentType)) : (documentSettings.title_en || getDocTypeTitle(documentType)) }}
                </span>
                <span class="font-bold" :style="{ color: primaryColor }">#{{ data.code || 'WO-100293' }}</span>
            </div>
            <div class="flex justify-between">
                <span>{{ isRtl ? 'التاريخ:' : 'Date:' }}</span>
                <span>{{ formatDate(data.created_at || new Date()) }}</span>
            </div>
            <div v-if="data.expected_end_date" class="flex justify-between text-amber-600 font-bold">
                <span>{{ isRtl ? 'التسليم المتوقع:' : 'Expected Delivery:' }}</span>
                <span>{{ formatDate(data.expected_end_date) }}</span>
            </div>
        </div>

        <!-- Customer & Vehicle Info -->
        <div class="py-2 border-b border-dashed border-gray-300 text-[10px] text-gray-600 space-y-0.5">
            <div class="flex justify-between">
                <span>{{ isRtl ? 'العميل:' : 'Client:' }}</span>
                <span class="font-bold text-gray-900">{{ data.customer?.name || (isRtl ? 'أحمد عبد الله' : 'Ahmed Abdullah') }}</span>
            </div>
            <div v-if="documentSettings.show_customer_address !== false && data.customer?.address" class="flex justify-between">
                <span>{{ isRtl ? 'العنوان:' : 'Address:' }}</span>
                <span class="text-gray-800 text-right">{{ data.customer.address }}</span>
            </div>
            <div v-if="data.customer?.tax_number" class="flex justify-between">
                <span>{{ isRtl ? 'الرقم الضريبي:' : 'Tax ID:' }}</span>
                <span class="text-gray-900 font-mono">{{ data.customer.tax_number }}</span>
            </div>
            <div class="flex justify-between items-center">
                <span>{{ isRtl ? 'المركبة:' : 'Vehicle:' }}</span>
                <span class="text-gray-800 flex items-center gap-1.5">
                    <img v-if="data.vehicle?.make_logo" :src="data.vehicle.make_logo" alt="Make Logo" class="w-3.5 h-3.5 object-contain" />
                    {{ data.vehicle?.make || (isRtl ? 'تويوتا' : 'Toyota') }}
                </span>
            </div>
            <div class="flex justify-between items-center">
                <span>{{ isRtl ? 'اللوحة:' : 'Plate:' }}</span>
                <SaudiPlateDisplay v-if="data.vehicle?.plate" :plate-number="data.vehicle.plate" size="sm" />
                <span v-else class="font-bold text-gray-900">-</span>
            </div>
            <div v-if="data.vehicle?.color" class="flex justify-between">
                <span>{{ isRtl ? 'اللون:' : 'Color:' }}</span>
                <span class="text-gray-900 font-medium">{{ getLocalizedColor(data.vehicle.color, isRtl) }}</span>
            </div>
            <div class="flex justify-between">
                <span>{{ isRtl ? 'العداد:' : 'Odometer:' }}</span>
                <span>{{ data.odometer || '45,210' }}{{ isRtl ? ' كم' : ' km' }}</span>
            </div>
            <div v-if="data.fuel_level" class="flex justify-between">
                <span>{{ isRtl ? 'مستوى الوقود:' : 'Fuel Level:' }}</span>
                <span>{{ data.fuel_level }}%</span>
            </div>
        </div>

        <!-- Content Body -->
        <div class="py-3">
            <!-- Scenario 1: Receipt -->
            <div v-if="documentType === 'receipt'" class="bg-gray-50/70 p-3 rounded-lg border border-dashed border-gray-200 leading-relaxed text-[10px] text-gray-700">
                <div class="flex justify-between items-center mb-2 pb-1.5 border-b border-dashed border-gray-200">
                    <span class="font-bold text-gray-600">{{ isRtl ? 'سند قبض' : 'Receipt' }}</span>
                    <span class="font-bold text-emerald-600 text-sm" dir="ltr">{{ formatCurrency(data.amount || 250) }}</span>
                </div>
                <p>{{ isRtl ? 'مستلم من:' : 'Received from:' }} <strong>{{ data.customer?.name || (isRtl ? 'أحمد عبد الله' : 'Ahmed Abdullah') }}</strong></p>
                <p class="mt-1">{{ isRtl ? 'مبلغ:' : 'Amount:' }} <strong>{{ formatCurrency(data.amount || 250) }}</strong></p>
                <p class="mt-1 text-gray-500 italic">{{ isRtl ? 'البيان:' : 'Notes:' }} {{ data.notes || (isRtl ? 'دفعة مقدمة لصيانة السيارة المذكورة أعلاه.' : 'Down payment for maintenance of the above-mentioned vehicle.') }}</p>
            </div>

            <!-- Scenario 2: Invoice (Separated Services and Parts) -->
            <div v-else-if="documentType === 'invoice'" class="space-y-4">
                <!-- Services -->
                <div v-if="services.length > 0" class="space-y-2">
                    <span class="block font-bold text-[9px] text-gray-500 border-b border-gray-100 pb-0.5">
                        {{ isRtl ? 'أجور الخدمات واليد:' : 'Labor & Services:' }}
                    </span>
                    <div 
                        v-for="(item, index) in services" 
                        :key="'srv-' + index" 
                        class="flex flex-col text-[10px] pb-2 border-b border-gray-50 last:border-0"
                    >
                        <div class="flex justify-between font-bold text-gray-900">
                            <span>{{ index + 1 }}. {{ item.service_name || item.description }}</span>
                            <span v-if="showPricingColumns" dir="ltr">{{ formatCurrency((item.unit_price * (item.qty || 1)) - (item.discount || 0)) }}</span>
                        </div>
                        <div v-if="item.description && item.service_name" class="text-[9px] text-gray-500 mt-0.5 max-w-[70mm] leading-snug">
                             {{ item.description }}
                        </div>
                        <div v-if="showPricingColumns" class="text-[8px] text-gray-400 mt-0.5 flex justify-between">
                            <span>{{ isRtl ? 'الحساب:' : 'Calculation:' }} {{ item.qty || 1 }} × {{ formatCurrency(item.unit_price) }}</span>
                            <span v-if="item.discount > 0" class="text-red-500">{{ isRtl ? 'خصم:' : 'Discount:' }} -{{ formatCurrency(item.discount) }}</span>
                        </div>
                    </div>
                </div>

                <!-- Parts -->
                <div v-if="parts.length > 0" class="space-y-2">
                    <span class="block font-bold text-[9px] text-gray-500 border-b border-gray-100 pb-0.5 mt-2">
                        {{ isRtl ? 'قطع الغيار المستبدلة:' : 'Replaced Spare Parts:' }}
                    </span>
                    <div 
                        v-for="(item, index) in parts" 
                        :key="'part-' + index" 
                        class="flex flex-col text-[10px] pb-2 border-b border-gray-50 last:border-0"
                    >
                        <div class="flex justify-between font-bold text-gray-900">
                            <span>{{ index + 1 }}. {{ item.service_name || item.description }}</span>
                            <span v-if="showPricingColumns" dir="ltr">{{ formatCurrency((item.unit_price * (item.qty || 1)) - (item.discount || 0)) }}</span>
                        </div>
                        <div v-if="item.description && item.service_name" class="text-[9px] text-gray-500 mt-0.5 max-w-[70mm] leading-snug">
                             {{ item.description }}
                        </div>
                        <div v-if="showPricingColumns" class="text-[8px] text-gray-400 mt-0.5 flex justify-between">
                            <span>{{ isRtl ? 'الحساب:' : 'Calculation:' }} {{ item.qty || 1 }} × {{ formatCurrency(item.unit_price) }}</span>
                            <span v-if="item.discount > 0" class="text-red-500">{{ isRtl ? 'خصم:' : 'Discount:' }} -{{ formatCurrency(item.discount) }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Scenario 3: Vehicle Condition Report -->
            <div v-else-if="documentType === 'condition_report'" class="space-y-4">
                <!-- Simple list of damage marks for thermal print -->
                <div v-if="damageMarks.length > 0" class="space-y-2">
                    <span class="block font-bold text-[9px] text-gray-500 border-b border-gray-100 pb-0.5">
                        {{ isRtl ? 'تفاصيل حالة المركبة:' : 'Vehicle Condition Details:' }}
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
                        <span class="text-gray-800 font-medium">{{ mark.description || (isRtl ? 'بدون تفاصيل' : 'No details') }}</span>
                    </div>
                </div>
            </div>

            <!-- Scenario 5: Standard Item List (work order, etc.) -->
            <div v-else class="space-y-3">
                <div 
                    v-for="(item, index) in data.items || dummyItems" 
                    :key="index" 
                    class="flex flex-col text-[10px] pb-2 border-b border-gray-50 last:border-0"
                >
                    <div class="flex justify-between font-bold text-gray-900">
                        <span>{{ index + 1 }}. {{ item.service_name || item.description }}</span>
                        <span v-if="showPricingColumns" dir="ltr">{{ formatCurrency((item.unit_price * (item.qty || 1)) - (item.discount || 0)) }}</span>
                    </div>
                    <div v-if="item.description && item.service_name" class="text-[9px] text-gray-500 mt-0.5 max-w-[70mm] leading-snug">
                         {{ item.description }}
                    </div>
                    <div v-if="showPricingColumns" class="text-[8px] text-gray-400 mt-0.5 flex justify-between">
                        <span>{{ isRtl ? 'الحساب:' : 'Calculation:' }} {{ item.qty || 1 }} × {{ formatCurrency(item.unit_price) }}</span>
                        <span v-if="item.discount > 0" class="text-red-500">{{ isRtl ? 'خصم:' : 'Discount:' }} -{{ formatCurrency(item.discount) }}</span>
                    </div>
                    <div v-if="item.technician" class="text-[8px] text-indigo-600 font-semibold mt-0.5">
                        {{ isRtl ? 'الفني:' : 'Technician:' }} {{ item.technician }}
                    </div>
                </div>
            </div>
        </div>

        <!-- Financial Summary -->
        <div v-if="showPricingColumns && documentType !== 'receipt'" class="py-2 border-t border-dashed border-gray-300 space-y-1 text-[10px]">
            <div class="flex justify-between text-gray-500">
                <span>{{ isRtl ? 'المجموع الفرعي:' : 'Subtotal:' }}</span>
                <span dir="ltr">{{ formatCurrency(totals.subtotal) }}</span>
            </div>
            <div v-if="totals.discount > 0" class="flex justify-between text-red-500">
                <span>{{ isRtl ? 'الخصم الإجمالي:' : 'Total Discount:' }}</span>
                <span dir="ltr">-{{ formatCurrency(totals.discount) }}</span>
            </div>
            <div v-if="totals.vat > 0" class="flex justify-between text-gray-500">
                <span>{{ isRtl ? 'الضريبة (VAT):' : 'VAT:' }}</span>
                <span dir="ltr">{{ formatCurrency(totals.vat) }}</span>
            </div>
            <div class="flex justify-between font-bold text-sm text-gray-900 border-t border-dashed border-gray-200 pt-1.5">
                <span>{{ isRtl ? 'الإجمالي النهائي:' : 'Total Amount:' }}</span>
                <span :style="{ color: primaryColor }" dir="ltr">{{ formatCurrency(totals.total) }}</span>
            </div>
            <div v-if="centerData.iban && documentSettings.show_iban" class="bg-gray-50/70 p-2 rounded border border-dashed border-gray-200 mt-2 text-[8px] text-gray-500 leading-normal">
                <span class="block font-bold text-gray-600">{{ isRtl ? 'آيبان البنك (IBAN):' : 'Bank IBAN:' }}</span>
                {{ centerData.iban }}
            </div>
        </div>

        <!-- Terms and Conditions -->
        <div v-if="documentSettings.print_terms && (documentSettings.terms?.length > 0 || dummyTerms.length > 0)" class="py-2 border-t border-dashed border-gray-300">
            <span class="block font-bold text-[9px] text-gray-500 mb-1">{{ isRtl ? 'الشروط والأحكام:' : 'Terms & Conditions:' }}</span>
            <ul class="text-[8px] text-gray-400 space-y-0.5 list-disc list-inside leading-tight">
                <li v-for="(term, idx) in documentSettings.terms?.length > 0 ? documentSettings.terms : dummyTerms" :key="idx">
                    {{ isRtl ? (term.text_ar || term) : (term.text_en || term.text_ar || term) }}
                </li>
            </ul>
        </div>

        <!-- Signatures & Stamp -->
        <div class="py-4 border-t border-dashed border-gray-300 relative min-h-[25mm]">
            <!-- Dynamic Signatures Columns -->
            <div class="grid gap-4 items-center justify-center text-center" :class="getSignatureGridClass(documentSettings.signatures?.length || 2)">
                <div 
                    v-for="(sig, index) in documentSettings.signatures?.length > 0 ? documentSettings.signatures : defaultSignatures" 
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
            <div v-if="visualSettings.show_stamp" class="absolute bottom-2 left-4 w-16 h-16 select-none pointer-events-none z-10 opacity-70 -rotate-12 transition-all">
                <img v-if="centerData.stamp_url || visualSettings.stamp_url" :src="centerData.stamp_url || visualSettings.stamp_url" class="w-full h-full object-contain" />
                <svg v-else class="w-full h-full text-emerald-600/80" fill="none" viewBox="0 0 100 100" stroke="currentColor">
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

        <!-- Footer Text -->
        <div v-if="visualSettings.footer_text" class="text-center text-[8px] text-gray-400 mt-2 border-t border-dashed border-gray-200 pt-2 leading-tight">
            {{ visualSettings.footer_text }}
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import SaudiPlateDisplay from '@/Components/Vehicles/SaudiPlateDisplay.vue';

const { locale } = useI18n();
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

// Services and Parts computeds for Invoice separation
const services = computed(() => {
    const items = props.data.items || dummyItems;
    return items.filter(item => !item.is_part);
});

const parts = computed(() => {
    const items = props.data.items || dummyItems;
    return items.filter(item => item.is_part);
});

const isSleek = computed(() => props.visualSettings.active_template === 'TemplateSleekThermal');

const damageMarks = computed(() => props.data.damage_marks || []);
const getColorValue = (color) => ({ red: '#ef4444', blue: '#3b82f6', gray: '#6b7280' }[color] || '#ef4444');

// Format Currency
function formatCurrency(value) {
    const suffix = isRtl.value ? ' ر.س' : ' SAR';
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
    const titlesAr = {
        invoice: 'فاتورة مبسطة',
        proforma_invoice: 'فاتورة أولية مبسطة',
        quotation: 'عرض سعر صيانة',
        parts_invoice: 'فاتورة قطع غيار مبسطة',
        work_order: 'كرت الصيانة',
        receipt: 'سند قبض مالي',
        checklist: 'الفحص المنهجي',
        delivery_note: 'سند تسليم مركبة',
        condition_report: 'تقرير حالة المركبة'
    };
    const titlesEn = {
        invoice: 'Simplified Invoice',
        proforma_invoice: 'Simplified Proforma Invoice',
        quotation: 'Maintenance Quote',
        parts_invoice: 'Simplified Parts Invoice',
        work_order: 'Work Order',
        receipt: 'Receipt',
        checklist: 'Systematic Checklist',
        delivery_note: 'Vehicle Delivery Note',
        condition_report: 'Vehicle Condition Report'
    };
    if (isRtl.value) {
        return titlesAr[type] || 'وثيقة رسمية';
    } else {
        return titlesEn[type] || 'Official Document';
    }
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
    { name_ar: 'المستلم', name_en: 'Recipient' },
    { name_ar: 'العميل', name_en: 'Customer' }
];

// Computed Totals
const totals = computed(() => {
    const items = props.data.items || dummyItems;
    let subtotal = 0;
    let discount = 0;
    let vat = 0;
    
    const taxEnabled = props.data.tax_enabled_snapshot !== false;

    items.forEach(item => {
        const itemQty = item.qty || 1;
        const itemPrice = item.unit_price || 0;
        const itemDiscount = item.discount || 0;

        subtotal += itemPrice * itemQty;
        discount += itemDiscount;

        if (taxEnabled && item.is_taxable !== false) {
            const lineExclTax = Math.max((itemPrice * itemQty) - itemDiscount, 0);
            const rate = item.tax_rate_snapshot != null ? item.tax_rate_snapshot / 100 : 0.15;
            vat += lineExclTax * rate;
        }
    });

    const subtotalAfterDiscount = Math.max(subtotal - discount, 0);
    const total = subtotalAfterDiscount + vat;

    return { subtotal, discount, vat, total };
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
