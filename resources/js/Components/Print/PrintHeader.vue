<template>
    <div class="print-header mb-6 pb-4 border-b-2 border-gray-300">
        <div class="grid grid-cols-3 items-start">
            <!-- Column 1: Company Info (Right in Arabic, Left in English) -->
            <div :class="isRtl ? 'text-right' : 'text-left'">
                <h1 class="text-xl font-bold text-gray-900">{{ companyName }}</h1>
                <p v-if="centerName" class="text-sm font-medium text-gray-600">{{ centerName }}</p>
                <div class="mt-2 text-[10px] space-y-0.5 text-gray-500">
                    <p v-if="phone">
                        {{ isRtl ? 'الهاتف' : 'Phone' }}: <span dir="ltr">{{ toEnglish(phone) }}</span>
                    </p>
                    <p v-if="email">
                        {{ isRtl ? 'البريد' : 'Email' }}: {{ email }}
                    </p>
                    <p v-if="crNumber">
                        {{ isRtl ? 'السجل التجاري' : 'CR' }}: {{ toEnglish(crNumber) }}
                    </p>
                    <p v-if="taxNumber">
                        {{ isRtl ? 'الرقم الضريبي' : 'VAT' }}: {{ toEnglish(taxNumber) }}
                    </p>
                </div>
            </div>

            <!-- Column 2: Logo (Always Center) -->
            <div class="flex justify-center">
                <div v-if="logoUrl" class="w-24 h-24 flex-shrink-0">
                    <img :src="logoUrl" :alt="companyName" class="w-full h-full object-contain" />
                </div>
            </div>

            <!-- Column 3: Date (Far Left in Arabic, Far Right in English) -->
            <div :class="isRtl ? 'text-left' : 'text-right'">
                <p class="text-[10px] text-gray-400 font-bold" dir="ltr">
                    {{ new Date().toLocaleDateString('en-US') }}
                </p>
            </div>
        </div>
        
        <!-- Title centered -->
        <div class="mt-8 text-center">
            <h2 class="text-xl font-bold text-gray-900 uppercase tracking-wider">{{ title }}</h2>
            <p v-if="subtitle" class="text-sm text-gray-600 mt-1 font-mono">{{ subtitle }}</p>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { usePage } from '@inertiajs/vue3';
import { useNumberFormat } from '@/Composables/useNumberFormat';

const { toEnglish } = useNumberFormat();

const props = defineProps({
    title: { type: String, required: true },
    subtitle: { type: String, default: '' },
    workOrder: { type: Object, default: null },
});

const { locale } = useI18n();
const page = usePage();

const isRtl = computed(() => locale.value === 'ar');

// Get company data from workOrder or page props
const logoUrl = computed(() => {
    if (props.workOrder?.center?.logo_url) return props.workOrder.center.logo_url;
    if (props.workOrder?.tenant?.logo_url) return props.workOrder.tenant.logo_url;
    return page.props?.tenant?.logo_url || null;
});

const companyName = computed(() => {
    return props.workOrder?.tenant?.trade_name || props.workOrder?.tenant?.name || page.props?.tenant?.trade_name || page.props?.tenant?.name || 'Carag';
});

const centerName = computed(() => {
    if (props.workOrder?.center?.name) return props.workOrder.center.name;
    const center = page.props?.center;
    if (center?.name) {
        return isRtl.value ? center.name : (center.name_en || center.name);
    }
    return null;
});

const phone = computed(() => {
    return props.workOrder?.center?.phone || props.workOrder?.tenant?.phone || page.props?.center?.phone || page.props?.tenant?.phone || null;
});

const email = computed(() => {
    return props.workOrder?.center?.email || props.workOrder?.tenant?.email || page.props?.center?.email || page.props?.tenant?.email || null;
});

const crNumber = computed(() => {
    return props.workOrder?.tenant?.cr_number || page.props?.tenant?.cr_number || null;
});

const taxNumber = computed(() => {
    return props.workOrder?.tenant?.tax_number || page.props?.tenant?.tax_number || null;
});

const printDate = computed(() => {
    return new Date().toLocaleDateString('en-US');
});
</script>
