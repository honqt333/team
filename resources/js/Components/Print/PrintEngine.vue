<template>
    <div class="print-engine-wrapper">
        <component 
            :is="activeTemplate" 
            v-bind="props" 
            :documentType="normalizedDocType"
        />
    </div>
</template>

<script setup>
import { computed } from 'vue';
import TemplateDefaultA4 from './PrintTemplates/TemplateDefaultA4.vue';
import TemplateThermal80 from './PrintTemplates/TemplateThermal80.vue';

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
        default: () => ({
            active_template: 'TemplateDefaultA4',
            show_logo: true,
            show_stamp: true,
            show_qr_code: true,
            primary_color: '#fbbf24',
            footer_text: ''
        })
    },
    previewMode: {
        type: Boolean,
        default: false
    }
});

const normalizedDocType = computed(() => {
    return props.documentType === 'quote' ? 'quotation' : props.documentType;
});

// Map active template name to imported template component
const templates = {
    TemplateDefaultA4,
    TemplateThermal80,
    TemplateModernA4: TemplateDefaultA4,
    TemplateSleekThermal: TemplateThermal80
};

// Resolve the active template component
const activeTemplate = computed(() => {
    const activeName = props.visualSettings?.active_template || 'TemplateDefaultA4';
    return templates[activeName] || TemplateDefaultA4;
});
</script>
