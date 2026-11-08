<template>
    <div class="standard-report-header mb-6 pb-4 border-b-2 border-gray-900 text-xs">
        <div class="grid grid-cols-3 items-center gap-4">
            <!-- Column 1: Company / Center Information (Right in RTL, Left in LTR) -->
            <div class="text-start space-y-1">
                <h1 class="text-base font-black text-gray-900">{{ companyName }}</h1>
                <p v-if="centerName" class="font-bold text-gray-700">
                    {{ $t('common.branch_label') }} {{ centerName }}
                </p>
                <p v-if="phone" class="text-gray-600">
                    {{ $t('common.phone_label') }}
                    <span class="font-mono font-medium" dir="ltr">{{ phone }}</span>
                </p>
                <p v-if="taxNumber" class="text-gray-600">
                    {{ $t('common.tax_number_label') }}
                    <span class="font-mono font-bold" dir="ltr">{{ taxNumber }}</span>
                </p>
                <p v-if="crNumber" class="text-gray-600">
                    {{ $t('common.commercial_registration_label') }}
                    <span class="font-mono" dir="ltr">{{ crNumber }}</span>
                </p>
                <slot name="company-extra" />
            </div>

            <!-- Column 2: Logo (Absolute Center) & Report Title -->
            <div class="text-center flex flex-col items-center justify-center">
                <div v-if="logoUrl" class="w-20 h-20 mb-1 flex items-center justify-center">
                    <img
                        :src="logoUrl"
                        :alt="companyName"
                        class="max-w-full max-h-full object-contain"
                    />
                </div>
                <div v-else class="text-center py-2">
                    <span class="text-base font-black tracking-wide text-gray-900">
                        {{ companyName }}
                    </span>
                </div>

                <h2 class="text-sm font-black uppercase tracking-wider text-gray-900 mt-1">
                    {{ title }}
                </h2>
                <p v-if="subtitle" class="text-[11px] text-gray-500 font-medium mt-0.5 font-mono">
                    {{ subtitle }}
                </p>
                <slot name="title-extra" />
            </div>

            <!-- Column 3: Date, Time & Report Metadata (Left in RTL, Right in LTR) -->
            <div class="text-end space-y-1">
                <p class="text-gray-500">
                    <span>{{ $t('work_orders.print_view.date') }}:</span>
                    <span class="font-mono font-bold text-gray-900 ms-1.5" dir="ltr">
                        {{ currentDate }}
                    </span>
                </p>
                <p v-if="showTime" class="text-gray-500">
                    <span>{{ $t('common.time') || 'الوقت' }}:</span>
                    <span class="font-mono text-gray-700 ms-1.5" dir="ltr">{{ currentTime }}</span>
                </p>
                <p
                    v-if="totalCount !== null && totalCount !== undefined"
                    class="text-gray-600 font-bold"
                >
                    <span>{{ totalCountLabel || $t('common.total') }}:</span>
                    <span class="font-mono text-gray-900 ms-1.5" dir="ltr">{{ totalCount }}</span>
                </p>
                <slot name="metadata" />
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';

const props = defineProps({
    title: {
        type: String,
        required: true,
    },
    subtitle: {
        type: String,
        default: '',
    },
    totalCount: {
        type: [Number, String],
        default: null,
    },
    totalCountLabel: {
        type: String,
        default: '',
    },
    customCompany: {
        type: Object,
        default: null,
    },
    showTime: {
        type: Boolean,
        default: true,
    },
});

const page = usePage();
const { locale } = useI18n();
const isRtl = computed(() => locale.value === 'ar');

const tenant = computed(() => props.customCompany || page.props.tenant || {});
const center = computed(() => page.props.center || {});

const companyName = computed(() => {
    return tenant.value.trade_name || tenant.value.name || 'Carag V2';
});

const centerName = computed(() => {
    // Show branch only if available or explicitly defined
    if (center.value.name) {
        return isRtl.value ? center.value.name : center.value.name_en || center.value.name;
    }
    return null;
});

const phone = computed(() => {
    return center.value.phone || tenant.value.phone || null;
});

const taxNumber = computed(() => {
    return tenant.value.tax_number || tenant.value.vat_number || null;
});

const crNumber = computed(() => {
    return tenant.value.cr_number || null;
});

const logoUrl = computed(() => {
    if (center.value.logo_url) return center.value.logo_url;
    if (tenant.value.logo_url) return tenant.value.logo_url;
    if (tenant.value.logo_path) return '/storage/' + tenant.value.logo_path;
    return null;
});

// Standard YYYY-MM-DD in English numbers
const currentDate = computed(() => {
    const d = new Date();
    const year = d.getFullYear();
    const month = String(d.getMonth() + 1).padStart(2, '0');
    const day = String(d.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
});

// Standard HH:MM in English numbers
const currentTime = computed(() => {
    const d = new Date();
    const hours = String(d.getHours()).padStart(2, '0');
    const minutes = String(d.getMinutes()).padStart(2, '0');
    return `${hours}:${minutes}`;
});
</script>

<style scoped>
@media print {
    .standard-report-header {
        border-bottom: 2px solid #111827 !important;
    }
}
</style>
