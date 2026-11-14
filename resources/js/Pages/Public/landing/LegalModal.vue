<script setup>
import BaseModal from '@/Components/BaseModal.vue';
/**
 * Legal pages modal.
 */

defineProps({
    settings: { type: Object, required: true },
    isRtl: { type: Boolean, default: false },
    activeLegalPage: { type: String, default: null },
});

const emit = defineEmits(['close']);
</script>

<template>
    <BaseModal
        :show="!!activeLegalPage"
        size="lg"
        :title="
            activeLegalPage === 'terms'
                ? isRtl
                    ? 'الشروط والأحكام'
                    : 'Terms & Conditions'
                : isRtl
                  ? 'سياسة الخصوصية'
                  : 'Privacy Policy'
        "
        @close="emit('close')"
    >
        <div
            class="overflow-y-auto max-h-[60vh] leading-relaxed text-gray-700 dark:text-gray-300 whitespace-pre-line"
        >
            {{
                activeLegalPage === 'terms'
                    ? isRtl
                        ? settings.page_terms_ar
                        : settings.page_terms_en
                    : isRtl
                      ? settings.page_privacy_ar
                      : settings.page_privacy_en
            }}
        </div>
    </BaseModal>
</template>
