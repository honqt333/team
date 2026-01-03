<template>
    <div class="print-header mb-6 pb-4 border-b-2 border-gray-300">
        <!-- Arabic Layout: Logo right, info beside it -->
        <div v-if="isRtl" class="flex items-start gap-4 mb-4" style="direction: rtl;">
            <!-- Logo -->
            <div v-if="logoUrl" class="w-20 h-20 flex-shrink-0">
                <img :src="logoUrl" :alt="companyName" class="w-full h-full object-contain" />
            </div>
            <!-- Center/Company Info -->
            <div class="flex-1 text-right">
                <h1 class="text-xl font-bold text-gray-900">{{ companyName }}</h1>
                <p class="text-sm text-gray-600" v-if="phone">هاتف: <span dir="ltr">{{ phone }}</span></p>
                <p class="text-sm text-gray-600" v-if="email">البريد: {{ email }}</p>
                <p class="text-sm text-gray-600" v-if="crNumber">السجل التجاري: {{ crNumber }}</p>
                <p class="text-sm text-gray-600" v-if="taxNumber">الرقم الضريبي: {{ taxNumber }}</p>
            </div>
        </div>
        
        <!-- English Layout: Logo left with info beside it -->
        <div v-else class="flex items-start gap-4 mb-4">
            <!-- Logo -->
            <div v-if="logoUrl" class="w-20 h-20 flex-shrink-0">
                <img :src="logoUrl" :alt="companyName" class="w-full h-full object-contain" />
            </div>
            <!-- Center/Company Info -->
            <div class="flex-1">
                <h1 class="text-xl font-bold text-gray-900">{{ companyName }}</h1>
                <p class="text-sm text-gray-600" v-if="phone">Phone: {{ phone }}</p>
                <p class="text-sm text-gray-600" v-if="email">Email: {{ email }}</p>
                <p class="text-sm text-gray-600" v-if="crNumber">CR: {{ crNumber }}</p>
                <p class="text-sm text-gray-600" v-if="taxNumber">Tax No: {{ taxNumber }}</p>
            </div>
        </div>
        
        <!-- Title centered (both languages) -->
        <div class="border-t pt-4 border-gray-300 text-center">
            <h2 class="text-xl font-bold text-gray-900">{{ title }}</h2>
            <p v-if="subtitle" class="text-sm text-gray-600 mt-1">{{ subtitle }}</p>
            <p class="text-xs text-gray-500 mt-1">{{ printDate }}</p>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { usePage } from '@inertiajs/vue3';

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
    if (props.workOrder?.center?.name) return props.workOrder.center.name;
    return props.workOrder?.tenant?.name || page.props?.tenant?.trade_name || page.props?.tenant?.name || 'Carag';
});

const phone = computed(() => {
    return props.workOrder?.center?.phone || props.workOrder?.tenant?.phone || page.props?.auth?.center?.phone || page.props?.tenant?.phone || null;
});

const email = computed(() => {
    return props.workOrder?.center?.email || props.workOrder?.tenant?.email || page.props?.auth?.center?.email || page.props?.tenant?.email || null;
});

const crNumber = computed(() => {
    return props.workOrder?.tenant?.cr_number || page.props?.tenant?.cr_number || null;
});

const taxNumber = computed(() => {
    return props.workOrder?.tenant?.tax_number || page.props?.tenant?.tax_number || null;
});

const printDate = computed(() => {
    return new Date().toLocaleDateString(isRtl.value ? 'ar-SA' : 'en-US');
});
</script>
