<template>
    <div class="print-engine-wrapper">
        <slot>
            <component :is="activeTemplate" v-bind="props" :documentType="normalizedDocType" />
        </slot>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import TemplateDefaultA4 from './PrintTemplates/TemplateDefaultA4.vue';
import TemplateThermal80 from './PrintTemplates/TemplateThermal80.vue';
import TemplateCustomersReport from './PrintTemplates/TemplateCustomersReport.vue';

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
        default: () => ({
            active_template: 'TemplateDefaultA4',
            show_logo: true,
            show_stamp: true,
            show_qr_code: true,
            primary_color: '#fbbf24',
            footer_text: '',
            show_currency_symbol: true,
            currency_display_style: 'text',
        }),
    },
    previewMode: {
        type: Boolean,
        default: false,
    },
});

const normalizedDocType = computed(() => {
    return props.documentType === 'quote' ? 'quotation' : props.documentType;
});

// Map active template name to imported template component
const templates = {
    TemplateDefaultA4,
    TemplateThermal80,
    TemplateModernA4: TemplateDefaultA4,
    TemplateSleekThermal: TemplateThermal80,
    TemplateCustomersReport,
};

// Resolve the active template component (document-specific template takes precedence over global visual default)
const activeTemplate = computed(() => {
    if (props.documentType === 'customers_report') {
        return TemplateCustomersReport;
    }
    const activeName =
        props.documentSettings?.template ||
        props.visualSettings?.active_template ||
        'TemplateDefaultA4';
    return templates[activeName] || TemplateDefaultA4;
});
</script>
